# Analisis Tracking Stok

## Overview

Tracking Stok adalah fitur untuk melihat dan memantau stok barang berdasarkan parent grade company, grade company, dan lokasi penyimpanan. Fitur ini berbeda dengan modul grading atau barang masuk — fokusnya hanya pada **baca/monitoring** stok, bukan input transaksi.

---

## Tabel yang Digunakan

| Tabel | Peran | Operasi |
|-------|-------|---------|
| `parent_grade_companies` | Header parent grade (misal: "Walet", "Madu") | Read |
| `grades_company` | Grade company (produk akhir) | Read |
| `inventory_transactions` | Akumulasi stok per grade + lokasi + supplier | Read (SUM) |
| `sort_materials` | Data bahan sortir per parent grade | Read |
| `stock_transfers` | Riwayat transfer (untuk susut) | Read |
| `locations` | Lokasi gudang | Read |

### Tabel yang Ada di Model tapi TIDAK Digunakan di Tracking Stok

| Tabel | Catatan |
|-------|---------|
| `sales` | Tidak digunakan — hanya baca stok, bukan transaksi |
| `sale_items` | Tidak digunakan — hanya baca stok |
| `receipt_items` | Tidak digunakan — fokus ke stok yang sudah masuk inventory |
| `sorting_results` | Tidak digunakan langsung — hanya lewat inventory_transactions |
| `suppliers` | Tidak digunakan — hanya tampil di view sebagai info tambahan |

---

## Controller, Service, dan Blade

### Controller
- `app/Http/Controllers/Feature/TrackingStockController.php`

### Service
- `app/Services/Stock/TrackingStockService.php`

### Blade Views
| File | Fungsi |
|------|--------|
| `resources/views/admin/stock/index.blade.php` | Daftar parent grade dengan link ke grades dan sorts |
| `resources/views/admin/stock/parent-grades.blade.php` | Daftar child grade company di bawah satu parent |
| `resources/views/admin/stock/parent-sorts.blade.php` | Daftar sort materials di bawah satu parent |
| `resources/views/admin/stock/detail.blade.php` | Detail stok per lokasi untuk satu grade company |
| `resources/views/admin/stock/susut.blade.php` | Riwayat transfer/penyusutan untuk satu grade |
| `resources/views/admin/stock/tracking.blade.php` | (file ada, perlu dicek fungsinya) |

### Routes
```
GET  /tracking-stock/                      → index (list parent grades)
GET  /tracking-stock/parent/{id}/grades   → parentGrades
GET  /tracking-stock/parent/{id}/sorts    → parentSorts
GET  /tracking-stock/{id}                  → detail (grade company detail)
GET  /tracking-stock/{id}/susut           → susut (stock transfer history)
```

---

## Alur Kerja & Struktur Data

```
TrackingStockController
    │
    ├── index()         → list semua parent grade companies
    │                       └─ TrackingStockService::getParentGradeCompany()
    │
    ├── parentGrades($id)
    │   ├─ get parent grade by ID
    │   ├─ list child grades (grade_companies)
    │   ├─ calculateGlobalStock() per child grade
    │   ├─ calculateParentGlobalStock() — SUM semua child
    │   ├─ calculateParentPositiveStock()
    │   └─ calculateParentNegativeStock()
    │
    ├── parentSorts($id)
    │   ├─ get parent grade
    │   ├─ list sort_materials
    │   ├─ calculateParentGlobalStock()
    │   └─ calculateParentSortStock() — SUM(sort_materials.weight)
    │
    ├── detail($id)
    │   ├─ get grade company by ID
    │   ├─ getStockPerLocation() — SUM(quantity_change_grams) GROUP BY location_id, supplier_id
    │   └─ calculate global stock dari hasil query
    │
    └── susut($id)
        ├─ get grade company
        ├─ getAllLocations()
        └─ getSusutHistory() — StockTransfer dengan filter tanggal/lokasi
```

---

## Logika Perhitungan Stok

### Global Stock (per grade company)

```php
// TrackingStockService::calculateGlobalStock()
return (int) round(
    InventoryTransaction::where('grade_company_id', $gradeId)
        ->sum('quantity_change_grams')
);
```

**Cara kerja:** Menjumlahkan semua `quantity_change_grams` di `inventory_transactions` untuk grade tertentu. Nilai bisa positif (barang masuk) atau negatif (barang keluar). **Tidak ada filter lokasi** — ini total keseluruhan.

---

