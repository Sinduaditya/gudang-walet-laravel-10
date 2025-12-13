<?php

namespace App\Exports;

use App\Models\GradeCompany;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradeCompanyExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
{
    
    public function query()
    {
        return GradeCompany::select("id", "name", "description", "created_at", "updated_at")
            ->orderBy('id', 'asc');
    }

    public function chunkSize(): int
    {
        return 500; // Process 500 records at a time
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Grade Company',
            'Deskripsi',
            'Tanggal Dibuat',
            'Terakhir Diupdate'
        ];
    }

    public function map($gradeCompany): array
    {
        return [
            $gradeCompany->id ?? '-',
            $gradeCompany->name ?? '-',
            $gradeCompany->description ?? 'Tidak ada deskripsi',
            $gradeCompany->created_at ? $gradeCompany->created_at->format('d/m/Y H:i') : '-',
            $gradeCompany->updated_at ? $gradeCompany->updated_at->format('d/m/Y H:i') : '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A1:E1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '9CA3AF']
                    ]
                ]
            ]
        ];
    }
}