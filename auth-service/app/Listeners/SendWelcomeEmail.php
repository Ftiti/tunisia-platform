<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

// ShouldQueue → traitement asynchrone via Redis/RabbitMQ
class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public function handle(UserRegistered $event): void
    {
        Log::info('Email de bienvenue envoyé', [
            'user_id' => $event->user->id,
            'email'   => $event->user->email,
        ]);

        // TODO: décommenter quand le Mailable WelcomeMail sera créé
        // Mail::to($event->user->email)->send(new WelcomeMail($event->user));
    }

    public function failed(UserRegistered $event, \Throwable $exception): void
    {
        Log::error('Échec envoi email de bienvenue', [
            'user_id' => $event->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
