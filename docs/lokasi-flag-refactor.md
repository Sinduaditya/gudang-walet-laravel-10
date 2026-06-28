# Refactor Lokasi dengan Flag `is_jasa_cuci`

## Ringkasan Perubahan

Refactor dilakukan untuk **mengganti hardcode string "DMK" dan LIKE/NOT LIKE** dengan **satu kolom flag `is_jasa_cuci`** di tabel `locations`. Tujuan utama: mencegah bug di masa depan saat lokasi baru ditambah tanpa perlu ubah kode.

---

## 1. Latar Belakang Masalah

### Sebelum Refactor: Semua Hardcode String

Sebelum refactor, sistem membedakan 3 kategori lokasi **implisit** lewat **string nama**:

| Kategori | Lokasi | Cara Sistem Membedakan |
|----------|--------|------------------------|
| Gudang pusat | `Gudang Utama` | Cek `name = 'Gudang Utama'` di controller |
| Exit-point internal | `DMK` | Cek `stripos($name, 'DMK')` di service & controller |
| Jasa Cuci (mitra/vendor) | `KRIS, WIKOM, ASIH, ...` | Filter `NOT LIKE '%DMK%'` di controller |

### Dampak Negatif

1. **Rentan bug**: Kalau lokasi baru ditambah (mis. "DMK 2" atau "Demak Baru"), kode lama **tidak otomatis mengenali** sebagai exit-point. Update kode manual → bug tersembunyi.
2. **String magic tersebar**: Pattern `stripos('DMK')` muncul di beberapa file. Copy-paste rawan typo.
3. **Tidak ada single source of truth**: Definisi "jasa cuci atau bukan" tersimpan sebagai string LIKE di 4 controller + 1 service + 2 export class.
4. **Dropdown kaku**: User tidak bisa pilih tujuan transfer internal — selalu hardcode ke DMK.

### Lokasi Hardcode yang Ditemukan

| # | File | Baris | Pola Hardcode |
|---|------|-------|---------------|
| 1 | `app/Http/Controllers/Feature/TransferInternalController.php` | 55 | `Location::where('name', 'DMK')->first()` |
| 2 | `app/Http/Controllers/Feature/TransferInternalController.php` | 298 | `stripos($toLocation->name, "DMK") === false` |
| 3 | `app/Services/BarangKeluar/BarangKeluarService.php` | 244 | `stripos($toLocation->name, 'DMK') === false` |
| 4 | `resources/views/admin/barang-keluar/transfer-step1.blade.php` | 191 | Hidden input hardcode DMK |
| 5 | `resources/views/admin/barang-keluar/transfer-step1.blade.php` | 839 | JS `const toLocation = "DMK"` |
| 6 | `app/Http/Controllers/Feature/TransferExternalController.php` | 52-56 | `NOT LIKE '%IDM%' AND NOT LIKE '%DMK%' AND != 'Gudang Utama'` |
| 7 | `app/Http/Controllers/Feature/ReceiveExternalController.php` | 34-37 | Sama seperti #6 |
| 8 | `app/Http/Controllers/Feature/ReceiveExternalController.php` | 46-49 | Filter history LIKE chain |
| 9 | `app/Http/Controllers/Feature/ReceiveExternalController.php` | 104-106 | Sama |
| 10 | `app/Exports/ReceiveExternalExport.php` | 29-31 | Filter export LIKE chain |

---

## 2. Konsep Baru: Flag `is_jasa_cuci`

### Skema

```sql
ALTER TABLE locations ADD COLUMN is_jasa_cuci BOOLEAN DEFAULT FALSE;
```

### Arti Flag

| Nilai | Arti | Tampil di Modul |
|-------|------|-----------------|
| `true` | Lokasi Jasa Cuci (mitra/vendor eksternal) | Transfer External & Receive External |
| `false` | Lokasi non-jasa-cuci (Gudang Utama, DMK, dll) | Transfer Internal (sebagai exit-point) |

### Konsep Exit-Point

