# Catatan Perbaikan Midtrans ArenaSports

## Temuan dari source code yang diberikan

1. `payments.transaction_id` dipakai untuk menyimpan `order_id` buatan aplikasi saat checkout.
2. Pada webhook, `transaction_id` dari payload Midtrans dipakai sebagai ID transaksi untuk memanggil API status. Dengan demikian satu kolom berpotensi dipakai untuk dua arti berbeda: order ID aplikasi dan transaction ID Midtrans.
3. Webhook sudah menghitung signature SHA-512 dan memeriksa status API, tetapi pencocokan order/status menjadi rentan ketika ID DANA yang dikembalikan API tidak sama formatnya dengan order ID aplikasi.
4. Migration tambahan pada ZIP sumber membuat `reward_points_awarded`, dan model sudah melakukan casting boolean. Pemberian poin dilakukan dalam transaksi DB dengan lock, lalu flag disimpan untuk idempotensi.
5. `database/arena_sports_create.sql` yang disertakan hanya berisi `CREATE DATABASE IF NOT EXISTS arena_sports ...`; file itu bukan dump schema/tabel. Struktur aktual dalam source code diketahui dari migration Laravel, tetapi kondisi database aktif tidak dapat diverifikasi dari ZIP.
6. File `.env` pada ZIP sumber berisi nilai credential Midtrans yang terisi. File tersebut tidak dimasukkan ke ZIP hasil perbaikan. Rotasi Server Key di dashboard Midtrans Sandbox disarankan karena credential pernah berada di dalam arsip yang dibagikan.

## Perubahan

- Menambahkan `payments.midtrans_order_id` sebagai kolom khusus untuk order ID yang dibuat aplikasi, nullable dan unique.
- `payments.transaction_id` sekarang menyimpan transaction ID asli dari respons/status API Midtrans setelah webhook terverifikasi.
- Checkout menyimpan order ID ke `midtrans_order_id`, bukan ke `transaction_id`.
- Webhook memetakan pembayaran menggunakan `midtrans_order_id`; fallback ke `transaction_id` hanya untuk baris legacy yang belum memiliki `midtrans_order_id` dan nilainya persis sama dengan order ID notifikasi.
- Signature wajib valid; payload wajib memiliki `order_id`, `transaction_id`, `status_code`, `gross_amount`, `signature_key`, dan `transaction_status`; status diverifikasi lagi lewat API Midtrans.
- ID transaksi API wajib sama dengan ID transaksi notifikasi. Order ID API harus sama dengan order lokal, dengan pengecualian terbatas untuk respons DANA ketika API mengembalikan `order_id` sama dengan `transaction_id`; signature payload tetap harus valid dan mapping lokal harus ditemukan berdasarkan order ID bertanda tangan.
- Nominal dari API dibandingkan dengan nominal notifikasi, lalu nominal pembayaran diverifikasi lagi terhadap total booking dan payment lokal.
- Pembaruan status berhasil dilakukan dalam database transaction dengan `lockForUpdate`; flag reward menjaga pemberian 10 poin satu kali per payment.
- Menambahkan unit test untuk aturan status sukses, capture/fraud, status gagal, dan normalisasi nominal.

## Cara menerapkan di Windows CMD

Buka CMD dan jalankan:

```bat
cd C:\xampp\htdocs\penyewaan_lapangan
copy .env .env.backup
```

Salin file hasil ZIP ke proyek atau pindahkan perubahan file terkait secara manual. Pastikan `.env` lokal tetap ada dan tidak diganti dengan `.env.example`. Lalu:

```bat
php artisan down
php artisan migrate --force
php artisan optimize:clear
php artisan up
```

Jika `php artisan down` gagal karena konfigurasi maintenance lokal, lewati perintah tersebut; jangan mengubah production. Migration ini menambah kolom nullable dan tidak menulis ulang nilai transaksi lama. Backup database sebelum migration.

## Konfigurasi Midtrans Sandbox

Di `.env` lokal:

```dotenv
MIDTRANS_SERVER_KEY=ISI_SERVER_KEY_SANDBOX
MIDTRANS_CLIENT_KEY=ISI_CLIENT_KEY_SANDBOX
MIDTRANS_IS_PRODUCTION=false
APP_URL=http://127.0.0.1:8000
```

Jangan pernah menaruh Server Key di frontend atau mengunggah `.env`. Setelah mengubah `.env`:

```bat
php artisan config:clear
php artisan config:cache
php artisan serve --host=127.0.0.1 --port=8000
```

Di CMD kedua, jalankan Cloudflare Tunnel:

```bat
cloudflared.exe tunnel --url http://127.0.0.1:8000
```

Jika CMD tidak mengenali `cloudflared.exe`, jalankan dari folder tempat executable berada atau gunakan path lengkap ke `cloudflared.exe`. Salin URL HTTPS baru yang dihasilkan tunnel. Pada Dashboard Midtrans Sandbox, set URL notifikasi ke:

`https://URL-TUNNEL-AKTIF/payments/midtrans/notification`

URL harus diperbarui setiap kali URL quick tunnel berubah. Tes `POST` webhook melalui simulasi/fitur notifikasi Midtrans, bukan hanya membuka URL di browser. Route CSRF sudah dikecualikan hanya untuk `payments/midtrans/notification` melalui `bootstrap/app.php`; signature Midtrans tetap wajib.

## Rekonsiliasi transaksi lama yang sudah settlement

Jangan menjalankan SQL massal untuk mengubah status. Untuk setiap transaksi lama:

1. Ambil `order_id` dari riwayat transaksi/notification Midtrans dan cocokkan dengan booking/payment yang tepat.
2. Periksa status menggunakan status API resmi Midtrans dengan Server Key Sandbox yang benar.
3. Pastikan status API `settlement` (atau `capture` dengan fraud status yang diterima), ID transaksi konsisten, nominal sama dengan `payments.amount` dan `bookings.total_price`, dan booking memang milik transaksi tersebut.
4. Jika `payments.transaction_id` lama ternyata berisi order ID aplikasi, webhook yang valid dapat melakukan normalisasi ke `midtrans_order_id` dan mengganti `transaction_id` dengan transaction ID asli. Midtrans dapat mengirim ulang notifikasi dari dashboard jika fitur tersedia.
5. Jika order ID tidak bisa dipetakan dengan yakin atau status API tidak konsisten, jangan otomatis mengubah status; catat untuk pemeriksaan manual oleh admin berwenang.

## Hasil pemeriksaan yang dapat dilakukan di lingkungan ini

- `php -l` berhasil untuk controller, model, migration, dan unit test yang diubah.
- `php artisan test` belum dapat berjalan karena PHP CLI pada lingkungan ini tidak memiliki ekstensi `dom`, `mbstring`, `xml`, dan `xmlwriter` yang diwajibkan PHPUnit.
- `vendor/bin/pint` juga tidak dapat berjalan karena ekstensi `mbstring` dan `xml` tidak tersedia.
- Pengujian integrasi nyata ke Midtrans Sandbox, MySQL aktif, webhook Cloudflare, serta dua notifikasi paralel belum dapat diverifikasi dari arsip saja.