### Parent Global Stock

```php
// TrackingStockService::calculateParentGlobalStock()
$gradeIds = GradeCompany::where('parent_grade_company_id', $parentId)->pluck('id');
return (int) round(
    InventoryTransaction::whereIn('grade_company_id', $gradeIds)
        ->sum('quantity_change_grams')
);
```

Menjumlahkan semua child grades dalam satu parent.

---

### Positive Stock vs Negative Stock

```php
// calculateParentPositiveStock() dan calculateParentNegativeStock()
foreach ($gradeIds as $id) {
    $gradeStock = InventoryTransaction::where('grade_company_id', $id)
        ->selectRaw('location_id, SUM(quantity_change_grams) as total')
        ->groupBy('location_id')
        ->get()
        ->filter(fn($t) => $t->total > 0)  // atau < 0 untuk negative
        ->sum('total');
}
```

**Perbedaan dengan Global Stock:** Perhitungan ini GROUP BY location_id terlebih dahulu, baru dijumlahkan. Artinya:
- Positive stock = hanya lokasi yang punya total > 0
- Negative stock = hanya lokasi yang punya total < 0

Ini模拟 "Gross Stock" yang diharapkan user — hanya lokasi dengan stok positif.

---

### Stock Per Location

```php
// TrackingStockService::getStockPerLocation()
$query = InventoryTransaction::query()
    ->select('location_id', 'supplier_id')
    ->selectRaw('SUM(quantity_change_grams) as total_stock')
    ->where('grade_company_id', $gradeId)
    ->groupBy('location_id', 'supplier_id');
```

Returns Collection dengan struktur:
- `location_id` — ID lokasi
- `supplier_id` — ID supplier
- `total_stock` — jumlah stok di lokasi itu dari supplier itu
- `location` — relasi Location (eager loaded)
- `supplier` — relasi Supplier (eager loaded)

---

## Inventory Transactions Schema

```sql
inventory_transactions
├── id                        (bigint, PK)
├── transaction_date          (datetime) — tanggal transaksi
├── grade_company_id          (bigint, FK) — produk apa
├── location_id              (bigint, FK) — disimpan di mana
├── supplier_id              (bigint, FK) — asal dari siapa
├── quantity_change_grams    (float) — perubahan stok (positif/negatif)
├── transaction_type         (string) — jenis transaksi
│   ├── GRADING_IN          — stok masuk dari grading
│   ├── SALE_OUT            — stok keluar karena penjualan
│   ├── TRANSFER_OUT        — stok keluar karena transfer
│   ├── TRANSFER_IN         — stok masuk dari transfer
│   └── IDM_TRANSFER_OUT/IN
├── reference_id             (bigint, nullable) — ID transaksi asli
├── sorting_result_id        (bigint, FK, nullable) — dari sorting hasil apa
├── created_by               (bigint, FK)
├── created_at
├── updated_at
├── deleted_at               (soft delete)
└── deleted_by               (bigint, FK, nullable)
```

### Transaction Types

| Type | Effect | Source |
|------|--------|--------|
| GRADING_IN | +quantity | GradingGoodsService::createInventoryFromGrading() |
| SALE_OUT | -quantity | BarangKeluarService (jual langsung) |
| TRANSFER_OUT | -quantity | TransferIdmService, TransferInternalService |
| TRANSFER_IN | +quantity | ReceiveInternalController, ReceiveExternalController |
| IDM_TRANSFER_OUT | -quantity | TransferIdmService |
| IDM_TRANSFER_IN | +quantity | ReceiveExternalController |

---

## Analisis Permasalahan

### 1. Stock Tidak Bisa di-Edit Manual

Tracking stock adalah **read-only view** — tidak ada form untuk input atau adjust stok manual. Jika ada kesalahan input di transaksi asal (grading, penjualan, transfer), yang bisa dilakukan:
- Edit/delete transaksi asal (grading, penjualan, transfer)
- Tidak ada "stock adjustment" khusus

### 2. Tidak Ada Snapshot/Stok Point-in-Time

Semua perhitungan langsung dari `inventory_transactions` yang ada. Tidak ada tabel history mingguan/bulanan untuk tahu stok di tanggal tertentu.

### 3. Positive vs Negative Stock Mungkin Membingungkan

Rumus positive stock yang GROUP BY location_id baru dijumlahkan bisa jadi tidak intuitif:
- Lokasi A: +100gr, Lokasi B: -50gr → positive = 100, negative = 50, global = 50
- User mungkin mengharapkan: positive = 150 (net global positif), negative = 0

