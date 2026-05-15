# Deploy ke Shared Hosting

## Prasyarat
- Akses FTP/cPanel/File Manager ke shared hosting
- PHP 8.1+ dengan ekstensi: pdo_mysql, mbstring, xml, curl, zip
- MySQL 5.7+ atau MariaDB 10.3+

---

## Langkah 1: Persiapan di Local

```bash
# 1. Pastikan kode sudah di-commit
git add .
git commit -m "fix: prevent negative stock - add validation to all transaction methods"

# 2. Hapus folder vendor dan node_modules (akan di-upload terpisah)
# atau biarkan dan jalankan composer install di server

# 3. Buat file .env.production jika perlu
```

---

## Langkah 2: Upload ke Shared Hosting

### Opsi A: Via FTP/cPanel File Manager

1. **Upload semua file** KE FOLDER `public_html` atau subdomain Anda:
   ```
   public_html/
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── public/          <-- ini isinya yang masuk public_html
   ├── resources/
   ├── routes/
   ├── storage/
   ├── vendor/          <-- upload ini juga
   ├── .env
   ├── artisan
   ├── composer.json
   └── composer.lock
   ```

2. **PENTING**: Pastikan structure folder benar:
   - Semua file Laravel di luar folder `public` harus di luar `public_html`
   - Hanya ISI dari `public/` yang masuk `public_html`

### Opsi B: Via SSH (jika shared hosting mendukung)

```bash
# 1. Clone repository
git clone https://github.com/username/gudang-walet-laravel-10.git

# 2. Install dependency
composer install --no-dev --optimize-autoloader

# 3. Set permission
chmod -R 755 storage bootstrap/cache
chown -R username:username .
```

---

## Langkah 3: Konfigurasi .env

Edit file `.env` di server:

```env
APP_NAME="Gudang Walet"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx  # Generate baru
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username_db
DB_PASSWORD=password_db

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

**Generate APP_KEY baru:**
```bash
php artisan key:generate
```

---

## Langkah 4: Set Permission

```bash
# Shared hosting (cPanel)
chmod 755 storage bootstrap/cache
chmod 644 .env

# Jika ada error, coba:
chmod -R 775 storage bootstrap/cache
```

---

## Langkah 5: Database Migration (JIKA ADA PERUBAHAN STRUKTUR)

```bash
php artisan migrate
```

**CATATAN**: Jika tidak ada perubahan struktur database, skip langkah ini.

---

## Langkah 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

---

## Langkah 7: Verifikasi

1. Buka browser → `https://domain-anda.com`
2. Login dan test:
   - Penjualan (sell)
   - Transfer Internal
   - Transfer External
   - Receive External
3. Cek apakah error "Stok tidak mencukupi" muncul dengan benar saat coba input melebihi stock

---

## Troubleshooting

### Error 500 Internal Server Error
```bash
# cek storage/logs/laravel.log
# atau enable APP_DEBUG=true sementara

chmod -R 775 storage bootstrap/cache
```

### Error "No such file or directory" pada storage
```bash
php artisan storage:link
```

### Database Connection Error
- Pastikan kredensial DB di .env benar
- Pastikan MySQL service running

### Permission Denied
```bash
# cPanel biasanya pakai:
chmod 755 storage bootstrap/cache
chown -R username:username .
```

---

## Update Kode di Production

1. Upload file yang berubah via FTP
2. Atau kalau pake git:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate
php artisan optimize:clear
```

---

## Struktur Folder Shared Hosting (cPanel)

```
/home/username/
├── public_html/           <-- Document Root
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── app/                  <-- Laravel app
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/              <-- Writable
├── vendor/
├── .env
└── artisan
```

---

## Catatan Penting

1. **LAKUKAN BACKUP** sebelum deploy:
   - Backup database
   - Backup file

2. **TEST di local dulu** sebelum upload ke production

3. **JIKA ADA ERROR** setelah deploy:
   - Cek `storage/logs/laravel.log`
   - Set `APP_DEBUG=true` untuk lihat error detail (sementara, lalu matikan lagi)

4. Untuk keamanan, matikan `APP_DEBUG` di production:
   ```env
   APP_DEBUG=false
   ```