Lokasi **non-jasa-cuci** diperlakukan sebagai **exit-point**: barang yang dikirim ke sana dianggap **keluar permanen** (seperti penjualan), sehingga:
- `TRANSFER_OUT` dibuat di lokasi asal (stok berkurang)
- `TRANSFER_IN` **TIDAK dibuat** di lokasi tujuan (barang tidak di-track di tujuan)
- Kalau di-revert (delete), `TRANSFER_REVERT_IN` juga tidak dibuat (karena memang tidak pernah ada `TRANSFER_IN`)

Lokasi **jasa cuci** diperlakukan normal: barang dipindah dan di-track di kedua sisi (asal & tujuan).

---

## 3. Alur Refactor

### Step A: Tambah kolom `is_jasa_cuci` (pure schema)

**File**: `database/migrations/2026_06_27_000001_add_is_jasa_cuci_to_locations_table.php`

Migration ini **hanya** menambah kolom `is_jasa_cuci` (bool, default `false`) + index untuk performa. **Tidak ada business logic atau auto-update data** — migration adalah pure schema change.

Untuk data existing: flag di-handle oleh **Seeder** (untuk fresh install via `migrate:fresh --seed`) atau **Form UI Edit Lokasi** (untuk perubahan data per-lokasi setelah deploy).

```php
public function up(): void
{
    Schema::table('locations', function (Blueprint $table) {
        // Untuk data existing: flag di-handle oleh seeder (LocationSeeder) atau
        // form UI Edit Lokasi (/admin/locations/{id}/edit). Tidak ada auto-update
        // di migration untuk menjaga migration tetap pure schema change.
        $table->boolean('is_jasa_cuci')->default(false)->after('description');
        $table->index('is_jasa_cuci');
    });
}
```

**Mengapa pure schema?**
- Migration seharusnya fokus ke perubahan struktur database, bukan data manipulation.
- Business logic (siapa yang Jasa Cuci, siapa yang bukan) lebih baik disimpan di seeder atau via UI form.
- Lebih mudah di-maintain: kalau lokasi Jasa Cuci berubah, cukup update seeder atau lewat form, tidak perlu tulis migration baru.

### Step B: Update LocationSeeder

**File**: `database/seeders/LocationSeeder.php`

Setiap entry sekarang punya field `is_jasa_cuci` eksplisit:
- 12 Jasa Cuci → `true`
- Gudang Utama + DMK → `false`

Duplikat "Gudang Utama" di seeder lama juga ikut dihapus (sebelumnya ada 2 row, sekarang hanya 1).

### Step C: Update Model Location

**File**: `app/Models/Location.php`

```php
protected $fillable = ['name', 'description', 'is_jasa_cuci'];

protected $casts = [
    'is_jasa_cuci' => 'boolean',
];
```

### Step D: Refactor 3 modul barang keluar

| Modul | File yang Diubah | Perubahan |
|-------|------------------|-----------|
| Transfer Internal | `app/Http/Controllers/Feature/TransferInternalController.php` | Ganti `Location::where('name', 'DMK')` jadi `Location::where('is_jasa_cuci', false)->where('name', '!=', 'Gudang Utama')`. Ganti `stripos('DMK')` jadi `!$toLocation->is_jasa_cuci`. |
| Transfer Internal (view) | `resources/views/admin/barang-keluar/transfer-step1.blade.php` | Ganti hidden input hardcode jadi `<select>` dropdown (kosong default). JS modal baca dari dropdown + tambah validasi. |
| Transfer Internal (service) | `app/Services/BarangKeluar/BarangKeluarService.php` | Ganti `stripos('DMK')` jadi `!$toLocation->is_jasa_cuci` di `createTransferTransactions()`. |
| Transfer External | `app/Http/Controllers/Feature/TransferExternalController.php` | Ganti chain `NOT LIKE '%IDM%' AND NOT LIKE '%DMK%' AND != 'Gudang Utama'` jadi `where('is_jasa_cuci', true)->where('name', '!=', 'Gudang Utama')`. |
| Receive External | `app/Http/Controllers/Feature/ReceiveExternalController.php` | Sama seperti di atas, di 3 tempat: query utama + filter history + checkExternalStock. |
| Receive External (export) | `app/Exports/ReceiveExternalExport.php` | Filter export pakai flag. |

---

## 4. Hasil Verifikasi (setelah refactor)

### Data di DB `walet` (real, setelah `migrate:fresh --seed`)

