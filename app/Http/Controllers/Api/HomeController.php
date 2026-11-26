<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        $venueCount = Venue::where('status', 'Aktif')->count();

        $venues = Venue::withCount([
            'lapangans' => fn ($q) => $q->operational(),
        ])
            ->where('status', 'Aktif')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (Venue $venue) => $this->venuePayload($venue, $lat, $lng))
            ->values();

        $courts = Lapangan::with('venue')
            ->operational()
            ->where(function ($query) {
                $query->whereNull('venue_id')
                    ->orWhereHas('venue', fn ($venue) => $venue->where('status', 'Aktif'));
            })
            ->latest()
            ->get()
            ->map(fn (Lapangan $court) => $this->courtPayload($court, $lat, $lng))
            ->values();

        $popularCourtIds = DB::table('bookings')
            ->select('lapangan_id', DB::raw('COUNT(*) as total'))
            ->whereIn('status', ['Menunggu', 'DP', 'Lunas', 'Selesai'])
            ->groupBy('lapangan_id')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('lapangan_id');

        $popularCourts = $popularCourtIds
            ->map(fn ($id) => $courts->firstWhere('id', (int) $id))
            ->filter()
            ->values();

        $bookings = Booking::with(['lapangan.venue', 'payment', 'payments', 'extensions.payment'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (Booking $booking) => $this->bookingPayload($booking))
            ->values();

        $matches = OpenMatch::with('booking.lapangan.venue')
            ->whereIn('status', ['Open', 'Penuh'])
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->take(4)
            ->get()
            ->map(fn (OpenMatch $match) => $this->matchPayload($match, $user))
            ->values();

        $notifications = $user->notifications()->latest()->take(5)->get()
            ->map(fn ($notification) => $this->notificationPayload($notification))
            ->values();

        return $this->ok([
            'user' => $this->userPayload($user),
            'stats' => [
                'venue_count' => $venueCount,
                'booking_count' => Booking::where('user_id', $user->id)->count(),
                'points' => (int) $user->points,
                'unread_notifications' => $user->unreadNotifications()->count(),
            ],
            'venues' => $venues,
            'courts' => $courts,
            'popular_courts' => $popularCourts,
            'bookings' => $bookings,
            'matches' => $matches,
            'notifications' => $notifications,
        ]);
    }
}
