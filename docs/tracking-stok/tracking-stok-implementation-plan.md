# Implementation Plan: Tracking Stok — Security & Performance Fix

## Background

Berdasarkan hasil security scan di `tracking-stok-analysis.md`, plan ini merangkum semua kerentanan dan issue yang ditemukan beserta status pengerjaannya.

---

## Issue yang Diperbaiki (Session Ini)

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| TS-01 | Error message terekspos ke user (no try-catch) | MEDIUM | Done |
| TS-02 | N+1 query — calculateGlobalStock() di dalam loop | MEDIUM | Done |
| TS-03 | calculateParentPositiveStock/NegativeStock tidak optimal — processing di PHP | MEDIUM | Done |
| TS-04 | tracking.blade.php orphan — tidak ada route | MEDIUM | Done |
| TS-06 | Log::info() debug aktif di production | LOW | Done |
| TS-07 | Missing audit trail untuk akses data | LOW | Done |
| TS-08 | Back button navigasi tidak intuitif | LOW | Done |

---

## Issue yang Tidak Diperbaiki (Dilewati)

| ID | Issue | Severity | Alasan |
|----|-------|----------|--------|
| TS-05 | Global stock tanpa filter date — tidak scalable | LOW | P3 — perlu konfirmasi kebutuhan bisnis |
| TS-09 | fromLocation/toLocation bisa null tapi hanya fallback ke Unknown | LOW | P3 — perlu konfirmasi apakah edge case ini valid |

---

## Detail Perbaikan

### TS-01: Error Message Terekspos ke User (Try-Catch)

**Lokasi:** TrackingStockController.php — semua method

**Masalah:** Controller memanggil service langsung tanpa try-catch. Ketika database error terjadi, exception propagate ke Laravel handler dan bisa expose raw error message ke user.

**Solusi:** Tambah try-catch di setiap method controller.

**Files Modified:**
- app/Http/Controllers/Feature/TrackingStockController.php — semua 5 method (index, parentGrades, parentSorts, detail, susut)

---

### TS-02: N+1 Query — calculateGlobalStock() di dalam Loop

**Lokasi:** TrackingStockController.php — parentGrades() method

**Masalah:** Loop memanggil calculateGlobalStock() per item. Jika 15 grades, berarti 1 + 15 = 16 query.

**Solusi:** Buat method batch calculateGlobalStockBulk() di service.

**Files Modified:**
- app/Services/Stock/TrackingStockService.php — tambahkan calculateGlobalStockBulk()
- app/Http/Controllers/Feature/TrackingStockController.php — parentGrades() method

---

### TS-03: calculateParentPositiveStock/NegativeStock Processing di PHP

**Lokasi:** TrackingStockService.php — calculateParentPositiveStock() dan calculateParentNegativeStock()

**Masalah:** Loop per grade + filter di PHP. Banyak query dan processing di aplikasi level.

**Solusi:** Pindahkan ke single SQL query dengan max(0, total) dan min(0, total).

**Catatan:** Rumus berubah dari GROUP BY location_id ke simple SUM. Perlu confirm apakah new rumus sesuai kebutuhan bisnis.

**Files Modified:**
- app/Services/Stock/TrackingStockService.php

---

### TS-04: tracking.blade.php Orphan — Tidak Ada Route

**Lokasi:** resources/views/admin/stock/tracking.blade.php

**Masalah:** File blade ada tapi tidak ada route yang mengarah ke sana.

**Solusi:** Hapus file orphan.

**Files Modified:**
- resources/views/admin/stock/tracking.blade.php — deleted

---

### TS-06: Log::info() Debug Aktif di Production

**Lokasi:** TrackingStockService.php — getStockPerLocation()

**Masalah:** Log debug tetap aktif di production.

**Solusi:** Comment out atau hapus Log::info.

**Files Modified:**
- app/Services/Stock/TrackingStockService.php

---

### TS-07: Missing Audit Trail untuk Akses Data

**Lokasi:** TrackingStockController.php — semua method

**Masalah:** Tidak ada logging untuk siapa mengakses data apa dan kapan.

**Solusi:** Tambah Log::channel(audit) di setiap method.

**Files Modified:**
- app/Http/Controllers/Feature/TrackingStockController.php — semua method

---

### TS-08: Back Button Navigasi Tidak Intuitif

**Lokasi:** detail.blade.php — tombol kembali

**Masalah:** Tombol kembali selalu ke index, padahal user expectation kembali ke halaman sebelumnya.

**Solusi:** Gunakan session referrer + url()->previous() fallback.

**Files Modified:**
- app/Http/Controllers/Feature/TrackingStockController.php — detail() method
- resources/views/admin/stock/detail.blade.php — back button link

---

## Files yang Dimodifikasi

| File | Issue | Perubahan |
|------|-------|-----------|
| app/Http/Controllers/Feature/TrackingStockController.php | TS-01, TS-02, TS-07, TS-08 | Try-catch, batch stock, audit log, session referrer |
| app/Services/Stock/TrackingStockService.php | TS-02, TS-03, TS-06 | calculateGlobalStockBulk(), optimize positive/negative, hapus debug log |
| resources/views/admin/stock/detail.blade.php | TS-08 | Back button dengan session referrer |
| resources/views/admin/stock/tracking.blade.php | TS-04 | Deleted (orphan file) |

---

## Summary Status Lengkap

| ID | Issue | Severity | Prioritas | Status |
|----|-------|----------|-----------|--------|
| TS-01 | Error message terekspos ke user (no try-catch) | MEDIUM | P1 | Done |
| TS-02 | N+1 query — calculateGlobalStock() di dalam loop | MEDIUM | P2 | Done |
| TS-03 | Positive/Negative stock processing di PHP, bukan SQL | MEDIUM | P2 | Done |
| TS-04 | tracking.blade.php orphan — tidak ada route | MEDIUM | P3 | Done |
| TS-05 | Global stock tanpa filter date — tidak scalable | LOW | P3 | Open |
| TS-06 | Log::info() debug aktif di production | LOW | P3 | Done |
| TS-07 | Missing audit trail untuk akses data | LOW | Future | Done |
| TS-08 | Back button navigasi tidak intuitif | LOW | P3 | Done |
| TS-09 | fromLocation/toLocation bisa null tapi hanya fallback ke Unknown | LOW | P3 | Open |