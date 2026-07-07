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
                ['Crossover', 'car'], ['Motorbike', 'motor_bike'],
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
     * Every make sold in the US market (current + recent), plus common discontinued makes whose
     * vehicles are still widely driven, each with its US model line-up (make + model).
     */
    private function brands(): array
    {
        return [
            // ---- Mainstream (current) ----
            'Acura' => ['ILX', 'TLX', 'RLX', 'TL', 'TSX', 'RSX', 'Integra', 'RDX', 'MDX', 'ZDX', 'NSX'],
            'Alfa Romeo' => ['Giulia', 'Stelvio', 'Tonale', '4C', 'Giulietta'],
            'Aston Martin' => ['Vantage', 'DB11', 'DB12', 'DBS', 'DBX', 'Rapide', 'Vanquish'],
            'Audi' => ['A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q3', 'Q4 e-tron', 'Q5', 'Q7', 'Q8', 'e-tron', 'e-tron GT', 'TT', 'R8', 'S3', 'S4', 'S5', 'RS5', 'RS7', 'SQ5'],
            'Bentley' => ['Continental GT', 'Flying Spur', 'Bentayga', 'Mulsanne'],
            'BMW' => ['2 Series', '3 Series', '4 Series', '5 Series', '6 Series', '7 Series', '8 Series', 'X1', 'X2', 'X3', 'X4', 'X5', 'X6', 'X7', 'Z4', 'i3', 'i4', 'i5', 'i7', 'iX', 'M2', 'M3', 'M4', 'M5', 'X3 M', 'X5 M'],
            'Buick' => ['Enclave', 'Encore', 'Encore GX', 'Envision', 'Envista', 'LaCrosse', 'Regal', 'Verano', 'Lucerne', 'Cascada'],
            'Cadillac' => ['CT4', 'CT5', 'CT6', 'ATS', 'CTS', 'XTS', 'XT4', 'XT5', 'XT6', 'SRX', 'Escalade', 'Escalade ESV', 'Lyriq', 'ELR'],
            'Chevrolet' => ['Spark', 'Sonic', 'Cruze', 'Malibu', 'Impala', 'Camaro', 'Corvette', 'Cobalt', 'HHR', 'Trax', 'Trailblazer', 'Equinox', 'Blazer', 'Traverse', 'Tahoe', 'Suburban', 'Captiva', 'Colorado', 'Silverado 1500', 'Silverado 2500HD', 'Silverado 3500HD', 'Bolt EV', 'Bolt EUV', 'Volt', 'Express'],
            'Chrysler' => ['300', 'Pacifica', 'Voyager', 'Town & Country', '200', 'Sebring', 'PT Cruiser'],
            'Dodge' => ['Charger', 'Challenger', 'Durango', 'Journey', 'Grand Caravan', 'Dart', 'Avenger', 'Caliber', 'Hornet', 'Nitro'],
            'Fiat' => ['500', '500e', '500X', '500L', '124 Spider'],
            'Ford' => ['Fiesta', 'Focus', 'Fusion', 'Taurus', 'Mustang', 'Mustang Mach-E', 'EcoSport', 'Escape', 'Bronco', 'Bronco Sport', 'Edge', 'Explorer', 'Expedition', 'Flex', 'C-Max', 'Ranger', 'Maverick', 'F-150', 'F-150 Lightning', 'F-250', 'F-350', 'Transit', 'Transit Connect'],
            'Genesis' => ['G70', 'G80', 'G90', 'GV60', 'GV70', 'GV80'],
            'GMC' => ['Terrain', 'Acadia', 'Yukon', 'Yukon XL', 'Canyon', 'Sierra 1500', 'Sierra 2500HD', 'Sierra 3500HD', 'Savana', 'Hummer EV'],
            'Honda' => ['Fit', 'Civic', 'Insight', 'Accord', 'HR-V', 'CR-V', 'Passport', 'Pilot', 'Ridgeline', 'Odyssey', 'CR-Z', 'Clarity', 'Prologue', 'Element'],
            'Hyundai' => ['Accent', 'Elantra', 'Sonata', 'Veloster', 'Ioniq', 'Ioniq 5', 'Ioniq 6', 'Venue', 'Kona', 'Tucson', 'Santa Fe', 'Palisade', 'Santa Cruz', 'Nexo', 'Azera', 'Veracruz', 'Genesis Coupe'],
            'Infiniti' => ['Q50', 'Q60', 'Q70', 'QX30', 'QX50', 'QX55', 'QX60', 'QX80', 'G37', 'FX35', 'EX35', 'M37'],
            'Jaguar' => ['XE', 'XF', 'XJ', 'F-Type', 'E-Pace', 'F-Pace', 'I-Pace'],
            'Jeep' => ['Renegade', 'Compass', 'Cherokee', 'Grand Cherokee', 'Grand Cherokee L', 'Wrangler', 'Gladiator', 'Wagoneer', 'Grand Wagoneer', 'Patriot', 'Liberty', 'Commander'],
            'Kia' => ['Rio', 'Forte', 'K5', 'Stinger', 'Optima', 'Cadenza', 'Soul', 'Seltos', 'Sportage', 'Sorento', 'Telluride', 'Carnival', 'Sedona', 'Niro', 'EV6', 'EV9'],
            'Lamborghini' => ['Huracan', 'Aventador', 'Urus', 'Revuelto', 'Gallardo'],
            'Land Rover' => ['Defender', 'Discovery', 'Discovery Sport', 'Range Rover', 'Range Rover Sport', 'Range Rover Velar', 'Range Rover Evoque', 'LR2', 'LR4'],
            'Lexus' => ['IS', 'ES', 'GS', 'LS', 'RC', 'LC', 'CT', 'UX', 'NX', 'RX', 'GX', 'LX', 'RZ', 'HS'],
            'Lincoln' => ['Corsair', 'Nautilus', 'Aviator', 'Navigator', 'MKZ', 'MKC', 'MKX', 'MKS', 'MKT', 'Continental', 'Town Car'],
            'Lucid' => ['Air', 'Gravity'],
            'Maserati' => ['Ghibli', 'Quattroporte', 'Levante', 'Grecale', 'GranTurismo', 'MC20'],
            'Mazda' => ['Mazda2', 'Mazda3', 'Mazda6', 'MX-5 Miata', 'CX-3', 'CX-30', 'CX-5', 'CX-9', 'CX-50', 'CX-90', 'MX-30', 'CX-7', 'Tribute'],
            'McLaren' => ['570S', '720S', '765LT', 'GT', 'Artura'],
            'Mercedes-Benz' => ['A-Class', 'C-Class', 'E-Class', 'S-Class', 'CLA', 'CLS', 'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'G-Class', 'EQB', 'EQE', 'EQS', 'SL', 'SLC', 'AMG GT', 'Sprinter', 'Metris', 'GLK'],
            'MINI' => ['Cooper', 'Cooper Clubman', 'Cooper Countryman', 'Hardtop', 'Convertible', 'Paceman'],
            'Mitsubishi' => ['Mirage', 'Mirage G4', 'Eclipse Cross', 'Outlander', 'Outlander Sport', 'Outlander PHEV', 'Lancer', 'Galant', 'Endeavor', 'Montero'],
            'Nissan' => ['Versa', 'Sentra', 'Altima', 'Maxima', 'Leaf', 'Ariya', 'Kicks', 'Rogue', 'Rogue Sport', 'Murano', 'Pathfinder', 'Armada', 'Frontier', 'Titan', 'GT-R', 'Z', '370Z', 'Juke', 'Cube', 'Quest', 'Xterra'],
            'Polestar' => ['Polestar 2', 'Polestar 3', 'Polestar 4'],
            'Porsche' => ['911', '718 Cayman', '718 Boxster', 'Panamera', 'Macan', 'Cayenne', 'Taycan'],
            'Ram' => ['1500', '2500', '3500', 'ProMaster', 'ProMaster City', 'Dakota'],
            'Rivian' => ['R1T', 'R1S'],
            'Rolls-Royce' => ['Phantom', 'Ghost', 'Wraith', 'Dawn', 'Cullinan', 'Spectre'],
            'Subaru' => ['Impreza', 'Legacy', 'WRX', 'BRZ', 'Crosstrek', 'Forester', 'Outback', 'Ascent', 'Solterra', 'Baja', 'Tribeca'],
            'Tesla' => ['Model 3', 'Model Y', 'Model S', 'Model X', 'Cybertruck', 'Roadster'],
            'Toyota' => ['Corolla', 'Corolla Cross', 'Camry', 'Prius', 'Prius Prime', 'Mirai', 'Avalon', 'Crown', 'Yaris', 'GR86', 'GR Supra', 'C-HR', 'RAV4', 'Venza', 'Highlander', '4Runner', 'Sequoia', 'Land Cruiser', 'Tacoma', 'Tundra', 'Sienna', 'bZ4X', 'Matrix', 'FJ Cruiser'],
            'Volkswagen' => ['Jetta', 'Passat', 'Arteon', 'Golf', 'GTI', 'Golf R', 'Beetle', 'Taos', 'Tiguan', 'Atlas', 'Atlas Cross Sport', 'ID.4', 'Touareg', 'CC', 'Eos', 'Routan'],
            'Volvo' => ['S60', 'S90', 'V60', 'V90', 'XC40', 'XC60', 'XC90', 'C40', 'EX30', 'EX90', 'S40', 'C30'],

            // ---- Discontinued in the US but still widely driven ----
            'Pontiac' => ['G5', 'G6', 'G8', 'Grand Prix', 'Grand Am', 'Bonneville', 'Firebird', 'GTO', 'Vibe', 'Solstice', 'Torrent', 'Aztek'],
            'Saturn' => ['Ion', 'Aura', 'Sky', 'Vue', 'Outlook', 'Astra', 'Relay'],
            'Scion' => ['xA', 'xB', 'xD', 'tC', 'iA', 'iM', 'iQ', 'FR-S'],
            'Hummer' => ['H1', 'H2', 'H3'],
            'Mercury' => ['Grand Marquis', 'Milan', 'Sable', 'Mariner', 'Mountaineer', 'Montego'],
            'Oldsmobile' => ['Alero', 'Aurora', 'Intrigue', 'Bravada', 'Silhouette'],
            'Saab' => ['9-3', '9-5', '9-7X', '9-2X'],
            'Smart' => ['Fortwo'],
            'Suzuki' => ['SX4', 'Kizashi', 'Grand Vitara', 'Forenza', 'Aerio', 'Reno', 'Equator', 'XL7'],
            'Isuzu' => ['Ascender', 'i-Series', 'Rodeo', 'Trooper', 'VehiCROSS'],
        ];
    }
}
