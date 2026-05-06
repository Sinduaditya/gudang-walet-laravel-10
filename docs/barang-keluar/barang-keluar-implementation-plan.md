# Implementation Plan: Barang Keluar — Security & Performance Fix

## Background

Berdasarkan hasil security scan di `barang-keluar-analysis.md`, plan ini merangkum semua kerentanan dan issue yang ditemukan beserta status pengerjaannya.

---

## Issue yang Diperbaiki (Session Ini)

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| BK-01 | Error message terekspos ke user (no try-catch) | MEDIUM | ✅ Done |
| BK-03 | Inconsistent stock check logic | MEDIUM | ✅ Done |
| BK-04 | N+1 query — getGradingSourcesWithStock() | MEDIUM | ✅ Done |
| BK-10 | Global Budgeting logic complex | MEDIUM | ✅ Done |

---

## Issue yang Tidak Diperbaiki (Dilewati)

| ID | Issue | Severity | Alasan |
|----|-------|----------|--------|
| BK-02 | getGradesByFilter() tidak digunakan | LOW | P3 — perlu konfirmasi apakah akan digunakan |
| BK-05 | TransferIdmService consistency | MEDIUM | P3 — perlu review lebih mendalam |
| BK-06 | updateTransfer() orphan method | LOW | P3 — dead code, low priority |
| BK-07 | withInput() expose data | LOW | P3 — low risk, informational |
| BK-08 | Inline validation vs Form Request | LOW | P3 — consistency issue, non-critical |
| BK-09 | deleted_by column check | LOW | P3 — perlu cek schema dulu |

---

## Detail Perbaikan

### BK-01: Error Message Terekspos ke User (Try-Catch)

**Lokasi:** PenjualanController.php, TransferInternalController.php, TransferExternalController.php, ReceiveInternalController.php, ReceiveExternalController.php

**Masalah:** Controller memanggil service langsung tanpa try-catch. Ketika database error terjadi, exception propagate ke Laravel handler dan bisa expose raw error message ke user.

**Solusi:** Tambah try-catch di setiap method controller dengan logging dan user-friendly error message.

**Files Modified:**
- app/Http/Controllers/Feature/PenjualanController.php — semua method
- app/Http/Controllers/Feature/TransferInternalController.php — semua method
- app/Http/Controllers/Feature/TransferExternalController.php — semua method
- app/Http/Controllers/Feature/ReceiveInternalController.php — semua method
- app/Http/Controllers/Feature/ReceiveExternalController.php — semua method

---

### BK-03: Inconsistent Stock Check Logic

**Lokasi:** BarangKeluarService.php vs Controller

**Masalah:** Ada 2 tempat stock check yang berbeda:
1. Service: `hasEnoughStock()` dan `getAvailableStock()`
2. Controller: duplicate validation logic dengan message yang berbeda

**Solusi:** Controller menggunakan service method yang sudah ada. Logika stock check tetap di service layer untuk konsistensi.

**Files Modified:**
- app/Http/Controllers/Feature/PenjualanController.php
- app/Http/Controllers/Feature/TransferInternalController.php
- app/Services/BarangKeluar/BarangKeluarService.php

---

### BK-04: N+1 Query — getGradingSourcesWithStock()

**Lokasi:** BarangKeluarService.php — getGradingSourcesWithStock()

**Masalah:** Loop di dalam map() yang memanggil query per item. Jika 50 sources, berarti banyak query.

**Solusi:** Batch query optimization:
- Pre-calculate semua budget pools dalam 1 query per parent group
- Pre-calculate semua location budgets dalam 1 query
- Pre-calculate semua batch stocks dalam 1 query
- Iterate menggunakan pre-calculated data (NO additional queries)

**Files Modified:**
- app/Services/BarangKeluar/BarangKeluarService.php

---

### BK-10: Global Budgeting Logic Kompleks

**Lokasi:** BarangKeluarService.php — getGradingSourcesWithStock()

**Masalah:** Logic dengan mutable `$budgetPools` dan `$locationBudgets` yang di-mutate dalam loop. Sulit di-test dan di-debug.

**Solusi:** Refactor complete function dengan:
- Clear separation of concerns (data collection vs calculation)
- Pre-calculation phase vs iteration phase
- No nested queries dalam loop
- Self-documenting variable names

**Files Modified:**
- app/Services/BarangKeluar/BarangKeluarService.php

---

## Files yang Dimodifikasi

| File | Issue | Perubahan |
|------|-------|-----------|
| app/Http/Controllers/Feature/PenjualanController.php | BK-01, BK-03 | Try-catch, centralize stock check |
| app/Http/Controllers/Feature/TransferInternalController.php | BK-01, BK-03 | Try-catch, centralize stock check |
| app/Http/Controllers/Feature/TransferExternalController.php | BK-01, BK-03 | Try-catch, centralize stock check |
| app/Http/Controllers/Feature/ReceiveInternalController.php | BK-01, BK-03 | Try-catch, centralize stock check |
| app/Http/Controllers/Feature/ReceiveExternalController.php | BK-01, BK-03 | Try-catch, centralize stock check |
| app/Services/BarangKeluar/BarangKeluarService.php | BK-03, BK-04, BK-10 | Batch query, refactor Global Budgeting |

---

## Summary Status Lengkap

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| BK-01 | Error message terekspos ke user (no try-catch) | MEDIUM | P1 | ✅ Done |
| BK-02 | getGradesByFilter() tidak digunakan | LOW | P3 | Open |
| BK-03 | Inconsistent stock check logic | MEDIUM | P2 | ✅ Done |
| BK-04 | N+1 query di getGradingSourcesWithStock | MEDIUM | P2 | ✅ Done |
| BK-05 | TransferIdmService consistency | MEDIUM | P3 | Open |
| BK-06 | updateTransfer() orphan method | LOW | P3 | Open |
| BK-07 | withInput() expose data | LOW | P3 | Open |
| BK-08 | Inline validation vs Form Request | LOW | P3 | Open |
| BK-09 | deleted_by column check | LOW | P3 | Open |
| BK-10 | Global Budgeting logic complex | MEDIUM | P2 | ✅ Done |