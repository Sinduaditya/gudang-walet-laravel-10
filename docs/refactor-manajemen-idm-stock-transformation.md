# Refactor: ManajemenIDM sebagai Stock Transformation + Drop Transfer IDM

**Tanggal**: 2026-06-29
**Status**: Approved design — siap implementasi
**Branch target**: `fix/manajemen-idm-normalisasi` (TBD)
**Supersedes**: `docs/desain-normalisasi-idm-grade.md` (proposal lama, simpler)

---

## Context

Setelah klarifikasi dengan client + diskusi internal:

- Deploy fresh di **shared hosting**, data mulai dari nol → tidak perlu data migration/backfill
- Struktur grade yang benar:
  - `ParentGradeCompany "IDM"` → child `GradeCompany "IDM A"`, `"IDM B"` (input grading)
  - Seeder lama yang taruh `IDM A`/`IDM B` sebagai parent → harus dikoreksi
- `Perutan`, `Kakian`, `Alu/Afkir` = `grade_company` biasa **tanpa parent** (output bin)
- Output IDM perlu bisa keluar via **semua** modul barang-keluar — Transfer Internal, Transfer External, Receive External — bukan cuma Transfer IDM
- Susut: hanya IDM (berat + grade) wajib; sisanya opsional; `susut = initial - sum(outputs)`

**Keputusan utama**: Drop modul Transfer IDM sepenuhnya. Treat `ManajemenIDM` sebagai **stock transformation** — saat disimpan, output langsung jadi stok normal di `inventory_transactions`. Setelah itu, output bisa keluar via flow standar manapun.

---

## Arsitektur Baru

### Flow End-to-End

```
Grading → SortingResult (kategori IDM A) → GRADING_IN +1000g [IDM A]
    ↓
ManajemenIDM (regrading):
    Form: IDM=700g + grade dropdown, KAKIAN=100g, PERUTAN=150g, ALU=50g
    Susut auto = 1000 - 1000 = 0g
    ↓
Saat save:
    1× IDM_REGRADING_OUT  -1000g  [IDM A]                ← input deduksi
    1× IDM_REGRADING_IN   +700g   [IDM A atau IDM B]     ← user pick
    1× IDM_REGRADING_IN   +100g   [KAKIAN]               ← auto by name
    1× IDM_REGRADING_IN   +150g   [PERUTAN]              ← auto by name
    1× IDM_REGRADING_IN   +50g    [ALU/AFKIR]            ← auto by name
    ↓
Stok output muncul di tracking-stock sebagai grade_company normal.
    ↓
User pilih Transfer Internal (DMK), Transfer External (jasa cuci), atau Receive External.
    Tidak ada flow khusus IDM — pakai flow yang sudah ada.
```

---

## Naming Convention

**Semua identifier grade UPPERCASE**:
- Enum values di `idm_details.grade_idm_name`: `IDM`, `KAKIAN`, `PERUTAN`, `ALU`
- Form field names: `details[IDM][weight]`, `details[KAKIAN][weight]`, dst.
- PHP array keys di service
- Lookup name di seeder & service: `'PERUTAN'`, `'KAKIAN'`, `'ALU/AFKIR'`
- Display label di view: `IDM`, `KAKIAN`, `PERUTAN`, `ALU` (atau `Berat IDM`, `Berat Kakian` kalau mau title case di label — tapi value tetap uppercase)

---

## Schema Changes

### 1. `idm_details` — Tambah `grade_company_id` + perluas ENUM

```php
// Migration: add FK
Schema::table('idm_details', function (Blueprint $table) {
    $table->foreignId('grade_company_id')->nullable()->after('grade_idm_name')
        ->constrained('grades_company')->nullOnDelete();
});

// Migration: perluas enum + uppercase (breaking change tapi data zero)
DB::statement("ALTER TABLE idm_details
    MODIFY COLUMN grade_idm_name ENUM('IDM', 'KAKIAN', 'PERUTAN', 'ALU') NOT NULL");
```

| `grade_idm_name` (UPPERCASE) | `grade_company_id` |
|---|---|
| `IDM` | **wajib** — dari dropdown user (filter parent.name='IDM') |
| `KAKIAN` | nullable jika `weight = 0`, wajib jika `weight > 0` — auto by name |
| `PERUTAN` | sama |
| `ALU` | sama (bin baru) |

### 2. Tambah Row `ALU` di `idm_details`

