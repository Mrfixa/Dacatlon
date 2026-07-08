<?php

namespace Modules\VehicleManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds a comprehensive, US-market catalogue of vehicle categories, brands and models so the
 * driver vehicle-registration dropdowns are populated out of the box with every make sold in
 * the United States (current line-ups + common discontinued makes/models still on the road).
 *
 * Granularity is make + model (no model-year): the registration form captures brand, model,
 * category, licence plate, licence expiry and VIN — there is no year field — so make + model
 * is exactly what the searchable Brand → Model dropdowns need.
 *
 * Idempotent: existing rows are left in place (only re-activated); never mutates a primary key,
 * so brand_id foreign links stay intact. The driver brand/model API filters is_active = 1, so
 * everything here is seeded active. Safe to re-run to pick up newly-added makes/models.
 *
 * Run: php artisan db:seed --class="Modules\VehicleManagement\Database\Seeders\VehicleBrandModelSeeder"
 */
class VehicleBrandModelSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ---- Vehicle categories (type must be car|motor_bike) ----
        if (Schema::hasTable('vehicle_categories')) {
            $categories = [
                ['Sedan', 'car'], ['SUV', 'car'], ['Hatchback', 'car'], ['Coupe', 'car'],
                ['Convertible', 'car'], ['Wagon', 'car'], ['Minivan', 'car'], ['Pickup', 'car'],
                ['Luxury', 'car'], ['Electric', 'car'], ['Hybrid', 'car'], ['Van', 'car'],
                ['Crossover', 'car'], ['Motorbike', 'motor_bike'], ['Other', 'car'],
            ];
            foreach ($categories as [$name, $type]) {
                $this->ensureRow('vehicle_categories', ['name' => $name], [
                    'description' => $name . ' vehicles',
                    'image' => '',
                    'type' => $type,
                ], $now);
            }
        }

        // ---- Brands -> models ----
        foreach ($this->brands() as $brandName => $models) {
            // Guarantee an "Other" model on every make so a driver whose exact model/trim/year
            // isn't catalogued is never blocked — they pick <Make> -> Other and still submit
            // plate + VIN, which uniquely identify the vehicle. Keeps the flow unblockable.
            $models[] = 'Other';
            $models = array_values(array_unique($models));

            $brandId = DB::table('vehicle_brands')->where('name', $brandName)->value('id');
            if (!$brandId) {
                $brandId = (string) Str::uuid();
                DB::table('vehicle_brands')->insert([
                    'id' => $brandId,
                    'name' => $brandName,
                    'description' => $brandName . ' vehicles',
                    'image' => '',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('vehicle_brands')->where('id', $brandId)->update(['is_active' => 1, 'updated_at' => $now]);
            }

            foreach ($models as $modelName) {
                $exists = DB::table('vehicle_models')->where('name', $modelName)->where('brand_id', $brandId)->exists();
                if (!$exists) {
                    DB::table('vehicle_models')->insert([
                        'id' => (string) Str::uuid(),
                        'name' => $modelName,
                        'brand_id' => $brandId,
                        'seat_capacity' => 4,
                        'maximum_weight' => 500,
                        'hatch_bag_capacity' => 2,
                        'engine' => '1500',
                        'description' => $brandName . ' ' . $modelName,
                        'image' => '',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('vehicle_models')->where('name', $modelName)->where('brand_id', $brandId)->update(['is_active' => 1, 'updated_at' => $now]);
                }
            }
        }
    }

    private function ensureRow(string $table, array $key, array $extra, $now): void
    {
        if (!DB::table($table)->where($key)->exists()) {
            DB::table($table)->insert(array_merge($key, $extra, [
                'id' => (string) Str::uuid(),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        } else {
            DB::table($table)->where($key)->update(['is_active' => 1, 'updated_at' => $now]);
        }
    }

    /**
     * Every make sold in the US market from 2005 through today, with each make's full
     * 2005-2026 US nameplate history (make + model, no model-year), plus a catch-all
     * "Other" brand. run() appends an "Other" model to every make.
     */
    private function brands(): array
    {
        return [
            'Acura' => ['ILX', 'TLX', 'RLX', 'TL', 'TSX', 'RL', 'RSX', 'Integra', 'RDX', 'MDX', 'ZDX', 'NSX'],
            'Alfa Romeo' => ['Giulia', 'Stelvio', 'Tonale', '4C', '8C Competizione'],
            'Aston Martin' => ['DB9', 'DB11', 'DB12', 'DBS', 'Vantage', 'V8 Vantage', 'V12 Vantage', 'Vanquish', 'Virage', 'Rapide', 'DBX', 'Valkyrie'],
            'Audi' => ['A3', 'A4', 'A4 allroad', 'A5', 'A6', 'A6 allroad', 'A7', 'A8', 'Q3', 'Q4 e-tron', 'Q5', 'Q7', 'Q8', 'Q8 e-tron', 'e-tron', 'e-tron GT', 'TT', 'R8', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'RS3', 'RS5', 'RS6 Avant', 'RS7', 'SQ5', 'SQ7', 'SQ8'],
            'Bentley' => ['Continental GT', 'Continental Flying Spur', 'Flying Spur', 'Bentayga', 'Mulsanne', 'Azure', 'Brooklands', 'Arnage'],
            'BMW' => ['1 Series', '2 Series', '3 Series', '4 Series', '5 Series', '6 Series', '7 Series', '8 Series', 'X1', 'X2', 'X3', 'X4', 'X5', 'X6', 'X7', 'XM', 'Z4', 'i3', 'i4', 'i5', 'i7', 'i8', 'iX', 'M2', 'M3', 'M4', 'M5', 'M6', 'M8', 'X3 M', 'X4 M', 'X5 M', 'X6 M'],
            'Buick' => ['Enclave', 'Encore', 'Encore GX', 'Envision', 'Envista', 'LaCrosse', 'Lucerne', 'Regal', 'Verano', 'Cascada', 'Rainier', 'Rendezvous', 'Terraza', 'LeSabre'],
            'Cadillac' => ['ATS', 'CTS', 'CT4', 'CT5', 'CT6', 'STS', 'DTS', 'DeVille', 'XLR', 'SRX', 'XT4', 'XT5', 'XT6', 'XTS', 'Escalade', 'Escalade ESV', 'Escalade EXT', 'ELR', 'Lyriq', 'Celestiq'],
            'Chevrolet' => ['Astro', 'Avalanche', 'Aveo', 'Blazer', 'Blazer EV', 'Bolt EV', 'Bolt EUV', 'Camaro', 'Caprice', 'Captiva Sport', 'City Express', 'Cobalt', 'Colorado', 'Corvette', 'Cruze', 'Equinox', 'Equinox EV', 'Express', 'HHR', 'Impala', 'Malibu', 'Monte Carlo', 'Silverado 1500', 'Silverado 2500HD', 'Silverado 3500HD', 'Silverado EV', 'Sonic', 'Spark', 'SS', 'SSR', 'Suburban', 'Tahoe', 'TrailBlazer', 'Trailblazer', 'Traverse', 'Trax', 'Uplander', 'Venture', 'Volt'],
            'Chrysler' => ['200', '300', 'Aspen', 'Crossfire', 'Pacifica', 'PT Cruiser', 'Sebring', 'Town & Country', 'Voyager'],
            'Dodge' => ['Avenger', 'Caliber', 'Caravan', 'Challenger', 'Charger', 'Charger Daytona', 'Dakota', 'Dart', 'Durango', 'Grand Caravan', 'Hornet', 'Journey', 'Magnum', 'Neon', 'Nitro', 'Sprinter', 'Stratus', 'Viper'],
            'Fiat' => ['500', '500e', '500L', '500X', '124 Spider'],
            'Ford' => ['Bronco', 'Bronco Sport', 'C-Max', 'Crown Victoria', 'E-Series', 'EcoSport', 'Edge', 'Escape', 'Excursion', 'Expedition', 'Explorer', 'Explorer Sport Trac', 'F-150', 'F-150 Lightning', 'F-250', 'F-350', 'F-450', 'Fiesta', 'Five Hundred', 'Flex', 'Focus', 'Freestar', 'Freestyle', 'Fusion', 'GT', 'Maverick', 'Mustang', 'Mustang Mach-E', 'Ranger', 'Taurus', 'Taurus X', 'Thunderbird', 'Transit', 'Transit Connect'],
            'Genesis' => ['G70', 'G80', 'G90', 'GV60', 'GV70', 'GV80', 'Electrified G80', 'Electrified GV70'],
            'GMC' => ['Acadia', 'Canyon', 'Envoy', 'Hummer EV Pickup', 'Hummer EV SUV', 'Savana', 'Sierra 1500', 'Sierra 2500HD', 'Sierra 3500HD', 'Terrain', 'Yukon', 'Yukon XL'],
            'Honda' => ['Accord', 'Accord Crosstour', 'Civic', 'Clarity', 'CR-V', 'CR-Z', 'Crosstour', 'Element', 'Fit', 'HR-V', 'Insight', 'Odyssey', 'Passport', 'Pilot', 'Prologue', 'Ridgeline', 'S2000'],
            'Hyundai' => ['Accent', 'Azera', 'Elantra', 'Elantra GT', 'Entourage', 'Equus', 'Genesis', 'Genesis Coupe', 'Ioniq', 'Ioniq 5', 'Ioniq 6', 'Kona', 'Kona Electric', 'Nexo', 'Palisade', 'Santa Cruz', 'Santa Fe', 'Sonata', 'Tiburon', 'Tucson', 'Veloster', 'Venue', 'Veracruz'],
            'Infiniti' => ['EX35', 'EX37', 'FX35', 'FX37', 'FX45', 'FX50', 'G25', 'G35', 'G37', 'JX35', 'M35', 'M37', 'M45', 'M56', 'Q40', 'Q45', 'Q50', 'Q60', 'Q70', 'QX30', 'QX50', 'QX55', 'QX56', 'QX60', 'QX70', 'QX80'],
            'Jaguar' => ['E-Pace', 'F-Pace', 'F-Type', 'I-Pace', 'S-Type', 'X-Type', 'XE', 'XF', 'XJ', 'XK'],
            'Jeep' => ['Cherokee', 'Commander', 'Compass', 'Gladiator', 'Grand Cherokee', 'Grand Cherokee L', 'Grand Wagoneer', 'Liberty', 'Patriot', 'Renegade', 'Wagoneer', 'Wrangler', 'Wrangler Unlimited'],
            'Kia' => ['Amanti', 'Borrego', 'Cadenza', 'Carnival', 'EV6', 'EV9', 'Forte', 'K5', 'K900', 'Niro', 'Optima', 'Rio', 'Rondo', 'Sedona', 'Seltos', 'Sorento', 'Soul', 'Spectra', 'Sportage', 'Stinger', 'Telluride'],
            'Lamborghini' => ['Gallardo', 'Murcielago', 'Aventador', 'Huracan', 'Urus', 'Revuelto'],
            'Land Rover' => ['Defender', 'Discovery', 'Discovery Sport', 'Freelander', 'LR2', 'LR3', 'LR4', 'Range Rover', 'Range Rover Sport', 'Range Rover Velar', 'Range Rover Evoque'],
            'Lexus' => ['CT', 'ES', 'GS', 'GX', 'HS', 'IS', 'LC', 'LFA', 'LS', 'LX', 'NX', 'RC', 'RX', 'RZ', 'SC', 'TX', 'UX'],
            'Lincoln' => ['Aviator', 'Continental', 'Corsair', 'LS', 'Mark LT', 'MKC', 'MKS', 'MKT', 'MKX', 'MKZ', 'Nautilus', 'Navigator', 'Town Car', 'Zephyr'],
            'Lotus' => ['Elise', 'Exige', 'Evora', 'Emira', 'Eletre', 'Evija'],
            'Lucid' => ['Air', 'Gravity'],
            'Maserati' => ['Coupe', 'GranSport', 'GranTurismo', 'Ghibli', 'Quattroporte', 'Levante', 'Grecale', 'MC20'],
            'Mazda' => ['B-Series', 'CX-3', 'CX-30', 'CX-5', 'CX-50', 'CX-7', 'CX-70', 'CX-9', 'CX-90', 'Mazda2', 'Mazda3', 'Mazda5', 'Mazda6', 'MPV', 'MX-30', 'MX-5 Miata', 'RX-8', 'Tribute'],
            'McLaren' => ['MP4-12C', '570S', '570GT', '600LT', '650S', '675LT', '720S', '765LT', 'GT', 'Artura', 'P1', 'Senna'],
            'Mercedes-Benz' => ['A-Class', 'B-Class', 'C-Class', 'CL-Class', 'CLA', 'CLK', 'CLS', 'E-Class', 'EQB', 'EQE', 'EQS', 'G-Class', 'GL-Class', 'GLA', 'GLB', 'GLC', 'GLE', 'GLK', 'GLS', 'M-Class', 'Metris', 'R-Class', 'S-Class', 'SL', 'SLC', 'SLK', 'SLR McLaren', 'SLS AMG', 'Sprinter', 'AMG GT'],
            'MINI' => ['Cooper', 'Clubman', 'Countryman', 'Convertible', 'Coupe', 'Roadster', 'Paceman', 'Hardtop'],
            'Mitsubishi' => ['Eclipse', 'Eclipse Cross', 'Endeavor', 'Galant', 'i-MiEV', 'Lancer', 'Lancer Evolution', 'Mirage', 'Mirage G4', 'Montero', 'Outlander', 'Outlander PHEV', 'Outlander Sport', 'Raider'],
            'Nissan' => ['350Z', '370Z', 'Altima', 'Ariya', 'Armada', 'Cube', 'Frontier', 'GT-R', 'Juke', 'Kicks', 'Leaf', 'Maxima', 'Murano', 'Murano CrossCabriolet', 'NV', 'NV200', 'Pathfinder', 'Quest', 'Rogue', 'Rogue Sport', 'Sentra', 'Titan', 'Versa', 'Xterra', 'Z'],
            'Polestar' => ['Polestar 1', 'Polestar 2', 'Polestar 3', 'Polestar 4'],
            'Porsche' => ['911', '718 Boxster', '718 Cayman', '918 Spyder', 'Boxster', 'Carrera GT', 'Cayenne', 'Cayman', 'Macan', 'Panamera', 'Taycan'],
            'Ram' => ['1500', '2500', '3500', 'Dakota', 'ProMaster', 'ProMaster City'],
            'Rivian' => ['R1T', 'R1S', 'R2'],
            'Rolls-Royce' => ['Phantom', 'Ghost', 'Wraith', 'Dawn', 'Cullinan', 'Spectre'],
            'Subaru' => ['Ascent', 'B9 Tribeca', 'Baja', 'BRZ', 'Crosstrek', 'Forester', 'Impreza', 'Legacy', 'Outback', 'Solterra', 'Tribeca', 'WRX', 'WRX STI', 'XV Crosstrek'],
            'Tesla' => ['Model S', 'Model 3', 'Model X', 'Model Y', 'Roadster', 'Cybertruck'],
            'Toyota' => ['4Runner', '86', 'Avalon', 'bZ4X', 'C-HR', 'Camry', 'Camry Solara', 'Celica', 'Corolla', 'Corolla Cross', 'Corolla iM', 'Crown', 'Crown Signia', 'FJ Cruiser', 'GR Corolla', 'GR Supra', 'GR86', 'Grand Highlander', 'Highlander', 'Land Cruiser', 'Matrix', 'Mirai', 'MR2 Spyder', 'Prius', 'Prius c', 'Prius Prime', 'Prius v', 'RAV4', 'RAV4 Prime', 'Sequoia', 'Sienna', 'Solara', 'Supra', 'Tacoma', 'Tundra', 'Venza', 'Yaris', 'Yaris iA'],
            'Volkswagen' => ['Arteon', 'Atlas', 'Atlas Cross Sport', 'Beetle', 'CC', 'Eos', 'e-Golf', 'Golf', 'Golf Alltrack', 'Golf R', 'Golf SportWagen', 'GTI', 'ID.4', 'ID. Buzz', 'Jetta', 'Jetta SportWagen', 'New Beetle', 'Passat', 'Phaeton', 'Rabbit', 'Routan', 'Taos', 'Tiguan', 'Touareg'],
            'Volvo' => ['C30', 'C40', 'C70', 'EX30', 'EX90', 'S40', 'S60', 'S80', 'S90', 'V50', 'V60', 'V70', 'V90', 'XC40', 'XC60', 'XC70', 'XC90'],
            'Pontiac' => ['Aztek', 'Bonneville', 'G3', 'G5', 'G6', 'G8', 'Grand Am', 'Grand Prix', 'GTO', 'Montana', 'Solstice', 'Sunfire', 'Torrent', 'Vibe'],
            'Saturn' => ['Astra', 'Aura', 'Ion', 'L300', 'Outlook', 'Relay', 'Sky', 'Vue'],
            'Scion' => ['FR-S', 'iA', 'iM', 'iQ', 'tC', 'xA', 'xB', 'xD'],
            'Hummer' => ['H1', 'H2', 'H3', 'H3T'],
            'Mercury' => ['Grand Marquis', 'Mariner', 'Milan', 'Montego', 'Monterey', 'Mountaineer', 'Sable'],
            'Oldsmobile' => ['Alero', 'Aurora', 'Bravada', 'Intrigue', 'Silhouette'],
            'Saab' => ['9-2X', '9-3', '9-5', '9-7X'],
            'Smart' => ['Fortwo', 'Fortwo Electric Drive'],
            'Suzuki' => ['Aerio', 'Equator', 'Forenza', 'Grand Vitara', 'Kizashi', 'Reno', 'SX4', 'Verona', 'XL7'],
            'Isuzu' => ['Ascender', 'i-280', 'i-290', 'i-350', 'i-370'],
            'Maybach' => ['57', '62'],
            'Ferrari' => ['F430', '458 Italia', '458 Spider', '488 GTB', '488 Spider', 'F8 Tributo', '296 GTB', 'SF90 Stradale', '812 Superfast', 'Roma', 'Portofino', 'California', 'California T', 'GTC4Lusso', 'FF', 'LaFerrari', 'Purosangue', '599 GTB', '612 Scaglietti'],
            'Bugatti' => ['Veyron', 'Chiron', 'Mistral'],
            'Fisker' => ['Karma', 'Ocean'],
            'Karma' => ['Revero', 'GS-6'],
            'VinFast' => ['VF 8', 'VF 9'],
            'INEOS' => ['Grenadier'],
            'Other' => [],
        ];
    }
}
