# Analisis Manajemen Grading

## Overview

Manajemen Grading adalah fitur untuk memproses barang masuk (`receipt_items` status MENTAH) menjadi produk bersih dengan grade tertentu. Setelah grading, status `receipt_items` berubah menjadi `SELESAI_DISORTIR` dan stok masuk ke `inventory_transactions`.

---

## Tabel yang Digunakan

| Tabel | Peran | Operasi |
|-------|-------|---------|
| `receipt_items` | Sumber barang yang akan di-grading | Read, Update status |
| `sorting_results` | Hasil grading per grade | Create, Update (delete+recreate), Soft Delete |
| `grade_companies` | Grade company (nama produk hasil) | Read, Create (firstOrCreate) |
| `grades_supplier` | Grade supplier (untuk tampilan asal barang) | Read only (via join) |
| `purchase_receipts` | Header barang masuk | Read only (via join/relation) |
| `suppliers` | Nama supplier | Read only (via join) |
| `inventory_transactions` | Stok yang masuk setelah grading | Create, Update, Soft Delete |
| `locations` | Lokasi default gudang | Read only (`Gudang Utama`) |

### Tabel yang Ada di Model tapi TIDAK Digunakan di Grading

| Tabel | Catatan |
|-------|---------|
| `idm_management` | Field `idm_management_id` ada di fillable `sorting_results`, tapi **tidak pernah di-set** saat create/update grading — nilainya selalu `null` |
| `deleted_by` pada `sorting_results` | Ada dan dipakai saat delete — ✅ sudah benar |

---

## Controller, Service, dan Blade

### Controller
- `app/Http/Controllers/Feature/GradingGoodsController.php`
- `app/Http/Controllers/Master/GradeCompanyController.php` ← master data grade company

### Service
- `app/Services/GradingGoods/GradingGoodsService.php`
- `app/Services/GradeCompany/GradeCompanyService.php` ← CRUD grade company + image upload

### FormRequests
- `app/Http/Requests/GradingGoods/Step1Request.php`
- `app/Http/Requests/GradingGoods/Step2Request.php`
- `app/Http/Requests/GradeCompany/GradeCompanyRequest.php` ← untuk CRUD grade company

### Blade Views
| File | Fungsi |
|------|--------|
| `resources/views/admin/grading-goods/index.blade.php` | Daftar semua grading (dengan filter) |
| `resources/views/admin/grading-goods/step1.blade.php` | Pilih receipt item yang akan di-grading + tanggal |
| `resources/views/admin/grading-goods/step2.blade.php` | Input berat & grade hasil |
| `resources/views/admin/grading-goods/show.blade.php` | Detail hasil grading |
| `resources/views/admin/grading-goods/edit.blade.php` | Edit hasil grading |
| `resources/views/admin/grading-goods/step3.blade.php` | File ada tapi kosong / tidak dipakai ⚠️ |

### Routes
```
GET  /grading-goods/             → index
GET  /grading-goods/step1        → createStep1
POST /grading-goods/step1        → storeStep1
GET  /grading-goods/step2/{id}   → createStep2
POST /grading-goods/step2/{id}   → storeStep2
GET  /grading-goods/export       → export
GET  /grading-goods/show/{receiptItemId}  → show
GET  /grading-goods/edit/{receiptItemId}  → edit
PUT  /grading-goods/update/{receiptItemId} → update
DELETE /grading-goods/delete/{receiptItemId} → destroy
```

### FormRequests
- `app/Http/Requests/GradingGoods/Step1Request.php`
- `app/Http/Requests/GradingGoods/Step2Request.php`
- **Tidak ada UpdateGradingRequest** — update pakai inline `$request->validate()` di controller

### Export`
- `app/Exports/GradingGoodsExport.php`

---

## Alur Kerja

```
Step 1: Pilih receipt_item (status MENTAH, belum ada sorting_results)
    → createSortingResultStep1() — buat 1 SortingResult kosong (placeholder)

Step 2: Input grade hasil (bisa multiple grade)
    → updateSortingResultStep2Multiple() — delete placeholder, buat N SortingResult
    → Setiap SortingResult → createInventoryFromGrading() → 1 InventoryTransaction
    → Update receipt_item.status = SELESAI_DISORTIR

Edit (jika ada kesalahan):
    → updateMultipleSortingResults() — delete semua SR lama, buat ulang
    → Setiap SR lama → deleteInventoryFromGrading()
    → Setiap SR baru → createInventoryFromGrading()
    ⚠️ Status receipt_item TIDAK di-reset/update saat edit

Delete:
    → deleteGrading() — delete semua SR, kembalikan status ke MENTAH
```