Service saat ini hanya generate 3 row (PERUTAN/KAKIAN/IDM, lowercase). Tambah 1 row baru `ALU` + uppercase semua.

### 3. Drop Tabel Transfer IDM

```php
Schema::dropIfExists('idm_transfer_details');
Schema::dropIfExists('idm_transfers');
```

Aman karena data zero.

### 4. Restruktur Seeder

**`ParentGradeCompanySeeder.php`**:
```php
$data = [
    ['name' => 'LEMPENG'],
    ['name' => 'MANGKOK'],
    ['name' => 'IDM'],
];
```
Parent "IDM" tetap ada untuk mengorganisir kategori input (IDM A, IDM B).

**`GradeCompanySeeder.php`** — tambah:
- `IDM`, `IDM A`, `IDM B` dengan `parent_grade_company_id` = ID parent "IDM"
  - `IDM` = output grade untuk ManajemenIDM regrading
  - `IDM A` = kategori input saat grading
  - `IDM B` = kategori input saat grading
- `PERUTAN`, `KAKIAN`, `ALU/AFKIR` dengan `parent_grade_company_id = NULL` (standalone byproducts)

Grade existing yang nama-nya mengandung `(IDM A)`/`(IDM B)` → opsional link parent. Tidak required untuk fungsi regrading.

---

## Transaction Types Baru

| Type | Sign | Trigger |
|---|---|---|
| `IDM_REGRADING_OUT` | negatif | Save ManajemenIDM — deduksi input grade |
| `IDM_REGRADING_IN` | positif | Save ManajemenIDM — tambah output grade |
| `IDM_REGRADING_REVERT_OUT` | positif | Delete/update ManajemenIDM — balikkan input |
| `IDM_REGRADING_REVERT_IN` | negatif | Delete/update ManajemenIDM — balikkan output |

Tambah ke `CLAUDE.md` (Domain Model section, list transaction types).

---

## Form Mapping Output Bin → grade_company

| Bin (key) | UI | Resolve grade_company_id |
|---|---|---|
| **IDM** | Berat saja, no dropdown | `GradeCompany::where('name', 'IDM')->first()` → langsung ke parent "IDM" |
| **PERUTAN** | Berat saja, label fixed | `GradeCompany::where('name', 'PERUTAN')->firstOrFail()` |
| **KAKIAN** | Berat saja | `where('name', 'KAKIAN')->firstOrFail()` |
| **ALU** | Berat saja | `where('name', 'ALU/AFKIR')->firstOrFail()` (slash retained di grade name) |

**Catatan Implementasi:**
- IDM output **TIDAK** dipilih user — otomatis ke grade parent "IDM" (standalone)
- Form hanya tampil field berat IDM saja + helper text "Output langsung ke grade parent IDM"
- Input grade (IDM A/B) tersimpan di `IdmManagement.grade_company_id` untuk tracking asal
- Output grade (semua bin) tersimpan di `IdmDetail.grade_company_id` untuk tracking inventory

Form field names uppercase: `details[IDM][weight]`, `details[KAKIAN][weight]`, etc.

Kalau grade by-name tidak ditemukan saat save → throw `Exception('Seed data missing, jalankan db:seed')`.

---

## Service Changes

### `ManajemenIdmService::create()` — Refactor

