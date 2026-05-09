<?php

namespace App\Exports;

use App\Models\SortMaterial;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SortMaterialExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected ?string $search;
    protected int $rowNumber = 0;

    public function __construct(?string $search = null)
    {
        $this->search = $search;
    }

    public function query()
    {
        $query = SortMaterial::with(['parentGradeCompany', 'gradeCompany'])
            ->orderBy('sort_date', 'desc');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('parentGradeCompany', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('gradeCompany', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Parent Grade', 'Grade Company', 'Berat (Gram)', 'Deskripsi'];
    }

    public function map($item): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            \Carbon\Carbon::parse($item->sort_date)->format('d/m/Y'),
            $item->parentGradeCompany->name ?? '-',
            $item->gradeCompany->name ?? '-',
            number_format($item->weight, 2, ',', '.'),
            $item->description ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return [];
    }
}