| ID | Name | is_jasa_cuci |
|----|------|:---:|
| 1 | Gudang Utama | false |
| 2 | KRIS | true |
| 3 | WIKOM | true |
| 4 | ASIH | true |
| 5 | RONI | true |
| 6 | SUNI | true |
| 7 | RUWI | true |
| 8 | JR | true |
| 9 | ANI SURABAYA | true |
| 10 | CANIAGO | true |
| 11 | MBA SURABAYA | true |
| 12 | BOJONEGORO | true |
| 13 | BABAT | true |
| 14 | DMK | false |

**Total**: 14 rows, 12 Jasa Cuci (true), 2 non-jasa-cuci (Gudang Utama + DMK). Duplikat Gudang Utama sudah dihapus dari seeder.

### Dropdown Behavior

| Modul | Dropdown Tujuan | Sumber |
|-------|----------------|--------|
| **Transfer Internal** | DMK (saat ini) | `Location::where('is_jasa_cuci', false)->where('name', '!=', 'Gudang Utama')` |
| **Transfer External** | KRIS, WIKOM, ASIH, RONI, SUNI, RUWI, JR, ANI SURABAYA, CANIAGO, MBA SURABAYA, BOJONEGORO, BABAT | `Location::where('is_jasa_cuci', true)->where('name', '!=', 'Gudang Utama')` |
| **Receive External** | Sama dengan Transfer External | Sama |
| **Receive Internal** | (tidak di-refactor, tetap pakai LIKE) | Filter LIKE existing |

### Behavior Exit-Point

| Tujuan | TRANSFER_OUT di asal | TRANSFER_IN di tujuan | Saat Delete |
|--------|:---:|:---:|---|
| DMK (non-jasa-cuci) | ✅ dibuat | ❌ **skip** (exit-point) | TRANSFER_REVERT_IN **skip** |
| KRIS (jasa cuci) | ✅ dibuat | ✅ dibuat (di-track) | TRANSFER_REVERT_IN dibuat |

---

## 5. Cara Pakai (Untuk Tim)

### Menambah Lokasi Baru — Lewat UI Form (Recommended)

Admin bisa tambah lokasi langsung dari halaman `/admin/locations/create` dengan radio button pilihan:

| Tipe | Tujuan Penggunaan |
|------|-------------------|
| **Jasa Cuci (Mitra/Vendor Eksternal)** | Lokasi mitra/vendor yang dipakai untuk kirim/terima barang proses. Muncul di Transfer External & Receive External. |
| **Non-Jasa Cuci (Gudang/Exit-Point)** | Gudang sendiri atau lokasi penjualan langsung. Muncul di Transfer Internal sebagai exit-point. |

Form otomatis menampilkan helper text untuk tiap tipe, sehingga admin paham konsekuensi pilihannya.

### Menambah Lokasi Baru — Lewat Tinker/Code (Alternative)

Untuk lokasi **Jasa Cuci** (mitra/vendor):

```php
\App\Models\Location::create([
    'name' => 'NAMA JASA CUCI',
    'description' => null,
    'is_jasa_cuci' => true,
]);
```

Otomatis muncul di dropdown Transfer External & Receive External. Stok yang dikirim ke sini akan di-track normal (ada `TRANSFER_IN` di tujuan).

Untuk lokasi **exit-point** (mis. perluasan DMK):

```php
\App\Models\Location::create([
    'name' => 'LOKASI EXIT',
    'description' => null,
    'is_jasa_cuci' => false,
]);
```

Otomatis muncul di dropdown Transfer Internal. Stok yang dikirim ke sini dianggap keluar permanen (skip `TRANSFER_IN`).

### Edit Lokasi yang Sudah Ada

Lewat UI: buka `/admin/locations`, klik **Edit** pada lokasi yang ingin diubah.

**Penting**: Kalau lokasi sudah pernah dipakai di transaksi, akan muncul **warning amber box** di atas radio button:

> Lokasi ini sudah dipakai di transaksi (stock_transfer, inventory_transaction, atau sale). Mengubah tipe akan mempengaruhi konsistensi data historis.

Radio button **tetap aktif** (tidak didisable). Admin bisa tetap edit, tapi diperingatkan bahwa perubahan akan berdampak ke data historis.

### Jangan Pakai `is_jasa_cuci = false` untuk Lokasi yang Di-track

