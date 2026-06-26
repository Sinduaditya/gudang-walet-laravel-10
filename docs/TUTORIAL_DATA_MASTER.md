# Tutorial Pengisian Data Master - Sistem Gudang Walet

Data master adalah data awal yang perlu disiapkan sebelum sistem bisa digunakan. Ibarat buku catatan pegawai, nama lokasi gudang, atau daftar supplier — semua harus diisi terlebih dahulu agar transaksi harian bisa berjalan lancar.

---

## Urutan Pengisian

Pengisian harus dilakukan sesuai urutan berikut karena beberapa data saling terkait:

1. Supplier
2. Lokasi Gudang
3. Grade Supplier
4. Kategori Grade (Parent Grade Company)
5. Grade Produk (Grade Company)
6. Pengelompokan Grade ke Kategori (Bulk Assignment)

---

## 1. Supplier

**Menu:** Suppliers

Supplier adalah orang atau perusahaan yang menyetor sarang walet ke gudang. Setiap kali ada barang masuk, sistem akan menanyakan datang dari supplier mana — maka data ini harus sudah ada lebih dulu.

### Isi Data Berikut

| Kolom | Wajib Diisi | Keterangan |
|-------|-------------|------------|
| Nama Supplier | Ya | Nama lengkap petani atau perusahaan pemasok |
| Alamat | Tidak | Alamat tempat tinggal atau kantor supplier |
| Contact Person | Tidak | Nama atau nomor telepon yang bisa dihubungi |

### Cara Mengisi

1. Buka menu **Suppliers**
2. Klik tombol **Tambah Supplier**
3. Isi **Nama Supplier** — ini wajib diisi
4. Isi **Alamat** dan **Contact Person** jika informasinya sudah ada
5. Klik **Simpan**

### Contoh Isian

| Nama Supplier | Alamat | Contact Person |
|---------------|--------|----------------|
| Pak Budi Santoso | Jl. Merdeka No. 12, Kalimantan | 0812-3456-7890 |
| CV. Walet Makmur | Jl. Raya Borneo No. 5, Palangkaraya | Ahmad - 0813-9876-5432 |

---

## 2. Lokasi Gudang

**Menu:** Locations

Lokasi adalah tempat penyimpanan sarang walet di dalam atau di luar gudang. Setiap kali barang masuk, keluar, atau dipindahkan, sistem akan menanyakan dari mana dan ke mana — maka lokasi harus sudah tercatat.

### Isi Data Berikut

| Kolom | Wajib Diisi | Keterangan |
|-------|-------------|------------|
| Nama Lokasi | Ya | Nama ruang atau gudang penyimpanan |
| Deskripsi | Tidak | Keterangan tambahan tentang lokasi ini |

### Cara Mengisi

1. Buka menu **Locations**
2. Klik tombol **Tambah Lokasi**
3. Isi **Nama Lokasi** — ini wajib diisi
4. Isi **Deskripsi** jika perlu
5. Klik **Simpan**

### Contoh Isian

| Nama Lokasi | Deskripsi |
|-------------|-----------|
| Gudang Utama | Gudang penyimpanan utama di lantai 1 |
| Gudang Sortir | Ruang khusus untuk proses grading |
| Gudang Transit | Tempat penampungan sementara barang masuk |

---

## 3. Grade Supplier

**Menu:** Grade Supplier

Grade Supplier adalah penanda kualitas sarang walet **menurut penilaian supplier itu sendiri** saat barang disetor ke gudang. Biasanya sudah ditetapkan oleh petani atau pemasok sebelum barang datang.

Ini berbeda dengan grade yang ditetapkan perusahaan — grade supplier hanya dipakai saat pencatatan barang masuk.

### Isi Data Berikut

| Kolom | Wajib Diisi | Keterangan |
|-------|-------------|------------|
| Nama Grade | Ya | Nama atau kode grade dari supplier |
| Gambar | Tidak | Foto contoh fisik grade ini (format jpg/png, ukuran maks 2MB) |
| Deskripsi | Tidak | Penjelasan singkat tentang ciri-ciri grade ini |

### Cara Mengisi

1. Buka menu **Grade Supplier**
2. Klik tombol **Tambah Grade Supplier**
3. Isi **Nama Grade** — ini wajib diisi
4. Unggah **Gambar** referensi jika ada
5. Isi **Deskripsi** untuk membantu pengguna lain mengenali grade ini
6. Klik **Simpan**

### Contoh Isian

| Nama Grade | Deskripsi |
|------------|-----------|
| Grade A | Sarang utuh, bersih, warna putih cerah |
| Grade B | Sarang masih utuh, sedikit kotor atau ada yang patah |
| Grade C | Sarang patah atau pecahan, masih bisa diolah |

---

## 4. Kategori Grade Produk

**Menu:** Parent Grade Companies

Kategori Grade adalah pengelompokan besar untuk grade-grade milik perusahaan. Gunanya seperti "folder" — di dalamnya nanti akan berisi grade-grade yang lebih spesifik.

Contoh: perusahaan punya dua kategori besar, yaitu **IDM A** dan **IDM B**. Masing-masing kategori nanti akan memiliki grade-grade di bawahnya seperti IDM A1, IDM A2, dan seterusnya.

> Isi bagian ini sebelum mengisi Grade Produk di langkah berikutnya.

### Isi Data Berikut