---

## Pertanyaan: Apakah Grading Bisa Di-Edit?

**Ya, grading bisa di-edit.** Ada route `PUT /grading-goods/update/{receiptItemId}` dan halaman `edit.blade.php`.

### Apakah Ini Aman?

Secara design, **ini problematik** karena:

1. **Edit grading = hapus inventory lama + buat inventory baru** — artinya ada periode di mana stok "hilang sejenak" dalam satu transaksi DB (aman karena dibungkus transaction).

2. **Tidak ada cek apakah grading sudah "dipakai"** — misalnya jika `sorting_result` sudah digunakan sebagai `penjualan_langsung`, `internal`, atau `external`, data tersebut masih bisa di-edit. Ini bisa menyebabkan inkonsistensi stok.

3. **Bandingkan dengan Barang Masuk** — di modul barang masuk sudah ada proteksi: jika `sortingResults` sudah ada, barang masuk tidak bisa di-edit. Tapi di grading, tidak ada pengecekan serupa — jika `sorting_result` sudah punya transaksi barang keluar, masih bisa di-edit.

**Rekomendasi:** Grading sebaiknya **tidak bisa di-edit jika sudah ada transaksi barang keluar** (`inventory_transactions` tipe selain GRADING_IN yang mereferensikan sorting_result tersebut, atau ada `StockTransfer`/`Penjualan` yang terhubung).

---

## Security Review

### ✅ Yang Sudah Baik

| Aspek | Status | Detail |
|-------|--------|--------|
| Mass Assignment | ✅ | `fillable` didefinisikan di `SortingResult` |
| SQL Injection | ✅ | Eloquent ORM + raw query yang aman (`DB::raw` hanya untuk agregasi) |
| CSRF | ✅ | Semua form pakai `@csrf` |
| Auth Middleware | ✅ | `auth` middleware di semua route |
| DB Transaction | ✅ | Create & Update dibungkus `DB::transaction()` |
| Soft Deletes | ✅ | `SortingResult` & `InventoryTransaction` pakai SoftDeletes |
| Audit Trail | ✅ | `created_by`, `deleted_by` terisi di `SortingResult` dan `InventoryTransaction` |
| XSS — Blade | ✅ | `{{ }}` auto-escape di semua blade |
| Logging | ✅ | Error dan success event di-log |

---

### ⚠️ Bug dan Kerentanan yang Ditemukan

| Severity | ID | Aspek | Masalah | Lokasi | Status |
|----------|----|-------|---------|--------|--------|
| 🔴 HIGH | G-01 | Logic/Business | Grading bisa di-edit meski `sorting_result` sudah dipakai di transaksi barang keluar (penjualan/transfer) — stok bisa tidak konsisten | `GradingGoodsController::edit()` + `update()` | ✅ Done |
| 🟡 MEDIUM | G-02 | Bug | Error message di `storeStep2` dan `destroy` langsung ekspos `$e->getMessage()` ke user | `GradingGoodsController.php:103, 209` | ✅ Done |
| 🟡 MEDIUM | G-03 | Bug | `updateMultipleSortingResults()` tidak update status `receipt_item` — status bisa tidak sinkron setelah edit | `GradingGoodsService.php:204-274` | ✅ Done |
| 🟡 MEDIUM | G-04 | Logic | `createInventoryFromGrading()` **silent fail** jika `Gudang Utama` tidak ditemukan — grading berhasil tapi stok tidak masuk | `GradingGoodsService.php:423-427` | ✅ Done |
| 🟡 MEDIUM | G-05 | Bug | `updateFullGrading()` ada di service tapi **tidak dipanggil dari manapun** — dead method | `GradingGoodsService.php:362-391` | ✅ Done |
| 🟡 MEDIUM | G-06 | Logic | `getAllGrading()` dan `getAllGradingForExport()` adalah **duplikasi query yang identik** — DRY violation, satu perubahan bisa lupa diaplikasikan di yang lain | `GradingGoodsService.php:22-83 vs 85-147` | ✅ Done |
| 🟡 MEDIUM | G-07 | Security | `update()` di controller tidak pakai FormRequest — validasi inline bisa terlewat jika ada field baru | `GradingGoodsController.php:160-168` | ✅ Done |
| 🟢 LOW | G-08 | Bug | `step3.blade.php` ada di direktori tapi file kosong — route tidak ada, membingungkan | `resources/views/admin/grading-goods/step3.blade.php` | ✅ Done |
| 🟢 LOW | G-09 | Data | `idm_management_id` di fillable `SortingResult` tapi tidak pernah di-set saat create grading — selalu null | `SortingResult.php:14`, `GradingGoodsService.php:247` | ✅ Done |
| 🟢 LOW | G-10 | N+1 | `index` controller memanggil `Supplier::orderBy()->get()` langsung, bukan via service | `GradingGoodsController.php:34` | ✅ Done |
| 🟢 LOW | G-11 | Error | `show()` mengembalikan `abort(404)` untuk grading tidak ditemukan — tidak konsisten dengan pattern lain yang redirect dengan error | `GradingGoodsController.php:44` | ✅ Done |
| 🟢 LOW | G-12 | Export | Export grading tidak punya filter wajib — bisa load semua data jika tidak ada filter | `GradingGoodsController.php:107-138` | ✅ Done |
| 🟡 MEDIUM | G-13 | UX/Logic | Tombol Edit di `show.blade.php` sudah conditional — `$canEdit` dari controller | `show.blade.php:23`, `GradingGoodsController.php:57` | ✅ Done |