```php
public function create(array $itemIds, array $data): IdmManagement
{
    return DB::transaction(function () use ($itemIds, $data) {
        $items = SortingResult::whereIn('id', $itemIds)->get();
        $initialWeight = $items->sum('weight_grams');

        // Lookup ALL output grades by name (uppercase)
        // IDM = parent-level grade, bukan dari input dropdown
        $idmGc     = GradeCompany::where('name', 'IDM')->first();
        $perutanGc = GradeCompany::where('name', 'PERUTAN')->first();
        $kakianGc  = GradeCompany::where('name', 'KAKIAN')->first();
        $aluGc     = GradeCompany::where('name', 'ALU/AFKIR')->first();

        $outputs = [
            'IDM' => [
                'weight'           => (float)($data['details']['IDM']['weight'] ?? 0),
                'grade_company_id' => $idmGc?->id,  // Langsung ke parent "IDM", bukan dari form
            ],
            'KAKIAN'  => ['weight' => (float)($data['details']['KAKIAN']['weight'] ?? 0),  'grade_company_id' => $kakianGc?->id],
            'PERUTAN' => ['weight' => (float)($data['details']['PERUTAN']['weight'] ?? 0), 'grade_company_id' => $perutanGc?->id],
            'ALU'     => ['weight' => (float)($data['details']['ALU']['weight'] ?? 0),     'grade_company_id' => $aluGc?->id],
        ];

        // Validasi IDM wajib
        if ($outputs['IDM']['weight'] <= 0 || !$outputs['IDM']['grade_company_id']) {
            throw new \Exception('Berat IDM wajib diisi. Grade untuk IDM tidak ditemukan di database. Jalankan db:seed.');
        }
        // Validasi byproduct: kalau berat > 0, grade harus ada
        foreach (['KAKIAN', 'PERUTAN', 'ALU'] as $k) {
            if ($outputs[$k]['weight'] > 0 && !$outputs[$k]['grade_company_id']) {
                throw new \Exception("Grade untuk {$k} tidak ditemukan. Jalankan db:seed.");
            }
        }

        $totalOutput = array_sum(array_column($outputs, 'weight'));
        $shrinkage = $initialWeight - $totalOutput;
        if ($shrinkage < 0) {
            throw new \Exception('Total output melebihi berat awal.');
        }

        $firstItem    = $items->first();
        $supplierId   = optional($firstItem->receiptItem?->purchaseReceipt)->supplier_id;
        $sourceGradeCompanyId = $data['grade_company_id'];

        $mgmt = IdmManagement::create([
            'supplier_id'      => $supplierId,
            'grade_company_id' => $sourceGradeCompanyId,
            'initial_weight'   => $initialWeight,
            'shrinkage'        => $shrinkage,
            'grading_date'     => now(),
        ]);

        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        $userId      = Auth::id();

        // 1. Deduct input
        InventoryTransaction::create([
            'transaction_date'      => now(),
            'grade_company_id'      => $sourceGradeCompanyId,
            'location_id'           => $gudangUtama->id,
            'supplier_id'           => $supplierId,
            'quantity_change_grams' => -$initialWeight,
            'transaction_type'      => 'IDM_REGRADING_OUT',
            'reference_id'          => $mgmt->id,
            'created_by'            => $userId,
        ]);

        // 2. Add outputs + create idm_details
        foreach ($outputs as $name => $out) {
            IdmDetail::create([
                'idm_management_id' => $mgmt->id,
                'grade_idm_name'    => $name,
                'grade_company_id'  => $out['grade_company_id'],
                'weight'            => $out['weight'],
            ]);

            if ($out['weight'] > 0 && $out['grade_company_id']) {
                InventoryTransaction::create([
                    'transaction_date'      => now(),
                    'grade_company_id'      => $out['grade_company_id'],
                    'location_id'           => $gudangUtama->id,
                    'supplier_id'           => $supplierId,
                    'quantity_change_grams' => $out['weight'],
                    'transaction_type'      => 'IDM_REGRADING_IN',
                    'reference_id'          => $mgmt->id,
                    'created_by'            => $userId,
                ]);
            }
        }

        SortingResult::whereIn('id', $itemIds)->update(['idm_management_id' => $mgmt->id]);

        return $mgmt;
    });
}
```

### `ManajemenIdmService::update()` — Same Pattern

- Revert semua transaction terkait via `IDM_REGRADING_REVERT_OUT/IN`
- Soft-delete transaksi lama
- Buat ulang dengan data baru
- Tambah validasi: cek apakah salah satu output sudah keluar via SALE_OUT/TRANSFER_OUT/EXTERNAL_TRANSFER_OUT/RECEIVE_EXTERNAL_OUT → kalau ya, block update

### `ManajemenIdmService::delete()` — Tambah Revert

