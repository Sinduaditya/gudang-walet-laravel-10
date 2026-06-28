<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['name' => 'Gudang Utama', 'description' => null, 'is_jasa_cuci' => false],
            ['name' => 'KRIS', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'WIKOM', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'ASIH', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'RONI', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'SUNI', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'RUWI', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'JR', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'ANI SURABAYA', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'CANIAGO', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'MBA SURABAYA', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'BOJONEGORO', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'BABAT', 'description' => null, 'is_jasa_cuci' => true],
            ['name' => 'DMK', 'description' => null, 'is_jasa_cuci' => false],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
