<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDeviceToken;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->filter()->values()->all();
        $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter(array_map('strval', $tokens))));
        if (empty($tokens)) {
            return;
        }

        $projectId = (string) config('services.firebase.project_id');
        $credentialsPath = $this->credentialsPath();

        if ($projectId === '' || $credentialsPath === null) {
            Log::warning('FCM skipped: FIREBASE_PROJECT_ID or FIREBASE_CREDENTIALS not configured.');
            return;
        }

        $accessToken = $this->accessToken($credentialsPath);
        if ($accessToken === null) {
            return;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $this->stringifyData($data),
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'channel_id' => 'arenasports_channel',
                            'sound' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(15)
                    ->post($url, $payload);

                if ($response->successful()) {
                    UserDeviceToken::where('token', $token)->update(['last_used_at' => now()]);
                    continue;
                }

                $errorCode = data_get($response->json(), 'error.details.0.errorCode')
                    ?? data_get($response->json(), 'error.status');
                $status = $response->status();

                if ($status === 404 || in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    UserDeviceToken::where('token', $token)->delete();
                } else {
                    Log::warning('FCM send failed', [
                        'status' => $status,
                        'error' => $response->json(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('FCM send exception: '.$e->getMessage());
            }
        }
    }

    private function credentialsPath(): ?string
    {
        $path = (string) config('services.firebase.credentials', env('FIREBASE_CREDENTIALS', ''));
        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:\\\\/', $path)) {
            $path = base_path($path);
        }

        return is_file($path) ? $path : null;
    }

    private function accessToken(string $credentialsPath): ?string
    {
        $cacheKey = 'fcm_access_token:'.md5($credentialsPath);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentialsPath) {
            try {
                $credentials = json_decode((string) file_get_contents($credentialsPath), true);
                if (! is_array($credentials)) {
                    Log::warning('FCM credentials file invalid.');
                    return null;
                }

                $clientEmail = (string) ($credentials['client_email'] ?? '');
                $privateKey = (string) ($credentials['private_key'] ?? '');
                if ($clientEmail === '' || $privateKey === '') {
                    Log::warning('FCM credentials missing client_email/private_key.');
                    return null;
                }

                $now = time();
                $jwt = JWT::encode([
                    'iss' => $clientEmail,
                    'sub' => $clientEmail,
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                ], $privateKey, 'RS256');

                $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if (! $response->successful()) {
                    Log::warning('FCM OAuth token request failed', ['response' => $response->json()]);
                    return null;
                }

                $token = $response->json('access_token');
                return is_string($token) && $token !== '' ? $token : null;
            } catch (\Throwable $e) {
                Log::warning('FCM OAuth token exception: '.$e->getMessage());
                return null;
            }
        });
    }

    private function stringifyData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $result[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }
        return $result;
    }
}
