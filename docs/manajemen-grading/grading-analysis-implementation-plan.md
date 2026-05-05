# Implementation Plan: Manajemen Grading — Bug Fix & Security

## Background

Berdasarkan hasil analisis di `grading-analysis.md`, ditemukan **13 bug/kerentanan** (G-01 s/d G-13) pada modul Manajemen Grading. Plan ini mendefinisikan langkah perbaikan beserta prioritas dan tracking status.

---

## TODO Tracker

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| G-01 | Grading bisa di-edit meski sudah dipakai di transaksi keluar | 🔴 HIGH | P1 | ✅ Done |
| G-02 | Error message ekspos `$e->getMessage()` ke user | 🟡 MEDIUM | P1 | ✅ Done |
| G-03 | Status `receipt_item` tidak di-update saat edit grading | 🟡 MEDIUM | P1 | ✅ Done |
| G-04 | Silent fail jika `Gudang Utama` tidak ditemukan | 🟡 MEDIUM | P1 | ✅ Done |
| G-05 | `updateFullGrading()` dead method — tidak pernah dipanggil | 🟡 MEDIUM | P2 | ✅ Done |
| G-06 | Duplikasi query `getAllGrading` vs `getAllGradingForExport` | 🟡 MEDIUM | P2 | ✅ Done |
| G-07 | `update()` tidak pakai FormRequest | 🟡 MEDIUM | P2 | ✅ Done |
| G-08 | `step3.blade.php` kosong — file tidak berguna | 🟢 LOW | P3 | ✅ Done |
| G-09 | `idm_management_id` selalu `null` saat create grading | 🟢 LOW | P3 | ✅ Done |
| G-10 | Direct `Supplier::get()` di controller, bukan via service | 🟢 LOW | P3 | ✅ Done |
| G-11 | `abort(404)` di `show()`, tidak konsisten dengan redirect | 🟢 LOW | P3 | ✅ Done |
| G-12 | Export tanpa filter wajib — risiko memory exhausted | 🟢 LOW | P2 | ✅ Done |

> **Legend:** ☐ Open | 🔄 In Progress | ✅ Done

---

## Proposed Changes

### P1 — Kerjakan Dulu (Bug Kritis & Data Integrity)

---

#### Fix G-01: Proteksi Edit Grading yang Sudah Dipakai

**Masalah:** Tidak ada pengecekan apakah `sorting_result` sudah dipakai di transaksi barang keluar sebelum allow edit/delete.

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

Tambahkan pengecekan di `edit()` dan `update()`:

```php
// Tambahkan method helper private di controller
private function isGradingUsedInOutgoingTransaction($receiptItemId): bool
{
    $sortingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

    foreach ($sortingResults as $sr) {
        $hasOutgoing = \App\Models\InventoryTransaction::where('sorting_result_id', $sr->id)
            ->where('transaction_type', '!=', 'GRADING_IN')
            ->exists();

        if ($hasOutgoing) return true;
    }

    return false;
}

// Di edit() — tambahkan guard setelah get allGradingResults
public function edit(Request $request, $receiptItemId)
{
    $allGradingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

    if ($allGradingResults->isEmpty()) {
        return redirect()->route('grading-goods.index')->with('error', 'Data grading tidak ditemukan.');
    }

    // ✅ Cek apakah sudah dipakai di transaksi keluar
    if ($this->isGradingUsedInOutgoingTransaction($receiptItemId)) {
        return redirect()->route('grading-goods.show', $receiptItemId)
            ->with('error', 'Tidak dapat edit. Grading sudah digunakan dalam transaksi barang keluar.');
    }

    // ... lanjut normal
}

// Di update() — tambahkan guard yang sama
public function update(Request $request, $receiptItemId)
{
    if ($this->isGradingUsedInOutgoingTransaction($receiptItemId)) {
        return back()->with('error', 'Tidak dapat diperbarui. Grading sudah digunakan dalam transaksi barang keluar.');
    }

    // ... validasi + update normal
}
```

**Opsional — juga proteksi di `show.blade.php`:** Sembunyikan tombol Edit jika sudah ada transaksi keluar (sama seperti pola di barang masuk).

---

#### Fix G-02: Sembunyikan Error Message Internal dari User

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

Update tiga catch block:

```php
// storeStep2() baris 100-104
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('GradingGoods storeStep2 error: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'sorting_result_id' => $id,
    ]);
    return back()->withInput()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
}

// update() baris 196-200
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('GradingGoods update error: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'receipt_item_id' => $receiptItemId,
    ]);
    return back()->withInput()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
}

// destroy() baris 208-210
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('GradingGoods destroy error: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'receipt_item_id' => $receiptItemId,
    ]);
    return back()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
}
```

---

#### Fix G-03: Update Status `receipt_item` di `updateMultipleSortingResults()`

##### [MODIFY] [GradingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradingGoods/GradingGoodsService.php)

Tambahkan update status di akhir loop create sorting result baru:

```php
// updateMultipleSortingResults() — tambahkan setelah foreach loop
// baris sebelum `return $createdResults;`

// ✅ Pastikan status receipt_item tetap SELESAI_DISORTIR setelah edit
$receiptItem->update(['status' => ReceiptItem::STATUS_SELESAI_DISORTIR]);

return $createdResults;
```

---

#### Fix G-04: Throw Exception Jika `Gudang Utama` Tidak Ditemukan

##### [MODIFY] [GradingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradingGoods/GradingGoodsService.php)

Update method `createInventoryFromGrading()` baris 423-427:

```php
// Sebelum — silent fail
private function createInventoryFromGrading(SortingResult $sortingResult)
{
    $defaultLocation = Location::where('name', 'Gudang Utama')->first();

    if (!$defaultLocation || !$sortingResult->grade_company_id || !$sortingResult->weight_grams) {
        return;  // ⚠️ Silent fail
    }
    // ...
}

// Sesudah — throw + log
private function createInventoryFromGrading(SortingResult $sortingResult)
{
    $defaultLocation = Location::where('name', 'Gudang Utama')->first();

    if (!$defaultLocation) {
        Log::error('createInventoryFromGrading: Lokasi "Gudang Utama" tidak ditemukan!', [
            'sorting_result_id' => $sortingResult->id,
        ]);
        throw new \Exception('Lokasi "Gudang Utama" tidak ditemukan. Grading tidak dapat disimpan. Hubungi administrator.');
    }

    if (!$sortingResult->grade_company_id || !$sortingResult->weight_grams) {
        Log::warning('createInventoryFromGrading: grade_company_id atau weight_grams kosong — skip inventory.', [
            'sorting_result_id' => $sortingResult->id,
        ]);
        return;
    }
    // ... lanjut buat inventory
}
```

---

### P2 — Kerjakan Berikutnya (Code Quality)

---

#### Fix G-05: Hapus Dead Method `updateFullGrading()`

##### [MODIFY] [GradingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradingGoods/GradingGoodsService.php)

Hapus method `updateFullGrading()` baris 362-391 karena tidak pernah dipanggil:

```php
// Hapus seluruh method ini:
public function updateFullGrading($sortingResultId, array $data)
{
    return DB::transaction(function () use ($sortingResultId, $data) {
        // ...
    });
}
```

> **Pastikan dulu:** Lakukan grep untuk memastikan tidak ada pemanggilan di tempat lain sebelum hapus.

---

#### Fix G-06: Hilangkan Duplikasi Query dengan Private Builder

##### [MODIFY] [GradingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradingGoods/GradingGoodsService.php)

Ekstrak query ke method private, lalu pakai di `getAllGrading()` dan `getAllGradingForExport()`:

```php
// Tambahkan private method baru
private function buildGradingQuery(array $filters = [])
{
    $query = ReceiptItem::select([
        'receipt_items.id as receipt_item_id',
        'receipt_items.warehouse_weight_grams',
        'receipt_items.supplier_weight_grams',
        'receipt_items.status',
        'grades_supplier.name as grade_supplier_name',
        'purchase_receipts.receipt_date',
        'suppliers.name as supplier_name',
        DB::raw('MIN(sorting_results.grading_date) as grading_date'),
        DB::raw('COUNT(sorting_results.id) as total_grades'),
        DB::raw('SUM(sorting_results.weight_grams) as total_grading_weight'),
        DB::raw('MIN(sorting_results.id) as first_sorting_id')
    ])
        ->join('sorting_results', 'receipt_items.id', '=', 'sorting_results.receipt_item_id')
        ->leftJoin('grades_supplier', 'receipt_items.grade_supplier_id', '=', 'grades_supplier.id')
        ->leftJoin('purchase_receipts', 'receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
        ->leftJoin('suppliers', 'purchase_receipts.supplier_id', '=', 'suppliers.id')
        ->groupBy([...])
        ->orderBy('grading_date', 'desc')
        ->orderBy('suppliers.name', 'asc')
        ->whereNull('sorting_results.deleted_at');

    if (!empty($filters['month'])) $query->whereMonth('sorting_results.grading_date', $filters['month']);
    if (!empty($filters['year'])) $query->whereYear('sorting_results.grading_date', $filters['year']);
    if (!empty($filters['supplier_name'])) $query->where('suppliers.name', $filters['supplier_name']);
    if (!empty($filters['grading_date'])) $query->whereDate('sorting_results.grading_date', $filters['grading_date']);

    return $query;
}

// Update getAllGrading() — pakai builder
public function getAllGrading($filters = [], $perPage = 15)
{
    $results = $this->buildGradingQuery($filters)->paginate($perPage)->appends(request()->query());
    $results->getCollection()->transform(fn($item) => tap($item, fn($i) => $i->grading_difference = ($i->total_grading_weight ?? 0) - ($i->warehouse_weight_grams ?? 0)));
    return $results;
}

// Update getAllGradingForExport() — pakai builder
public function getAllGradingForExport($filters = [])
{
    $results = $this->buildGradingQuery($filters)->get();
    $results->transform(fn($item) => tap($item, fn($i) => $i->grading_difference = ($i->total_grading_weight ?? 0) - ($i->warehouse_weight_grams ?? 0)));
    return $results;
}
```

---

#### Fix G-07: Buat `UpdateGradingRequest` FormRequest

##### [NEW] [UpdateGradingRequest.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Requests/GradingGoods/UpdateGradingRequest.php)

```php
<?php

namespace App\Http\Requests\GradingGoods;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'grades'                      => 'required|array|min:1',
            'grades.*.grading_date'       => 'required|date',
            'grades.*.grade_company_name' => 'required|string|max:255',
            'grades.*.quantity'           => 'required|numeric|min:0',
            'grades.*.weight_grams'       => 'required|numeric|min:0',
            'grades.*.notes'              => 'nullable|string|max:1000',
            'grades.*.outgoing_type'      => 'nullable|in:penjualan_langsung,internal,external',
            'grades.*.category_grade'     => 'nullable|in:IDM A,IDM B',
            'global_notes'                => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required'                      => 'Minimal satu grade harus diisi.',
            'grades.*.grading_date.required'       => 'Tanggal grading harus diisi.',
            'grades.*.grade_company_name.required' => 'Nama grade company harus diisi.',
            'grades.*.quantity.required'           => 'Jumlah item harus diisi.',
            'grades.*.weight_grams.required'       => 'Berat hasil harus diisi.',
        ];
    }
}
```

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

Update `update()` untuk pakai FormRequest:

```php
// Sebelum
public function update(Request $request, $receiptItemId)
{
    $request->validate([...]);

// Sesudah — import di atas + ganti parameter
use App\Http\Requests\GradingGoods\UpdateGradingRequest;

public function update(UpdateGradingRequest $request, $receiptItemId)
{
    // Hapus $request->validate() — sudah dihandle FormRequest
```

---

#### Fix G-12: Paksa Filter untuk Export

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

Tambahkan guard di `export()`:

```php
public function export(Request $request)
{
    // ✅ Wajib pilih minimal satu filter
    $hasFilter = !empty($request->get('month'))
        || !empty($request->get('year'))
        || !empty($request->get('supplier_name'))
        || !empty($request->get('grading_date'));

    if (!$hasFilter) {
        return back()->with('error', 'Pilih minimal satu filter (Bulan, Tahun, Supplier, atau Tanggal Grading) sebelum export.');
    }

    // ... lanjut export
}
```

---

### P3 — Opsional / Nice to Have

---

#### Fix G-08: Hapus `step3.blade.php` yang Kosong

```bash
rm resources/views/admin/grading-goods/step3.blade.php
```

Pastikan tidak ada route atau view yang mereferensikannya terlebih dahulu.

---

#### Fix G-09: Dokumentasikan `idm_management_id`