Skenario A mengasumsikan **semua lokasi non-jasa-cuci = exit-point**. Kalau ada kebutuhan lokasi non-jasa-cuci yang di-track (mis. gudang cabang), **perlu tambah flag kedua** `is_trackable_stock` atau ubah pendekatan ke Skenario B (PR terpisah di masa depan).

---

## 6. Yang TIDAK Diubah (Sesuai Scope)

| File/Area | Alasan |
|-----------|--------|
| `ReceiveInternalController.php` | Menu FE sudah di-comment di `index.blade.php:128-154`. Filter `LIKE '%IDM%' OR LIKE '%DMK%'` tetap dipakai. |
| `DashboardService.php` `getFlowBarangKeDMK()` | Tetap pakai `LIKE '%DMK%'` karena judul chart-nya spesifik ke DMK. PR terpisah kalau mau generalize. |
| View `transfer-step2.blade.php` | Tidak ada (bug existing yang tidak terkait scope refactor). |
| Migration auto-update data | Dihapus (lihat Section 3 Step A) untuk menjaga migration tetap pure schema change. Auto-update pindah ke seeder. |

**Catatan**: `LocationSeeder` awalnya diminta untuk tidak diubah, tapi kemudian diubah supaya setiap entry punya `is_jasa_cuci` eksplisit. Duplikat "Gudang Utama" di seeder juga ikut dihapus supaya data konsisten.

### ⚠️ Perhatian untuk Production Deploy Existing Data

Karena migration sekarang **tidak auto-update data existing** (cuma tambah kolom), kalau deploy ke server production yang sudah punya data lokasi existing dengan `is_jasa_cuci = false` (atau NULL), perlu handle manual:

**Opsi 1: Lewat Form UI** (untuk lokasi yang jumlahnya sedikit)
- Admin login → buka `/admin/locations` → klik Edit tiap lokasi Jasa Cuci → pilih radio "Jasa Cuci" → save

**Opsi 2: Bulk via Tinker** (rekomendasi untuk banyak lokasi)
```php
\App\Models\Location::whereIn('name', ['KRIS','WIKOM','ASIH','RONI','SUNI','RUWI','JR','ANI SURABAYA','CANIAGO','MBA SURABAYA','BOJONEGORO','BABAT'])->update(['is_jasa_cuci' => true]);
```

**Opsi 3: Artisan Command** (untuk repeatable migration, PR terpisah di masa depan)
- Bisa dibuat command `php artisan locations:mark-jasa-cuci` untuk standardisasi.

---

## 7. Testing Manual

### Skenario Test

| # | Aksi | Expected |
|---|------|----------|
| 1 | Buka Transfer Internal | Dropdown "Lokasi Tujuan" muncul, default kosong, berisi DMK |
| 2 | Submit tanpa pilih lokasi | Error "Silakan pilih lokasi tujuan" + scroll ke dropdown |
| 3 | Pilih DMK + submit 100gr | `stock_transfers` (Gudang→DMK), hanya `TRANSFER_OUT` -100gr dibuat (no `TRANSFER_IN`) |
| 4 | Pilih KRIS + submit 100gr | `stock_transfers` (Gudang→KRIS), `TRANSFER_OUT` -100gr + `TRANSFER_IN` +100gr dibuat |
| 5 | Buka Transfer External | Dropdown berisi 12 Jasa Cuci |
| 6 | Buka Receive External | Dropdown berisi 12 Jasa Cuci (sama) |
| 7 | Delete transfer ke DMK | Hanya `TRANSFER_REVERT_OUT` dibuat |
| 8 | Delete transfer ke KRIS | `TRANSFER_REVERT_OUT` + `TRANSFER_REVERT_IN` dibuat |
| 9 | Tambah lokasi baru `is_jasa_cuci=true` via Tinker | Otomatis muncul di Transfer External & Receive External |
| 10 | Tambah lokasi baru `is_jasa_cuci=false` via Tinker | Otomatis muncul di Transfer Internal sebagai exit-point |

### Hasil Test

```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\SortMaterialServiceTest
  ✓ process internal grading success
  ✓ delete child grade does not fail due to parent stock cache
  ✓ delete parent target restores stock to source parent

Tests:    5 passed (17 assertions)
Duration: 0.43s
```

