# Tutorial Manajemen IDM & Transfer IDM

IDM adalah proses lanjutan setelah grading, di mana sarang walet kelas IDM A atau IDM B dipecah secara fisik menjadi tiga bagian berdasarkan posisi dan kualitasnya — lalu hasilnya dikirim keluar melalui Transfer IDM.

---

## Gambaran Alur IDM

```
Grading
 └─ Tandai grade dengan Kategori IDM A atau IDM B
         ↓
Manajemen IDM
 └─ Kelompokkan hasil grading → pecah jadi Perutan + Kakian + IDM
         ↓
Transfer IDM
 └─ Pilih bagian yang ingin dikirim → catat berat & tanggal keluar
```

---

## Bagian 1 — Grading: Tandai Barang sebagai IDM

Sebelum barang bisa diproses di Manajemen IDM, barang tersebut harus ditandai saat proses grading dengan memilih **Kategori Grade** yang sesuai.

### Cara Menandai Barang sebagai IDM saat Grading

1. Buka menu **Grading Goods**
2. Pilih item yang akan di-grading → klik **Grading**
3. Di halaman Step 2, isi setiap baris grade hasil sortir:
   - Pilih **Grade Perusahaan** (searchable dropdown)
   - Isi **Berat** dan **Jumlah**
4. Untuk barang yang akan masuk ke IDM:
   - Di kolom **Kategori Grade**, pilih **IDM A** atau **IDM B**
   - Kosongkan kolom **Jenis Barang Keluar**
5. Klik **Simpan Hasil Grading**

### Aturan Penting: Kategori vs Jenis Barang Keluar

Setiap baris hasil grading hanya bisa memiliki **salah satu** dari dua pilihan berikut:

| Pilihan | Digunakan untuk | Contoh |
|---------|-----------------|--------|
| **Kategori Grade** (IDM A / IDM B) | Barang yang akan diproses di Manajemen IDM | Sarang kelas premium atau standar |
| **Jenis Barang Keluar** | Barang yang langsung keluar tanpa proses IDM | Penjualan Langsung, Internal, External |

> Jika kamu memilih salah satu, pilihan yang lain otomatis dikosongkan dan tidak bisa diisi. Ini dirancang agar tidak terjadi kebingungan arah keluar barang.

### Contoh Hasil Grading

| Grade Perusahaan | Berat | Kategori Grade | Jenis Barang Keluar |
|-----------------|-------|---------------|---------------------|
| IDM A W2 | 192 gr | **IDM A** | *(kosong)* |
| IDM B Super | 136 gr | **IDM B** | *(kosong)* |
| Mangkok AA | 278 gr | *(kosong)* | **Penjualan Langsung** |
| ALU | 96 gr | *(kosong)* | **Internal** |

Dari contoh di atas, hanya baris pertama dan kedua yang akan muncul di Manajemen IDM.

---

## Bagian 2 — Manajemen IDM

**Menu:** Manajemen IDM

Manajemen IDM adalah proses memecah hasil grading IDM menjadi tiga bagian fisik sarang walet, disertai pencatatan susut (penyusutan berat).

### Langkah 1 — Pilih Item Grading

1. Buka menu **Manajemen IDM**
2. Klik tombol **Tambah IDM**
3. Di halaman Step 1, pilih **kategori** yang ingin diproses: **IDM A** atau **IDM B**
4. Daftar hasil grading yang sudah ditandai dengan kategori tersebut akan muncul
5. Centang satu atau beberapa baris yang ingin digabung menjadi satu batch IDM
6. Klik **Lanjut ke Step 2**

> Hanya hasil grading yang belum pernah diproses IDM yang akan muncul di daftar ini.

### Langkah 2 — Input Berat Masing-masing Bagian

Di halaman Step 2, input berat untuk tiga bagian fisik sarang:

| Bagian | Keterangan |
|--------|------------|
| **Perutan** | Bagian tengah/perut sarang walet |
| **Kakian** | Bagian tepi atau kaki sarang |
| **IDM** | Bagian utama terbaik |
| **Susut** | Dihitung otomatis = Berat Awal − (Perutan + Kakian + IDM) |

**Cara mengisi:**
1. Isi **Berat Perutan**, **Berat Kakian**, dan **Berat IDM**
2. Kolom **Susut** terisi otomatis — tidak perlu diisi manual
3. Pastikan total ketiga berat **tidak melebihi Berat Awal**
4. Klik **Lanjut ke Konfirmasi**
5. Periksa ringkasan data, lalu klik **Simpan Data**

