# Fix: Bug Refactor `is_jasa_cuci`

**Branch**: `fix/barang-keluar-lokasi`
**Tanggal**: 2026-06-28
**Referensi Review**: `docs/review-is-jasa-cuci-bugs.md`

Dokumen ini mencatat 3 bug yang ditemukan dan diperbaiki setelah refactor flag `is_jasa_cuci`.

---

## Fix 1 — CRITICAL: `SortMaterialServiceTest` Pakai `RefreshDatabase`

**File**: `tests/Feature/SortMaterialServiceTest.php`

### Root Cause

Trait `RefreshDatabase` truncate semua tabel + migrate ulang setiap kali `php artisan test` dijalankan. Karena `phpunit.xml` tidak punya config DB testing terpisah (SQLite di-comment), test jalan di DB `walet` (MySQL). Akibatnya **semua data aplikasi hilang** setiap kali developer run test.

Dok `lokasi-flag-refactor.md` section 12 klaim fix sudah diapply, tapi kode aktual masih `RefreshDatabase`.

### Perubahan

```diff
- use Illuminate\Foundation\Testing\RefreshDatabase;
+ use Illuminate\Foundation\Testing\DatabaseTransactions;

  class SortMaterialServiceTest extends TestCase
  {
-     use RefreshDatabase;
+     use DatabaseTransactions;
```

### Cara Kerja `DatabaseTransactions`

Wrap setiap test dalam 1 DB transaction. Setelah test selesai, transaction di-rollback — data test hilang, data aplikasi **aman**. Test juga jauh lebih cepat karena tidak ada truncate/migrate.

### Hasil Verifikasi

```
PASS  Tests\Feature\SortMaterialServiceTest
  ✓ process internal grading success        0.68s
  ✓ delete child grade does not fail        0.20s
  ✓ delete parent target restores stock     0.19s

Tests: 3 passed (15 assertions)
Duration: 1.40s   ← sebelumnya ~23.5s dengan RefreshDatabase
```

---

## Fix 2 — HIGH: `getFlowBarangKeJasaCuci()` Pakai LIKE

**File**: `app/Services/Dashboard/DashboardService.php`

### Root Cause

Method `getFlowBarangKeJasaCuci()` (untuk chart dashboard "Flow ke Jasa Cuci") masih pakai filter nama-based, bukan flag. Ini adalah method **berbeda** dari `getFlowBarangKeDMK()` yang memang disengaja tetap LIKE.

Akibatnya: kalau lokasi Jasa Cuci baru ditambah lewat UI form (fitur baru dari refactor ini), lokasi itu akan muncul di dropdown Transfer External tapi **tidak muncul di chart dashboard**.

### Perubahan

```diff
- $jasaCuciLocations = Location::where('name', 'NOT LIKE', '%Gudang Utama%')
-     ->where('name', 'NOT LIKE', '%DMK%')
-     ->where('name', 'NOT LIKE', '%Demak%')
-     ->pluck('id');
+ $jasaCuciLocations = Location::where('is_jasa_cuci', true)->pluck('id');
```

Sekarang chart dashboard otomatis include semua lokasi dengan `is_jasa_cuci = true`, sama persis dengan sumber data dropdown Transfer External.

---

## Fix 3 — LOW: Dead Variable `$dmkLocation`

**File**: `app/Http/Controllers/Feature/TransferInternalController.php`

### Root Cause

Setelah refactor, `$dmkLocation` tidak lagi dipakai di view — view sudah pakai `$internalDestinations` (yang diambil via `is_jasa_cuci` flag). Tapi variabel ini masih di-fetch dan di-pass ke view.

Dampak:
- 1 DB query sia-sia per page load Transfer Internal
- Latent bug: developer lain bisa mulai pakai `$dmkLocation` lagi (kembali ke hardcode)

### Perubahan

```diff
- $dmkLocation = Location::where('name', 'DMK')->first();
-
  // Ambil semua lokasi non-jasa-cuci (exit-point) sebagai tujuan transfer internal,
```

```diff
  return view('admin.barang-keluar.transfer-step1', compact(
      'gradesWithStock',
-     'dmkLocation',
      'internalDestinations',
```

---

## Statistik Perubahan

```
3 files changed
4 insertions(+)
10 deletions(-)
```

| File | Perubahan |
|------|-----------|
| `tests/Feature/SortMaterialServiceTest.php` | `RefreshDatabase` → `DatabaseTransactions` |
| `app/Services/Dashboard/DashboardService.php` | 3 LIKE → 1 flag query |
| `app/Http/Controllers/Feature/TransferInternalController.php` | Hapus `$dmkLocation` fetch + compact |

---

## Verifikasi Akhir

```bash
php artisan test
# Semua test pass, DB aplikasi tidak ter-reset
```

```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\SortMaterialServiceTest
  ✓ process internal grading success
  ✓ delete child grade does not fail due to parent stock cache
  ✓ delete parent target restores stock to source parent

Tests: 5 passed (17 assertions)
Duration: 1.40s
```

---

*Fix oleh: Claude Code — 2026-06-28*
