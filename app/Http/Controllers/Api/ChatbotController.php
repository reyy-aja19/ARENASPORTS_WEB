<?php

namespace App\Http\Controllers\Api;

use App\Models\Lapangan;
use App\Models\Venue;
use Illuminate\Http\Request;

class ChatbotController extends BaseApiController
{
    public function nearbyVenues(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];

        $venues = Venue::withCount([
            'lapangans' => fn ($query) => $query->where('status', 'Tersedia'),
        ])
            ->where('status', 'Aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (Venue $venue) => $this->venuePayload($venue, $lat, $lng))
            ->filter(fn (array $venue) => $venue['distance_km'] !== null)
            ->sortBy('distance_km')
            ->take(5)
            ->values();

        return $this->ok([
            'venues' => $venues,
            'total' => $venues->count(),
        ]);
    }

    public function reply(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $message = mb_strtolower($data['message']);

        $courts = Lapangan::with('venue')
            ->where('status', 'Tersedia')
            ->orderBy('harga')
            ->take(6)
            ->get();

        $reply = null;

        if (str_contains($message, 'halo') || str_contains($message, 'hai') || str_contains($message, 'hello')) {
            $reply = 'Halo! Saya Arena Assistant. Saya bisa membantu info lapangan, harga, fasilitas, jadwal, booking, pembayaran, poin, dan open match.';
        } elseif (str_contains($message, 'harga') || str_contains($message, 'biaya') || str_contains($message, 'tarif')) {
            $reply = "Daftar harga lapangan:\n".$courts->map(
                fn (Lapangan $court) => '- '.$court->nama.' ('.$court->jenis.'): Rp '
                    .number_format((int) $court->harga, 0, ',', '.').'/jam'
            )->implode("\n");
        } elseif (str_contains($message, 'lapangan') || str_contains($message, 'venue') || str_contains($message, 'lokasi')) {
            $reply = "Lapangan yang tersedia:\n".$courts->map(
                fn (Lapangan $court) => '- '.$court->nama
                    .($court->venue?->name ? ' - '.$court->venue->name : '')
                    .($court->lokasi ? ' ('.$court->lokasi.')' : '')
            )->implode("\n");
        } elseif (str_contains($message, 'booking') || str_contains($message, 'pesan')) {
            $reply = 'Cara booking: buka menu Cari Venue, pilih venue dan lapangan, pilih tanggal serta jam, lalu lanjutkan pembayaran Midtrans atau tunai di venue.';
        } elseif (str_contains($message, 'bayar') || str_contains($message, 'pembayaran') || str_contains($message, 'midtrans')) {
            $reply = 'Pembayaran tersedia melalui Midtrans (otomatis) atau tunai di venue yang akan dikonfirmasi Admin Venue. Tiket aktif setelah pembayaran lunas.';
        } elseif (str_contains($message, 'poin') || str_contains($message, 'reward') || str_contains($message, 'tukar')) {
            $reply = 'Setiap pembayaran booking atau perpanjangan yang berhasil memberi 10 poin. Poin bisa ditukar di menu Tukar Poin.';
        } elseif (str_contains($message, 'open match') || str_contains($message, 'main bareng')) {
            $reply = 'Open Match dibuat dari booking aktif milikmu. Pemain lain bisa bergabung selama kuota tersedia, lalu berdiskusi di grup chat.';
        } elseif (str_contains($message, 'batal') || str_contains($message, 'refund')) {
            $reply = 'Pembatalan dan refund belum tersedia di aplikasi. Silakan hubungi Admin Venue untuk bantuan lebih lanjut.';
        }

        if (! $reply) {
            return $this->ok([
                'reply' => 'Maaf, saya belum memahami pertanyaan itu. Coba tanyakan tentang harga, lapangan, booking, pembayaran, poin, atau open match.',
                'courts' => [],
            ]);
        }

        return $this->ok([
            'reply' => $reply,
            'courts' => $courts->map(fn (Lapangan $court) => $this->courtPayload($court))->values(),
        ]);
    }
}
