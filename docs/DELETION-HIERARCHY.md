# Deletion Hierarchy — Bottom-to-Top Protection

**Status:** ✅ IMPLEMENTED  
**Date:** 2026-07-02  
**Purpose:** Prevent data corruption by enforcing deletion order from highest to lowest level

---

## Overview

The system implements a **bottom-to-top deletion hierarchy** to protect data integrity. Once a grading batch has transactions, it becomes "locked" at lower levels until all upstream transactions are deleted first.

```
LEVEL 3 (Top)      ← SALE_OUT from IDM-SR, other downstream txs
                       ↑ must delete first
LEVEL 2 (Middle)   ← SALE_OUT, IDM_REGRADING_IN, TRANSFER_OUT from grading
                       ↑ can only delete after Level 3 is gone
LEVEL 1 (Bottom)   ← SortingResult (Grading batch created)
                       ↑ can only delete after Level 2 is gone
```

---

## Deletion Hierarchy Explained

### Level 1: SortingResult (Grading Batch)
**When created:** After grading process completes
**Status:** Locked if any transactions exist

```sql
SortingResult created
├─ idm_management_id = NULL (regular grading)
└─ idm_output_id = NULL (not an IDM proxy)
```

**Cannot be deleted if:**
- Has any SALE_OUT transactions (Level 2)
- Is marked as input for IDM regrading (idm_management_id != NULL)
- Has any transfer transactions (TRANSFER_OUT)

---

### Level 2: Transactions from Grading
**When created:** When user performs action on grading batch

#### SALE_OUT (Regular Grading Sale)
```
SortingResult (grading)
└─ SALE_OUT created
   └─ quantity_change_grams = -100 (100g sold)
```

**Cannot be deleted if:**
- SortingResult is marked as input for IDM (idm_management_id != NULL)
- SortingResult has IDM_REGRADING_IN transaction
- Has downstream transactions (Level 3)

#### IDM_REGRADING_IN (Set as IDM Input)
```
SortingResult (grading)
└─ IDM_REGRADING_IN created
   └─ quantity_change_grams = -200 (200g sent to IDM)
```

**Cannot be deleted if:**
- Associated IdmManagement has output that was sold (Level 3 exists)
- Output transactions exist (SALE_OUT from IDM-SR proxy)

#### TRANSFER_OUT (Internal Transfer)
```
SortingResult (grading)
└─ TRANSFER_OUT created
   └─ quantity_change_grams = -50 (50g transferred)
```

**Cannot be deleted if:**
- Has downstream transactions from transfer destination

---

### Level 3: Downstream Transactions
**When created:** Result of Level 2 actions

#### SALE_OUT from IDM Output (IDM-SR Proxy)
```
IdmManagement (regrading)
└─ IdmOutput (regrading result)
   └─ SortingResult proxy (idm_output_id set)
      └─ SALE_OUT created (100g sold from IDM output)
```

