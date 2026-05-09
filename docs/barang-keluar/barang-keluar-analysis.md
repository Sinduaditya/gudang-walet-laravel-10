# Barang Keluar - Security & Stock Integrity Analysis

## Overview
Dokumen ini menganalisis potential bugs dan kerentanan yang dapat menyebabkan **minus di stok** pada modul Barang Keluar. Analisis mencakup: Penjualan, Transfer Internal, Transfer External, Receive Internal, Receive External, dan Transfer IDM.

---

## 1. PENJUALAN (PenjualanController)

### Current Flow:
1. User pilih batch (sorting_result_id) dari dropdown
2. Cek batch stock via `getBatchRemainingStock()`
3. Cek real stock via `hasEnoughStock()`
4. Proses `sell()` → buat InventoryTransaction SALE_OUT (negatif)

### Vulnerabilities Found:

#### V-1.1: Race Condition - Stok Check vs Execute
**Severity: HIGH**
- **Issue**: Terdapat time gap antara saat user submit form dan saat eksekusi `sell()`. User bisa mengubah jumlah di form setelah stock check.
- **Location**: `sell()` method di `BarangKeluarService.php:118`
- **Scenario**:
  1. User A cek stok → available 100gr
  2. User A submit form dengan 100gr
  3. User B juga cek stok → available 100gr (belum terdecrement)
  4. User B submit form dengan 50gr
  5. User A sell 100gr → SUCCESS
  6. User B sell 50gr → SUCCESS (tapi seharusnya hanya 50gr tersisa)
- **Likelihood**: Medium
- **Impact**: Stock minus if concurrent transactions exceed actual stock

#### V-1.2: Edit/Update Tanpa Validasi Stok YangAdequat
**Severity: HIGH**
- **Issue**: `update()` di PenjualanController:181-198 melakukan update tanpa recalculate stock validation.
- **Location**: `PenjualanController.php:181-198`
- **Problem**:
  ```php
  $tx->update([
      'quantity_change_grams' => -abs($request->input('weight_grams')), // hanya update quantity
      'transaction_date' => $request->input('transaction_date'),
  ]);
  ```
  - Tidak ada cek apakah stok mencukupi untuk perubahan
  - Tidak ada reverting transaksi lama
- **Impact**: Jika user edit dari 50gr ke 100gr, tapi stok hanya 80gr, terjadi minus

#### V-1.3: Delete Tanpa Revert Stok
**Severity: MEDIUM**
- **Location**: `PenjualanController.php:201-211`
- **Issue**: `destroy()` hanya delete transaksi, TIDAK mengembalikan stok.
- **Code**:
  ```php
  $tx->delete(); // hanya hapus, tidak ada reverting
  ```
- **Impact**: Jika transaksi dihapus, stok yang sudah ter-decrement tidak kembali, menyebabkan stock mismatch

---

## 2. TRANSFER INTERNAL (TransferInternalController)

### Current Flow:
1. User pilih batch (sorting_result_id)
2. Cek batch stock & grade stock
3. Simpan ke session step1
4. Di step2, submit → `transfer()` → `BarangKeluarService::transfer()`

### Vulnerabilities Found:

#### V-2.1: Edit/Update Meningkatkan Berat Tanpa Validasi
**Severity: HIGH**
- **Location**: `TransferInternalController.php:292-330`
- **Issue**:
  ```php
  // Line 305-306
  $totalWeight = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);
  $availableStock = $this->service->getAvailableStock(...);
  // ...
  if ($availableStock < $totalWeight) {
      return back()->with('error', "Stok tidak mencukupi!");
  }
  ```
  - Available stock dihitung dari current stock, tapi tidak memperhitungkan berat TRANSFER YANG LAMA yang akan di-replace
  - Line 308-314: Ada effort untuk add back old weight, tapi hanya jika grade_company_id sama
  - **Masalah**: Jika user ganti grade_company_id saat edit, stok lama tidak di-revert
- **Scenario**:
  1. Transfer A: Grade X, 50gr dari Gudang Utama ke DMK
  2. User edit Transfer A: Ganti ke Grade Y, 80gr
  3. Validasi cek stok Grade Y = 100gr → OK
  4. Stok Grade X tidak di-revert → minus 50gr di Grade X
- **Impact**: Minus di grade yang lama

