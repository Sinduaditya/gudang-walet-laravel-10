<?php

namespace Database\Seeders;

use App\Models\GradeCompany;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gradeCompany = [

            // --- MANGKOK RPS ---
            ['name' => 'MANGKOK RPS MK BS', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK RPS PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK RPS PENDEK (IDM B)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK RPS IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK RPS ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK RPS AA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK RPS AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- MANGKOK 2 ---
            ['name' => 'MANGKOK 2 W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 2 W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 2 ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 2 PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 2 AA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 2 AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 2 IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- MANGKOK KW ---
            ['name' => 'MANGKOK KW IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK KW PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK KW JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK KW AA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

             // --- MANGKOK BERAS ---
            ['name' => 'MANGKOK BERAS MK CERAH', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK BERAS PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK BERAS IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK BERAS AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK BERAS AA/BULU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK BERAS ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- MANGKOK 1 PUTIH ---
            ['name' => 'MANGKOK 1 PUTIH W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 1 PUTIH W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 1 PUTIH W3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 1 PUTIH KECIL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 1 PUTIH PECAH/TIDAK SEMPURNA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK 1 PUTIH PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- MANGKOK PLONTOS ---
            ['name' => 'MANGKOK PLONTOS W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK PLONTOS W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK PLONTOS W3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK PLONTOS TIDAK SEMPURNA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK PLONTOS ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK PLONTOS PECAH', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'MANGKOK PLONTOS AF BAGUS', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- BULU ---
            ['name' => 'BULU AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'BULU BAGUS', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- OVAL ---
            ['name' => 'OVAL PANJANG', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'OVAL PENDEK', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'OVAL AA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'OVAL ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'OVAL IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'OVAL MK KECIL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- SP KW ---
            ['name' => 'SP KW IDM A', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP KW IDM B', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP KW AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- SUDUT ---
            ['name' => 'SUDUT W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT W3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT PECAH', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT IDM A', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT IDM B', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SUDUT AA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- SP PUTIH ---
            ['name' => 'SP PUTIH W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH W3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH PANJANG', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH PECAH', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH STEROFOAM', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH AA/BULU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP PUTIH IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- SP BERAS ---
            ['name' => 'SP BERAS LEMPENG W1, W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS IDM B', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS AA/BULU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- SP BERAS CERAH ---
            ['name' => 'SP BERAS CERAH LEMPENG W1,2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH PENDEK (IDM B)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH PECAH', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'SP BERAS CERAH AA/BULU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- PATAHAN 2/3 ---
            ['name' => 'PATAHAN 2/3 PANJANG', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN 2/3 PENDEK', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN 2/3 STEROFOAM', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN 2/3 AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN 2/3 AA/BULU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN 2/3 ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- PATAHAN PLONTOS ---
            ['name' => 'PATAHAN PLONTOS W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PLONTOS W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PLONTOS W3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PLONTOS PANJANG', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PLONTOS AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PLONTOS BS', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PLONTOS PENDEK', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- PATAHAN ---
            ['name' => 'PATAHAN W1', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN W2', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN W3', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN STEROFOAM', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN AA/BULU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN PENDEK (IDM B)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'PATAHAN IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],

            // --- CONG ---
            ['name' => 'CONG PANJANG (IDM A)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG LEMPENG', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG MK UTUH/SEMPURNA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG PENDEK (IDM B)', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG AF JUAL', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG AA', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG ALU', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG IDM P', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
            ['name' => 'CONG STEROFOAM', 'image_url' => 'https://statik.tempo.co/data/2019/11/13/id_888979/888979_720.jpg', 'description' => null],
        ];

        foreach ($gradeCompany as $gradeCompany) {
            GradeCompany::create($gradeCompany);
        }
    }
}
