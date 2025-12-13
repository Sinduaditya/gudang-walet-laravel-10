<?php

namespace App\Exports;

use App\Models\GradeSupplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping; // <-- 1. TAMBAHKAN INI

class GradeSupplierExport implements FromCollection, WithHeadings, WithMapping // <-- 2. TAMBAHKAN INI
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Query ini tetap sama, mengambil data mentah
        return GradeSupplier::select("id", "name", "description", "created_at")->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {

        return [
            'ID',
            'Nama Grade',
            'Deskripsi',
            'Tanggal Dibuat',
        ];
    }

    /**
     * @var GradeSupplier
     *
     * @return array
     */
    public function map($grade): array
    {

        return [
            $grade->id,
            $grade->name,
            $grade->description,
            $grade->created_at ? $grade->created_at->format('d/m/Y H:i') : '-'
        ];
    }
}
