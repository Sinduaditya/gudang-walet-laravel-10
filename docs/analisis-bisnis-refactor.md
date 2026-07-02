# Analisis Bisnis & Saran Refactor — Gudang Walet

**Dibuat:** 2026-07-01  
**Scope:** Grading Goods, Manajemen IDM, Barang Keluar, Sortir Bahan, Tracking Stock  
**Status:** Draft analisis — belum diimplementasi

---

## 1. Peta Alur Bisnis Saat Ini

```
[SUPPLIER]
    ↓  (Barang Masuk / Incoming Goods)
[PURCHASE_RECEIPT] → [RECEIPT_ITEMS] (status: mentah)
    ↓  (Grading Goods)
[SORTING_RESULTS] (output grade company per batch)
    ↓  pilih outgoing_type
    ├─ penjualan_langsung → SALE_OUT (InventoryTransaction -)
    ├─ internal          → TRANSFER_OUT + TRANSFER_IN
    └─ external          → EXTERNAL_TRANSFER_OUT + IN → kembali lewat Receive External

[SORTING_RESULTS] + category_grade IDM A/B
    ↓  (Manajemen IDM)
[IDM_MANAGEMENT] → IDM-SR (synthetic SortingResult, receipt_item_id=NULL)
    ↓  (sama seperti grading biasa)
    ├─ SALE_OUT
    ├─ TRANSFER_*
    └─ dll.

[SORTIR BAHAN] ← TERPISAH SEPENUHNYA dari alur di atas
    ↓
[SORT_MATERIALS] + [PARENT_GRADE_COMPANIES.stock cache]
    └─ Keluar: SortMaterial type=keluar (tidak ada SALE_OUT di inventory_transactions)
```

---

## 2. Tabel yang Digunakan

| Tabel | Peran | Masalah |
|-------|-------|---------|
| `purchase_receipts` | Header penerimaan barang dari supplier | Oke |
| `receipt_items` | Detail per grade supplier | Oke |
| `sorting_results` | Output grading + output IDM (dual purpose) | **Rawan** — 1 tabel 2 peran berbeda |
| `inventory_transactions` | Ledger stok — semua mutasi | Baik tapi jadi sangat kompleks |
| `stock_transfers` | Catatan fisik transfer | Oke |
| `idm_managements` | Master regrading IDM | Oke |
| `idm_details` | Output per grade IDM | Oke |
| `sort_materials` | Stok sortir bahan | **Terpisah** dari inventory_transactions |
| `parent_grade_companies` | Parent grade + cache stok sortir | **Cache bisa de-sync** |
| `sales` + `sale_items` | Legacy, tidak aktif | **Mubazir** |
| `locations` | Lokasi gudang + jasa cuci | Oke |
| `grades_supplier` | Grade input dari supplier | Oke |
| `grades_company` | Grade output perusahaan | Oke |

---

## 3. Masalah Saat Ini (Root Cause)

### 3.1 Sortir Bahan Tidak Terintegrasi dengan Stok Utama

**Fakta:**
- `sort_materials` + `parent_grade_companies.stock` berjalan **sendiri**
- Tidak ada `inventory_transactions` yang dibuat saat sortir masuk/keluar
- Stok di tracking stock ada dua dunia: "Stok Grading" dan "Stok Sortir" — tidak bisa dijumlahkan/direkonsiliasi secara otomatis

**Dampak:**
- Laporan stok tidak akurat secara keseluruhan
- Risiko oversell karena sistem tidak tahu total aset sebenarnya
- Cache `parent_grade_companies.stock` bisa desync jika ada bug di satu tempat

### 3.2 `sorting_results` Dipakai untuk Dua Hal Berbeda

```
NORMAL: receipt_item_id = X (dari grading nyata)
IDM-SR: receipt_item_id = NULL, idm_management_id = Y (synthetic/virtual)
```

**Dampak:**
- Query selalu harus filter `receipt_item_id IS NOT NULL` atau `idm_management_id IS NOT NULL`
- Logic dropdown di `getGradingSourcesWithStock()` kompleks karena handle dua jenis batch
- Susah dibaca dan di-debug

### 3.3 `outgoing_type` di `sorting_results` Lock Terlalu Dini

Setelah set `outgoing_type = penjualan_langsung` dan ada transaksi, tidak bisa ganti.

