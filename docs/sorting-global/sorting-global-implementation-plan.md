# Implementation Plan: Sorting Global ALU, AF, Indomie P

## Overview

Fitur baru/revisi untuk process sorting barang dengan perhitungan **GLOBAL** dan multi-output (stok atau penjualan langsung).

---

## Scope

### Include
- Halaman Sortir baru dengan dropdown grade company
- Tampilan stock GLOBAL per grade
- 2 tombol aksi: "Sortir Masuk Stok" dan "Penjualan Langsung"
- Pilihan tujuan sortir: Sortir Bahan, Mangkok, IDM, AA, AF, Jual
- Input berat manual
- Logic khusus ALU (100% masuk stok, tidak ada pilihan jual)
- Logic AF + Indomie P (masuk stok atau jual langsung)

### Exclude
- Modifikasi GradingGoodsController yang sudah ada (buat controller baru)
- Modifikasi BarangKeluarService (buat service baru)

---

## Approach

### Option A: New Controller + Service (Recommended)

**Alasan:**
- Tidak menganggu sistem grading yang sudah ada
- Logic global sorting berdiri sendiri
- Mudah di-test dan di-maintain
- Jika gagal, mudah di-rollback

**Struktur:**
```
app/Http/Controllers/Feature/SortirGlobalController.php (NEW)
app/Services/SortirGlobal/SortirGlobalService.php (NEW)
resources/views/admin/sortir-global/*.blade.php (NEW)
routes/web.php → sortir-global routes (NEW)
```

### Option B: Extend GradingGoodsController

**Alasan melawan:**
- Complex, banyak kondisi if/else
- Meningkatkan risiko regression
- GradingGoods sudah complex dengan step1/step2

**Rejected.**

---

## Implementation Steps

### Step 1: Database & Model (If Needed)

#### 1.1 Check existing columns di `sorting_results`

```php
// sorting_results table sudah ada:
// - outgoing_type (string, nullable) — untuk categorize output
// - category_grade (string, nullable) — untuk IDM A/B

// Kemungkinan perlu menambah:
// - sort_destination (string, nullable) — untuk track: Sortir Bahan, Mangkok, IDM, AA, AF, Jual
```

#### 1.2 Check locations untuk "Mangkok", "IDM", "AA", "AF"

```sql
SELECT * FROM locations WHERE name IN ('Mangkok', 'IDM', 'AA', 'AF', 'Sortir Bahan');
```

**Jika tidak ada**, perlu insert location baru.

---

### Step 2: Create SortirGlobalService

**File:** `app/Services/SortirGlobal/SortirGlobalService.php`

**Methods:**

```php
class SortirGlobalService
{
    /**
     * Get grades dengan stock GLOBAL per parent grade ALU, AF, Indomie P
     * Menggunakan batch query untuk avoid N+1
     */
    public function getGradesWithGlobalStock(array $parentGradeNames = ['ALU', 'AF', 'Indomie P']): Collection

    /**
     * Get global stock untuk satu grade
     */
    public function getGlobalStock(int $gradeCompanyId): float

    /**
     * Get supplier info dari sorting results
     */
    public function getSupplierInfo(int $gradeCompanyId): ?Supplier

    /**
     * Proses sortir masuk stok (GRADING_IN)
     * Untuk ALU: selalu masuk stok 100%
     * Untuk AF/Indomie P: sebagian masuk, sisa tetap di tracking
     */
    public function processSortirMasukStok(array $data): SortingResult

    /**
     * Proses penjualan langsung (SALE_OUT)
     * Untuk AF/Indomie P saja
     */
    public function processPenjualanLangsung(array $data): InventoryTransaction
}
```

---

### Step 3: Create SortirGlobalController

**File:** `app/Http/Controllers/Feature/SortirGlobalController.php`

**Routes:**

```php
Route::prefix('sortir-global')->name('sortir-global.')->group(function () {
    Route::get('/', 'index')->name('index');           // Form utama
    Route::post('/store', 'store')->name('store');      // Process action
    Route::get('/api/stock/{gradeId}', 'getStock')->name('api.stock'); // AJAX stock
});
```

**Methods:**

```php
class SortirGlobalController extends Controller
{
    protected SortirGlobalService $service;

    public function __construct(SortirGlobalService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $grades = $this->service->getGradesWithGlobalStock();
        return view('admin.sortir-global.index', compact('grades'));
    }

    public function store(Request $request)
    {
        // Validate
        // Determine action: 'sortir_stok' or 'penjualan'
        // Call appropriate service method
        // Redirect with success/error
    }

    public function getStock(Request $request, $gradeId)
    {
        // AJAX: Return global stock for selected grade
    }
}
```

