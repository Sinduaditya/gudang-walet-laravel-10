# Analisis Barang Masuk (Incoming Goods)

## Overview

Stock memang tidak masuk saat barang datang - ini by design. Barang masuk status "MENTAH", stock baru di-track setelah grading (status "SELESAI_DISORTIR").

## Alur Data Saat Ini

```
Barang Masuk (3 Step Wizard)
    │
    ├─→ purchase_receipts     (header)
    │       - supplier_id
    │       - receipt_date
    │       - unloading_date
    │       - notes
    │       - created_by, updated_by
    │
    └─→ receipt_items         (detail per grade)
            - grade_supplier_id
            - supplier_weight_grams   (berat nota/supplier)
            - warehouse_weight_grams  (berat timbangan gudang)
            - difference_grams         (selisih)
            - percentage_difference
            - moisture_percentage      (kadar air)
            - is_flagged_red           (flag jika selisih > 2%)
            - status                   (MENTAH / SELESAI_DISORTIR)
            - created_by, updated_by

    ⚠️ TIDAK ADA inventory_transactions DIBUAT PADA TAHAP INI
```

---

## Security Review

### ✅ Yang Sudah Baik

| Aspek | Status | Detail |
|-------|--------|--------|
| Mass Assignment Protection | ✅ | `fillable` sudah ditentukan di semua model |
| SQL Injection | ✅ | Eloquent ORM, tidak ada raw query |
| CSRF | ✅ | Laravel default CSRF token di semua form |
| Auth Middleware | ✅ | `auth` middleware di semua routes `/admin/*` |
| Soft Deletes | ✅ | `PurchaseReceipt` & `ReceiptItem` pakai SoftDeletes |
| Input Validation | ✅ | FormRequest untuk setiap step (Step1/2/3Request) |
| XSS — Blade | ✅ | `{{ }}` auto-escape di semua blade files |
| XSS — JavaScript | ✅ | `textContent` (bukan `innerHTML`) + `escapeHtml()` |
| Grading Protection — Edit | ✅ | Controller & Service cek `sortingResults` sebelum edit |
| Grading Protection — Delete | ✅ | Service cek `sortingResults` sebelum delete |
| DB Transaction | ✅ | Create & Update dibungkus `DB::transaction()` |
| Audit Trail | ✅ | `created_by`, `updated_by`, `deleted_by` di semua operasi |
| Threshold Konsisten | ✅ | `FLAG_THRESHOLD_PERCENT = 2` di model, dipakai di service & blade |
| Error Message | ✅ | Exception di-log, user hanya dapat pesan generik |
| FormRequest authorize() | ✅ | Semua FormRequest pakai `auth()->check()` |
| Tombol Edit Conditional | ✅ | `show.blade.php` sembunyikan tombol Edit jika sudah graded |

---

### ⚠️ Kerentanan yang Ditemukan

#### Session Sebelumnya (Sudah Diperbaiki)

| Severity | Aspek | Masalah | Lokasi | Status |
|----------|-------|---------|--------|--------|
| 🔴 CRITICAL | XSS | `innerHTML` dengan data user | `edit.blade.php:309-313` | ✅ Fixed |
| 🟡 MEDIUM | Logic | Edit masih bisa diakses padahal sudah di-grading | View + Controller | ✅ Fixed |
| 🟡 MEDIUM | Logic | Add new item bypass validasi grading | `edit.blade.php:189` | ✅ Fixed |
| 🟢 LOW | Session | Wizard tanpa idempotency token | `IncomingGoodsController` | Future |

#### Session Ini (Sudah Diperbaiki)

| Severity | ID | Aspek | Masalah | Lokasi | Status |
|----------|----|-------|---------|--------|--------|
| 🟡 MEDIUM | V-01 | Logic | Threshold `is_flagged_red` tidak konsisten (create: 2%, update: 5%) | `IncomingGoodsService.php:105 vs 170` | ✅ Fixed |
| 🟡 MEDIUM | V-02 | Logic | `isPercentageAboveThreshold()` di model pakai 5%, view pakai 2% | `ReceiptItem.php:43` vs blade | ✅ Fixed |
| 🟡 MEDIUM | V-03 | Authorization | `authorize()` di semua FormRequest hardcoded `return true` | `Step1/2/3Request.php:10` | ✅ Fixed |
| 🟡 MEDIUM | V-04 | Session | Data step1/step2 di session tidak divalidasi ulang saat `storeFinal` | `IncomingGoodsController.php:116-117` | 🟡 Low Risk |
| 🟢 LOW | V-05 | Error Handling | Exception message dari DB langsung dikirim ke user via flash | `IncomingGoodsController.php:128, 200, 222` | ✅ Fixed |
| 🟢 LOW | V-06 | Missing Feature | Tidak ada rate limiting pada endpoint write | `routes/web.php:43-65` | 🔴 Open |
| 🟢 LOW | V-07 | Logic | `show.blade.php` tidak proteksi tombol Edit | `show.blade.php:17-24` | ✅ Fixed |
| 🟢 LOW | V-08 | Authorization | Tidak ada RBAC/role check | Semua routes | Future |

