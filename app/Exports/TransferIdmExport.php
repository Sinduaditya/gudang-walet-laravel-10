<?php

namespace App\Exports;

use App\Models\IdmTransfer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransferIdmExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;
    protected int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = IdmTransfer::withCount('details')->latest();

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('transfer_date', [$this->filters['start_date'], $this->filters['end_date']]);
        }
        if (!empty($this->filters['search'])) {
            $query->where('transfer_code', 'like', '%' . $this->filters['search'] . '%');
        }

        return $query;
    }

    public function headings(): array
    {
        return ['No', 'Kode Transfer', 'Tgl Transfer', 'Total Barang', 'Harga Transfer'];
    }

    public function map($transfer): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $transfer->transfer_code,
            \Carbon\Carbon::parse($transfer->transfer_date)->format('d/m/Y'),
            $transfer->details_count,
            'Rp ' . number_format($transfer->price_transfer ?? 0, 0, ',', '.'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return [];
    }
}
