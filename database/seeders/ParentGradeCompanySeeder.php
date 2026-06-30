<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParentGradeCompany;

class ParentGradeCompanySeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'LEMPENG'],
            ['name' => 'MANGKOK'],
            ['name' => 'IDM'],
            ['name' => 'PERUTAN'],
            ['name' => 'KAKIAN'],
            ['name' => 'ALU'],
        ];

        foreach ($data as $item) {
            ParentGradeCompany::create($item);
        }
    }
}
