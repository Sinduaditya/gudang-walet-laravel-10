# Desain: Normalisasi IDM — `grade_company_id` di `idm_details`

**Tanggal**: 2026-06-28
**Status**: Proposal — belum diimplementasi

---

## Masalah Saat Ini

`IdmDetail` hanya punya `grade_idm_name` (string: "perutan"/"kakian"/"idm") + `weight`. Tidak ada link ke `grade_company`.

Akibatnya di `TransferIdmService::storeTransfer()`, **semua** detail pakai grade company yang sama — yaitu `idm_management.grade_company_id` (grade sumber, misal "IDM A W2"):

```php
// Semua detail (perutan, kakian, IDM) pakai grade company yang sama
InventoryTransaction::create([
    'grade_company_id' => $detailModel->idmManagement->grade_company_id, // ← salah untuk IDM output
    'transaction_type' => 'IDM_TRANSFER_OUT',
    ...
]);
```

Ini tidak tepat karena:

| Tipe Detail | Seharusnya |
|---|---|
| `perutan` | Byproduct/limbah — tidak perlu grade company, tidak masuk inventory |
| `kakian` | Byproduct/limbah — tidak perlu grade company, tidak masuk inventory |
| `idm` | Output bernilai — harus masuk stok grade IDM yang spesifik |

---

## Jawaban: Tidak Perlu `is_idm` di `grade_company`

Flag `is_idm` di `GradeCompany` redundan karena:
- IDM grades sudah bisa diidentifikasi via `GradeCompany::whereHas('parent', fn($q) => $q->where('name', 'IDM'))`
- Menambah flag baru padahal parent relationship sudah cukup = overengineering

**Yang dibutuhkan:** kolom `grade_company_id` (nullable) di tabel `idm_details`.

---

## Desain Baru

### Schema

```sql
ALTER TABLE idm_details
    ADD COLUMN grade_company_id BIGINT UNSIGNED NULL AFTER grade_idm_name,
    ADD FOREIGN KEY (grade_company_id) REFERENCES grade_companies(id);
```

Migration: `database/migrations/YYYY_MM_DD_add_grade_company_id_to_idm_details.php`

### Semantik per Baris `idm_details`

| `grade_idm_name` | `grade_company_id` | Masuk `inventory_transactions`? |
|---|---|---|
| `perutan` | `NULL` | Tidak — byproduct |
| `kakian` | `NULL` | Tidak — byproduct |
| `idm` | ID grade IDM output yang dipilih user | **Ya** — via `IDM_TRANSFER_OUT` |

### Alur Lengkap (Setelah Normalisasi)

```
Grading
  → GRADING_IN "IDM A W2" +1000g  (inventory_transactions)

ManajemenIDM (grading ulang)
  → perutan  = 200g, grade_company_id = NULL
  → kakian   = 300g, grade_company_id = NULL
  → idm      = 400g, grade_company_id = [ID "IDM Bersih" dipilih user]
  → susut    = 100g (dihitung otomatis)

Transfer IDM (row "idm", 400g)
  → IDM_TRANSFER_OUT -400g, grade_company_id = "IDM Bersih"
     (bukan "IDM A W2" seperti sebelumnya)
```

---

## Dropdown UI — Filter Grade IDM Output

Di form ManajemenIDM (step2 & edit), baris IDM ditambah dropdown `grade_company_id`. Difilter hanya grade dengan parent IDM:

```php
// Controller — kirim ke view
$idmOutputGrades = GradeCompany::whereHas('parent', fn($q) => $q->where('name', 'IDM'))->get();
```

```blade
{{-- View step2.blade.php — baris IDM --}}
<select name="details[idm][grade_company_id]" required>
    <option value="">Pilih Grade IDM Output...</option>
    @foreach ($idmOutputGrades as $g)
        <option value="{{ $g->id }}">{{ $g->name }}</option>
    @endforeach
</select>
```

---

## Perubahan `storeTransfer()` — Skip Byproduct

```php
// Sesudah normalisasi
$detailModel = IdmDetail::with(['idmManagement.sourceItems', 'gradeCompany'])->find($item['id']);

$gradeCompanyId = $detailModel->grade_company_id;  // grade dari detail (hanya "idm" yang terisi)

// Skip inventory transaction jika tidak ada grade (perutan/kakian)
if (!$gradeCompanyId) {
    continue;
}

InventoryTransaction::create([
    'grade_company_id'      => $gradeCompanyId,
    'quantity_change_grams' => -($deductionWeight),
    'transaction_type'      => 'IDM_TRANSFER_OUT',
    ...
]);
```

---

## Files yang Perlu Diubah

| # | File | Perubahan |
|---|------|-----------|
| 1 | `database/migrations/*_add_grade_company_id_to_idm_details.php` | Tambah kolom nullable + FK |
| 2 | `app/Models/IdmDetail.php` | Tambah `grade_company_id` di `$fillable` + relasi `gradeCompany()` |
| 3 | `app/Services/Idm/ManajemenIdmService.php` | `create()` & `update()`: simpan `grade_company_id` untuk row "idm" |
| 4 | `app/Services/Idm/TransferIdmService.php` | `storeTransfer()`: pakai `idm_detail.grade_company_id`, skip tx jika null |
| 5 | `app/Http/Controllers/Feature/ManajemenIdmController.php` | Kirim `$idmOutputGrades` ke view |
| 6 | `resources/views/admin/manajemen-idm/step2.blade.php` | Tambah dropdown grade untuk baris IDM |
| 7 | `resources/views/admin/manajemen-idm/edit.blade.php` | Sinkron dengan step2 |

---

## Ringkasan Keputusan

| Pertanyaan | Jawaban |
|---|---|
| Perlu `is_idm` di `grade_company`? | **Tidak** — gunakan relasi parent |
| Cara identifikasi grade IDM? | `whereHas('parent', fn($q) => $q->where('name', 'IDM'))` |
| "Berat IDM" masuk `inventory_transactions`? | **Ya** — via `IDM_TRANSFER_OUT` saat Transfer IDM |
| "Berat Perutan/Kakian" masuk inventory? | **Tidak** — byproduct, `grade_company_id = null`, di-skip |
| Kapan inventory terdampak? | Saat Transfer IDM, bukan saat simpan ManajemenIDM |

---

*Dokumen ini adalah proposal desain untuk normalisasi tabel IDM.*
*Implementasi dilakukan setelah konfirmasi dengan client.*
