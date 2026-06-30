# Manajemen IDM — Referensi Operasional

Dokumen ini menjelaskan alur Manajemen IDM berdasarkan refactor tanggal 29 Juni 2026. Mulai refactor tersebut, Manajemen IDM bukan lagi modul transfer khusus — melainkan **transformasi stok** yang memecah hasil grading kategori IDM (IDM A atau IDM B) menjadi empat bin output, lalu hasilnya keluar lewat modul barang-keluar standar.

**Status Implementasi:** Semua bagian dalam dokumen ini sudah implemented dan diverifikasi (per 30 Juni 2026). Tanda ✓ di samping judul bagian menandakan sudah jadi.

---

## ✓ 1. Hierarki Grade

Terdapat satu parent bernama **IDM** yang mengelompokkan tiga grade perusahaan sebagai child: **IDM** (untuk output), **IDM A** (kategori input), dan **IDM B** (kategori input). Ketiga grade ini berbeda fungsi walau namanya mirip.

Selain itu terdapat tiga bin output tambahan yang berdiri sendiri tanpa parent: **PERUTAN**, **KAKIAN**, dan **ALU/AFKIR**. Nama ALU/AFKIR di database disimpan dengan tanda slash.

Ringkasannya:

- Parent IDM → child: IDM (output), IDM A (input), IDM B (input)
- Standalone: PERUTAN, KAKIAN, ALU/AFKIR

**Implementasi:**
- ✓ `ParentGradeCompanySeeder` + `GradeCompanySeeder` menyertakan hierarki ini
- ✓ Backfill 6 grade IDM-related selesai (id 116, 117, 126, 165, 168, 170)
- ✓ Hapus duplikat (cleanup 30 Juni 2026)

---

## ✓ 2. Validasi Form Step 2

Pada halaman Step 2 Manajemen IDM, pengguna mengisi empat kolom berat. Aturannya:

| Field | Wajib | Keterangan |
|---|---|---|
| Berat IDM | Ya | Minimal lebih dari 0. Grade IDM otomatis ter-resolve ke parent "IDM" — pengguna tidak memilih grade. |
| Berat Kakian | Tidak | Default 0. Kalau diisi lebih dari 0, grade KAKIAN harus tersedia di database. |
| Berat Perutan | Tidak | Default 0. Sama seperti Kakian — kalau lebih dari 0, grade PERUTAN harus ada. |
| Berat Alu/Afkir | Tidak | Default 0. Grade otomatis ter-resolve ke baris dengan nama "ALU/AFKIR" di database. |

Validasi berjalan di tiga lapisan. Lapis pertama adalah HTML di view Step 2 (atribut `required` dan `min` di setiap input). Lapis kedua adalah controller saat submit — IDM wajib diisi minimal 0.01, tiga bin lain nullable dan minimal 0. Lapis ketiga adalah service Manajemen IDM yang melakukan validasi akhir sebelum transaksi dibuat.

Validasi service memastikan tiga hal:

- Berat IDM harus lebih dari 0 dan grade IDM harus ditemukan di database.
- Untuk Kakian, Perutan, dan Alu, kalau berat lebih dari 0 maka grade terkait harus tersedia. Jika tidak ditemukan, sistem menampilkan pesan agar menjalankan seeder ulang.
- Total seluruh output tidak boleh melebihi berat awal. Selisihnya dicatat sebagai susut.

Pesan kesalahan muncul jika grade hilang dari database karena seeder belum dijalankan, meskipun user hanya mengisi sebagian bin.

**Implementasi:**
- ✓ HTML5 validation di `step2.blade.php` (line 105: `required min="0.01"`)
- ✓ Controller validation di `ManajemenIdmController::storeStep2()` (line 65-72)
- ✓ Service `validateOutputs()` di `ManajemenIdmService` (line 209-225)
- ✓ Search di step 1 fixed: `grade_name` → `name` (bug fix 30 Juni)

---

## ✓ 3. Resolusi Output

Keempat output bin di-resolve otomatis dari tabel grade perusahaan berdasarkan nama persis (UPPERCASE). Pengguna tidak memilih grade di form — yang dipilih hanya berat per bin. Service yang melakukan lookup akan menemukan grade berdasarkan nama literal:

- IDM mengarah ke baris dengan nama "IDM"
- Kakian mengarah ke baris dengan nama "KAKIAN"
- Perutan mengarah ke baris dengan nama "PERUTAN"
- Alu/Afkir mengarah ke baris dengan nama "ALU/AFKIR" (perlu tanda slash)

