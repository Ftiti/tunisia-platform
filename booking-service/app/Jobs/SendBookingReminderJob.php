<?php
namespace App\Jobs;

use App\Models\BookingReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBookingReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $reminderId) {}

    public function handle(): void
    {
        $reminder = BookingReminder::with('booking')->find($this->reminderId);

        if (! $reminder || $reminder->is_sent) {
            return;
        }

        if ($reminder->booking->isCancelled()) {
            return;
        }

        Log::channel('daily')->info('Envoi rappel réservation', [
            'reminder_id' => $reminder->id,
            'type'        => $reminder->type,
            'booking_id'  => $reminder->booking_id,
        ]);

        $reminder->update(['is_sent' => true, 'sent_at' => now()]);
    }
}