#### Scan Terbaru (Baru Ditemukan)

| Severity | ID | Aspek | Masalah | Lokasi | Status |
|----------|----|-------|---------|--------|--------|
| 🟡 MEDIUM | V-09 | Bug | `console.log` debug masih aktif di production — leak info struktur data | `step3.blade.php:180-212, 285-287` | ✅ Done |
| 🟡 MEDIUM | V-10 | Bug | Update receipt menghapus `deleted_by` audit trail pada `receiptItems` lama | `IncomingGoodsService.php:155-158` | ✅ Done |
| 🟡 MEDIUM | V-11 | Logic | Validasi `unloading_date` tidak ada di `update()` — hanya ada di Step1Request | `IncomingGoodsController.php:184` | ✅ Done |
| 🟡 MEDIUM | V-12 | Bug | Service `createPurchaseReceipt` re-throw exception dengan pesan yang mengandung detail DB | `IncomingGoodsService.php:125-127` | ✅ Done |
| 🟢 LOW | V-13 | N+1 Query | `show.blade.php` memanggil `sortingResults()->exists()` per-item di dalam loop | `show.blade.php:18-20` | ✅ Done |
| 🟢 LOW | V-14 | Code Quality | `exportToExcel` punya dead code — `$monthName` di-assign tapi tidak dipakai | `IncomingGoodsService.php:263, 282` | ✅ Done |
| 🟢 LOW | V-15 | Logic | Export tidak punya batas data — jika data jutaan baris, memory exhausted | `IncomingGoodsExport.php:35` | ✅ Done |
| 🟢 LOW | V-16 | UX/Bug | Step 2 "Kembali" menuju `step1` tapi tidak hapus `step1_data` — user bisa ubah data tapi step2 masih pakai data lama | `step2.blade.php:123` | ✅ Done |

---

## Detail Temuan Terbaru

### V-09 🟡 MEDIUM: `console.log` Debug Aktif di Production

**Lokasi:** `step3.blade.php` baris 180-212, 285-287

**Masalah:** Banyak `console.log` debug yang masih aktif, mengekspos struktur data ke browser console:

```javascript
// step3.blade.php baris 180-212
function calculateDifference(gradeId, beratAwal) {
    console.log('=== DEBUG CALCULATION ===');   // ⚠️ Info bocor
    console.log('Grade ID:', gradeId, 'Berat Awal:', beratAwal);
    // ...
    console.log('Selisih:', selisih);
    console.log('Decimal:', decimal);
    console.log('Percentage:', percentage);
    console.log('Final decimal:', decimalText);
    console.log('Final percentage:', percentageText);
    console.log('=== END DEBUG ===');           // ⚠️ Info bocor
}
```

**Dampak:** Mengekspos kalkulasi internal (grade ID, berat, selisih, persentase) ke siapapun yang membuka DevTools browser. Informasi bisnis yang sensitif.

**Fix:**
```javascript
// Hapus semua console.log atau bungkus dengan environment check
// Opsi 1: Hapus langsung
// Opsi 2: Buat debug flag
const DEBUG = false;
if (DEBUG) console.log('Grade ID:', gradeId);
```

---

### V-10 🟡 MEDIUM: Soft Delete `receiptItems` Lama Tanpa `deleted_by` Saat Update

**Lokasi:** `IncomingGoodsService.php` baris 155-158

**Masalah:** Saat update receipt, item lama dihapus (soft delete) tanpa mengisi `deleted_by`:

```php
// IncomingGoodsService.php baris 155-158
$receipt->receiptItems()->get()->each(function ($item) {
    $item->delete();  // ⚠️ Tidak ada: $item->deleted_by = auth()->id();
});
```