Ini perlu di-sosialisasi ke user agar tidak misunderstand.

### 4. Tidak Ada Filter Waktu

Semua query mengambil semua data dari awal. Tidak ada filter tanggal/periode di halaman tracking stok.

### 5. Susut Belum Ada Perhitungan Otomatis

Halaman `susut.blade.php` hanya menampilkan riwayat `stock_transfers`, tapi tidak menghitung ringkasan/analisis.

---

## Security Review (Detailed)

### Yang Sudah Baik

| Aspek | Status | Detail |
|-------|--------|--------|
| Mass Assignment | ✅ | `fillable` di semua model |
| SQL Injection | ✅ | Eloquent ORM |
| CSRF | ✅ | Laravel default di form |
| Auth Middleware | ✅ | `auth` di route |
| Soft Deletes | ✅ | Semua model utama pakai SoftDeletes |
| Eager Load | ✅ | `with(['location', 'supplier'])` di getStockPerLocation |
| XSS — Blade | ✅ | `{{ }}` auto-escape |
| Input Validation | ✅ | `findOrFail` di controller untuk validasi ID |

---

## Bug & Vulnerability Findings (Detail)

---

### TS-01: Error Message Terekspos ke User (No Try-Catch)

**Lokasi:** `TrackingStockController.php` — semua method tanpa exception handling

**Masalah:**
Controller memanggil service langsung tanpa try-catch. Ketika database error terjadi (timeout, connection failed, dll), exception propagate ke Laravel handler dan bisa expose raw error message ke user.

**Contoh Skenario:**
1. Database server down atau connection timeout
2. Laravel menampilkan error page: `SQLSTATE[HY000] [2002] Connection timed out`
3. User melihat error internal tersebut — mengganggu UX dan expose sistem internals

**Dampak:**
- User experience buruk karena melihat error teknis
- Information disclosure — attacker bisa tahu internal structure (table names, column names, query syntax)
- Tidak ada logging untuk debugging jika error terjadi di production

**Solusi:**
Bungkus semua method controller dengan try-catch:

```php
public function index(Request $request)
{
    try {
        $search = $request->input('search');
        $parentGrades = $this->trackingStockService->getParentGradeCompany($search);
        return view('admin.stock.index', compact('parentGrades', 'search'));
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('TrackingStock index error: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString(),
        ]);
        return back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
    }
}

// Apply ke: index(), parentGrades(), parentSorts(), detail(), susut()
```

**Priority: P1** — Ini security/UX issue yang harus segera diperbaiki.

---

### TS-02: N+1 Query — `calculateGlobalStock()` di dalam Loop

**Lokasi:** `TrackingStockController.php:39-41` (parentGrades method)

**Masalah:**
```php
foreach ($gradeCompanies as $item) {
    $item->total_stock = $this->trackingStockService->calculateGlobalStock($item->id);
    // Jika 15 grades → 15 query SUM ke inventory_transactions
}
```

Pattern N+1 — 1 query untuk fetch grades + N query untuk stock calculation. Dengan pagination 15 per page, ini berarti 1 + 15 = 16 query database hanya untuk satu halaman.

**Dampak:**
- Load time lambat (semakin banyak grades, semakin lambat)
- Database load tinggi
- Tidak scalable untuk warehouse dengan banyak grades

**Solusi:**
Buat method batch yang menghitung semua stock dalam 1 query:

```php
// TrackingStockService.php — tambahkan method baru
public function calculateGlobalStockBulk(array $gradeIds): array
{
    if (empty($gradeIds)) {
        return [];
    }

    $results = InventoryTransaction::select('grade_company_id')
        ->selectRaw('SUM(quantity_change_grams) as total_stock')
        ->whereIn('grade_company_id', $gradeIds)
        ->groupBy('grade_company_id')
        ->pluck('total_stock', 'grade_company_id')
        ->toArray();

    // Fill missing IDs dengan 0
    return array_map(fn($v) => (int) round($v ?: 0), array_fill_keys($gradeIds, 0) + $results);
}
```

```php
// TrackingStockController.php — parentGrades()
$gradeIds = $gradeCompanies->pluck('id')->toArray();
$stockMap = $this->trackingStockService->calculateGlobalStockBulk($gradeIds);

foreach ($gradeCompanies as $item) {
    $item->total_stock = $stockMap[$item->id] ?? 0;
}
```

