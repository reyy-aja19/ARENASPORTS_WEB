# Perbaikan notifikasi Midtrans Sandbox

## Perubahan
- Endpoint notifikasi tetap `POST /payments/midtrans/notification` dan dikecualikan dari CSRF karena autentikasi callback menggunakan signature Midtrans.
- Controller menerima payload melalui `$request->all()` agar dapat membaca JSON/form payload yang telah diparse Laravel.
- Controller menulis log `Midtrans notification received` saat callback masuk, sehingga mudah dibedakan dari masalah URL/tunnel.
- Model Payment melakukan cast `reward_points_awarded` sebagai boolean.
- Callback memvalidasi signature dan nominal, memperbarui pembayaran/booking secara transaksional, dan mencegah poin hadiah diberikan dua kali.

## Pemasangan di Windows CMD
Jalankan perintah dari folder proyek Laravel:

```cmd
copy app\Http\Controllers\MidtransController.php app\Http\Controllers\MidtransController.php.bak
copy app\Models\Payment.php app\Models\Payment.php.bak
```

Ekstrak arsip perbaikan, lalu salin isi folder `penyewaan_lapangan` ke folder proyek yang digunakan (izinkan overwrite untuk file yang sama). Jangan mengganti `.env` milikmu dengan `.env.example`; pertahankan kredensial lokal.

Pastikan `.env` memiliki kredensial Sandbox milik akun Midtrans sendiri:

```env
MIDTRANS_SERVER_KEY=SB-...
MIDTRANS_CLIENT_KEY=SB-...
MIDTRANS_IS_PRODUCTION=false
```

Jangan membagikan nilai key tersebut. Setelah salin file:

```cmd
php -l app\Http\Controllers\MidtransController.php
php -l app\Models\Payment.php
php artisan optimize:clear
php artisan migrate --force
php artisan route:list --path=payments
```

Perintah migrate diperlukan hanya untuk menerapkan migrasi yang belum dijalankan; cek status migrasi sebelum menjalankannya pada database berisi data penting.

## URL notifikasi
Pada Midtrans Sandbox, isi Payment Notification URL dengan URL tunnel aktif diikuti:

`/payments/midtrans/notification`

Contoh format: `https://URL-TUNNEL-AKTIF.trycloudflare.com/payments/midtrans/notification`

URL Quick Tunnel dapat berubah setelah tunnel dimulai ulang. Laravel server dan `cloudflared` harus tetap berjalan saat pengujian.

## Pemeriksaan log
Setelah melakukan transaksi Sandbox baru, jalankan:

```cmd
findstr /i /c:"Midtrans notification received" /c:"Invalid Midtrans notification signature" /c:"Midtrans amount mismatch" /c:"Midtrans notification processing failed" storage\logs\laravel.log
```

Jika callback diterima, log `Midtrans notification received` akan muncul. Jangan menandai pembayaran sebagai lunas secara manual hanya berdasarkan redirect browser; status lunas harus berasal dari callback tervalidasi.
