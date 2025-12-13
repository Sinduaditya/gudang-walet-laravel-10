<?php

namespace App\Exports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LocationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Location::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Lokasi',
            'Deskripsi',
            'Tanggal Dibuat',
            'Terakhir Diupdate'
        ];
    }

    public function map($location): array
    {
        return [
            $location->id,
            $location->name,
            $location->description ?? '-',
            $location->created_at?->format('d/m/Y H:i') ?? '-',
            $location->updated_at?->format('d/m/Y H:i') ?? '-'
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
                ]
            ]
        ];
    }
}