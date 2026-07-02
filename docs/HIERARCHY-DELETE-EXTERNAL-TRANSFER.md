# Deletion Hierarchy — External Transfer Workflow

**Status:** ✅ IMPLEMENTED  
**Date:** 2026-07-02  
**Purpose:** Prevent negative stock when deleting correlated send/receive transactions from wash service

---

## Problem

Workflow "kirim ke jasa cuci" (send to wash) dan "terima dari jasa cuci" (receive from wash) saling berkorelasi dan tidak independent:

```
Send 100g to wash:
  EXTERNAL_TRANSFER_OUT: Gudang Utama -100g
  EXTERNAL_TRANSFER_IN: Jasa Cuci +100g

Receive 80g back from wash:
  RECEIVE_EXTERNAL_OUT: Jasa Cuci -80g
  RECEIVE_EXTERNAL_IN: Gudang Utama +80g

Current stock:
  Gudang Utama: 80g
  Jasa Cuci: 20g
```

**Bug:** User bisa hapus EXTERNAL_TRANSFER meski RECEIVE_EXTERNAL sudah ada → stock jadi minus:

```
Jika hapus EXTERNAL_TRANSFER sebelum hapus RECEIVE_EXTERNAL:
  EXTERNAL_TRANSFER_REVERT_OUT: Gudang Utama +100g → 180g ✓
  EXTERNAL_TRANSFER_REVERT_IN: Jasa Cuci -100g → -80g ✗ NEGATIVE!
```

---

## Hierarchy Protection

```
LEVEL 2 (Send to Wash Service)
    ↓ EXTERNAL_TRANSFER_OUT/IN
    ↓ BLOCKED jika RECEIVE_EXTERNAL sudah ada
    ↓

LEVEL 1 (Receive from Wash Service)
    ↓ RECEIVE_EXTERNAL_OUT/IN
    ↓ Can be deleted freely (cancels receive)
```

---

## Correct Deletion Order

### Skenario: Batalkan seluruh proses jasa cuci

**Langkah yang BENAR:**

1. ✅ **Delete RECEIVE_EXTERNAL (penerimaan) terlebih dahulu**
   ```
   Penerimaan dari Jasa Cuci
     → Click delete button
     → System creates RECEIVE_EXTERNAL_REVERT_* (reversal)
     → Stok kembali ke state sebelum penerimaan
     → Status: RECEIVE_EXTERNAL deleted ✓
   ```

   Hasil setelah step 1:
   ```
   Gudang Utama: 0g (sebelum penerimaan)
   Jasa Cuci: 100g (sebelum penerimaan)
   ```

2. ✅ **Delete EXTERNAL_TRANSFER (pengiriman) sekarang baru bisa**
   ```
   Pengiriman ke Jasa Cuci
     → Check: Apakah ada RECEIVE_EXTERNAL? → No ✓
     → Click delete button
     → System creates EXTERNAL_TRANSFER_REVERT_* (reversal)
     → Stok kembali ke state sebelum pengiriman
     → Status: EXTERNAL_TRANSFER deleted ✓
   ```

   Hasil setelah step 2:
   ```
   Gudang Utama: 100g (original state)
   Jasa Cuci: 0g (original state)
   ```

---

## Implementation Details

### TransferExternalController::destroy()

```php
// ✅ Proteksi: Jangan hapus EXTERNAL_TRANSFER jika sudah ada RECEIVE_EXTERNAL
$hasReceiveExternal = InventoryTransaction::whereIn('transaction_type', 
    ['RECEIVE_EXTERNAL_IN', 'RECEIVE_EXTERNAL_OUT'])
    ->where('reference_id', $transfer->id)
    ->where('is_revert', false)
    ->whereNull('deleted_at')
    ->exists();

if ($hasReceiveExternal) {
    return redirect()->route('barang.keluar.external-transfer.step1')
        ->with('error', 'Tidak dapat menghapus pengiriman ke jasa cuci — 
                 barang sudah diterima kembali. Batalkan penerimaan dari 
                 jasa cuci terlebih dahulu.');
}
```

**Logika:**
1. Check apakah ada RECEIVE_EXTERNAL_IN atau RECEIVE_EXTERNAL_OUT dengan reference_id yang sama
2. Filter: hanya active (is_revert=false, deleted_at=NULL)
3. Jika ada → block deletion dengan pesan jelas

**Error Message:**
```
"Tidak dapat menghapus pengiriman ke jasa cuci — barang sudah diterima kembali. 
 Batalkan penerimaan dari jasa cuci terlebih dahulu."
```

---

## Workflow Examples

### Example 1: Normal Case (Send & Receive)