---

### Step 4: Create Blade Views

**Directory:** `resources/views/admin/sortir-global/`

**Files:**
- `index.blade.php` — Form utama dengan dropdown, stock display, 2 tombol
- `partials/_grade_selector.blade.php` — Dropdown grade dengan stock
- `partials/_action_buttons.blade.php` — 2 tombol aksi
- `partials/_destination_selector.blade.php` — Dropdown tujuan sortir

**UI Flow:**

```
1. Dropdown: Pilih Grade Company
   └─ Tampilkan: Grade Name + Global Stock + Supplier

2. Jika ALU selected:
   └─ Langsung tampilkan form input berat
   └─ Tombol "Masukkan Stok"

3. Jika AF/Indomie P selected:
   └─ Tampilkan 2 tombol:
      [ MASUK STOK ]  [ JUAL LANGSUNG ]

4a. Jika "Masuk Stok":
    └─ Dropdown: Pilih Tujuan (Sortir Bahan / Mangkok / IDM / AA / AF / Jual)
    └─ Input: Berat (gram)
    └─ Tombol: "Proses"

4b. Jika "Jual Langsung":
    └─ Input: Berat (gram)
    └─ Tombol: "Proses"
```

---

### Step 5: Add Navigation/Menu

**File:** `resources/views/layouts/admin/sidebar.blade.php` (or wherever menu exists)

Tambah menu item:

```blade
<li>
    <a href="{{ route('sortir-global.index') }}">
        <i class="fa fa-sort"></i> Sortir Global
    </a>
</li>
```

---

## File List

| File | Action | Description |
|------|--------|-------------|
| `app/Services/SortirGlobal/SortirGlobalService.php` | CREATE | Core business logic |
| `app/Http/Controllers/Feature/SortirGlobalController.php` | CREATE | Controller |
| `resources/views/admin/sortir-global/index.blade.php` | CREATE | Main form view |
| `resources/views/admin/sortir-global/partials/*.blade.php` | CREATE | Partial views |
| `routes/web.php` | MODIFY | Add routes |
| `resources/views/layouts/admin/sidebar.blade.php` | MODIFY | Add menu item |
| `database/migrations/xxxx_add_sort_destination_to_sorting_results.php` | CREATE (if needed) | Add column |

---

## Special Logic: ALU 100% Masuk Stok

```php
// SortirGlobalService::processSortirMasukStok()

if ($grade->parentGradeCompany->name === 'ALU') {
    // ALU: Langsung masuk stok, tidak ada pilihan lain
    // Buat GRADING_IN, update parent_grade_company.stock
}

// AF / Indomie P:
// - User pilih: masuk stok atau jual langsung
// - Jika masuk stok: GRADING_IN dengan quantity input
// - Jika jual langsung: SALE_OUT dengan quantity input
```

---

## Special Logic: AF + Indomie P Split

**CONFIRMED: Sisa stock langsung di-SALE_OUT-kan otomatis**

```
Total Global Stock: 30 kg (AFK) + Indomie P

User pilih "Masuk Stok" 4.5 kg:
→ GRADING_IN: 4.5 kg (ke gudang)
→ SALE_OUT: 25.5 kg (sisa langsung dijual otomatis)

User pilih "Jual Langsung" 4.5 kg:
→ SALE_OUT: 4.5 kg (semuanya dijual)
```

---

## Question 2: Tujuan sortir (Sortir Bahan, Mangkok, IDM, AA, AF, Jual)

**CONFIRMED: Ini adalah CATEGORY/LABEL untuk tracking aliran barang**

- Transaction type tetap `GRADING_IN` (masuk stok) atau `SALE_OUT` (jual)
- Kolom `outgoing_type` di `sorting_results` menyimpan tujuan ini
- Berguna untuk reporting/analytics: "Berapa banyak yang masuk ke Mangkok?"

**Contoh:**
- User pilih grade "AFK", aksi "Masuk Stok", tujuan "Mangkok", berat 4.5 kg
- Sistem: GRADING_IN 4.5 kg, dengan `outgoing_type='mangkok'`
- Sisa 25.5 kg → auto SALE_OUT

---

## Status Tracking

| Task | Status |
|------|--------|
| Analysis Document | ✅ Done |
| Implementation Plan | 🔄 In Progress |
| Create Service | Pending |
| Create Controller | Pending |
| Create Views | Pending |
| Add Routes | Pending |
| Add Menu | Pending |
| Testing | Pending |

---

## Referensi

- Analysis: `docs/sorting-global/sorting-global-alu-analysis.md`
- Existing Global Budgeting: `app/Services/BarangKeluar/BarangKeluarService.php::getGradingSourcesWithStock()`