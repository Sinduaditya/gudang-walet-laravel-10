# Barang Keluar - Implementation Plan

## Tujuan
Menghilangkan potential minus stok di modul Barang Keluar dengan:
1. Menghapus fungsi EDIT dari semua modul (paling signifikan)
2. Memperbaiki fungsi DELETE agar stok di-revert
3. Menambahkan validasi atomic untuk race condition

---

## Priority Matrix

| Priority | Issue | Effort | Impact |
|----------|-------|--------|--------|
| P0 | Remove ALL Edit Functions | Low | HIGH - eliminates most minus risk |
| P1 | Fix Delete dengan Stock Revert | Medium | HIGH - prevents orphaned minus |
| P2 | Add Atomic Stock Check | High | MEDIUM - prevents race condition |
| P3 | Fix getAvailableStock negative | Low | MEDIUM - prevents uncontrolled minus |

---

## Implementation Phases

### Phase 1: Remove Edit Functionality (Effort: Low, Impact: HIGH)

#### 1.1 PenjualanController
**Files**: `app/Http/Controllers/Feature/PenjualanController.php`
**Actions**:
- Hapus method `edit()` (lines 170-178)
- Hapus method `update()` (lines 181-199)
- Hapus route `edit` dan `update` di `routes/web.php`
- Hapus view `sell-edit.blade.php`

#### 1.2 TransferInternalController
**Files**: `app/Http/Controllers/Feature/TransferInternalController.php`
**Actions**:
- Hapus method `edit()` (lines 272-290)
- Hapus method `update()` (lines 292-330)
- Hapus route edit dan update di web.php
- Hapus view `transfer-edit.blade.php`

#### 1.3 TransferExternalController
**Files**: `app/Http/Controllers/Feature/TransferExternalController.php`
**Actions**:
- Hapus method `edit()` (lines 250-274)
- Hapus method `update()` (lines 276-314)
- Hapus route edit dan update di web.php
- Hapus view `external-transfer-edit.blade.php`

#### 1.4 ReceiveExternalController
**Files**: `app/Http/Controllers/Feature/ReceiveExternalController.php`
**Actions**:
- Hapus method `edit()` (lines 360-388)
- Hapus method `update()` (lines 390-433)
- Hapus route edit dan update di web.php
- Hapus view `receive-external-edit.blade.php`

#### 1.5 TransferIdmController
**Files**: `app/Http/Controllers/Feature/TransferIdmController.php`
**Actions**:
- Hapus method `edit()` (lines 198-207)
- Hapus method `update()` (lines 209-260)
- Hapus route edit dan update di web.php
- Hapus view `edit.blade.php`

---

### Phase 2: Fix Delete dengan Stock Revert (Effort: Medium, Impact: HIGH)

#### 2.1 PenjualanController::destroy()
**File**: `app/Http/Controllers/Feature/PenjualanController.php`
**Current Code** (lines 201-211):
```php
public function destroy($id)
{
    try {
        $tx = InventoryTransaction::findOrFail($id);
        $tx->delete();
        return redirect()->route('barang.keluar.sell.form')->with('success', 'Transaksi penjualan dihapus.');
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('PenjualanController destroy error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transaksi.');
    }
}
```

**New Code**:
```php
public function destroy($id)
{
    try {
        return DB::transaction(function () use ($id) {
            $tx = InventoryTransaction::lockForUpdate()->findOrFail($id);
            $oldQuantity = $tx->quantity_change_grams; // should be negative (e.g., -50)
            
            // Create reversal transaction (positive value to offset the negative)
            InventoryTransaction::create([
                'transaction_date' => now(),
                'grade_company_id' => $tx->grade_company_id,
                'location_id' => $tx->location_id,
                'quantity_change_grams' => abs($oldQuantity), // positive to revert
                'supplier_id' => $tx->supplier_id,
                'transaction_type' => 'SALE_REVERT',
                'reference_id' => $tx->id,
                'sorting_result_id' => $tx->sorting_result_id,
                'created_by' => auth()->id(),
            ]);
            
            $tx->deleted_by = auth()->id();
            $tx->save();
            $tx->delete();
            
            return redirect()->route('barang.keluar.sell.form')
                ->with('success', 'Transaksi penjualan dihapus dan stok dikembalikan.');
        });
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('PenjualanController destroy error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transaksi.');
    }
}
```