#### Temuan Baru — Scan Mendalam View + GradeCompany

| Severity | ID | Aspek | Masalah | Lokasi | Status |
|----------|----|-------|---------|--------|--------|
| 🟡 MEDIUM | G-14 | Security | `GradeCompanyRequest::authorize()` hardcoded `return true` — tidak cek autentikasi | `GradeCompanyRequest.php:14` | ✅ Done |
| 🟡 MEDIUM | G-15 | Logic | `GradeCompanyService::delete()` hapus grade company tanpa cek apakah masih dipakai di `sorting_results` — bisa orphan data | `GradeCompanyService.php:65-75` | ✅ Done |
| 🟡 MEDIUM | G-16 | Security | `GradeCompanyController::export()` ekspos `$e->getMessage()` ke user | `GradeCompanyController.php:36` | ✅ Done |
| 🟡 MEDIUM | G-17 | Logic | `GradeCompanyService::bulkAssign()` tidak validasi apakah `$parentGradeId` valid/exist — bisa set ke ID yang tidak ada | `GradeCompanyService.php:77-80` | ✅ Done |
| 🟡 MEDIUM | G-18 | Logic | `createSortingResultStep1()` membuat record `SortingResult` **setengah jadi** (null weight, null grade) — jika user tidak lanjut ke Step 2, record orphan tetap ada di DB | `GradingGoodsService.php:183-197` | ✅ Done |
| 🟡 MEDIUM | G-19 | Logic | Threshold flag di `index.blade.php` pakai **5%** (merah jika >5%), sedangkan modul Barang Masuk konsisten **2%** — inkonsistensi aturan bisnis | `index.blade.php:189` | ✅ Done |
| 🟢 LOW | G-20 | UX/Security | Delete di `index.blade.php` pakai URL hardcoded string `grading-goods/delete/${id}` bukan route helper — rawan typo dan tidak ikut perubahan prefix route | `index.blade.php:263` | ✅ Done |
| 🟢 LOW | G-21 | UX | Step 2 tombol "Kembali" mengarah ke `step1` tanpa hapus orphan `SortingResult` yang dibuat di Step 1 — data kotor terakumulasi | `step2.blade.php:14-17` | ✅ Done |
| 🟢 LOW | G-22 | Logic | `grading_date` di Step1 tidak ada validasi tidak boleh di masa depan — bisa input tanggal grading besok/bulan depan | `step1.blade.php:51-54`, `Step1Request` | ✅ Done |
| 🟢 LOW | G-23 | Copy-paste | `GradeCompanyRequest` messages() pakai label "Nama lokasi" (bukan "Nama grade company") — copy-paste dari LocationRequest | `GradeCompanyRequest.php:37-39` | ✅ Done |

---

## Detail Temuan

### G-01 🔴 HIGH: Grading Bisa Di-Edit Meski Sudah Digunakan

**Lokasi:** `GradingGoodsController::edit()` & `update()`

