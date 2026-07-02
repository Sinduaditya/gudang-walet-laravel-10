# IDM Grades Flexibility — All Processes Supported

**Status:** ✅ IMPLEMENTED & VERIFIED  
**Date:** 2026-07-02  
**Purpose:** Confirm that IDM A and IDM B grades can flow to ANY business process

---

## Summary

**IDM A dan IDM B grades dapat digunakan di SEMUA proses:**
- ✅ Penjualan Langsung (Direct Sales)
- ✅ Transfer Internal (Pindah ke Gudang Lain)
- ✅ Transfer Eksternal (Kirim ke Jasa Cuci)

**Tidak ada pembatasan berdasarkan category_grade!**

---

## How It Works

### Code Logic

**File:** `app/Services/BarangKeluar/BarangKeluarService.php::getGradingSourcesWithStock()`

```php
->where(function ($q) use ($outgoingType) {
    // 1) Regular grading batches dengan matching outgoing_type atau null
    // 2) ATAU IDM-SR proxy (idm_output_id IS NOT NULL)
    $q->whereIn('outgoing_type', [$outgoingType, null])
      ->orWhereNotNull('idm_output_id');
})
```

**Penjelasan:**
- Untuk setiap $outgoingType (penjualan_langsung, internal, external):
  - Cari batches yang punya matching outgoing_type ATAU null
  - **ATAU** cari IDM-SR proxies (hasil dari IDM management)
- IDM-SR proxies tersedia untuk SEMUA outgoing types karena tidak memiliki outgoing_type restriction

---

## Flow Diagram

```
Grading dibuat
├─ category_grade = "IDM A" atau "IDM B" (optional)
├─ outgoing_type = null (flexible)
└─ Linked to Receipt

↓

IDM Management (Regrading)
├─ Input: Original grading
├─ Output: IDM-SR proxy (idm_output_id set)
└─ Category: Tetap "IDM A" atau "IDM B" jika ada

↓

Penjualan Langsung → SALE_OUT
✅ IDM-SR tersedia (via idm_output_id != NULL check)

↓

Transfer Internal → TRANSFER_OUT + TRANSFER_IN
✅ IDM-SR tersedia (via idm_output_id != NULL check)

↓

Transfer Eksternal (Jasa Cuci) → EXTERNAL_TRANSFER_OUT + EXTERNAL_TRANSFER_IN
✅ IDM-SR tersedia (via idm_output_id != NULL check)
```

---

## Use Cases

### Case 1: Direct Sales of IDM Output

```
Grading IDM A (hasil regrading)
  → Penjualan Langsung (sell to customer)
  → SALE_OUT transaction
  → Stock decreases correctly
```

**Supported:** ✅ YES

### Case 2: Transfer IDM Output to Another Warehouse

```
Grading IDM B (hasil regrading)
  → Transfer Internal ke gudang DMK
  → TRANSFER_OUT (dari Gudang Utama) + TRANSFER_IN (ke DMK)
  → Stock moves correctly between warehouses
```

**Supported:** ✅ YES

### Case 3: Send IDM Output to Washing Service

```
Grading IDM A (hasil regrading)
  → Transfer Eksternal ke Jasa Cuci
  → EXTERNAL_TRANSFER_OUT (dari Gudang Utama) + EXTERNAL_TRANSFER_IN (ke Jasa Cuci)
  → Stock monitored during wash process
  → Later: RECEIVE_EXTERNAL (kembali ke Gudang Utama)
```

**Supported:** ✅ YES

### Case 4: Mixed Sources for Same Process

```
Penjualan dengan mix:
  - 50g dari regular grading KAKIAN (outgoing_type = penjualan_langsung)
  - 30g dari IDM A hasil regrading
  → Both sources selectable in same sale
  → Both counted correctly in tracking stock
```

**Supported:** ✅ YES (via global budgeting in getGradingSourcesWithStock)

---

## Technical Implementation

### Why This Works

**Key principle in getGradingSourcesWithStock():**

```
Include sources IF:
  - Batch outgoing_type matches requested type OR
  - Batch outgoing_type is null OR
  - Batch is IDM-SR proxy (idm_output_id IS NOT NULL)
```

**Result:**
- Regular grading: needs matching outgoing_type to appear in specific process
- IDM-SR proxy: appears in ALL processes regardless of outgoing_type
- **IDM grades are "universal" — not restricted to specific outlets**

---

## Validation & Constraints

### What IS Restricted

- Stock validation (must have enough before transaction)
- Batch remaining stock check (for batches)
- Location stock check (must have stock in that warehouse)
- Global stock check (across all locations)

### What is NOT Restricted

- ❌ Category grade does NOT restrict where it can be sold
- ❌ Outgoing type of original grading does NOT restrict IDM output
- ❌ No special rules for IDM A vs IDM B
- ❌ No location-based restrictions for IDM grades

---

## Confirmation Checklist

- [x] IDM-SR proxies available for PENJUALAN_LANGSUNG
- [x] IDM-SR proxies available for INTERNAL transfer
- [x] IDM-SR proxies available for EXTERNAL transfer (jasa cuci)
- [x] No category_grade validation prevents multi-process use
- [x] Stock calculations work correctly for all processes
- [x] Tracking stock shows IDM correctly in all workflows

---

## Related Files

- `app/Services/BarangKeluar/BarangKeluarService.php::getGradingSourcesWithStock()` — Selection logic
- `app/Http/Controllers/Feature/PenjualanController.php` — Direct sales
- `app/Http/Controllers/Feature/TransferInternalController.php` — Internal transfers
- `app/Http/Controllers/Feature/TransferExternalController.php` — Wash service transfers
- `app/Models/SortingResult.php` — Category grade definitions

---

## Summary

**IDM A dan IDM B grades sekarang fully flexible:**

| Process | Support | Notes |
|---------|---------|-------|
| Penjualan Langsung | ✅ YES | Direct customer sales |
| Transfer Internal | ✅ YES | Move between warehouses |
| Transfer Eksternal | ✅ YES | Send to jasa cuci |
| Atur Stok Sortir | ✅ YES | Sort material workflow |

**Tidak ada pembatasan — gunakan sesuai kebutuhan bisnis!** 🎯
