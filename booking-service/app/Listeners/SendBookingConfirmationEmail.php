<?php
namespace App\Listeners;

use App\Events\BookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmationEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(BookingCreated $event): void
    {
        Log::channel('daily')->info('Email confirmation réservation', [
            'booking_id' => $event->booking->id,
            'user_id'    => $event->booking->user_id,
        ]);
        // TODO: Mailer::to(...)->send(new BookingConfirmationMail($event->booking));
    }
}
