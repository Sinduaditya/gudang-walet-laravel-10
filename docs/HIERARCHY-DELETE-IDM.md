# Penghapusan Hierarki IDM → Grading

**Status:** ✅ FULLY IMPLEMENTED  
**Date:** 2026-07-02  
**Tujuan:** Enforce deletion order: IDM outputs → IDM Management → Original Grading

---

## Deskripsi Masalah

User mengatakan:
> "Aku sudah regrading di IDM, terus aku mau coba hapus data di Manajemen IDM itu masih bisa, kan datanya sudah ada yang jadi IDM. Ya intinya di Manajemen IDM harus dihapus dulu, baru di Grading bisa dihapus."

**Interpretrasi:** Proteksi penghapusan harus enforce hierarki yang ketat dari atas ke bawah.

---

## Hierarki Penghapusan (Top-Down)

```
LEVEL 3 (Paling Atas)
    ↓ SALE_OUT dari IDM output (IDM-SR proxy)
    ↓ TRANSFER_OUT dari IDM output
    ↓ Transaksi downstream lainnya
    ↓ HARUS DIHAPUS DULU
    ↓

LEVEL 2 (Tengah)
    ↓ Manajemen IDM (master entry)
    ↓ BLOCKED jika Level 3 ada
    ↓ Dihapus setelah Level 3 bersih
    ↓

LEVEL 1 (Paling Bawah)
    ↓ Grading Original (SortingResult)
    ↓ BLOCKED jika Level 2 ada (idm_management_id != NULL)
    ↓ Dihapus terakhir setelah Level 2 dihapus
```

---

## Alur Penjualan IDM (Contoh Konkret)

### Step 1: Grading Dibuat
```
SortingResult #10 (BULU AF, 500g)
  ├─ idm_management_id = NULL (belum dipakai IDM)
  ├─ weight_grams = 500
  └─ Status: SIAP untuk input IDM
```

### Step 2: Dijadikan Input untuk Regrading IDM
```
SortingResult #10
  ├─ idm_management_id = 5 (linked ke IDM Management #5)
  └─ Transactions created:
     └─ IDM_REGRADING_IN: -500g (dari grading original)

IdmManagement #5
  ├─ initial_weight = 500g
  ├─ shrinkage = 50g
  ├─ outputs = 3 (IDM, KAKIAN, PERUTAN)
  └─ Transaction created:
     └─ IDM_REGRADING_OUT: -500g (input ke regrading process)
```

### Step 3: IDM Output Dijual
```
SortingResult #11 (IDM-SR proxy, idm_output_id = 3)
  ├─ 250g terjual
  └─ Transaction created:
     └─ SALE_OUT: -250g (dari IDM output)
     └─ reference_id = NULL (bukan dari IDM, dari penjualan)

Hasil: Level 3 ada ✗ Tidak bisa delete IDM Management
```

### Step 4: Hapus SALE_OUT dari IDM (Level 3)
```
User delete SALE_OUT #xxx dari IDM output
  → System creates SALE_REVERT: +250g
  → Original SALE_OUT soft-deleted
  → Status: Level 3 sudah bersih ✓
```

### Step 5: Hapus IDM Management (Level 2)
```
User delete IdmManagement #5
  → System check hasOutflow() → false ✓
  → System unlink SortingResult #10: idm_management_id = NULL
  → Soft-delete IDM-SR proxy #11
  → Soft-delete IdmOutput #3
  → Status: Level 2 sudah bersih ✓
```

### Step 6: Hapus Grading Original (Level 1)
```
User try delete SALE_OUT dari SortingResult #10
  → idm_management_id = NULL ✓
  → No IDM lock anymore
  → Allowed to delete ✓
```

---

## Proteksi yang Diterapkan

### Level 3 → Level 2 Protection
**Lokasi:** `ManajemenIdmService::delete()`

```php
$this->assertNoOutflow($mgmt, 
    'Tidak bisa hapus — output sudah keluar via transfer/sale. 
     Hapus transfer/sale terlebih dahulu.');
```

**Check Logic:**
```
1. Get semua IdmOutput dari mgmt
2. Get semua proxy SortingResult dari outputs
3. Find SALE_OUT/TRANSFER_OUT dari proxy SR
4. Exclude IDM_REGRADING_OUT (internal)
5. Exclude revert transactions
6. If ANY found → hasOutflow() = true → BLOCK DELETE
```

**Error Message:**
```
"Tidak bisa hapus — output sudah keluar via transfer/sale. 
 Hapus transfer/sale terlebih dahulu."
```

---

### Level 2 → Level 1 Protection
**Lokasi:** `PenjualanController::destroy()`

**Check 1: Direct Check**
```php
if ($sr && !is_null($sr->idm_management_id)) {
    return redirect()->back()->with('error', 
        'Tidak dapat menghapus penjualan dari grading yang sedang 
         di-regrading di Manajemen IDM #' . $sr->idm_management_id);
}
```

