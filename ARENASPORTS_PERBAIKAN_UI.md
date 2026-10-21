# ArenaSports — catatan perbaikan UI dan akses

## Perubahan pada paket ini
- Halaman **Booking Saya** menjadi kartu booking. Ringkasan tampil terlebih dahulu; jadwal, durasi, metode/status pembayaran, dan ulasan berada di bagian **Lihat detail booking**.
- Tampilan login dan area user dipoles dengan gaya hijau, jarak elemen konsisten, dan layout responsif.
- Pengelolaan **Event** dan **Open Match** dibatasi untuk role `superadmin`, agar Admin Venue tidak memperoleh akses pengelolaan global.
- File `.env` lokal dihapus dari paket agar kredensial lokal tidak ikut tersebar. Salin `.env.example` menjadi `.env`.

## Instalasi Windows / XAMPP
1. Ekstrak proyek ke `C:\xampp\htdocs\penyewaan_lapangan`.
2. Buat database MySQL melalui phpMyAdmin dan sesuaikan `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD` pada `.env`.
3. Jalankan dari folder proyek:
   ```bat
   copy .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate --seed
   npm install
   npm run build
   php artisan optimize:clear
   php artisan serve --host=127.0.0.1 --port=8000
   ```
4. Buka `http://127.0.0.1:8000`.

## Catatan stack
Paket sumber yang diterima menggunakan Laravel, Blade, Vite, dan MySQL; `resources/js/app.js` masih berupa entry Vite dasar. Perubahan ini memperbaiki UI yang sudah ada dan akses route, **bukan** klaim bahwa seluruh frontend sudah dikonversi menjadi React SPA. Migrasi penuh ke React memerlukan pemindahan halaman, autentikasi/session/API, validasi, dan pengujian integrasi secara bertahap.
