<?php
namespace App\Listeners;

use App\Events\BookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ScheduleReminders implements ShouldQueue
{
    public function handle(BookingCreated $event): void
    {
        Log::channel('daily')->info('Rappels programmés', [
            'booking_id' => $event->booking->id,
        ]);
        // Les rappels sont déjà créés en BDD par BookingService::scheduleReminders()
    }
}