```
Step 1: Send 100g KAKIAN ke jasa cuci
  EXTERNAL_TRANSFER_OUT: Gudang -100g
  EXTERNAL_TRANSFER_IN: JasaCuci +100g
  Stock: Gudang=0g, JasaCuci=100g

Step 2: Receive 90g KAKIAN dari jasa cuci (10g hilang)
  RECEIVE_EXTERNAL_OUT: JasaCuci -90g
  RECEIVE_EXTERNAL_IN: Gudang +90g
  Stock: Gudang=90g, JasaCuci=10g

Step 3: Try delete pengiriman → BLOCKED ✗
  "Batalkan penerimaan dari jasa cuci terlebih dahulu"

Step 4: Delete penerimaan → SUCCESS ✓
  RECEIVE_EXTERNAL_REVERT_IN: Gudang -90g
  RECEIVE_EXTERNAL_REVERT_OUT: JasaCuci +90g
  Stock: Gudang=0g, JasaCuci=100g (back to after EXTERNAL_TRANSFER)

Step 5: Delete pengiriman → SUCCESS ✓
  EXTERNAL_TRANSFER_REVERT_OUT: Gudang +100g
  EXTERNAL_TRANSFER_REVERT_IN: JasaCuci -100g
  Stock: Gudang=100g, JasaCuci=0g (original state)
```

### Example 2: Send Without Receive

```
Step 1: Send 100g KAKIAN ke jasa cuci
  EXTERNAL_TRANSFER_OUT: Gudang -100g
  EXTERNAL_TRANSFER_IN: JasaCuci +100g
  Stock: Gudang=0g, JasaCuci=100g

Step 2: Delete pengiriman → SUCCESS ✓
  (No RECEIVE_EXTERNAL exists)
  EXTERNAL_TRANSFER_REVERT_OUT: Gudang +100g
  EXTERNAL_TRANSFER_REVERT_IN: JasaCuci -100g
  Stock: Gudang=100g, JasaCuci=0g
```

---

## Transaction Types Involved

| Type | Sign | Location | Purpose |
|------|------|----------|---------|
| EXTERNAL_TRANSFER_OUT | -ve | Gudang Utama | Goods leaving |
| EXTERNAL_TRANSFER_IN | +ve | Jasa Cuci | Goods arriving at service |
| RECEIVE_EXTERNAL_OUT | -ve | Jasa Cuci | Goods leaving service |
| RECEIVE_EXTERNAL_IN | +ve | Gudang Utama | Goods returning to warehouse |
| EXTERNAL_TRANSFER_REVERT_OUT | +ve | Gudang Utama | Reversal if send deleted |
| EXTERNAL_TRANSFER_REVERT_IN | -ve | Jasa Cuci | Reversal if send deleted |
| RECEIVE_EXTERNAL_REVERT_IN | -ve | Gudang Utama | Reversal if receive deleted |
| RECEIVE_EXTERNAL_REVERT_OUT | +ve | Jasa Cuci | Reversal if receive deleted |

---

## User-Facing Error Messages

| Scenario | Error | Action |
|----------|-------|--------|
| Delete EXTERNAL_TRANSFER, but RECEIVE_EXTERNAL exists | "Tidak dapat menghapus pengiriman ke jasa cuci — barang sudah diterima kembali. Batalkan penerimaan dari jasa cuci terlebih dahulu." | Delete RECEIVE_EXTERNAL first |
| Delete RECEIVE_EXTERNAL (any time) | No block, allowed | Can delete anytime |

---

## Related Files

- `app/Http/Controllers/Feature/TransferExternalController.php` → destroy() — EXTERNAL_TRANSFER deletion with protection
- `app/Http/Controllers/Feature/ReceiveExternalController.php` → destroy() — RECEIVE_EXTERNAL deletion (no protection needed)
- `app/Services/BarangKeluar/BarangKeluarService.php` → externalTransfer() & receiveExternal() — create transactions
- `resources/views/admin/barang-keluar/external-transfer-step1.blade.php` — UI for sending
- `resources/views/admin/barang-keluar/receive-external-step1.blade.php` — UI for receiving

---

## Testing Checklist

- [ ] Send 100g to wash service → stok Gudang -100, stok Jasa Cuci +100
- [ ] Receive 80g back from wash → stok Gudang +80, stok Jasa Cuci -80
- [ ] Try delete pengiriman → error "Batalkan penerimaan terlebih dahulu"
- [ ] Delete penerimaan → success, stok reverts
- [ ] Delete pengiriman → success, stok back to original
- [ ] Send and delete without receiving → should work (no RECEIVE_EXTERNAL)
- [ ] Check KAKIAN grade used in testing has correct final stock

---

## Key Principles

✅ **Deletion Order:** RECEIVE_EXTERNAL → EXTERNAL_TRANSFER  
✅ **Stock Protection:** No negative stock when deleting in correct order  
✅ **Clear Messages:** User knows exactly what to delete first  
✅ **Audit Trail:** All reversals logged with is_revert=true  

---

**Summary:** External transfer workflow sekarang fully protected dari negative stock. User harus delete penerimaan dulu sebelum bisa delete pengiriman. ✅