**Masalah:** Tidak ada pengecekan apakah `sorting_result` sudah digunakan di transaksi barang keluar.

Skenario berbahaya:
1. Grading selesai: `sorting_result` A punya stok 1000 gr
2. User membuat penjualan yang menggunakan stok dari grading ini → `inventory_transactions` berkurang
3. **User edit grading** → semua `sorting_result` lama dihapus + inventory GRADING_IN dihapus
4. Stok menjadi negatif atau tidak konsisten

**Fix yang disarankan:** Cek sebelum allow edit:

```php
// GradingGoodsController::edit()
$sortingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

foreach ($sortingResults as $sr) {
    // Cek apakah ada transaksi non-GRADING_IN yang mereferensikan sorting_result ini
    $hasOutgoingTransaction = \App\Models\InventoryTransaction::where('sorting_result_id', $sr->id)
        ->where('transaction_type', '!=', 'GRADING_IN')
        ->exists();

    if ($hasOutgoingTransaction) {
        return redirect()->route('grading-goods.show', $receiptItemId)
            ->with('error', 'Tidak dapat edit. Grading sudah digunakan dalam transaksi barang keluar.');
    }
}
```

---

### G-02 🟡 MEDIUM: Error Message Terekspos ke User

**Lokasi:** `GradingGoodsController.php:103` dan `209`

**Masalah:**

```php
// storeStep2() baris 100-104
} catch (\Exception $e) {
    return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    //                                                              ^^^^^^^^^^^^^^^^
}

// destroy() baris 208-210
} catch (\Exception $e) {
    return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
}
```

**Fix:**
```php
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('GradingGoods error: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
    ]);
    return back()->withInput()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
}
```

---

### G-03 🟡 MEDIUM: Status `receipt_item` Tidak Di-Update Saat Edit

**Lokasi:** `GradingGoodsService::updateMultipleSortingResults()` baris 204-274

**Masalah:** Saat edit grading, semua `sorting_result` lama dihapus lalu dibuat ulang, tapi status `receipt_item` **tidak di-update**. Bandingkan dengan `updateSortingResultStep2Multiple()` yang update status:

```php
// updateSortingResultStep2Multiple() baris 340 — ADA update status
$receiptItem->update(['status' => ReceiptItem::STATUS_SELESAI_DISORTIR]);

// updateMultipleSortingResults() — TIDAK ADA update status
// Item mungkin sudah SELESAI_DISORTIR, tapi jika ada bug ini bisa jadi tidak konsisten
```

**Konteks:** Jika grading dihapus (`deleteGrading()`), status dikembalikan ke MENTAH. Tapi jika di-edit, status tidak diperbarui ulang ke SELESAI_DISORTIR secara eksplisit. Ini bisa jadi masalah jika di masa depan ada logika yang bergantung pada status.

**Fix:**
```php
// Di akhir updateMultipleSortingResults() setelah create semua sorting result
$receiptItem->update(['status' => ReceiptItem::STATUS_SELESAI_DISORTIR]);
```

---

### G-04 🟡 MEDIUM: Silent Fail Jika `Gudang Utama` Tidak Ditemukan

**Lokasi:** `GradingGoodsService::createInventoryFromGrading()` baris 423-427

**Masalah:** Jika lokasi "Gudang Utama" tidak ada di tabel `locations`, fungsi return tanpa error dan **inventory tidak dibuat** — stok tidak masuk tapi grading dianggap berhasil:

```php
private function createInventoryFromGrading(SortingResult $sortingResult)
{
    $defaultLocation = Location::where('name', 'Gudang Utama')->first();

    if (!$defaultLocation || !$sortingResult->grade_company_id || !$sortingResult->weight_grams) {
        return;  // ⚠️ Silent fail — tidak ada error, tidak ada log!
    }
    // ...
}
```

**Dampak:** Grading berhasil disimpan, tapi stok tidak masuk ke `inventory_transactions`. Data grading dan stok tidak sinkron.

**Fix:**
```php
private function createInventoryFromGrading(SortingResult $sortingResult)
{
    $defaultLocation = Location::where('name', 'Gudang Utama')->first();

    if (!$defaultLocation) {
        Log::error('createInventoryFromGrading: Lokasi "Gudang Utama" tidak ditemukan!', [
            'sorting_result_id' => $sortingResult->id,
        ]);
        throw new \Exception('Lokasi "Gudang Utama" tidak ditemukan. Grading tidak dapat disimpan.');
    }

    if (!$sortingResult->grade_company_id || !$sortingResult->weight_grams) {
        Log::warning('createInventoryFromGrading: grade_company_id atau weight_grams kosong.', [
            'sorting_result_id' => $sortingResult->id,
        ]);
        return;
    }
    // ...
}
```