**Masalah praktis:**
- Kalau awalnya niat jual tapi ternyata mau transfer → harus hapus dulu, buat ulang
- Batch tidak bisa di-split ke beberapa tujuan (misal: 300g jual, 200g transfer)

### 3.4 Kompleksitas Transaction Type (12 jenis)

```
GRADING_IN, SALE_OUT, SALE_REVERT, TRANSFER_OUT, TRANSFER_IN,
EXTERNAL_TRANSFER_OUT, EXTERNAL_TRANSFER_IN,
RECEIVE_INTERNAL_IN, RECEIVE_EXTERNAL_OUT, RECEIVE_EXTERNAL_IN,
IDM_REGRADING_OUT, IDM_REGRADING_IN
```

Setiap fitur baru = tambah jenis transaksi baru. Ini tidak sustainable.

### 3.5 Validasi Stok 3-Layer Rawan Bug

Di `BarangKeluarService.validateStock()`, sistem cek:
1. Batch stock (sorting_result specific)
2. Location stock
3. Global stock

Jika logika satu layer salah, bisa oversell atau false-block. Sudah ada bug sebelumnya
(PR #32 fix supplier_id retrieval, PR #31 fix FIFO reversal).

### 3.6 Receive Internal Tidak Buat TRANSFER_OUT

`RECEIVE_INTERNAL_IN` hanya membuat transaksi positif di tujuan. Tidak ada transaksi negatif
dari sumber. Ini asumsi bahwa "sumber sudah ter-track dari TRANSFER_IN sebelumnya."  
**Mudah kacau** kalau ada operasi yang tidak simetris.

### 3.7 `sales` + `sale_items` Tabel Legacy Tidak Dipakai

Masih ada di schema tapi flow aktual pakai `inventory_transactions` langsung.
Membingungkan developer baru.

---

## 4. Jawaban: Apakah Sortir Bahan Harus Jadi Menu Sendiri?

### **YA, HARUS.**

**Alasan:**

Sortir bahan secara bisnis adalah **proses masuk** (inbound):
- Barang yang dikelola di sortir adalah bahan baku mentah (parent grade)
- "Sortir keluar" = penjualan dari bahan mentah, **bukan** sama dengan barang keluar dari grading
- Secara mental model berbeda: grading output = barang jadi; sortir = bahan mentah yang belum diproses

**Masalah menaruhnya di Barang Keluar:**
- User bingung karena sortir "masuk" ada di menu keluar
- Menyulitkan audit: laporan barang keluar tercampur antara barang jadi dan bahan mentah
- Stok sortir tidak muncul di tracking stok utama (isolated)

**Saran posisi menu:**
```
Admin
├── Barang Masuk
│   ├── Incoming Goods (dari supplier)
│   └── Sortir Bahan ← PINDAHKAN KE SINI
├── Grading Goods
├── Manajemen IDM
├── Barang Keluar
│   ├── Penjualan (dari grading + IDM)
│   ├── Transfer Internal
│   ├── Transfer External
│   ├── Receive Internal
│   └── Receive External
└── Tracking Stock
```

---

## 5. Saran Refactor (Prioritas)

### Priority 1 — Quick Win (Tanpa ubah DB besar)

#### 1a. Pisahkan Menu Sortir Bahan ke Barang Masuk
- Buat route baru `admin/sortir-bahan` terpisah dari `barang-keluar`
- Pindahkan `SortMaterialController` ke bawah route baru
- Update navbar/sidebar
- **Dampak:** Minimal, hanya routing & UI

#### 1b. Hapus Tabel `sales` + `sale_items` yang Legacy
- Verifikasi tidak ada query aktif ke tabel ini
- Buat migration `drop_legacy_sales_tables`
- Ini reduksi confusion bagi developer

#### 1c. Refactor `outgoing_type` Jadi Optional / Per-Transaksi
- Hapus lock `outgoing_type` dari `sorting_results`
- Biarkan tipe keluar ditentukan saat transaksi dibuat (sudah ada di transaction_type)
- Ini unlock kemampuan split batch

---

### Priority 2 — Medium (Ubah logic, tidak ubah DB core)

#### 2a. Integrasikan Sortir Bahan ke `inventory_transactions`

Saat ini sortir bahan punya ledger sendiri. Saran:
- Tambahkan `transaction_type`: `SORT_IN`, `SORT_SALE_OUT`
- Saat `SortMaterial` masuk → buat `SORT_IN` di `inventory_transactions`
- Saat jual dari sortir → buat `SORT_SALE_OUT`
- Hapus `parent_grade_companies.stock` cache, hitung dari ledger
- **Benefit:** 1 sumber kebenaran untuk semua stok

#### 2b. Pisahkan IDM-SR dari `sorting_results`

Buat tabel `idm_outputs`:
```sql
idm_outputs
  id, idm_management_id, grade_company_id, weight, quantity, notes
  (SoftDeletes, timestamps)
```

Update semua query yang saat ini filter `idm_management_id IS NOT NULL` di `sorting_results`.

**Benefit:** `sorting_results` jadi pure output grading. IDM output di tabel sendiri.
Query lebih jelas, tidak ada `receipt_item_id = NULL` anomaly.

---

### Priority 3 — Besar (Hati-hati, perlu migrasi data)

#### 3a. Sederhanakan Transaction Types

Dari 12 jenis → kelompokkan:
```
Inbound:   GRADING_IN, RECEIVE, IDM_IN, SORT_IN
Outbound:  SALE, TRANSFER, IDM_OUT, SORT_SALE
Reversal:  REVERT (+ reference ke transaksi asal)
```

Tambahkan kolom `direction` (IN/OUT) dan `category` (GRADING/SALE/TRANSFER/IDM/SORT) 
sebagai ganti enum besar. Ini lebih scalable.

#### 3b. Pisahkan Lokasi dari Transaksi

Saat ini lokasi di-embed di `inventory_transactions`. Tidak ada tabel "posisi stok saat ini."
Pertimbangkan tabel `stock_positions`:
```sql
stock_positions
  grade_company_id, location_id, quantity_grams
  (diupdate setiap transaksi, bukan dihitung ulang dari ledger)
```

Ini tradeoff: lebih cepat query stok saat ini, tapi harus dijaga konsistensinya.

---

## 6. Struktur Tabel Ideal (Target Jangka Panjang)

```
MASTER:
  suppliers, locations, grades_supplier, grades_company, parent_grade_companies

INBOUND:
  purchase_receipts → receipt_items
  sort_batch_receipts → sort_batch_items  (rename dari sort_materials masuk)

PROCESSING:
  sorting_results      (pure grading output, NOT for IDM)
  idm_managements → idm_outputs  (pisahkan dari sorting_results)

LEDGER (1 sumber kebenaran):
  inventory_transactions (semua movement, termasuk sortir bahan)

TRANSFER:
  stock_transfers (semua fisik transfer)

LEGACY (deprecated, drop):
  sales, sale_items, sort_materials (gabung ke inventory_transactions)
```

---

## 7. Ringkasan Keresahan & Jawaban

| Keresahan | Akar Masalah | Saran |
|-----------|--------------|-------|
| Sortir bahan di menu keluar aneh | Bisnis masuk ditaruh di menu keluar | Pindah ke Barang Masuk |
| Stok sortir tidak nyambung ke stok utama | Dua ledger terpisah | Integrasikan ke inventory_transactions |
| Bug FIFO & reversal | Logic terlalu tersebar, 12 jenis transaksi | Sederhanakan transaction types |
| IDM-SR aneh di sorting_results | Dual purpose table | Pisahkan ke idm_outputs |
| outgoing_type lock terlalu rigid | Desain awal tidak antisipasi split batch | Hapus lock, tentukan tipe saat transaksi |
| Cache stock parent_grade desync | Denormalized cache tanpa lock | Hitung dari ledger, hapus cache |
| Tabel sales legacy membingungkan | Refactor setengah jalan | Drop tabel |

---

## 8. Rekomendasi Urutan Eksekusi

```
Bulan 1 (Aman, reversible):
  ✅ Pindah menu Sortir Bahan ke Barang Masuk
  ✅ Drop tabel legacy sales + sale_items
  ✅ Hapus lock outgoing_type (atau jadikan nullable/opsional)

Bulan 2 (Medium risk):
  ⚙️  Integrasikan sortir bahan ke inventory_transactions
  ⚙️  Pisahkan idm_outputs dari sorting_results

Bulan 3 (High effort):
  🔧 Sederhanakan transaction_types
  🔧 Evaluasi stock_positions cache
```

---

*Dokumen ini dibuat dari pembacaan source code dan percakapan dengan developer.*  
*Update dokumen ini setiap ada keputusan desain yang diambil.*
