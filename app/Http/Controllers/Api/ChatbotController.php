<?php

namespace App\Http\Controllers\Api;

use App\Models\Lapangan;
use App\Models\Venue;
use App\Services\GeminiChatbotService;
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

    public function reply(Request $request, GeminiChatbotService $gemini)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $reply = $gemini->reply(
            $data['message'],
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
        );

        return $this->ok([
            'reply' => $reply,
            'courts' => [],
        ]);
    }
}