---

### G-05 🟡 MEDIUM: `updateFullGrading()` — Dead Method

**Lokasi:** `GradingGoodsService.php:362-391`

**Masalah:** Method `updateFullGrading()` ada di service tapi tidak dipanggil dari controller manapun. Dead code yang bisa membingungkan.

```php
public function updateFullGrading($sortingResultId, array $data)
{
    // Method ini tidak dipanggil dari GradingGoodsController
    // Tidak ada route yang mengarah ke sini
}
```

**Rekomendasi:** Hapus atau dokumentasikan tujuannya.

---

### G-06 🟡 MEDIUM: Duplikasi Query `getAllGrading` vs `getAllGradingForExport`

**Lokasi:** `GradingGoodsService.php:22-83` vs `85-147`

**Masalah:** Kedua method memiliki query yang **identik** (sama-sama SELECT, JOIN, GROUP BY, filter, whereNull). Perbedaannya hanya `paginate()` vs `get()`.

**Dampak:** Jika ada bug di query atau perlu tambah field, harus diupdate di 2 tempat.

**Fix:** Ekstrak query builder ke method private:

```php
private function buildGradingQuery(array $filters)
{
    $query = ReceiptItem::select([...])->join(...)->where(...);
    // Apply filters
    return $query;
}

public function getAllGrading($filters = [], $perPage = 15)
{
    return $this->buildGradingQuery($filters)->paginate($perPage)->appends(request()->query());
}

public function getAllGradingForExport($filters = [])
{
    return $this->buildGradingQuery($filters)->get();
}
```

---

### G-07 🟡 MEDIUM: `update()` Tidak Pakai FormRequest

**Lokasi:** `GradingGoodsController.php:160-168`

**Masalah:** Method `update()` menggunakan `$request->validate()` inline, berbeda dengan Create yang pakai `Step1Request`/`Step2Request`:

```php
public function update(Request $request, $receiptItemId)
{
    $request->validate([
        'grades.*.grading_date' => 'required|date',
        'grades.*.grade_company_name' => 'required|string|max:255',
        // ...
    ]);
```

**Fix:** Buat `UpdateGradingRequest` FormRequest:

```php
// app/Http/Requests/GradingGoods/UpdateGradingRequest.php
public function authorize(): bool { return auth()->check(); }
public function rules(): array {
    return [
        'grades.*.grading_date' => 'required|date',
        'grades.*.grade_company_name' => 'required|string|max:255',
        'grades.*.quantity' => 'required|numeric|min:0',
        'grades.*.weight_grams' => 'required|numeric|min:0',
        'grades.*.notes' => 'nullable|string',
        'grades.*.outgoing_type' => 'nullable|in:penjualan_langsung,internal,external',
        'grades.*.category_grade' => 'nullable|in:IDM A,IDM B',
        'global_notes' => 'nullable|string',
    ];
}
```

---

### G-08 🟢 LOW: `step3.blade.php` — File Kosong

**Lokasi:** `resources/views/admin/grading-goods/step3.blade.php`

File ini ada tapi kosong. Tidak ada route yang mengarah ke sana. Membingungkan developer.

**Fix:** Hapus file jika tidak digunakan, atau dokumentasikan rencana penggunaannya.

---

### G-09 🟢 LOW: `idm_management_id` Selalu `null`

**Lokasi:** `SortingResult.php:14` (fillable), `GradingGoodsService.php:247`

`idm_management_id` ada di fillable dan ada relasi `idmManagement()`, tapi **tidak pernah di-set** saat create grading. Selalu `null`.

**Dampak:** Relasi ke `IdmManagement` tidak berfungsi dari arah `SortingResult`. Jika ada fitur yang bergantung ini, datanya tidak ada.

---

### G-10 🟢 LOW: Direct Model Call di Controller (Bukan via Service)

**Lokasi:** `GradingGoodsController.php:34`

```php
$suppliers = \App\Models\Supplier::orderBy('name')->get();  // ⚠️ Direct call, bukan via service
```