Karena `idm_management_id` tidak pernah di-set saat create grading, ada dua opsi:

1. **Hapus dari fillable** jika memang tidak dipakai di alur grading (biarkan diset dari modul IDM jika perlu).
2. **Dokumentasikan** dengan komentar bahwa field ini diset oleh modul IDM, bukan oleh proses grading biasa.

```php
// SortingResult.php — opsi tambah komentar
protected $fillable = [
    // ... fields lain
    'idm_management_id',  // Di-set oleh modul IDM Management, bukan proses grading biasa
];
```

---

#### Fix G-10: Pindahkan `Supplier::get()` ke Service

##### [MODIFY] [GradingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradingGoods/GradingGoodsService.php)

```php
// Tambahkan method
public function getSuppliers()
{
    return \App\Models\Supplier::orderBy('name')->get();
}
```

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

```php
// Sebelum
$suppliers = \App\Models\Supplier::orderBy('name')->get();

// Sesudah
$suppliers = $this->gradingGoodsService->getSuppliers();
```

---

#### Fix G-11: Ganti `abort(404)` dengan Redirect di `show()`

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

```php
// Sebelum
if ($allGradingResults->isEmpty()) {
    return abort(404, 'Grading not found');
}

// Sesudah — konsisten dengan pola lain
if ($allGradingResults->isEmpty()) {
    return redirect()->route('grading-goods.index')
        ->with('error', 'Data grading tidak ditemukan.');
}
```

---

## Files yang Akan Dimodifikasi

| File | G-ID | Perubahan |
|------|------|-----------|
| `app/Http/Controllers/Feature/GradingGoodsController.php` | G-01, G-02, G-07, G-10, G-11, G-12 | Guard edit/update, error logging, FormRequest, redirect |
| `app/Services/GradingGoods/GradingGoodsService.php` | G-03, G-04, G-05, G-06, G-18 | Status update, throw exception, hapus dead method, refactor query, opsi fix orphan |
| `app/Http/Requests/GradingGoods/UpdateGradingRequest.php` | G-07 | **[NEW]** FormRequest untuk update |
| `app/Http/Requests/GradingGoods/Step1Request.php` | G-22 | Tambah validasi `before_or_equal:today` |
| `app/Http/Requests/GradeCompany/GradeCompanyRequest.php` | G-14, G-23 | Ganti `return true` → `auth()->check()`, perbaiki copy-paste messages |
| `app/Services/GradeCompany/GradeCompanyService.php` | G-15, G-17 | Cek usage sebelum delete, validasi `bulkAssign` |
| `app/Http/Controllers/Master/GradeCompanyController.php` | G-16 | Error logging export |
| `app/Models/SortingResult.php` | G-09 | Tambah komentar dokumentasi |
| `resources/views/admin/grading-goods/index.blade.php` | G-19, G-20 | Standarkan threshold 2%, ganti hardcoded URL delete |
| `resources/views/admin/grading-goods/step2.blade.php` | G-21 | Tambah link cancel yang cleanup orphan |
| `resources/views/admin/grading-goods/step3.blade.php` | G-08 | **[DELETE]** File kosong |
| `routes/web.php` | G-21 | **[NEW ROUTE]** `cancel.step2` |

---

## Urutan Pengerjaan

```
P1 (kerjakan dulu — bug kritis + data integrity):
  ├─ G-01: Proteksi edit/update jika sudah ada transaksi keluar       ✅ Done
  ├─ G-02: Error message logging (bukan ekspos ke user)
  ├─ G-03: Update status receipt_item di updateMultipleSortingResults()
  ├─ G-04: Throw exception jika Gudang Utama tidak ada
  ├─ G-14: GradeCompanyRequest authorize() → auth()->check()
  ├─ G-15: GradeCompanyService::delete() cek usage sebelum hapus
  └─ G-19: Standarkan threshold persentase ke 2%

P2 (kerjakan berikutnya — code quality & UX):
  ├─ G-05: Hapus dead method updateFullGrading()
  ├─ G-06: Refactor duplikasi query ke buildGradingQuery()
  ├─ G-07: Buat UpdateGradingRequest + gunakan di controller
  ├─ G-12: Guard filter wajib di export()                             ✅ Done
  ├─ G-16: GradeCompanyController export() error logging
  ├─ G-17: bulkAssign() validasi parentGradeId exist
  ├─ G-18: Fix orphan SortingResult dari Step 1 (cleanup / refactor)
  ├─ G-20: Ganti URL delete hardcoded ke route helper
  └─ G-21: Tombol Kembali Step 2 → cleanup orphan SortingResult

P3 (opsional / nice to have):
  ├─ G-08: Hapus step3.blade.php yang kosong
  ├─ G-09: Dokumentasikan idm_management_id
  ├─ G-10: Pindah Supplier::get() ke service
  ├─ G-11: Ganti abort(404) dengan redirect di show()
  ├─ G-22: Tambah validasi before_or_equal:today di Step1Request
  └─ G-23: Perbaiki copy-paste messages di GradeCompanyRequest
```

