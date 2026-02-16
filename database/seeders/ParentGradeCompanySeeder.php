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
            ['name' => 'IDM A'],
            ['name' => 'IDM B'],
            ['name' => 'MANGKOK'],
        ];

        foreach ($data as $item) {
            ParentGradeCompany::create($item);
        }
    }
}
