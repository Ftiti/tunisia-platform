<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Plomberie',       'icon' => '🔧', 'children' => ['Fuite d\'eau', 'Installation sanitaire', 'Débouchage canalisation']],
            ['name' => 'Électricité',     'icon' => '⚡', 'children' => ['Tableau électrique', 'Prise & interrupteur', 'Éclairage']],
            ['name' => 'Peinture',        'icon' => '🎨', 'children' => ['Peinture intérieure', 'Peinture extérieure', 'Enduit & ragréage']],
            ['name' => 'Menuiserie',      'icon' => '🪚', 'children' => ['Portes & fenêtres', 'Parquet & plancher', 'Mobilier sur mesure']],
            ['name' => 'Climatisation',   'icon' => '❄️', 'children' => ['Installation climatiseur', 'Entretien & nettoyage', 'Dépannage clim']],
            ['name' => 'Jardinage',       'icon' => '🌿', 'children' => ['Tonte de pelouse', 'Taille de haies', 'Aménagement paysager']],
            ['name' => 'Nettoyage',       'icon' => '🧹', 'children' => ['Ménage à domicile', 'Nettoyage de vitres', 'Nettoyage après travaux']],
            ['name' => 'Déménagement',    'icon' => '📦', 'children' => ['Déménagement local', 'Déménagement longue distance', 'Montage de meubles']],
            ['name' => 'Informatique',    'icon' => '💻', 'children' => ['Dépannage PC', 'Installation réseau', 'Récupération données']],
            ['name' => 'Maçonnerie',      'icon' => '🧱', 'children' => ['Carrelage', 'Rénovation façade', 'Construction extension']],
        ];

        foreach ($categories as $cat) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name'      => $cat['name'],
                    'icon'      => $cat['icon'],
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );

            foreach ($cat['children'] as $childName) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name'      => $childName,
                        'icon'      => null,
                        'parent_id' => $parent->id,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ ' . Category::count() . ' catégories créées.');
    }
}