**Check 2: Grade-Based Check**
```php
$lockedByIdm = SortingResult::where('grade_company_id', $tx->grade_company_id)
    ->whereNotNull('idm_management_id')
    ->whereNull('deleted_at')
    ->exists();

if ($lockedByIdm) {
    return redirect()->back()->with('error',
        'Tidak dapat menghapus penjualan — grade ini sedang 
         di-regrading di Manajemen IDM');
}
```

**Keuntungan 2-Layer Check:**
- ✓ Catch direct link via sorting_result_id
- ✓ Catch grade-level lock (if multiple batches of same grade)
- ✓ Robust even if sorting_result_id NULL

**Error Messages:**
```
"Tidak dapat menghapus penjualan dari grading yang sedang 
 di-regrading di Manajemen IDM #X. Batalkan proses IDM terlebih dahulu."

"Tidak dapat menghapus penjualan — grade ini sedang di-regrading 
 di Manajemen IDM #X. Batalkan proses IDM terlebih dahulu."
```

---

## Workflow Penghapusan yang Benar

### Skenario: User Ingin Membatalkan Seluruh Proses IDM

```
User ingin cancel regrading IDM yang sudah ada SALE_OUT
```

**Langkah yang BENAR:**

1. ✅ **Buka tab "Riwayat Penjualan dari Manajemen IDM"**
   - Lihat semua SALE_OUT dari IDM output

2. ✅ **Delete semua SALE_OUT dari IDM** (Level 3)
   ```
   TX #xxx (SALE_OUT -250g dari IDM)
     → Click delete button
     → System creates SALE_REVERT +250g
     → BLOCKED if there are related transfers
   ```
   - Repeat untuk setiap SALE_OUT

3. ✅ **Delete IDM Management** (Level 2)
   ```
   IdmManagement #5
     → Check: Apakah semua outflow sudah dihapus?
     → hasOutflow() = false ✓
     → Click delete
     → Automatically unlinks source grading
     → Soft-deletes IDM outputs & proxies
   ```

4. ✅ **Sekarang baru bisa delete Grading** (Level 1)
   ```
   SALE_OUT dari grading original
     → Check: idm_management_id sudah NULL? ✓
     → Click delete
     → Allowed ✓
   ```

---

## Error Messages & Meaning

| Error | Artinya | Solusi |
|-------|---------|--------|
| "Tidak dapat menghapus penjualan dari grading yang sedang di-regrading di Manajemen IDM #X" | SALE_OUT dari grading yang masih linked ke IDM | Hapus IdmManagement #X dulu |
| "Tidak dapat menghapus penjualan — grade ini sedang di-regrading" | Grade ini ada di IDM mana pun | Batalkan semua IDM yang pakai grade ini |
| "Tidak bisa hapus — output sudah keluar via transfer/sale. Hapus transfer/sale terlebih dahulu." | IdmManagement masih punya SALE_OUT dari output-nya | Delete SALE_OUT/TRANSFER_OUT dari IDM output dulu |

---

## Testing Checklist

- [ ] Create IDM Management dengan grading input
- [ ] Create SALE_OUT dari IDM output
- [ ] Try delete SALE_OUT → error (blocked by hasOutflow)
- [ ] Delete SALE_OUT successfully
- [ ] Try delete IdmManagement → success (hasOutflow = false now)
- [ ] Try delete original grading SALE_OUT → success (idm_management_id = NULL)

---

## Files Modified

1. **PenjualanController::destroy()** 
   - Add 2-layer proteksi (direct + grade-based)
   - Block SALE_OUT jika grading linked ke IDM

2. **ManajemenIdmService::hasOutflow()**
   - Improve check logic
   - Add `whereNull('deleted_at')`
   - Better comments

3. **ManajemenIdmService::delete()**
   - Already has `assertNoOutflow()` check
   - Blocks deletion if Level 3 exists
   - Unlinks source grading setelah soft-delete

---

## Key Principles

✅ **Hierarki Ketat:** Delete harus dari atas ke bawah  
✅ **Clear Error Messages:** User tahu harus dihapus apa dulu  
✅ **Data Integrity:** Tidak ada orphaned transactions  
✅ **Soft Deletes:** Audit trail tetap tersimpan  
✅ **Two-Way Linking:** SR ↔ IDM mutual protection  

---

## Kesimpulan

Sistem sekarang **fully enforces deletion order:**

```
Level 3 ← Must delete first
   ↓
Level 2 ← Blocked until Level 3 clean
   ↓
Level 1 ← Blocked until Level 2 clean
```

User tidak bisa delete IDM Management jika masih ada SALE_OUT dari output-nya.  
User tidak bisa delete Grading jika masih di-link ke IDM Management.

**Intinya:** Dari bawah ke atas! Atau dalam hal ini: **Dari IDM ke Grading!** 🎯
