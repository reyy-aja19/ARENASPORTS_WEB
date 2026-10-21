<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
            $query->where('owner_admin_id', $request->session()->get('admin_user_id'));
        }
        return $query;
    }

    private function ensureCourtAccess(Request $request, Lapangan $lapangan): void
    {
        abort_unless($this->isSuperadmin($request) || (int) $lapangan->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
    }

    private function ensureBookingAccess(Request $request, Booking $booking): void
    {
        $booking->loadMissing('lapangan');
        abort_unless($this->isSuperadmin($request) || (int) $booking->lapangan?->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
    }

    private function layoutData(Request $request, array $extra = []): array
    {
        $courtIds = $this->ownedCourtsQuery($request)->select('id');
        $pendingReviewCount = Review::whereIn('lapangan_id', $courtIds)->whereNull('reply_message')->count();
        $pendingPaymentCount = Payment::whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds))->where('status', '!=', 'Lunas')->count();

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
                        'label' => 'Review belum terbaca',
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
            ->sortByDesc('sort_at')
            ->values();

        return array_merge([
            'adminUser' => $this->adminUser($request),
            'pendingReviewCount' => $pendingReviewCount,
            'pendingPaymentCount' => $pendingPaymentCount,
            'totalNotificationCount' => $pendingReviewCount + $pendingPaymentCount,
            'notifications' => $notifications,
        ], $extra);
    }

    public function dashboard(Request $request): View
    {
        // Group in PHP so dashboard analytics work on SQLite and MySQL alike.
        $ownedBookingIds = Booking::whereIn('lapangan_id', $this->ownedCourtsQuery($request)->select('id'))->pluck('id');
        $paymentQuery = Payment::whereIn('booking_id', $ownedBookingIds);
        $revenuePerMonth = (clone $paymentQuery)->where('status', 'Lunas')->whereNotNull('paid_at')->get()->groupBy(fn ($item) => $item->paid_at?->format('m'))->map(fn ($items) => $items->sum('amount'));
        $bookingPerMonth = Booking::whereIn('lapangan_id', $this->ownedCourtsQuery($request)->select('id'))->get()->groupBy(fn ($item) => $item->booking_date ? date('m', strtotime($item->booking_date)) : null)->map(fn ($items) => $items->count());
        $userPerMonth = User::where('role', 'user')->get()->groupBy(fn ($item) => $item->created_at?->format('m'))->map(fn ($items) => $items->count());
        $months = collect(range(4, 0))->map(fn ($offset) => now()->subMonths($offset)->format('m'))->all();

        return view('admin.dashboard', $this->layoutData($request, [
            'stats' => [
                ['label' => 'Total Users', 'value' => User::where('role', 'user')->count()],
                ['label' => 'Total Lapangan', 'value' => $this->ownedCourtsQuery($request)->count()],
                ['label' => 'Total Pendapatan', 'value' => 'Rp ' . number_format((int) (clone $paymentQuery)->where('status', 'Lunas')->sum('amount'), 0, ',', '.')],
            ],
            'chartLabels' => array_map(fn($m) => now()->setMonth((int) $m)->translatedFormat('M'), $months),
            'chartUsers' => array_map(fn($m) => (int) ($userPerMonth[$m] ?? 0), $months),
            'chartLapangan' => array_map(fn($m) => (int) ($bookingPerMonth[$m] ?? 0), $months),
            'chartRevenue' => array_map(fn($m) => (int) ($revenuePerMonth[$m] ?? 0), $months),
            'userRatio' => [
                User::where('role', 'user')->where('status', 'Aktif')->count(),
                User::where('role', 'user')->where('status', '!=', 'Aktif')->count(),
            ],
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
            'role' => ['required', 'in:admin,user,superadmin'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

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
        if (in_array($user->role, ['admin', 'superadmin'], true) || $user->id === request()->session()->get('admin_user_id')) {
            return back()->with('error', 'Akun administrator atau akun yang sedang digunakan tidak dapat dihapus.');
        }
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    public function courts(Request $request): View
    {
        $courts = $this->ownedCourtsQuery($request)->latest()->get();
        return view('admin.courts', $this->layoutData($request, compact('courts')));
    }

    public function courtCreate(Request $request): View
    {
        return view('lapangan.create', $this->layoutData($request, ['venueAdmins' => User::where('role', 'admin')->where('status', 'Aktif')->orderBy('name')->get()]));
    }

    public function courtStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'harga' => ['required', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'status' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_gallery' => ['nullable', 'array'],
            'foto_gallery.*' => ['image', 'max:4096'],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['nullable', 'string', 'max:80'],
            'owner_admin_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->publicUpload($request, 'foto', 'courts');
        }

        $data['foto_gallery'] = $this->publicUploadMultiple($request, 'foto_gallery', 'courts');
        $data['fasilitas'] = $this->normalizeFacilities($request->input('fasilitas', []));

        $data['rating'] = $data['rating'] ?? 4.5;
        if (! $this->isSuperadmin($request)) {
            $data['owner_admin_id'] = $request->session()->get('admin_user_id');
        } else {
            $ownerId = $request->input('owner_admin_id');
            if ($ownerId && ! User::where('id', $ownerId)->where('role', 'admin')->exists()) {
                return back()->withInput()->withErrors(['owner_admin_id' => 'Pemilik venue harus akun admin venue aktif.']);
            }
            $data['owner_admin_id'] = $ownerId;
        }
        Lapangan::create($data);
        return redirect()->route('admin.courts')->with('success', 'Lapangan berhasil ditambahkan.');
    }

    public function courtEdit(Request $request, Lapangan $lapangan): View
    {
        $this->ensureCourtAccess($request, $lapangan);
        return view('lapangan.edit', $this->layoutData($request, ['court' => $lapangan, 'venueAdmins' => User::where('role', 'admin')->where('status', 'Aktif')->orderBy('name')->get()]));
    }

    public function courtUpdate(Request $request, Lapangan $lapangan): RedirectResponse
    {
        $this->ensureCourtAccess($request, $lapangan);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'harga' => ['required', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'status' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_gallery' => ['nullable', 'array'],
            'foto_gallery.*' => ['image', 'max:4096'],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['nullable', 'string', 'max:80'],
            'owner_admin_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($request->hasFile('foto')) {
            $this->deletePublicUpload($lapangan->foto);
            $data['foto'] = $this->publicUpload($request, 'foto', 'courts');
        }

        $existingGallery = $lapangan->foto_gallery ?? [];
        $newGallery = $this->publicUploadMultiple($request, 'foto_gallery', 'courts');
        $data['foto_gallery'] = array_values(array_filter(array_merge($existingGallery, $newGallery)));
        $data['fasilitas'] = $this->normalizeFacilities($request->input('fasilitas', []));

        if ($this->isSuperadmin($request) && $request->filled('owner_admin_id')) {
            $ownerId = $request->input('owner_admin_id');
            if ($ownerId && ! User::where('id', $ownerId)->where('role', 'admin')->exists()) {
                return back()->withInput()->withErrors(['owner_admin_id' => 'Pemilik venue harus akun admin venue aktif.']);
            }
            $data['owner_admin_id'] = $ownerId;
        }
        $lapangan->update($data);
        return redirect()->route('admin.courts')->with('success', 'Lapangan berhasil diperbarui.');
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
        $bookings = Booking::with(['user', 'lapangan'])->whereIn('lapangan_id', $this->ownedCourtsQuery($request)->select('id'))->latest()->get();
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
        $data['total_price'] = $court->harga * (int) $data['hours'];
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

    /** Single Superadmin workspace for both global Open Match and Event controls. */
    public function controlCenter(Request $request): View
    {
        abort_unless($this->isSuperadmin($request), 403);

        $matches = OpenMatch::with(['booking.user', 'booking.lapangan'])->latest()->get();
        $bookings = Booking::with(['user', 'lapangan'])
            ->whereIn('status', ['Lunas', 'DP'])
            ->latest()
            ->get();
        $events = Event::with('creator')->latest()->get();

        return view('admin.control-center', $this->layoutData($request, compact('matches', 'bookings', 'events')));
    }

    public function openMatches(Request $request): View
    {
        $matches = OpenMatch::with(['booking.user', 'booking.lapangan'])->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $this->ownedCourtsQuery($request)->select('id')))->latest()->get();
        $bookings = Booking::with(['user', 'lapangan'])
            ->whereIn('lapangan_id', $this->ownedCourtsQuery($request)->select('id'))
            ->whereIn('status', ['Lunas', 'DP'])
            ->latest()
            ->get();

        return view('admin.open-matches', $this->layoutData($request, compact('matches', 'bookings')));
    }

    public function openMatchStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'jumlah_pemain' => ['required', 'integer', 'min:1', 'max:99'],
            'jumlah_bergabung' => ['nullable', 'integer', 'min:0', 'max:99'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Open,Penuh,Selesai,Dibatalkan'],
        ]);

        if (!empty($data['booking_id'])) {
            $booking = Booking::findOrFail($data['booking_id']);
            $this->ensureBookingAccess($request, $booking);
        }
        $data['jumlah_bergabung'] = $data['jumlah_bergabung'] ?? 0;
        OpenMatch::create($data);

        return back()->with('success', 'Open match berhasil ditambahkan.');
    }

    public function openMatchUpdate(Request $request, OpenMatch $openMatch): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'jumlah_pemain' => ['required', 'integer', 'min:1', 'max:99'],
            'jumlah_bergabung' => ['nullable', 'integer', 'min:0', 'max:99'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Open,Penuh,Selesai,Dibatalkan'],
        ]);

        abort_unless($this->isSuperadmin($request) || (int) $openMatch->booking?->lapangan?->owner_admin_id === (int) $request->session()->get('admin_user_id'), 403);
        if (!empty($data['booking_id'])) { $this->ensureBookingAccess($request, Booking::findOrFail($data['booking_id'])); }
        $data['jumlah_bergabung'] = $data['jumlah_bergabung'] ?? 0;
        $openMatch->update($data);

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

    public function rewards(Request $request): View
    {
        $rewards = Reward::latest()->get();
        $redemptions = Redemption::with(['reward', 'user'])->latest()->get();
        return view('admin.rewards', $this->layoutData($request, compact('rewards', 'redemptions')));
    }

    public function rewardStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $this->publicUpload($request, 'image', 'rewards');
        }
        Reward::create($data);
        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function rewardEdit(Request $request, Reward $reward): View
    {
        return view('admin.reward-edit', $this->layoutData($request, compact('reward')));
    }

    public function rewardUpdate(Request $request, Reward $reward): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $this->deletePublicUpload($reward->image);
            $data['image'] = $this->publicUpload($request, 'image', 'rewards');
        }

        $reward->update($data);
        return redirect()->route('admin.rewards')->with('success', 'Reward berhasil diperbarui.');
    }

    public function rewardToggle(Reward $reward): RedirectResponse
    {
        $reward->update(['status' => $reward->status === 'Aktif' ? 'Nonaktif' : 'Aktif']);
        return back()->with('success', 'Status reward berhasil diperbarui.');
    }

    public function rewardDelete(Reward $reward): RedirectResponse
    {
        $this->deletePublicUpload($reward->image);
        $reward->delete();
        return back()->with('success', 'Reward berhasil dihapus.');
    }

    public function payments(Request $request): View
    {
        $payments = Payment::with(['user', 'booking.lapangan'])->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $this->ownedCourtsQuery($request)->select('id')))->latest()->get();
        return view('admin.payments', $this->layoutData($request, compact('payments')));
    }

    public function paymentVerify(Request $request, Payment $payment): RedirectResponse
    {
        // Keep the route for older views, but never allow manual confirmation.
        return back()->with('error', 'Verifikasi manual dinonaktifkan. Status lunas hanya ditetapkan oleh notifikasi Midtrans yang valid.');
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

    public function logoutConfirm(Request $request): View
    {
        return view('admin.logout-confirm', $this->layoutData($request));
    }

    public function logoutSuccess(Request $request): View
    {
        return view('admin.logout-success', $this->layoutData($request));
    }
}
