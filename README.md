# AMIRA — aplikasi (MVP)

AMIRA (Artificial Mentor for Intelligent Reasoning and Assistance) adalah mentor AI yang dibentuk guru, mirip Gemini Gems. Ini MVP versi bangun ulang dari aplikasi AMIRA 2025.

**Pengguna hanya 18 tahun ke atas.** Ketentuan Gemini API dan Google Cloud melarang layanan yang ditujukan atau kemungkinan besar dipakai orang di bawah 18 tahun. Karena itu pendaftaran mewajibkan pernyataan usia.

## Menjalankan di komputer sendiri

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder   # opsional, data contoh
PHP_CLI_SERVER_WORKERS=4 php artisan serve --port=8010 --no-reload
```

`PHP_CLI_SERVER_WORKERS` dipakai supaya jawaban yang sedang mengalir tidak menahan permintaan lain. Di server produksi (Apache atau Nginx), matikan buffering untuk respons `text/event-stream`. AMIRA sudah mengirim header `X-Accel-Buffering: no`.

Akun contoh dari `DemoSeeder` (kata sandi `amira-demo`):

- `guru@amira.test`: guru, kelas "Pemrograman Web Dasar" dengan dua mentor
- `murid@amira.test`: murid dengan satu percakapan contoh

Supaya mentor bisa menjawab, masuk sebagai guru, buka **Pengaturan AI**, lalu tempel API key Gemini dari [aistudio.google.com/apikey](https://aistudio.google.com/apikey). Key Groq dari [console.groq.com/keys](https://console.groq.com/keys) bersifat opsional dan menjadi cadangan.

Tes: `php artisan test`

## Cara kerja

**Guru**

- Mengikuti daftar centang "Siap mengajar" di beranda guru: pasang key, buat kelas, buat mentor, bagikan tautan.
- Memasang key di **Pengaturan AI**. Key dites sebelum disimpan dan daftar modelnya ikut disimpan, jadi model kelas dipilih dari daftar, bukan diketik. Ada peringatan kalau model kelas tidak tersedia.
- Membuat kelas, lalu membagikan **tautan gabung** (`/gabung/KODE`), **QR layar penuh** untuk proyektor, atau kode 6 huruf.
- Membuat mentor per topik, bisa mulai dari **template** (Pelatih Debugging, Penjelas Konsep, Teman Latihan Soal, Pemeriksa Kode). Isinya: ikon, tujuan belajar, instruksi, Prompt Preset, tangga petunjuk, dan jelaskan balik. Template dan ikon ada di `config/amira_mentor.php`.
- Mengunggah materi PDF, TXT, atau MD. Teksnya diekstrak dan ikut dikirim sebagai rujukan, maksimal `AMIRA_MATERIAL_CHAR_BUDGET` karakter.
- Memakai "Coba sebagai murid" untuk menguji mentor.
- Melihat **Radar Kelas** (`app/Services/RadarKelas.php`) untuk 7 hari terakhir:
  - topik beserta jumlah yang sampai petunjuk 3, selesai, dan hasil jelaskan balik (paham/belum);
  - daftar **Perlu disapa**;
  - aktivitas per murid.

  Isi percakapan murid tidak ditampilkan.
- Menekan **Susun usulan** untuk mendapat rencana mini-lesson dari AI. Yang dikirim ke AI hanya angka per topik dan tujuan mentor, tanpa nama murid dan isi chat.

**Murid**

- Bergabung lewat tautan, QR, atau kode, memilih mentor, lalu chat. Jawaban tampil mengalir (streaming).
- Melampirkan gambar atau screenshot error lewat tombol, tempel (Ctrl/Cmd+V), atau seret. Gambar diperkecil ke 1600 px dan hanya bisa dibuka pemiliknya. Groq tidak bisa melihat gambar, jadi mentor cadangan meminta murid menyalin teks error-nya.
- Memakai tombol Prompt Preset, **Salin kode**, **Salin sitasi** (komentar `// Dibantu AMIRA (...)`), dan **Jelaskan balik**.

- Membaca pemberitahuan privasi sekali sebelum chat pertama: apa yang dilihat guru, ke mana pesan dikirim, dan larangan menulis data pribadi. Isinya juga ada di halaman `/privasi`.

**Akun**

- Lupa kata sandi lewat email (`/lupa-sandi`). Di komputer lokal `MAIL_MAILER=log`, jadi tautannya tercatat di `storage/logs/laravel.log`. Di server, isi pengaturan SMTP di `.env`.
- Ganti kata sandi di halaman Akun, untuk guru dan murid.

**Tangga petunjuk dikendalikan aplikasi**

- Setiap percakapan menyimpan tingkat bantuan (1 Pertanyaan penuntun → 2 Petunjuk konkret → 3 Contoh mirip).
- Prompt hanya berisi aturan tingkat yang sedang berlaku. Tingkat naik hanya lewat tombol **Petunjuk berikutnya**, bukan lewat permintaan di chat.
- Saat mentor menandai `[[selesai]]`, tangga kembali ke 1 untuk masalah berikutnya.
- Saat murid menjelaskan balik, mentor memberi label `[[paham: ya|belum]]` untuk Radar.
- Label tingkat tampil di setiap jawaban dan di penanda 1·2·3 di atas kotak pesan.

**Urutan key** (`app/Services/Ai/MentorChat.php`)

1. Key Gemini milik murid, bila dipasang di halaman Akun dan diizinkan guru. Ini menghemat kuota guru.
2. Key Gemini milik guru, selama jatah harian murid belum habis.
3. Key Groq milik guru sebagai cadangan, selama jatah harian murid belum habis.

Setiap key dibatasi per menit. Pengaturan Gemini ada di kelas, Groq di `AMIRA_GROQ_RPM`, dan key murid di `AMIRA_STUDENT_RPM`. Bila semua key penuh, murid melihat pesan "AMIRA sedang ramai" dan pesannya dikirim ulang otomatis.

**Topik Radar**

Mentor menulis label `[[topik: ...]]` di akhir setiap jawaban. Label ini dipotong sebelum jawaban disimpan dan ditampilkan, jadi tidak ada panggilan AI tambahan.

**Keamanan**

- Semua key disimpan terenkripsi dengan `APP_KEY`. Jika `APP_KEY` berganti, key harus dipasang ulang.
- Jawaban model dirender sebagai Markdown dengan HTML di-escape.

## Kuota gratis

Kuota Gemini gratis dihitung per project Google, bukan per key. Daily quota terisi ulang tengah malam waktu Pasifik. Kuota gratis kecil: menurut laporan pihak ketiga per Oktober 2026, Flash-Lite sekitar 10–15 permintaan per menit dan sekitar 500 per hari. Cek angka resminya di [aistudio.google.com/rate-limit](https://aistudio.google.com/rate-limit), lalu samakan isian "Batas permintaan per menit" di pengaturan kelas.

## Belum ada di MVP

- Bank Mentor antarguru.
- Ekspor Radar.
- Antrean di server. Saat ini antrean berupa kirim ulang otomatis di browser.