Tidak konsisten dengan pola yang pakai service. Kecil, tapi melanggar separation of concerns.

---

### G-11 🟢 LOW: `abort(404)` Tidak Konsisten

**Lokasi:** `GradingGoodsController.php:44`

```php
if ($allGradingResults->isEmpty()) {
    return abort(404, 'Grading not found');  // ⚠️ abort() bukan redirect
}
```

Metode lain seperti `createStep2()` melakukan redirect dengan pesan error. Ini tidak konsisten — user akan mendapat halaman 404 default Laravel bukan flash message yang informatif.

---

### G-12 🟢 LOW: Export Tanpa Filter Wajib

**Lokasi:** `GradingGoodsController.php:107-138`

Sama seperti barang masuk (V-15), export bisa dipanggil tanpa filter apapun dan akan load semua data ke memory.

---

---

### G-14 🟡 MEDIUM: `GradeCompanyRequest::authorize()` Hardcoded `return true`

**Lokasi:** `app/Http/Requests/GradeCompany/GradeCompanyRequest.php:14`

```php
public function authorize(): bool
{
    return true;  // ⚠️ Tidak cek autentikasi!
}
```

**Masalah:** Sama seperti kerentanan yang ditemukan di modul Barang Masuk. Jika middleware auth dilewati karena bug konfigurasi, request tetap diotorisasi karena `authorize()` selalu return `true`.

**Fix:**
```php
public function authorize(): bool
{
    return auth()->check();
}
```

---

### G-15 🟡 MEDIUM: `GradeCompanyService::delete()` Tidak Cek Penggunaan

**Lokasi:** `GradeCompanyService.php:65-75`

**Masalah:** Menghapus grade company tanpa memeriksa apakah masih digunakan di `sorting_results`:

```php
public function delete(int $id)
{
    $gradeCompany = $this->getById($id);
    // ⚠️ Tidak ada cek sorting_results yang menggunakan grade company ini!
    if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
        Storage::disk('public')->delete($gradeCompany->image_url);
    }
    $gradeCompany->delete();
    return true;
}
```

**Dampak:** Jika grade company dihapus, `sorting_results.grade_company_id` yang mereferensikannya akan menjadi orphan (foreign key null atau broken). Data grading menjadi tidak lengkap.

**Fix:**
```php
public function delete(int $id)
{
    $gradeCompany = $this->getById($id);

    // ✅ Cek apakah masih digunakan
    if ($gradeCompany->sortingResults()->exists()) {
        throw new \Exception('Grade company tidak dapat dihapus karena masih digunakan di ' . $gradeCompany->sortingResults()->count() . ' data grading.');
    }

    if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
        Storage::disk('public')->delete($gradeCompany->image_url);
    }
    $gradeCompany->delete();
    return true;
}
```

---

### G-16 🟡 MEDIUM: `GradeCompanyController::export()` Ekspos Error Message

**Lokasi:** `GradeCompanyController.php:36`

```php
public function export()
{
    try {
        return $this->GradeCompanyService->exportToExcel();
    } catch (\Exception $e) {
        return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage()); // ⚠️
    }
}
```

**Fix:**
```php
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('GradeCompany export error: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
    ]);
    return back()->with('error', 'Gagal mengekspor data. Silakan coba lagi.');
}
```

---

### G-17 🟡 MEDIUM: `bulkAssign()` Tidak Validasi `$parentGradeId`

**Lokasi:** `GradeCompanyService.php:77-80`

```php
public function bulkAssign(int $parentGradeId, array $gradeCompanyIds)
{
    return GradeCompany::whereIn('id', $gradeCompanyIds)->update(['parent_grade_company_id' => $parentGradeId]);
    // ⚠️ Tidak cek apakah $parentGradeId exist di DB
    // ⚠️ Tidak cek apakah $gradeCompanyIds valid
}
```

**Dampak:** Bisa assign `parent_grade_company_id` ke ID yang tidak ada, merusak integritas data hierarki grade.

**Fix:**
```php
public function bulkAssign(int $parentGradeId, array $gradeCompanyIds)
{
    // Validasi parent exists
    GradeCompany::findOrFail($parentGradeId);

    // Validasi IDs tidak kosong
    if (empty($gradeCompanyIds)) {
        throw new \InvalidArgumentException('Tidak ada grade company yang dipilih.');
    }

    return GradeCompany::whereIn('id', $gradeCompanyIds)->update(['parent_grade_company_id' => $parentGradeId]);
}
```

