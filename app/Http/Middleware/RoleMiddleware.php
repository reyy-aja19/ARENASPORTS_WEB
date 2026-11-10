<?php

namespace App\Http\Middleware;

use App\Models\VenueApplication;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->session()->get('admin_logged_in')) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        $userId = $request->session()->get('admin_user_id');
        $role = $request->session()->get('admin_role');

        if (!$userId || !$role) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Sesi login tidak valid. Silakan login kembali.');
        }

        if (!in_array($role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Pemohon venue hanya boleh membuka halaman status/pengajuan sampai diputuskan.
        if (
            in_array('user', $roles, true) &&
            $request->routeIs('user.*') &&
            !$request->routeIs('user.venue-application*')
        ) {
            $hasPendingVenueApplication = VenueApplication::where('user_id', $userId)
                ->whereIn('status', ['pending', 'revision', 'Menunggu', 'Perlu Revisi'])
                ->exists();

            if ($hasPendingVenueApplication) {
                return redirect()->route('user.venue-application')
                    ->with('error', 'Akun sedang dalam proses pengajuan Admin Venue. Fitur User biasa sementara dinonaktifkan sampai pengajuan diputuskan.');
            }
        }

        return $next($request);
    }
}
