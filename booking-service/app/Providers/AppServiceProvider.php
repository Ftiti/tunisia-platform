<?php
namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Http\Clients\ProviderServiceClient;
use App\Listeners\ScheduleReminders;
use App\Listeners\SendBookingConfirmationEmail;
use App\Listeners\SendCancellationNotification;
use App\Listeners\SendConfirmationNotification;
use App\Repositories\BookingRepository;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository local (réservations)
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);

        // Client HTTP vers le Provider Service (singleton pour réutiliser la connexion)
        $this->app->singleton(ProviderServiceClient::class, fn() => new ProviderServiceClient());
    }

    public function boot(): void
    {
        Event::listen(BookingCreated::class,   SendBookingConfirmationEmail::class);
        Event::listen(BookingCreated::class,   ScheduleReminders::class);
        Event::listen(BookingConfirmed::class, SendConfirmationNotification::class);
        Event::listen(BookingCancelled::class, SendCancellationNotification::class);
    }
}
