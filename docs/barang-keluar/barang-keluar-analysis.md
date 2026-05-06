# Analisis Barang Keluar

## Overview

Barang Keluar adalah fitur untuk mencatat dan mengelola pengeluaran/transfer stok barang dari gudang. Fitur ini mencakup beberapa operasi: penjualan langsung, transfer internal, transfer eksternal, penerimaan dari internal, dan penerimaan dari eksternal.

---

## Tabel yang Digunakan

| Tabel | Peran | Operasi |
|-------|-------|---------|
| `inventory_transactions` | Akumulasi stok per grade + lokasi + supplier | Create, Read |
| `stock_transfers` | Record transfer antar lokasi | Create, Read, Update, Delete |
| `sorting_results` | Data batch/sorting per grade | Read |
| `grades_company` | Grade company (produk akhir) | Read |
| `locations` | Lokasi gudang | Read |
| `suppliers` | Supplier (informasi tambahan) | Read |
| `purchase_receipts` | Receipt item untuk tracing supplier | Read (via relations) |

### Tabel yang Ada di Model tapi TIDAK Digunakan Langsung

| Tabel | Catatan |
|-------|---------|
| `grades` | Tidak digunakan — fokus ke grades_company |
| `parent_grade_companies` | Tidak digunakan langsung |

---

## Controller, Service, dan Blade

### Controllers
| File | Fungsi |
|------|--------|
| `BarangKeluarController.php` | Halaman utama menu (4 card) |
| `PenjualanController.php` | Penjualan langsung (SALE_OUT) |
| `TransferInternalController.php` | Transfer internal antar lokasi |
| `TransferExternalController.php` | Transfer eksternal (Jasa Cuci) |
| `ReceiveInternalController.php` | Penerimaan dari IDM/DMK |
| `ReceiveExternalController.php` | Penerimaan dari eksternal (Jasa Cuci) |
| `TransferIdmController.php` | Transfer IDM (handled by separate service) |

### Services
| File | Fungsi |
|------|--------|
| `BarangKeluarService.php` | Core business logic — stock calculation, batch tracking, sell, transfer, receive |
| `TransferIdmService.php` | IDM-specific transfer operations |

### Blade Views
| File | Fungsi |
|------|--------|
| `barang-keluar/index.blade.php` | Menu utama 4 card |
| `barang-keluar/sell.blade.php` | Form penjualan + history |
| `barang-keluar/sell-edit.blade.php` | Edit penjualan |
| `barang-keluar/transfer-step1.blade.php` | Form transfer internal + history |
| `barang-keluar/transfer-step2.blade.php` | Konfirmasi transfer internal |
| `barang-keluar/transfer-edit.blade.php` | Edit transfer internal |
| `barang-keluar/external-transfer-step1.blade.php` | Form transfer eksternal + history |
| `barang-keluar/external-transfer-step2.blade.php` | Konfirmasi transfer eksternal |
| `barang-keluar/external-transfer-edit.blade.php` | Edit transfer eksternal |
| `barang-keluar/receive-internal-step1.blade.php` | Form receive internal + history |
| `barang-keluar/receive-internal-step2.blade.php` | Konfirmasi receive internal |
| `barang-keluar/receive-external-step1.blade.php` | Form receive eksternal + history |
| `barang-keluar/receive-external-edit.blade.php` | Edit receive eksternal |
| `transfer-idm/*.blade.php` | IDM transfer views |

### Routes
```
barang-keluar/
├── GET  /                            → index (menu)
├── GET  /sell                        → sellForm
├── POST /sell                        → sell
├── GET  /sell/{id}/edit              → edit (Penjualan)
├── PUT  /sell/{id}                    → update (Penjualan)
├── DELETE /sell/{id}                  → destroy (Penjualan)
├── GET  /transfer/step1               → transferStep1
├── POST /transfer/step1               → storeTransferStep1
├── GET  /transfer/step2               → transferStep2
├── POST /transfer                    → transfer
├── GET  /transfer/{id}/edit          → edit (Transfer)
├── PUT  /transfer/{id}                → update (Transfer)
├── DELETE /transfer/{id}             → destroy (Transfer)
├── GET  /transfer-external/step1     → externalTransferStep1
├── POST /transfer-external/step1     → storeExternalTransferStep1
├── GET  /transfer-external/step2     → externalTransferStep2
├── POST /transfer-external           → externalTransfer
├── GET  /transfer-external/{id}/edit → editExternalTransfer
├── PUT  /transfer-external/{id}      → updateExternalTransfer
├── DELETE /transfer-external/{id}    → destroyExternalTransfer
├── GET  /receive-internal/step1      → receiveInternalStep1
├── POST /receive-internal/step1       → storeReceiveInternalStep1
├── GET  /receive-internal/step2      → receiveInternalStep2
├── POST /receive-internal            → receiveInternal
├── GET  /receive-external/step1      → receiveExternalStep1
├── POST /receive-external/step1      → storeReceiveExternalStep1
├── GET  /receive-external/step2      → receiveExternalStep2
├── POST /receive-external            → receiveExternal
├── GET  /receive-external/{id}/edit → editReceiveExternal
├── PUT  /receive-external/{id}       → updateReceiveExternal
├── DELETE /receive-external/{id}     → destroyReceiveExternal
└── GET  /transfer-idm               → TransferIdmController@index
```

