<?php
namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderCategory;
use App\Models\ProviderSchedule;
use App\Models\ProviderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProviderSeeder extends Seeder
{
    private array $cities = [
        'Tunis'    => ['lat' => 36.8065, 'lng' => 10.1815, 'gov' => 'Tunis'],
        'La Marsa' => ['lat' => 36.8782, 'lng' => 10.3246, 'gov' => 'Tunis'],
        'Ariana'   => ['lat' => 36.8665, 'lng' => 10.1647, 'gov' => 'Ariana'],
        'Sfax'     => ['lat' => 34.7398, 'lng' => 10.7600, 'gov' => 'Sfax'],
        'Sousse'   => ['lat' => 35.8256, 'lng' => 10.6369, 'gov' => 'Sousse'],
        'Nabeul'   => ['lat' => 36.4528, 'lng' => 10.7353, 'gov' => 'Nabeul'],
        'Bizerte'  => ['lat' => 37.2746, 'lng' => 9.8738,  'gov' => 'Bizerte'],
        'Monastir' => ['lat' => 35.7643, 'lng' => 10.8113, 'gov' => 'Monastir'],
        'Gabes'    => ['lat' => 33.8828, 'lng' => 10.0982, 'gov' => 'Gabes'],
        'Kairouan' => ['lat' => 35.6781, 'lng' => 10.0964, 'gov' => 'Kairouan'],
    ];

    public function run(): void
    {
        $data  = $this->getData();
        $count = 0;

        foreach ($data as $d) {
            $cat = ProviderCategory::where('slug', $d['cat'])->first();
            if (!$cat) continue;

            $loc  = $this->cities[$d['city']];
            $lat  = $loc['lat'] + (mt_rand(-400, 400) / 100000);
            $lng  = $loc['lng'] + (mt_rand(-400, 400) / 100000);
            $slug = Str::slug($d['name']);
            if (Provider::where('slug', $slug)->exists()) continue;

            $provider = Provider::create([
                'category_id'   => $cat->id,
                'name'          => $d['name'],
                'slug'          => $slug,
                'description'   => $d['desc'],
                'address'       => $d['address'],
                'city'          => $d['city'],
                'governorate'   => $loc['gov'],
                'phone'         => $d['phone'],
                'email'         => $d['email'] ?? null,
                'rating'        => $d['rating'],
                'total_reviews' => $d['reviews'],
                'is_active'     => true,
                'is_verified'   => $d['verified'] ?? false,
                'is_featured'   => $d['featured'] ?? false,
            ]);

            DB::statement("UPDATE providers SET location = ST_SetSRID(ST_Point(?,?),4326) WHERE id=?", [$lng,$lat,$provider->id]);

            foreach ($d['services'] as $s) {
                ProviderService::create([
                    'provider_id'      => $provider->id,
                    'name'             => $s['name'],
                    'price'            => $s['price'],
                    'price_max'        => $s['max'] ?? null,
                    'duration_minutes' => $s['dur'],
                    'unit'             => $s['unit'] ?? 'fixed',
                    'is_active'        => true,
                ]);
            }

            for ($day = 0; $day <= 6; $day++) {
                ProviderSchedule::create([
                    'provider_id' => $provider->id,
                    'day_of_week' => $day,
                    'open_time'   => '08:00:00',
                    'close_time'  => $day === 6 ? '13:00:00' : '18:00:00',
                    'break_start' => in_array($day,[1,2,3,4,5]) ? '12:00:00' : null,
                    'break_end'   => in_array($day,[1,2,3,4,5]) ? '13:30:00' : null,
                    'is_closed'   => ($day === 0),
                ]);
            }

            $count++;
        }

        $this->command->info("✅ {$count} prestataires créés avec coordonnées PostGIS.");
    }

    private function getData(): array
    {
        return [
            // Plombiers
            ['name'=>'Plomberie Ben Ali','cat'=>'plombier','city'=>'Tunis','address'=>'12 Rue de la République','phone'=>'71100001',
             'desc'=>'Plombier professionnel, urgences 24h/24.','rating'=>4.7,'reviews'=>143,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Réparation fuite','price'=>80,'dur'=>60],['name'=>'Débouchage','price'=>120,'dur'=>90],['name'=>'Installation chauffe-eau','price'=>350,'dur'=>180]]],

            ['name'=>'Plomb Service Sfax','cat'=>'plombier','city'=>'Sfax','address'=>'45 Avenue des Martyrs','phone'=>'74200001',
             'desc'=>'Expert plomberie sanitaire.','rating'=>4.5,'reviews'=>89,'verified'=>true,
             'services'=>[['name'=>'Fuite robinetterie','price'=>60,'dur'=>45],['name'=>'Pose baignoire','price'=>400,'dur'=>240]]],

            ['name'=>'Aqualink Sousse','cat'=>'plombier','city'=>'Sousse','address'=>'8 Rue Habib Thameur','phone'=>'73300001',
             'desc'=>'Plomberie générale.','rating'=>4.3,'reviews'=>67,
             'services'=>[['name'=>'Débouchage WC','price'=>80,'dur'=>60],['name'=>'Mitigeur','price'=>70,'dur'=>45]]],

            ['name'=>'Hydro Pro Nabeul','cat'=>'plombier','city'=>'Nabeul','address'=>'14 Avenue Bourguiba','phone'=>'72400001',
             'desc'=>'Plomberie et chauffage, 10 ans expérience.','rating'=>4.6,'reviews'=>112,'verified'=>true,
             'services'=>[['name'=>'Réparation tuyau','price'=>70,'dur'=>60],['name'=>'Chauffe-eau solaire','price'=>800,'dur'=>360]]],

            // Électriciens
            ['name'=>'Électro Pro Tunis','cat'=>'electricien','city'=>'Tunis','address'=>'33 Rue Alain Savary','phone'=>'71100002',
             'desc'=>'Électricien certifié.','rating'=>4.8,'reviews'=>201,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Tableau électrique','price'=>350,'dur'=>180],['name'=>'Prises et interrupteurs','price'=>50,'dur'=>45],['name'=>'Dépannage panne','price'=>70,'dur'=>60]]],

            ['name'=>'Voltage Sfax','cat'=>'electricien','city'=>'Sfax','address'=>'17 Bd de la Corniche','phone'=>'74200002',
             'desc'=>'Installations électriques résidentielles.','rating'=>4.6,'reviews'=>112,
             'services'=>[['name'=>'Clim installation','price'=>150,'dur'=>120],['name'=>'Mise aux normes','price'=>500,'dur'=>240]]],

            ['name'=>'ElecTech Bizerte','cat'=>'electricien','city'=>'Bizerte','address'=>'22 Rue du Port','phone'=>'72500001',
             'desc'=>'Electricien rapide et fiable.','rating'=>4.4,'reviews'=>78,
             'services'=>[['name'=>'Éclairage LED','price'=>80,'dur'=>60],['name'=>'Borne recharge VE','price'=>600,'dur'=>240]]],

            // Restaurants
            ['name'=>'Restaurant Le Carthage','cat'=>'restaurant','city'=>'Tunis','address'=>'15 Avenue de France','phone'=>'71100003',
             'email'=>'contact@lecarthage.tn','desc'=>'Cuisine tunisienne traditionnelle.','rating'=>4.9,'reviews'=>456,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Déjeuner','price'=>25,'dur'=>60],['name'=>'Dîner','price'=>40,'max'=>80,'dur'=>90]]],

            ['name'=>'La Médina Sousse','cat'=>'restaurant','city'=>'Sousse','address'=>'5 Rue Souk el Caïd','phone'=>'73300002',
             'desc'=>'Gastronomie tunisienne authentique.','rating'=>4.7,'reviews'=>289,'verified'=>true,
             'services'=>[['name'=>'Menu déjeuner','price'=>20,'dur'=>60],['name'=>'Banquet','price'=>500,'max'=>1500,'dur'=>180]]],

            ['name'=>'Dar Sfax','cat'=>'restaurant','city'=>'Sfax','address'=>'12 Rue de la Driba','phone'=>'74200003',
             'desc'=>'Spécialités sfaxiennes, poisson frais.','rating'=>4.6,'reviews'=>178,
             'services'=>[['name'=>'Poisson grillé','price'=>35,'dur'=>60],['name'=>'Couscous','price'=>28,'dur'=>60]]],

            ['name'=>'Chez Mounir Monastir','cat'=>'restaurant','city'=>'Monastir','address'=>'3 Place de la Liberté','phone'=>'73500001',
             'desc'=>'Restaurant familial, cuisine du terroir.','rating'=>4.5,'reviews'=>134,
             'services'=>[['name'=>'Plat du jour','price'=>18,'dur'=>45],['name'=>'Menu complet','price'=>30,'dur'=>60]]],

            // Coiffeurs
            ['name'=>'Salon Prestige Tunis','cat'=>'coiffeur','city'=>'Tunis','address'=>'88 Avenue Mohamed V','phone'=>'71100004',
             'desc'=>'Salon mixte, toutes techniques.','rating'=>4.8,'reviews'=>334,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Coupe homme','price'=>20,'dur'=>30],['name'=>'Coupe+couleur femme','price'=>120,'dur'=>120],['name'=>'Brushing','price'=>40,'dur'=>45]]],

            ['name'=>'Beauty Hair La Marsa','cat'=>'coiffeur','city'=>'La Marsa','address'=>'3 Rue du Port','phone'=>'71100005',
             'desc'=>'Spécialiste kératine et ombré.','rating'=>4.9,'reviews'=>412,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Kératine brésilienne','price'=>250,'dur'=>180],['name'=>'Balayage','price'=>180,'dur'=>150]]],

            ['name'=>'Studio Coiff Sfax','cat'=>'coiffeur','city'=>'Sfax','address'=>'56 Rue Farhat Hached','phone'=>'74200004',
             'desc'=>'Coiffure moderne prix accessibles.','rating'=>4.5,'reviews'=>156,
             'services'=>[['name'=>'Coupe+shampoing','price'=>30,'dur'=>45],['name'=>'Coloration','price'=>80,'dur'=>90]]],

            // Cafés
            ['name'=>'Café Saf-Saf Tunis','cat'=>'cafe','city'=>'Tunis','address'=>'2 Place du Gouvernement','phone'=>'71100006',
             'desc'=>'Café traditionnel depuis 1952.','rating'=>4.6,'reviews'=>521,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Café express','price'=>2,'dur'=>15],['name'=>'Thé menthe','price'=>3,'dur'=>15]]],

            ['name'=>'Café de la Mer Bizerte','cat'=>'cafe','city'=>'Bizerte','address'=>'1 Quai du Port','phone'=>'72500002',
             'desc'=>'Vue mer exceptionnelle.','rating'=>4.7,'reviews'=>234,
             'services'=>[['name'=>'Café+pâtisserie','price'=>8,'dur'=>30],['name'=>'Jus frais','price'=>5,'dur'=>15]]],

            ['name'=>'Café Kairouan','cat'=>'cafe','city'=>'Kairouan','address'=>'Place de la Médina','phone'=>'77400001',
             'desc'=>'Au cœur de la médina historique.','rating'=>4.8,'reviews'=>189,
             'services'=>[['name'=>'Café arabe','price'=>2,'dur'=>15],['name'=>'Makroudh+café','price'=>5,'dur'=>20]]],

            // Pharmacies
            ['name'=>'Pharmacie El Amal Tunis','cat'=>'pharmacie','city'=>'Tunis','address'=>'55 Avenue Bourguiba','phone'=>'71100007',
             'desc'=>'Pharmacie de garde, parapharmacie.','rating'=>4.5,'reviews'=>189,'verified'=>true,
             'services'=>[['name'=>'Conseil médicament','price'=>0,'dur'=>10],['name'=>'Livraison domicile','price'=>5,'dur'=>60]]],

            ['name'=>'Pharmacie Centrale Sfax','cat'=>'pharmacie','city'=>'Sfax','address'=>'10 Place de la République','phone'=>'74200005',
             'desc'=>'Grande pharmacie, stock complet.','rating'=>4.4,'reviews'=>145,
             'services'=>[['name'=>'Dispensaire','price'=>0,'dur'=>5]]],

            ['name'=>'Pharmacie Sousse Nord','cat'=>'pharmacie','city'=>'Sousse','address'=>'Av. 14 Janvier, Sousse','phone'=>'73300003',
             'desc'=>'Pharmacie ouverte nuit.','rating'=>4.3,'reviews'=>98,
             'services'=>[['name'=>'Consultation','price'=>0,'dur'=>10]]],

            // Médecins
            ['name'=>'Cabinet Dr. Mansour','cat'=>'medecin','city'=>'Tunis','address'=>'12 Rue Ibn Khaldoun','phone'=>'71100008',
             'email'=>'dr.mansour@clinique.tn','desc'=>'Médecin généraliste, RDV requis.','rating'=>4.8,'reviews'=>267,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Consultation générale','price'=>30,'dur'=>20],['name'=>'Certificat médical','price'=>15,'dur'=>15]]],

            ['name'=>'Polyclinique Sousse','cat'=>'medecin','city'=>'Sousse','address'=>'20 Av. de la Corniche','phone'=>'73300004',
             'desc'=>'Multi-spécialités.','rating'=>4.6,'reviews'=>198,'verified'=>true,
             'services'=>[['name'=>'Consultation spécialiste','price'=>50,'dur'=>30],['name'=>'Analyse sanguine','price'=>25,'dur'=>20]]],

            // Taxis
            ['name'=>'Taxi Express Tunis','cat'=>'taxi','city'=>'Tunis','address'=>'Station Barcelone','phone'=>'71100009',
             'desc'=>'Taxi conventionné 24h/24.','rating'=>4.3,'reviews'=>892,'verified'=>true,
             'services'=>[['name'=>'Course ville','price'=>5,'max'=>20,'dur'=>30],['name'=>'Aéroport Tunis','price'=>30,'dur'=>30]]],

            ['name'=>'VTC Confort Sfax','cat'=>'taxi','city'=>'Sfax','address'=>'Sfax Centre','phone'=>'74200006',
             'desc'=>'VTC climatisé, véhicules premium.','rating'=>4.7,'reviews'=>345,'verified'=>true,
             'services'=>[['name'=>'Intra-ville','price'=>8,'dur'=>20],['name'=>'Sfax–Tunis','price'=>150,'dur'=>180]]],

            // Mécaniciens
            ['name'=>'Garage Auto Plus Tunis','cat'=>'mecanicien','city'=>'Tunis','address'=>'Zone Industrielle Charguia','phone'=>'71100010',
             'desc'=>'Réparation toutes marques.','rating'=>4.5,'reviews'=>234,'verified'=>true,
             'services'=>[['name'=>'Vidange+filtre','price'=>80,'dur'=>60],['name'=>'Révision complète','price'=>250,'dur'=>240],['name'=>'Clim recharge','price'=>100,'dur'=>90]]],

            ['name'=>'Méca Service Sousse','cat'=>'mecanicien','city'=>'Sousse','address'=>'Zone Ind. Sousse','phone'=>'73300005',
             'desc'=>'Diagnostic électronique.','rating'=>4.4,'reviews'=>156,
             'services'=>[['name'=>'Diagnostic OBD','price'=>30,'dur'=>30],['name'=>'Freinage complet','price'=>200,'dur'=>120]]],

            // Livraison
            ['name'=>'Flash Livraison Tunis','cat'=>'livraison','city'=>'Tunis','address'=>'Tunis Centre','phone'=>'71100011',
             'desc'=>'Livraison express 1h dans Tunis.','rating'=>4.6,'reviews'=>789,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Livraison standard','price'=>8,'dur'=>60],['name'=>'Express 30min','price'=>15,'dur'=>30]]],

            ['name'=>'Rapid Delivery Sfax','cat'=>'livraison','city'=>'Sfax','address'=>'Sfax Ville','phone'=>'74200007',
             'desc'=>'Colis et documents.','rating'=>4.4,'reviews'=>432,
             'services'=>[['name'=>'Colis standard','price'=>7,'dur'=>90]]],

            // Hôtels
            ['name'=>'Hôtel Africa Tunis','cat'=>'hotel','city'=>'Tunis','address'=>'50 Av. Habib Bourguiba','phone'=>'71100012',
             'email'=>'reservation@hotelafricatunis.tn','desc'=>'Hôtel 5 étoiles centre Tunis.','rating'=>4.8,'reviews'=>1247,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Chambre standard','price'=>180,'dur'=>1440,'unit'=>'day'],['name'=>'Suite','price'=>400,'max'=>800,'dur'=>1440,'unit'=>'day']]],

            ['name'=>'Hôtel Sousse Palace','cat'=>'hotel','city'=>'Sousse','address'=>'Bd de la Corniche','phone'=>'73300006',
             'desc'=>'Bord de mer, piscine, spa.','rating'=>4.6,'reviews'=>892,'verified'=>true,
             'services'=>[['name'=>'Chambre vue mer','price'=>220,'dur'=>1440,'unit'=>'day'],['name'=>'Demi-pension','price'=>280,'dur'=>1440,'unit'=>'day']]],

            ['name'=>'Résidence Nabeul','cat'=>'hotel','city'=>'Nabeul','address'=>'Route Touristique','phone'=>'72400002',
             'desc'=>'Résidence touristique 4 étoiles.','rating'=>4.4,'reviews'=>456,
             'services'=>[['name'=>'Chambre double','price'=>120,'dur'=>1440,'unit'=>'day']]],

            // Dentistes
            ['name'=>'Cabinet Dr. Saida Dentiste','cat'=>'dentiste','city'=>'Tunis','address'=>'34 Avenue de Paris','phone'=>'71100013',
             'desc'=>'Dentisterie esthétique.','rating'=>4.9,'reviews'=>312,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Détartrage','price'=>50,'dur'=>45],['name'=>'Blanchiment','price'=>300,'dur'=>90],['name'=>'Couronne zircone','price'=>600,'dur'=>60]]],

            ['name'=>'Clinique Dentaire Sfax','cat'=>'dentiste','city'=>'Sfax','address'=>'11 Rue des Orangers','phone'=>'74200008',
             'desc'=>'Soins dentaires complets.','rating'=>4.6,'reviews'=>178,
             'services'=>[['name'=>'Consultation','price'=>25,'dur'=>20],['name'=>'Plombage','price'=>80,'dur'=>45]]],

            // Banques
            ['name'=>'STB Agence Tunis Centre','cat'=>'banque','city'=>'Tunis','address'=>'92 Av. Bourguiba','phone'=>'71100014',
             'desc'=>'Banque de dépôt, crédits.','rating'=>3.8,'reviews'=>456,'verified'=>true,
             'services'=>[['name'=>'Ouverture compte','price'=>0,'dur'=>30],['name'=>'Virement international','price'=>15,'dur'=>15]]],

            ['name'=>'BIAT Agence Sfax','cat'=>'banque','city'=>'Sfax','address'=>'Centre Commercial Sfax','phone'=>'74200009',
             'desc'=>'Services bancaires premium.','rating'=>4.2,'reviews'=>234,'verified'=>true,
             'services'=>[['name'=>'Conseil financier','price'=>0,'dur'=>30]]],

            // Gyms
            ['name'=>'FitZone Tunis','cat'=>'gym','city'=>'Tunis','address'=>'Centre Commercial Lac','phone'=>'71100015',
             'desc'=>'Salle moderne, coach perso.','rating'=>4.7,'reviews'=>567,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Abonnement mensuel','price'=>80,'dur'=>60],['name'=>'Séance coaching','price'=>40,'dur'=>60],['name'=>'CrossFit','price'=>15,'dur'=>60]]],

            ['name'=>'Sport Club Sousse','cat'=>'gym','city'=>'Sousse','address'=>'Bd de la Plage','phone'=>'73300007',
             'desc'=>'Gym premium, piscine, sauna.','rating'=>4.5,'reviews'=>345,
             'services'=>[['name'=>'Abonnement 3 mois','price'=>200,'dur'=>1440],['name'=>'Yoga','price'=>20,'dur'=>60]]],

            // Vétérinaires
            ['name'=>'Clinique Vétérinaire Tunis','cat'=>'veterinaire','city'=>'Tunis','address'=>'15 Rue Ibn Sina','phone'=>'71100016',
             'desc'=>'Urgences animales, généraliste.','rating'=>4.8,'reviews'=>234,'verified'=>true,
             'services'=>[['name'=>'Consultation','price'=>35,'dur'=>20],['name'=>'Vaccination','price'=>50,'dur'=>15],['name'=>'Stérilisation','price'=>250,'dur'=>120]]],

            // Avocats
            ['name'=>'Cabinet Maître Hamdi','cat'=>'avocat','city'=>'Tunis','address'=>'Cité des Avocats Tunis','phone'=>'71100017',
             'email'=>'contact@hamdi-avocat.tn','desc'=>'Droit des affaires, droit famille.','rating'=>4.7,'reviews'=>156,'verified'=>true,
             'services'=>[['name'=>'Consultation juridique','price'=>100,'dur'=>60],['name'=>'Rédaction contrat','price'=>300,'max'=>800,'dur'=>120]]],

            // Boulangeries
            ['name'=>'Boulangerie El Khobz','cat'=>'boulangerie','city'=>'Tunis','address'=>'22 Rue du Commerce','phone'=>'71100018',
             'desc'=>'Pain chaud toute la journée.','rating'=>4.6,'reviews'=>678,'verified'=>true,
             'services'=>[['name'=>'Pain baguette','price'=>0.5,'dur'=>5],['name'=>'Pâtisseries','price'=>2,'max'=>8,'dur'=>5]]],

            ['name'=>'Fournil de Sfax','cat'=>'boulangerie','city'=>'Sfax','address'=>'Marché Central Sfax','phone'=>'74200010',
             'desc'=>'Pains traditionnels et viennoiseries.','rating'=>4.5,'reviews'=>234,
             'services'=>[['name'=>'Tabouna','price'=>1,'dur'=>5],['name'=>'Croissant','price'=>1.5,'dur'=>5]]],

            // Supermarchés
            ['name'=>'Monoprix Tunis Lac','cat'=>'supermarche','city'=>'Tunis','address'=>'Les Berges du Lac','phone'=>'71100019',
             'desc'=>'Supermarché premium.','rating'=>4.2,'reviews'=>1089,'verified'=>true,'featured'=>true,
             'services'=>[['name'=>'Livraison à domicile','price'=>5,'dur'=>120]]],

            ['name'=>'MG Market Sfax','cat'=>'supermarche','city'=>'Sfax','address'=>'Route de Tunis, Sfax','phone'=>'74200011',
             'desc'=>'Grande surface, rayon frais.','rating'=>4.0,'reviews'=>567,
             'services'=>[['name'=>'Drive','price'=>0,'dur'=>30]]],

            // Comptables
            ['name'=>'Cabinet Comptable Ben Salah','cat'=>'comptable','city'=>'Tunis','address'=>'Tour Utica Tunis','phone'=>'71100020',
             'email'=>'bensalah@compta.tn','desc'=>'Expert-comptable certifié.','rating'=>4.8,'reviews'=>89,'verified'=>true,
             'services'=>[['name'=>'Déclaration fiscale','price'=>200,'dur'=>60],['name'=>'Création entreprise','price'=>500,'dur'=>120],['name'=>'Audit','price'=>1500,'max'=>5000,'dur'=>480,'unit'=>'day']]],

            // Écoles
            ['name'=>'École Les Étoiles La Marsa','cat'=>'ecole','city'=>'La Marsa','address'=>'5 Rue de l\'École La Marsa','phone'=>'71100021',
             'desc'=>'Primaire+collège bilingue fr/ar.','rating'=>4.7,'reviews'=>234,'verified'=>true,
             'services'=>[['name'=>'Inscription primaire','price'=>2500,'dur'=>30],['name'=>'Soutien scolaire','price'=>40,'dur'=>60]]],
        ];
    }
}
