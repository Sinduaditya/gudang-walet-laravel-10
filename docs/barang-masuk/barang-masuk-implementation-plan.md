# Implementation Plan: Barang Masuk — Security Fix

## Background

Berdasarkan hasil security scan di `barang-masuk-analysis.md`, plan ini merangkum semua kerentanan yang ditemukan beserta status pengerjaannya.

---

## Issue yang Sudah Diperbaiki (Session Ini)

| # | Issue | Severity | Status |
|---|-------|----------|--------|
| V-01 | Threshold `is_flagged_red` tidak konsisten create vs update | 🟡 MEDIUM | ✅ Done |
| V-02 | `isPercentageAboveThreshold()` dan semua view konsisten 2% | 🟡 MEDIUM | ✅ Done |
| V-03 | FormRequest `authorize()` hardcoded `true` | 🟡 MEDIUM | ✅ Done |
| V-05 | Error message internal terekspos ke user | 🟢 LOW | ✅ Done |
| V-07 | Tombol Edit di `show.blade.php` tidak di-disable | 🟢 LOW | ✅ Done |

---

## Issue yang Sudah Diperbaiki (Session Sebelumnya)

| # | Issue | Severity | Status |
|---|-------|----------|--------|
| — | `percentage_difference` fillable | High | ✅ Done |
| — | Validasi edit/delete jika graded | High | ✅ Done |
| — | XSS via innerHTML | CRITICAL | ✅ Done |
| — | Edit form UX (graded items) | MEDIUM | ✅ Done |
| — | Add item bypass protection | MEDIUM | ✅ Done |

---

## Temuan Baru — Sudah Diperbaiki (V-09 s/d V-16)

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| V-09 | `console.log` debug aktif di `step3.blade.php` | 🟡 MEDIUM | P1 | ✅ Done |
| V-10 | Soft delete `receiptItems` tanpa `deleted_by` saat update | 🟡 MEDIUM | P1 | ✅ Done |
| V-11 | Validasi `after_or_equal:receipt_date` hilang di `update()` | 🟡 MEDIUM | P1 | ✅ Done |
| V-12 | Service re-throw exception dengan pesan DB internal | 🟡 MEDIUM | P2 | ✅ Done |
| V-13 | N+1 query — `sortingResults()->exists()` di loop | 🟢 LOW | P2 | ✅ Done |
| V-14 | Dead code — `$monthName` tidak dipakai di `exportToExcel` | 🟢 LOW | P3 | ✅ Done |
| V-15 | Export tanpa batas data — risiko memory exhausted | 🟢 LOW | P2 | ✅ Done |
| V-16 | Tombol "Kembali" Step 2 tidak reset session | 🟢 LOW | P3 | ✅ Done |

Remaining dari sebelumnya:

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| V-04 | Session data tidak divalidasi ulang di `storeFinal` | 🟡 MEDIUM | P3 | 🟡 Low Risk |
| V-06 | Tidak ada rate limiting pada endpoint write | 🟢 LOW | P3 | 🔴 Open |
| V-08 | Tidak ada RBAC/role check | 🟢 LOW | Future | Future |

---

## Proposed Changes

### P1 — Kerjakan Dulu (Bug + Data Integrity)

---

#### Fix V-09: Hapus `console.log` Debug di `step3.blade.php`

##### [MODIFY] [step3.blade.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/resources/views/admin/incoming_goods/step3.blade.php)

Hapus semua `console.log` di fungsi `calculateDifference()`:

```javascript
// Hapus baris-baris berikut dari step3.blade.php:
console.log('=== DEBUG CALCULATION ===');
console.log('Grade ID:', gradeId, 'Berat Awal:', beratAwal);
console.log('Berat Akhir:', beratAkhir);
console.log('Selisih:', selisih);
console.log('Decimal:', decimal);
console.log('Percentage:', percentage);
console.log('Final decimal:', decimalText);
console.log('Final percentage:', percentageText);
console.log('=== END DEBUG ===');
```

---

#### Fix V-10: Tambahkan `deleted_by` Saat Soft Delete di `updateReceipt()`

##### [MODIFY] [IncomingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/IncomingGoods/IncomingGoodsService.php)

Update blok delete items di `updateReceipt()` baris 155-158:

```php
// Sebelum (baris 155-158) — tidak ada deleted_by
$receipt->receiptItems()->get()->each(function ($item) {
    $item->delete();
});

// Sesudah — konsisten dengan deleteReceipt()
$receipt->receiptItems()->get()->each(function ($item) {
    $item->deleted_by = auth()->id();  // ✅ Audit trail
    $item->save();
    $item->delete();
});
```

---

