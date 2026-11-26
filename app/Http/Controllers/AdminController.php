<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Event;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\OpenMatchParticipant;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueWithdrawal;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function adminUser(Request $request): ?User
    {
        return User::find($request->session()->get('admin_user_id'));
    }

    private function publicUpload(Request $request, string $field, string $folder): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $destination = public_path('uploads/' . $folder);

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return 'uploads/' . $folder . '/' . $filename;
    }

    private function publicUploadMultiple(Request $request, string $field, string $folder): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ((array) $request->file($field) as $file) {
            if (! $file) {
                continue;
            }

            $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/' . $folder);

            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }

            $file->move($destination, $filename);
            $paths[] = 'uploads/' . $folder . '/' . $filename;
        }

        return $paths;
    }

    private function normalizeFacilities(?array $facilities): array
    {
        return collect($facilities ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    private function deletePublicUpload(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function isSuperadmin(Request $request): bool
    {
        return $request->session()->get('admin_role') === 'superadmin';
    }

    private function ownedCourtsQuery(Request $request)
    {
        $query = Lapangan::query();
        if (! $this->isSuperadmin($request)) {
            $userId = $request->session()->get('admin_user_id');
            $query->where(function ($q) use ($userId) {
                $q->where('owner_admin_id', $userId)
                  ->orWhereHas('venue', fn ($v) => $v->where('owner_admin_id', $userId));
            });
        }
        return $query;
    }

    private function ensureCourtAccess(Request $request, Lapangan $lapangan): void
    {
        $lapangan->loadMissing('venue');
        $isOwner = (int) $lapangan->owner_admin_id === (int) $request->session()->get('admin_user_id')
            || (int) ($lapangan->venue?->owner_admin_id ?? 0) === (int) $request->session()->get('admin_user_id');
        abort_unless($this->isSuperadmin($request) || $isOwner, 403);
    }

    private function ensureBookingAccess(Request $request, Booking $booking): void
    {
        $booking->loadMissing('lapangan.venue');
        $isOwner = (int) ($booking->lapangan?->owner_admin_id ?? 0) === (int) $request->session()->get('admin_user_id')
            || (int) ($booking->lapangan?->venue?->owner_admin_id ?? 0) === (int) $request->session()->get('admin_user_id');
        abort_unless($this->isSuperadmin($request) || $isOwner, 403);
    }

    private function layoutData(Request $request, array $extra = []): array
    {
        $adminUser = $this->adminUser($request);
        $courtIds = $this->ownedCourtsQuery($request)->select('id');
        $pendingReviewCount = Review::whereIn('lapangan_id', $courtIds)->whereNull('reply_message')->count();
        $pendingPaymentCount = Payment::whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds))->where('status', '!=', 'Lunas')->count();

        $dbNotifs = $adminUser ? $adminUser->unreadNotifications()->latest()->take(10)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => 'system',
                'icon' => 'fa-solid fa-bell',
                'label' => $n->data['title'] ?? 'Notifikasi Sistem',
                'meta' => $n->data['message'] ?? '',
                'time' => optional($n->created_at)->diffForHumans(),
                'sort_at' => optional($n->created_at)?->timestamp ?? 0,
                'url' => $n->data['url'] ?? route('admin.dashboard'),
                'read_at' => $n->read_at,
            ];
        }) : collect();

        $notifications = collect()
            ->merge(Review::with(['user', 'lapangan'])
                ->whereIn('lapangan_id', $courtIds)
                ->whereNull('reply_message')
                ->latest()
                ->get()
                ->map(function ($review) {
                    return [
                        'type' => 'review',
                        'icon' => 'fa-regular fa-message',
                        'label' => 'Review belum dibalas',
                        'meta' => ($review->user?->name ?? 'User') . ' • ' . ($review->lapangan?->nama ?? 'Lapangan') . ' • rating ' . $review->rating . '/5',
                        'time' => optional($review->created_at)->diffForHumans(),
                        'sort_at' => optional($review->created_at)?->timestamp ?? 0,
                        'url' => route('admin.reviews'),
                    ];
                }))
            ->merge(Payment::with(['user', 'booking.lapangan'])
                ->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds))
                ->where('status', '!=', 'Lunas')
                ->latest()
                ->get()
                ->map(function ($payment) {
                    return [
                        'type' => 'payment',
                        'icon' => 'fa-solid fa-wallet',
                        'label' => 'Pembayaran belum diverifikasi',
                        'meta' => ($payment->user?->name ?? 'User') . ' • ' . ($payment->booking?->lapangan?->nama ?? 'Lapangan'),
                        'time' => optional($payment->created_at)->diffForHumans(),
                        'sort_at' => optional($payment->created_at)?->timestamp ?? 0,
                        'url' => route('admin.payments'),
                    ];
                }))
            ->merge($dbNotifs)
            ->sortByDesc('sort_at')
            ->values();

        $unreadDbCount = $adminUser ? $adminUser->unreadNotifications()->count() : 0;
        $totalNotificationCount = $pendingReviewCount + $pendingPaymentCount + $unreadDbCount;

        return array_merge([
            'adminUser' => $adminUser,
            'pendingReviewCount' => $pendingReviewCount,
            'pendingPaymentCount' => $pendingPaymentCount,
            'totalNotificationCount' => $totalNotificationCount,
            'notifications' => $notifications,
        ], $extra);
    }

    public function apiNotifications(Request $request)
    {
        $data = $this->layoutData($request);
        return response()->json([
            'totalCount' => $data['totalNotificationCount'],
            'pendingReviews' => $data['pendingReviewCount'],
            'pendingPayments' => $data['pendingPaymentCount'],
            'notifications' => $data['notifications'],
        ]);
    }

    public function notifications(Request $request): View
    {
        $adminUser = $this->adminUser($request);
        abort_unless($adminUser, 403);

        $items = $adminUser->notifications()->latest()->paginate(15);

        return view('admin.notifications', $this->layoutData($request, compact('items')));
    }

    public function notificationRead(Request $request, string $notification): RedirectResponse
    {
        $adminUser = $this->adminUser($request);
        abort_unless($adminUser, 403);

        $item = $adminUser->notifications()->where('id', $notification)->firstOrFail();
        $item->markAsRead();

        $url = $item->data['url'] ?? route('admin.dashboard');
        if (! is_string($url) || (! str_starts_with($url, '/') && ! str_starts_with($url, url('/')))) {
            $url = route('admin.dashboard');
        }

        return redirect($url);
    }

    public function notificationMarkRead(Request $request, string $notification): RedirectResponse
    {
        $adminUser = $this->adminUser($request);
        abort_unless($adminUser, 403);

        $item = $adminUser->notifications()->where('id', $notification)->firstOrFail();
        if ($item->read_at === null) {
            $item->markAsRead();
        }

        return back()->with('success', 'Notifikasi berhasil ditandai sudah dibaca.');
    }

    public function notificationsReadAll(Request $request): RedirectResponse
    {
        $adminUser = $this->adminUser($request);
        abort_unless($adminUser, 403);

        $adminUser->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi sudah ditandai dibaca.');
    }

    public function notificationDelete(Request $request, string $notification): RedirectResponse
    {
        $adminUser = $this->adminUser($request);
        abort_unless($adminUser, 403);

        $adminUser->notifications()->where('id', $notification)->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function notificationsClear(Request $request): RedirectResponse
    {
        $adminUser = $this->adminUser($request);
        abort_unless($adminUser, 403);

        $deleted = $adminUser->notifications()->delete();

        return redirect()
            ->route('admin.notifications')
            ->with('success', $deleted > 0 ? 'Semua notifikasi berhasil dibersihkan.' : 'Tidak ada notifikasi untuk dibersihkan.');
    }

    public function dashboard(Request $request): View
    {
        $isSuper = $this->isSuperadmin($request);
        $commissionPercent = max(0, min(100, (float) config('arenasports.venue_commission_percent', 10)));
        $paidStatuses = ['Lunas', 'settlement', 'capture', 'success', 'paid'];

        $courtIds = $this->ownedCourtsQuery($request)->select('id');
        $ownerId = (int) $request->session()->get('admin_user_id');

        $months = collect(range(5, 0))->map(fn ($offset) => now()->subMonths($offset)->format('Y-m'))->values()->all();
        $chartLabels = array_map(fn ($ym) => \Carbon\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M Y'), $months);

        if ($isSuper) {
            $allBookings = Booking::query();
            $allPaidPayments = Payment::whereIn('status', $paidStatuses)
                ->whereHas('booking.lapangan', fn ($q) => $q->whereNotNull('venue_id'));
            $pendingVenueApps = class_exists(\App\Models\VenueApplication::class)
                ? \App\Models\VenueApplication::whereIn('status', ['pending', 'revision'])->count()
                : 0;
            $pendingPayments = Payment::where(function ($q) use ($paidStatuses) {
                $q->where('status', 'Menunggu')
                    ->orWhereNotIn('status', array_merge($paidStatuses, ['Gagal', 'Expired', 'Dibatalkan', 'Ditolak']));
            })->count();

            $gross = (int) (clone $allPaidPayments)->sum('amount');
            $platformCommission = (int) floor($gross * $commissionPercent / 100);

            $stats = [
                ['label' => 'Total User', 'value' => User::where('role', 'user')->count()],
                ['label' => 'Total Venue', 'value' => Venue::count()],
                ['label' => 'Total Lapangan', 'value' => Lapangan::count()],
                ['label' => 'Venue Menunggu Verifikasi', 'value' => $pendingVenueApps],
                ['label' => 'Total Booking', 'value' => (clone $allBookings)->count()],
                ['label' => 'Pendapatan Platform (Komisi '.$commissionPercent.'%)', 'value' => 'Rp ' . number_format($platformCommission, 0, ',', '.')],
                ['label' => 'Menunggu Konfirmasi Pembayaran', 'value' => $pendingPayments],
            ];

            $bookingPerMonth = Booking::all()
                ->groupBy(fn ($b) => $b->booking_date ? \Carbon\Carbon::parse($b->booking_date)->format('Y-m') : null)
                ->map(fn ($items) => $items->count());
            $revenuePerMonth = Payment::whereIn('status', $paidStatuses)
                ->whereNotNull('paid_at')
                ->get()
                ->groupBy(fn ($p) => $p->paid_at?->format('Y-m'))
                ->map(fn ($items) => (int) floor($items->sum('amount') * $commissionPercent / 100));
            $grossPerMonth = Payment::whereIn('status', $paidStatuses)
                ->whereNotNull('paid_at')
                ->get()
                ->groupBy(fn ($p) => $p->paid_at?->format('Y-m'))
                ->map(fn ($items) => (int) $items->sum('amount'));

            $bookingStatus = [
                'Menunggu' => Booking::where('status', 'Menunggu')->count(),
                'Lunas/DP' => Booking::whereIn('status', ['Lunas', 'DP'])->count(),
                'Selesai' => Booking::where('status', 'Selesai')->count(),
                'Dibatalkan' => Booking::where('status', 'Dibatalkan')->count(),
            ];
        } else {
            $venueCount = Venue::where('owner_admin_id', $ownerId)->count();
            $ownedBookings = Booking::whereIn('lapangan_id', $courtIds);
            $ownedPayments = Payment::whereIn('booking_id', (clone $ownedBookings)->select('id'));
            $pendingPayments = (clone $ownedPayments)
                ->where(function ($q) use ($paidStatuses) {
                    $q->where('status', 'Menunggu')
                        ->orWhereNotIn('status', array_merge($paidStatuses, ['Gagal', 'Expired', 'Dibatalkan', 'Ditolak']));
                })->count();
            $grossVenue = (int) (clone $ownedPayments)->whereIn('status', $paidStatuses)->sum('amount');
            $netVenue = (int) floor($grossVenue * (100 - $commissionPercent) / 100);

            $stats = [
                ['label' => 'Total Venue Sendiri', 'value' => $venueCount],
                ['label' => 'Total Lapangan Sendiri', 'value' => (clone $this->ownedCourtsQuery($request))->count()],
                ['label' => 'Total Booking Venue Sendiri', 'value' => (clone $ownedBookings)->count()],
                ['label' => 'Menunggu Konfirmasi Pembayaran', 'value' => $pendingPayments],
                ['label' => 'Pendapatan Kotor', 'value' => 'Rp ' . number_format($grossVenue, 0, ',', '.')],
                ['label' => 'Pendapatan Bersih (setelah komisi '.$commissionPercent.'%)', 'value' => 'Rp ' . number_format($netVenue, 0, ',', '.')],
            ];

            $bookingPerMonth = (clone $ownedBookings)->get()
                ->groupBy(fn ($b) => $b->booking_date ? \Carbon\Carbon::parse($b->booking_date)->format('Y-m') : null)
                ->map(fn ($items) => $items->count());
            $grossPerMonth = (clone $ownedPayments)->whereIn('status', $paidStatuses)->whereNotNull('paid_at')->get()
                ->groupBy(fn ($p) => $p->paid_at?->format('Y-m'))
                ->map(fn ($items) => (int) $items->sum('amount'));
            $revenuePerMonth = $grossPerMonth->map(fn ($v) => (int) floor($v * (100 - $commissionPercent) / 100));

            $bookingStatus = [
                'Menunggu' => (clone $ownedBookings)->where('status', 'Menunggu')->count(),
                'Lunas/DP' => (clone $ownedBookings)->whereIn('status', ['Lunas', 'DP'])->count(),
                'Selesai' => (clone $ownedBookings)->where('status', 'Selesai')->count(),
                'Dibatalkan' => (clone $ownedBookings)->where('status', 'Dibatalkan')->count(),
            ];
        }

        return view('admin.dashboard', $this->layoutData($request, [
            'stats' => $stats,
            'isSuper' => $isSuper,
            'isVenueAdmin' => ! $isSuper,
            'chartLabels' => $chartLabels,
            'chartBookings' => array_map(fn ($m) => (int) ($bookingPerMonth[$m] ?? 0), $months),
            'chartRevenue' => array_map(fn ($m) => (int) ($revenuePerMonth[$m] ?? 0), $months),
            'chartGross' => array_map(fn ($m) => (int) ($grossPerMonth[$m] ?? 0), $months),
            'bookingStatusLabels' => array_keys($bookingStatus),
            'bookingStatusValues' => array_values($bookingStatus),
        ]));
    }

    public function users(Request $request): View
    {
        $users = User::withCount('bookings')->orderByRaw("CASE role WHEN 'superadmin' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")->orderByDesc('id')->get();
        return view('admin.users', $this->layoutData($request, compact('users')));
    }

    public function userCreate(Request $request): View
    {
        return view('admin.user-form', $this->layoutData($request, [
            'mode' => 'create',
            'user' => new User(['role' => 'user', 'status' => 'Aktif']),
        ]));
    }

    public function userStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'points' => ['nullable', 'integer', 'min:0'],
            'role' => ['required', 'in:admin,user,superadmin'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['points'] = $data['points'] ?? 0;

        User::create($data);
        return redirect()->route('admin.users')->with('success', 'User berhasil ditambahkan.');
    }

    public function userEdit(Request $request, User $user): View
    {
        return view('admin.user-form', $this->layoutData($request, [
            'mode' => 'edit',
            'user' => $user,
        ]));
    }

    public function userUpdate(Request $request, User $user): RedirectResponse
    {
        if ($user->role === 'superadmin' && (int) $user->id === (int) $request->session()->get('admin_user_id')) {
            return back()->with('error', 'Role akun Superadmin yang sedang digunakan tidak dapat diubah dari menu ini.');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'points' => ['nullable', 'integer', 'min:0'],
            'role' => ['required', 'in:admin,user,superadmin,venue_admin'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);
        if ($user->role === 'venue_admin' && $data['role'] !== 'venue_admin') {
            return back()->withInput()->with('error', 'Role Admin Venue tidak dapat diubah sebelum kepemilikan venue dialihkan.');
        }
        if ($data['role'] === 'venue_admin' && ! Venue::where('owner_admin_id', $user->id)->exists()) {
            return back()->withInput()->with('error', 'Role Admin Venue hanya diberikan melalui persetujuan pengajuan venue.');
        }

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        if (!array_key_exists('points', $data) || $data['points'] === null || $data['points'] === '') {
            unset($data['points']);
        }

        $user->update($data);
        return redirect()->route('admin.users')->with('success', 'User berhasil diperbarui.');
    }

    public function userToggle(User $user): RedirectResponse
    {
        if ($user->id === request()->session()->get('admin_user_id') || $user->role === 'superadmin') {
            return back()->with('error', 'Status akun superadmin tidak dapat diubah dari menu ini.');
        }
        $user->update(['status' => $user->status === 'Aktif' ? 'Nonaktif' : 'Aktif']);
        return back()->with('success', 'Status user berhasil diperbarui.');
    }

    public function userDelete(User $user): RedirectResponse
    {
        if (in_array($user->role, ['admin', 'superadmin', 'venue_admin'], true) || $user->id === request()->session()->get('admin_user_id')) {
            return back()->with('error', 'Akun administrator atau akun yang sedang digunakan tidak dapat dihapus.');
        }
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    public function venuesManage(Request $request): View
    {
        abort_unless($this->isSuperadmin($request), 403);

        $venues = Venue::with(['owner', 'lapangans'])
            ->withCount('lapangans')
            ->latest()
            ->get();

        return view('admin.venues-manage', $this->layoutData($request, compact('venues')));
    }

    public function venueManageShow(Request $request, Venue $venue): View
    {
        abort_unless($this->isSuperadmin($request), 403);

        $venue->load(['owner', 'lapangans' => fn ($q) => $q->latest()]);
        $courts = $venue->lapangans;

        return view('admin.venue-manage-show', $this->layoutData($request, compact('venue', 'courts')));
    }

    public function courts(Request $request): View
    {
        $courts = $this->ownedCourtsQuery($request)->latest()->get();
        return view('admin.courts', $this->layoutData($request, compact('courts')));
    }

    public function courtCreate(Request $request): View
    {
        $venues = $this->isSuperadmin($request)
            ? Venue::with('owner')->orderBy('name')->get()
            : Venue::where('owner_admin_id', $request->session()->get('admin_user_id'))->orderBy('name')->get();

        return view('lapangan.create', $this->layoutData($request, [
            'venueAdmins' => User::whereIn('role', ['admin', 'venue_admin'])->where('status', 'Aktif')->orderBy('name')->get(),
            'venues' => $venues,
            'isSuperadmin' => $this->isSuperadmin($request),
        ]));
    }

    public function courtStore(Request $request): RedirectResponse
    {
        $data = $this->validateCourtPayload($request);
        $venue = $this->resolveCourtVenue($request, $request->integer('venue_id') ?: null);

        if ($this->isSuperadmin($request) && !$venue) {
            return back()->withInput()->withErrors(['venue_id' => 'Pilih venue untuk lapangan ini.']);
        }

        $data = $this->applyCourtRelations($request, $data, $venue);

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->publicUpload($request, 'foto', 'courts');
        }

        $data['foto_gallery'] = $this->publicUploadMultiple($request, 'foto_gallery', 'courts');
        $data['court_facilities'] = $this->normalizeFacilities($request->input('court_facilities', []));
        $data['rating'] = 0;
        $data['status'] = $data['operational_status'] === 'Beroperasi'
            ? 'Tersedia'
            : $data['operational_status'];

        foreach ([
            'price_labels', 'price_day_types', 'price_days',
            'price_start_times', 'price_end_times', 'price_prices',
            'remove_gallery', 'cover_photo',
        ] as $key) {
            unset($data[$key]);
        }

        $court = Lapangan::create($data);
        $this->syncCourtPriceRules($court, $request);

        return redirect()->route('admin.courts')->with('success', 'Lapangan berhasil ditambahkan.');
    }

    public function courtEdit(Request $request, Lapangan $lapangan): View
    {
        $this->ensureCourtAccess($request, $lapangan);

        $venues = $this->isSuperadmin($request)
            ? Venue::with('owner')->orderBy('name')->get()
            : Venue::where('owner_admin_id', $request->session()->get('admin_user_id'))->orderBy('name')->get();

        $lapangan->load(['priceRules', 'venue']);

        return view('lapangan.edit', $this->layoutData($request, [
            'court' => $lapangan,
            'venueAdmins' => User::whereIn('role', ['admin', 'venue_admin'])->where('status', 'Aktif')->orderBy('name')->get(),
            'venues' => $venues,
            'isSuperadmin' => $this->isSuperadmin($request),
        ]));
    }

    public function courtUpdate(Request $request, Lapangan $lapangan): RedirectResponse
    {
        $this->ensureCourtAccess($request, $lapangan);

        $data = $this->validateCourtPayload($request);
        $venue = $this->resolveCourtVenue($request, $request->integer('venue_id') ?: $lapangan->venue_id);

        if ($this->isSuperadmin($request) && !$venue) {
            return back()->withInput()->withErrors(['venue_id' => 'Pilih venue untuk lapangan ini.']);
        }

        $data = $this->applyCourtRelations($request, $data, $venue);

        $gallery = collect(is_array($lapangan->foto_gallery) ? $lapangan->foto_gallery : [])
            ->filter()
            ->values()
            ->all();

        foreach (($request->input('remove_gallery', []) ?: []) as $path) {
            $this->deletePublicUpload($path);
            $gallery = array_values(array_filter($gallery, fn ($item) => $item !== $path));
        }

        $coverPhoto = trim((string) $request->input('cover_photo', ''));
        if ($coverPhoto !== '' && in_array($coverPhoto, $gallery, true)) {
            if ($lapangan->foto && $lapangan->foto !== $coverPhoto) {
                $gallery[] = $lapangan->foto;
            }

            $data['foto'] = $coverPhoto;
            $gallery = array_values(array_filter($gallery, fn ($item) => $item !== $coverPhoto));
        }

        if ($request->hasFile('foto')) {
            if ($lapangan->foto) {
                $gallery[] = $lapangan->foto;
            }

            $data['foto'] = $this->publicUpload($request, 'foto', 'courts');
        }

        $newGallery = $this->publicUploadMultiple($request, 'foto_gallery', 'courts');
        $data['foto_gallery'] = array_values(array_unique(array_filter(array_merge($gallery, $newGallery))));
        $data['court_facilities'] = $this->normalizeFacilities($request->input('court_facilities', []));
        $data['status'] = $data['operational_status'] === 'Beroperasi'
            ? 'Tersedia'
            : $data['operational_status'];

        foreach ([
            'price_labels', 'price_day_types', 'price_days',
            'price_start_times', 'price_end_times', 'price_prices',
            'remove_gallery', 'cover_photo',
        ] as $key) {
            unset($data[$key]);
        }

        $lapangan->update($data);
        $this->syncCourtPriceRules($lapangan, $request);

        return redirect()->route('admin.courts')->with('success', 'Lapangan berhasil diperbarui.');
    }

    private function validateCourtPayload(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:Futsal,Badminton'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'harga' => ['required', 'numeric', 'min:0'],
            'operational_status' => ['required', 'in:Beroperasi,Perawatan,Perbaikan,Tidak Aktif'],
            'maintenance_note' => ['nullable', 'string', 'max:1000'],
            'deskripsi' => ['nullable', 'string', 'max:3000'],
            'use_venue_location' => ['nullable', 'boolean'],
            'area_type' => ['nullable', 'in:Indoor,Outdoor'],
            'floor_type' => ['nullable', 'string', 'max:80'],
            'court_facilities' => ['nullable', 'array', 'max:30'],
            'court_facilities.*' => ['nullable', 'string', 'max:80'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_gallery' => ['nullable', 'array', 'max:20'],
            'foto_gallery.*' => ['image', 'max:4096'],
            'remove_gallery' => ['nullable', 'array'],
            'remove_gallery.*' => ['string', 'max:500'],
            'cover_photo' => ['nullable', 'string', 'max:500'],
            'owner_admin_id' => ['nullable', 'exists:users,id'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'price_labels' => ['nullable', 'array'],
            'price_labels.*' => ['nullable', 'string', 'max:80'],
            'price_day_types' => ['nullable', 'array'],
            'price_day_types.*' => ['nullable', 'in:weekday,weekend,custom'],
            'price_days' => ['nullable', 'array'],
            'price_days.*' => ['nullable', 'array'],
            'price_days.*.*' => ['nullable', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu'],
            'price_start_times' => ['nullable', 'array'],
            'price_start_times.*' => ['nullable', 'date_format:H:i'],
            'price_end_times' => ['nullable', 'array'],
            'price_end_times.*' => ['nullable', 'date_format:H:i'],
            'price_prices' => ['nullable', 'array'],
            'price_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function resolveCourtVenue(Request $request, ?int $venueId): ?Venue
    {
        if ($venueId) {
            $query = Venue::query();

            if (!$this->isSuperadmin($request)) {
                $query->where('owner_admin_id', $request->session()->get('admin_user_id'));
            }

            return $query->findOrFail($venueId);
        }

        if ($request->session()->get('admin_role') === 'venue_admin') {
            return Venue::where('owner_admin_id', $request->session()->get('admin_user_id'))->first();
        }

        return null;
    }

    private function applyCourtRelations(Request $request, array $data, ?Venue $venue): array
    {
        $useVenueLocation = $request->boolean('use_venue_location');

        if (!$venue) {
            $useVenueLocation = false;
        }

        if ($useVenueLocation && $venue) {
            $data['lokasi'] = $venue->address ?: ($venue->name ?? '');
            $data['latitude'] = $venue->latitude;
            $data['longitude'] = $venue->longitude;
            $data['use_venue_location'] = true;
        } else {
            $data['use_venue_location'] = false;

            if (blank($data['lokasi'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lokasi' => 'Alamat lapangan wajib diisi jika memakai lokasi khusus.',
                ]);
            }
        }

        if ($this->isSuperadmin($request)) {
            $data['owner_admin_id'] = $request->input('owner_admin_id')
                ?: $venue?->owner_admin_id;
        } else {
            $data['owner_admin_id'] = $request->session()->get('admin_user_id');
        }

        $data['venue_id'] = $venue?->id;

        if (!in_array($data['operational_status'], ['Perawatan', 'Perbaikan'], true)) {
            $data['maintenance_note'] = null;
        }

        return $data;
    }

    private function syncCourtPriceRules(Lapangan $lapangan, Request $request): void
    {
        $lapangan->priceRules()->delete();

        $prices = $request->input('price_prices', []) ?: [];
        $starts = $request->input('price_start_times', []) ?: [];
        $ends = $request->input('price_end_times', []) ?: [];
        $labels = $request->input('price_labels', []) ?: [];
        $dayTypes = $request->input('price_day_types', []) ?: [];
        $days = $request->input('price_days', []) ?: [];

        foreach ($prices as $index => $price) {
            if ($price === null || $price === '') {
                continue;
            }

            $start = $starts[$index] ?? null;
            $end = $ends[$index] ?? null;

            if (!$start || !$end) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'price_start_times' => 'Jam mulai dan jam selesai aturan harga wajib diisi.',
                ]);
            }

            $dayType = $dayTypes[$index] ?? 'weekday';
            $ruleDays = array_values(array_filter($days[$index] ?? []));

            if ($dayType === 'custom' && $ruleDays === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'price_days' => 'Pilih hari untuk aturan harga khusus.',
                ]);
            }

            $lapangan->priceRules()->create([
                'label' => trim((string) ($labels[$index] ?? '')) ?: null,
                'day_type' => $dayType,
                'days' => $dayType === 'custom' ? $ruleDays : null,
                'start_time' => $start,
                'end_time' => $end,
                'price' => (int) $price,
                'priority' => (int) $index,
            ]);
        }
    }

    public function courtDelete(Request $request, Lapangan $lapangan): RedirectResponse
    {
        $this->ensureCourtAccess($request, $lapangan);
        $this->deletePublicUpload($lapangan->foto);
        foreach (($lapangan->foto_gallery ?? []) as $galleryPath) {
            $this->deletePublicUpload($galleryPath);
        }
        $lapangan->delete();
        return back()->with('success', 'Lapangan berhasil dihapus.');
    }

    public function bookings(Request $request): View
    {
        $bookings = Booking::with(['user', 'lapangan', 'extensions'])->whereIn('lapangan_id', $this->ownedCourtsQuery($request)->select('id'))->latest()->get();
        $users = User::where('role', 'user')->where('status', 'Aktif')->orderBy('name')->get();
        $courts = $this->ownedCourtsQuery($request)->orderBy('nama')->get();
        return view('admin.bookings', $this->layoutData($request, compact('bookings', 'users', 'courts')));
    }

    public function bookingStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'lapangan_id' => ['required', 'exists:lapangan,id'],
            'payment_method' => ['required', 'in:Transfer,Virtual Account,E-Wallet,Cash'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'hours' => ['required', 'integer', 'min:1', 'max:8'],
            'status' => ['required', 'in:Menunggu,Dibatalkan'],
        ]);
        $court = $this->ownedCourtsQuery($request)->findOrFail($data['lapangan_id']);
        if ($data['booking_date'] === today()->toDateString() && strtotime($data['start_time']) <= strtotime(now()->format('H:i'))) {
            return back()->withInput()->withErrors(['start_time' => 'Untuk booking hari ini, jam mulai harus masih akan datang.']);
        }
        $start = strtotime($data['start_time']);
        $end = strtotime($data['end_time']);
        if ($end <= $start) {
            return back()->withInput()->withErrors(['end_time' => 'Jam selesai harus lebih akhir dari jam mulai.']);
        }
        if (($end - $start) !== ((int) $data['hours'] * 3600)) {
            return back()->withInput()->withErrors(['hours' => 'Durasi harus sesuai dengan selisih jam mulai dan jam selesai.']);
        }
        $overlap = Booking::where('lapangan_id', $court->id)
            ->whereDate('booking_date', $data['booking_date'])
            ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->exists();
        if ($overlap) {
            return back()->withInput()->withErrors(['start_time' => 'Jadwal lapangan bentrok dengan booking yang sudah ada.']);
        }
        $data['total_price'] = app(\App\Services\CourtPricingService::class)
            ->quote($court, $data['booking_date'], $data['start_time'], (int) $data['hours'])['total'];
        $data['payment_method'] = 'Midtrans';
        $data['status'] = $data['status'] === 'Dibatalkan' ? 'Dibatalkan' : 'Menunggu';
        $booking = Booking::create($data);
        Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'method' => 'Midtrans',
            'amount' => $booking->total_price,
            'status' => 'Menunggu',
        ]);
        return back()->with('success', 'Booking berhasil ditambahkan.');
    }

    public function bookingToggle(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureBookingAccess($request, $booking);
        return back()->with('error', 'Status lunas tidak dapat diubah manual. Pembayaran hanya dikonfirmasi otomatis oleh notifikasi Midtrans yang valid.');
    }

    public function bookingDelete(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureBookingAccess($request, $booking);
        $booking->delete();
        return back()->with('success', 'Booking berhasil dihapus.');
    }

    /** Workspace for Open Match and Event controls. */
    public function controlCenter(Request $request): View
    {
        $courtIds = $this->ownedCourtsQuery($request)->select('id');
        $matchesQuery = OpenMatch::with(['booking.user', 'booking.lapangan']);
        $bookingsQuery = Booking::with(['user', 'lapangan'])->whereIn('status', ['Lunas', 'DP']);
        $eventsQuery = Event::with('creator');

        if (!$this->isSuperadmin($request)) {
            $matchesQuery->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds));
            $bookingsQuery->whereIn('lapangan_id', $courtIds);
            $eventsQuery->where('created_by', $request->session()->get('admin_user_id'));
        }

        $matches = $matchesQuery->latest()->get();
        $bookings = $bookingsQuery->latest()->get();
        $events = $eventsQuery->latest()->get();

        return view('admin.control-center', $this->layoutData($request, compact('matches', 'bookings', 'events')));
    }

    public function openMatches(Request $request): View
    {
        $courtIds = $this->ownedCourtsQuery($request)->select('id');
        $filter = $request->query('filter', 'aktif');

        $baseQuery = OpenMatch::with(['booking.user', 'booking.lapangan.venue', 'participants']);
        if (!$this->isSuperadmin($request)) {
            $baseQuery->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds));
        }

        $allMatches = (clone $baseQuery)->latest('tanggal')->latest('start_time')->get();
        $isFinished = fn ($match) => $match->status === 'Selesai' || $match->has_ended;
        $isCancelled = fn ($match) => $match->status === 'Dibatalkan';

        $counts = [
            'aktif' => $allMatches->filter(fn ($m) => ! $isCancelled($m) && ! $isFinished($m))->count(),
            'selesai' => $allMatches->filter(fn ($m) => ! $isCancelled($m) && $isFinished($m))->count(),
            'dibatalkan' => $allMatches->filter(fn ($m) => $isCancelled($m))->count(),
            'semua' => $allMatches->count(),
        ];

        if (! in_array($filter, ['aktif', 'selesai', 'dibatalkan', 'semua'], true)) {
            $filter = 'aktif';
        }

        $matches = match ($filter) {
            'selesai' => $allMatches->filter(fn ($m) => ! $isCancelled($m) && $isFinished($m))->values(),
            'dibatalkan' => $allMatches->filter(fn ($m) => $isCancelled($m))->values(),
            'semua' => $allMatches->values(),
            default => $allMatches->filter(fn ($m) => ! $isCancelled($m) && ! $isFinished($m))->values(),
        };

        $bookings = Booking::with(['user', 'lapangan'])
            ->whereIn('lapangan_id', $courtIds)
            ->whereIn('status', ['Lunas', 'DP'])
            ->latest()
            ->get();

        return view('admin.open-matches', $this->layoutData($request, compact('matches', 'bookings', 'filter', 'counts')));
    }

    public function openMatchStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'registration_type' => ['nullable', 'in:Individu,Kelompok'],
            'tanggal' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'jumlah_pemain' => ['required', 'integer', 'min:1', 'max:99'],
            'jumlah_bergabung' => ['nullable', 'integer', 'min:0', 'max:99'],
            'biaya_per_pemain' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'level' => ['nullable', 'string', 'max:50'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Open,Penuh,Selesai,Dibatalkan'],
        ]);

        if (!empty($data['booking_id'])) {
            $booking = Booking::findOrFail($data['booking_id']);
            $this->ensureBookingAccess($request, $booking);
        }
        $data['jumlah_bergabung'] = $data['jumlah_bergabung'] ?? 0;
        $data['registration_type'] = $data['registration_type'] ?? 'Individu';
        $match = OpenMatch::create($data);

        if (! empty($data['booking_id'])) {
            $hostId = Booking::find($data['booking_id'])?->user_id;

            if ($hostId) {
                OpenMatchParticipant::insertOrIgnore([
                    'open_match_id' => $match->id,
                    'user_id' => $hostId,
                    'role' => 'Host',
                    'member_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Notifikasi realtime ke semua user aktif
        User::where('role', 'user')->where('status', 'Aktif')->get()->each(function (User $user) use ($match) {
            $user->notify(new ArenaUserNotification(
                'Open Match Baru: ' . $match->title,
                'Pertandingan terbuka baru ' . $match->title . ' (' . $match->jenis . ') dibuka. Segera bergabung!',
                route('user.openmatches', [], false)
            ));
        });

        return back()->with('success', 'Open match berhasil ditambahkan.');
    }

    public function openMatchUpdate(Request $request, OpenMatch $openMatch): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'registration_type' => ['nullable', 'in:Individu,Kelompok'],
            'tanggal' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'jumlah_pemain' => ['required', 'integer', 'min:1', 'max:99'],
            'jumlah_bergabung' => ['nullable', 'integer', 'min:0', 'max:99'],
            'biaya_per_pemain' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'level' => ['nullable', 'string', 'max:50'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Open,Penuh,Selesai,Dibatalkan'],
        ]);

        abort_unless($this->isSuperadmin($request) || (int) $openMatch->booking?->lapangan?->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
        $wasCancelled = $openMatch->status === 'Dibatalkan';
        if (!empty($data['booking_id'])) { $this->ensureBookingAccess($request, Booking::findOrFail($data['booking_id'])); }
        $data['jumlah_bergabung'] = $data['jumlah_bergabung'] ?? 0;
        $data['registration_type'] = $data['registration_type'] ?? $openMatch->registration_type ?? 'Individu';
        $openMatch->update($data);
        if (!$wasCancelled && $openMatch->status === 'Dibatalkan') {
            $openMatch->loadMissing('booking.user');
            $openMatch->booking?->user?->notify(new ArenaUserNotification(
                'Open Match Dibatalkan',
                'Open Match ' . $openMatch->title . ' telah dibatalkan oleh pengelola.',
                route('user.openmatches', [], false)
            ));
        }

        return back()->with('success', 'Open match berhasil diperbarui.');
    }

    public function openMatchToggle(Request $request, OpenMatch $openMatch): RedirectResponse
    {
        abort_unless($this->isSuperadmin($request) || (int) $openMatch->booking?->lapangan?->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
        $next = match ($openMatch->status) {
            'Open' => 'Penuh',
            'Penuh' => 'Selesai',
            'Selesai' => 'Open',
            default => 'Open',
        };

        $openMatch->update(['status' => $next]);
        return back()->with('success', 'Status open match berhasil diperbarui.');
    }

    public function openMatchDelete(Request $request, OpenMatch $openMatch): RedirectResponse
    {
        abort_unless($this->isSuperadmin($request) || (int) $openMatch->booking?->lapangan?->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
        $openMatch->loadMissing('booking.user');
        $openMatch->booking?->user?->notify(new ArenaUserNotification(
            'Open Match Dibatalkan',
            'Open Match ' . $openMatch->title . ' telah dihapus oleh pengelola.',
            route('user.openmatches', [], false)
        ));
        $openMatch->delete();
        return back()->with('success', 'Open match berhasil dihapus.');
    }

    public function reviews(Request $request): View
    {
        $reviews = Review::with(['user', 'lapangan'])->whereIn('lapangan_id', $this->ownedCourtsQuery($request)->select('id'))->latest()->get();
        return view('admin.reviews', $this->layoutData($request, compact('reviews')));
    }

    public function reviewReply(Request $request, Review $review): RedirectResponse
    {
        $this->ensureCourtAccess($request, $review->lapangan);
        $data = $request->validate(['reply_message' => ['required', 'string']]);
        $review->update($data);

        // Notifikasi ke user yang memberikan ulasan
        if ($review->user) {
            $review->user->notify(new ArenaUserNotification(
                'Ulasan Anda Dibalas Pengelola',
                'Pengelola venue membalas ulasan Anda untuk lapangan ' . ($review->lapangan?->nama ?? 'Lapangan') . ': "' . \Illuminate\Support\Str::limit($data['reply_message'], 60) . '"',
                route('user.courts.show', $review->lapangan_id, false)
            ));
        }

        return back()->with('success', 'Balasan review berhasil disimpan.');
    }

    public function reviewToggle(Request $request, Review $review): RedirectResponse
    {
        $this->ensureCourtAccess($request, $review->lapangan);
        $review->update(['is_visible' => !$review->is_visible]);
        return back()->with('success', 'Status review berhasil diperbarui.');
    }

    public function reviewDelete(Request $request, Review $review): RedirectResponse
    {
        $this->ensureCourtAccess($request, $review->lapangan);
        $review->delete();
        return back()->with('success', 'Review berhasil dihapus.');
    }

    private function ensureRewardAccess(Request $request, Reward $reward): void
    {
        if ($this->isSuperadmin($request)) {
            return;
        }

        abort_unless((int) $reward->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
    }

    private function rewardVenues(Request $request)
    {
        if ($this->isSuperadmin($request)) {
            return Venue::orderBy('name')->get();
        }

        return Venue::where('owner_admin_id', $request->session()->get('admin_user_id'))->orderBy('name')->get();
    }

    public function rewards(Request $request): View
    {
        $isSuperadmin = $this->isSuperadmin($request);
        $adminId = (int) $request->session()->get('admin_user_id');

        $rewardsQuery = Reward::with(['owner', 'venue'])->latest();
        $redemptionsQuery = Redemption::with(['reward.venue', 'reward.owner', 'user', 'venue', 'owner'])->latest();

        if (! $isSuperadmin) {
            $rewardsQuery->where(function ($q) use ($adminId) {
                $q->where('owner_admin_id', $adminId)->orWhereNull('owner_admin_id');
            });
            $redemptionsQuery->where(function ($q) use ($adminId) {
                $q->where('owner_admin_id', $adminId)
                    ->orWhereHas('reward', fn ($reward) => $reward->where('owner_admin_id', $adminId));
            });
        }

        $rewards = $rewardsQuery->get();
        $redemptions = $redemptionsQuery->get();
        $venues = $this->rewardVenues($request);

        return view('admin.rewards', $this->layoutData($request, compact('rewards', 'redemptions', 'venues', 'isSuperadmin')));
    }

    public function rewardStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ], [
            'title.required' => 'Judul reward wajib diisi.',
            'points_required.required' => 'Poin reward wajib diisi.',
            'points_required.integer' => 'Poin reward harus berupa angka bulat.',
            'status.in' => 'Status reward harus Aktif atau Nonaktif.',
            'image.image' => 'File reward harus berupa gambar valid.',
            'image.mimes' => 'Format gambar reward harus JPG, JPEG, PNG, WEBP, atau SVG.',
            'image.max' => 'Ukuran gambar reward maksimal 5 MB.',
        ]);

        $isSuperadmin = $this->isSuperadmin($request);
        $adminId = (int) $request->session()->get('admin_user_id');
        $venueId = $data['venue_id'] ?? null;

        if ($venueId) {
            $venue = Venue::find($venueId);
            abort_unless($venue && ($isSuperadmin || (int) $venue->owner_admin_id === $adminId), 403, 'Anda tidak memiliki akses ke venue yang dipilih.');
        }

        $data['owner_admin_id'] = $isSuperadmin ? ($venueId ? (Venue::find($venueId)?->owner_admin_id) : $adminId) : $adminId;
        $data['venue_id'] = $venueId ?: null;

        if ($request->hasFile('image')) {
            $data['image'] = $this->publicUpload($request, 'image', 'rewards');
        }

        Reward::create($data);

        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function rewardEdit(Request $request, Reward $reward): View
    {
        $this->ensureRewardAccess($request, $reward);
        $venues = $this->rewardVenues($request);
        $isSuperadmin = $this->isSuperadmin($request);
        return view('admin.reward-edit', $this->layoutData($request, compact('reward', 'venues', 'isSuperadmin')));
    }

    public function rewardUpdate(Request $request, Reward $reward): RedirectResponse
    {
        $this->ensureRewardAccess($request, $reward);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ], [
            'title.required' => 'Judul reward wajib diisi.',
            'points_required.required' => 'Poin reward wajib diisi.',
            'points_required.integer' => 'Poin reward harus berupa angka bulat.',
            'status.in' => 'Status reward harus Aktif atau Nonaktif.',
            'image.image' => 'File reward harus berupa gambar valid.',
            'image.mimes' => 'Format gambar reward harus JPG, JPEG, PNG, WEBP, atau SVG.',
            'image.max' => 'Ukuran gambar reward maksimal 5 MB.',
        ]);

        $isSuperadmin = $this->isSuperadmin($request);
        $adminId = (int) $request->session()->get('admin_user_id');
        $venueId = $data['venue_id'] ?? null;

        if ($venueId) {
            $venue = Venue::find($venueId);
            abort_unless($venue && ($isSuperadmin || (int) $venue->owner_admin_id === $adminId), 403, 'Anda tidak memiliki akses ke venue yang dipilih.');
        }

        $data['venue_id'] = $venueId ?: null;
        if (! $isSuperadmin) {
            $data['owner_admin_id'] = $adminId;
        }

        if ($request->hasFile('image')) {
            $this->deletePublicUpload($reward->image);
            $data['image'] = $this->publicUpload($request, 'image', 'rewards');
        }

        $reward->update($data);

        return redirect()->route('admin.rewards')->with('success', 'Reward berhasil diperbarui.');
    }

    public function rewardToggle(Request $request, Reward $reward): RedirectResponse
    {
        $this->ensureRewardAccess($request, $reward);
        $reward->update(['status' => $reward->status === 'Aktif' ? 'Nonaktif' : 'Aktif']);
        return back()->with('success', 'Status reward berhasil diperbarui.');
    }

    public function rewardDelete(Request $request, Reward $reward): RedirectResponse
    {
        $this->ensureRewardAccess($request, $reward);
        $this->deletePublicUpload($reward->image);
        $reward->delete();
        return back()->with('success', 'Reward berhasil dihapus.');
    }

    public function redemptionVerify(Request $request, Redemption $redemption): RedirectResponse
    {
        $redemption->loadMissing('reward');
        $isSuperadmin = $this->isSuperadmin($request);
        $adminId = (int) $request->session()->get('admin_user_id');
        $ownerId = $redemption->owner_admin_id ?? $redemption->reward?->owner_admin_id;
        abort_unless($isSuperadmin || ($ownerId && (int) $ownerId === $adminId), 403);

        $redemption->update([
            'status' => 'Diverifikasi',
            'verified_at' => now(),
            'verified_by' => $adminId,
        ]);

        if ($redemption->user) {
            $redemption->user->notify(new ArenaUserNotification(
                'Reward Diverifikasi',
                'Reward ' . ($redemption->reward?->title ?? 'Reward') . ' telah diverifikasi oleh venue/admin.',
                route('user.rewards', [], false)
            ));
        }

        return back()->with('success', 'Penukaran reward berhasil diverifikasi.');
    }

    public function payments(Request $request): View
    {
        $payments = Payment::with(['user', 'booking.lapangan'])->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $this->ownedCourtsQuery($request)->select('id')))->latest()->get();
        return view('admin.payments', $this->layoutData($request, compact('payments')));
    }

    public function paymentVerify(Request $request, Payment $payment): RedirectResponse
    {
        $payment->loadMissing('booking.lapangan');
        abort_unless($payment->booking, 404);
        $this->ensureBookingAccess($request, $payment->booking);

        if (strtolower((string) $payment->method) !== 'cash') {
            return back()->with(
                'error',
                'Pembayaran online tidak dapat dikonfirmasi secara manual. Status Midtrans diperbarui melalui notifikasi resmi.'
            );
        }

        // Tetap masuk transaksi untuk memperbaiki pembayaran lama yang sudah
        // Lunas tetapi penanda pemberian poinnya belum tersimpan.

        if (in_array(strtolower((string) $payment->status), [
            'gagal', 'failed', 'deny', 'cancel', 'expire', 'expired'
        ], true)) {
            return back()->with('error', 'Pembayaran Cash yang sudah gagal tidak dapat dikonfirmasi.');
        }

        $paymentChanged = DB::transaction(function () use ($payment): bool {
            $lockedPayment = Payment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking = Booking::whereKey($lockedPayment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (strtolower((string) $lockedPayment->method) !== 'cash') {
                throw new \RuntimeException('Metode pembayaran bukan Cash.');
            }

            $isAlreadyPaid = in_array(strtolower((string) $lockedPayment->status), [
                'lunas', 'settlement', 'capture', 'success', 'paid'
            ], true);

            $booking->loadMissing('user');
            if (!$lockedPayment->reward_points_awarded && $booking->user) {
                $lockedUser = User::whereKey($booking->user_id)->lockForUpdate()->firstOrFail();
                $lockedUser->increment('points', 10);
                $lockedPayment->reward_points_awarded = true;
                $lockedPayment->save();
            }

            if ($isAlreadyPaid) {
                return false;
            }

            if (!in_array(strtolower((string) $lockedPayment->status), [
                'lunas', 'settlement', 'capture', 'success', 'paid'
            ], true)) {
                $lockedPayment->status = 'Lunas';
                $lockedPayment->paid_at = now();
                $lockedPayment->transaction_id = $lockedPayment->transaction_id
                    ?: 'CASH-' . str_pad((string) $lockedPayment->id, 8, '0', STR_PAD_LEFT);
                $lockedPayment->save();

                $booking->status = 'Lunas';
                $booking->save();
                $booking->user?->notify(new ArenaUserNotification(
                    'Pembayaran Berhasil',
                    'Pembayaran Cash untuk booking #' . $booking->id . ' telah dikonfirmasi oleh Admin Venue.',
                    route('user.bookings', [], false)
                ));
                return true;
            }
            return false;
        }, 3);

        return back()->with(
            'success',
            'Pembayaran Cash berhasil dikonfirmasi. Booking sekarang berstatus Lunas.'
        );
    }

    public function paymentSync(Request $request, Payment $payment): RedirectResponse
    {
        $payment->loadMissing('booking.lapangan');
        abort_unless($payment->booking, 404);
        $this->ensureBookingAccess($request, $payment->booking);

        if (strtolower((string) $payment->method) === 'cash') {
            return back()->with('error', 'Pembayaran Cash tidak perlu disinkronkan dari Midtrans.');
        }

        try {
            app(\App\Http\Controllers\MidtransController::class)->syncForAdmin($payment);
            $payment->refresh();

            $isPaid = in_array(strtolower((string) $payment->status), ['lunas', 'settlement', 'capture', 'success', 'paid'], true);

            return back()->with(
                $isPaid ? 'success' : 'error',
                $isPaid
                    ? 'Status Midtrans berhasil disinkronkan. Pembayaran sudah Lunas.'
                    : 'Status Midtrans diperiksa: ' . $payment->status . '. Jika pengguna sudah bayar DANA/QRIS, tunggu beberapa saat lalu cek lagi.'
            );
        } catch (\Throwable $e) {
            \Log::warning('Admin payment sync failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengecek status Midtrans. Coba lagi beberapa saat.');
        }
    }

    public function reports(Request $request): View
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $type = $request->query('type', 'Semua Transaksi');

        $query = Payment::with(['user', 'booking.lapangan'])->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $this->ownedCourtsQuery($request)->select('id')))->latest();
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($type === 'Lunas') {
            $query->where('status', 'Lunas');
        }
        if ($type === 'Menunggu') {
            $query->where('status', 'Menunggu');
        }

        return view('admin.reports', $this->layoutData($request, [
            'reports' => $query->get(),
            'filters' => compact('from', 'to', 'type'),
        ]));
    }

    private function reportQuery(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $type = $request->query('type', 'Semua Transaksi');

        $query = Payment::with(['user', 'booking.lapangan'])->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $this->ownedCourtsQuery($request)->select('id')))->latest();

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($type === 'Lunas') {
            $query->where('status', 'Lunas');
        }
        if ($type === 'Menunggu') {
            $query->where('status', 'Menunggu');
        }

        return $query;
    }

    public function exportReportsCsv(Request $request)
    {
        $reports = $this->reportQuery($request)->get();
        $filename = 'laporan-transaksi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Tanggal', 'User', 'Email', 'Lapangan', 'Metode', 'Jumlah', 'Status']);

            foreach ($reports as $report) {
                fputcsv($handle, [
                    optional($report->created_at)->format('Y-m-d H:i'),
                    $report->user?->name ?? '-',
                    $report->user?->email ?? '-',
                    $report->booking?->lapangan?->nama ?? '-',
                    $report->method ?? '-',
                    (int) $report->amount,
                    $report->status ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportReportsExcel(Request $request)
    {
        $reports = $this->reportQuery($request)->get();
        $filename = 'laporan-transaksi-' . now()->format('Ymd-His') . '.xls';

        $html = '<table border="1">';
        $html .= '<thead><tr>';
        foreach (['Tanggal', 'User', 'Email', 'Lapangan', 'Metode', 'Jumlah', 'Status'] as $header) {
            $html .= '<th style="background:#0fa741;color:#ffffff;font-weight:bold;">' . e($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($reports as $report) {
            $html .= '<tr>';
            $html .= '<td>' . e(optional($report->created_at)->format('Y-m-d H:i')) . '</td>';
            $html .= '<td>' . e($report->user?->name ?? '-') . '</td>';
            $html .= '<td>' . e($report->user?->email ?? '-') . '</td>';
            $html .= '<td>' . e($report->booking?->lapangan?->nama ?? '-') . '</td>';
            $html .= '<td>' . e($report->method ?? '-') . '</td>';
            $html .= '<td>' . e((string) (int) $report->amount) . '</td>';
            $html .= '<td>' . e($report->status ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function profile(Request $request): View
    {
        $profile = $this->adminUser($request);
        return view('admin.profile', $this->layoutData($request, compact('profile')));
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $admin = $this->adminUser($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $admin->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $this->deletePublicUpload($admin->profile_photo);
            $data['profile_photo'] = $this->publicUpload($request, 'profile_photo', 'profiles');
        }

        $admin->update($data);
        return back()->with('success', 'Profil admin berhasil diperbarui.');
    }

    public function bookingExtensionManual(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureBookingAccess($request, $booking);

        if ($booking->status !== 'Lunas') {
            return back()->with('error', 'Perpanjangan manual hanya bisa dilakukan untuk booking yang sudah Lunas.');
        }

        $data = $request->validate([
            'hours'        => ['required', 'integer', 'min:1', 'max:8'],
            'payment_type' => ['required', 'in:Tunai,Gratis'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);

        $previousEnd = substr((string) $booking->end_time, 0, 5);
        $endTimestamp = strtotime($previousEnd) + ((int) $data['hours'] * 3600);
        $newEnd = date('H:i', $endTimestamp);

        if ($newEnd <= $previousEnd) {
            return back()->withInput()->with('error', 'Perpanjangan melewati tengah malam. Pilih durasi yang lebih pendek.');
        }

        $bookingDate = $booking->booking_date->toDateString();

        $overlap = Booking::where('lapangan_id', $booking->lapangan_id)
            ->whereDate('booking_date', $bookingDate)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
            ->where('start_time', '<', $newEnd)
            ->where('end_time', '>', $previousEnd)
            ->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'Jadwal perpanjangan berbenturan dengan booking lain.');
        }

        $lapangan = $booking->lapangan;
        $extensionTotal = app(\App\Services\CourtPricingService::class)
            ->quote($lapangan, $bookingDate, $previousEnd, (int) $data['hours'])['total'];
        $amount = $data['payment_type'] === 'Gratis' ? 0 : $extensionTotal;
        $status = 'Lunas';

        DB::transaction(function () use ($booking, $data, $previousEnd, $newEnd, $amount, $status, $request) {
            $extension = BookingExtension::create([
                'booking_id'       => $booking->id,
                'user_id'          => $booking->user_id,
                'admin_user_id'    => $request->session()->get('admin_user_id'),
                'hours'            => (int) $data['hours'],
                'previous_end_time'=> $previousEnd,
                'new_end_time'     => $newEnd,
                'amount'           => $amount,
                'payment_method'   => $data['payment_type'],
                'status'           => $status,
                'note'             => $data['note'] ?? 'Perpanjangan manual oleh admin.',
                'processed_at'     => now(),
            ]);

            if ($amount > 0) {
                Payment::create([
                    'booking_id'           => $booking->id,
                    'booking_extension_id' => $extension->id,
                    'user_id'              => $booking->user_id,
                    'method'               => $data['payment_type'],
                    'amount'               => $amount,
                    'status'               => 'Lunas',
                    'paid_at'              => now(),
                    'transaction_id'       => 'MANUAL-' . str_pad((string) $extension->id, 8, '0', STR_PAD_LEFT),
                    'reward_points_awarded'=> true,
                ]);

                // Tambahkan poin ke user seperti saat booking (10 poin)
                $user = User::whereKey($booking->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->increment('points', 10);
                }
            }

            // Update end_time, hours, dan total_price booking
            $booking->end_time = $newEnd;
            $booking->hours = (int) $booking->hours + (int) $data['hours'];
            $booking->total_price = (float) $booking->total_price + (float) $amount;
            $booking->save();

            // Kirim notifikasi ke user
            $userForNotif = User::find($booking->user_id);
            if ($userForNotif) {
                $userForNotif->notify(new ArenaUserNotification(
                    'Perpanjangan Booking Disetujui',
                    'Booking #' . $booking->id . ' Anda telah diperpanjang ' . $data['hours'] . ' jam hingga ' . $newEnd . '.' . ($amount > 0 ? ' Anda mendapatkan 10 poin reward!' : ''),
                    route('user.bookings', [], false)
                ));
            }
        });

        return back()->with('success', 'Perpanjangan booking berhasil diproses. Jam selesai diperbarui ke ' . $newEnd . '.');
    }

    public function logoutConfirm(Request $request): View
    {
        return view('admin.logout-confirm', $this->layoutData($request));
    }

    public function logoutSuccess(Request $request): View
    {
        return view('admin.logout-success', $this->layoutData($request));
    }
}