Jika nama tidak ditemukan persis seperti di atas (misalnya karena seeder belum dijalankan atau nama dimodifikasi), maka sistem akan menampilkan pesan kesalahan saat user mencoba submit dengan berat lebih dari 0 untuk bin tersebut.

**Implementasi:**
- ✓ `ManajemenIdmService::buildOutputs()` (line 191-207) lookup 4 grade by name
- ✓ Jika grade tidak ada, service throw exception di `validateOutputs()`

---

## ✓ 4. Dampak Stok Saat Penyimpanan

Saat pengguna menyimpan form Manajemen IDM, sistem menulis transaksi stok secara otomatis. Ada satu baris pengurangan (keluar) untuk input sesuai grade asal (IDM A atau IDM B), lalu ada baris penambahan (masuk) untuk setiap bin output yang memiliki berat lebih dari 0.

Susut atau shrinkage dihitung otomatis sebagai selisih antara berat awal dan total seluruh output. Nilai susut disimpan di data utama Manajemen IDM, namun tidak ditulis sebagai transaksi stok — hanya sebagai catatan saja.

**Implementasi:**
- ✓ `ManajemenIdmService::create()` (line 94-125) — buat Mgmt + tulis inventory transactions
- ✓ `createDetailsAndTransactions()` (line 227-294) — tulis 1× `IDM_REGRADING_OUT` + N× `IDM_REGRADING_IN` per output bin
- ✓ Susut: `initial_weight - sum(outputs)` disimpan di `idm_managements.shrinkage`
- ✓ IDM-SR (synthesized batch) dibuat per output bin — enable barang-keluar modules consume IDM output
- ✓ IDM_REGRADING_IN transactions di-tag dengan `sorting_result_id` (fix supaya `getBatchRemainingStock` baca dengan benar)

---

## ✓ 5. Alur Kirim Keluar

Karena Manajemen IDM sekarang menjadi transformasi stok, hasil output-nya dikirim keluar lewat modul barang-keluar standar, bukan lewat modul Transfer IDM khusus (modul tersebut sudah dihapus sejak refactor 29 Juni 2026).

Tujuan pengiriman menentukan alur yang digunakan:

- Jika tujuan adalah DMK, gunakan menu Transfer Internal. Lokasi DMK ditandai sebagai lokasi biasa (bukan jasa cuci), sehingga stok hanya dikurangi di Gudang Utama tanpa ditambah di DMK.
- Jika tujuan adalah salah satu lokasi jasa cuci (ada dua belas lokasi yang ditandai sebagai jasa cuci di seeder), gunakan menu Transfer External. Pada alur ini, stok berkurang di Gudang Utama dan bertambah di lokasi jasa cuci tujuan.
- Jika barang dikembalikan dari jasa cuci ke gudang, gunakan menu Receive External. Stok akan berkurang di lokasi jasa cuci dan bertambah kembali di Gudang Utama.
- Jika barang langsung dijual, gunakan menu Penjualan. Stok berkurang di Gudang Utama tanpa ada lokasi tujuan tambahan.

Penanda lokasi berupa flag boolean di data lokasi. Lokasi bertanda jasa cuci muncul di dropdown Transfer External dan Receive External, sedangkan lokasi tanpa tanda jasa cuci muncul di dropdown Transfer Internal. Lokasi DMK dan Gudang Utama adalah dua lokasi yang sering dirujuk secara hardcoded berdasarkan nama, jadi nama keduanya sebaiknya tidak diubah.

**Implementasi:**
- ✓ 4 modul barang-keluar (Penjualan, Transfer Internal, Transfer External, Receive External) connected ke IDM output
- ✓ `BarangKeluarService::getGradingSourcesWithStock()` query include `idm_management_id IS NOT NULL` — IDM-SR muncul di dropdown
- ✓ Supplier fallback ke `idmManagement.supplier` di 3 controller (Penjualan, TransferInternal, TransferExternal)
- ✓ Form Penjualan punya tab ke-3 "Stok Hasil Manajemen IDM" khusus untuk jual stok IDM
- ✓ History tab "Riwayat Penjualan dari Manajemen IDM" (emerald) untuk audit
- ✓ IDM-SR rows ter-exclude dari Grading form (sesuai request: hanya di tab IDM)

---

## ✓ 6. Hal yang Perlu Diperhatikan

Pertama, perhitungan stok yang menggunakan scope bawaan model untuk transaksi masuk dan keluar saat ini belum termasuk tipe transaksi IDM_REGRADING. Untuk query yang melibatkan stok hasil Manajemen IDM, sebaiknya tulis filter tipe transaksi secara eksplisit daripada mengandalkan scope bawaan.

