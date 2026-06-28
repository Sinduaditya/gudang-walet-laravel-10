# Changelog: Refactor Transfer IDM & Manajemen IDM

**Commit:** `cbabdf7` — Refactor transfer IDM views and logic
**Branch:** `fix/edit-grade-outgoing-type`
**Tanggal:** 2026-06-27

Dokumen ini merangkum seluruh perubahan yang dilakukan pada modul **Manajemen IDM**, **Transfer IDM**, dan **Grading Goods** beserta dampaknya terhadap aplikasi.

---

## Ringkasan Cepat

| Area | Perubahan Utama |
|---|---|
| **Database** | Hapus semua kolom harga (`price`) dari tabel-tabel IDM |
| **Manajemen IDM** | Hapus input harga, tambah validasi berat, refactor besar di service |
| **Transfer IDM** | Dukung *partial transfer*, tampilkan sisa berat, fix duplicate code |
| **Grading Goods** | Tambah *searchable dropdown* (Tom Select) untuk Grade Perusahaan |
| **Layout** | Tambah `@stack('styles')` agar CSS bisa di-inject per halaman |
| **Dokumentasi** | Tutorial baru: `TUTORIAL_DATA_MASTER.md` & `TUTORIAL_IDM.md` |

---

## 1. Database

### Migration Baru: `2026_06_26_000001_drop_price_columns_from_idm_tables.php`

Menghapus kolom harga yang tidak lagi digunakan dari 4 tabel IDM:

| Tabel | Kolom Dihapus |
|---|---|
| `idm_managements` | `price`, `total_price` |
| `idm_details` | `price` |
| `idm_transfers` | `total_price` |
| `idm_transfer_details` | `price` |

**Alasan:** Bisnis IDM hanya melacak berat & jenis bagian (perutan/kakian/IDM). Harga tidak relevan di modul ini — harga hanya dicatat saat penjualan akhir.

**Rollback aman:** Migration `down()` mengembalikan semua kolom dengan default 0.

---

## 2. Manajemen IDM

### 2.1 Model — Pembersihan `$fillable`

- `app/Models/IdmManagement.php` — hapus `price`, `total_price`
- `app/Models/IdmDetail.php` — hapus `price`
- `app/Models/IdmTransfer.php` — hapus `total_price`
- `app/Models/IdmTransferDetail.php` — hapus `price`

### 2.2 Service — `ManajemenIdmService.php`

**Sebelum:** logika campur antara berat & harga, parameter tidak konsisten.

**Sesudah:**
- Method `create()` dan `update()` hanya menerima `details` (perutan/kakian/idm) dengan field `weight` saja
- **Validasi server-side baru:** jika total `perutan + kakian + idm > initial_weight` → throw exception
  ```php
  if ($shrinkage < 0) {
      throw new \Exception('Total berat (perutan + kakian + IDM) melebihi berat awal...');
  }
  ```
- Susut (shrinkage) dihitung otomatis = `initial_weight - (perutan + kakian + idm)`

### 2.3 Controller — `ManajemenIdmController.php`

- Hapus seluruh logika harga dari `store`, `update`, `step2`
- Validasi `details.*.weight` jadi `required|numeric|min:0`
- Pertahankan query params (`page`, `supplier_id`, `grade_company_id`, `category_grade`) saat redirect agar filter index tidak hilang setelah edit/update

### 2.4 Views

- **`step2.blade.php`** — refactor besar, hapus semua input harga, tambah live calculation untuk susut & disable tombol simpan jika berat tidak valid
- **`edit.blade.php`** — sinkron dengan step2: tambah live validation, error indicator, dan disable submit saat melebihi berat awal
- **`show.blade.php`** — hapus tampilan harga, tata ulang layout agar fokus ke berat
- **`index.blade.php`** — kolom harga hilang, tampilan lebih ringkas

---

## 3. Transfer IDM

### 3.1 Service — `TransferIdmService.php`

#### Dukungan Partial Transfer
Tambah private method `transferredWeightSubquery()` untuk menghitung berat yang sudah ditransfer per item IDM (exclude soft-deleted):

```php
private function transferredWeightSubquery(): string {
    return '(SELECT COALESCE(SUM(itd.weight), 0) FROM idm_transfer_details itd
             INNER JOIN idm_transfers it ON it.id = itd.idm_transfer_id
                AND it.deleted_at IS NULL
             WHERE itd.idm_detail_id = idm_details.id AND itd.deleted_at IS NULL)';
}
```

`getAvailableIdmDetails()` sekarang:
- Menampilkan item dengan `remaining_weight = weight − transferred_weight`
- Filter `weight > transferred_weight` (item baru hilang dari daftar saat **fully consumed**, bukan saat sudah ditransfer sebagian)

#### Fix Duplicate Transfer Code
`generateTransferCode()` sekarang menggunakan `withTrashed()` dalam pengecekan keunikan:
```php
while (IdmTransfer::withTrashed()->where('transfer_code', $code)->exists()) {
```
**Alasan:** Soft-deleted record tetap menempati unique key di database. Sebelumnya, hapus transfer lalu buat lagi di hari yang sama → error duplicate.

#### Inventory Transaction
- Saat transfer dibuat → buat `IDM_TRANSFER_OUT` dengan deduksi proporsional (`weight × initial_weight / total_output`) agar shrinkage ikut terdistribusi
- Saat transfer dihapus → buat `IDM_TRANSFER_REVERT` (audit trail), bukan hard delete