#### 2.2 TransferInternalController::destroy()
**File**: `app/Http/Controllers/Feature/TransferInternalController.php`
**Current Code** (lines 332-355):
```php
public function destroy($id)
{
    try {
        $transfer = \App\Models\StockTransfer::findOrFail($id);

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->transactions as $transaction) {
                $transaction->deleted_by = auth()->id();
                $transaction->save();
                $transaction->delete();
            }

            $transfer->deleted_by = auth()->id();
            $transfer->save();
            $transfer->delete();
        });

        return redirect()->route('barang.keluar.transfer.step1')
            ->with('success', 'Transfer internal berhasil dihapus dan stok dikembalikan.');
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('TransferInternalController destroy error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transfer.');
    }
}
```

**New Code**:
```php
public function destroy($id)
{
    try {
        return DB::transaction(function () use ($id) {
            $transfer = \App\Models\StockTransfer::lockForUpdate()->findOrFail($id);
            $userId = auth()->id();
            
            // Calculate total deduction (weight + susut)
            $totalDeduction = abs($transfer->weight_grams) + abs($transfer->susut_grams ?? 0);
            
            // Create reversal for TRANSFER_OUT (at from_location)
            $outTx = $transfer->transactions()
                ->where('transaction_type', 'TRANSFER_OUT')
                ->first();
            
            if ($outTx) {
                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $transfer->grade_company_id,
                    'location_id' => $transfer->from_location_id,
                    'supplier_id' => $outTx->supplier_id,
                    'quantity_change_grams' => $totalDeduction, // positive to revert
                    'transaction_type' => 'TRANSFER_REVERT_OUT',
                    'reference_id' => $transfer->id,
                    'sorting_result_id' => $transfer->sorting_result_id,
                    'created_by' => $userId,
                ]);
            }
            
            // Create reversal for TRANSFER_IN (at to_location) if not DMK
            $inTx = $transfer->transactions()
                ->where('transaction_type', 'TRANSFER_IN')
                ->first();
            
            $toLocation = Location::find($transfer->to_location_id);
            if ($inTx && $toLocation && stripos($toLocation->name, 'DMK') === false) {
                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $transfer->grade_company_id,
                    'location_id' => $transfer->to_location_id,
                    'supplier_id' => $inTx->supplier_id,
                    'quantity_change_grams' => -abs($transfer->weight_grams), // negative to revert IN
                    'transaction_type' => 'TRANSFER_REVERT_IN',
                    'reference_id' => $transfer->id,
                    'sorting_result_id' => $transfer->sorting_result_id,
                    'created_by' => $userId,
                ]);
            }
            
            // Soft delete original transactions and transfer
            foreach ($transfer->transactions as $transaction) {
                $transaction->deleted_by = $userId;
                $transaction->save();
                $transaction->delete();
            }
            
            $transfer->deleted_by = $userId;
            $transfer->save();
            $transfer->delete();
            
            return redirect()->route('barang.keluar.transfer.step1')
                ->with('success', 'Transfer internal berhasil dihapus dan stok dikembalikan.');
        });
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('TransferInternalController destroy error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transfer.');
    }
}
```

#### 2.3 TransferExternalController::destroy()
**File**: `app/Http/Controllers/Feature/TransferExternalController.php`
**New Code**:
```php
public function destroy($id)
{
    try {
        return DB::transaction(function () use ($id) {
            $transfer = \App\Models\StockTransfer::lockForUpdate()->findOrFail($id);
            $userId = auth()->id();
            
            $totalDeduction = abs($transfer->weight_grams) + abs($transfer->susut_grams ?? 0);
            
            // Get original transactions
            $outTx = $transfer->transactions()
                ->where('transaction_type', 'EXTERNAL_TRANSFER_OUT')
                ->first();
            $inTx = $transfer->transactions()
                ->where('transaction_type', 'EXTERNAL_TRANSFER_IN')
                ->first();
            
            // Create reversal for EXTERNAL_TRANSFER_OUT at Gudang Utama
            if ($outTx) {
                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $transfer->grade_company_id,
                    'location_id' => $transfer->from_location_id,
                    'supplier_id' => $outTx->supplier_id,
                    'quantity_change_grams' => $totalDeduction, // positive to revert
                    'transaction_type' => 'EXTERNAL_TRANSFER_REVERT_OUT',
                    'reference_id' => $transfer->id,
                    'sorting_result_id' => $transfer->sorting_result_id,
                    'created_by' => $userId,
                ]);
            }
            
            // Create reversal for EXTERNAL_TRANSFER_IN at Jasa Cuci
            if ($inTx) {
                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $transfer->grade_company_id,
                    'location_id' => $transfer->to_location_id,
                    'supplier_id' => $inTx->supplier_id,
                    'quantity_change_grams' => -abs($transfer->weight_grams), // negative to revert IN
                    'transaction_type' => 'EXTERNAL_TRANSFER_REVERT_IN',
                    'reference_id' => $transfer->id,
                    'sorting_result_id' => $transfer->sorting_result_id,
                    'created_by' => $userId,
                ]);
            }
            
            // Soft delete
            $transfer->transactions()->delete();
            $transfer->deleted_by = $userId;
            $transfer->save();
            $transfer->delete();
            
            return redirect()->route('barang.keluar.external-transfer.step1')
                ->with('success', 'Transfer eksternal berhasil dihapus dan stok dikembalikan.');
        });
    } catch (\Exception $e) {
        Log::error('TransferExternalController destroy error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transfer.');
    }
}
```