---

## Transaction Types di Inventory Transactions

| Type | Effect | Source |
|------|--------|--------|
| SALE_OUT | -quantity | PenjualanController::sell() |
| TRANSFER_OUT | -quantity | BarangKeluarService::createTransferTransactions() |
| TRANSFER_IN | +quantity | BarangKeluarService::createTransferTransactions() |
| EXTERNAL_TRANSFER_OUT | -quantity | BarangKeluarService::externalTransfer() |
| EXTERNAL_TRANSFER_IN | +quantity | BarangKeluarService::externalTransfer() |
| RECEIVE_INTERNAL_IN | +quantity | BarangKeluarService::receiveInternal() |
| RECEIVE_EXTERNAL_IN | +quantity | BarangKeluarService::receiveExternal() |
| RECEIVE_EXTERNAL_OUT | -quantity | BarangKeluarService::receiveExternal() |
| IDM_TRANSFER_OUT | -quantity | TransferIdmService |
| IDM_TRANSFER_IN | +quantity | TransferIdmService |

---

## Alur Kerja & Struktur Data

### Penjualan Langsung (Sell)

```
PenjualanController
├── sellForm()
│   ├─ getGradingSourcesWithStock() — "Global Budgeting" logic
│   ├─ Filter by supplier, grade, date
│   └─ Show sales history with pagination
├── checkStock() — AJAX check batch remaining
├── sell()
│   ├─ Validate batch stock (getBatchRemainingStock)
│   ├─ Validate actual stock (hasEnoughStock)
│   └─ BarangKeluarService::sell() → SALE_OUT transaction
├── edit() — Show edit form
├── update() — Update weight/date directly on InventoryTransaction
└── destroy() — Soft delete transaction
```

### Transfer Internal

```
TransferInternalController
├── transferStep1()
│   ├─ getGradingSourcesWithStock() — dropdown stock
│   ├─ Show internal transfer history
│   └─ Filter by date, supplier, grade
├── storeTransferStep1() — Validate & store to session
├── transferStep2() — Confirmation view
├── transfer() — Final submit
│   ├─ Validate batch stock
│   ├─ Validate actual stock
│   └─ BarangKeluarService::transfer() → TRANSFER_OUT + TRANSFER_IN
├── checkStock() — AJAX check
├── edit() — Show edit form
├── update() → BarangKeluarService::updateTransferInternal()
└── destroy() — Soft delete + revert stock
```

### Transfer Eksternal

```
TransferExternalController
├── externalTransferStep1()
│   └─ Similar to internal but for external (Jasa Cuci)
├── externalTransfer() → EXTERNAL_TRANSFER_OUT + EXTERNAL_TRANSFER_IN
└── update/destroy similar pattern
```

### Receive Internal/External

```
ReceiveInternalController
└─ receiveInternal() → RECEIVE_INTERNAL_IN transaction

ReceiveExternalController
└─ receiveExternal() → RECEIVE_EXTERNAL_IN + RECEIVE_EXTERNAL_OUT
```

---

## Logika Perhitungan Stok ("Global Budgeting")

### getBatchRemainingStock()

```php
// 1. Batch stock (sorting_result_id specific)
$batchStock = InventoryTransaction::where('sorting_result_id', $sortingResultId)
    ->where('location_id', $locationId)
    ->sum('quantity_change_grams');

// 2. Total grade stock at location
$totalGradeLocationStock = InventoryTransaction::where('grade_company_id', $gradeCompanyId)
    ->where('location_id', $locationId)
    ->sum('quantity_change_grams');

// 3. Global net stock (entire warehouse system)
$globalNetStock = InventoryTransaction::where('grade_company_id', $gradeCompanyId)
    ->sum('quantity_change_grams');

// Final = min(batch, location, global)
$finalStock = min($batchStock, $totalGradeLocationStock, $globalNetStock);
```

