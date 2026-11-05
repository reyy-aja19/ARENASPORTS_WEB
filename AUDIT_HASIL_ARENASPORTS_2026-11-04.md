# Hasil Audit dan Perbaikan ArenaSports — 4 November 2026

## Ruang lingkup pemeriksaan
Project Laravel pada arsip `ArenaSports_Profil_Gmail_WhatsApp_OTP.zip` diperiksa pada struktur aplikasi, controller, model, middleware, route, migration, Blade, konfigurasi, dan dependensi.

## Perubahan yang diterapkan

1. **Google OAuth:** callback sekarang mensyaratkan email Google valid dan `verified_email=true` sebelum membuat akun atau menautkan email ke akun lokal. Ini mencegah penautan akun hanya berdasarkan email yang belum terverifikasi.
2. **WhatsApp OTP:** nomor untuk login dinormalisasi dan divalidasi panjangnya (10–15 digit), sama seperti validasi dasar saat registrasi.
3. **Booking:** menghapus kondisi `whereIn(status...)` yang terduplikasi pada pemeriksaan bentrok di transaksi booking; pemeriksaan bentrok dan row lock tetap dipertahankan.
4. **Zona waktu:** aplikasi sekarang menggunakan `APP_TIMEZONE` dengan default `Asia/Jakarta`; `.env.example` juga mencantumkan `APP_TIMEZONE=Asia/Jakarta`. Ini menyelaraskan validasi tanggal/jam booking dengan waktu operasional Indonesia.
5. **Kebersihan dan keamanan arsip:** file `env` lokal yang berisi konfigurasi/credential tidak disertakan; file payload transaksi kosong dan log/session runtime lokal juga dibersihkan dari paket. `.gitignore` ditambah untuk mengabaikan file `env` tersebut.

## Pemeriksaan yang berhasil

- `php artisan route:list --json`: berhasil, terdeteksi 108 route.
- Pemeriksaan sintaks PHP dengan `php -l` pada file PHP aplikasi, bootstrap, config, migration, route, dan test: tidak ditemukan kesalahan sintaks.
- Kompilasi dan pemeriksaan sintaks Blade secara langsung: 51 file Blade berhasil dikompilasi dan lolos `php -l`.
- Audit migration statis: 25 file migration ditemukan; tidak ditemukan nama tabel yang dibuat berulang dengan `Schema::create()`.

## Pemeriksaan yang belum dapat dijalankan di lingkungan audit

- `php artisan migrate:status`: tidak dapat dijalankan karena runtime audit tidak memiliki driver PDO MySQL (`could not find driver`). Tidak ada migration yang dijalankan dan database pengguna tidak disentuh.
- `php artisan test`: PHPUnit tidak dapat berjalan karena ekstensi PHP `dom`, `mbstring`, `xml`, dan `xmlwriter` tidak tersedia di runtime audit.
- `php artisan optimize:clear` dan `php artisan view:cache`: terhenti karena ekstensi `DOMDocument` tidak tersedia.
- `npm run build`: binary `vite` pada arsip tidak memiliki izin eksekusi di lingkungan Linux audit. Pemanggilan langsung Node juga terhenti karena optional dependency Rollup untuk Linux tidak tersedia. Jalankan `npm install` di Windows/XAMPP pengguna lalu `npm run build`.
- Integrasi Google OAuth, Fonnte, Gmail SMTP, dan Midtrans tidak dapat diuji end-to-end tanpa credential aktif dan endpoint/tunnel yang dapat dijangkau.

## Temuan yang masih memerlukan implementasi/pengujian lanjutan

1. Perubahan email pada profil hanya mengosongkan `email_verified_at`; alur verifikasi email baru belum ditemukan.
2. Perubahan nomor WhatsApp pada profil belum mewajibkan OTP ke nomor baru.
3. Notifikasi WhatsApp aktivitas akun masih dikirim sinkron dari request; belum dipindahkan ke queue.
4. Template email aktivitas akun saat ini menggunakan `MailMessage` standar, belum berupa template HTML bermerek yang lengkap.
5. Pengujian isolasi multi-venue, seluruh role, double booking pada dua koneksi database, callback Midtrans, dan Open Match memerlukan database MySQL aktif serta pengujian integrasi.

## Langkah aman setelah ekstraksi di Windows/XAMPP

1. Buat backup database terlebih dahulu.
2. Salin `.env.example` menjadi `.env`, lalu isi credential lokal sendiri. Jangan gunakan file `env` dari backup lama.
3. Pastikan `APP_TIMEZONE=Asia/Jakarta`, `APP_URL`, koneksi MySQL, dan `APP_KEY` benar.
4. Jalankan `composer install` bila vendor tidak lengkap; jangan hapus folder `vendor` yang sudah ada.
5. Jalankan `npm install` kemudian `npm run build` bila ingin membangun asset frontend.
6. Periksa migration dengan `php artisan migrate:status`. Setelah backup dan meninjau daftar migration yang pending, jalankan `php artisan migrate` (bukan `migrate:fresh`).
7. Jalankan `php artisan optimize:clear`, `php artisan route:list`, lalu `php artisan test` setelah ekstensi PHP yang dibutuhkan tersedia.
8. Buat symlink foto dengan `php artisan storage:link` bila belum tersedia.

**Catatan:** laporan ini mencatat hasil yang benar-benar dapat diperiksa di runtime audit. Fitur yang belum diuji tidak dinyatakan berhasil.