#### 2.4 ReceiveExternalController::destroy()
**File**: `app/Http/Controllers/Feature/ReceiveExternalController.php`
**New Code**:
```php
public function destroy($id)
{
    try {
        return DB::transaction(function () use ($id) {
            $transfer = \App\Models\StockTransfer::lockForUpdate()->findOrFail($id);
            $userId = auth()->id();
            
            $totalDeduction = abs($transfer->weight_grams) + abs($transfer->susut_grams ?? 0);
            
            // Get original transactions
            $inTx = $transfer->transactions()
                ->where('transaction_type', 'RECEIVE_EXTERNAL_IN')
                ->first();
            $outTx = $transfer->transactions()
                ->where('transaction_type', 'RECEIVE_EXTERNAL_OUT')
                ->first();
            
            // Create reversal for RECEIVE_EXTERNAL_IN (at Gudang Utama) - NEGATIVE to remove stock
            if ($inTx) {
                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $transfer->grade_company_id,
                    'location_id' => $transfer->to_location_id,
                    'supplier_id' => $inTx->supplier_id,
                    'quantity_change_grams' => -abs($transfer->weight_grams), // negative to remove stock
                    'transaction_type' => 'RECEIVE_EXTERNAL_REVERT_IN',
                    'reference_id' => $transfer->id,
                    'sorting_result_id' => $transfer->sorting_result_id,
                    'created_by' => $userId,
                ]);
            }
            
            // Create reversal for RECEIVE_EXTERNAL_OUT (at Jasa Cuci) - POSITIVE to return stock
            if ($outTx) {
                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $transfer->grade_company_id,
                    'location_id' => $transfer->from_location_id,
                    'supplier_id' => $outTx->supplier_id,
                    'quantity_change_grams' => $totalDeduction, // positive to return
                    'transaction_type' => 'RECEIVE_EXTERNAL_REVERT_OUT',
                    'reference_id' => $transfer->id,
                    'sorting_result_id' => $transfer->sorting_result_id,
                    'created_by' => $userId,
                ]);
            }
            
            // Soft delete
            $transfer->transactions()->delete();
            $transfer->deleted_by = $userId;
            $transfer->save();
            $transfer->delete();
            
            return redirect()->route('barang.keluar.receive-external.step1')
                ->with('success', 'Penerimaan berhasil dihapus dan stok dikembalikan.');
        });
    } catch (\Exception $e) {
        Log::error('ReceiveExternalController destroy error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data.');
    }
}
```

#### 2.5 TransferIdmController::destroy()
**File**: `app/Http/Controllers/Feature/TransferIdmController.php`
**Note**: We need to check TransferIdmService for the implementation

---

### Phase 3: Fix getAvailableStock Negative Issue (Effort: Low, Impact: MEDIUM)

**File**: `app/Services/BarangKeluar/BarangKeluarService.php`

**Current Code** (lines 398-404):
```php
public function getAvailableStock(int $gradeCompanyId, int $locationId): float
{
    return (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
        ->where('location_id', $locationId)
        ->sum('quantity_change_grams');
}
```