### Batasan Manajemen IDM

- **Total berat tidak boleh melebihi berat awal.** Jika melebihi, tombol simpan akan dinonaktifkan dan muncul pesan peringatan.
- **Data IDM yang sudah ditransfer tidak bisa diedit atau dihapus.** Harus hapus Transfer IDM-nya terlebih dahulu.
- **Satu batch IDM hanya dari satu kategori** (IDM A atau IDM B) — tidak bisa campur.

---

## Bagian 3 — Transfer IDM

**Menu:** Barang Keluar → Transfer IDM

Transfer IDM adalah proses mencatat keluarnya bagian-bagian IDM (perutan, kakian, IDM) dari gudang.

### Langkah 1 — Pilih Barang yang akan Ditransfer

1. Buka menu **Barang Keluar → Transfer IDM**
2. Klik tombol **Tambah Data**
3. Gunakan filter jika perlu (Supplier, Grade, Kategori IDM, dll.)
4. Centang satu atau beberapa item yang ingin ditransfer
5. Klik **Lanjut ke Step 2**

Di daftar, setiap item menampilkan:
- Jenis bagian (perutan / kakian / IDM)
- **Sisa berat** yang belum ditransfer

> Item yang sudah pernah ditransfer secara penuh tidak akan muncul. Item yang pernah ditransfer sebagian akan tetap muncul dengan sisa berat yang tersisa.

### Langkah 2 — Konfirmasi & Input Detail Transfer

1. Periksa daftar barang yang dipilih
2. Isi kolom **Berat Transfer** untuk setiap item:
   - Default sudah terisi dengan sisa berat penuh
   - Bisa dikurangi jika ingin transfer sebagian saja
   - Nilai tidak boleh melebihi sisa berat yang tersedia
3. Isi **Tanggal Transfer**, **Lokasi Asal**, dan **Catatan** (opsional)
4. Klik **Simpan Transfer**

### Transfer Sebagian (Partial Transfer)

Sistem mendukung transfer sebagian. Contoh: item perutan 1.000 gr bisa ditransfer 400 gr dulu, dan sisanya 600 gr masih tersedia untuk transfer berikutnya.

| Kondisi Item | Tampil di Step 1? |
|---|---|
| Belum pernah ditransfer (1.000 gr) | Ya — Sisa: 1.000 gr |
| Sudah transfer 400 gr, sisa 600 gr | Ya — Sisa: 600 gr |
| Sudah ditransfer penuh | Tidak |

### Hapus Transfer IDM

Jika Transfer IDM perlu dibatalkan:

1. Buka detail Transfer IDM
2. Klik tombol **Hapus**
3. Sistem akan otomatis mengembalikan item ke daftar yang tersedia
4. Transfer IDM yang dihapus tidak bisa dipulihkan

---

## Ringkasan Batasan Sistem

| Kondisi | Aturan |
|---|---|
| Kategori IDM + Jenis Barang Keluar | Tidak bisa dipilih bersamaan dalam satu baris grading |
| Berat perutan + kakian + IDM | Tidak boleh melebihi berat awal |
| Edit/hapus Manajemen IDM | Tidak bisa jika sudah ada Transfer IDM aktif |
| Berat Transfer | Tidak boleh melebihi sisa berat yang tersedia |
| Transfer sebagian | Didukung — sisa berat tetap tersedia |
| Hapus Transfer IDM | Item kembali tersedia untuk ditransfer ulang |

---

## Contoh Alur Lengkap

```
1. Grading — tandai IDM A W2 (192 gr) dengan Kategori: IDM A

2. Manajemen IDM — pilih item tersebut, input:
   Berat Awal  : 192 gr
   Perutan     :  80 gr
   Kakian      :  60 gr
   IDM         :  45 gr
   Susut (auto):   7 gr  ← 192 - (80+60+45)

3. Transfer IDM — pilih bagian "Perutan 80 gr":
   Berat Transfer: 50 gr  ← transfer sebagian dulu
   → Sisa perutan: 30 gr (masih tersedia)

4. Transfer IDM (lagi) — pilih sisa "Perutan 30 gr":
   Berat Transfer: 30 gr
   → Perutan habis, tidak muncul lagi
```
