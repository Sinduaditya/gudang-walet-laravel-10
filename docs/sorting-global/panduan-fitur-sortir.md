# Panduan Fitur Sortir Bahan, Grading Internal, dan Penjualan Sortir

Dokumen ini menjelaskan secara detail tentang alur kerja (workflow), logika penyimpanan data, dan aturan penghapusan (rollback) pada modul **Sortir Bahan**, **Grading Internal (Pecah Stok)**, dan **Penjualan Langsung dari Sortir**.

---

## 📌 1. Struktur Data & Hierarki Grade

Sistem sortir menggunakan hierarki dua tingkat untuk manajemen grade:

```
Parent Grade Company (Kategori Utama)
   └── Grade Company (Detail Grade / Child)
```

**Contoh Riil:**
* **Mangkok** (Parent)
  * Mangkok W1 (Child)
  * Mangkok W2 (Child)
* **IDM** (Parent)
  * IDM A (Child)
  * IDM B (Child)

> [!NOTE]
> Sistem memperbolehkan penyimpanan stok pada tingkat **Parent saja** (tanpa detail child grade) maupun spesifik ke tingkat **Child grade**.

---

## 📥 2. Fitur Sortir Bahan (Input Masuk)

Fitur ini digunakan untuk memasukkan stok awal bahan sortir ke dalam sistem.

* **Alur Input:**
  * User memasukkan berat barang (gram), memilih **Parent Grade** (wajib), dan memilih **Child Grade** (opsional).
  * Data disimpan sebagai record bertipe `masuk` di tabel `sort_materials`.
* **Logika Stok:**
  * Jika **Child Grade** kosong (hanya pilih Parent): Berat barang akan langsung menambah kolom `stock` (cache) pada tabel `parent_grade_companies`.
  * Jika **Child Grade** dipilih: Stok disimpan di level detail. Cache `parent_grade_companies.stock` **tidak bertambah** karena stok tersebut sudah terspesifikasi ke child.

---

## 🔄 3. Fitur Grading Internal (Pecah Stok)

Grading internal adalah proses memecah stok dari satu Parent asal (sumber mentah) ke beberapa Parent atau Child tujuan.

**Contoh Kasus:**
User memproses **70 gram** stok mentah **Mangkok** menjadi:
1. **Lempeng** (Parent saja, tanpa detail grade) = **60 gram**
2. **IDM A** (Parent: IDM, Child: IDM A) = **10 gram**

### Alur Kerja & Penyimpanan Data:
1. **Validasi Stok Asal:**
   Sistem memastikan stok mentah Parent asal (Mangkok) mencukupi untuk diproses (dalam contoh ini, minimal tersedia 70 gram).
2. **Record KELUAR (Pengurangan Sumber):**
   Sistem membuat 1 record bertipe `keluar` dengan `parent_grade_company_id = Mangkok` seberat **70 gram**. Kolom cache `stock` pada Parent Mangkok dikurangi 70 gram.
3. **Record MASUK (Penambahan Target):**
   Sistem membuat record bertipe `masuk` untuk setiap target pecahan:
   * Target 1: Parent **Lempeng** (60 gr). Karena tidak ada child grade, kolom cache `stock` Parent Lempeng **bertambah 60 gram**.
   * Target 2: Child **IDM A** (10 gr). Kolom cache Parent IDM **tidak bertambah**, tetapi stok dinamis `IDM A` bertambah 10 gram.
4. **Relasi Hubungan (grading_source_parent_id):**
   Setiap record MASUK target pecahan akan menyimpan nilai `grading_source_parent_id` yang merujuk ke Parent asal (`Mangkok`). Ini penting untuk melacak asal-usul barang saat pembatalan/penghapusan.

---

## 💰 4. Penjualan Langsung dari Sortir Bahan

Fitur ini digunakan untuk menjual barang yang berada di dalam stok sortir.

* **Penjualan Tingkat Child Grade:**
  * User memilih child grade (misal: `IDM A`).
  * Sistem memvalidasi stok riil child tersebut (menggunakan penjumlahan bersih `masuk` - `keluar`).
  * Jika stok cukup, record `keluar` dibuat dengan mencantumkan `grade_company_id` terkait.
* **Penjualan Tingkat Parent Grade:**
  * User menjual langsung dari parent (misal: `Lempeng` tanpa detail grade).
  * Sistem memvalidasi berdasarkan cache stok parent (`parent_grade_companies.stock`).
  * Jika cukup, record `keluar` dibuat dan cache stok parent terkait langsung dikurangi.

---

## 🗑️ 5. Aturan Penting Penghapusan & Rollback Data

Untuk menjaga konsistensi stok dan mencegah terjadinya stok minus, sistem menerapkan aturan ketat dalam penghapusan data.

### ⚠️ Mengapa Harus Hapus dari Child/Target Terlebih Dahulu?

Jika Anda melakukan **Grading Internal** (misal: memecah Mangkok menjadi Lempeng dan IDM A), Anda **tidak bisa langsung menghapus** record masuk Mangkok yang asli jika sisa stok di cache Mangkok tidak mencukupi untuk dikurangi kembali.

> [!IMPORTANT]
> **Aturan Utama Pembatalan Grading:**
> Batalkan atau hapus terlebih dahulu hasil pecahan (Target/Child) satu per satu, barulah Anda bisa menghapus/membatalkan sumber asal (Parent/Source).

### Mekanisme Detail Saat Menghapus Hasil Pecahan (Target):

Ketika user menghapus salah satu target hasil pecahan (misalnya menghapus `Lempeng 60 gr` atau `IDM A 10 gr`):

1. **Validasi Stok Target:**
   Sistem memastikan barang target tersebut belum terjual atau diproses lagi.
   * Untuk child (`IDM A`), sistem mengecek apakah stok dinamisnya masih mencukupi untuk ditarik kembali sebesar 10 gram.
   * Untuk parent (`Lempeng`), sistem mengecek apakah cache stok Parent Lempeng masih mencukupi (minimal 60 gram).
2. **Rollback Stok ke Parent Asal (Source):**
   Menggunakan kolom `grading_source_parent_id` (yaitu `Mangkok`), sistem akan **mengembalikan (menambah) stok** ke Parent asal.
   * Jika menghapus `Lempeng 60 gr` $\rightarrow$ Stok Mangkok bertambah kembali **+60 gr**.
   * Jika menghapus `IDM A 10 gr` $\rightarrow$ Stok Mangkok bertambah kembali **+10 gr**.
3. **Penyesuaian Record KELUAR Sumber:**
   Sistem mencari record `keluar` tipe grading asal pada Parent asal (`Mangkok`) yang dibuat pada hari yang sama.
   * Berat record `keluar` tersebut akan dikurangi sebesar berat target yang dihapus.
   * Jika semua target pecahan telah dihapus (berat record `keluar` menjadi $\le 0$), maka record `keluar` tersebut otomatis ikut dihapus dari database.
4. **Pengurangan Stok Target:**
   * Jika target yang dihapus adalah tingkat parent (`Lempeng`), maka cache stok Parent Lempeng dikurangi sebesar berat yang dihapus agar seimbang.
   * Jika target yang dihapus adalah tingkat child (`IDM A`), tidak ada cache parent yang perlu dikurangi (karena dari awal tidak menambah cache parent).
