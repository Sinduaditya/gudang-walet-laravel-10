<?php

namespace App\Exports;

use App\Models\StockTransfer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransferInternalExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = StockTransfer::whereHas('transactions', function ($q) {
                $q->where('transaction_type', 'TRANSFER_OUT');
            })
            ->with(['gradeCompany', 'fromLocation', 'toLocation', 'sortingResult.receiptItem.purchaseReceipt.supplier'])
            ->orderBy('transfer_date', 'desc');

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('transfer_date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('transfer_date', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->whereHas('sortingResult.receiptItem.purchaseReceipt', function ($q) {
                $q->where('supplier_id', $this->filters['supplier_id']);
            });
        }
        if (!empty($this->filters['grade_company_id'])) {
            $query->where('grade_company_id', $this->filters['grade_company_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Grade', 'Supplier', 'Transfer', 'Berat (gr)', 'Susut (gr)', 'Referensi'];
    }

    public function map($transfer): array
    {
        $from = $transfer->fromLocation->name ?? '-';
        $to   = $transfer->toLocation->name ?? '-';

        return [
            \Carbon\Carbon::parse($transfer->transfer_date)->format('d/m/Y'),
            $transfer->gradeCompany->name ?? '-',
            $transfer->sortingResult->receiptItem->purchaseReceipt->supplier->name ?? '-',
            $from . ' → ' . $to,
            number_format($transfer->weight_grams, 2, ',', '.'),
            number_format($transfer->susut_grams ?? 0, 2, ',', '.'),
            '#' . $transfer->id,
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
