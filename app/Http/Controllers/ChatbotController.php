<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function nearbyVenues(Request $request)
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $userLat = (float) $request->latitude;
        $userLng = (float) $request->longitude;

        // Ambil venue yang memiliki koordinat.
        // Asumsi status venue aktif adalah "active".
        $venues = Venue::where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $now = Carbon::now('Asia/Jakarta');

        $results = [];

        foreach ($venues as $venue) {

            $venueLat = (float) $venue->latitude;
            $venueLng = (float) $venue->longitude;

            // Menghitung jarak menggunakan rumus Haversine.
            $earthRadius = 6371;

            $latDifference = deg2rad($venueLat - $userLat);
            $lngDifference = deg2rad($venueLng - $userLng);

            $a = sin($latDifference / 2) ** 2
                + cos(deg2rad($userLat))
                * cos(deg2rad($venueLat))
                * sin($lngDifference / 2) ** 2;

            $distance = $earthRadius * 2 * atan2(
                sqrt($a),
                sqrt(1 - $a)
            );

            $operatingHours = $venue->operating_hours;

            $hours = is_string($operatingHours)
                ? json_decode($operatingHours, true)
                : null;

            // Format jam operasional harus berupa JSON.
            if (!is_array($hours)) {
                continue;
            }

            $dayNames = [
                1 => 'senin',
                2 => 'selasa',
                3 => 'rabu',
                4 => 'kamis',
                5 => 'jumat',
                6 => 'sabtu',
                7 => 'minggu',
            ];

            $today = $dayNames[$now->dayOfWeekIso];

            $schedule = $hours[$today] ?? 'tutup';

            $isOpen = false;

            if (
                is_string($schedule)
                && preg_match(
                    '/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/',
                    $schedule,
                    $matches
                )
            ) {
                $openTime = Carbon::createFromFormat(
                    '!H:i',
                    $matches[1],
                    'Asia/Jakarta'
                );

                $closeTime = Carbon::createFromFormat(
                    '!H:i',
                    $matches[2],
                    'Asia/Jakarta'
                );

                if ($closeTime->greaterThan($openTime)) {
                    $isOpen = $now->between(
                        $openTime,
                        $closeTime,
                        false
                    );
                } else {
                    // Mendukung jam operasional melewati tengah malam.
                    $isOpen = $now->greaterThanOrEqualTo($openTime)
                        || $now->lessThan($closeTime);
                }
            }

            if (!$isOpen) {
                continue;
            }

            $results[] = [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->address,
                'city' => $venue->city,
                'distance' => round($distance, 2),
                'operating_hours' => $schedule,
                'google_maps_url' => $venue->google_maps_url,
            ];
        }

        // Urutkan dari venue terdekat.
        usort($results, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return response()->json([
            'success' => true,
            'total' => count($results),
            'venues' => array_slice($results, 0, 5),
        ]);
    }
}