Bandingkan dengan `deleteReceipt()` yang sudah benar:
```php
// deleteReceipt() baris 212-216 — BENAR
$receipt->receiptItems()->get()->each(function ($item) {
    $item->deleted_by = auth()->id();  // ✅ Ada deleted_by
    $item->save();
    $item->delete();
});
```

**Dampak:** Audit trail `deleted_by` tidak terisi saat Edit/Update — tidak bisa melacak siapa yang menghapus item lama.

**Fix:**
```php
$receipt->receiptItems()->get()->each(function ($item) {
    $item->deleted_by = auth()->id();  // ✅ Tambahkan ini
    $item->save();
    $item->delete();
});
```

---

### V-11 🟡 MEDIUM: Validasi Tanggal `unloading_date` Tidak Konsisten di `update()`

**Lokasi:** `IncomingGoodsController.php` baris 183-184

**Masalah:** Saat Create, validasi `unloading_date` menggunakan `after_or_equal:receipt_date` (di Step1Request). Tapi saat Update, validasi ini tidak ada:

```php
// IncomingGoodsController.php update() baris 183-184
'receipt_date'   => 'required|date',
'unloading_date' => 'required|date',          // ⚠️ Tidak ada after_or_equal
```

Sedangkan di Step1Request untuk Create:
```php
// Step1Request.php — BENAR
'unloading_date' => 'required|date|after_or_equal:receipt_date',  // ✅ Ada after_or_equal
```

**Dampak:** User bisa menyimpan `unloading_date` yang lebih awal dari `receipt_date` saat melakukan Edit — secara bisnis tidak valid (tanggal bongkar tidak boleh sebelum tanggal kedatangan).

**Fix:**
```php
// IncomingGoodsController.php update() — tambahkan after_or_equal
'unloading_date' => 'required|date|after_or_equal:receipt_date',
```

---

### V-12 🟡 MEDIUM: Service Re-throw Exception Dengan Pesan DB Internal

**Lokasi:** `IncomingGoodsService.php` baris 125-127, 191-193, 225-227

**Masalah:** Exception di service di-wrap dan di-throw ulang dengan pesan yang masih mengandung detail database:

```php
// createPurchaseReceipt() baris 125-127
} catch (Exception $e) {
    throw new Exception('Gagal menyimpan data: ' . $e->getMessage());
    //                                              ^^^^^^^^^^^^^^^^
    //  Masih mengandung pesan DB, misal: "SQLSTATE[23000]..."
}
```

Meskipun controller sudah di-fix (V-05) untuk tidak ekspos ke user, exception ini bisa ter-log dengan pesan yang ambigu karena di-wrap dua kali. Di controller:

```php
// Controller baris 129
Log::error('IncomingGoods storeFinal error: ' . $e->getMessage());
// Log akan mencatat: "Gagal menyimpan data: SQLSTATE[23000]..."
// Ini masih berguna, tapi message-nya double-wrapped
```

**Dampak:** Log yang tidak bersih, dan jika exception tidak tertangkap di controller (misal future refactor), pesan ini bisa sampai ke user.

**Fix:**
```php
} catch (Exception $e) {
    // Log di service level jika perlu
    throw $e;  // Re-throw original exception tanpa wrap tambahan
    // Biarkan controller yang handle user-facing message
}
```

---

### V-13 🟢 LOW: N+1 Query — `sortingResults()->exists()` di Loop

**Lokasi:** `show.blade.php` baris 18-20

**Masalah:** Cek `sortingResults()->exists()` memanggil query DB per-item di dalam loop Blade:

```blade
@php
    $isReceiptGraded = $receipt->receiptItems->contains(
        fn($item) => $item->sortingResults()->exists()  // ⚠️ N query untuk N items
    );
@endphp
```

Jika receipt punya 10 items, ini menjalankan **10 query** hanya untuk cek status grading.

**Konteks:** Ini juga terjadi di `edit.blade.php` baris 94-96 dan controller `edit()` baris 158-162.

**Fix:** Load `sortingResults` sebagai eager load di controller, lalu cek via collection (0 query tambahan):

```php
// Controller show() — tambahkan sortingResults ke eager load
$receipt = PurchaseReceipt::with([
    'supplier',
    'receiptItems.gradeSupplier',
    'receiptItems.sortingResults',  // ✅ Eager load
])->findOrFail($id);
```