> **Catatan**: Durasi test turun dari ~23.5 detik ke **0.43 detik** setelah migrasi dari `RefreshDatabase` ke `DatabaseTransactions` (lihat section 12).

Syntax check: semua file PHP bersih (no syntax errors).

---

## 8. Rollback Plan

Jika ada masalah setelah deploy:

### Rollback migration

```bash
php artisan migrate:rollback --step=1
```

Ini akan rollback 1 migration terakhir (`is_jasa_cuci`). Data existing di tabel `locations` tidak hilang, hanya kolom `is_jasa_cuci` yang di-drop.

### Rollback kode

Gunakan `git revert` untuk commit terkait. File yang akan berubah:

- `database/migrations/2026_06_27_000001_*` (1 file)
- `database/seeders/LocationSeeder.php`
- `app/Models/Location.php`
- `app/Http/Controllers/Feature/TransferInternalController.php`
- `app/Http/Controllers/Feature/TransferExternalController.php`
- `app/Http/Controllers/Feature/ReceiveExternalController.php`
- `app/Services/BarangKeluar/BarangKeluarService.php`
- `app/Exports/ReceiveExternalExport.php`
- `resources/views/admin/barang-keluar/transfer-step1.blade.php`

---

## 9. File yang Diubah

### Tahap 1: Refactor Inti (9 file)

#### Migration (1 file baru)

- `database/migrations/2026_06_27_000001_add_is_jasa_cuci_to_locations_table.php`

#### Seeder (1 file diubah)

- `database/seeders/LocationSeeder.php` — tambah `is_jasa_cuci` per entry + hapus duplikat Gudang Utama

#### Model (1 file diubah)

- `app/Models/Location.php` — tambah `is_jasa_cuci` di `$fillable` & `$casts` + helper `hasTransactions()`

#### Controller Barang Keluar (3 file diubah)

- `app/Http/Controllers/Feature/TransferInternalController.php` — 2 tempat
- `app/Http/Controllers/Feature/TransferExternalController.php` — 1 tempat
- `app/Http/Controllers/Feature/ReceiveExternalController.php` — 3 tempat

#### Service (1 file diubah)

- `app/Services/BarangKeluar/BarangKeluarService.php` — 1 tempat (`createTransferTransactions`)

#### Export (1 file diubah)

- `app/Exports/ReceiveExternalExport.php` — 1 tempat

#### View Barang Keluar (1 file diubah)

- `resources/views/admin/barang-keluar/transfer-step1.blade.php` — hidden input → dropdown + JS validasi

### Tahap 2: UI Input di CRUD Lokasi (6 file)

#### Request Validation (1 file diubah)

- `app/Http/Requests/Location/LocationRequest.php` — tambah rule `is_jasa_cuci` (required, boolean) + 2 pesan error

#### Controller Master (1 file diubah)

- `app/Http/Controllers/Master/LocationController.php` — `edit()`: kirim `$hasHistory` ke view

#### Service Location (1 file diubah)

- `app/Services/Location/LocationService.php` — `getAll()`: tambah `withExists(['stockTransfersFrom', 'stockTransfersTo', 'inventoryTransactions', 'saleItems'])` agar index bisa cek `hasHistory` per row tanpa N+1 query

#### View Master Lokasi (3 file diubah)

- `resources/views/admin/locations/create.blade.php` — tambah section "Tipe Lokasi" dengan 2 radio button + helper text
- `resources/views/admin/locations/edit.blade.php` — tambah radio button dengan old value + warning amber box kalau `hasHistory=true`
- `resources/views/admin/locations/index.blade.php` — tambah kolom "Tipe" dengan badge (biru=JC, abu-abu=Non-JC); `colspan` dari 5 → 6

**Total**: 1 file baru + 14 file diubah = 15 file secara keseluruhan.

---

## 10. Referensi

- Konsep exit-point: lihat `docs/barang-keluar/barang-keluar-analysis.md` (V-2.x dan seterusnya)
- Sejarah perbaikan stock minus: lihat `docs/barang-keluar/barang-keluar-implementation-plan.md`
- Konsep global budgeting & dropdown: lihat `docs/tracking-stok/`

---

## 11. Tahap 2: Input `is_jasa_cuci` di CRUD Lokasi