#### V-2.2: Delete Tidak Revert Stok Secara Total
**Severity: HIGH**
- **Location**: `TransferInternalController.php:332-355`
- **Code**:
  ```php
  foreach ($transfer->transactions as $transaction) {
      $transaction->deleted_by = auth()->id();
      $transaction->save();
      $transaction->delete(); // Only soft delete, no revert
  }
  $transfer->deleted_by = auth()->id();
  $transfer->save();
  $transfer->delete();
  ```
- **Issue**: Menghapus transaksi TIDAK mengembalikan stok. Hanya menandai deleted_by.
- **Impact**: Stock yang sudah ter-decrement tetap minus

#### V-2.3: Race Condition di Session-Based Step
**Severity: MEDIUM**
- **Issue**: Menggunakan session untuk menyimpan step1 data. Jika user lain melakukan transaksi berbeda, session bisa terkontaminasi.
- **Location**: `storeTransferStep1()` line 167: `$request->session()->put('transfer_step1', $validated);`
- **Impact**: Low - session per user, tapi tetap ada potential race dalam kondisi edge

---

## 3. TRANSFER EXTERNAL (TransferExternalController)

### Vulnerabilities Found:

#### V-3.1: Edit Ganti Grade Tapi Stok Lama Tidak di-Revert
**Severity: HIGH**
- **Location**: `TransferExternalController.php:276-313`
- **Issue**: Sama seperti V-2.1
  ```php
  if ($oldTransfer->grade_company_id == $validated['grade_company_id']) {
      $availableStock += $oldTransfer->weight_grams + ($oldTransfer->susut_grams ?? 0);
  }
  ```
  - Hanya revert jika grade sama
  - Jika grade berbeda, stok lama tetap minus
- **Impact**: Minus di grade yang lama

#### V-3.2: Delete Tanpa Revert Stok
**Severity: HIGH**
- **Location**: `TransferExternalController.php:316-332`
- **Code**:
  ```php
  $transfer->transactions()->delete();
  $transfer->delete();
  ```
- **Issue**: Hanya delete, tidak ada reverting stock
- **Impact**: Stock tetap minus

---

## 4. RECEIVE EXTERNAL (ReceiveExternalController)

### Vulnerabilities Found:

#### V-4.1: Edit Tidak Melakukan Validasi Pending Stock Yang Tepat
**Severity: HIGH**
- **Location**: `ReceiveExternalController.php:390-432`
- **Issue**:
  ```php
  // Line 406-409
  if ($oldTransfer->grade_company_id == $validated['grade_company_id'] &&
      $oldTransfer->from_location_id == $validated['from_location_id']) {
      $receivedStock -= ($oldTransfer->weight_grams + ($oldTransfer->susut_grams ?? 0));
  }
  ```
  - Logic sudah benar untuk subtract old transfer
  - Tapi ada subtle issue: jika user edit jadi lebih besar, tidak ada extra validation
- **Impact**: Jika edit menambah berat, pending stock validation bisa gagal

#### V-4.2: Delete Tidak Revert Stok
**Severity: MEDIUM**
- **Location**: `ReceiveExternalController.php:435-452`
- **Issue**: Delete hanya remove transactions, tidak revert
- **Impact**: Stok tidak kembali

---

## 5. RECEIVE INTERNAL (ReceiveInternalController)

### Vulnerabilities Found:

#### V-5.1: Delete Method Not Implemented
**Severity: LOW**
- **Location**: `ReceiveInternalController.php`
- **Issue**: Tidak ada `destroy()` method
- **Impact**: Receive Internal tidak bisa dihapus, tidak ada minus risk dari sini

---

## 6. TRANSFER IDM (TransferIdmController)

### Vulnerabilities Found:

#### V-6.1: Edit/Update Ganti Items Bisa Cause Stock Issues
**Severity: HIGH**
- **Location**: `TransferIdmController.php:209-260`
- **Issue**:
  ```php
  // Line 234-235
  $itemIds = array_column($request->items, 'id');
  $items = \App\Models\IdmDetail::whereIn('id', $itemIds)->get();
  ```
  - Jika user remove items dari transfer, IdmDetail tidak di-return ke available pool
  - IdmTransferDetail tetap pointing ke IdmDetail yang sudah tidak terkait
- **Impact**: Item "tersesat" dalam sistem, tidak bisa di-transfer ulang tapi juga tidak di-count sebagai available

#### V-6.2: Delete Tidak Clear IdmTransferDetails
**Severity: MEDIUM**
- **Location**: `TransferIdmController.php:268-272`
- **Issue**: `deleteTransfer()` menghapus IdmTransfer tapi tidak mengembalikan IdmDetail ke available state

---

