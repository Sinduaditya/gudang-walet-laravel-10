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

class PenjualanSortirExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;
    protected int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = SortMaterial::where('type', SortMaterial::TYPE_KELUAR)
            ->with(['parentGradeCompany', 'gradeCompany'])
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('sale_date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('sale_date', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['parent_grade_company_id'])) {
            $query->where('parent_grade_company_id', $this->filters['parent_grade_company_id']);
        }
        if (!empty($this->filters['grade_company_id'])) {
            $query->where('grade_company_id', $this->filters['grade_company_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['No', 'Tanggal Penjualan', 'Parent Grade', 'Grade Company', 'Berat Keluar (gr)', 'Catatan', 'Referensi'];
    }

    public function map($tx): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $tx->sale_date ? \Carbon\Carbon::parse($tx->sale_date)->format('d/m/Y') : '-',
            $tx->parentGradeCompany->name ?? '-',
            $tx->gradeCompany->name ?? '-',
            number_format($tx->weight, 2, ',', '.'),
            $tx->notes ?? 'Tidak ada catatan',
            '#' . $tx->id,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return [];
    }
}
