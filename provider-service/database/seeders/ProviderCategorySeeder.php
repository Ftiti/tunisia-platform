<?php
namespace Database\Seeders;

use App\Models\ProviderCategory;
use Illuminate\Database\Seeder;

class ProviderCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name'=>'Plombier',    'slug'=>'plombier',    'icon'=>'🔧','color'=>'#3B82F6','order'=>1],
            ['name'=>'Électricien', 'slug'=>'electricien', 'icon'=>'⚡','color'=>'#F59E0B','order'=>2],
            ['name'=>'Pharmacie',   'slug'=>'pharmacie',   'icon'=>'💊','color'=>'#10B981','order'=>3],
            ['name'=>'Médecin',     'slug'=>'medecin',     'icon'=>'🏥','color'=>'#EF4444','order'=>4],
            ['name'=>'Restaurant',  'slug'=>'restaurant',  'icon'=>'🍽️','color'=>'#F97316','order'=>5],
            ['name'=>'Café',        'slug'=>'cafe',        'icon'=>'☕','color'=>'#92400E','order'=>6],
            ['name'=>'Coiffeur',    'slug'=>'coiffeur',    'icon'=>'✂️','color'=>'#8B5CF6','order'=>7],
            ['name'=>'Mécanicien',  'slug'=>'mecanicien',  'icon'=>'🔨','color'=>'#6B7280','order'=>8],
            ['name'=>'Avocat',      'slug'=>'avocat',      'icon'=>'⚖️','color'=>'#1F2937','order'=>9],
            ['name'=>'Comptable',   'slug'=>'comptable',   'icon'=>'📊','color'=>'#065F46','order'=>10],
            ['name'=>'Livraison',   'slug'=>'livraison',   'icon'=>'🚚','color'=>'#DC2626','order'=>11],
            ['name'=>'Supermarché', 'slug'=>'supermarche', 'icon'=>'🛒','color'=>'#059669','order'=>12],
            ['name'=>'Boulangerie', 'slug'=>'boulangerie', 'icon'=>'🥖','color'=>'#D97706','order'=>13],
            ['name'=>'Taxi',        'slug'=>'taxi',        'icon'=>'🚕','color'=>'#FBBF24','order'=>14],
            ['name'=>'Hôtel',       'slug'=>'hotel',       'icon'=>'🏨','color'=>'#7C3AED','order'=>15],
            ['name'=>'Dentiste',    'slug'=>'dentiste',    'icon'=>'🦷','color'=>'#2563EB','order'=>16],
            ['name'=>'Vétérinaire', 'slug'=>'veterinaire', 'icon'=>'🐾','color'=>'#16A34A','order'=>17],
            ['name'=>'Banque',      'slug'=>'banque',      'icon'=>'🏦','color'=>'#1D4ED8','order'=>18],
            ['name'=>'École',       'slug'=>'ecole',       'icon'=>'📚','color'=>'#7C3AED','order'=>19],
            ['name'=>'Gym',         'slug'=>'gym',         'icon'=>'💪','color'=>'#EF4444','order'=>20],
        ];

        foreach ($categories as $cat) {
            ProviderCategory::firstOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }

        $this->command->info('✅ '.ProviderCategory::count().' catégories créées.');
    }
}