**Tujuan:** Memastikan dropdown stock tidak melebihi stok nyata di sistem manapun.

### getGradingSourcesWithStock()

```php
// Menambah logging budget per parent grade family
// Agar SUM dropdown tidak melebihi total keluarga grade
$budgetPools[$budgetKey] -= $displayWeight;
```

---

## Security Review

### Yang Sudah Baik

| Aspek | Status | Detail |
|-------|--------|--------|
| Mass Assignment | ✅ | `fillable` di semua model |
| SQL Injection | ✅ | Eloquent ORM |
| CSRF | ✅ | Laravel default di form |
| Auth Middleware | ✅ | `auth` di route |
| Soft Deletes | ✅ | InventoryTransaction, StockTransfer pakai SoftDeletes |
| Eager Load | ✅ | `with(['gradeCompany', 'location', ...])` di query |
| XSS — Blade | ✅ | `{{ }}` auto-escape |
| Input Validation | ✅ | Form Request classes (SellRequest, TransferRequest) |

### Area yang Perlu Diperhatikan

| Aspek | Status | Catatan |
|-------|--------|---------|
| Error Handling | ⚠️ | Beberapa controller tidak ada try-catch |
| Stock Validation | ⚠️ | double-check di service dan controller |
| Session Security | ⚠️ | Step1 data di session perlu di-validate |
| Delete Logic | ✅ | Soft delete dengan deleted_by tracking |

---

## Bug & Vulnerability Findings (Detail)

---

### BK-01: Error Message Terekspos ke User (No Try-Catch)

**Lokasi:** Semua controller method

**Masalah:**
Controller memanggil service langsung tanpa try-catch. Ketika database error terjadi, exception propagate ke Laravel handler dan bisa expose raw error message.

**Solusi:**
Bungkus semua method controller dengan try-catch + user-friendly error message.

**Priority: P1** — Security/UX issue.

---

### BK-02: getGradesByFilter() Tidak Digunakan

**Lokasi:** `BarangKeluarService.php:602-635`

**Masalah:**
Method `getGradesByFilter()` ada di service tapi tidak pernah dipanggil dari controller manapun.

**Solusi:**
Hapus jika memang tidak diperlukan, atau integrate dengan fitur filter.

**Priority: P3** — Dead code.

---

### BK-03: Inconsistent Stock Check Logic

**Lokasi:** `BarangKeluarService.php:406-416` vs Controller

**Masalah:**
Ada 2 tempat stock check:
1. Service: `hasEnoughStock()` dan `getAvailableStock()`
2. Controller: duplicate validation logic

Ini berpotensi inconsistent jika salah satu diupdate tapi yang lain tidak.

**Solusi:**
Pastikan semua stock check centralized di service layer. Controller hanya memanggil service method.

**Priority: P2** — Maintainability issue.

---

### BK-04: getGradingSourcesWithStock() N+1 Query Potensial

**Lokasi:** `BarangKeluarService.php:652-712`

**Masalah:**
Loop di dalam map function yang memanggil query per item:
```php
$sources->map(function ($source) use (...) {
    // Multiple queries per iteration:
    // 1. GradeCompany lookup
    // 2. Budget pool calculation (potentially per parent)
    // 3. Location budget lookup
    // 4. Batch stock query
});
```

**Solusi:**
Buat batch query untuk menghitung semua budget pools di awal sebelum map().

**Priority: P2** — Performance issue.

---

### BK-05: Transfer IDM Handler dengan Code Generation

**Lokasi:** `TransferIdmService.php`

**Masalah:**
Service ini punya logic untuk auto-generate transfer code dengan pattern. Perlu dicek apakah sudah menggunakan database transaction dengan benar.

**Solusi:**
Audit TransferIdmService untuk ensure consistency dengan service lain.

**Priority: P2** — Consistency issue.

---

### BK-06: updateTransfer() Orphan Method

**Lokasi:** `BarangKeluarService.php:434-466`

**Masalah:**
Method `updateTransfer()` ada tapi isinya incomplete (komentar "Refactor needed") dan tidak dipanggil. Yang dipanggil adalah `updateTransferInternal()`, `updateExternalTransfer()`, `updateReceiveExternal()`.