#### Fix V-11: Tambahkan Validasi `after_or_equal` di `update()`

##### [MODIFY] [IncomingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/IncomingGoodsController.php)

Update validasi di method `update()` baris 184:

```php
// Sebelum (baris 184) — tidak ada business rule
'unloading_date' => 'required|date',

// Sesudah — konsisten dengan Step1Request
'unloading_date' => 'required|date|after_or_equal:receipt_date',
```

---

### P2 — Kerjakan Berikutnya (Code Quality + Performance)

---

#### Fix V-12: Bersihkan Re-throw di Service

##### [MODIFY] [IncomingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/IncomingGoods/IncomingGoodsService.php)

Update tiga catch block di `createPurchaseReceipt()`, `updateReceipt()`, `deleteReceipt()`:

```php
// Sebelum — wrap exception dengan pesan tambahan (double-wrapping)
} catch (Exception $e) {
    throw new Exception('Gagal menyimpan data: ' . $e->getMessage());
}

// Sesudah — re-throw original exception agar controller bisa handle dengan bersih
} catch (Exception $e) {
    throw $e;
}
```

> **Catatan:** Controller sudah handle user-facing message via `Log::error()` + pesan generik. Service tidak perlu wrap lagi.

---

#### Fix V-13: Eager Load `sortingResults` untuk Hindari N+1 Query

##### [MODIFY] [IncomingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/IncomingGoodsController.php)

Update `show()` dan `edit()` untuk eager load `sortingResults`:

```php
// show() baris 141 — sebelum
$receipt = \App\Models\PurchaseReceipt::with(['supplier', 'receiptItems.gradeSupplier'])->findOrFail($id);

// show() — sesudah: tambah sortingResults ke eager load
$receipt = \App\Models\PurchaseReceipt::with([
    'supplier',
    'receiptItems.gradeSupplier',
    'receiptItems.sortingResults',   // ✅ Eager load
])->findOrFail($id);
```

##### [MODIFY] [IncomingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/IncomingGoods/IncomingGoodsService.php)

Update `getReceiptById()` baris 70:

```php
// Sebelum
return PurchaseReceipt::with(['supplier', 'receiptItems.gradeSupplier'])->findOrFail($id);

// Sesudah
return PurchaseReceipt::with([
    'supplier',
    'receiptItems.gradeSupplier',
    'receiptItems.sortingResults',   // ✅ Eager load untuk edit & show
])->findOrFail($id);
```

Setelah eager load, update Blade agar tidak trigger query baru:

```blade
{{-- show.blade.php dan edit.blade.php — ganti ->exists() dengan ->isNotEmpty() --}}
@php
    $isReceiptGraded = $receipt->receiptItems->contains(
        fn($item) => $item->sortingResults->isNotEmpty()  // ✅ Tidak ada query baru
    );
@endphp
```

Dan di `edit()` controller baris 158-162:
```php
// Sebelum — N+1 query
foreach ($receipt->receiptItems as $item) {
    if ($item->sortingResults()->exists()) {  // ⚠️ Query per item

// Sesudah — pakai relasi yang sudah di-load
foreach ($receipt->receiptItems as $item) {
    if ($item->sortingResults->isNotEmpty()) {  // ✅ No extra query
```

---

#### Fix V-15: Paksa Filter untuk Export / Batasi Data

##### [MODIFY] [IncomingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/IncomingGoodsController.php)

Tambahkan guard di `export()` — wajib pilih salah satu filter:

```php
public function export(Request $request)
{
    // ✅ Paksa minimal satu filter (bulan atau tahun)
    if (empty($request->get('month')) && empty($request->get('year'))) {
        return back()->with('error', 'Pilih minimal filter Bulan atau Tahun sebelum export.');
    }

    try {
        $filters = [
            'month' => $request->get('month'),
            'year'  => $request->get('year'),
        ];
        return $this->incomingGoodsService->exportToExcel($filters);
    } catch (\Exception $e) {
        Log::error('IncomingGoods export error: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
        ]);
        return back()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
    }
}
```

---

### P3 — Kerjakan Bila Ada Waktu

---

#### Fix V-14: Hapus Dead Code `$monthName` di `exportToExcel`

##### [MODIFY] [IncomingGoodsService.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Services/IncomingGoods/IncomingGoodsService.php)

Sederhanakan method `exportToExcel()` — hapus `$monthNames` yang duplikat dan `$monthName` yang tidak dipakai:

```php
// Sebelum — duplikat array $monthNames dan $monthName tidak dipakai
public function exportToExcel($filters = [])
{
    $filename = 'laporan_barang_masuk';

    if (!empty($filters['month']) && !empty($filters['year'])) {
        $monthNames = [...]; // ← array duplikat
        $monthName = $monthNames[...]; // ← tidak dipakai
        $filename .= '_bulan_' . $filters['month'] . '_tahun_' . $filters['year'];
    } elseif (!empty($filters['year'])) {
        $filename .= '_tahun_' . $filters['year'];
    } elseif (!empty($filters['month'])) {
        $monthNames = [...]; // ← array duplikat kedua
        $monthName = $monthNames[...]; // ← tidak dipakai
        $filename .= '_bulan_' . $filters['month'];
    }
    // ...
}

// Sesudah — bersih, tidak ada dead code
public function exportToExcel($filters = [])
{
    $filename = 'laporan_barang_masuk';

    if (!empty($filters['month'])) {
        $filename .= '_bulan_' . $filters['month'];
    }
    if (!empty($filters['year'])) {
        $filename .= '_tahun_' . $filters['year'];
    }

    $filename .= '_' . date('Y-m-d') . '.xlsx';

    return Excel::download(new IncomingGoodsExport($filters), $filename);
}
```

---

#### Fix V-04: Re-validasi Session Data di `storeFinal`

##### [MODIFY] [IncomingGoodsController.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/app/Http/Controllers/Feature/IncomingGoodsController.php)

Tambahkan validasi isi session sebelum digunakan di `storeFinal()`:

```php
public function storeFinal(Step3Request $request)
{
    if (!session()->has('step1_data') || !session()->has('step2_data')) {
        return redirect()->route('incoming-goods.step1')->with('error', 'Silakan mulai dari awal');
    }

    $step1Data = session('step1_data');
    $step2Data = session('step2_data');

    // ✅ Re-validasi isi session
    $v = validator($step1Data, [
        'supplier_id'    => 'required|exists:suppliers,id',
        'receipt_date'   => 'required|date',
        'unloading_date' => 'required|date|after_or_equal:receipt_date',
        'grade_ids'      => 'required|array|min:1',
        'grade_ids.*'    => 'exists:grades_supplier,id',
    ]);

    if ($v->fails()) {
        $this->incomingGoodsService->clearWizardSession();
        return redirect()->route('incoming-goods.step1')
            ->with('error', 'Data sesi tidak valid. Silakan mulai dari awal.');
    }

    try {
        $step3Data = $request->validated();
        $receipt = $this->incomingGoodsService->createPurchaseReceipt($step1Data, $step2Data, $step3Data);
        $this->incomingGoodsService->clearWizardSession();
        return redirect()->route('incoming-goods.show', $receipt->id)->with('success', 'Data barang masuk berhasil disimpan!');
    } catch (\Exception $e) {
        Log::error('IncomingGoods storeFinal error: ' . $e->getMessage(), ['user_id' => auth()->id()]);
        return back()->withInput()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
    }
}
```

---

#### Fix V-06: Tambahkan Rate Limiting

##### [MODIFY] [web.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/routes/web.php)

```php
Route::prefix('incoming-goods')->name('incoming-goods.')->group(function () {
    // Read routes
    Route::get('/', ...)->name('index');
    Route::get('export', ...)->name('export');
    Route::get('step-1', ...)->name('step1');
    Route::get('step-2', ...)->name('step2');
    Route::get('step-3', ...)->name('step3');
    Route::get('cancel', ...)->name('cancel');
    Route::get('{id}/edit', ...)->name('edit');
    Route::get('{id}', ...)->name('show');

    // Write routes — dengan throttle
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('step-1', ...)->name('store-step1');
        Route::post('step-2', ...)->name('store-step2');
        Route::post('step-3', ...)->name('store-final');
        Route::put('{id}', ...)->name('update');
        Route::delete('{id}', ...)->name('destroy');
    });
});
```

---

#### Fix V-16: Tombol "Kembali" Step 2

##### [MODIFY] [step2.blade.php](file:///home/sinduaditya/Documents/ristack/cici/gudang-walet-laravel-10/resources/views/admin/incoming_goods/step2.blade.php)

Opsi sederhana — arahkan ke cancel route agar session bersih:

```blade
{{-- Sebelum --}}
<a href="{{ route('incoming-goods.step1') }}">Kembali</a>

{{-- Sesudah — pakai cancel yang bersihkan session --}}
<a href="{{ route('incoming-goods.cancel') }}">Batal & Mulai Ulang</a>
```

> **Catatan:** Jika ingin user bisa kembali ke step1 tanpa kehilangan data step1, buat route baru `back-to-step1` yang hanya forget `step2_data`.

---

### Future

---

#### Fix V-08: RBAC / Role-Based Access Control