```blade
{{-- Blade — gunakan relasi yang sudah di-load --}}
@php
    $isReceiptGraded = $receipt->receiptItems->contains(
        fn($item) => $item->sortingResults->isNotEmpty()  // ✅ Tidak ada query baru
    );
@endphp
```

---

### V-14 🟢 LOW: Dead Code — `$monthName` Di-assign Tapi Tidak Dipakai

**Lokasi:** `IncomingGoodsService.php` baris 263, 282

**Masalah:** Variable `$monthName` di-assign dua kali tapi tidak pernah digunakan (nama file Excel menggunakan nomor bulan, bukan nama):

```php
// baris 248-264 — blok month+year
$monthName = $monthNames[$filters['month']] ?? $filters['month'];  // ⚠️ Tidak dipakai
$filename .= '_bulan_' . $filters['month'] . '_tahun_' . $filters['year'];  // Pakai nomor bulan

// baris 267-282 — blok month only
$monthName = $monthNames[$filters['month']] ?? $filters['month'];  // ⚠️ Tidak dipakai
$filename .= '_bulan_' . $filters['month'];  // Pakai nomor bulan
```

**Dampak:** Dead code, bisa membingungkan developer. Array `$monthNames` juga di-duplikat dua kali.

**Fix:** Hapus dead code dan deduplikasi array:

```php
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
    // ...
}
```

---

### V-15 🟢 LOW: Export Tanpa Batas Data — Risiko Memory Exhausted

**Lokasi:** `IncomingGoodsExport.php` baris 35

**Masalah:** Export mengambil semua data sekaligus tanpa pagination atau batas:

```php
$receipts = $query->latest('receipt_date')->get();  // ⚠️ Tidak ada limit
```

Jika ada 10.000+ receipt dengan masing-masing multiple items, proses export bisa menyebabkan:
- Memory exhausted
- Request timeout
- Server overload

**Dampak:** Moderat untuk sistem warehouse yang data akan terus bertambah.

**Fix — Opsi 1:** Paksa filter bulan/tahun wajib untuk export:
```php
// Validasi di controller sebelum export
if (empty($filters['month']) && empty($filters['year'])) {
    return back()->with('error', 'Pilih minimal filter bulan atau tahun untuk export.');
}
```

**Fix — Opsi 2:** Gunakan `chunk()` di Export untuk proses batch:
```php
// IncomingGoodsExport.php — ganti implementasi ke FromQuery
class IncomingGoodsExport implements FromQuery, ...
{
    public function query()
    {
        return PurchaseReceipt::with([...])->latest('receipt_date');
        // Maatwebsite akan handle chunking otomatis
    }
}
```

---

### V-16 🟢 LOW: Tombol "Kembali" Step 2 Tidak Reset Session Step1

**Lokasi:** `step2.blade.php` baris 123

**Masalah:** Tombol "Kembali" di Step 2 mengarah langsung ke `step1` tanpa membersihkan session:

```blade
{{-- step2.blade.php baris 123-124 --}}
<a href="{{ route('incoming-goods.step1') }}">
    Kembali
</a>
```

**Skenario masalah:**
1. User isi Step 1 (pilih Supplier A, Grade X) → session `step1_data` tersimpan
2. User lanjut ke Step 2, isi berat nota
3. User klik "Kembali" ke Step 1
4. User ganti pilihan (Supplier B, Grade Y)
5. User submit Step 1 baru → session `step1_data` di-overwrite ✅
6. User lanjut ke Step 2 — tampilan benar menampilkan Supplier B

Sebenarnya ini aman karena Step 1 re-submit akan overwrite session. **Tapi** jika user di Step 2 langsung klik Kembali tanpa submit Step 1 ulang, lalu langsung navigate ke Step 2 lagi — session lama masih ada dan form Step 2 akan menampilkan data dari step1 sebelumnya.

**Dampak:** Potential UX confusion, bukan security issue besar.

**Fix — Opsi:** Arahkan ke `cancel` route yang membersihkan session, lalu redirect ke step1:
```blade
<a href="{{ route('incoming-goods.cancel') }}">
    Batal
</a>
```
Atau tambahkan route back-to-step1 yang clear step2_data saja.

---

