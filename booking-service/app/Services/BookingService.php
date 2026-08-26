<?php
namespace App\Services;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Http\Clients\ProviderServiceClient;
use App\Models\Booking;
use App\Models\BookingReminder;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    private const MIN_CANCEL_HOURS = 2;

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly AvailabilityService        $availabilityService,
        private readonly ProviderServiceClient      $providerClient,
    ) {}

    /**
     * Créer une réservation.
     *
     * Flux :
     *  1. Vérifier que le prestataire existe dans le Provider Service
     *  2. Vérifier que le service existe et appartient au prestataire
     *  3. Vérifier la disponibilité (planning + pas de conflit local)
     *  4. Créer la réservation et les rappels
     */
    public function createBooking(array $data): Booking
    {
        // ── 1. Vérifier le prestataire ─────────────────────────────────────────
        $provider = $this->providerClient->getProvider($data['provider_id']);
        if (!$provider) {
            throw ValidationException::withMessages([
                'provider_id' => ['Prestataire introuvable dans le Provider Service.'],
            ]);
        }

        // ── 2. Vérifier le service ─────────────────────────────────────────────
        $service = $this->providerClient->getService($data['provider_id'], $data['service_id']);
        if (!$service) {
            throw ValidationException::withMessages([
                'service_id' => ['Service introuvable pour ce prestataire.'],
            ]);
        }

        // ── 3. Vérifier la disponibilité ───────────────────────────────────────
        if (!$this->availabilityService->isSlotAvailable(
            $data['provider_id'],
            $data['service_id'],
            $data['scheduled_date'],
            $data['scheduled_time']
        )) {
            throw ValidationException::withMessages([
                'scheduled_time' => ['Ce créneau n\'est plus disponible.'],
            ]);
        }

        // ── 4. Créer la réservation ────────────────────────────────────────────
        return DB::transaction(function () use ($data, $service) {
            $durationMinutes = (int) ($service['duration_minutes'] ?? 60);
            $price           = (float) ($service['price'] ?? 0);

            $endTime = Carbon::parse($data['scheduled_time'])
                ->addMinutes($durationMinutes)
                ->format('H:i');

            $booking = $this->bookingRepo->create([
                'user_id'        => $data['user_id'],
                'provider_id'    => $data['provider_id'],
                'service_id'     => $data['service_id'],
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'],
                'end_time'       => $endTime,
                'status'         => 'pending',
                'total_price'    => $price,
                'notes'          => $data['notes'] ?? null,
            ]);

            // Invalider le cache de disponibilités
            $this->availabilityService->invalidateCache(
                $data['provider_id'],
                $data['scheduled_date']
            );

            // Programmer les rappels (24h et 1h avant)
            $this->scheduleReminders($booking);

            event(new BookingCreated($booking));

            return $booking;
        });
    }

    /**
     * Confirmer une réservation (statut pending → confirmed).
     */
    public function confirmBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepo->findById($bookingId);

        if (!$booking) {
            abort(404, 'Réservation introuvable.');
        }
        if (!$booking->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Seules les réservations en attente peuvent être confirmées.'],
            ]);
        }

        $booking = $this->bookingRepo->update($bookingId, [
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        event(new BookingConfirmed($booking));

        return $booking;
    }

    /**
     * Annuler une réservation (délai minimum MIN_CANCEL_HOURS avant le RDV).
     */
    public function cancelBooking(int $bookingId, string $reason, int $userId): Booking
    {
        $booking = $this->bookingRepo->findById($bookingId);

        if (!$booking) {
            abort(404, 'Réservation introuvable.');
        }
        if ($booking->isCancelled() || $booking->isCompleted()) {
            throw ValidationException::withMessages([
                'status' => ['Cette réservation ne peut pas être annulée.'],
            ]);
        }

        $scheduledAt = Carbon::parse(
            $booking->scheduled_date->format('Y-m-d').' '.$booking->scheduled_time
        );

        if ($scheduledAt->diffInHours(now(), false) > -self::MIN_CANCEL_HOURS) {
            throw ValidationException::withMessages([
                'scheduled_time' => [
                    'L\'annulation doit se faire au moins '.self::MIN_CANCEL_HOURS.'h avant le rendez-vous.'
                ],
            ]);
        }

        $booking = $this->bookingRepo->update($bookingId, [
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);

        $this->availabilityService->invalidateCache(
            $booking->provider_id,
            $booking->scheduled_date->format('Y-m-d')
        );

        event(new BookingCancelled($booking));

        return $booking;
    }

    /**
     * Marquer une réservation comme terminée.
     */
    public function completeBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepo->findById($bookingId);

        if (!$booking) {
            abort(404, 'Réservation introuvable.');
        }
        if (!$booking->isConfirmed()) {
            throw ValidationException::withMessages([
                'status' => ['Seules les réservations confirmées peuvent être terminées.'],
            ]);
        }

        return $this->bookingRepo->update($bookingId, [
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }

    // ── Privé ─────────────────────────────────────────────────────────────────

    private function scheduleReminders(Booking $booking): void
    {
        $scheduledAt = Carbon::parse(
            $booking->scheduled_date->format('Y-m-d').' '.$booking->scheduled_time
        );

        foreach ([
            ['offset' => -1440, 'type' => 'email'],  // 24h avant
            ['offset' => -60,   'type' => 'push'],    // 1h avant
        ] as $r) {
            $remindAt = $scheduledAt->copy()->addMinutes($r['offset']);
            if ($remindAt->isFuture()) {
                BookingReminder::create([
                    'booking_id' => $booking->id,
                    'remind_at'  => $remindAt,
                    'type'       => $r['type'],
                    'is_sent'    => false,
                ]);
            }
        }
    }
}
