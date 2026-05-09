<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReceiveExternalExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = InventoryTransaction::where('transaction_type', 'RECEIVE_EXTERNAL_IN')
            ->with(['gradeCompany', 'location', 'stockTransfer.fromLocation', 'sortingResult.receiptItem.purchaseReceipt.supplier'])
            ->whereHas('stockTransfer.fromLocation', function ($q) {
                $q->where('name', 'NOT LIKE', '%IDM%')->where('name', 'NOT LIKE', '%DMK%');
            })
            ->orderBy('transaction_date', 'desc');

        if (!empty($this->filters['grade_id'])) {
            $query->where('grade_company_id', $this->filters['grade_id']);
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->whereHas('sortingResult.receiptItem.purchaseReceipt.supplier', function ($q) {
                $q->where('id', $this->filters['supplier_id']);
            });
        }
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $this->filters['end_date']);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Supplier', 'Grade', 'Penerimaan (dari)', 'Berat Diterima (gr)', 'Susut (gr)', 'Referensi'];
    }

    public function map($tx): array
    {
        return [
            \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y'),
            $tx->sortingResult->receiptItem->purchaseReceipt->supplier->name ?? '-',
            $tx->gradeCompany->name ?? '-',
            $tx->stockTransfer->fromLocation->name ?? '-',
            number_format(abs($tx->quantity_change_grams), 2, ',', '.'),
            number_format($tx->stockTransfer->susut_grams ?? 0, 2, ',', '.'),
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
