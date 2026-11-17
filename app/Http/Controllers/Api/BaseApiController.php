<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BaseApiController extends Controller
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function fail(string $message, int $status = 422, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function currentUser(Request $request): User
    {
        return $request->user();
    }

    protected function absoluteImage(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return url($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return url('storage/'.$path);
        }

        return url($path);
    }

    protected function imageList(mixed $images): array
    {
        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($image) => $this->absoluteImage(is_string($image) ? $image : null),
            $images
        )));
    }

    protected function distanceKm(?float $lat, ?float $lng, ?float $targetLat, ?float $targetLng): ?float
    {
        if ($lat === null || $lng === null || $targetLat === null || $targetLng === null) {
            return null;
        }

        $dLat = deg2rad($targetLat - $lat);
        $dLng = deg2rad($targetLng - $lng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat))
            * cos(deg2rad($targetLat))
            * sin($dLng / 2) ** 2;

        return round(6371 * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a))), 2);
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'points' => (int) $user->points,
            'role' => $user->role,
            'status' => $user->status,
            'profile_photo_url' => $user->profile_photo_url,
            'email_verified' => $user->email_verified_at !== null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    protected function venuePayload(Venue $venue, ?float $lat = null, ?float $lng = null, bool $withCourts = false): array
    {
        $courts = collect();

        if ($withCourts) {
            $courts = $venue->lapangans()
                ->where('status', 'Tersedia')
                ->latest()
                ->get();
        }

        $courtStats = $venue->lapangans()
            ->where('status', 'Tersedia')
            ->selectRaw('COUNT(*) as total, MIN(harga) as min_price, AVG(rating) as rating')
            ->first();

        $availableCourtIds = $venue->lapangans()
            ->where('status', 'Tersedia')
            ->select('id');

        $reviewStats = \App\Models\Review::whereIn('lapangan_id', $availableCourtIds)
            ->where('is_visible', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as rating')
            ->first();

        $reviewCount = (int) ($reviewStats->total ?? 0);

        $venueLat = is_numeric($venue->latitude) ? (float) $venue->latitude : null;
        $venueLng = is_numeric($venue->longitude) ? (float) $venue->longitude : null;

        return [
            'id' => (int) $venue->id,
            'name' => $venue->name,
            'slug' => $venue->slug,
            'company_name' => $venue->company_name,
            'address' => $venue->address,
            'city' => $venue->city,
            'province' => $venue->province,
            'contact_phone' => $venue->contact_phone,
            'description' => $venue->description,
            'photo_url' => $venue->photo_url,
            'gallery' => $this->imageList($venue->gallery),
            'sports' => $venue->sports ?? [],
            'facilities' => $venue->facilities ?? [],
            'status' => $venue->status,
            'operating_hours' => $venue->operating_hours,
            'google_maps_url' => $venue->google_maps_url,
            'latitude' => $venueLat,
            'longitude' => $venueLng,
            'distance_km' => $this->distanceKm($lat, $lng, $venueLat, $venueLng),
            'courts_count' => $withCourts
                ? $courts->count()
                : (int) ($venue->lapangans_count ?? ($courtStats->total ?? 0)),
            'min_price' => (int) ($courtStats->min_price ?? 0),
            'rating' => $reviewCount > 0
                ? round((float) $reviewStats->rating, 1)
                : round((float) ($courtStats->rating ?? 0), 1),
            'review_count' => $reviewCount,
            'courts' => $withCourts
                ? $courts->map(fn (Lapangan $court) => $this->courtPayload($court, $lat, $lng, true))->values()->all()
                : [],
        ];
    }

    protected function courtPayload(Lapangan $court, ?float $lat = null, ?float $lng = null, bool $withReviews = false): array
    {
        $court->loadMissing('venue');

        $latitude = $court->venue && is_numeric($court->venue->latitude)
            ? (float) $court->venue->latitude
            : (is_numeric($court->latitude) ? (float) $court->latitude : null);

        $longitude = $court->venue && is_numeric($court->venue->longitude)
            ? (float) $court->venue->longitude
            : (is_numeric($court->longitude) ? (float) $court->longitude : null);

        $payload = [
            'id' => (int) $court->id,
            'name' => $court->nama,
            'type' => $court->jenis,
            'location' => $court->lokasi ?: $court->venue?->address,
            'price' => (int) $court->harga,
            'rating' => (float) $court->rating,
            'status' => $court->status,
            'description' => $court->deskripsi,
            'image_url' => $court->foto_url,
            'gallery_images' => $this->imageList($court->foto_gallery),
            'facilities' => $court->fasilitas ?? [],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'venue_id' => $court->venue_id ? (int) $court->venue_id : null,
            'venue_name' => $court->venue?->name,
            'venue_address' => $court->venue?->address,
            'distance_km' => $this->distanceKm($lat, $lng, $latitude, $longitude),
        ];

        if ($withReviews) {
            $reviews = $court->reviews()
                ->where('is_visible', true)
                ->with('user:id,name,profile_photo')
                ->latest()
                ->get();

            $payload['review_count'] = $reviews->count();
            $payload['rating_average'] = $reviews->count() > 0
                ? round((float) $reviews->avg('rating'), 1)
                : (float) $court->rating;
            $payload['reviews'] = $reviews->map(fn ($review) => [
                'id' => (int) $review->id,
                'rating' => (int) $review->rating,
                'message' => $review->message,
                'reply_message' => $review->reply_message,
                'user_name' => $review->user?->name ?? 'Pengguna',
                'user_photo_url' => $review->user?->profile_photo_url,
                'created_at' => $review->created_at?->toIso8601String(),
            ])->values()->all();
        }

        return $payload;
    }

    protected function paymentPayload(?Payment $payment): ?array
    {
        if (! $payment) {
            return null;
        }

        return [
            'id' => (int) $payment->id,
            'method' => $payment->method,
            'amount' => (int) $payment->amount,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'transaction_id' => $payment->transaction_id,
            'midtrans_order_id' => $payment->midtrans_order_id,
            'has_snap_token' => filled($payment->snap_token),
            'booking_extension_id' => $payment->booking_extension_id
                ? (int) $payment->booking_extension_id
                : null,
        ];
    }

    protected function bookingPayload(Booking $booking, bool $withExtensions = false): array
    {
        $booking->loadMissing(['lapangan.venue', 'payment', 'payments', 'extensions.payment']);

        $payment = $booking->payment ?: $booking->payments->first();

        $effectiveStatus = $booking->status;
        if (! in_array($effectiveStatus, ['Dibatalkan', 'Selesai'], true)) {
            $end = \Illuminate\Support\Carbon::parse(
                $booking->booking_date?->toDateString().' '.substr((string) $booking->end_time, 0, 5)
            );
            if ($end->isPast()) {
                $effectiveStatus = 'Selesai';
            }
        }

        $court = $booking->lapangan;

        return [
            'id' => (int) $booking->id,
            'status' => $booking->status,
            'effective_status' => $effectiveStatus,
            'payment_method' => $booking->payment_method,
            'booking_date' => $booking->booking_date?->toDateString(),
            'start_time' => substr((string) $booking->start_time, 0, 5),
            'end_time' => substr((string) $booking->end_time, 0, 5),
            'hours' => (int) $booking->hours,
            'total_price' => (int) $booking->total_price,
            'created_at' => $booking->created_at?->toIso8601String(),
            'court' => $court ? $this->courtPayload($court) : null,
            'payment' => $this->paymentPayload($payment),
            'is_paid' => $this->paymentIsPaid($payment, $booking),
            'open_match_id' => $booking->openMatch?->id,
            'has_review' => \App\Models\Review::where('user_id', $booking->user_id)
                ->where('lapangan_id', $booking->lapangan_id)
                ->exists(),
            'extensions' => $withExtensions
                ? $booking->extensions->map(fn ($extension) => [
                    'id' => (int) $extension->id,
                    'hours' => (int) $extension->hours,
                    'previous_end_time' => substr((string) $extension->previous_end_time, 0, 5),
                    'new_end_time' => substr((string) $extension->new_end_time, 0, 5),
                    'amount' => (int) $extension->amount,
                    'status' => $extension->status,
                    'note' => $extension->note,
                    'created_at' => $extension->created_at?->toIso8601String(),
                    'payment' => $this->paymentPayload($extension->payment),
                ])->values()->all()
                : [],
        ];
    }

    protected function paymentIsPaid(?Payment $payment, ?Booking $booking = null): bool
    {
        $paidStatuses = ['lunas', 'selesai', 'settlement', 'capture', 'success'];

        if ($payment && in_array(strtolower((string) $payment->status), $paidStatuses, true)) {
            return true;
        }

        if ($booking && in_array(strtolower((string) $booking->status), $paidStatuses, true)) {
            return true;
        }

        return false;
    }

    protected function matchPayload(OpenMatch $match, ?User $user = null): array
    {
        $match->loadMissing('booking.lapangan.venue');

        $booking = $match->booking;
        $court = $booking?->lapangan;
        $hostId = $booking?->user_id ? (int) $booking->user_id : null;

        $joined = false;
        $isHost = false;

        if ($user) {
            $isHost = $hostId === (int) $user->id;
            $joined = $isHost || DB::table('open_match_participants')
                ->where('open_match_id', $match->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return [
            'id' => (int) $match->id,
            'booking_id' => $match->booking_id ? (int) $match->booking_id : null,
            'title' => $match->title,
            'jenis' => $match->jenis,
            'tanggal' => $match->tanggal?->toDateString(),
            'start_time' => substr((string) $match->start_time, 0, 5),
            'end_time' => substr((string) $match->end_time, 0, 5),
            'jumlah_pemain' => (int) $match->jumlah_pemain,
            'jumlah_bergabung' => (int) $match->jumlah_bergabung,
            'deskripsi' => $match->deskripsi,
            'status' => $match->status,
            'event_type' => 'open_match',
            'organizer' => $booking?->user?->name,
            'field_id' => $court?->id,
            'field_name' => $court?->nama,
            'venue_name' => $court?->venue?->name,
            'image_url' => $court?->foto_url,
            'registration_fee' => null,
            'prize' => null,
            'joined' => $joined,
            'is_host' => $isHost,
            'is_full' => (int) $match->jumlah_bergabung >= (int) $match->jumlah_pemain,
            'chat_available' => $joined,
        ];
    }

    protected function eventPayload(Event $event): array
    {
        $event->loadMissing('creator.venues');

        $venue = $event->creator?->venues?->first();

        return [
            'id' => (int) $event->id,
            'booking_id' => null,
            'title' => $event->title,
            'jenis' => $event->jenis,
            'tanggal' => $event->tanggal?->toDateString(),
            'start_time' => substr((string) $event->start_time, 0, 5),
            'end_time' => substr((string) $event->end_time, 0, 5),
            'jumlah_pemain' => (int) $event->kuota,
            'jumlah_bergabung' => 0,
            'deskripsi' => $event->deskripsi,
            'status' => $event->status,
            'event_type' => 'tournament',
            'organizer' => $event->creator?->name,
            'field_id' => null,
            'field_name' => $event->lokasi,
            'venue_name' => $venue?->name ?: $event->lokasi,
            'image_url' => $venue?->photo_url,
            'registration_fee' => null,
            'prize' => null,
            'joined' => false,
            'is_host' => false,
            'is_full' => false,
            'chat_available' => false,
        ];
    }

    protected function notificationPayload($notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? 'Notifikasi',
            'message' => $data['message'] ?? '',
            'url' => $data['url'] ?? null,
            'type' => $data['type'] ?? Str::afterLast((string) $notification->type, '\\'),
            'is_read' => $notification->read_at !== null,
            'time' => $notification->created_at?->diffForHumans(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    protected function ticketCodeFor(Booking $booking): string
    {
        return 'AS-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT).'-'
            .strtoupper(substr(hash_hmac(
                'sha256',
                'booking:'.$booking->id,
                (string) config('app.key')
            ), 0, 10));
    }
}
