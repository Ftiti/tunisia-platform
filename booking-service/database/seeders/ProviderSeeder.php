<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name'        => 'Ahmed Plomberie Pro',
                'email'       => 'ahmed.plomberie@example.com',
                'phone'       => '22100001',
                'address'     => '12 Rue de la République, Tunis',
                'city'        => 'Tunis',
                'category'    => 'Plomberie',
                'description' => 'Plombier professionnel avec 10 ans d\'expérience. Disponible 7j/7.',
                'rating'      => 4.7,
                'reviews'     => 143,
                'services'    => [
                    ['name' => 'Réparation fuite', 'price' => 80.000,  'duration' => 60,  'desc' => 'Réparation de fuites d\'eau (robinets, tuyaux, joints)'],
                    ['name' => 'Débouchage urgence', 'price' => 120.000, 'duration' => 90,  'desc' => 'Débouchage canalisations et évacuations bouchées'],
                    ['name' => 'Installation lavabo', 'price' => 150.000, 'duration' => 120, 'desc' => 'Pose et raccordement de lavabo, évier ou douche'],
                ],
            ],
            [
                'name'        => 'Électro-Service Karim',
                'email'       => 'karim.elec@example.com',
                'phone'       => '22100002',
                'address'     => '45 Avenue Habib Bourguiba, Sfax',
                'city'        => 'Sfax',
                'category'    => 'Électricité',
                'description' => 'Électricien certifié, intervention rapide pour tous travaux électriques.',
                'rating'      => 4.5,
                'reviews'     => 89,
                'services'    => [
                    ['name' => 'Dépannage électrique', 'price' => 70.000,  'duration' => 60,  'desc' => 'Diagnostic et réparation pannes électriques'],
                    ['name' => 'Pose tableau électrique', 'price' => 350.000, 'duration' => 180, 'desc' => 'Installation ou remplacement tableau électrique'],
                    ['name' => 'Pose prises & interrupteurs', 'price' => 50.000, 'duration' => 45,  'desc' => 'Installation prises, interrupteurs et luminaires'],
                ],
            ],
            [
                'name'        => 'Peinture & Décor Sami',
                'email'       => 'sami.peinture@example.com',
                'phone'       => '22100003',
                'address'     => '8 Rue Ibn Khaldoun, Sousse',
                'city'        => 'Sousse',
                'category'    => 'Peinture',
                'description' => 'Peintre décorateur, travaux soignés et finitions impeccables.',
                'rating'      => 4.8,
                'reviews'     => 212,
                'services'    => [
                    ['name' => 'Peinture intérieure (pièce)', 'price' => 200.000, 'duration' => 240, 'desc' => 'Peinture complète d\'une pièce (préparation + 2 couches)'],
                    ['name' => 'Enduit décoratif', 'price' => 180.000, 'duration' => 180, 'desc' => 'Application enduit tadelakt, béton ciré ou stucco'],
                    ['name' => 'Ravalement façade', 'price' => 500.000, 'duration' => 480, 'desc' => 'Nettoyage, préparation et peinture façade extérieure'],
                ],
            ],
            [
                'name'        => 'ClimPro Techni',
                'email'       => 'techni.clim@example.com',
                'phone'       => '22100004',
                'address'     => '31 Avenue de la Liberté, Monastir',
                'city'        => 'Monastir',
                'category'    => 'Climatisation',
                'description' => 'Spécialiste en installation et maintenance climatisation toutes marques.',
                'rating'      => 4.6,
                'reviews'     => 167,
                'services'    => [
                    ['name' => 'Installation climatiseur', 'price' => 280.000, 'duration' => 180, 'desc' => 'Pose complète d\'un climatiseur (unité intérieure + extérieure)'],
                    ['name' => 'Entretien annuel', 'price' => 80.000,  'duration' => 60,  'desc' => 'Nettoyage filtre, contrôle fluide frigorigène, test'],
                    ['name' => 'Dépannage clim', 'price' => 100.000, 'duration' => 90,  'desc' => 'Diagnostic et réparation panne climatisation'],
                ],
            ],
            [
                'name'        => 'Ménage Express Fatima',
                'email'       => 'fatima.menage@example.com',
                'phone'       => '22100005',
                'address'     => '17 Rue du Commerce, Bizerte',
                'city'        => 'Bizerte',
                'category'    => 'Nettoyage',
                'description' => 'Service de ménage professionnel à domicile. Produits fournis, résultat garanti.',
                'rating'      => 4.9,
                'reviews'     => 305,
                'services'    => [
                    ['name' => 'Ménage standard (3h)', 'price' => 60.000,  'duration' => 180, 'desc' => 'Nettoyage cuisine, salle de bain, salon, chambres'],
                    ['name' => 'Grand ménage (6h)', 'price' => 110.000, 'duration' => 360, 'desc' => 'Nettoyage complet incluant placards, vitres et terrasse'],
                    ['name' => 'Nettoyage après travaux', 'price' => 200.000, 'duration' => 480, 'desc' => 'Débarras et nettoyage complet post-chantier'],
                ],
            ],
        ];

        foreach ($providers as $data) {
            $category = Category::where('name', $data['category'])->first();

            /** @var Provider $provider */
            $provider = Provider::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'         => $data['name'],
                    'phone'        => $data['phone'],
                    'address'      => $data['address'],
                    'city'         => $data['city'],
                    'category_id'  => $category?->id,
                    'description'  => $data['description'],
                    'rating'       => $data['rating'],
                    'total_reviews'=> $data['reviews'],
                    'is_active'    => true,
                    'is_verified'  => true,
                ]
            );

            // Services
            foreach ($data['services'] as $svc) {
                Service::firstOrCreate(
                    ['provider_id' => $provider->id, 'name' => $svc['name']],
                    [
                        'description'      => $svc['desc'],
                        'price'            => $svc['price'],
                        'duration_minutes' => $svc['duration'],
                        'is_active'        => true,
                    ]
                );
            }

            // Horaires Lun–Sam 08:00–18:00, Dim fermé
            for ($day = 0; $day <= 6; $day++) {
                Schedule::firstOrCreate(
                    ['provider_id' => $provider->id, 'day_of_week' => $day],
                    [
                        'open_time'  => '08:00:00',
                        'close_time' => '18:00:00',
                        'is_closed'  => ($day === 0), // dimanche fermé
                    ]
                );
            }
        }

        $this->command->info('✅ ' . Provider::count() . ' prestataires créés avec services et horaires.');
    }
}
