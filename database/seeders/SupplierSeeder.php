<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Pakah', 'address' => '', 'contact_person' => ''],
            ['name' => 'Subahan', 'address' => '', 'contact_person' => ''],
            ['name' => 'Awan', 'address' => '', 'contact_person' => ''],
            ['name' => 'Aris', 'address' => '', 'contact_person' => ''],
            ['name' => 'Goa', 'address' => '', 'contact_person' => ''],
            ['name' => 'Novel', 'address' => '', 'contact_person' => ''],
            ['name' => 'Join', 'address' => '', 'contact_person' => ''],
            ['name' => 'Ridwan', 'address' => '', 'contact_person' => ''],
            ['name' => 'Sinhai Kalimantan', 'address' => '', 'contact_person' => ''],
            ['name' => 'Awi', 'address' => '', 'contact_person' => ''],
            ['name' => 'Adi', 'address' => '', 'contact_person' => ''],
            ['name' => 'Hengki', 'address' => '', 'contact_person' => ''],
            ['name' => 'Abel', 'address' => '', 'contact_person' => ''],
            ['name' => 'Edwin', 'address' => '', 'contact_person' => ''],
            ['name' => 'Enggar', 'address' => '', 'contact_person' => ''],
            ['name' => 'Joko', 'address' => '', 'contact_person' => ''],
            ['name' => 'Hendra', 'address' => '', 'contact_person' => ''],
            ['name' => 'Oni', 'address' => '', 'contact_person' => ''],
            ['name' => 'Candra', 'address' => '', 'contact_person' => ''],
            ['name' => 'Jodi', 'address' => '', 'contact_person' => ''],
            ['name' => 'Palu', 'address' => '', 'contact_person' => ''],
            ['name' => 'Ervan', 'address' => '', 'contact_person' => ''],
            ['name' => 'Agung', 'address' => '', 'contact_person' => ''],
            ['name' => 'Kalimantan', 'address' => '', 'contact_person' => ''],
            ['name' => 'Sur', 'address' => '', 'contact_person' => ''],
            ['name' => 'Subur', 'address' => '', 'contact_person' => ''],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

    }
}