## 7. ROOT CAUSE - GRADING_IN Tidak Memiliki sorting_result_id (CRITICAL)

### G-0: GRADING_IN Transactions Tidak Terikat SortingResult
**Severity: CRITICAL**
**Location**: Database & GradingGoodsService, BarangKeluarService::getGradingSourcesWithStock()

#### Evidence dari SQL Dump:
```
INSERT INTO `inventory_transactions` (`id`, ..., `transaction_type`, `reference_id`, `sorting_result_id`, ...)
VALUES
(1059, '2025-12-15', 118, 1, 36, 368.00, 'GRADING_IN', 1060, NULL, 1, ...),
(1060, '2025-12-15', 118, 1, 36, 302.00, 'GRADING_IN', 1061, NULL, 1, ...),
...
```
**Sorting_result_id = NULL** pada semua GRADING_IN transactions!

#### Dampak pada Dropdown Stock:

**Location**: `BarangKeluarService.php:713-722`
```php
// Batch stock hanya menghitung yang ADA sorting_result_id
$batchStockResults = InventoryTransaction::select('sorting_result_id')
    ->selectRaw('SUM(quantity_change_grams) as batch_stock')
    ->whereIn('sorting_result_id', $sourceIds)  // GRADING_IN dengan NULL TIDAK TERHITUNG!
    ->where('location_id', $locationId)
    ->groupBy('sorting_result_id')
    ->pluck('batch_stock', 'sorting_result_id')
    ->toArray();
```

#### Contoh Bug:
1. **Initial State**: SortingResult A dibuat dengan GRADING_IN 500gr (sorting_result_id = NULL)
2. **Penjualan 1**: User jual 300gr dari SortingResult A → SALE_OUT dengan sorting_result_id = A.id
3. **Dropdown Refresh**:
   - `batchStock` = SUM WHERE sorting_result_id = A.id = 0 (GRADING_IN tidak punya sorting_result_id!)
   - `locationBudget` = SUM WHERE grade_company_id = A.grade_id AND location_id = 1 = 200gr
   - `globalBudget` = SUM WHERE grade_company_id = A.grade_id = 200gr
4. **displayWeight** = min(0, 200, 200) = 0 → Batch A **TIDAK TAMPIL** di dropdown
5. Tapi user tetap bisa jual 200gr tersisa karena `hasEnoughStock()` berdasarkan `globalBudget = 200gr` tetap OK

#### Kenapa Minus Bisa Terjadi:

**Scenario lengkap:**
1. SortingResult A (Grade X) punya GRADING_IN 500gr (sorting_result_id = NULL)
2. User A pilih Grade X di dropdown barang keluar
3. Sistem cek: globalBudget = 500gr (dari SUM semua transactions tanpa filter sorting_result_id)
4. User A jual 500gr → globalBudget - 500 = 0
5. Tapi dropdown masih menampilkan Grade X dengan stock 500gr (ERROR: dropdown tidak update!)
6. User B pilih Grade X → jual lagi 500gr → **MINUS 500gr!**

#### Root Cause:
- GRADING_IN tidak menyimpan `sorting_result_id` saat dibuat
- `getBatchRemainingStock()` menggunakan SUM dengan `WHERE sorting_result_id = X`, tapi GRADING_IN tidak punya ID ini
- Global stock dihitung dari `grade_company_id` saja (tanpa sorting_result_id), jadi GRADING_IN tetap terhitung

#### Solution:
1. **Fix segera**: GRADING_IN harus menyimpan `sorting_result_id` saat dibuat (relasi harus benar)
2. **Alternative fix**: Ubah query `getBatchRemainingStock()` untuk tidak bergantung pada sorting_result_id

### G-0 STATUS: ALREADY FIXED ✅
**Tanggal Update**: 2026-05-09

**Verifikasi di Production Database**:
- Total GRADING_IN transactions: 4,687
- GRADING_IN dengan sorting_result_id: 4,374 (93.3%)
- GRADING_IN dengan NULL: 313 (6.7%) - **data lama historis**

**Bukti GRADING_IN terbaru sudah terisi**:
```
ID: 9122 | sorting_result_id: 7826   | ✓ HAS ID
ID: 9121 | sorting_result_id: 7825   | ✓ HAS ID
ID: 9120 | sorting_result_id: 7824   | ✓ HAS ID
... (10 GRADING_IN terbaru - SEMUA punya sorting_result_id)
```