**New Code**:
```php
public function getAvailableStock(int $gradeCompanyId, int $locationId): float
{
    $stock = (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
        ->where('location_id', $locationId)
        ->sum('quantity_change_grams');
    
    // Warn if stock goes negative (indicates data integrity issue)
    if ($stock < 0) {
        \Illuminate\Support\Facades\Log::warning('Negative stock detected', [
            'grade_company_id' => $gradeCompanyId,
            'location_id' => $locationId,
            'stock' => $stock,
        ]);
    }
    
    return $stock; // Return actual value (can be negative) for validation purposes
}
```

Also add a helper method for display:
```php
public function getDisplayStock(int $gradeCompanyId, int $locationId): float
{
    return max(0, $this->getAvailableStock($gradeCompanyId, $locationId));
}
```

---

### Phase 4: Add Atomic Stock Check (Effort: High, Impact: MEDIUM)

**File**: `app/Services/BarangKeluar/BarangKeluarService.php`

Add locking mechanism to prevent race conditions:

```php
public function sellWithLock(array $data): InventoryTransaction
{
    return DB::transaction(function () use ($data) {
        $userId = Auth::id();
        $sortingResultId = $data['sorting_result_id'] ?? null;
        
        // Lock the sorting result row for update
        $sortingResult = SortingResult::lockForUpdate()->find($sortingResultId);
        
        // Lock inventory transactions for this grade and location
        $lockedTx = InventoryTransaction::where('grade_company_id', $data['grade_company_id'])
            ->where('location_id', $data['location_id'])
            ->lockForUpdate()
            ->get();
        
        // Now validate stock with locked data
        $batchRemaining = $this->getBatchRemainingStock($sortingResultId, $data['location_id']);
        if ($batchRemaining < $data['weight_grams']) {
            throw new \Exception('Stok batch tidak mencukupi');
        }
        
        if (!$this->hasEnoughStock($data['grade_company_id'], $data['location_id'], $data['weight_grams'])) {
            throw new \Exception('Stok fisik tidak mencukupi');
        }
        
        // Proceed with insert
        $supplierId = $this->getSupplierIdFromSortingResult($sortingResultId);
        
        return InventoryTransaction::create([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'grade_company_id' => $data['grade_company_id'],
            'location_id' => $data['location_id'],
            'quantity_change_grams' => -abs($data['weight_grams']),
            'supplier_id' => $supplierId,
            'transaction_type' => 'SALE_OUT',
            'reference_id' => null,
            'sorting_result_id' => $sortingResultId,
            'created_by' => $userId,
        ]);
    });
}
```

Note: Full atomic locking may impact performance. Consider implementing at critical points only.

---

## Files to Delete

After removing edit functionality:
1. `resources/views/admin/barang-keluar/sell-edit.blade.php`
2. `resources/views/admin/barang-keluar/transfer-edit.blade.php`
3. `resources/views/admin/barang-keluar/external-transfer-edit.blade.php`
4. `resources/views/admin/barang-keluar/receive-external-edit.blade.php`
5. `resources/views/admin/transfer-idm/edit.blade.php`

---

## Testing Checklist

### After Phase 1 (Remove Edit):
- [ ] Penjualan: Pastikan button Edit tidak muncul di UI
- [ ] Transfer Internal: Pastikan button Edit tidak muncul di UI
- [ ] Transfer External: Pastikan button Edit tidak muncul di UI
- [ ] Receive External: Pastikan button Edit tidak muncul di UI
- [ ] Transfer IDM: Pastikan button Edit tidak muncul di UI

### After Phase 2 (Fix Delete):
- [ ] Penjualan: Delete transaksi, verify stok kembali
- [ ] Transfer Internal: Delete transfer, verify stok di kedua lokasi kembali
- [ ] Transfer External: Delete transfer, verify stok di Gudang Utama dan Jasa Cuci kembali
- [ ] Receive External: Delete receive, verify stok di Gudang Utama dan Jasa Cuci kembali
- [ ] Transfer IDM: Delete transfer, verify IdmDetails kembali available

### After Phase 3 (Fix getAvailableStock):
- [ ] Verify minus stock trigger warning logs
- [ ] Verify UI display shows 0 for negative stock (using getDisplayStock)

### After Phase 4 (Atomic Lock):
- [ ] Concurrent sell test: 2 users sell same batch simultaneously
- [ ] Verify no minus occurs under race conditions

---

## Rollback Plan

If issues arise:
1. Revert controller changes using git
2. Restore deleted views from git
3. Review logs for specific failure points

---

*Document created: 2026-05-09*
*Project: gudang-walet-laravel-10*
