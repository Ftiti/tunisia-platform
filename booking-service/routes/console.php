<?php
use App\Jobs\SendBookingReminderJob;
use App\Models\BookingReminder;
use Illuminate\Support\Facades\Schedule;

// Envoyer les rappels de réservation toutes les 5 minutes
Schedule::call(function () {
    BookingReminder::where('is_sent', false)
        ->where('remind_at', '<=', now())
        ->get()
        ->each(fn($r) => SendBookingReminderJob::dispatch($r->id));
})->everyFiveMinutes()->name('send-booking-reminders');