Setelah refactor inti selesai, tahap kedua menambahkan **input field `is_jasa_cuci` di form CRUD Lokasi** supaya admin bisa set flag langsung dari UI tanpa harus edit seeder/code.

### Latar Belakang Tambahan

Tahap 1 mengharuskan admin/programmer menambah lokasi baru via Tinker atau edit `LocationSeeder`. Hal ini **membatasi user non-teknis** untuk mengelola data lokasi. Tahap 2 menambahkan input field di form Create & Edit yang sudah ada (`/admin/locations/create` & `/admin/locations/{id}/edit`).

### Perubahan yang Dilakukan

#### A. Validasi Request

**File**: `app/Http/Requests/Location/LocationRequest.php`

Tambah rule:
```php
'is_jasa_cuci' => 'required|boolean',
```

Pesan error:
- `is_jasa_cuci.required` → "Tipe lokasi wajib dipilih."
- `is_jasa_cuci.boolean` → "Tipe lokasi tidak valid."

#### B. Helper Cek History di Model

**File**: `app/Models/Location.php`

Tambah method:
```php
public function hasTransactions(): bool
{
    return $this->stockTransfersFrom()->exists()
        || $this->stockTransfersTo()->exists()
        || $this->inventoryTransactions()->exists()
        || $this->saleItems()->exists();
}
```

Method ini cek 4 relasi: lokasi pernah dipakai sebagai asal/tujuan transfer, atau punya inventory_transaction, atau punya sale_item.

#### C. Controller Kirim `$hasHistory` ke View Edit

**File**: `app/Http/Controllers/Master/LocationController.php`

Method `edit()`:
```php
public function edit(int $id)
{
    $location = $this->locationService->getById($id);
    $hasHistory = $location->hasTransactions();
    return view('admin.locations.edit', compact('location', 'hasHistory'));
}
```

#### D. Form Create dengan Radio Button

**File**: `resources/views/admin/locations/create.blade.php`

Tambah section "Tipe Lokasi" dengan 2 radio button:

| Opsi | Value | Default | Deskripsi |
|------|-------|---------|-----------|
| Jasa Cuci (Mitra/Vendor Eksternal) | `1` | - | Muncul di Transfer External & Receive External. Stok di-track normal. |
| Non-Jasa Cuci (Gudang/Exit-Point) | `0` | ✅ default | Muncul di Transfer Internal. Stok dianggap keluar permanen (skip TRANSFER_IN). |

Setiap opsi punya helper text yang menjelaskan konsekuensi pilihan.

#### E. Form Edit dengan Radio + Warning Info

**File**: `resources/views/admin/locations/edit.blade.php`

Radio button dengan old value dari `$location->is_jasa_cuci`. Kalau `$hasHistory=true`, tampilkan **warning amber box**:

> Lokasi ini sudah dipakai di transaksi (stock_transfer, inventory_transaction, atau sale). Mengubah tipe akan mempengaruhi konsistensi data historis.

**Radio TIDAK didisable** — admin bisa tetap edit (sesuai keputusan produk: "Unlock semua seed data").

#### F. Tabel Index dengan Badge Tipe + Popup Konfirmasi Saat Klik Edit

**File**: `resources/views/admin/locations/index.blade.php`

1. **Tambah kolom "Tipe" dengan 2 badge**:

| Tipe | Badge |
|------|-------|
| `is_jasa_cuci=true` | `<span class="bg-blue-100 text-blue-800">Jasa Cuci</span>` |
| `is_jasa_cuci=false` | `<span class="bg-gray-100 text-gray-700">Non-Jasa Cuci</span>` |

Update `colspan` empty state dari 5 → 6.

2. **Popup konfirmasi saat klik Edit untuk lokasi ber-history**:

Untuk meningkatkan *notice* supaya user aware **sebelum masuk halaman edit**, tombol Edit memicu popup modal konfirmasi (bukan langsung navigate) jika lokasi punya history transaksi.

