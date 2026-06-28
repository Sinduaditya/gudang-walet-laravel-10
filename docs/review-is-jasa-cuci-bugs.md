# Review: Bug & Inkonsistensi Refactor `is_jasa_cuci`

**Branch**: `fix/barang-keluar-lokasi`
**Tanggal Review**: 2026-06-28

Dokumen ini mencatat bug dan inkonsistensi yang ditemukan setelah refactor flag `is_jasa_cuci` di `docs/lokasi-flag-refactor.md`.

---

## Bug 1 — CRITICAL: `SortMaterialServiceTest` Masih Pakai `RefreshDatabase`

**File**: `tests/Feature/SortMaterialServiceTest.php` baris 14–15

### Kondisi Aktual

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
// ...
use RefreshDatabase;
```

### Masalah

Dok section 12 (`lokasi-flag-refactor.md`) klaim fix sudah diapply — trait diganti ke `DatabaseTransactions` agar DB aplikasi tidak ter-reset saat test. **Tapi kode aktual masih `RefreshDatabase`.**

Karena `phpunit.xml` tidak punya config DB testing terpisah (SQLite di-comment), test jalan pakai DB `walet` (MySQL). Setiap `php artisan test` dijalankan:

1. `RefreshDatabase` truncate semua tabel
2. Migrate ulang dari awal
3. **Semua data yang admin sudah input hilang permanen**

### Fix

```php
// Sebelum
use Illuminate\Foundation\Testing\RefreshDatabase;
// ...
use RefreshDatabase;

// Sesudah
use Illuminate\Foundation\Testing\DatabaseTransactions;
// ...
use DatabaseTransactions;
```

`DatabaseTransactions` wrap setiap test dalam 1 DB transaction lalu rollback setelah selesai. Data test hilang, data aplikasi **aman**.

### Verifikasi Fix

```bash
# Catat jumlah data sebelum test
php artisan tinker --execute="echo \App\Models\Location::count();"

# Jalankan test
php artisan test

# Pastikan jumlah data sama setelah test
php artisan tinker --execute="echo \App\Models\Location::count();"
```

---

## Bug 2 — HIGH: `DashboardService::getFlowBarangKeJasaCuci()` Masih Pakai LIKE

**File**: `app/Services/Dashboard/DashboardService.php` baris 120–127

### Kondisi Aktual

```php
public function getFlowBarangKeJasaCuci()
{
    $jasaCuciLocations = Location::where('name', 'NOT LIKE', '%Gudang Utama%')
        ->where('name', 'NOT LIKE', '%DMK%')
        ->where('name', 'NOT LIKE', '%Demak%')
        ->pluck('id');
```

### Masalah

Ini adalah method **berbeda** dari `getFlowBarangKeDMK()` (yang memang disengaja tetap LIKE per dok). Method ini bertujuan menampilkan chart "Flow ke Jasa Cuci" — justru merupakan target utama refactor.

Karena masih pakai filter nama-based (`NOT LIKE '%DMK%'`), kalau lokasi Jasa Cuci baru ditambah (mis. "SEMARANG"):
- Dropdown Transfer External → ✅ SEMARANG muncul (sudah pakai `is_jasa_cuci`)
- Chart dashboard Jasa Cuci → ❌ SEMARANG **tidak muncul** (masih LIKE)

Ini defeating the purpose of the whole refactor.

### Fix

```php
// Sebelum
$jasaCuciLocations = Location::where('name', 'NOT LIKE', '%Gudang Utama%')
    ->where('name', 'NOT LIKE', '%DMK%')
    ->where('name', 'NOT LIKE', '%Demak%')
    ->pluck('id');

// Sesudah
$jasaCuciLocations = Location::where('is_jasa_cuci', true)->pluck('id');
```

---

## Inkonsistensi Rendah — Dead Variable `$dmkLocation`

**File**: `app/Http/Controllers/Feature/TransferInternalController.php` baris 55, 104

### Kondisi Aktual

```php
// Baris 55 — fetch DMK by name
$dmkLocation = Location::where('name', 'DMK')->first();

// Baris 104 — di-pass ke view
return view('...', compact(
    'dmkLocation',          // ← di-pass
    'internalDestinations', // ← ini yang sebenarnya dipakai view
    ...
));
```

View `transfer-step1.blade.php` **tidak menggunakan `$dmkLocation`** — hanya pakai `$internalDestinations`.

### Dampak

- 1 wasted DB query per page load Transfer Internal
- Latent bug: kalau developer lain melihat `$dmkLocation` ada di view data, mungkin mulai pakai lagi → hardcode kembali

### Fix

Hapus baris 55 (fetch `$dmkLocation`) dan hapus `'dmkLocation'` dari array compact.

---

## Yang Tidak Perlu Diubah

| File | Alasan |
|------|--------|
| `ReceiveInternalController.php` — LIKE `%DMK%` | Menu FE sudah di-comment, PR terpisah |
| `DashboardService::getFlowBarangKeDMK()` — LIKE | Intentional, chart spesifik DMK |

---

## Ringkasan

| Severity | Issue | File | Baris |
|---|---|---|---|
| **CRITICAL** | `RefreshDatabase` reset DB produksi setiap test | `tests/Feature/SortMaterialServiceTest.php` | 14–15 |
| **HIGH** | `getFlowBarangKeJasaCuci()` pakai LIKE bukan flag | `app/Services/Dashboard/DashboardService.php` | 124–126 |
| **LOW** | Dead variable `$dmkLocation` + wasted query | `app/Http/Controllers/Feature/TransferInternalController.php` | 55, 104 |

---

*Review oleh: Claude Code — 2026-06-28*
