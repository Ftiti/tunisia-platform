<?php
namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(private readonly Booking $model) {}

    public function create(array $data): Booking
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Booking
    {
        // Plus de with(['provider','service']) — enrichissement via ProviderServiceClient
        return $this->model->with(['reminders'])->find($id);
    }

    public function update(int $id, array $data): Booking
    {
        $booking = $this->model->findOrFail($id);
        $booking->update($data);
        return $booking->fresh();
    }

    public function getByFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date'])) {
            $query->whereDate('scheduled_date', $filters['date']);
        }

        $perPageInt = (int) ($filters['per_page'] ?? $perPage);

        return $query->latest('scheduled_date')->paginate($perPageInt);
    }

    /**
     * Compte les réservations actives qui chevauchent un créneau donné.
     * Utilisé pour vérifier les conflits avant de créer une nouvelle réservation.
     */
    public function getActiveBookingsForSlot(
        int    $providerId,
        string $date,
        string $time,
        string $endTime,
        ?int   $excludeId = null
    ): int {
        return $this->model
            ->where('provider_id', $providerId)
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where(function ($q) use ($time, $endTime) {
                // Chevauchement : [time, endTime[ chevauche [scheduled_time, end_time[
                $q->where('scheduled_time', '<', $endTime)
                  ->where('end_time', '>', $time);
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->count();
    }
}
