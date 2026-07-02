# IDM A/B Grading Workflow — Mandatory Regrading

**Status:** ✅ ENFORCED  
**Date:** 2026-07-02  
**Purpose:** Enforce mandatory regrading for IDM A/B grades before any barang-keluar operations

---

## Business Rule

**Grading dengan category_grade = "IDM A" atau "IDM B" WAJIB di-regrade di Manajemen IDM terlebih dahulu sebelum dapat digunakan di barang-keluar.**

### Alasan:
1. ✅ **Quality Control** — Memastikan proses regrading dilakukan sebelum distribusi
2. ✅ **Process Compliance** — Workflow yang terstruktur dan terkontrol
3. ✅ **Clear Traceability** — Tracking dari raw grading hingga final output
4. ✅ **Prevents Bypass** — Tidak bisa langsung jual/transfer tanpa regrading

---

## Workflow

```
┌─────────────────────────────────────────┐
│ STEP 1: Create Grading                  │
├─────────────────────────────────────────┤
│ • category_grade = "IDM A" atau "IDM B" │
│ • outgoing_type = null (flexible)       │
│ • Weight: 100g                          │
│ • Status: BELUM BISA DI BARANG KELUAR   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│ STEP 2: Regrade di Manajemen IDM        │
├─────────────────────────────────────────┤
│ • Input: Raw IDM A/B grading (100g)     │
│ • Process: Regrading (cuci, sortir, etc)│
│ • Output:                               │
│   - IDM: 50g                            │
│   - KAKIAN: 20g                         │
│   - PERUTAN: 15g                        │
│   - ALU/AFKIR: 10g                      │
│   - Shrinkage: 5g                       │
│ • Create: IDM-SR proxy untuk setiap     │
│           output dengan idm_output_id   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│ STEP 3: Gunakan di Barang Keluar        │
├─────────────────────────────────────────┤
│ • Penjualan: Jual IDM/KAKIAN/PERUTAN    │
│ • Transfer Internal: Pindah ke gudang   │
│ • Transfer Eksternal: Kirim ke jasa cuci│
│ • Hanya output IDM yang bisa dipakai    │
│ • Raw IDM A/B tetap BLOCKED             │
└─────────────────────────────────────────┘
```

---

## Implementation Details

### What's Allowed in Barang Keluar

#### ✅ CAN BE USED (Available)

**1. Regular Grading (tanpa kategori IDM)**
```
category_grade = null
outgoing_type = "penjualan_langsung" / "internal" / "external"

Contoh: KAKIAN hasil grading biasa, ALU hasil grading biasa
Status: Available langsung, tidak perlu regrading
```

**2. IDM Outputs (dari regrading)**
```
idm_output_id != NULL
Dari IdmManagement regrading process

Contoh: IDM grade, KAKIAN grade, PERUTAN grade dari regrading
Status: Available setelah regrading selesai
```

#### ❌ CANNOT BE USED (Blocked)

**Raw IDM A/B Grading**
```
category_grade = "IDM A" atau "IDM B"
idm_management_id = NULL (belum di-regrade)
idm_output_id = NULL (bukan output)

Contoh: Hasil grading dengan kategori IDM A yang belum di-regrade
Status: BLOCKED dari barang-keluar, HARUS regrade dulu
```

---

## Code Implementation

### Filter in getGradingSourcesWithStock()

**File:** `app/Services/BarangKeluar/BarangKeluarService.php`

```php
->where(function ($q) use ($outgoingType) {
    // 1) Regular grading (tanpa kategori IDM) dengan outgoing_type match
    $q->where(function ($q2) use ($outgoingType) {
        $q2->whereNull('category_grade')  // ← Exclude "IDM A" dan "IDM B"
           ->whereIn('outgoing_type', [$outgoingType, null]);
    })
    // 2) ATAU IDM-SR proxy (sudah di-regrade)
    ->orWhereNotNull('idm_output_id');
})
```

**Logic:**
- `whereNull('category_grade')` → Exclude semua grading dengan kategori IDM
- `whereNotNull('idm_output_id')` → Include hanya yang sudah di-regrade (punya output ID)

