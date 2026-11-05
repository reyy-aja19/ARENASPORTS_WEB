<?php
namespace App\Http\Middleware;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CekLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('admin_logged_in')) {
            if ($request->routeIs('user.booking.store')) {
                $lapangan = $request->route('lapangan');
                $lapanganId = is_object($lapangan) ? $lapangan->getRouteKey() : $lapangan;
                if ($lapanganId) {
                    $request->session()->put('url.intended', route('courts.detail', ['lapangan' => $lapanganId], false));
                    $request->session()->put('booking.pending', $request->only(['booking_date', 'start_time', 'hours', 'payment_method']));
                }
            } else {
                $request->session()->put('url.intended', $request->getRequestUri());
            }
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk melakukan booking lapangan.');
        }
        $user=User::find($request->session()->get('admin_user_id'));
        if (!$user || $user->status!=='Aktif') {
            $request->session()->invalidate(); $request->session()->regenerateToken();
            return redirect()->route('login')->with('error','Sesi berakhir atau akun tidak aktif. Silakan login kembali.');
        }
        // Keep authorization synchronized when Superadmin changes a user's role.
        $previousRole=$request->session()->get('admin_role');
        $request->session()->put('admin_role',$user->role);
        if ($previousRole && $previousRole !== $user->role) {
            $request->session()->regenerate();
            if ($request->routeIs('user.*') && $user->role !== 'user' && !$request->routeIs('user.venue-application*')) return redirect()->route('admin.dashboard');
            if ($request->routeIs('admin.*') && $user->role === 'user') return redirect()->route('user.home');
        }
        return $next($request);
    }
}
