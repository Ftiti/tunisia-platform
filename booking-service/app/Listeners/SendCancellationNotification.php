<?php
namespace App\Listeners;

use App\Events\BookingCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendCancellationNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(BookingCancelled $event): void
    {
        Log::channel('daily')->info('Notification annulation', [
            'booking_id' => $event->booking->id,
        ]);
    }
}
