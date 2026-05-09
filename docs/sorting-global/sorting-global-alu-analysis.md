# Analisis Sorting Global — ALU, AF, Indomie P

## Overview

Fitur baru/revisi untuk process sorting barang dengan perhitungan **GLOBAL** (tidak per-batch/per-partai), khusus untuk parent grade:
- **ALU** — 100% masuk stok gudang
- **AF** dan **Indomie P** — sebagian masuk stok, sisa langsung dijual

---

## Requirements (User Request)

### Jenis Barang & Alur

| Jenis Barang | Perilaku |
|--------------|----------|
| **ALU** | Disortir, **100% langsung masuk stok gudang** (tidak ada yang dijual/keluar) |
| **AF** | Disortir, sebagian masuk stok, **sisanya langsung dijual** |
| **Indomie P** | Disortir, sebagian masuk stok, **sisanya langsung dijual** |

### UI/Workflow yang Diusulkan

1. **Input**: Pilih Grade Company
2. **Tampilkan**:
   - Total stok **GLOBAL** (semua lokasi digabung)
   - Nama supplier
3. **Pilih Aksi**: 2 tombol
   - **"Sortir Masuk Stok"** → Proses sortir ke stok gudang
   - **"Penjualan Langsung"** → Langsung buat transaksi penjualan
4. **Jika Sortir** → Pilih tujuan:
   - Sortir Bahan
   - Mangkok
   - IDM
   - AA
   - AF
   - Jual
5. **Input Berat** secara manual

### Aturan Penting

> **Penghitungan HARUS GLOBAL (bukan per-partai/batch)**
>
> Contoh: Total AFK + Indomie P = 30 kg
> - Masuk stok: 4.5 kg
> - Terjual (auto): 25.5 kg
> - Sisa di sistem = 4.5 kg

---

## Analisis Sistem Sekarang

### Tabel yang Relevan

| Tabel | Peran | Operasi |
|-------|-------|---------|
| `parent_grade_companies` | Header ALU, AF, Indomie P | Read |
| `grades_company` | Child grades (ALU, AFK, Indomie P, dll) | Read |
| `sorting_results` | Hasil sorting per batch | Create, Read |
| `inventory_transactions` | Stok per grade + lokasi | Create, Read |
| `suppliers` | Info supplier | Read |
| `locations` | Lokasi gudang | Read |

### Sistem Sekarang

#### Grading Goods (Sortir dari Barang Mentah)

```
Flow:
ReceiptItem (barang mentah) → GradingGoodsController (Step 1, Step 2)
→ SortingResult dibuat per grade + weight
→ InventoryTransaction (GRADING_IN) otomatis dibuat
```

- **Stock calculation**: per-batch melalui `sorting_result_id`
- **Global budgeting** di `BarangKeluarService::getGradingSourcesWithStock()` sudah ada untuk dropdown stock

#### Sortir Bahan (Sortir dari Barang Sudah Disortir)

```
Flow:
SortMaterialController (create/update) → sort_materials table
→ Langsung add stock ke parent_grade_company.stock
```

- Stock di `parent_grade_companies.stock` (simple counter)

### Permasalahan dengan Sistem Sekarang

| Aspek | Kondisi Sekarang | Yang Dibutuhkan |
|-------|-----------------|-----------------|
| Stock ALU | Per-partai via sorting_result | **Global** (sum semua) |
| Penghitungan | Per-batch | **Global** (total keseluruhan) |
| Output Sorting | Hanya Sortir Bahan | **Multi-output**: Stok, Jual langsung |
| Alur AF/Indomie P | Tidak ada penjualan auto | **Auto create SALE_OUT** untuk sisa |

---

## Design Solution

