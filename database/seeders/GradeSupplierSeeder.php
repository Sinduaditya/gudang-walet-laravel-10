<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GradeSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            // Gambar 1 - Baris 1
            ['name' => 'MANGKOK', 'description' => 'Grade Mangkok standar', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK PUTIH', 'description' => 'Grade Mangkok putih', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK KW', 'description' => 'Grade Mangkok KW', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK RAMPAS KECIL', 'description' => 'Grade Mangkok rampas kecil', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK BULU RAMPAS', 'description' => 'Grade Mangkok bulu rampas', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 2', 'description' => 'Grade Mangkok tipe 2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK BERAS KREM', 'description' => 'Grade Mangkok beras krem', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 1 - Baris 2
            ['name' => 'MANGKOK BERAS', 'description' => 'Grade Mangkok beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK PINK', 'description' => 'Grade Mangkok pink', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK BB', 'description' => 'Grade Mangkok BB', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK OVAL RAMPAS', 'description' => 'Grade Mangkok oval rampas', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK OVAL BULU', 'description' => 'Grade Mangkok oval bulu', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK BERAS KW', 'description' => 'Grade Mangkok beras KW', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 4', 'description' => 'Grade Mangkok tipe 4', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 1 - Baris 3
            ['name' => 'MANGKOK 3', 'description' => 'Grade Mangkok tipe 3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK PUTIH BERAS', 'description' => 'Grade Mangkok putih beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 1 KREM', 'description' => 'Grade Mangkok 1 krem', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 2 KREM', 'description' => 'Grade Mangkok 2 krem', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK KREM', 'description' => 'Grade Mangkok krem', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 2 RAMPAS PUTIH', 'description' => 'Grade Mangkok 2 rampas putih', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 5', 'description' => 'Grade Mangkok tipe 5', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 1 - Baris 4
            ['name' => 'MANGKOK KUNING', 'description' => 'Grade Mangkok kuning', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK/OVAL BS', 'description' => 'Grade Mangkok oval BS', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK PLONTOS', 'description' => 'Grade Mangkok plontos', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK RAMPAS', 'description' => 'Grade Mangkok rampas', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK A/B PUTIH', 'description' => 'Grade Mangkok A/B putih', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK KR', 'description' => 'Grade Mangkok KR', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'MANGKOK 7', 'description' => 'Grade Mangkok tipe 7', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 1 - Baris 5
            ['name' => 'MANGKOK 6', 'description' => 'Grade Mangkok tipe 6', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 2 - OVAL Series
            ['name' => 'OVAL', 'description' => 'Grade Oval standar', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'OVAL RAMPAS PUTIH', 'description' => 'Grade Oval rampas putih', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'RAMPAS OVAL', 'description' => 'Grade Rampas oval', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'OVAL RAMPAS BERAS', 'description' => 'Grade Oval rampas beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 2 - PB
            ['name' => 'PB', 'description' => 'Grade PB', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 2 - SUDUT Series
            ['name' => 'SUDUT', 'description' => 'Grade Sudut standar', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SUDUT LUNUT', 'description' => 'Grade Sudut lunut', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SUDUT 2', 'description' => 'Grade Sudut tipe 2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SUDUT/PATAHAN JAWA', 'description' => 'Grade Sudut patahan jawa', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 2 - SP Series
            ['name' => 'SP', 'description' => 'Grade SP standar', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP PUTIH', 'description' => 'Grade SP putih', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SPBC', 'description' => 'Grade SPBC', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP BERAS', 'description' => 'Grade SP beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN', 'description' => 'Grade Patahan standar', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP KW', 'description' => 'Grade SP KW', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP KREM BERAS', 'description' => 'Grade SP krem beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP KREM', 'description' => 'Grade SP krem', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP LUMUT', 'description' => 'Grade SP lumut', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'SP BULU', 'description' => 'Grade SP bulu', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 2 - 23 Series & PAHATAN
            ['name' => '2/3', 'description' => 'Grade 2/3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => '2/3 BULU', 'description' => 'Grade 2/3 bulu', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN 2/3 KW', 'description' => 'Grade patahan 2/3 KW', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN 2/3 BB', 'description' => 'Grade patahan 2/3 BB', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN BERAS', 'description' => 'Grade patahan beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN PENDEK', 'description' => 'Grade patahan pendek', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN PLONTOS', 'description' => 'Grade patahan plontos', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            ['name' => 'PATAHAN 2/3 BERAS', 'description' => 'Grade patahan 2/3 beras', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN JAWA', 'description' => 'Grade patahan jawa', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN A KREM', 'description' => 'Grade patahan A krem', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'PATAHAN KECIL 2/3', 'description' => 'Grade patahan kecil 2/3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 3 - KAKIAN Series
            ['name' => 'KAKIAN/CUPING', 'description' => 'Grade Kakian', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'KAKI', 'description' => 'Grade Kaki', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'BB', 'description' => 'Grade BB', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'KW', 'description' => 'Grade KW', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'KAKI KUNING', 'description' => 'Grade Kaki kuning', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'RAMPAS PUTIH', 'description' => 'Grade Rampas putih', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'FLEK', 'description' => 'Grade Flek', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],

            // Gambar 3 - KRONIS Series
            ['name' => 'KRONIS', 'description' => 'Grade Kronis', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'CONG', 'description' => 'Grade Cong', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'BULU CONG', 'description' => 'Grade Bulu cong', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
            ['name' => 'BAKPAO/KRONIS', 'description' => 'Grade Bakpao/Kronis', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg'],
        ];

        $timestamp = Carbon::now();

        foreach ($grades as &$grade) {
            $grade['created_at'] = $timestamp;
            $grade['updated_at'] = $timestamp;
        }

        DB::table('grades_supplier')->insert($grades);

        $this->command->info('✓ ' . count($grades) . ' Grade Supplier seeded successfully!');
    }
}
