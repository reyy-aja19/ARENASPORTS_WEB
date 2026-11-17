<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Authenticate a mobile request using a Bearer token.
     *
     * Usage:
     *  - api.token          => token required
     *  - api.token:optional => token optional (guest allowed)
     */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $optional = $mode === 'optional';

        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            if ($optional) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token autentikasi tidak ditemukan. Silakan login kembali.',
            ], 401);
        }

        $record = ApiToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $record || ! $record->user) {
            if ($optional) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Sesi login sudah berakhir. Silakan login kembali.',
            ], 401);
        }

        /** @var User $user */
        $user = $record->user;

        if ($user->status !== 'Aktif') {
            $record->delete();

            return response()->json([
                'success' => false,
                'message' => 'Akun sedang nonaktif. Hubungi pengelola.',
            ], 403);
        }

        // Keep one timestamp update per minute to reduce write amplification.
        if (! $record->last_used_at || $record->last_used_at->lt(now()->subMinute())) {
            $record->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('api_token', $record);
        $request->setUserResolver(fn () => $user);
        Auth::setUser($user);

        return $next($request);
    }
}