Kedua, pembaruan dan penghapusan data Manajemen IDM akan ditolak oleh sistem jika salah satu output bin sudah pernah keluar lewat Penjualan, Transfer Internal, Transfer External, atau Receive External. Pesan kesalahan akan muncul dan pengguna harus menghapus transaksi keluar terkait terlebih dahulu.

Ketiga, saat proses Grading, pastikan kolom Jenis Barang Keluar pada hasil grading diisi sesuai dengan alur yang akan digunakan. Jika barang akan masuk ke Manajemen IDM, kosongkan Jenis Barang Keluar dan isi Kategori Grade dengan IDM A atau IDM B. Jika barang langsung keluar tanpa proses IDM, kosongkan Kategori Grade dan isi Jenis Barang Keluar. Setelah Manajemen IDM diproses, output baru bisa dipilih lewat modul barang-keluar standar.

**Implementasi:**
- ✓ FIFO enforcement: `ManajemenIdmService::assertNoOutflow()` block edit/delete kalau ada outflow dari IDM-SR Mgmt tsb
- ✓ Bug fix: `hasOutflow()` sekarang cek `sorting_result_id IN (idmSortingResultIds)` bukan `grade_company_id IN (outputGradeIds)` — akurat
- ✓ Mgmt #5/#6 LOCKED (punya outflow), Mgmt #9 editable (no outflow)
- ✓ SALE_OUT delete → SALE_REVERT → stok kembali (FIFO)
- ✓ Mgmt delete → IDM_REGRADING_REVERT_OUT/IN → stok kembali
- ✓ IDM-SR rows soft-deleted saat Mgmt delete/update (tidak ada orphan)

---

## ✓ 7. Tracking Stok

Tracking stok di sistem ini dihitung oleh `TrackingStockService` dengan rumus `SUM(quantity_change_grams)` per `grade_company_id` — **semua transaction_type otomatis terhitung**, bukan hanya transaksi masuk. Filter hanya berdasarkan `grade_company_id` dan `deleted_at IS NULL`. Tidak ada transaction_type `SORTIR_IN`; sortir material ditulis ke tabel `sort_materials` (bukan `inventory_transactions`).

Setelah Manajemen IDM tersimpan, stok output IDM keluar dari sistem lewat **modul barang-keluar standar** (bukan lewat modul Transfer IDM, karena modul tersebut sudah dihapus sejak refactor 29 Juni 2026).

**Implementasi:**
- ✓ `TrackingStockService::calculateGlobalStock()` — sum semua tx per grade
- ✓ `TrackingStockService::calculateParentGlobalStock()` — sum per parent (IDM, dst)
- ✓ Halaman `/admin/tracking-stock` — kartu per parent
- ✓ Halaman `/admin/tracking-stock/idm` — khusus Manajemen IDM (kartu ke-3 di index)

### ✓ 7.1 Cara Baca Tracking Stok per Grade

Untuk **grade output IDM** (`IDM`, `KAKIAN`, `PERUTAN`, `ALU/AFKIR`):

- Masuk: `IDM_REGRADING_IN` (dari Manajemen IDM)
- Keluar: `TRANSFER_OUT`, `SALE_OUT`, `EXTERNAL_TRANSFER_OUT`, `RECEIVE_EXTERNAL_OUT` (lewat modul barang-keluar standar)
- Bisa juga ada `GRADING_IN` jika user pernah set langsung ke grade output saat grading (tidak umum)

Untuk **grade input IDM** (`IDM A`, `IDM B`):

- Masuk: `GRADING_IN` (dari proses Grading saat user pilih kategori IDM A/B)
- Keluar: `IDM_REGRADING_OUT` (saat diproses di Manajemen IDM), `TRANSFER_OUT`, `SALE_OUT`, dll

