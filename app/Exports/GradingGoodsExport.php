<?php

namespace App\Exports;

use App\Services\GradingGoods\GradingGoodsService;
use App\Models\ReceiptItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GradingGoodsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $filters;
    protected $gradingGoodsService;

    public function __construct(GradingGoodsService $gradingGoodsService, $filters = [])
    {
        $this->gradingGoodsService = $gradingGoodsService;
        $this->filters = $filters;
        $this->prepareData();
    }

    private function prepareData()
    {
        $gradingItems = $this->gradingGoodsService->getAllGradingForExport($this->filters);

        $this->data = collect();

        if ($gradingItems->isEmpty()) {
            $this->data->push([
                'type' => 'no_data',
                'supplier_name' => 'Tidak ada data grading',
            ]);
            return;
        }

        foreach ($gradingItems as $gradingItem) {
            $sortingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($gradingItem->receipt_item_id);

            // Ambil data Receipt Item untuk Berat Nota Supplier
            $receiptItem = ReceiptItem::with('purchaseReceipt')->find($gradingItem->receipt_item_id);
            $supplierWeight = $receiptItem ? (float)$receiptItem->supplier_weight_grams : 0;
            $arrivalDate = ($receiptItem && $receiptItem->purchaseReceipt) ? $receiptItem->purchaseReceipt->receipt_date : null;

            $warehouseWeight = (float)($gradingItem->warehouse_weight_grams ?? 0);
            $totalGradingWeight = (float)($gradingItem->total_grading_weight ?? 0);
            $totalQuantity = $sortingResults->sum('quantity');
            $difference = $totalGradingWeight - $warehouseWeight;

            // DETAIL: Setiap hasil grading dalam satu baris
            foreach ($sortingResults as $result) {
                $this->data->push([
                    'type' => 'detail',
                    'supplier_name' => $gradingItem->supplier_name,
                    'grade_supplier_name' => $gradingItem->grade_supplier_name,
                    'arrival_date' => $arrivalDate,
                    'supplier_weight' => $supplierWeight,
                    'warehouse_weight' => $warehouseWeight,
                    'grading_date' => $result->grading_date,
                    'grade_company_name' => $result->gradeCompany->name ?? '-',
                    'category_grade' => $result->category_grade,
                    'outgoing_type' => $result->outgoing_type,
                    'quantity' => $result->quantity ?? 0,
                    'grading_weight' => (float)$result->weight_grams,
                    'difference' => '', // Kosong untuk detail
                    'receipt_item_id' => $gradingItem->receipt_item_id,
                ]);
            }

            // TOTAL: Total hasil grading dalam satu baris
            $this->data->push([
                'type' => 'total',
                'supplier_name' => '',
                'grade_supplier_name' => '',
                'arrival_date' => null,
                'supplier_weight' => '',
                'warehouse_weight' => '',
                'grading_date' => null,
                'grade_company_name' => 'TOTAL',
                'category_grade' => '',
                'outgoing_type' => '',
                'quantity' => $totalQuantity,
                'grading_weight' => $totalGradingWeight,
                'difference' => $difference, // Selisih di kolom terpisah
                'receipt_item_id' => $gradingItem->receipt_item_id,
            ]);

            // Separator
            $this->data->push([
                'type' => 'separator',
            ]);
        }
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Supplier',                     // A (1)
            'Grade Supplier',               // B (2)
            'Tanggal Datang',               // C (3)
            'Berat Nota Supplier (gr)',     // D (4)
            'Berat Barang di Gudang (gr)',  // E (5)
            'Tanggal Grading',              // F (6)
            'Grade Company',                // G (7)
            'Kategori',                     // H (8)
            'Jenis Keluar',                 // I (9)
            'Jumlah Item',                  // J (10)
            'Berat Hasil (gr)',             // K (11)
            'Selisih (gr)'                  // L (12) - Kolom baru
        ];
    }

    public function map($row): array
    {
        if ($row['type'] === 'separator') {
            return array_fill(0, 12, ''); // Tambah 1 kolom
        }

        if ($row['type'] === 'no_data') {
             return [$row['supplier_name'], '', '', '', '', '', '', '', '', '', '', ''];
        }

        // Helper format angka 0 desimal untuk gram
        $fmtGram = fn($val) => ($val !== '' && $val !== null) ? number_format($val, 0, ',', '') : '';
        // Helper tanggal
        $fmtDate = fn($val) => $val ? \Carbon\Carbon::parse($val)->format('d/m/Y') : '';

        if ($row['type'] === 'detail') {
            $outgoingLabel = '';
            if ($row['outgoing_type']) {
                $outgoingLabel = ucwords(str_replace('_', ' ', $row['outgoing_type']));
            }

            return [
                $row['supplier_name'],
                $row['grade_supplier_name'],
                $fmtDate($row['arrival_date']),
                $fmtGram($row['supplier_weight']),
                $fmtGram($row['warehouse_weight']),
                $fmtDate($row['grading_date']),
                $row['grade_company_name'],
                $row['category_grade'],
                $outgoingLabel,
                number_format($row['quantity'], 0, ',', '.'),
                $fmtGram($row['grading_weight']),
                '' // Selisih kosong untuk detail
            ];
        }

        if ($row['type'] === 'total') {
            // Format selisih
            $diffFormatted = '';
            if ($row['difference'] != 0) {
                $sign = $row['difference'] > 0 ? '+' : '';
                $diffFormatted = $sign . number_format($row['difference'], 0, ',', '');
            } else {
                $diffFormatted = '0';
            }

            return [
                '', '', '', '', '',
                '',
                'TOTAL',
                '', '',
                number_format($row['quantity'], 0, ',', '.'),
                $fmtGram($row['grading_weight']),
                $diffFormatted // Selisih di kolom terpisah
            ];
        }

        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];

        // Style Header Row (Row 1) untuk semua kolom A-L
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        $rowNumber = 2;
        foreach ($this->data as $row) {
            if ($row['type'] === 'separator') {
                // Skip styling untuk separator
                $rowNumber++;
                continue;
            }

            if ($row['type'] === 'no_data') {
                $sheet->getStyle("A{$rowNumber}:L{$rowNumber}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                $rowNumber++;
                continue;
            }

            if ($row['type'] === 'detail') {
                // Detail Block: Border untuk semua kolom A-L
                $sheet->getStyle("A{$rowNumber}:L{$rowNumber}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Center alignment untuk tanggal
                $sheet->getStyle("C{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            } elseif ($row['type'] === 'total') {
                // Total Block: Kolom A-L dengan background kuning dan border medium
                $sheet->getStyle("A{$rowNumber}:L{$rowNumber}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Warna teks Selisih di kolom L
                if (isset($row['difference']) && $row['difference'] != 0) {
                    $color = $row['difference'] > 0 ? '059669' : 'DC2626'; // Hijau / Merah
                    $sheet->getStyle("L{$rowNumber}")->getFont()->getColor()->setRGB($color);
                }
            }

            $rowNumber++;
        }

        return $styles;
    }
}
