<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $providers = Provider::with('services')->get();

        if ($providers->isEmpty()) {
            $this->command->warn('⚠️  Aucun prestataire trouvé. Lancez d\'abord ProviderSeeder.');
            return;
        }

        $statuses = ['pending', 'confirmed', 'completed', 'completed', 'completed', 'cancelled'];
        $userIds  = [1, 2, 3, 4, 5]; // IDs clients fictifs (table users de auth-service)
        $notes    = [
            'Merci de sonner à l\'interphone.',
            'Prévoir accès au disjoncteur général.',
            'Chien présent, ne pas s\'inquiéter.',
            null,
            'Appeler 10 minutes avant d\'arriver.',
            null,
        ];

        $bookings = 0;

        foreach ($providers as $provider) {
            $services = $provider->services;
            if ($services->isEmpty()) continue;

            // 3 à 5 réservations par prestataire
            $count = rand(3, 5);
            for ($i = 0; $i < $count; $i++) {
                /** @var Service $service */
                $service = $services->random();
                $status  = $statuses[array_rand($statuses)];

                // Dates dans les 60 derniers / prochains 30 jours
                $daysOffset = $status === 'completed' ? -rand(1, 60) : rand(1, 30);
                $date = now()->addDays($daysOffset)->format('Y-m-d');
                $hour = rand(8, 16);
                $time    = sprintf('%02d:00:00', $hour);
                $endHour = $hour + (int) ceil($service->duration_minutes / 60);
                $endTime = sprintf('%02d:00:00', min($endHour, 20));

                Booking::create([
                    'user_id'        => $userIds[array_rand($userIds)],
                    'provider_id'    => $provider->id,
                    'service_id'     => $service->id,
                    'scheduled_date' => $date,
                    'scheduled_time' => $time,
                    'end_time'       => $endTime,
                    'total_price'    => $service->price,
                    'status'         => $status,
                    'notes'          => $notes[array_rand($notes)],
                    'cancelled_at'   => $status === 'cancelled' ? now()->subDays(rand(1, 10)) : null,
                    'confirmed_at'   => in_array($status, ['confirmed', 'completed']) ? now()->subDays(rand(1, 5)) : null,
                    'completed_at'   => $status === 'completed' ? now()->subDays(rand(1, 30)) : null,
                ]);

                $bookings++;
            }
        }

        $this->command->info("✅ {$bookings} réservations créées.");
    }
}