**Hasil:** 1 query instead of N queries. Atau bisa juga dengan eager loading approach (0 query tambahan).

**Priority: P2** — Performance issue yang penting untuk scalability.

---

### TS-03: `calculateParentPositiveStock/NegativeStock` Processing di PHP, bukan SQL

**Lokasi:** `TrackingStockService.php:85-102` dan `104-119`

**Masalah:**
```php
public function calculateParentPositiveStock(int $parentId): int
{
    $gradeIds = GradeCompany::where('parent_grade_company_id', $parentId)->pluck('id');

    $positiveTotal = 0;
    foreach ($gradeIds as $id) {
        $gradeStock = InventoryTransaction::where('grade_company_id', $id)
            ->selectRaw('location_id, SUM(quantity_change_grams) as total')
            ->groupBy('location_id')
            ->get()
            ->filter(fn($t) => $t->total > 0)  // ← PHP filter, bukan SQL
            ->sum('total');
        $positiveTotal += $gradeStock;
    }
    return (int) round($positiveTotal);
}
```

**Dampak:**
- Banyak query per grade (N+1 pattern sama seperti TS-02)
- Processing di PHP lebih lambat dari pada SQL aggregate
- Tidak scalable untuk parent grade dengan banyak child grades

**Solusi:**
Pindahkan filter ke SQL level dengan `HAVING`:

```php
public function calculateParentPositiveStock(int $parentId): int
{
    $gradeIds = GradeCompany::where('parent_grade_company_id', $parentId)->pluck('id');

    if ($gradeIds->isEmpty()) {
        return 0;
    }

    // Single query dengan HAVING di SQL
    $result = InventoryTransaction::whereIn('grade_company_id', $gradeIds)
        ->selectRaw('SUM(quantity_change_grams) as grand_total')
        ->first();

    $total = $result?->grand_total ?? 0;

    // Hanya return positive portion
    return (int) round(max(0, $total));
}

public function calculateParentNegativeStock(int $parentId): int
{
    $gradeIds = GradeCompany::where('parent_grade_company_id', $parentId)->pluck('id');

    if ($gradeIds->isEmpty()) {
        return 0;
    }

    $result = InventoryTransaction::whereIn('grade_company_id', $gradeIds)
        ->selectRaw('SUM(quantity_change_grams) as grand_total')
        ->first();

    $total = $result?->grand_total ?? 0;

    return (int) round(min(0, $total));
}
```

**Catatan Penting:** Rumus baru ini berbeda dengan rumus lama. Rumus lama GROUP BY location_id terlebih dahulu:
- Lokasi A: +100gr, Lokasi B: -50gr → positive = 100, negative = 50, global = 50

Rumus baru simple SUM:
- Lokasi A: +100gr, Lokasi B: -50gr → positive = 50, negative = 0

**Perlu konfirmasi user:** Apakah rumus sekarang (per-location filter) atau simple SUM yang benar sesuai kebutuhan bisnis?

**Priority: P2** — Performance issue, perlu clarify business logic dulu.

---

### TS-04: `tracking.blade.php` Orphan — Tidak Ada Route

**Lokasi:** `resources/views/admin/stock/tracking.blade.php`

**Masalah:**
File blade ini ada di folder views tapi tidak ada route yang mengarah ke sana. Ini orphan view yang:
- Membingungkan developer (tidak jelas fungsinya)
- Tidak pernah di-maintain karena tidak dipakai
- Kode bisa outdated dan安全隐患 jika suatu hari ternyata dipanggil

**Cek keberadaan:**
```bash
# Cek apakah ada reference ke route ini
grep -r "tracking-stock/tracking" routes/
grep -r "tracking.blade" app/
```

Jika tidak ada reference di seluruh codebase, file ini orphan.

**Solusi — Opsi A (Hapus jika tidak digunakan):**
```bash
rm resources/views/admin/stock/tracking.blade.php
```

**Solusi — Opsi B (Buat route jika memang mau dipakai):**
```php
// routes/web.php
Route::get('/tracking', [TrackingStockController::class, 'tracking'])->name('tracking');
```

**Solusi — Opsi C (Clarify dengan user):**
Tanyakan apakah fitur ini memang belum selesai atau tidak diperlukan.

**Priority: P3** — Bukan bug tapi butuh clarify.

---

### TS-05: Global Stock Tanpa Filter Date — Tidak Scalable

**Lokasi:** `TrackingStockService.php:71-74`