```php
public function delete(int $id): void
{
    DB::transaction(function () use ($id) {
        $mgmt   = IdmManagement::with('details')->findOrFail($id);
        $userId = Auth::id();

        // Block kalau output sudah keluar
        $outflowTypes = ['SALE_OUT', 'TRANSFER_OUT', 'EXTERNAL_TRANSFER_OUT', 'RECEIVE_EXTERNAL_OUT'];
        $hasOutflow = InventoryTransaction::where('reference_id', '!=', $mgmt->id)
            ->whereIn('grade_company_id', $mgmt->details->pluck('grade_company_id')->filter())
            ->whereIn('transaction_type', $outflowTypes)
            ->exists();

        if ($hasOutflow) {
            throw new \Exception('Tidak bisa hapus — output sudah keluar via transfer/sale.');
        }

        $txs = InventoryTransaction::where('reference_id', $mgmt->id)
            ->whereIn('transaction_type', ['IDM_REGRADING_OUT', 'IDM_REGRADING_IN'])
            ->get();

        foreach ($txs as $tx) {
            InventoryTransaction::create([
                'transaction_date'      => now(),
                'grade_company_id'      => $tx->grade_company_id,
                'location_id'           => $tx->location_id,
                'supplier_id'           => $tx->supplier_id,
                'quantity_change_grams' => -$tx->quantity_change_grams,
                'transaction_type'      => $tx->transaction_type === 'IDM_REGRADING_OUT'
                    ? 'IDM_REGRADING_REVERT_OUT'
                    : 'IDM_REGRADING_REVERT_IN',
                'reference_id'          => $mgmt->id,
                'created_by'            => $userId,
            ]);
            $tx->deleted_by = $userId;
            $tx->save();
            $tx->delete();
        }

        SortingResult::where('idm_management_id', $id)->update(['idm_management_id' => null]);
        $mgmt->details()->delete();
        $mgmt->delete();
    });
}
```

---

## Drop Transfer IDM Module

| File / Resource | Aksi |
|---|---|
| `app/Http/Controllers/Feature/TransferIdmController.php` | **Hapus** |
| `app/Services/Idm/TransferIdmService.php` | **Hapus** |
| `app/Models/IdmTransfer.php` | **Hapus** |
| `app/Models/IdmTransferDetail.php` | **Hapus** |
| `app/Exports/TransferIdmExport.php` | **Hapus** |
| `resources/views/admin/transfer-idm/*` | **Hapus** |
| `tests/Feature/TransferIdmServiceTest.php` | **Hapus** |
| `routes/web.php` (group `barang.keluar.transfer-idm.*`) | Hapus route |
| Sidebar / menu | Hapus link Transfer IDM |
| Tabel `idm_transfers`, `idm_transfer_details` | Drop via migration |

---

## Files yang Diubah

| # | File | Perubahan |
|---|------|-----------|
| 1 | `database/migrations/*_add_grade_company_id_to_idm_details.php` | Tambah FK nullable |
| 2 | `database/migrations/*_drop_idm_transfer_tables.php` | Drop dua tabel |
| 3 | `database/seeders/ParentGradeCompanySeeder.php` | Restruktur: tambah `IDM`, hapus `IDM A`/`IDM B` |
| 4 | `database/seeders/GradeCompanySeeder.php` | Tambah `IDM A`/`IDM B`/`PERUTAN`/`KAKIAN`/`ALU/AFKIR` + set parent |
| 5 | `app/Models/IdmDetail.php` | Tambah `grade_company_id` di fillable + relasi `gradeCompany()` |
| 6 | `app/Services/Idm/ManajemenIdmService.php` | Major refactor: 4 outputs, create inventory_transactions, delete dengan revert |
| 7 | `app/Http/Controllers/Feature/ManajemenIdmController.php` | Pass `$idmOutputGrades` ke view + validasi baru |
| 8 | `resources/views/admin/manajemen-idm/step2.blade.php` | 4 row, dropdown grade untuk IDM, Tom Select |
| 9 | `resources/views/admin/manajemen-idm/edit.blade.php` | Sinkron step2 |
| 10 | `resources/views/admin/manajemen-idm/show.blade.php` | Tampilkan `grade_company` per row |
| 11 | `CLAUDE.md` | Update transaction types + domain model flow |

Plus 8 file Transfer IDM yang dihapus (lihat tabel di atas).

---

## Verifikasi End-to-End

### Setup

```bash
php artisan migrate:fresh --seed
php artisan serve
npm run dev
```

### Skenario Test

| # | Aksi | Expected |
|---|------|----------|
| 1 | Cek seeder | `ParentGradeCompany` punya `IDM`. `GradeCompany` punya `IDM A`/`IDM B` (parent=IDM) + `PERUTAN`/`KAKIAN`/`ALU/AFKIR` (no parent) |
| 2 | Grading, set kategori `IDM A`, weight 1000g | SortingResult.category_grade = IDM A |
| 3 | Buka Manajemen IDM → pilih item IDM A → step2 | Form muncul 4 row (IDM/Kakian/Perutan/Alu) |
| 4 | Isi IDM=700g + dropdown grade, KAKIAN=100g, ALU=50g, PERUTAN=0, submit | 1× IDM_REGRADING_OUT -1000g; 3× IDM_REGRADING_IN (IDM/KAKIAN/ALU). Susut=150g |
| 5 | Buka tracking-stock | 3 grade output muncul dengan stok yang benar |
| 6 | Buka Transfer External → pilih grade IDM output → kirim ke jasa cuci | TRANSFER_OUT terbuat, stok berkurang |
| 7 | Buka Transfer Internal → pilih grade IDM output → kirim ke DMK | TRANSFER_OUT terbuat (skip TRANSFER_IN karena DMK exit-point) |
| 8 | Hapus ManajemenIDM yang outputnya BELUM keluar | Sukses, REVERT transactions dibuat, stok kembali |
| 9 | Hapus ManajemenIDM yang outputnya SUDAH ditransfer | Block dengan error |
| 10 | Cek menu sidebar | Tidak ada lagi link Transfer IDM |