---

## Fix Detail — Temuan Baru G-14 s/d G-23

### P1 — G-14: `GradeCompanyRequest::authorize()` Hardcoded `return true`

##### [MODIFY] [GradeCompanyRequest.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Requests/GradeCompany/GradeCompanyRequest.php)

```php
// Sebelum
public function authorize(): bool
{
    return true;
}

// Sesudah
public function authorize(): bool
{
    return auth()->check();
}
```

---

### P1 — G-15: `GradeCompanyService::delete()` Tidak Cek Penggunaan

##### [MODIFY] [GradeCompanyService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradeCompany/GradeCompanyService.php)

```php
public function delete(int $id)
{
    $gradeCompany = $this->getById($id);

    // ✅ Cek apakah masih digunakan di sorting_results
    $usageCount = $gradeCompany->sortingResults()->count();
    if ($usageCount > 0) {
        throw new \Exception(
            "Grade company \"{$gradeCompany->name}\" tidak dapat dihapus karena masih digunakan di {$usageCount} data grading."
        );
    }

    if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
        Storage::disk('public')->delete($gradeCompany->image_url);
    }

    $gradeCompany->delete();
    return true;
}
```

##### [MODIFY] [GradeCompanyController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Master/GradeCompanyController.php)

Karena service sekarang bisa throw exception, controller harus wrap dengan try-catch:

```php
public function destroy(int $id)
{
    try {
        $this->GradeCompanyService->delete($id);
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil dihapus.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

> **Catatan:** Khusus G-15, pesan exception dari `delete()` **boleh** diekspos ke user karena isinya adalah pesan validasi bisnis (bukan pesan internal DB/sistem). Berbeda dengan G-16 yang ekspos error sistem.

---

### P1 — G-19: Standarkan Threshold Persentase ke 2%

##### [MODIFY] [index.blade.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/resources/views/admin/grading-goods/index.blade.php)

```php
// Sebelum
if ($percentage > 5) {
    $percentageClass = 'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
} elseif ($percentage > 1) {
    $percentageClass = 'text-orange-600 font-semibold';
} elseif ($percentage > 0) {
    $percentageClass = 'text-green-600';
}

// Sesudah — konsisten dengan threshold 2% di Barang Masuk
if ($percentage > 2) {
    $percentageClass = 'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
} elseif ($percentage > 0) {
    $percentageClass = 'text-orange-600 font-semibold';
}

// Update juga kondisi emoji warning
@if ($percentage > 2)  {{-- Sebelumnya > 5 --}}
    ⚠️
@endif
```

---

### P2 — G-16: `GradeCompanyController::export()` Error Logging

##### [MODIFY] [GradeCompanyController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Master/GradeCompanyController.php)

```php
// Tambah use di atas
use Illuminate\Support\Facades\Log;

public function export()
{
    try {
        return $this->GradeCompanyService->exportToExcel();
    } catch (\Exception $e) {
        Log::error('GradeCompany export error: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
        ]);
        return back()->with('error', 'Gagal mengekspor data. Silakan coba lagi.');
    }
}
```

---

### P2 — G-17: `bulkAssign()` Validasi `$parentGradeId`

##### [MODIFY] [GradeCompanyService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/GradeCompany/GradeCompanyService.php)

```php
// Sebelum
public function bulkAssign(int $parentGradeId, array $gradeCompanyIds)
{
    return GradeCompany::whereIn('id', $gradeCompanyIds)->update(['parent_grade_company_id' => $parentGradeId]);
}