**Masalah:**
```php
public function calculateGlobalStock(int $gradeId): int
{
    return (int) round(
        InventoryTransaction::where('grade_company_id', $gradeId)->sum('quantity_change_grams')
        // ⚠️ Tidak ada filter tanggal/periode — mengambil semua data dari awal
    );
}
```

Untuk warehouse dengan data bertahun-tahun, query ini full table scan. Bisa lambat untuk grade dengan jutaan rows di `inventory_transactions`.

**Solusi — Tambah optional date filter:**
```php
// TrackingStockService.php
use Carbon\Carbon;

public function calculateGlobalStock(
    int $gradeId,
    ?Carbon $fromDate = null,
    ?Carbon $toDate = null
): int {
    $query = InventoryTransaction::where('grade_company_id', $gradeId);

    if ($fromDate) {
        $query->where('transaction_date', '>=', $fromDate);
    }
    if ($toDate) {
        $query->where('transaction_date', '<=', $toDate);
    }

    return (int) round($query->sum('quantity_change_grams'));
}
```

Kalau user sering complain slow, baru implement. Untuk sekarang bisa jadi P3.

**Priority: P3** — Baru проблема kalau data sudah besar.

---

### TS-06: `Log::info()` Debug Aktif di Production

**Lokasi:** `TrackingStockService.php:187`

**Masalah:**
```php
Log::info("getStockPerLocation for Grade $gradeId: " . $results->count() . " rows found. Total Stock Sum: " . $results->sum('total_stock'));
```

Log ini terus berjalan di production environment.

**Dampak:**
- Log file membesar cepat
- Potentially expose data structure ke yang punya akses log
- Tidak perlu di-production

**Solusi:**
```php
if (app()->environment('local', 'development')) {
    Log::info("getStockPerLocation for Grade $gradeId...", [...]);
}
```

Atau hapus sama sekali jika memang hanya untuk debugging.

**Priority: P3** — Bukan bug tapi perlu cleanup.

---

### TS-07: Missing Audit Trail untuk Akses Data

**Lokasi:** `TrackingStockController.php`

**Masalah:**
Modul ini read-only, tapi untuk compliance/audit, sebaiknya di-log siapa mengakses data apa dan kapan. Tidak ada logging untuk:
- Siapa yang access tracking stok
- Kapan data di-view
- Parameter search apa yang digunakan

**Solusi (future improvement):**
```php
public function index(Request $request)
{
    try {
        // Log akses
        Log::channel('audit')->info('TrackingStock accessed', [
            'user_id' => auth()->id(),
            'action' => 'index',
            'search' => $request->input('search'),
            'ip' => $request->ip(),
            'timestamp' => now(),
        ]);
        
        // ... rest of code
    }
}
```

Ini bukan urgent issue — future improvement untuk compliance.

**Priority: Future** — Bukan bug, nice-to-have.

---

### TS-08: Back Button Navigasi Tidak Intuitif

**Lokasi:** `detail.blade.php:11-17`

**Masalah:**
```blade
<a href="{{ route('tracking-stock.get.grade.company') }}"
```

Tombol kembali selalu ke index parent grades. Tapi user bisa datang dari flow: `index` → `parent-grades` → `detail`. User expectation tombol kembali ke halaman sebelumnya (`parent-grades`), bukan index.

**Dampak:**
- Navigasi tidak intuitif
- User harus klik banyak kali untuk kembali ke tempat yang sama

**Solusi:**
```blade
<a href="{{ url()->previous() ?? route('tracking-stock.get.grade.company') }}">
    Kembali
</a>
```

`url()->previous()` akan redirect ke halaman yang membawa user ke sini. Fallback ke index jika tidak ada referrer (misal: user buka URL langsung).

**Alternatif lain:** Gunakan session untuk store "referrer" state:
```php
// Di controller parentGrades()
session(['tracking_stock_referrer' => url()->current()]);

// Di detail.blade.php
<a href="{{ session('tracking_stock_referrer', route('tracking-stock.get.grade.company')) }}">
    Kembali
</a>
```

**Priority: P3** — UX issue yang mengganggu tapi tidak critical.

---

### TS-09: `fromLocation/toLocation` Bisa Null Tapi Cuma Fallback 'Unknown'

**Lokasi:** `susut.blade.php:175` dan `186`

**Masalah:**
```blade
{{ $transfer->fromLocation->name ?? 'Unknown' }}
{{ $transfer->toLocation->name ?? 'Unknown' }}
```

