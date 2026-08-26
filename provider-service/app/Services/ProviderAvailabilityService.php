<?php
namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderSchedule;
use Carbon\Carbon;

class ProviderAvailabilityService
{
    // Pas d'avance entre deux créneaux consécutifs (minutes)
    private const SLOT_STEP = 30;

    // ── Statut ouverture actuel ────────────────────────────────────────────────

    public function isOpenNow(Provider $provider): bool
    {
        $now = now();
        $s   = $provider->schedules()->where('day_of_week', $now->dayOfWeek)->first();
        if (!$s || $s->is_closed) return false;

        $time = $now->format('H:i:s');
        $open = $time >= $s->open_time && $time <= $s->close_time;

        if ($open && $s->break_start && $s->break_end) {
            if ($time >= $s->break_start && $time <= $s->break_end) return false;
        }
        return $open;
    }

    public function getOpenStatus(Provider $provider): array
    {
        $isOpen = $this->isOpenNow($provider);
        $next   = $this->getNextOpenTime($provider);
        $today  = $provider->schedules()->where('day_of_week', now()->dayOfWeek)->first();

        return [
            'is_open'     => $isOpen,
            'status'      => $isOpen ? 'Ouvert maintenant' : 'Fermé',
            'status_code' => $isOpen ? 'open' : 'closed',
            'hours_today' => $today && !$today->is_closed
                ? ($today->open_time.' – '.$today->close_time)
                : "Fermé aujourd'hui",
            'next_open'   => $next?->locale('fr')->isoFormat('dddd [à] HH:mm'),
        ];
    }

    public function getNextOpenTime(Provider $provider): ?Carbon
    {
        $now = now();
        for ($i = 1; $i <= 7; $i++) {
            $day = $now->copy()->addDays($i);
            $s   = $provider->schedules()
                ->where('day_of_week', $day->dayOfWeek)
                ->where('is_closed', false)->first();
            if ($s) return $day->setTimeFromTimeString($s->open_time);
        }
        return null;
    }

    public function getWeeklySchedule(Provider $provider): array
    {
        $names     = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        $schedules = $provider->schedules()->get()->keyBy('day_of_week');
        $result    = [];
        for ($day = 0; $day <= 6; $day++) {
            $s = $schedules->get($day);
            $result[] = [
                'day'         => $day,
                'day_name'    => $names[$day],
                'is_closed'   => !$s || $s->is_closed,
                'open_time'   => $s?->open_time,
                'close_time'  => $s?->close_time,
                'break_start' => $s?->break_start,
                'break_end'   => $s?->break_end,
                'is_today'    => $day === now()->dayOfWeek,
            ];
        }
        return $result;
    }

    // ── Créneaux disponibles pour une date donnée ─────────────────────────────

    /**
     * Retourne les créneaux horaires du planning pour un prestataire/date.
     * (sans filtrage des réservations — fait par le Booking Service)
     */
    public function getSlotsForDate(Provider $provider, string $date, int $durationMinutes = 60): array
    {
        $carbon = Carbon::parse($date);
        $dow    = $carbon->dayOfWeek;  // 0=dim, 1=lun, …

        $schedule = $provider->schedules()
            ->where('day_of_week', $dow)
            ->first();

        // Fermé ce jour
        if (!$schedule || $schedule->is_closed) {
            return [];
        }

        $slots = $this->generateSlots(
            $schedule->open_time,
            $schedule->close_time,
            $durationMinutes,
            $schedule->break_start,
            $schedule->break_end
        );

        return array_values($slots);
    }

    /**
     * Vérifie si le prestataire est censé être ouvert à un instant précis
     * (basé uniquement sur le planning hebdomadaire — sans tenir compte des réservations).
     */
    public function isScheduledOpen(Provider $provider, string $date, string $time): bool
    {
        $carbon = Carbon::parse($date);
        $schedule = $provider->schedules()
            ->where('day_of_week', $carbon->dayOfWeek)
            ->first();

        if (!$schedule || $schedule->is_closed) return false;

        $t = Carbon::parse($time);
        if ($t->format('H:i') < $schedule->open_time || $t->format('H:i') >= $schedule->close_time) {
            return false;
        }

        // Pendant la pause ?
        if ($schedule->break_start && $schedule->break_end) {
            if ($t->format('H:i') >= $schedule->break_start && $t->format('H:i') < $schedule->break_end) {
                return false;
            }
        }

        return true;
    }

    // ── Privé ─────────────────────────────────────────────────────────────────

    private function generateSlots(
        string  $openTime,
        string  $closeTime,
        int     $durationMinutes,
        ?string $breakStart = null,
        ?string $breakEnd   = null
    ): array {
        $slots  = [];
        $cursor = Carbon::parse($openTime);
        $close  = Carbon::parse($closeTime);

        while ($cursor->copy()->addMinutes($durationMinutes)->lte($close)) {
            $start   = $cursor->format('H:i');
            $end     = $cursor->copy()->addMinutes($durationMinutes)->format('H:i');

            // Exclure si le créneau chevauche la pause
            $duringBreak = false;
            if ($breakStart && $breakEnd) {
                $duringBreak = $start < $breakEnd && $end > $breakStart;
            }

            if (!$duringBreak) {
                $slots[] = [
                    'time'     => $start,
                    'end_time' => $end,
                ];
            }

            $cursor->addMinutes(self::SLOT_STEP);
        }

        return $slots;
    }
}