// Sesudah
public function bulkAssign(int $parentGradeId, array $gradeCompanyIds)
{
    // ✅ Validasi parent ada
    GradeCompany::findOrFail($parentGradeId);

    // ✅ Validasi IDs tidak kosong
    if (empty($gradeCompanyIds)) {
        throw new \InvalidArgumentException('Tidak ada grade company yang dipilih.');
    }

    return GradeCompany::whereIn('id', $gradeCompanyIds)->update(['parent_grade_company_id' => $parentGradeId]);
}
```

---

### P2 — G-18: Orphan `SortingResult` dari Step 1

Ada dua pendekatan. **Pilih salah satu:**

#### Opsi A (Cepat): Artisan Command Cleanup

##### [NEW] `app/Console/Commands/CleanOrphanSortingResults.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\SortingResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOrphanSortingResults extends Command
{
    protected $signature = 'grading:clean-orphan {--hours=24 : Hapus orphan lebih tua dari X jam}';
    protected $description = 'Hapus SortingResult placeholder dari Step 1 yang tidak dilanjutkan ke Step 2';

    public function handle()
    {
        $hours = $this->option('hours');

        $orphans = SortingResult::whereNull('weight_grams')
            ->whereNull('grade_company_id')
            ->where('created_at', '<', now()->subHours($hours))
            ->get();

        $count = $orphans->count();

        foreach ($orphans as $orphan) {
            $orphan->deleted_by = null; // sistem
            $orphan->save();
            $orphan->delete();
        }

        Log::info("CleanOrphanSortingResults: {$count} orphan records dihapus.");
        $this->info("{$count} orphan sorting results berhasil dihapus.");
    }
}
```

Daftarkan di `app/Console/Kernel.php`:
```php
// Di method schedule()
$schedule->command('grading:clean-orphan')->daily();
```

#### Opsi B (Proper): Tidak Buat Record di Step 1

Refactor alur Step 1 → simpan data sebagai session, buat `SortingResult` hanya saat Step 2 disubmit. Memerlukan perubahan signifikan di controller dan service.

---

### P2 — G-20: URL Delete Hardcoded → Route Helper

##### [MODIFY] [index.blade.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/resources/views/admin/grading-goods/index.blade.php)

```html
<!-- Sebelum: modal tanpa data attribute -->
<div id="deleteModal" class="hidden fixed ...">

<!-- Sesudah: tambah data-url -->
<div id="deleteModal"
     class="hidden fixed ..."
     data-url-template="{{ route('grading-goods.destroy', ['receiptItemId' => ':id']) }}">
```

```javascript
// Sebelum
function confirmDelete(id) {
    const form = document.getElementById('deleteForm');
    form.action = `grading-goods/delete/${id}`;
    modal.classList.remove('hidden');
}

// Sesudah
function confirmDelete(id) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    const urlTemplate = modal.dataset.urlTemplate;
    form.action = urlTemplate.replace(':id', id);
    modal.classList.remove('hidden');
}
```

---

### P2 — G-21: Tombol Kembali Step 2 Cleanup Orphan

##### [NEW ROUTE] [web.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/routes/web.php)

```php
// Tambahkan di dalam grup grading-goods
Route::delete('/cancel/{sortingResultId}', [GradingGoodsController::class, 'cancelStep2'])->name('cancel.step2');
```

##### [MODIFY] [GradingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/GradingGoodsController.php)

```php
// Method baru
public function cancelStep2($sortingResultId)
{
    $sortingResult = \App\Models\SortingResult::find($sortingResultId);

    // Hanya hapus jika masih placeholder (weight_grams null = belum diisi Step 2)
    if ($sortingResult && is_null($sortingResult->weight_grams)) {
        $sortingResult->deleted_by = auth()->id();
        $sortingResult->save();
        $sortingResult->delete();

        Log::info('Orphan SortingResult dari Step 1 dihapus via cancel', [
            'sorting_result_id' => $sortingResultId,
            'user_id' => auth()->id(),
        ]);
    }

    return redirect()->route('grading-goods.step1')->with('info', 'Proses grading dibatalkan.');
}
```

##### [MODIFY] [step2.blade.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/resources/views/admin/grading-goods/step2.blade.php)

```html
<!-- Sebelum: link biasa ke step1 -->
<a href="{{ route('grading-goods.step1') }}" class="...">Kembali</a>

