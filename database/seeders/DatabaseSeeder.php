<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'role' => 'superadmin',
                'phone' => '0812-3456-7890',
                'points' => 0,
                'status' => 'Aktif',
            ]
        );
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin ArenaSports',
                'password' => Hash::make('Admin123!'),
                'role' => 'admin',
                'phone' => '0812-3456-7891',
                'points' => 0,
                'status' => 'Aktif',
            ]
        );
        User::updateOrCreate(
    ['email' => 'trivaldo2345@gmail.com'],
    [
        'name' => 'Trivaldo',
        'password' => Hash::make('12345678'),
        'role' => 'venue_admin',
        'phone' => '081234567895',
        'points' => 0,
        'status' => 'Aktif',
    ]
);

        $users = collect([
            ['name' => 'Restu', 'email' => 'restu@gmail.com', 'phone' => '0811111111', 'points' => 10, 'status' => 'Aktif'],
            ['name' => 'Reyhan', 'email' => 'reyhan@gmail.com', 'phone' => '0822222222', 'points' => 15, 'status' => 'Aktif'],
            ['name' => 'Wendi', 'email' => 'wendi@gmail.com', 'phone' => '0833333333', 'points' => 5, 'status' => 'Aktif'],
            ['name' => 'Furab', 'email' => 'furab@gmail.com', 'phone' => '0844444444', 'points' => 7, 'status' => 'Aktif'],
            ['name' => 'AdiBurger', 'email' => 'adiburger@gmail.com', 'phone' => '0855555555', 'points' => 25, 'status' => 'Nonaktif'],
            [
    'name' => 'Restu',
    'email' => 'restu112345@gmail.com',
    'phone' => '081234567896',
    'points' => 10,
    'status' => 'Aktif',
],
        ])->map(fn ($u) => User::updateOrCreate(
            ['email' => $u['email']],
            array_merge($u, ['password' => Hash::make('password123'), 'role' => 'user'])
        ));

        $courts = collect([
            [
                'nama' => 'Lapangan Badminton A',
                'jenis' => 'Badminton',
                'lokasi' => 'Rajasinga, Kab. Indramayu',
                'harga' => 90000,
                'rating' => 4.7,
                'status' => 'Tersedia',
                'deskripsi' => 'Lapangan indoor badminton dengan pencahayaan terang dan lantai vinyl.',
                'foto' => 'uploads/courts/badminton-a.svg',
                'foto_gallery' => ['uploads/courts/badminton-a.svg', 'uploads/courts/badminton-b.svg'],
                'fasilitas' => ['Parkir motor', 'Toilet', 'Musholla', 'Jual minuman', 'Jual makanan', 'WiFi'],
            ],
            [
                'nama' => 'Lapangan Futsal A',
                'jenis' => 'Futsal',
                'lokasi' => 'Rajasinga, Kab. Indramayu',
                'harga' => 150000,
                'rating' => 4.8,
                'status' => 'Tersedia',
                'deskripsi' => 'Lapangan futsal sintetis dengan tribun dan lampu malam.',
                'foto' => 'uploads/courts/futsal-a.svg',
                'foto_gallery' => ['uploads/courts/futsal-a.svg', 'uploads/courts/badminton-a.svg'],
                'fasilitas' => ['Parkir motor', 'Toilet', 'Tribun', 'Jual minuman', 'Lampu malam', 'WiFi'],
            ],
            [
                'nama' => 'Lapangan Badminton B',
                'jenis' => 'Badminton',
                'lokasi' => 'Sindang, Indramayu',
                'harga' => 85000,
                'rating' => 4.6,
                'status' => 'Perawatan',
                'deskripsi' => 'Lapangan badminton dengan area parkir luas dan ruang tunggu.',
                'foto' => 'uploads/courts/badminton-b.svg',
                'foto_gallery' => ['uploads/courts/badminton-b.svg', 'uploads/courts/badminton-a.svg'],
                'fasilitas' => ['Parkir motor', 'Ruang tunggu', 'Musholla', 'Toilet'],
            ],
        ])->map(fn ($c) => Lapangan::updateOrCreate(['nama' => $c['nama']], $c));

        $rewards = collect([
            ['title' => 'Voucher Minuman', 'points_required' => 20, 'badge' => 'voucher', 'description' => 'Tukar 20 poin untuk 1 voucher minuman.', 'status' => 'Aktif', 'image' => 'uploads/rewards/voucher-minuman.svg'],
            ['title' => 'Diskon 50%', 'points_required' => 50, 'badge' => '50%', 'description' => 'Potongan setengah harga untuk booking berikutnya.', 'status' => 'Aktif', 'image' => 'uploads/rewards/diskon-50.svg'],
            ['title' => 'Voucher 1 Jam', 'points_required' => 70, 'badge' => '1 jam', 'description' => 'Main gratis 1 jam pada lapangan pilihan.', 'status' => 'Aktif', 'image' => 'uploads/rewards/voucher-1-jam.svg'],
        ])->map(fn ($r) => Reward::updateOrCreate(['title' => $r['title']], $r));

        $bookingData = [
            [$users[0], $courts[1], 'SayurDipay', '2026-04-10', '19:00', '20:00', 1, 'Lunas'],
            [$users[1], $courts[1], 'Dana', '2026-04-10', '11:00', '12:00', 1, 'Lunas'],
            [$users[2], $courts[0], 'ShopeePay', '2026-04-10', '08:00', '10:00', 2, 'DP'],
            [$users[3], $courts[2], 'BRI', '2026-04-11', '19:00', '20:00', 1, 'DP'],
            [$users[4], $courts[1], 'GoPay', '2026-04-10', '20:00', '22:00', 2, 'Lunas'],
        ];

        foreach ($bookingData as [$user, $court, $method, $date, $start, $end, $hours, $status]) {
            $booking = Booking::updateOrCreate(
                ['user_id' => $user->id, 'lapangan_id' => $court->id, 'booking_date' => $date, 'start_time' => $start],
                [
                    'payment_method' => $method,
                    'end_time' => $end,
                    'hours' => $hours,
                    'total_price' => $court->harga * $hours,
                    'status' => $status,
                ]
            );

            Payment::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'user_id' => $user->id,
                    'method' => $method,
                    'amount' => $booking->total_price,
                    'proof_image' => 'uploads/payments/sample-proof.svg',
                    'status' => $status === 'Lunas' ? 'Lunas' : 'Menunggu',
                    'paid_at' => $status === 'Lunas' ? now() : null,
                ]
            );
        }

        $reviews = [
            [$users[3], $courts[0], 5, 'Pemilik lapangan ramah, bersih, dan terawat.', null, true],
            [$users[0], $courts[1], 4, 'Lapangan futsal nyaman dipakai untuk sparing malam.', 'Terima kasih, kami tunggu booking berikutnya.', true],
        ];
        foreach ($reviews as [$user, $court, $rating, $message, $reply, $visible]) {
            Review::updateOrCreate(
                ['user_id' => $user->id, 'lapangan_id' => $court->id, 'message' => $message],
                ['rating' => $rating, 'reply_message' => $reply, 'is_visible' => $visible]
            );
        }

        Redemption::updateOrCreate(
            ['user_id' => $users[0]->id, 'reward_id' => $rewards[0]->id],
            ['redeemed_at' => now(), 'qr_code' => 'QR-MINUMAN-001', 'status' => 'Berhasil']
        );

        OpenMatch::updateOrCreate(
            ['title' => 'Sparing Futsal Malam'],
            [
                'booking_id' => Booking::first()?->id,
                'jenis' => 'Futsal',
                'tanggal' => '2026-04-24',
                'start_time' => '20:00',
                'end_time' => '21:00',
                'jumlah_pemain' => 10,
                'jumlah_bergabung' => 6,
                'deskripsi' => 'Open match untuk user yang ingin ikut sparing futsal malam.',
                'status' => 'Open',
            ]
        );

        OpenMatch::updateOrCreate(
            ['title' => 'Badminton Double Friendly'],
            [
                'booking_id' => Booking::skip(2)->first()?->id,
                'jenis' => 'Badminton',
                'tanggal' => '2026-04-25',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'jumlah_pemain' => 4,
                'jumlah_bergabung' => 2,
                'deskripsi' => 'Cari pasangan bermain badminton santai.',
                'status' => 'Open',
            ]
        );
    }
}
