<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Lapangan;
use App\Models\Venue;
use Illuminate\Http\Request;

class CatalogController extends BaseApiController
{
    public function venues(Request $request)
    {
        $query = Venue::withCount([
            'lapangans' => fn ($q) => $q->where('status', 'Tersedia'),
        ])->where('status', 'Aktif');

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('company_name', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('province', 'like', "%{$term}%");
            });
        }

        $venues = $query->latest()->get();

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        $payload = $venues
            ->map(fn (Venue $venue) => $this->venuePayload($venue, $lat, $lng))
            ->when($lat !== null && $lng !== null, fn ($collection) => $collection
                ->sortBy(fn ($venue) => $venue['distance_km'] ?? INF)
                ->values())
            ->values();

        return $this->ok([
            'venues' => $payload,
            'total' => $payload->count(),
        ]);
    }

    public function venueDetail(Request $request, Venue $venue)
    {
        abort_unless($venue->status === 'Aktif', 404, 'Venue tidak ditemukan atau belum aktif.');

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        return $this->ok([
            'venue' => $this->venuePayload($venue, $lat, $lng, true),
        ]);
    }

    public function courts(Request $request)
    {
        $query = Lapangan::with('venue')
            ->where('status', 'Tersedia')
            ->where(function ($venueQuery) {
                $venueQuery->whereNull('venue_id')
                    ->orWhereHas('venue', fn ($venue) => $venue->where('status', 'Aktif'));
            });

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($sub) use ($term) {
                $sub->where('nama', 'like', "%{$term}%")
                    ->orWhere('lokasi', 'like', "%{$term}%")
                    ->orWhere('jenis', 'like', "%{$term}%");
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        if ($request->filled('venue_id')) {
            $query->where('venue_id', (int) $request->query('venue_id'));
        }

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        $courts = $query->latest()->get()
            ->map(fn (Lapangan $court) => $this->courtPayload($court, $lat, $lng))
            ->when($lat !== null && $lng !== null, fn ($collection) => $collection
                ->sortBy(fn ($court) => $court['distance_km'] ?? INF)
                ->values())
            ->values();

        return $this->ok([
            'courts' => $courts,
            'total' => $courts->count(),
        ]);
    }

    public function courtDetail(Request $request, Lapangan $lapangan)
    {
        $lapangan->loadMissing('venue');

        abort_unless(
            $lapangan->status === 'Tersedia'
                && (! $lapangan->venue_id || $lapangan->venue?->status === 'Aktif'),
            404,
            'Lapangan tidak tersedia.'
        );

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        return $this->ok([
            'court' => $this->courtPayload($lapangan, $lat, $lng, true),
        ]);
    }

    public function availability(Request $request, Lapangan $lapangan)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $date = $data['date'];

        $bookings = Booking::where('lapangan_id', $lapangan->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
            ->orderBy('start_time')
            ->get(['start_time', 'end_time', 'hours', 'status']);

        $extensions = BookingExtension::where('status', 'Menunggu')
            ->whereHas('booking', function ($query) use ($lapangan, $date) {
                $query->where('lapangan_id', $lapangan->id)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', ['Menunggu', 'DP', 'Lunas']);
            })
            ->get(['previous_end_time', 'new_end_time']);

        $booked = $bookings->map(fn (Booking $booking) => [
            'start_time' => substr((string) $booking->start_time, 0, 5),
            'end_time' => substr((string) $booking->end_time, 0, 5),
            'hours' => (int) $booking->hours,
            'status' => $booking->status,
        ])->values()->all();

        foreach ($extensions as $extension) {
            $booked[] = [
                'start_time' => substr((string) $extension->previous_end_time, 0, 5),
                'end_time' => substr((string) $extension->new_end_time, 0, 5),
                'hours' => 0,
                'status' => 'Perpanjangan',
            ];
        }

        $opening = '07:00';
        $closing = '23:00';
        $slots = [];

        $cursor = strtotime('today '.$opening);
        $closingTimestamp = strtotime('today '.$closing);

        while ($cursor < $closingTimestamp) {
            $slotStart = date('H:i', $cursor);
            $slotEnd = date('H:i', $cursor + 3600);

            $isBooked = false;
            foreach ($booked as $range) {
                if ($range['start_time'] < $slotEnd && $range['end_time'] > $slotStart) {
                    $isBooked = true;
                    break;
                }
            }

            $slots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'available' => ! $isBooked,
            ];

            $cursor += 3600;
        }

        return $this->ok([
            'date' => $date,
            'opening' => $opening,
            'closing' => $closing,
            'booked' => $booked,
            'slots' => $slots,
            'price' => (int) $lapangan->harga,
        ]);
    }
}