---

### G-18 🟡 MEDIUM: Orphan `SortingResult` dari Step 1 yang Tidak Dilanjutkan

**Lokasi:** `GradingGoodsService::createSortingResultStep1()` baris 183-197

**Masalah:** Step 1 membuat record `SortingResult` dengan `weight_grams = null`, `grade_company_id = null`, dll. Jika user membuka Step 1, submit, lalu menutup browser (tidak lanjut ke Step 2), record ini tetap ada di DB:

```php
$data = [
    'grading_date' => $gradingDate,
    'receipt_item_id' => $receiptItemId,
    'grade_company_id' => null,   // ⚠️ Null
    'weight_grams' => null,       // ⚠️ Null
    'quantity' => null,           // ⚠️ Null
    'percentage_difference' => null,
    'notes' => null,
    'created_by' => Auth::id(),
];
return SortingResult::create($data);
```

**Dampak:**
1. `receipt_item` dengan status MENTAH yang sudah ada `sorting_result` tidak akan muncul di Step 1 (karena ada filter `whereDoesntHave('sortingResults')`).
2. Data `sorting_results` sampah menumpuk di DB.

**Fix — Opsi A:** Cleanup periodic (artisan command / scheduled job) untuk hapus orphan sorting results yang `weight_grams IS NULL` lebih dari X jam.

**Fix — Opsi B (lebih proper):** Tidak buat record di Step 1, cukup pass `grading_date` dan `receipt_item_id` sebagai session/flash ke Step 2, lalu buat record sekaligus saat Step 2 disubmit.

---

### G-19 🟡 MEDIUM: Threshold Persentase Inkonsisten (5% vs 2%)

**Lokasi:** `index.blade.php:189`

**Masalah:** Kolom persentase di halaman index grading menggunakan threshold **5%** sebagai batas "merah/peringatan":

```php
// index.blade.php — GRADING
if ($percentage > 5) {              // ⚠️ 5%
    $percentageClass = 'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
} elseif ($percentage > 1) {
    $percentageClass = 'text-orange-600 font-semibold';
}
```

Sedangkan modul **Barang Masuk** menggunakan threshold **2%**. Ini tidak konsisten dengan aturan bisnis yang sudah disepakati.

**Fix:** Standarkan ke 2% (sama seperti Barang Masuk):
```php
if ($percentage > 2) {
    $percentageClass = 'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
```

---

### G-20 🟢 LOW: URL Delete Hardcoded, Bukan Route Helper

**Lokasi:** `index.blade.php:263`

```javascript
function confirmDelete(id) {
    const form = document.getElementById('deleteForm');
    form.action = `grading-goods/delete/${id}`;  // ⚠️ Hardcoded URL!
}
```

**Masalah:** Jika prefix route berubah (misalnya dari `/grading-goods` ke `/admin/grading-goods`), URL ini tidak ikut berubah dan delete akan 404.

**Fix — pakai route helper di Blade:**
```html
<!-- Simpan route template sebagai data attribute -->
<div id="deleteModal" data-url="{{ route('grading-goods.destroy', ':id') }}">
```

```javascript
function confirmDelete(id) {
    const modal = document.getElementById('deleteModal');
    const urlTemplate = modal.dataset.url;
    form.action = urlTemplate.replace(':id', id);
}
```

---

### G-21 🟢 LOW: Tombol "Kembali" Step 2 Tidak Hapus Orphan SortingResult

**Lokasi:** `step2.blade.php:14-17`

```html
<a href="{{ route('grading-goods.step1') }}" class="...">Kembali</a>
```

**Masalah:** Tombol kembali di Step 2 membawa user ke Step 1, tapi `SortingResult` placeholder yang dibuat di Step 1 **tidak dihapus**. Ini berhubungan langsung dengan G-18 (orphan record).

**Fix:** Tambahkan route untuk "batalkan" yang menghapus orphan sorting result sebelum redirect ke step1:

```php
// Route baru
Route::delete('/cancel/{sortingResultId}', [GradingGoodsController::class, 'cancelStep2'])->name('cancel.step2');

// Controller
public function cancelStep2($sortingResultId)
{
    $sortingResult = SortingResult::find($sortingResultId);
    if ($sortingResult && is_null($sortingResult->weight_grams)) {
        $sortingResult->deleted_by = auth()->id();
        $sortingResult->save();
        $sortingResult->delete();
    }
    return redirect()->route('grading-goods.step1')->with('info', 'Proses grading dibatalkan.');
}
```