---

## Ringkasan Keputusan

| Pertanyaan | Jawaban |
|---|---|
| Drop Transfer IDM? | **Ya** — output IDM langsung jadi stok normal di parent "IDM" |
| Schema `idm_details` | Tambah `grade_company_id` nullable + row `ALU` baru |
| Input grade (IDM A/B)? | Tetap tersimpan di `IdmManagement.grade_company_id` (untuk tracking asal) |
| Output grade (IDM)? | **Langsung ke parent "IDM"** — standalone grade, **BUKAN dari dropdown** |
| Dropdown untuk IDM? | **TIDAK ADA** — otomatis resolve ke grade "IDM" di service |
| Struktur GradeCompany | `IDM` standalone (output) + `IDM A`, `IDM B` child of parent `IDM` (input kategori) |
| PERUTAN/KAKIAN/ALU/AFKIR | Grade biasa, standalone (no parent), auto-lookup by name (UPPERCASE) di service |
| Susut | Computed: `initial - sum(outputs)`, tidak dicatat di inventory |
| Wajib | IDM berat saja (grade auto). KAKIAN/PERUTAN/ALU opsional |
| Data migration | **Tidak perlu** — fresh deploy |
| Tabel `idm_transfers` lama | **Drop langsung** — data zero |

---

## Update Implementasi (2026-06-29)

### Perubahan dari Design Awal

**Design awal** menyebutkan:
> Form IDM: "Berat + dropdown (Tom Select)" + GradeCompany "IDM" standalone (no parent)

**Implementasi final** (yang sebenarnya dilakukan):
1. **Tidak ada dropdown untuk IDM** — cukup input berat saja
2. **Grade IDM jadi child dari parent "IDM"** — bukan standalone
3. **Service auto-lookup** — `GradeCompany.where('name', 'IDM')`
4. **Form hint**: "Output langsung ke grade parent IDM" (di step2 dan edit view)

### Alasan Perubahan

- **Simplifikasi UX**: User tidak perlu memilih grade IDM A vs B saat output
- **Stock tracking otomatis**: IDM output stock otomatis include dalam parent "IDM" calculations (via `TrackingStockService`)
- **Tidak perlu custom logic**: Tracking service sudah built untuk menghitung child grades, jadi IDM cukup jadi child seperti biasa
- **Konsistensi**: Semua byproduct (KAKIAN/PERUTAN/ALU) auto-lookup by name, jadi IDM juga seharusnya sama

### Files yang Berubah

1. `database/seeders/GradeCompanySeeder.php` — tambah standalone `GradeCompany "IDM"`
2. `app/Services/Idm/ManajemenIdmService.php` → `buildOutputs()` — lookup `GradeCompany "IDM"` bukan dari form
3. `resources/views/admin/manajemen-idm/step2.blade.php` — hapus dropdown, tampil hint "Output langsung ke grade parent IDM"
4. `resources/views/admin/manajemen-idm/edit.blade.php` — sama seperti step2

### Grade Hierarchy Final

```
ParentGradeCompany "IDM"
  ├─ GradeCompany "IDM"       (output grade, ID 1)
  ├─ GradeCompany "IDM A"     (input category, ID 2)
  └─ GradeCompany "IDM B"     (input category, ID 3)

GradeCompany "PERUTAN"        (standalone output, ID ...)
GradeCompany "KAKIAN"         (standalone output, ID ...)
GradeCompany "ALU/AFKIR"      (standalone output, ID ...)
```

**Benefit**: IDM output stock otomatis include dalam parent "IDM" tracking calculations via `TrackingStockService.calculateParentGlobalStock()`

---

*Design Document created: 2026-06-29*
*Implementation completed: 2026-06-29*
*Author: Claude Code (design + implementation)*
