<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Mengisi lapangan contoh untuk venue yang sudah ada pada database produksi.
 *
 * Database produksi sempat hanya berisi venue tanpa satu pun lapangan,
 * sehingga katalog aplikasi mobile dan halaman booking kosong. Migrasi ini
 * hanya berjalan sekali (tercatat di tabel `migrations`) dan tidak melakukan
 * apa pun bila sudah ada lapangan, jadi aman untuk deploy berikutnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('lapangan')->exists()) {
            return;
        }

        $venue = DB::table('venues')->orderBy('id')->first();

        $now = now();

        $courts = [
            [
                'nama' => 'Lapangan Badminton A',
                'jenis' => 'Badminton',
                'lokasi' => $venue?->city ?: 'Indramayu',
                'harga' => 90000,
                'rating' => 4.7,
                'status' => 'Tersedia',
                'deskripsi' => 'Lapangan badminton indoor dengan lantai vinyl, pencahayaan LED, dan tribun penonton.',
                'foto' => 'uploads/courts/20260425184205-6T9Ggss6.jpg',
                'foto_gallery' => [
                    'uploads/courts/20260425184205-6T9Ggss6.jpg',
                    'uploads/courts/20260425184506-MlwtaxLe.jpg',
                ],
                'fasilitas' => [
                    'Parkir motor',
                    'Parkir mobil',
                    'Toilet',
                    'Musholla',
                    'Ruang ganti',
                    'WiFi',
                ],
            ],
            [
                'nama' => 'Lapangan Futsal A',
                'jenis' => 'Futsal',
                'lokasi' => $venue?->city ?: 'Indramayu',
                'harga' => 150000,
                'rating' => 4.8,
                'status' => 'Tersedia',
                'deskripsi' => 'Lapangan futsal dengan rumput sintetis, tribun, dan lampu untuk bermain malam.',
                'foto' => 'uploads/courts/20260425184419-6s6ixWyv.jpg',
                'foto_gallery' => [
                    'uploads/courts/20260425184419-6s6ixWyv.jpg',
                    'uploads/courts/20260425184419-8VjUjmnE.jpg',
                ],
                'fasilitas' => [
                    'Parkir motor',
                    'Parkir mobil',
                    'Toilet',
                    'Tribun',
                    'Lampu malam',
                    'Kantin',
                ],
            ],
            [
                'nama' => 'Lapangan Badminton B',
                'jenis' => 'Badminton',
                'lokasi' => $venue?->city ?: 'Indramayu',
                'harga' => 85000,
                'rating' => 4.6,
                'status' => 'Tersedia',
                'deskripsi' => 'Lapangan badminton dengan area parkir luas dan ruang tunggu yang nyaman.',
                'foto' => 'uploads/courts/20261112135504-RiuORs9p.png',
                'foto_gallery' => [
                    'uploads/courts/20261112135504-RiuORs9p.png',
                    'uploads/courts/20261112135504-XUTTrtW0.png',
                ],
                'fasilitas' => [
                    'Parkir motor',
                    'Toilet',
                    'Musholla',
                    'Ruang tunggu',
                    'WiFi',
                ],
            ],
        ];

        foreach ($courts as $court) {
            DB::table('lapangan')->insert([
                'owner_admin_id' => $venue?->owner_admin_id,
                'venue_id' => $venue?->id,
                'nama' => $court['nama'],
                'jenis' => $court['jenis'],
                'lokasi' => $court['lokasi'],
                'latitude' => $venue?->latitude,
                'longitude' => $venue?->longitude,
                'harga' => $court['harga'],
                'rating' => $court['rating'],
                'status' => $court['status'],
                'deskripsi' => $court['deskripsi'],
                'foto' => $court['foto'],
                'foto_gallery' => json_encode($court['foto_gallery']),
                'fasilitas' => json_encode($court['fasilitas']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Data contoh tidak dihapus otomatis agar tidak menghapus data asli.
    }
};