---

## User Experience

### Scenario 1: Try to Sell Raw IDM A Grading

```
User: Buka Penjualan → pilih grade IDM A
System: ❌ Tidak tampil di list (BLOCKED)

Why: category_grade = "IDM A" dan belum di-regrade
Solution: Buka Manajemen IDM → Regrade IDM A dulu
```

### Scenario 2: After Regrading

```
User: Buka Manajemen IDM → Regrade IDM A (100g)
Result: 4 outputs created
  - IDM-SR proxy: 50g
  - KAKIAN-SR proxy: 20g
  - PERUTAN-SR proxy: 15g
  - ALU-SR proxy: 10g

User: Buka Penjualan
System: ✅ Semua 4 output TAMPIL dan bisa dijual

Why: idm_output_id != NULL untuk semua proxy
```

### Scenario 3: Regular Grading (No Category)

```
User: Buka Grading Goods → Grade KAKIAN biasa (tanpa kategori)
System: ✅ Langsung tampil di Penjualan

Why: category_grade = null, allowed langsung
```

---

## Database View

### SortingResult Record Examples

```sql
-- Raw IDM A Grading (BLOCKED from barang-keluar)
id: 100
category_grade: "IDM A"
idm_management_id: NULL
idm_output_id: NULL
weight_grams: 100
Status: ❌ NOT available

-- Regular Grading (ALLOWED in barang-keluar)
id: 101
category_grade: NULL
idm_management_id: NULL
idm_output_id: NULL
weight_grams: 50
Status: ✅ Available

-- IDM Output Proxy (ALLOWED after regrading)
id: 102
category_grade: NULL
idm_management_id: NULL
idm_output_id: 25  ← Link ke IdmOutput
weight_grams: 50
Status: ✅ Available (hanya setelah IdmManagement created)
```

---

## Validation Flow

```
User selects grade in Penjualan
  ↓
getGradingSourcesWithStock() queries SortingResult
  ↓
Filter conditions:
  1. Is it regular grading?
     → category_grade = null? ✅ Include
  2. Is it IDM output proxy?
     → idm_output_id != null? ✅ Include
  3. Is it raw IDM A/B?
     → category_grade = "IDM A" or "IDM B"? ❌ EXCLUDE
  ↓
Result: Only compliant gradings shown
```

---

## Related Features

| Feature | Status | Notes |
|---------|--------|-------|
| IDM A/B appear in Penjualan | ✅ Works | Only after regrading |
| IDM A/B appear in Transfer Internal | ✅ Works | Only after regrading |
| IDM A/B appear in Transfer Eksternal | ✅ Works | Only after regrading |
| Manajemen IDM can select raw IDM A/B | ✅ Works | That's where they go first |
| Direct sale of raw IDM A/B | ❌ Blocked | Must regrade first |

---

## Testing Checklist

- [ ] Create grading with category_grade = "IDM A"
- [ ] Try to use in Penjualan → Should NOT appear
- [ ] Regrade in Manajemen IDM → Creates 4 outputs
- [ ] Check Penjualan → All 4 outputs now appear
- [ ] Try to sell each output → Should work
- [ ] Create regular grading (no category) → Should appear directly in Penjualan
- [ ] Delete IDM Management → All outputs disappear from Penjualan
- [ ] Original grading remains (now unlinked, still BLOCKED until re-regrained)

---

## Business Impact

**Before:** IDM A/B could bypass regrading → inconsistent quality

**After:** 
- ✅ All IDM A/B MUST go through regrading
- ✅ Only processed outputs available for sales/transfers
- ✅ Process is enforced at application level
- ✅ Clear, visible workflow

---

## Summary

**Rule:** Raw IDM A/B grades are BLOCKED from barang-keluar.

**Path:** Grading (IDM A/B) → Manajemen IDM (Regrade) → Barang Keluar (Sell/Transfer outputs)

**Benefit:** Quality-assured, traceable, compliant workflow. 🎯