| Kolom | Wajib Diisi | Keterangan |
|-------|-------------|------------|
| Nama Kategori | Ya | Nama kategori besar grade perusahaan, harus unik |
| Gambar | Tidak | Foto referensi untuk kategori ini (format jpg/png, maks 2MB) |
| Deskripsi | Tidak | Keterangan tentang kategori ini |

### Cara Mengisi

1. Buka menu **Parent Grade Companies**
2. Klik tombol **Tambah Parent Grade**
3. Isi **Nama Kategori** — ini wajib diisi dan tidak boleh sama dengan kategori lain
4. Unggah **Gambar** jika ada
5. Isi **Deskripsi** jika perlu
6. Klik **Simpan**

### Contoh Isian

| Nama Kategori | Deskripsi |
|---------------|-----------|
| IDM A | Kategori sarang kualitas premium |
| IDM B | Kategori sarang kualitas standar |
| Serpihan | Kategori pecahan atau serpihan sarang |

---

## 5. Grade Produk

**Menu:** Grade Company

Grade Produk adalah kelas atau tingkatan kualitas sarang walet yang ditetapkan oleh **perusahaan sendiri** setelah proses sortir atau grading selesai. Grade inilah yang dipakai dalam semua transaksi penjualan, transfer, dan pencatatan stok.

Setelah dibuat, grade-grade ini akan dikelompokkan ke dalam Kategori Grade (langkah 6).

### Isi Data Berikut

| Kolom | Wajib Diisi | Keterangan |
|-------|-------------|------------|
| Nama Grade | Ya | Nama atau kode grade yang digunakan perusahaan |
| Gambar | Tidak | Foto contoh fisik grade ini (format jpg/png, maks 2MB) |
| Deskripsi | Tidak | Penjelasan ciri-ciri kualitas grade ini |

### Cara Mengisi

1. Buka menu **Grade Company**
2. Klik tombol **Tambah Grade Company**
3. Isi **Nama Grade** — ini wajib diisi
4. Unggah **Gambar** referensi jika ada
5. Isi **Deskripsi** untuk memperjelas standar kualitas grade ini
6. Klik **Simpan**

### Contoh Isian

| Nama Grade | Deskripsi |
|------------|-----------|
| IDM A1 | Sarang premium kelas 1, utuh sempurna tanpa cacat |
| IDM A2 | Sarang premium kelas 2, utuh dengan sedikit cacat ringan |
| IDM B1 | Sarang standar kelas 1 |
| IDM B2 | Sarang standar kelas 2 |
| Serpihan A | Serpihan dari grade premium |

---

## 6. Pengelompokan Grade ke Kategori

**Menu:** Bulk Assignment

Setelah Kategori Grade dan Grade Produk dibuat, langkah terakhir adalah memasukkan setiap grade ke dalam kategori yang sesuai. Proses ini yang disebut Bulk Assignment.

Tanpa langkah ini, grade-grade yang sudah dibuat tidak akan terkelompok dan tidak bisa ditampilkan secara hierarki di laporan stok.

### Cara Mengelompokkan Grade (Pertama Kali)

1. Buka menu **Bulk Assignment**
2. Klik tombol **Tambah Assignment**
3. Pilih **Kategori Grade** yang menjadi tujuan
4. Centang satu atau beberapa **Grade Produk** yang ingin dimasukkan ke kategori tersebut
   - Hanya grade yang belum masuk ke kategori manapun yang bisa dipilih di sini
5. Klik **Simpan**

### Cara Mengubah atau Memperbaiki Pengelompokan

1. Buka menu **Bulk Assignment**
2. Cari kategori yang ingin diubah, lalu klik **Edit**
3. Di halaman edit, tersedia dua pilihan:
   - **Lepas grade** dari kategori ini (unassign)
   - **Tambah grade baru** ke kategori ini
4. Simpan perubahan

### Contoh Hasil Pengelompokan

```
Kategori: IDM A
├── IDM A1
└── IDM A2

Kategori: IDM B
├── IDM B1
└── IDM B2

Kategori: Serpihan
└── Serpihan A
```

---

## Gambaran Umum Alur Data Master

Berikut gambaran bagaimana data master yang sudah diisi akan digunakan dalam operasional sehari-hari:

```
Barang datang dari Supplier
        ↓
Dicatat ke Lokasi Gudang tertentu
dengan Grade Supplier dari petani
        ↓
Proses Sortir / Grading
        ↓
Hasilnya dicatat sebagai Grade Produk
yang sudah dikelompokkan ke Kategori Grade
        ↓
Digunakan untuk Penjualan dan Transfer
```

---

## Hal yang Perlu Diperhatikan

- **Jangan hapus data master** yang sudah pernah digunakan dalam transaksi. Sistem akan otomatis mencegah penghapusan, tapi lebih baik dihindari dari awal.
- **Nama grade harus konsisten** dan mencerminkan standar yang sudah disepakati internal perusahaan, agar tidak membingungkan saat proses sortir atau penjualan.
- **Satu grade hanya bisa masuk ke satu kategori** dalam satu waktu. Jika ingin dipindah, lepas dulu dari kategori lama, baru masukkan ke kategori baru.
- Semua halaman daftar data master dilengkapi fitur **ekspor ke Excel** untuk keperluan backup atau pelaporan.
- Gunakan fitur **pencarian** di setiap halaman untuk menemukan data dengan cepat tanpa perlu scroll panjang.