Implementasikan setelah ada kebutuhan multi-role user.

```php
// Contoh menggunakan Gate
Gate::define('manage-incoming-goods', function (User $user) {
    return in_array($user->role, ['admin', 'warehouse_operator']);
});

Route::middleware(['auth', 'can:manage-incoming-goods'])->group(function () {
    // write routes
});
```

---

## Urutan Pengerjaan

```
P1 (satu sesi — bug kritis):
  ├─ V-09: Hapus console.log di step3.blade.php
  ├─ V-10: Tambahkan deleted_by di updateReceipt() soft delete
  └─ V-11: Tambahkan after_or_equal di update() controller

P2 (satu sesi — code quality + performance):
  ├─ V-12: Bersihkan re-throw exception di service (3 method)
  ├─ V-13: Eager load sortingResults di controller & service
  └─ V-15: Paksa filter sebelum export di export() controller

P3 (opsional):
  ├─ V-14: Hapus dead code $monthName di exportToExcel
  ├─ V-04: Re-validasi session di storeFinal
  ├─ V-06: Tambahkan throttle di routes write
  └─ V-16: Fix tombol Kembali Step 2

Future:
  └─ V-08: RBAC implementation
```

---

## Files yang Akan Dimodifikasi (Next Session)

| File | V-ID | Perubahan |
|------|------|-----------|
| `resources/views/admin/incoming_goods/step3.blade.php` | V-09 | Hapus semua `console.log` debug |
| `app/Services/IncomingGoods/IncomingGoodsService.php` | V-10, V-12, V-13 | `deleted_by` di update, re-throw bersih, eager load |
| `app/Http/Controllers/Feature/IncomingGoodsController.php` | V-11, V-13, V-15, V-04 | `after_or_equal`, eager load, guard export, re-validasi session |
| `resources/views/admin/incoming_goods/show.blade.php` | V-13 | Ganti `->exists()` → `->isNotEmpty()` |
| `resources/views/admin/incoming_goods/edit.blade.php` | V-13 | Ganti `->exists()` → `->isNotEmpty()` |
| `routes/web.php` | V-06 | Tambah throttle |
| `resources/views/admin/incoming_goods/step2.blade.php` | V-16 | Fix tombol Kembali |

---

## Summary Status Lengkap

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| — | percentage_difference fillable | High | — | ✅ Done |
| — | Validasi edit/delete jika graded | High | — | ✅ Done |
| — | XSS via innerHTML | CRITICAL | — | ✅ Done |
| — | Edit form UX (graded items) | MEDIUM | — | ✅ Done |
| — | Add item bypass protection | MEDIUM | — | ✅ Done |
| — | Wizard idempotency | LOW | — | Future |
| V-01 | Threshold tidak konsisten create vs update | 🟡 MEDIUM | P1 | ✅ Done |
| V-02 | `isPercentageAboveThreshold()` & view konsisten 2% | 🟡 MEDIUM | P1 | ✅ Done |
| V-03 | FormRequest `authorize()` hardcoded `true` | 🟡 MEDIUM | P2 | ✅ Done |
| V-04 | Session tidak re-validated di `storeFinal` | 🟡 MEDIUM | P3 | 🔴 Open |
| V-05 | Error message DB terekspos ke user | 🟢 LOW | P2 | ✅ Done |
| V-06 | Tidak ada rate limiting endpoint write | 🟢 LOW | P3 | 🔴 Open |
| V-07 | Tombol Edit show.blade.php tidak conditional | 🟢 LOW | P2 | ✅ Done |
| V-08 | Tidak ada RBAC | 🟢 LOW | Future | Future |
| **V-09** | **`console.log` debug aktif di step3** | **🟡 MEDIUM** | **P1** | **✅ Done** |
| **V-10** | **Soft delete tanpa `deleted_by` di updateReceipt** | **🟡 MEDIUM** | **P1** | **✅ Done** |
| **V-11** | **Validasi `after_or_equal` hilang di update()** | **🟡 MEDIUM** | **P1** | **✅ Done** |
| V-12 | **Service re-throw dengan pesan DB** | **🟡 MEDIUM** | **P2** | **✅ Done** |
| **V-13** | **N+1 query sortingResults di loop** | **🟢 LOW** | **P2** | **✅ Done** |
| **V-14** | **Dead code `$monthName` di exportToExcel** | **🟢 LOW** | **P3** | **✅ Done** |
| **V-15** | **Export tanpa batas data** | **🟢 LOW** | **P2** | **✅ Done** |
| **V-16** | **Tombol Kembali Step 2 tidak reset session** | **🟢 LOW** | **P3** | **✅ Done** |