**Can be deleted freely** (no protection needed, it's the top level)

#### Other Downstream Transactions
```
Location A (after transfer)
└─ Inventory at Location A increased
   └─ Can be sold, transferred further, etc.
```

**Can be deleted** until those actions reference this transaction

---

## Deletion Workflow

### Scenario: Grading → IDM Regrading → Sale from IDM

**Initial state:**
```
SortingResult #4 (IDM grade, 200g)
├─ IDM_REGRADING_IN (200g → IDM management)
├─ Linked to: IdmManagement #1
└─ IdmOutput #1 (IDM output from regrading)
   └─ SortingResult proxy #5
      └─ SALE_OUT (100g sold from IDM output)
```

**To delete the SALE_OUT (Level 3):**
1. User clicks delete on SALE_OUT from IDM-SR
2. System checks: no downstream transactions ✓
3. System creates SALE_REVERT (100g refund)
4. **Status:** SALE_OUT deleted ✓

**After Level 3 deletion:**
```
SortingResult #4 (still locked)
├─ IDM_REGRADING_IN
└─ IdmManagement #1 (still active)
```

**To delete IDM_REGRADING_IN (Level 2):**
1. User tries to delete in PenjualanController
2. System checks: "Does SortingResult #4 have IDM_REGRADING_IN?" → Yes ✓
3. System checks: "Does IdmManagement #1 have outflow?" → No ✓
4. System blocks: "Tidak dapat menghapus — output sudah keluar via sale"

**To delete IdmManagement #1 (Level 2 - IDM side):**
1. User deletes IdmManagement in ManajemenIdmService
2. System calls `assertNoOutflow()`: checks if SALE_OUT from IDM-SR exists
3. If exists: Block with "Tidak bisa hapus — output sudah keluar via transfer/sale. Hapus transfer/sale terlebih dahulu."
4. **Status:** IdmManagement NOT deleted, user must delete SALE_OUT first

**Correct deletion order:**
1. ✅ Delete SALE_OUT from IDM-SR (Level 3) → creates SALE_REVERT
2. ✅ Then delete IDM_REGRADING_IN from original grading (Level 2)
3. ✅ Then delete IdmManagement #1 (Level 2)
4. ✅ Then can delete or re-use original SortingResult #4 (Level 1)

---

## Protection Mechanisms

### 1. PenjualanController::destroy()
**Protects:** SALE_OUT from grading

```php
// Check: Is this grading being used for IDM regrading?
if ($sr && !is_null($sr->idm_management_id)) {
    throw Exception("Batalkan proses IDM terlebih dahulu");
}

// Check: Does SortingResult have "locking" transactions?
if ($hasIdmLock || $lockingTransactions) {
    throw Exception("Batalkan proses yang lebih atas terlebih dahulu");
}
```

### 2. ManajemenIdmService::delete()
**Protects:** IdmManagement entry

```php
// Check: Has output been sold or transferred?
$this->assertNoOutflow($mgmt, "Hapus transfer/sale terlebih dahulu");

// Blocks deletion if Level 3 transactions exist
```

### 3. ManajemenIdmService::hasOutflow()
**Detects:** Any negative transaction from IDM-SR proxy (excluding reversal)

```php
// Finds all SALE_OUT/TRANSFER_OUT from IDM output
// Returns: true if any outflow exists → blocks deletion
```

---

## Error Messages

| Scenario | Error Message |
|----------|---------------|
| Delete SALE_OUT, grading is IDM input | "Tidak dapat menghapus penjualan dari grading yang sedang di-regrading di Manajemen IDM. Batalkan proses IDM terlebih dahulu." |
| Delete transaction, SortingResult has locking tx | "Tidak dapat menghapus transaksi dari grading yang sedang diproses di tingkat yang lebih atas. Batalkan proses yang lebih atas terlebih dahulu." |
| Delete IdmManagement, output was sold | "Tidak bisa hapus — output sudah keluar via transfer/sale. Hapus transfer/sale terlebih dahulu." |

---

## Best Practices

### For Users
1. **Delete downstream first:** Always delete higher-level transactions before lower-level
2. **Follow error messages:** If blocked, read message to understand what must be deleted first
3. **Check transaction status:** View history tabs to see what transactions exist

### For Developers
1. **Always check downstream:** Before allowing deletion at Level 2, verify Level 3 doesn't exist
2. **Use assertNoOutflow pattern:** Reuse this method when adding new deletion points
3. **Provide clear errors:** Error message should indicate what must be deleted first

---

## Related Files

- `app/Http/Controllers/Feature/PenjualanController.php` — SALE_OUT deletion protection
- `app/Services/Idm/ManajemenIdmService.php` — IDM deletion protection
- `resources/views/admin/barang-keluar/sell.blade.php` — Transaction history display

---

## Testing

### Test Case 1: Delete SALE_OUT with IDM Lock
```
1. Create grading batch
2. Set as IDM input (creates IDM_REGRADING_IN)
3. Attempt to delete SALE_OUT → Should be blocked
4. Expected: Error "Batalkan proses IDM terlebih dahulu"
```

### Test Case 2: Delete IDM with Outflow
```
1. Create IdmManagement with input grading
2. Create SALE_OUT from IDM output
3. Attempt to delete IdmManagement → Should be blocked
4. Expected: Error "Hapus transfer/sale terlebih dahulu"
```

### Test Case 3: Correct Deletion Order
```
1. Create full flow: Grading → IDM → Sale from IDM
2. Delete SALE_OUT from IDM output ✓
3. Delete IDM_REGRADING_IN from grading ✓
4. Delete IdmManagement ✓
5. All deletions succeed when done in correct order
```

---

## Summary

The **bottom-to-top deletion hierarchy** ensures:
- ✅ Data integrity: No orphaned transactions
- ✅ Audit trail: Preserves history of all changes
- ✅ User guidance: Clear error messages explain deletion order
- ✅ Business logic: Prevents operations on locked resources

**Key principle:** Delete from highest level (Level 3) down to lowest (Level 1).