### 3.2 Controller — `TransferIdmController.php`

- Tambah `use Illuminate\Support\Facades\DB;` (fix `Class "DB" not found` saat hapus transfer)
- `step2()` sekarang load `remaining_weight` via subquery
- `store()` validasi berat per item: tidak boleh > sisa berat tersedia
  ```php
  if ($submitted['weight'] > $remaining + 0.001) {
      return redirect()->back()->with('error', 'Berat melebihi sisa tersedia');
  }
  ```
- `destroy()` reversi otomatis via `IDM_TRANSFER_REVERT` + soft delete

### 3.3 Views

| File | Perubahan |
|---|---|
| `create-step-1.blade.php` | Tampilkan **Sisa: X gr** (bukan berat awal) + total asli kalau pernah transfer sebagian |
| `create-step-2.blade.php` | Input berat editable per item, default = sisa berat, max = sisa berat. Hapus footer "Total Berat" |
| `index.blade.php` | Tambah kolom **Nama Barang** (badge per jenis IDM) & **Total Berat** |
| `show.blade.php` | Hapus tampilan harga |

---

## 4. Grading Goods — Searchable Dropdown

### 4.1 Partial — `partials/grade-row.blade.php`

Ganti `<input type="text" list="...">` (datalist) dengan `<select class="grade-company-select">`:

```html
<select name="grades[{{ $index }}][grade_company_name]" required
    class="grade-company-select w-full ...">
    <option value="">Cari atau pilih grade...</option>
    @foreach($allGradeCompanies as $gc)
        <option value="{{ $gc->name }}">{{ $gc->name }}</option>
    @endforeach
</select>
```

### 4.2 Step2 — `step2.blade.php`

Integrasi **Tom Select v2.3.1** (via CDN) untuk searchable dropdown:

```javascript
function initGradeCompanySelect(el) {
    new TomSelect(el, {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        // ...
    });
}
```

- Init otomatis untuk semua `.grade-company-select` saat DOMContentLoaded
- Saat user klik **Tambah Grade** → row baru juga di-init Tom Select
- Data grade dipassing via `gradeCompaniesData = @json(...)` agar JS punya akses

**Manfaat:** User bisa ketik untuk cari grade dari daftar puluhan/ratusan opsi — jauh lebih cepat daripada scroll dropdown biasa.

---

## 5. Layout — `app.blade.php`

Tambah satu baris:
```blade
@stack('styles')
```
sebelum `</head>`. Memungkinkan halaman individual inject CSS via `@push('styles')` — diperlukan untuk Tom Select CDN CSS.

---

## 6. Dokumentasi Baru

### 6.1 `docs/TUTORIAL_DATA_MASTER.md`
Tutorial lengkap penggunaan menu Data Master: Supplier, Grade Perusahaan (Parent & Child), Grade Supplier, Lokasi, User. Termasuk best practice penamaan dan batasan sistem.

### 6.2 `docs/TUTORIAL_IDM.md`
Tutorial alur lengkap IDM dari grading hingga transfer keluar:
- **Bagian 1** — Penandaan IDM saat grading (Kategori IDM A/B vs Jenis Barang Keluar)
- **Bagian 2** — Manajemen IDM: pemecahan jadi Perutan/Kakian/IDM + Susut
- **Bagian 3** — Transfer IDM: pengeluaran termasuk dukungan partial transfer
- **Ringkasan Batasan Sistem** dan contoh alur end-to-end

---

## 7. Update Export — `app/Exports/TransferIdmExport.php`

Header & mapping disinkronkan dengan struktur baru (tanpa kolom harga).

---

## Dampak Bagi User

### Sebelum Refactor
- Input harga di setiap form IDM (membingungkan, tidak dipakai)
- Item IDM hilang dari Transfer setelah satu kali transfer (meskipun masih ada sisa)
- Error "Duplicate entry" saat buat ulang transfer di tanggal yang sama
- Dropdown grade perusahaan harus scroll manual
- Bisa simpan berat IDM yang melebihi berat awal → data jadi tidak konsisten

### Setelah Refactor
- Form IDM bersih, fokus ke berat & jenis bagian
- Partial transfer didukung penuh — sisa berat ter-track akurat
- Tidak ada lagi error duplicate code
- Searchable dropdown — ketik untuk cari grade
- Validasi double layer (client + server) mencegah berat berlebih

---

## Verifikasi yang Disarankan

1. **Migration**: jalankan `php artisan migrate` di staging → cek kolom price hilang dari 4 tabel
2. **Manajemen IDM**: buat batch dengan berat melebihi → harus muncul error
3. **Transfer IDM partial**:
   - Buat transfer 30g dari perutan 1000g
   - Kembali ke Tambah Data → item harus tetap muncul dengan sisa 970g
4. **Duplicate code fix**:
   - Buat transfer → hapus → buat lagi tanggal sama → harus berhasil dengan counter
5. **Grading dropdown**: buka halaman step2 grading → ketik nama grade di dropdown → harus auto-filter

---

## Statistik Perubahan

```
23 files changed
1,230 insertions(+)
1,122 deletions(-)
```

Net penambahan baris kecil (+108) tapi cakupan refactor luas — banyak penyederhanaan logika sambil menambah fitur baru.