### Conceptual Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    SORTIR GLOBAL ALU                         │
├─────────────────────────────────────────────────────────────┤
│ 1. User pilih Grade Company (dropdown dengan global stock)  │
│ 2. Tampilkan:                                               │
│    - Grade Name                                             │
│    - Supplier Name (dari receipt_item)                      │
│    - Global Stock (SUM semua lokasi)                        │
│ 3. User pilih aksi:                                         │
│    ┌──────────────────┐  ┌──────────────────┐              │
│    │  SORTIR MASUK     │  │  PENJUALAN      │              │
│    │  STOK GUDANG      │  │  LANGSUNG       │              │
│    └──────────────────┘  └──────────────────┘              │
│                                                             │
│ 4a. Jika SORTIR → Pilih tujuan:                            │
│      - Sortir Bahan / Mangkok / IDM / AA / AF / Jual       │
│      - Input berat (manual)                                 │
│      → Create InventoryTransaction (GRADING_IN)            │
│                                                             │
│ 4b. Jika PENJUALAN LANGSUNG:                                │
│      - Input berat (manual)                                │
│      → Create InventoryTransaction (SALE_OUT)               │
└─────────────────────────────────────────────────────────────┘
```

### Special Case: ALU Global

**ALU** tidak masuk ke flow di atas — ALU langsung 100% masuk stok tanpa pilihan penjualan.

```
┌─────────────────────────────────────────────────────────────┐
│                      SORTIR ALU                              │
├─────────────────────────────────────────────────────────────┤
│ 1. User pilih ALU (parent grade atau grade?)                │
│ 2. Tampilkan:                                               │
│    - Supplier Name                                          │
│    - Global Stock (SUM semua lokasi)                       │
│ 3. AUTO → Langsung masuk stok (tidak ada pilihan lain)     │
│ 4. User input berat (manual)                                │
│ 5. Create InventoryTransaction (GRADING_IN)                 │
│    + Update parent_grade_companies.stock                    │
└─────────────────────────────────────────────────────────────┘
```

### Special Case: AF + Indomie P

```
┌─────────────────────────────────────────────────────────────┐
│                   SORTIR AF / INDOIMIE P                    │
├─────────────────────────────────────────────────────────────┤
│ 1. User pilih grade (AFK atau Indomie P)                    │
│ 2. Tampilkan:                                               │
│    - Supplier Name                                          │
│    - Global Stock (SUM semua lokasi)                        │
│ 3. User pilih:                                             │
│    ┌──────────────────┐  ┌──────────────────┐              │
│    │  MASUK STOK       │  │  JUAL LANGSUNG   │              │
│    │  + INPUT BERAT    │  │  + INPUT BERAT   │              │
│    └──────────────────┘  └──────────────────┘              │
│                                                             │
│ 4. MASUK STOK:                                             │
│    → Create GRADING_IN dengan quantity sesuai input         │
│    → Sisa (global_stock - input) tetap di tracking         │
│                                                             │
│ 5. JUAL LANGSUNG:                                           │
│    → Create SALE_OUT dengan quantity sesuai input           │
│    → Kurangi global stock                                  │
└─────────────────────────────────────────────────────────────┘
```

### Note Penting tentang "Global"

Pertanyaan: **"Global" yang dimaksud di sini，是指:**

1. **Total stock semua batch digabung** per grade company?
2. **Atau gabungan AFK + Indomie P** menjadi satu "keluarga" dan dihitung bareng?

**Asumsi saat ini**: Global = total stock semua batch per grade company (bukan digabung antar grade).

Jika maksudnya berbeda (AFK + Indomie P digabung), maka perlu konfirmasi lebih lanjut.

---

## Estimasi Effort

| Komponen | Effort | Catatan |
|---------|--------|--------|
| New Controller | Medium | SortirGlobalController atau integrate ke GradingGoodsController |
| New Service | Medium | SortirGlobalService dengan logic global |
| View/Blade | Medium | Form baru dengan 2 tombol, dropdown tujuan |
| Modify Inventory Transaction | Low | Tambah type baru jika perlu |
| Database Migration | Low | Mungkin perlu kolom baru di sorting_results |

---

## Open Questions

1. **"Sortir Bahan" yang dimaksud** — apakah ini:
   - Fitur Sortir Bahan yang sudah ada (`sort_materials`)?
   - Atau halaman Grading baru?
   - Atau lokasi baru di `locations` table?

2. **Mangkok, IDM, AA, AF** — ini:
   - Jenis output/category?
   - Atau nama grade company baru?
   - Atau lokasi di `locations` table?

3. **"Global"** — yang dimaksud:
   - Total stock semua batch per grade?
   - Atau gabungan AFK + Indomie P jadi satu keluarga?

4. **Tombol "Penjualan Langsung"** — apakah ini langsung create `SALE_OUT` transaction, atau tetap buat `GRADING_IN` dulu kemudian user harus ke menu Penjualan untuk jual?

---

## Next Steps

1. **Konfirmasi requirement** via pertanyaan di atas
2. **Buat mockup UI** atau minta screenshot sistem sekarang
3. **Decision**: Integrate ke GradingGoodsController atau buat controller baru?
4. **Implementasi** dengan approach yang sudah disepakati

---

## Related Files (Existing)

| File | Path | Relevan |
|------|------|---------|
| GradingGoodsController | app/Http/Controllers/Feature/ | Similar flow |
| GradingGoodsService | app/Services/GradingGoods/ | Similar logic |
| GradingGoodsService::getGradingSourcesWithStock | app/Services/BarangKeluar/ | Global budgeting reference |
| SortingResult | app/Models/SortingResult.php | existing outgoing_type |
| SortMaterial | app/Models/SortMaterial.php | Sortir Bahan existing |
| ParentGradeCompany | app/Models/ParentGradeCompany.php | Stock field |

---

## Referensi: Existing "Global Budgeting" di Barang Keluar

```php
// BarangKeluarService.php - getGradingSourcesWithStock()
// Ini sudah menghitung stock GLOBAL untuk dropdown

public function getGradingSourcesWithStock(string $outgoingType, int $locationId)
{
    // 1. Pre-calculate budget pools per parent grade (GLOBAL)
    foreach ($gradeIdsByParent as $parentId => $childIds) {
        $budgetPools["parent_{$parentId}"] = InventoryTransaction::whereIn('grade_company_id', $childIds)->sum('quantity_change_grams');
    }

    // 2. Pre-calculate location budgets per grade
    $locationBudgets[...] = InventoryTransaction::where('grade_company_id', $gradeId)->where('location_id', $locationId)->sum('quantity_change_grams');

    // 3. Pre-calculate batch stocks
    $batchStocks[...] = InventoryTransaction::where('sorting_result_id', $source->id)->where('location_id', $locationId)->sum('quantity_change_grams');
}
```

Logic ini bisa di-reuse untuk fitur Sortir Global.