**Kenapa NULL di data lama tidak berbahaya**:
1. Batch dengan `batchStock = 0` tidak muncul di dropdown (displayWeight = 0)
2. Sistem tetap melakukan validasi `hasEnoughStock()` saat proses jual
3. NULL GRADING_IN hanya mempengaruhi global budget, TIDAK batch stock
4. Protection: `min(batchStock, locationBudget, globalBudget)` tetap berjalan

**Kesimpulan**: Issue G-0 sudah **FIXED untuk data baru**. Data NULL lama (313 records) adalah historical data yang tidak menyebabkan minus stock karena batchnya tidak muncul di dropdown.

---

## 8. GENERAL ISSUES (Semua Modul)

### G-1: hasEnoughStock() Check But Not Atomic
**Severity: MEDIUM**
- **Location**: `BarangKeluarService.php:406-417`
- **Issue**: `hasEnoughStock()` adalah separate query, tidak atomic dengan insert
- **Race Condition**: Thread A check stock OK → Thread B check stock OK → Thread A insert → Thread B insert → minus
- **Solution**: Perlu locking atau transaction isolation level

### G-2: getAvailableStock() Returns Negative Values
**Severity: MEDIUM**
- **Location**: `BarangKeluarService.php:398-404`
- **Code**:
  ```php
  public function getAvailableStock(int $gradeCompanyId, int $locationId): float
  {
      return (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
          ->where('location_id', $locationId)
          ->sum('quantity_change_grams'); // Bisa negatif!
  }
  ```
- **Issue**: Function returns negative values instead of 0, allowing operations on already-minus stock
- **Impact**: Minus stock bisa semakin minus

### G-3: No Database Transaction Isolation
**Severity: MEDIUM**
- **Issue**: Tidak ada penggunaan `SELECT FOR UPDATE` atau transaction isolation level
- **Impact**: Race conditions pada concurrent transactions

---

## SUMMARY - Risk Matrix

| ID | Issue | Severity | Impact | Status |
|----|-------|----------|--------|--------|
| ~~**G-0**~~ | ~~**GRADING_IN sorting_result_id = NULL**~~ | ~~**CRITICAL**~~ | ~~**Minus dari dropdown error**~~ | **✅ ALREADY FIXED** |
| V-1.2 | Edit Penjualan Tanpa Validasi Stok | HIGH | Stock minus | **Need Fix** |
| V-1.3 | Delete Penjualan Tidak Revert Stok | MEDIUM | Stock mismatch | **Need Fix** |
| V-2.1 | Edit Transfer Internal Ganti Grade | HIGH | Minus di grade lama | **Need Fix** |
| V-2.2 | Delete Transfer Internal Tidak Revert | HIGH | Stock minus | **Need Fix** |
| V-3.1 | Edit Transfer External Ganti Grade | HIGH | Minus di grade lama | **Need Fix** |
| V-3.2 | Delete Transfer External Tidak Revert | HIGH | Stock minus | **Need Fix** |
| V-4.1 | Edit Receive External Validation | MEDIUM | Potential minus | **Need Fix** |
| V-4.2 | Delete Receive External Tidak Revert | MEDIUM | Stock mismatch | **Need Fix** |
| V-6.1 | Edit Transfer IDM Item Management | HIGH | Item tersesat | **Need Fix** |
| V-6.2 | Delete Transfer IDM Orphan | MEDIUM | Item inconsistency | **Need Fix** |
| G-1 | Race Condition - Non Atomic Check | MEDIUM | Stock minus | Consider |
| G-2 | getAvailableStock Returns Negative | MEDIUM | Uncontrolled minus | Consider |
| G-3 | No Transaction Isolation | MEDIUM | Race conditions | Consider |

---

## Key Recommendations

1. **REMOVE EDIT functionality** dari semua modul Barang Keluar (mirip dengan yang sudah dilakukan di Grading Goods dan Barang Masuk)

2. **IMPLEMENT HARD DELETE with STOCK REVERT** untuk semua delete operations:
   - Saat delete, hitung kembali perubahan stok dan revert
   - Atau: gunakan soft delete dan biarkan admin handle manual adjustment

3. **IMPLEMENT ATOMIC STOCK CHECK** menggunakan:
   - Database transaction dengan `lockForUpdate()`
   - Atau:乐观锁 dengan version field

4. **USE TRANSACTION ISOLATION LEVEL** SERIALIZABLE untuk critical stock operations

5. **ADD MONITORING** untuk detect minus stock conditions early

---

*Document created: 2026-05-09*
*Project: gudang-walet-laravel-10*