## Summary Keseluruhan

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| — | percentage_difference fillable | High | ✅ Fixed |
| — | Validasi edit/delete jika graded | High | ✅ Fixed |
| — | XSS via innerHTML | CRITICAL | ✅ Fixed |
| — | Edit form UX (bisa lihat tapi tidak bisa submit) | MEDIUM | ✅ Fixed |
| — | Add item bypass protection | MEDIUM | ✅ Fixed |
| — | Wizard idempotency | LOW | Future |
| V-01 | Threshold `is_flagged_red` tidak konsisten create vs update | 🟡 MEDIUM | ✅ Fixed |
| V-02 | `isPercentageAboveThreshold()` model 5%, view 2% | 🟡 MEDIUM | ✅ Fixed |
| V-03 | FormRequest `authorize()` hardcoded `true` | 🟡 MEDIUM | ✅ Fixed |
| V-04 | Session tidak re-validated di `storeFinal` | 🟡 MEDIUM | 🟡 Low Risk |
| V-05 | Error message DB terekspos ke user | 🟢 LOW | ✅ Fixed |
| V-06 | Tidak ada rate limiting endpoint write | 🟢 LOW | 🔴 Open |
| V-07 | Tombol Edit show.blade.php tidak conditional | 🟢 LOW | ✅ Fixed |
| V-08 | Tidak ada RBAC | 🟢 LOW | Future |
| V-09 | **`console.log` debug aktif di step3.blade.php** | **🟡 MEDIUM** | **✅ Fixed** |
| V-10 | **Soft delete receiptItems tanpa `deleted_by` saat update** | **🟡 MEDIUM** | **✅ Fixed** |
| V-11 | **Validasi `after_or_equal` tidak ada di update()** | **🟡 MEDIUM** | **✅ Fixed** |
| V-12 | **Service re-throw exception dengan pesan DB internal** | **🟡 MEDIUM** | **✅ Fixed** |
| V-13 | **N+1 query — sortingResults()->exists() di loop** | **🟢 LOW** | **✅ Fixed** |
| V-14 | **Dead code — `$monthName` tidak dipakai di exportToExcel** | **🟢 LOW** | **✅ Fixed** |
| V-15 | **Export tanpa batas data — risiko memory exhausted** | **🟢 LOW** | **✅ Fixed** |
| V-16 | **Tombol Kembali Step 2 tidak reset session** | **🟢 LOW** | **✅ Fixed** |

---

## Remaining Issues (Open)

| ID | Issue | Severity | Prioritas |
|----|-------|----------|-----------|
| V-04 | Session re-validation di storeFinal | 🟡 MEDIUM | P3 |
| V-06 | Rate limiting endpoint write | 🟢 LOW | P3 |
| V-08 | RBAC | 🟢 LOW | Future |
| V-09 | `console.log` debug di step3 | 🟡 MEDIUM | P1 |
| V-10 | `deleted_by` kosong saat soft delete di update | 🟡 MEDIUM | P1 |
| V-11 | Validasi `after_or_equal` tidak ada di update | 🟡 MEDIUM | P1 |
| V-12 | Service re-throw exception dengan pesan DB | 🟡 MEDIUM | P2 |
| V-13 | N+1 query sortingResults di loop | 🟢 LOW | P2 |
| V-14 | Dead code `$monthName` di exportToExcel | 🟢 LOW | P3 |
| V-15 | Export tanpa batas data | 🟢 LOW | P2 |
| V-16 | Tombol Kembali Step 2 tidak reset session | 🟢 LOW | P3 |

---

## Files Modified (History)

| File | Issue |
|------|-------|
| `app/Models/ReceiptItem.php` | V-01, V-02 |
| `app/Services/IncomingGoods/IncomingGoodsService.php` | V-01 |
| `app/Http/Requests/IncomingGoods/Step1Request.php` | V-03 |
| `app/Http/Requests/IncomingGoods/Step2Request.php` | V-03 |
| `app/Http/Requests/IncomingGoods/Step3Request.php` | V-03 |
| `app/Http/Controllers/Feature/IncomingGoodsController.php` | V-05 |
| `resources/views/admin/incoming_goods/show.blade.php` | V-07 |
| `resources/views/admin/incoming_goods/index.blade.php` | Threshold 2% |
| `resources/views/admin/incoming_goods/edit.blade.php` | Threshold 2% |
| `resources/views/admin/incoming_goods/step3.blade.php` | Threshold 2% |