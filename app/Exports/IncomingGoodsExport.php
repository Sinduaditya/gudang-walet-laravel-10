<?php

namespace App\Exports;

use App\Models\PurchaseReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IncomingGoodsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;

        $query = PurchaseReceipt::with(['supplier', 'receiptItems.gradeSupplier']);

        if (!empty($filters['month'])) {
            $query->whereMonth('receipt_date', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('receipt_date', $filters['year']);
        }

        $receipts = $query->latest('receipt_date')->get();

        $this->data = collect();

        foreach ($receipts as $receipt) {
            foreach ($receipt->receiptItems as $index => $item) {
                $percentage = 0;
                $decimal = 0;
                // Hitung decimal dan percentage
                if ($item->supplier_weight_grams > 0) {
                    $decimal = $item->difference_grams / $item->supplier_weight_grams;
                    $percentage = abs($decimal) * 100;
                }

                $this->data->push([
                    'type' => 'item',
                    'supplier_name' => $receipt->supplier->name ?? '-',
                    'receipt_date' => $receipt->receipt_date,
                    'unloading_date' => $receipt->unloading_date,
                    'grade_name' => $item->gradeSupplier->name ?? '-',
                    'supplier_weight' => (float) $item->supplier_weight_grams,
                    'warehouse_weight' => (float) $item->warehouse_weight_grams,
                    'difference' => (float) $item->difference_grams,
                    'decimal_ratio' => $decimal,
                    'percentage' => $percentage,
                    'moisture_percentage' => $item->moisture_percentage,
                    'status' => $item->status,
                    'is_first_item' => $index === 0,
                    'receipt_id' => $receipt->id,
                ]);
            }

            // Hitung Total
            $totalSupplierWeight = $receipt->receiptItems->sum('supplier_weight_grams');
            $totalWarehouseWeight = $receipt->receiptItems->sum('warehouse_weight_grams');
            $totalDifference = $receipt->receiptItems->sum('difference_grams');

            $totalDecimal = $totalSupplierWeight > 0 ? ($totalDifference / $totalSupplierWeight) : 0;
            $totalPercentage = abs($totalDecimal) * 100;

            $this->data->push([
                'type' => 'total',
                'supplier_name' => '',
                'receipt_date' => null,
                'unloading_date' => null,
                'grade_name' => 'TOTAL',
                'supplier_weight' => (float) $totalSupplierWeight,
                'warehouse_weight' => (float) $totalWarehouseWeight,
                'difference' => (float) $totalDifference,
                'decimal_ratio' => $totalDecimal,
                'percentage' => $totalPercentage,
                'moisture_percentage' => null,
                'status' => '',
                'is_first_item' => false,
                'receipt_id' => $receipt->id,
            ]);

            $this->data->push([
                'type' => 'separator',
                'supplier_name' => '',
                'receipt_date' => null,
                'unloading_date' => null,
                'grade_name' => '',
                'supplier_weight' => '',
                'warehouse_weight' => '',
                'difference' => '',
                'decimal_ratio' => '',
                'percentage' => '',
                'moisture_percentage' => '',
                'status' => '',
                'is_first_item' => false,
                'receipt_id' => $receipt->id,
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
            'Nama Supplier',       // A (1)
            'Tanggal Datang',      // B (2)
            'Tanggal Bongkar',     // C (3)
            'Grade Supplier',      // D (4)
            'Berat Supplier (gr)', // E (5)
            'Berat Gudang (gr)',   // F (6)
            'Selisih (gr)',        // G (7)
            'Rasio Desimal',       // H (8)
            'Persentase (%)',      // I (9)
            'Kadar Air (%)',       // J (10)
            'Status'               // K (11)
        ];
    }

    public function map($row): array
    {
        if ($row['type'] === 'separator') {
            return array_fill(0, 11, '');
        }

        // Format Persentase (ubah dari 2 atau 1.9 ke bentuk gram/persen yang benar)
        $percentageFormatted = '';
        if ($row['type'] === 'item' || $row['type'] === 'total') {
            // Persentase sudah dihitung dengan benar dari difference/supplier_weight * 100
            if ($row['percentage'] > 0) {
                if ($row['percentage'] == floor($row['percentage'])) {
                    $percentageFormatted = number_format($row['percentage'], 0, ',', '.');
                } else {
                    $percentageFormatted = number_format($row['percentage'], 2, ',', '.'); // 2 desimal untuk akurasi
                }
            } else {
                $percentageFormatted = '0';
            }
        }

        // Format Rasio Desimal (3 desimal)
        $decimalFormatted = '';
        if ($row['type'] === 'item' || $row['type'] === 'total') {
            if (isset($row['decimal_ratio']) && abs($row['decimal_ratio']) > 0.0001) {
                $decimalFormatted = number_format($row['decimal_ratio'], 3, ',', '.');
            } else {
                $decimalFormatted = '0,000';
            }
        }

        // Format Selisih (langsung dalam gram, bukan kg)
        $differenceFormatted = '';
        if ($row['type'] === 'item' || $row['type'] === 'total') {
            if ($row['difference'] > 0) {
                $differenceFormatted = '+' . number_format($row['difference'], 0, ',', '') . ' (kelebihan)';
            } elseif ($row['difference'] < 0) {
                $differenceFormatted = number_format($row['difference'], 0, ',', '') . ' (susut)';
            } else {
                $differenceFormatted = '0 (sama)';
            }
        }

        // Format Status
        $statusFormatted = '';
        if ($row['type'] === 'item' && !empty($row['status'])) {
            $statusFormatted = ucwords(str_replace('_', ' ', $row['status']));
        }

        // Format Kadar Air
        $moistureFormatted = '';
        if ($row['type'] === 'item' && isset($row['moisture_percentage'])) {
            $moistureFormatted = number_format($row['moisture_percentage'], 2, ',', '.') . '%';
        }

        return [
            ($row['is_first_item'] && $row['type'] === 'item') ? $row['supplier_name'] : '',
            ($row['is_first_item'] && $row['type'] === 'item') ? optional($row['receipt_date'])->format('d/m/Y') : '',
            ($row['is_first_item'] && $row['type'] === 'item') ? optional($row['unloading_date'])->format('d/m/Y') : '',
            $row['grade_name'],
            // Format angka bulat untuk gram (sudah dalam gram dari database)
            $row['supplier_weight'] !== '' ? number_format($row['supplier_weight'], 0, ',', '') : '',
            $row['warehouse_weight'] !== '' ? number_format($row['warehouse_weight'], 0, ',', '') : '',
            $differenceFormatted,
            $decimalFormatted,
            $percentageFormatted,
            $moistureFormatted,
            $statusFormatted
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];

        // Style Header Row (Row 1)
        $sheet->getStyle('A1:K1')->applyFromArray([
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

            if ($row['type'] === 'total') {
                // Total Row: Background kuning dengan border medium untuk semua kolom
                $sheet->getStyle("A{$rowNumber}:K{$rowNumber}")->applyFromArray([
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
            } elseif ($row['type'] === 'item') {
                $percentage = (float) $row['percentage'];
                $difference = (float) $row['difference'];

                // Apply border thin untuk semua kolom item
                $sheet->getStyle("A{$rowNumber}:K{$rowNumber}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Conditional coloring untuk kolom Selisih (G), Rasio (H), Persentase (I)
                if ($percentage > 2) {
                    // Background merah muda dengan teks merah bold untuk G, H, I
                    $sheet->getStyle("G{$rowNumber}:I{$rowNumber}")->applyFromArray([
                        'font' => ['color' => ['rgb' => 'DC2626'], 'bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FEE2E2']
                        ]
                    ]);
                } else {
                    // Warna teks saja untuk kolom Selisih
                    if ($difference < 0) {
                        $sheet->getStyle("G{$rowNumber}")->getFont()->getColor()->setRGB('DC2626'); // Merah
                    } elseif ($difference > 0) {
                        $sheet->getStyle("G{$rowNumber}")->getFont()->getColor()->setRGB('059669'); // Hijau
                    }
                }

                // Center alignment untuk tanggal dan status
                $sheet->getStyle("B{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("K{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $rowNumber++;
        }

        return $styles;
    }
}
