<?php

namespace App\Exports;

use App\Models\ParentGradeCompany;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParentGradeCompanyExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ParentGradeCompany::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Deskripsi',
            'Image',
            'Dibuat Pada',
            'Diupdate Pada',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->description,
            asset('storage/' . $row->image_url),
            $row->created_at,
            $row->updated_at,
        ];
    }
}
