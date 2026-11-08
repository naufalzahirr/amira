# Memasang AMIRA di app.amiraedu.com (cPanel Rumahweb)

Setiap push ke `main` menjalankan GitHub Actions **Build hosting branch**:

1. Menjalankan semua tes.
2. Memasang paket Composer tanpa paket dev.
3. Menyimpan hasilnya di branch **`hosting`**.

Server tidak perlu menjalankan `composer install`. Server cukup `git pull`.

File berikut tidak pernah ikut ke branch `hosting`:

- `.env`
- database SQLite
- lampiran gambar murid
- materi guru

Semuanya hanya ada di server.

## Yang perlu disiapkan di cPanel (sekali)

1. **Versi PHP 8.2 atau lebih baru.** Atur di *Select PHP Version* (atau *MultiPHP Manager*). Aktifkan ekstensi `pdo_sqlite`, `sqlite3`, `gd`, `mbstring`, `fileinfo`, `curl`, `openssl`, dan `zip`.
2. **Akun email** untuk lupa kata sandi, misalnya `admin@amiraedu.com`, di menu *Email Accounts*. Catat kata sandinya.
3. **Subdomain** `app.amiraedu.com` di menu *Domains* (atau *Subdomains*). Isi document root dengan:

   ```text
   amira-app/public
   ```

   Kalau kolomnya meminta path lengkap, isi `/home/<akun>/amira-app/public`.
4. **SSL**. Jalankan *SSL/TLS Status* → *Run AutoSSL* sampai `app.amiraedu.com` bergembok hijau.

## Pemasangan pertama (cPanel → Terminal)

Ganti `php` dengan PHP 8.2+ milik akun bila `php -v` menunjukkan versi lain, misalnya `/opt/alt/php82/usr/bin/php`.

```sh
cd ~
git clone --branch hosting --single-branch https://github.com/naufalzahirr/amira.git amira-app
cd amira-app
cp .env.production.example .env
nano .env                      # isi MAIL_PASSWORD (pakai tanda kutip bila ada spasi atau #)
php artisan key:generate --force
touch database/database.sqlite
php artisan migrate --force
php artisan optimize
chmod -R u+rwX storage bootstrap/cache database
```

Buka `https://app.amiraedu.com`, lalu:

1. Daftar sebagai **Guru**.
2. Pasang key Gemini dan Groq di **Pengaturan AI**.
3. Buat kelas.

Jangan menjalankan `DemoSeeder` di server, karena seeder itu membuat akun contoh dengan kata sandi yang tertulis di README.

`APP_KEY` mengunci key Gemini/Groq yang tersimpan. **Jangan pernah mengganti `APP_KEY` setelah aplikasi dipakai.** Kalau berganti, semua key harus dipasang ulang.

## Memperbarui

1. Push ke `main`.
2. Tunggu **Build hosting branch** hijau di tab *Actions* GitHub.
3. Jalankan di Terminal cPanel:

```sh
cd ~/amira-app
git pull --ff-only
php artisan migrate --force
php artisan optimize
```

`build-source.txt` mencatat commit `main` yang sedang terpasang.

Setelah mengubah `.env`, jalankan `php artisan optimize` lagi.

## Cadangan

Data penting ada di tiga tempat:

- `~/amira-app/.env`, terutama `APP_KEY`
- `~/amira-app/database/database.sqlite`
- `~/amira-app/storage/app/private/`, berisi materi dan lampiran

Sertakan ketiganya dalam *Backup* cPanel.

## Kalau ada masalah

- **Halaman error 500.** Lihat `~/amira-app/storage/logs/laravel-*.log`.
- **Jawaban chat muncul sekaligus, tidak mengalir.** Server atau proxy menahan respons. Chat tetap berfungsi, hanya tidak bertahap. `public/.htaccess` sudah mematikan kompresi untuk `/kirim`; bila masih tertahan, tanyakan ke support Rumahweb soal *output buffering* untuk respons `text/event-stream`.
- **Unggah materi atau gambar gagal.** `public/.user.ini` menaikkan batas unggah ke 20 MB. Bila hosting mengabaikannya, atur `upload_max_filesize` dan `post_max_size` di *Select PHP Version* → *Options*.
- **Tautan gabung atau QR masih `http://`.** Pastikan `APP_URL` memakai `https://`, lalu jalankan `php artisan optimize`.