<!-- Sesudah: form DELETE ke cancel route -->
<form method="POST" action="{{ route('grading-goods.cancel.step2', $sortingResult->id) }}" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
        Kembali
    </button>
</form>
```

---

### P3 — G-22: Validasi `grading_date` Tidak Boleh Masa Depan

##### [MODIFY] `app/Http/Requests/GradingGoods/Step1Request.php`

```php
// Tambahkan rule before_or_equal:today
'grading_date' => 'required|date|before_or_equal:today',
```

---

### P3 — G-23: Perbaiki Copy-Paste Messages di `GradeCompanyRequest`

##### [MODIFY] [GradeCompanyRequest.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Requests/GradeCompany/GradeCompanyRequest.php)

```php
// Sebelum (copy-paste dari LocationRequest)
'name.required' => 'Nama lokasi wajib diisi.',
'name.string'   => 'Nama lokasi harus berupa teks.',
'name.max'      => 'Nama lokasi maksimal 255 karakter.',

// Sesudah
'name.required' => 'Nama grade company wajib diisi.',
'name.string'   => 'Nama grade company harus berupa teks.',
'name.max'      => 'Nama grade company maksimal 255 karakter.',
```

---

## Summary Status

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| G-01 | Grading bisa di-edit meski sudah dipakai di transaksi keluar | 🔴 HIGH | P1 | ✅ Done |
| G-02 | Error message ekspos `$e->getMessage()` ke user (controller grading) | 🟡 MEDIUM | P1 | ✅ Done |
| G-03 | Status `receipt_item` tidak di-update saat edit grading | 🟡 MEDIUM | P1 | ✅ Done |
| G-04 | Silent fail jika `Gudang Utama` tidak ditemukan | 🟡 MEDIUM | P1 | ✅ Done |
| G-05 | `updateFullGrading()` dead method | 🟡 MEDIUM | P2 | ✅ Done |
| G-06 | Duplikasi query `getAllGrading` vs `getAllGradingForExport` | 🟡 MEDIUM | P2 | ✅ Done |
| G-07 | `update()` tidak pakai FormRequest | 🟡 MEDIUM | P2 | ✅ Done |
| G-08 | `step3.blade.php` kosong | 🟢 LOW | P3 | ✅ Done |
| G-09 | `idm_management_id` selalu `null` | 🟢 LOW | P3 | ✅ Done |
| G-10 | Direct `Supplier::get()` di controller | 🟢 LOW | P3 | ✅ Done |
| G-11 | `abort(404)` tidak konsisten | 🟢 LOW | P3 | ✅ Done |
| G-12 | Export tanpa filter wajib | 🟢 LOW | P2 | ✅ Done |
| G-13 | Tombol Edit conditional `$canEdit` di show.blade | 🟡 MEDIUM | P1 | ✅ Done |
| G-14 | `GradeCompanyRequest::authorize()` hardcoded `return true` | 🟡 MEDIUM | P1 | ✅ Done |
| G-15 | `GradeCompanyService::delete()` tidak cek usage di sorting_results | 🟡 MEDIUM | P1 | ✅ Done |
| G-16 | `GradeCompanyController::export()` ekspos error message | 🟡 MEDIUM | P2 | ✅ Done |
| G-17 | `bulkAssign()` tidak validasi `$parentGradeId` exist | 🟡 MEDIUM | P2 | ✅ Done |
| G-18 | Orphan `SortingResult` dari Step 1 yang tidak dilanjutkan | 🟡 MEDIUM | P2 | ✅ Done |
| G-19 | Threshold persentase inkonsisten: 5% (grading) vs 2% (barang masuk) | 🟡 MEDIUM | P1 | ✅ Done |
| G-20 | URL delete hardcoded string, bukan route helper | 🟢 LOW | P2 | ✅ Done |
| G-21 | Tombol "Kembali" Step 2 tidak cleanup orphan SortingResult | 🟢 LOW | P2 | ✅ Done |
| G-22 | `grading_date` tidak ada validasi `before_or_equal:today` | 🟢 LOW | P3 | ✅ Done |
| G-23 | Copy-paste bug: `GradeCompanyRequest` messages pakai "Nama lokasi" | 🟢 LOW | P3 | ✅ Done |