Jika `from_location_id` atau `to_location_id` null atau refer ke lokasi yang deleted, ini fallback ke 'Unknown'. Ini aman secara teknis tapi perlu confirm apakah ini acceptable business logic.

**Pertanyaan:**
- Apakah null location adalah invalid state (seharusnya tidak pernah terjadi)?
- Atau apakah 'Unknown' acceptable dan memang intended state?

**Solusi:**
Jika null location adalah invalid state, bisa tambahkan logging untuk detect data corruption:

```php
// Di StockTransfer model
public function getFromLocationNameAttribute(): string
{
    if (!$this->fromLocation) {
        Log::warning('StockTransfer has null fromLocation', [
            'id' => $this->id,
            'from_location_id' => $this->from_location_id,
        ]);
        return 'Unknown';
    }
    return $this->fromLocation->name;
}
```

Untuk now, `?? 'Unknown'` sudah cukup dan aman.

**Priority: P3** — Edge case yang perlu di-confirm dengan user.

---

## Summary Temuan

| ID | Issue | Severity | Lokasi | Solusi |
|----|-------|----------|--------|--------|
| TS-01 | Error message terekspos ke user (no try-catch) | 🟡 MEDIUM | TrackingStockController.php | Tambah try-catch di semua method |
| TS-02 | N+1 query — calculateGlobalStock() di dalam loop | 🟡 MEDIUM | parentGrades() controller | Buat method batch calculateGlobalStockBulk() |
| TS-03 | Positive/Negative stock processing di PHP, bukan SQL | 🟡 MEDIUM | TrackingStockService.php | Pindahkan filter ke SQL HAVING |
| TS-04 | tracking.blade.php orphan — tidak ada route | 🟡 MEDIUM | resources/views/admin/stock/tracking.blade.php | Hapus atau buat route |
| TS-05 | Global stock tanpa filter date — tidak scalable | 🟢 LOW | TrackingStockService.php | Tambah optional date filter parameter |
| TS-06 | Log::info() debug aktif di production | 🟢 LOW | TrackingStockService.php:187 | Hapus atau bungkus dengan env check |
| TS-07 | Missing audit trail untuk akses data | 🟢 LOW | TrackingStockController.php | Tambahkan logging untuk audit |
| TS-08 | Back button navigasi tidak intuitif | 🟢 LOW | detail.blade.php | Gunakan url()->previous() |
| TS-09 | fromLocation/toLocation bisa null tapi hanya fallback ke 'Unknown' | 🟢 LOW | susut.blade.php | Konfirmasi dengan user apakah edge case ini valid |

---

## Remaining Issues (Open)

| ID | Severity | Issue | Prioritas |
|----|----------|-------|-----------|
| TS-01 | 🟡 MEDIUM | Error handling — try-catch di controller | P1 |
| TS-02 | 🟡 MEDIUM | N+1 query di parentGrades | P2 |
| TS-03 | 🟡 MEDIUM | Query tidak optimal di positive/negative stock | P2 |
| TS-04 | 🟡 MEDIUM | Orphan blade file tracking.blade.php | P3 — perlu clarify apakah digunakan |
| TS-05 | 🟢 LOW | Global stock tanpa date filter | P3 |
| TS-06 | 🟢 LOW | Debug log aktif | P3 |
| TS-07 | 🟢 LOW | Audit trail untuk akses | Future |
| TS-08 | 🟢 LOW | Navigasi back button | P3 |
| TS-09 | 🟢 LOW | Null location fallback | P3 |

---

## Pending Questions / Butuh Konfirmasi

1. **Apakah perlu ada fitur stock adjustment manual?** Jika ada salah input, sekarang harus edit/delete transaksi asal.

2. **Apakah perlu ada filter tanggal/periode di tracking stok?**

3. **Rumus positive/negative stock sudah tepat sesuai kebutuhan bisnis?**

4. **Apakah halaman "susut" sudah sesuai kebutuhan?** Saat ini hanya list riwayat, belum ada ringkasan/analisis.

5. **File `tracking.blade.php` — apakah masih digunakan?** Jika tidak, sebaiknya dihapus untuk avoid confusion.

---

## Existing Documentation untuk Referensi

Dokumentasi ini dibuat mengikuti format analisis yang sama dengan:
- `docs/barang-masuk/barang-masuk-analysis.md`
- `docs/manajemen-grading/grading-analysis.md`