---

### G-22 🟢 LOW: `grading_date` Boleh di Masa Depan

**Lokasi:** `step1.blade.php:51-54`, `Step1Request`

**Masalah:** Tidak ada validasi bahwa tanggal grading tidak boleh di masa depan. User bisa submit grading dengan tanggal besok atau bulan depan.

**Fix di FormRequest:**
```php
'grading_date' => 'required|date|before_or_equal:today',
```

---

### G-23 🟢 LOW: Copy-Paste Bug di `GradeCompanyRequest` Messages

**Lokasi:** `GradeCompanyRequest.php:37-39`

```php
public function messages(): array
{
    return [
        'name.required' => 'Nama lokasi wajib diisi.',     // ⚠️ Harusnya "Nama grade company"
        'name.string'   => 'Nama lokasi harus berupa teks.', // ⚠️ Copy-paste dari LocationRequest
        'name.max'      => 'Nama lokasi maksimal 255 karakter.',
        // ...
    ];
}
```

User yang mendapat error validasi akan membaca "Nama **lokasi** wajib diisi" padahal sedang mengisi form Grade Company.

**Fix:**
```php
'name.required' => 'Nama grade company wajib diisi.',
'name.string'   => 'Nama grade company harus berupa teks.',
'name.max'      => 'Nama grade company maksimal 255 karakter.',
```

---

## Summary

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| G-01 | Grading bisa di-edit meski sudah dipakai di transaksi keluar | 🔴 HIGH | ✅ Done |
| G-02 | Error message ekspos `$e->getMessage()` ke user (controller grading) | 🟡 MEDIUM | ✅ Done |
| G-03 | Status `receipt_item` tidak di-update saat edit grading | 🟡 MEDIUM | ✅ Done |
| G-04 | Silent fail jika `Gudang Utama` tidak ditemukan | 🟡 MEDIUM | ✅ Done |
| G-05 | `updateFullGrading()` — dead method | 🟡 MEDIUM | ✅ Done |
| G-06 | Duplikasi query `getAllGrading` vs `getAllGradingForExport` | 🟡 MEDIUM | ✅ Done |
| G-07 | `update()` tidak pakai FormRequest | 🟡 MEDIUM | ✅ Done |
| G-08 | `step3.blade.php` kosong | 🟢 LOW | ✅ Done |
| G-09 | `idm_management_id` selalu `null` | 🟢 LOW | ✅ Done |
| G-10 | Direct model call di controller (Supplier) | 🟢 LOW | ✅ Done |
| G-11 | `abort(404)` tidak konsisten | 🟢 LOW | ✅ Done |
| G-12 | Export tanpa filter wajib | 🟢 LOW | ✅ Done |
| G-13 | Tombol Edit conditional sudah ada `$canEdit` | 🟡 MEDIUM | ✅ Done |
| G-14 | `GradeCompanyRequest::authorize()` hardcoded `return true` | 🟡 MEDIUM | ✅ Done |
| G-15 | `GradeCompanyService::delete()` tidak cek penggunaan di sorting_results | 🟡 MEDIUM | ✅ Done |
| G-16 | `GradeCompanyController::export()` ekspos error message | 🟡 MEDIUM | ✅ Done |
| G-17 | `bulkAssign()` tidak validasi `$parentGradeId` exist | 🟡 MEDIUM | ✅ Done |
| G-18 | Orphan `SortingResult` dari Step 1 yang tidak dilanjutkan | 🟡 MEDIUM | ✅ Done |
| G-19 | Threshold persentase inkonsisten: 5% (grading) vs 2% (barang masuk) | 🟡 MEDIUM | ✅ Done |
| G-20 | URL delete hardcoded string, bukan route helper | 🟢 LOW | ✅ Done |
| G-21 | Tombol "Kembali" Step 2 tidak hapus orphan SortingResult | 🟢 LOW | ✅ Done |
| G-22 | `grading_date` tidak ada validasi `before_or_equal:today` | 🟢 LOW | ✅ Done |
| G-23 | Copy-paste bug: `GradeCompanyRequest` messages pakai "Nama lokasi" | 🟢 LOW | ✅ Done |

