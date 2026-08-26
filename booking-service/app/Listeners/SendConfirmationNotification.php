<?php
namespace App\Listeners;

use App\Events\BookingConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendConfirmationNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(BookingConfirmed $event): void
    {
        Log::channel('daily')->info('Notification confirmation', [
            'booking_id' => $event->booking->id,
        ]);
    }
}
