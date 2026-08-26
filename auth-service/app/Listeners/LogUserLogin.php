<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogUserLogin implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function handle(UserLoggedIn $event): void
    {
        Log::channel('daily')->info('Connexion utilisateur', [
            'user_id'  => $event->user->id,
            'email'    => $event->user->email,
            'role'     => $event->user->role,
            'ip'       => $event->ipAddress,
            'at'       => now()->toISOString(),
        ]);
    }

    public function failed(UserLoggedIn $event, \Throwable $exception): void
    {
        Log::error('Échec log connexion', [
            'user_id' => $event->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
