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
        $locations = [['name' => 'Gudang Utama', 'description' => null], ['name' => 'KRIS', 'description' => null], ['name' => 'WIKOM', 'description' => null], ['name' => 'ASIH', 'description' => null], ['name' => 'RONI', 'description' => null], ['name' => 'SUNI', 'description' => null], ['name' => 'RUWI', 'description' => null], ['name' => 'JR', 'description' => null], ['name' => 'ANI SURABAYA', 'description' => null], ['name' => 'CANIAGO', 'description' => null], ['name' => 'MBA SURABAYA', 'description' => null], ['name' => 'BOJONEGORO', 'description' => null], ['name' => 'BABAT', 'description' => null], ['name' => 'Gudang Utama', 'description' => null], ['name' => 'DMK', 'description' => null]];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