**Solusi:**
Hapus `updateTransfer()` jika memang tidak digunakan.

**Priority: P3** — Dead code.

---

### BK-07: Redirect dengan withInput() Setelah Validasi Gagal

**Lokasi:** `PenjualanController.php:128-130`, `TransferInternalController.php:149-151`

**Masalah:**
Redirect dengan `withInput()` setelah gagal stok check. Ini potentially expose sensitive data jika ada error message dengan data lengkap.

**Solusi:**
Pastikan error message tidak expose internal data. Consider menggunakan session flash untuk error saja tanpa input.

**Priority: P3** — Information disclosure low risk.

---

### BK-08: Missing Validation pada Edit/Update

**Lokasi:** `PenjualanController.php:153-166`

**Masalah:**
`update()` method di PenjualanController tidak menggunakan Form Request, hanya inline validation. Ini inconsistent dengan `sell()` yang menggunakan `SellRequest`.

**Solusi:**
Gunakan Form Request untuk konsistensi.

**Priority: P3** — Inconsistency.

---

### BK-09: Soft Delete dengan deleted_by tapi tidak ada Kolom deleted_by

**Lokasi:** `TransferInternalController.php:300-320`

**Masalah:**
Kode mencoba set `deleted_by` sebelum delete, tapi perlu pastikan kolom `deleted_by` ada di tabel `stock_transfers`.

**Solusi:**
Cek schema atau buat migration jika diperlukan.

**Priority: P3** — Potential runtime error.

---

### BK-10: Global Budgeting Logic Kompleks dan Sulit di-Maintain

**Lokasi:** `BarangKeluarService.php:652-712`

**Masalah:**
`getGradingSourcesWithStock()` memiliki logic complex dengan mutable `$budgetPools` dan `$locationBudgets` yang di-mutate dalam loop. Ini:
- Sulit di-test
- Sulit di-debug
- Potentially inconsistent jika ada exception mid-loop

**Solusi:**
Refactor untuk gunakan state object atau query yang lebih straightforward.

**Priority: P2** — Technical debt.

---

## Summary Temuan

| ID | Issue | Severity | Lokasi | Solusi |
|----|-------|----------|--------|--------|
| BK-01 | Error message terekspos (no try-catch) | 🟡 MEDIUM | Semua controller | Tambah try-catch |
| BK-02 | getGradesByFilter() tidak digunakan | 🟢 LOW | BarangKeluarService.php | Hapus atau integrate |
| BK-03 | Inconsistent stock check logic | 🟡 MEDIUM | Service vs Controller | Centralize di service |
| BK-04 | N+1 query di getGradingSourcesWithStock | 🟡 MEDIUM | BarangKeluarService.php | Batch query |
| BK-05 | TransferIdmService consistency | 🟡 MEDIUM | TransferIdmService.php | Audit transaction handling |
| BK-06 | updateTransfer() orphan method | 🟢 LOW | BarangKeluarService.php | Hapus dead code |
| BK-07 | withInput() expose data | 🟢 LOW | Controller methods | Review error messages |
| BK-08 | Inline validation vs Form Request | 🟢 LOW | PenjualanController | Gunakan Form Request |
| BK-09 | deleted_by column check | 🟢 LOW | TransferInternalController | Verify schema |
| BK-10 | Global Budgeting logic complex | 🟡 MEDIUM | BarangKeluarService.php | Refactor untuk testability |

---

## Pending Questions / Butuh Konfirmasi

1. **Apakah getGradesByFilter() masih diperlukan?** Jika tidak, sebaiknya dihapus.

2. **Apakah TransferIdmService sudah consistent dengan pattern service lain?** Perlu review terhadap transaction handling.

3. **Apakah Global Budgeting logic sudah sesuai ekspektasi bisnis?** Logic saat ini kompleks dan perlu confirm apakah berjalan benar.

4. **Error messages yang show stock numbers — apakah acceptable?** Beberapa error message menampilkan jumlah stok actual untuk debugging user, perlu confirm apakah ini desired behavior.

5. **Apakah perlu ada fitur "stock adjustment" untuk koreksi manual?** Saat ini koreksi hanya bisa lewat edit/delete transaksi original.

---

## Existing Documentation untuk Referensi

Dokumentasi ini dibuat mengikuti format analisis yang sama dengan:
- `docs/tracking-stok/tracking-stok-analysis.md`
- `docs/barang-masuk/barang-masuk-analysis.md`
- `docs/manajemen-grading/grading-analysis.md`