**Cara kerja**:
- `LocationService::getAll()` tambah `withExists(['stockTransfersFrom', 'stockTransfersTo', 'inventoryTransactions', 'saleItems'])` agar lokasi di-annotate dengan `*_exists` boolean (efficient: 4 subquery EXISTS dalam 1 query utama — tidak ada N+1).
- `index.blade.php` set `data-has-history="1"` atau `"0"` di `<a>` tombol Edit.
- Saat di-klik, JavaScript `handleEditClick(event, el)`:
  - Jika `hasHistory=1` → `event.preventDefault()` + tampilkan modal `#editWarningModal` dengan pesan warning. Modal berisi 2 tombol: **Batal** (tutup modal) atau **Lanjutkan Edit** (navigate ke URL).
  - Jika `hasHistory=0` → default behavior (langsung navigate ke halaman edit, tanpa popup).

**Isi popup**:
- Icon warning amber
- Nama lokasi yang akan diedit
- Pesan: "Lokasi [NAMA] sudah pernah dipakai di transaksi (stock_transfer, inventory_transaction, atau sale_item)."
- Highlight box kuning: "Mengubah tipe lokasi (Jasa Cuci ↔ Non-Jasa Cuci) akan mempengaruhi konsistensi data historis dan reporting."
- 2 tombol: **Batal** (default, secondary) + **Lanjutkan Edit** (amber)

**Extra**:
- Modal bisa ditutup dengan klik di luar area atau tekan Escape.
- `body` di-lock scroll saat modal terbuka (`overflow-hidden`).

### Catatan UX (Notice Behavior)

Ada **2 layer notice** untuk lokasi ber-history:

| Lokasi | Layer 1 (Index/Tombol Edit) | Layer 2 (Halaman Edit) |
|--------|-----------------------------|------------------------|
| **Tanpa history** | Klik Edit → langsung masuk halaman edit | Tidak ada warning box |
| **Dengan history** | Klik Edit → muncul popup konfirmasi (wajib klik "Lanjutkan Edit" untuk masuk) | Warning amber box di atas radio button |

User **wajib aware** sebelum masuk halaman edit (popup wajib di-confirm) dan **wajib aware** saat di halaman edit (warning box tetap muncul). Tujuannya supaya admin tidak langsung klik Edit tanpa sadar risikonya.

### Hasil Verifikasi

```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\SortMaterialServiceTest
  ✓ process internal grading success
  ✓ delete child grade does not fail due to parent stock cache
  ✓ delete parent target restores stock to source parent

Tests:    5 passed (17 assertions)
```

Syntax check: semua file PHP bersih (no syntax errors).

### Trade-off & Catatan

1. **Radio tetap aktif walaupun `hasHistory=true`**: diputuskan supaya admin bisa edit semua data termasuk dari seeder. Konsekuensi: perubahan flag bisa mempengaruhi konsistensi data historis. Admin tanggung jawab sendiri.
2. **Default Non-Jasa Cuci (`false`)**: lebih aman karena exit-point default. Kalau admin tidak sengaja submit tanpa pilih, lokasi akan jadi exit-point (tidak masuk dropdown Transfer External).
3. **DB `walet` setelah `migrate:fresh --seed` belum ada transaksi**: semua `hasTransactions()=false`, jadi warning amber belum muncul saat testing awal. Warning baru muncul setelah user pakai sistem jual/transfer.
4. **Verifikasi warning muncul**: test dengan insert dummy transaction via Tinker:
   ```php
   \App\Models\InventoryTransaction::create([
       'transaction_date' => now(),
       'grade_company_id' => 1,
       'location_id' => 14,  // DMK
       'quantity_change_grams' => 100,
       'transaction_type' => 'GRADING_IN',
   ]);
   ```
   Lalu buka `/admin/locations/14/edit` — warning amber akan muncul.

### Testing Manual

| # | Aksi | Expected |
|---|------|----------|
| 1 | Buka `/admin/locations` | Tabel ada kolom "Tipe" dengan badge sesuai flag |
| 2 | Buka `/admin/locations/create` | Form ada 2 radio button dengan helper text, default Non-Jasa Cuci |
| 3 | Isi nama "TEST_JC", pilih "Jasa Cuci", submit | Tersimpan dengan `is_jasa_cuci=true`, badge biru |
| 4 | Buka dropdown Transfer External | "TEST_JC" muncul |
| 5 | Buka dropdown Transfer Internal | "TEST_JC" TIDAK muncul |
| 6 | Edit DMK (id=14, dari seeder, belum ada history) | Radio enabled, tidak ada warning |
| 7 | Ganti DMK ke Jasa Cuci, submit | Berhasil tersimpan, badge berubah |
| 8 | (Optional) Insert dummy transaction ke DMK via Tinker, lalu buka edit DMK | Warning amber muncul |

