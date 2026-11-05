# ArenaSports — Implementasi Marketplace Multi Venue

Tanggal pengerjaan: 5 November 2026

## Perubahan

1. Menambahkan kolom data pemilik, perusahaan, lokasi, dan koordinat pada pengajuan venue serta data perusahaan/lokasi venue.
2. Menambahkan tabel `venue_application_histories` untuk mencatat perubahan status dan catatan pemeriksa.
3. Menormalisasi status pengajuan lama menjadi `pending`, `revision`, `approved`, dan `rejected` melalui migration terpisah.
4. Mengizinkan akun `venue_admin` mengajukan venue tambahan setelah pengajuan sebelumnya disetujui.
5. Menambahkan pencarian dan filter status pada daftar pengajuan Superadmin.
6. Menambahkan tindakan minta revisi, persetujuan, dan penolakan dengan catatan wajib untuk revisi/penolakan.
7. Proses persetujuan menggunakan database transaction dan row lock; pembuatan venue dikaitkan ke `venue_application_id` agar tidak dibuat dua kali.
8. Menambahkan notifikasi database kepada pemohon untuk pengajuan dikirim, revisi, persetujuan, dan penolakan.
9. Menambahkan preview privat untuk PDF/gambar dokumen. Dokumen identitas tetap disimpan pada disk `local` (private), bukan folder publik.
10. Menambahkan pemilihan venue pada profil, keuangan, dan form lapangan agar satu akun dapat mengelola beberapa venue.
11. Form lapangan Admin Venue hanya menerima `venue_id` yang benar-benar dimiliki oleh akun tersebut.
12. Daftar lapangan publik, detail lapangan, ketersediaan, dan booking memeriksa agar lapangan yang terhubung ke venue nonaktif tidak tampil atau dipesan.
12. Mempertahankan route marketplace publik, route booking terautentikasi, serta integrasi pembayaran yang sudah ada.

## File utama yang berubah

- `app/Http/Controllers/MarketplaceController.php`
- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Middleware/CekLogin.php`
- `app/Models/User.php`
- `app/Models/Venue.php`
- `app/Models/VenueApplication.php`
- `app/Models/VenueApplicationHistory.php` (baru)
- `routes/web.php`
- `resources/views/user/venue-application.blade.php`
- `resources/views/admin/venue-applications.blade.php`
- `resources/views/admin/venue-profile.blade.php`
- `resources/views/admin/venue-finance.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/lapangan/create.blade.php`
- `resources/views/lapangan/edit.blade.php`
- dua migration baru di `database/migrations/`.

## Instalasi aman pada Windows/XAMPP

1. Backup database MySQL/MariaDB dan folder proyek terlebih dahulu.
2. Buka folder proyek hasil ekstraksi.
3. Buat `.env` dari `.env.example` bila belum ada, kemudian isi ulang credential database, `APP_KEY`, Midtrans, Google OAuth, dan WhatsApp/Fonnte milik sendiri. File `.env` lokal tidak disertakan dalam arsip distribusi.
4. Pastikan `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `APP_TIMEZONE=Asia/Jakarta` sesuai konfigurasi lokal.
5. Jalankan:

   ```bat
   composer install
   php artisan key:generate
   php artisan optimize:clear
   php artisan migrate:status
   ```

6. Tinjau migration yang berstatus `Pending`. Setelah backup dipastikan tersedia, jalankan:

   ```bat
   php artisan migrate
   php artisan storage:link
   php artisan route:list
   ```

7. Jalankan aplikasi:

   ```bat
   php artisan serve --host=127.0.0.1 --port=8000
   ```

8. Jika asset frontend perlu dibangun ulang:

   ```bat
   npm install
   npm run build
   ```

**Jangan menjalankan** `php artisan migrate:fresh`, `db:wipe`, atau menghapus database. Migration baru tidak dijalankan selama proses modifikasi arsip ini.

## Pemeriksaan yang dilakukan pada lingkungan pengerjaan

- `php artisan route:list --json`: berhasil.
- `php -l` pada file PHP aplikasi, route, model, dan migration: berhasil.
- Kompilasi Blade dan lint PHP terhadap hasil kompilasi: 51 file berhasil.
- Pemeriksaan `php artisan migrate:status`: tidak dapat menyambung karena runtime pengerjaan tidak memiliki driver PDO MySQL. Database tidak disentuh.
- `php artisan test`: tidak dapat dijalankan karena ekstensi PHP `dom`, `mbstring`, `xml`, dan `xmlwriter` tidak tersedia.
- `composer validate`: tidak dijalankan karena executable Composer tidak tersedia di runtime pengerjaan.
- Build Vite: tidak dijalankan karena `node_modules` tidak disertakan di arsip kerja. Jalankan `npm install` dan `npm run build` di Windows/XAMPP.
- Uji end-to-end Midtrans, Google OAuth, WhatsApp OTP, serta alur database multi-user belum dilakukan karena memerlukan credential dan database aktif.

## Catatan operasional

- Status lama `Menunggu`, `Disetujui`, `Ditolak`, dan `Perlu Revisi` dinormalisasi oleh migration baru. Jalankan `php artisan migrate` setelah backup agar data pengajuan lama dapat dibaca dengan status standar.
- Foto utama venue yang disetujui disalin ke disk publik; dokumen identitas/legalitas tetap berada di disk private.
- Pastikan symlink `public/storage` tersedia untuk menampilkan foto venue dari disk publik.
- Untuk keamanan, jangan mengirimkan `.env` ke repositori publik atau membagikan credential Midtrans/Google/Fonnte.