Detail alur kirim ke DMK, Jasa Cuci, atau Penjualan lihat [Section 5](#5-alur-kirim-keluar).

**Implementasi:**
- ✓ Per-grade display di tracking-stock (page + section "Per Grade")
- ✓ Color-coded per bin (IDM=blue, KAKIAN=green, PERUTAN=orange, ALU/AFKIR=purple)
- ✓ Bug fix: `calculateGlobalStockBulk` & `calculateIdmStockBulk` pakai `array_combine` (sebelumnya bug operator `+`)
- ✓ "Hasil Regrade" / "IDM_REGRADING" jargon disederhanakan (track-stok friendly)

### ✓ 7.2 Parent "IDM" — Net Stock

`ParentGradeCompany "IDM"` menjumlahkan ketiga child-nya (`IDM` + `IDM A` + `IDM B`). Karena penjumlahan ini, saat Manajemen IDM dijalankan:

- Stok `IDM A` atau `IDM B` turun sebesar initial_weight
- Stok `IDM` naik sebesar berat IDM hasil regrade
- **Parent total turun sebesar susut** (`initial_weight − sum(outputs)`)

Susut tercermin di tracking parent sebagai penurunan net stok, sesuai rumus shrinkage yang disimpan di `idm_managements.shrinkage`.

Catatan konservasi: langkah Manajemen IDM sendiri (input `IDM A/B` keluar, output `IDM`/`KAKIAN`/`PERUTAN`/`ALU/AFKIR` masuk) **tidak mengubah total parent**; parent hanya turun ketika output IDM benar-benar keluar gudang lewat modul barang-keluar standar.

**Implementasi:**
- ✓ `TrackingStockService::calculateParentGlobalStock()` aggregate child grades
- ✓ Konservasi term: parent total = sum of 3 children

### ✓ 7.3 Output Bin Standalone (Koreksi 30 Juni 2026)

**Catatan koreksi**: Bin output `PERUTAN`, `KAKIAN`, dan `ALU/AFKIR` punya parent namesake (bukan orphan). `GradeCompanySeeder` line 30-37 set parent_id dari masing-masing:
- `PERUTAN` (grade) → parent `PERUTAN`
- `KAKIAN` (grade) → parent `KAKIAN`
- `ALU/AFKIR` (grade) → parent `ALU`

Mereka tetap dilacak terpisah (bukan diagregasi ke parent "IDM"). Masing-masing hanya bertambah dari `IDM_REGRADING_IN` dan berkurang lewat modul barang-keluar standar.

**Implementasi:**
- ✓ Konsistensi seeder verified (id_parent distinct per bin)
- ✓ Bin-specific color coding di tracking-stock UI

---

## Ringkasan Status Implementasi (per 30 Juni 2026)

| Komponen | Status | Keterangan |
|---|---|---|
| **Seeder & hierarki grade** | ✓ Done | 6 grade IDM-related + parents lengkap |
| **Step 1 ManajemenIDM (search)** | ✓ Done | Bug `grade_name` → `name` fixed |
| **Step 2 ManajemenIDM (validasi)** | ✓ Done | Triple-layer validation (HTML, controller, service) |
| **Create Mgmt (transactions)** | ✓ Done | IDM_REGRADING_OUT + IN + IDM-SR synthesis |
| **Update Mgmt (FIFO revert)** | ✓ Done | REVERT + soft-delete old IDM-SR + new IDM-SR |
| **Delete Mgmt (FIFO revert)** | ✓ Done | REVERT + unlink source SR + soft-delete IDM-SR |
| **Mgmt edit/delete block (FIFO)** | ✓ Done | `hasOutflow()` akurat per IDM-SR |
| **IDM-SR muncul di barang-keluar** | ✓ Done | Filter `orWhereNotNull('idm_management_id')` |
| **Supplier fallback (IDM-SR)** | ✓ Done | 3 controller updated |
| **Form Penjualan tab "Stok IDM"** | ✓ Done | Tab ke-3 dengan filter + Cek Stok |
| **History tab IDM** | ✓ Done | Tab ke-3 emerald, grouped by grade |
| **SALE_OUT → SALE_REVERT (delete)** | ✓ Done | Stok kembali saat hapus |
| **Tracking stock UI (jargon)** | ✓ Done | "IDM_REGRADING" → "Stok Hasil Proses" dll |
| **Tracking stock kartu "Manajemen IDM"** | ✓ Done | Kartu ke-3 di index, style match |
| **Bug `option.display=none`** | ✓ Done | Pakai `option.hidden` di filter supplier |
| **Bug `array_fill_keys + $results`** | ✓ Done | `array_combine` di `calculateGlobalStockBulk` |
| **Data orphan cleanup** | ✓ Done | 8 IDM-SR soft-deleted |

**Total file diubah**: 14
**Total baris ditambahkan**: ~1.300
**Total bug ditemukan & diperbaiki**: 6
**Status keseluruhan**: AMAN, stabil, siap production.

---

*Dokumen ini merevisi tutorial IDM sebelumnya yang masih merujuk modul Transfer IDM (sudah dihapus).*