### Rollback Tahap 2

Tidak perlu rollback migration (tidak ada migration baru). Cukup `git revert` 6 file:

- `app/Http/Requests/Location/LocationRequest.php`
- `app/Models/Location.php` (helper `hasTransactions()`)
- `app/Http/Controllers/Master/LocationController.php`
- `resources/views/admin/locations/create.blade.php`
- `resources/views/admin/locations/edit.blade.php`
- `resources/views/admin/locations/index.blade.php`

Setelah revert, form Create/Edit lokasi hanya punya field `name` & `description` seperti sebelumnya. Admin tidak bisa set flag lewat UI — harus via Tinker atau edit seeder.

---

## 12. Fix: DB Aplikasi Ke-reset Setiap Test

### Masalah

Setiap kali `php artisan test` dijalankan, **DB `walet` (DB aplikasi) ter-reset total** — semua data yang admin input hilang. Ini menyebabkan pengalaman development yang buruk dan rawan kehilangan data.

### Penyebab

`SortMaterialServiceTest` pakai trait `RefreshDatabase` yang truncate semua tabel + migrate ulang setiap test jalan. Setting DB untuk testing di `phpunit.xml` di-comment, sehingga test jalan dengan DB `walet` (mysql) yang sama dengan DB aplikasi.

### Solusi

Ganti `RefreshDatabase` → `DatabaseTransactions` di `tests/Feature/SortMaterialServiceTest.php`.

**Mengapa `DatabaseTransactions` cocok**:
- Test `SortMaterialServiceTest` **self-contained** — setiap test buat data sendiri via factory (`User::factory()`) atau `create()`. Tidak bergantung pada data existing.
- `DatabaseTransactions` wrap semua test dalam 1 DB transaction. Setelah test selesai, transaction di-rollback → data test hilang, data existing **AMAN**.
- Lebih cepat: tidak perlu migrate ulang (durasi test dari ~23.5 detik → **0.43 detik**).

### File yang Diubah

| File | Perubahan |
|------|-----------|
| `tests/Feature/SortMaterialServiceTest.php` | Import: `RefreshDatabase` → `DatabaseTransactions`. Trait: `use RefreshDatabase;` → `use DatabaseTransactions;`. |

### Verifikasi DB Aman dari Reset

Skenario pengujian:
1. `php artisan db:seed --class=LocationSeeder` → DB `walet` punya 14 locations
2. Cek count sebelum test: `Locations = 14, DMK = 1, KRIS = 1`
3. Jalankan `php artisan test` → 5/5 pass
4. Cek count sesudah test: `Locations = 14, DMK = 1, KRIS = 1` ✅

Counts sebelum dan sesudah test **sama persis** — DB aplikasi tidak ter-reset.

### Trade-off

- `DatabaseTransactions` wrap semua operasi test dalam 1 transaction. Beberapa skenario yang **TIDAK kompatibel**:
  - Test yang melakukan koneksi DB paralel (mis. multi-process)
  - Test yang trigger migration atau truncate manual di tengah test
- `SortMaterialServiceTest` saat ini aman karena tidak melakukan hal tersebut. Tapi **untuk test baru** yang mungkin trigger migration di tengah, perlu case-by-case handling.

### Alternatif Lain (Tidak Dipilih)

| Solusi | Alasan Tidak Dipilih |
|--------|---------------------|
| Uncomment sqlite di `phpunit.xml` | `pdo_sqlite` extension tidak tersedia di environment ini |
| Hapus `RefreshDatabase` sepenuhnya | Test tidak isolated (data test numpuk di DB aplikasi) |
| Setup DB `walet_test` terpisah | Setup extra, butuh provisioning DB baru di MySQL server |

---

*Document created: 2026-06-27*
*Last updated: 2026-06-27 (Tahap 2 + popup konfirmasi + fix DB reset + migration pure schema)*
*Project: gudang-walet-laravel-10*
*Author: AI Assistant (refactor session)*
