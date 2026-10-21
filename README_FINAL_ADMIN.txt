PANDUAN MENJALANKAN PROJECT LARAVEL ADMIN PENYEWAAN LAPANGAN

LOGIN ADMIN
Email    : admin@gmail.com
Password : admin123

FITUR FINAL
1. Dashboard modern hijau-putih dengan statistik realtime.
2. Tombol Export CSV dan Export Excel langsung muncul di dashboard.
3. Halaman Export Laporan punya filter tanggal/status + tombol export.
4. Management lapangan bisa tambah, edit, hapus, upload foto, preview foto.
5. Foto admin bisa diganti dari menu Profil.
6. Notifikasi komentar dan pembayaran tampil di topbar + badge menu.
7. Dashboard lama /dashboard diarahkan ke /admin/dashboard.

CARA JALANKAN
1. Extract ZIP.
2. Buka folder project di terminal / VS Code.
3. Jalankan:
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan optimize:clear
   php artisan serve

DATABASE SQLITE
File database sudah tersedia di:
database/database.sqlite

Pastikan .env berisi:
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file

Kalau mau reset data demo:
php artisan migrate:fresh --seed

BUKA WEBSITE
http://127.0.0.1:8000/login

CATATAN ERROR UMUM
Jika muncul could not find driver, aktifkan sqlite di php.ini:
extension=pdo_sqlite
extension=sqlite3

Jika tampilan tidak berubah:
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
