<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueApplication;
use App\Models\VenueApplicationDocument;
use App\Models\VenueWithdrawal;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketplaceController extends Controller
{
    /**
     * Mengambil user admin yang sedang login.
     */
    private function user(Request $request): User
    {
        return User::findOrFail(
            $request->session()->get('admin_user_id')
        );
    }

    /**
     * Mengambil venue milik admin.
     */
    private function venue(User $user, ?int $venueId = null): Venue
    {
        $query = Venue::where('owner_admin_id', $user->id);
        if ($venueId) $query->whereKey($venueId);
        return $query->orderBy('id')->firstOrFail();
    }

    /**
     * Status pembayaran yang dianggap sudah dibayar.
     */
    private function paidStatuses(): array
    {
        return [
            'Lunas',
            'settlement',
            'capture',
            'success',
            'paid'
        ];
    }

    /**
     * Menghitung saldo venue setelah komisi dan penarikan.
     */
    private function balance(Venue $venue): int
    {
        $gross = (int) Payment::whereIn(
            'status',
            $this->paidStatuses()
        )
            ->whereHas('booking.lapangan', function ($query) use ($venue) {
                $query->where('venue_id', $venue->id);
            })
            ->sum('amount');

        $commission = max(
            0,
            min(
                100,
                (float) config(
                    'arenasports.venue_commission_percent',
                    10
                )
            )
        );

        $net = (int) floor(
            $gross * (100 - $commission) / 100
        );

        $reserved = (int) $venue->withdrawals()
            ->whereIn('status', [
                'Menunggu',
                'Diproses',
                'Dibayar'
            ])
            ->sum('amount');

        return max(0, $net - $reserved);
    }

    /**
     * Menampilkan seluruh venue aktif kepada pengunjung maupun pengguna login.
     */
    public function venues(Request $request)
    {
        $user = User::whereKey($request->session()->get('admin_user_id'))->where('role', 'user')->where('status', 'Aktif')->first();

        $venues = Venue::withCount([
            'lapangans' => function ($query) {
                $query->where('status', 'Tersedia');
            }
        ])
            ->where('status', 'Aktif')
            ->latest()
            ->get();

        return view('user.venues', compact('user', 'venues'));
    }

    /**
     * Menampilkan detail venue dan lapangan yang tersedia secara publik.
     */
    public function venueDetail(Request $request, Venue $venue)
    {
        $user = User::whereKey($request->session()->get('admin_user_id'))->where('role', 'user')->where('status', 'Aktif')->first();

        abort_unless($venue->status === 'Aktif', 404);

        $courts = $venue->lapangans()
            ->where('status', 'Tersedia')
            ->latest()
            ->get();

        return view(
            'user.venue-detail',
            compact('user', 'venue', 'courts')
        );
    }

    /**
     * Menampilkan profil venue untuk Admin Venue.
     */
    public function venueProfile(Request $request)
    {
        $user = $this->user($request);

        abort_unless($user->role === 'venue_admin', 403);

        $adminUser = $user;
        $venue = $this->venue($user, $request->integer('venue_id') ?: null);
        $venues = $user->venues()->orderBy('name')->get();

        return view('admin.venue-profile', compact('venue', 'venues', 'user', 'adminUser'));
    }

    /**
     * Memperbarui profil venue.
     */
    public function venueProfileUpdate(Request $request)
    {
        $user = $this->user($request);

        abort_unless($user->role === 'venue_admin', 403);

        $venue = $this->venue($user, $request->integer('venue_id') ?: null);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:3000',
            'google_maps_url' => 'nullable|url|max:500',
            'operating_hours' => 'nullable|string|max:150',
            'sports' => 'required|array|min:1',
            'sports.*' => 'in:Badminton,Futsal,Basketball,Tenis,Mini Soccer',
            'company_name' => 'nullable|string|max:180',
            'business_type' => 'nullable|string|max:80',
            'nib' => 'nullable|string|max:100',
            'company_address' => 'nullable|string|max:1000',
            'company_phone' => 'nullable|string|max:30',
            'company_description' => 'nullable|string|max:3000',
            'facilities' => 'nullable|array|max:30',
            'facilities.*' => 'string|max:80',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Simpan foto venue ke storage/app/public/venues.
        // Foto lama baru dihapus setelah foto baru berhasil disimpan.
        if ($request->hasFile('photo')) {
            $newPhotoPath = $request->file('photo')->store('venues', 'public');

            $oldPhotoPath = ltrim((string) $venue->photo, '/');
            if (str_starts_with($oldPhotoPath, 'storage/')) {
                $oldPhotoPath = substr($oldPhotoPath, strlen('storage/'));
            }

            if ($oldPhotoPath !== '' && Storage::disk('public')->exists($oldPhotoPath)) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            // Konsisten dengan proses persetujuan pengajuan venue yang menyimpan prefix storage/.
            $data['photo'] = 'storage/' . $newPhotoPath;
        }

        unset($data['photo']); // field file tidak pernah dikirim sebagai object ke model
        if (isset($newPhotoPath)) {
            $data['photo'] = 'storage/' . $newPhotoPath;
        }

        $venue->update($data);

        return back()->with(
            'success',
            'Profil venue berhasil diperbarui.'
        );
    }

    /**
     * Menampilkan formulir pengajuan venue.
     */
    public function applicationForm(Request $request)
    {
        $user = $this->user($request);
        abort_unless(in_array($user->role, ['user', 'venue_admin'], true), 403);

        $application = $user->venueApplications()
            ->with(['documents', 'histories.actor'])
            ->latest()
            ->first();

        return view('user.venue-application', compact('user', 'application'));
    }

    public function applicationStore(Request $request)
    {
        $user = $this->user($request);
        abort_unless(in_array($user->role, ['user', 'venue_admin'], true), 403);

        $activeApplication = $user->venueApplications()
            ->whereIn('status', ['pending', 'revision', 'Menunggu', 'Perlu Revisi'])
            ->latest()->first();
        if ($activeApplication && $activeApplication->status !== 'revision' && $activeApplication->status !== 'Perlu Revisi') {
            return back()->with('error', 'Pengajuan masih menunggu pemeriksaan Superadmin.');
        }

        $data = $request->validate([
            'owner_address' => 'required|string|max:1000',
            'identity_number' => 'nullable|string|max:80',
            'applicant_phone' => 'required|string|max:30',
            'company_name' => 'required|string|max:180',
            'business_type' => 'required|string|max:80',
            'nib' => 'nullable|string|max:100',
            'company_address' => 'required|string|max:1000',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'company_phone' => 'required|string|max:30',
            'company_description' => 'nullable|string|max:3000',
            'venue_name' => 'required|string|max:150',
            'address' => 'required|string|max:1000',
            'contact_phone' => 'required|string|max:30',
            'description' => 'required|string|max:3000',
            'sports' => 'required|array|min:1',
            'sports.*' => 'required|in:Badminton,Futsal,Basketball,Tenis,Mini Soccer',
            'ownership_type' => 'required|in:Milik Sendiri,Sewa',
            'google_maps_url' => 'nullable|url|max:500',
            'operating_hours' => 'required|string|max:150',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'facilities' => 'nullable|array|max:30',
            'facilities.*' => 'string|max:80',
            'identity_document' => [$activeApplication ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'ownership_document' => 'required_if:ownership_type,Milik Sendiri|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'lease_document' => 'required_if:ownership_type,Sewa|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'business_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'venue_exterior_photo' => [$activeApplication ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'venue_interior_photo' => [$activeApplication ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'court_photos' => [$activeApplication ? 'nullable' : 'required', 'array', 'min:1', 'max:8'],
            'court_photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        DB::transaction(function () use ($user, $data, $activeApplication) {
            $application = $activeApplication ?: new VenueApplication(['user_id' => $user->id]);
            $oldStatus = $activeApplication?->status;
            $application->fill([
                'user_id' => $user->id,
                'owner_address' => $data['owner_address'],
                'identity_number' => $data['identity_number'] ?? null,
                'applicant_phone' => $data['applicant_phone'],
                'company_name' => $data['company_name'],
                'business_type' => $data['business_type'],
                'nib' => $data['nib'] ?? null,
                'company_address' => $data['company_address'],
                'city' => $data['city'],
                'province' => $data['province'],
                'company_phone' => $data['company_phone'],
                'company_description' => $data['company_description'] ?? null,
                'venue_name' => $data['venue_name'],
                'address' => $data['address'],
                'contact_phone' => $data['contact_phone'],
                'description' => $data['description'],
                'sports' => $data['sports'],
                'ownership_type' => $data['ownership_type'],
                'google_maps_url' => $data['google_maps_url'] ?? null,
                'operating_hours' => $data['operating_hours'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'facilities' => array_values(array_unique($data['facilities'] ?? [])),
                'status' => 'pending',
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            $application->save();

            $documentFields = [
                'identity_document' => 'Identitas Pemohon',
                'ownership_document' => 'Bukti Kepemilikan',
                'lease_document' => 'Perjanjian Sewa',
                'business_license' => 'NIB atau Izin Usaha',
                'venue_exterior_photo' => 'Foto Tampak Depan',
                'venue_interior_photo' => 'Foto Bagian Dalam',
            ];
            foreach ($documentFields as $field => $label) {
                if (!empty($data[$field])) {
                    $this->storeApplicationDocument($application, $label, $data[$field]);
                }
            }
            foreach (($data['court_photos'] ?? []) as $photo) {
                $this->storeApplicationDocument($application, 'Foto Fasilitas Lapangan', $photo);
            }
            $application->histories()->create([
                'actor_id' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => 'pending',
                'note' => $oldStatus === 'revision' ? 'Pemohon mengirim perbaikan pengajuan.' : 'Pengajuan dikirim.',
            ]);
        });

        $user->notify(new ArenaUserNotification(
            'Pengajuan Admin Venue Dikirim',
            'Pengajuan venue berhasil dikirim dan sedang menunggu pemeriksaan Superadmin.',
            route('user.venue-application', [], false)
        ));
        User::where('role', 'superadmin')->where('status', 'Aktif')->get()->each(function (User $superadmin) {
            $superadmin->notify(new ArenaUserNotification(
                'Pengajuan Venue Baru',
                'Ada pengajuan Admin Venue baru yang perlu diperiksa.',
                route('admin.venue-applications', [], false)
            ));
        });

        return redirect()->route('user.venue-application')->with('success', 'Pengajuan berhasil dikirim dan menunggu verifikasi Superadmin.');
    }

    private function storeApplicationDocument(
        VenueApplication $application,
        string $documentType,
        $file
    ): void {
        $path = Storage::disk('local')->putFile(
            'venue-applications/' . $application->id,
            $file
        );

        if (!$path) {
            throw new \RuntimeException('Dokumen gagal disimpan.');
        }

        $application->documents()->create([
            'document_type' => $documentType,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Mengunduh dokumen pengajuan. Hanya Superadmin yang dapat mengaksesnya.
     */
    public function applicationDocument(
        Request $request,
        VenueApplication $application,
        VenueApplicationDocument $document
    ) {
        abort_unless(
            $request->session()->get('admin_role') === 'superadmin',
            403
        );

        abort_unless(
            (int) $document->venue_application_id === (int) $application->id,
            404
        );

        abort_unless(
            Storage::disk('local')->exists($document->file_path),
            404
        );

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path)
        );
    }

    public function applicationDocumentPreview(Request $request, VenueApplication $application, VenueApplicationDocument $document)
    {
        abort_unless($request->session()->get('admin_role') === 'superadmin', 403);
        abort_unless((int) $document->venue_application_id === (int) $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $mime = strtolower((string) $document->mime_type);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true), 415);
        return response()->file(Storage::disk('local')->path($document->file_path), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function applications(Request $request)
    {
        abort_unless($request->session()->get('admin_role') === 'superadmin', 403);
        $query = VenueApplication::with(['user', 'documents', 'histories.actor']);
        if ($request->filled('status') && in_array($request->query('status'), ['pending','revision','approved','rejected'], true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('venue_name', 'like', '%' . $term . '%')
                    ->orWhere('company_name', 'like', '%' . $term . '%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $term . '%'));
            });
        }
        $applications = $query->latest()->paginate(15)->withQueryString();
        $adminUser = $this->user($request);
        return view('admin.venue-applications', compact('applications', 'adminUser'));
    }

    public function approveApplication(Request $request, VenueApplication $application)
    {
        abort_unless($request->session()->get('admin_role') === 'superadmin', 403);
        $data = $request->validate(['admin_note' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($application, $request, $data) {
            $lockedApplication = VenueApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            if (!in_array($lockedApplication->status, ['pending', 'revision', 'Menunggu', 'Perlu Revisi'], true)) {
                throw ValidationException::withMessages(['application' => 'Pengajuan ini sudah diproses.']);
            }
            $user = User::lockForUpdate()->findOrFail($lockedApplication->user_id);
            $existingVenue = Venue::where('venue_application_id', $lockedApplication->id)->first();
            if (!$existingVenue) {
                $baseSlug = Str::slug($lockedApplication->venue_name) ?: 'venue';
                $slug = $baseSlug;
                $suffix = 1;
                while (Venue::where('slug', $slug)->exists()) $slug = $baseSlug . '-' . $suffix++;
                $photoDocument = $lockedApplication->documents()->where('document_type', 'Foto Tampak Depan')->latest()->first();
                $photo = null;
                if ($photoDocument && Storage::disk('local')->exists($photoDocument->file_path)) {
                    $source = Storage::disk('local')->path($photoDocument->file_path);
                    $photo = Storage::disk('public')->putFile('venues', new \Illuminate\Http\File($source));
                }
                $gallery = [];
                $galleryDocuments = $lockedApplication->documents()
                    ->whereIn('document_type', ['Foto Bagian Dalam', 'Foto Fasilitas Lapangan'])
                    ->get();
                foreach ($galleryDocuments as $galleryDocument) {
                    if (!Storage::disk('local')->exists($galleryDocument->file_path)) continue;
                    $galleryPath = Storage::disk('public')->putFile('venues', new \Illuminate\Http\File(Storage::disk('local')->path($galleryDocument->file_path)));
                    if ($galleryPath) $gallery[] = 'storage/' . $galleryPath;
                }
                Venue::create([
                    'owner_admin_id' => $user->id,
                    'venue_application_id' => $lockedApplication->id,
                    'company_name' => $lockedApplication->company_name,
                    'name' => $lockedApplication->venue_name,
                    'slug' => $slug,
                    'address' => $lockedApplication->address,
                    'contact_phone' => $lockedApplication->contact_phone,
                    'description' => $lockedApplication->description,
                    'sports' => $lockedApplication->sports,
                    'facilities' => $lockedApplication->facilities,
                    'business_type' => $lockedApplication->business_type,
                    'nib' => $lockedApplication->nib,
                    'company_address' => $lockedApplication->company_address,
                    'company_phone' => $lockedApplication->company_phone,
                    'company_description' => $lockedApplication->company_description,
                    'photo' => $photo ? 'storage/' . $photo : null,
                    'gallery' => $gallery,
                    'google_maps_url' => $lockedApplication->google_maps_url,
                    'operating_hours' => $lockedApplication->operating_hours,
                    'city' => $lockedApplication->city,
                    'province' => $lockedApplication->province,
                    'latitude' => $lockedApplication->latitude,
                    'longitude' => $lockedApplication->longitude,
                    'status' => 'Aktif',
                ]);
            }
            if ($user->role === 'user') $user->update(['role' => 'venue_admin']);
            $lockedApplication->update([
                'status' => 'approved',
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => $request->session()->get('admin_user_id'),
                'reviewed_at' => now(),
            ]);
            $lockedApplication->histories()->create([
                'actor_id' => $request->session()->get('admin_user_id'),
                'from_status' => $application->status,
                'to_status' => 'approved',
                'note' => $data['admin_note'] ?? 'Pengajuan disetujui.',
            ]);
            $user->notify(new ArenaUserNotification('Pengajuan Venue Disetujui', 'Pengajuan venue kamu telah disetujui. Akses Admin Venue sekarang aktif.', '/admin/dashboard'));
        }, 3);

        return back()->with('success', 'Pengajuan disetujui. Venue telah diaktifkan dan akun pemilik memiliki akses Admin Venue.');
    }

    public function requestRevision(Request $request, VenueApplication $application)
    {
        abort_unless($request->session()->get('admin_role') === 'superadmin', 403);
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);
        DB::transaction(function () use ($application, $request, $data) {
            $locked = VenueApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            if (!in_array($locked->status, ['pending', 'revision'], true)) {
                throw ValidationException::withMessages(['application' => 'Pengajuan tidak dapat diminta revisi pada status saat ini.']);
            }
            $old = $locked->status;
            $locked->update(['status' => 'revision', 'admin_note' => $data['admin_note'], 'reviewed_by' => $request->session()->get('admin_user_id'), 'reviewed_at' => now()]);
            $locked->histories()->create(['actor_id' => $request->session()->get('admin_user_id'), 'from_status' => $old, 'to_status' => 'revision', 'note' => $data['admin_note']]);
            $locked->user?->notify(new ArenaUserNotification('Pengajuan Venue Perlu Revisi', $data['admin_note'], route('user.venue-application', [], false)));
        }, 3);
        return back()->with('success', 'Permintaan revisi berhasil dikirim kepada pemohon.');
    }

    public function rejectApplication(Request $request, VenueApplication $application)
    {
        abort_unless($request->session()->get('admin_role') === 'superadmin', 403);
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);
        DB::transaction(function () use ($application, $request, $data) {
            $locked = VenueApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            if (!in_array($locked->status, ['pending', 'revision'], true)) {
                throw ValidationException::withMessages(['application' => 'Pengajuan ini sudah diproses.']);
            }
            $old = $locked->status;
            $locked->update(['status' => 'rejected', 'admin_note' => $data['admin_note'], 'reviewed_by' => $request->session()->get('admin_user_id'), 'reviewed_at' => now()]);
            $locked->histories()->create(['actor_id' => $request->session()->get('admin_user_id'), 'from_status' => $old, 'to_status' => 'rejected', 'note' => $data['admin_note']]);
            $locked->user?->notify(new ArenaUserNotification('Pengajuan Venue Ditolak', $data['admin_note'], route('user.venue-application', [], false)));
        }, 3);
        return back()->with('success', 'Pengajuan ditolak dan alasan telah dikirim kepada pemohon.');
    }

    /**
     * Mengambil jadwal booking pada tanggal tertentu.
     */
    public function availability(
        Request $request,
        Lapangan $lapangan
    ) {
        $lapangan->loadMissing('venue');
        abort_unless(!$lapangan->venue_id || $lapangan->venue?->status === 'Aktif', 404);

        $data = $request->validate([
            'date' => 'required|date|after_or_equal:today'
        ]);

        $bookings = Booking::where(
            'lapangan_id',
            $lapangan->id
        )
            ->whereDate('booking_date', $data['date'])
            ->whereIn('status', [
                'Menunggu',
                'DP',
                'Lunas',
                'Selesai'
            ])
            ->get([
                'start_time',
                'end_time',
                'hours',
                'status'
            ]);

        return response()->json([
            'bookings' => $bookings,
            'opening' => '07:00',
            'closing' => '23:00'
        ]);
    }

    /**
     * Menampilkan laporan keuangan Admin Venue.
     */
    public function finance(Request $request)
    {
        $user = $this->user($request);

        abort_unless($user->role === 'venue_admin', 403);

        $adminUser = $user;
        $venues = $user->venues()->orderBy('name')->get();
        $venue = $this->venue($user, $request->integer('venue_id') ?: null);

        $balance = $this->balance($venue);

        $gross = (int) Payment::whereIn(
            'status',
            $this->paidStatuses()
        )
            ->whereHas('booking.lapangan', function ($query) use ($venue) {
                $query->where('venue_id', $venue->id);
            })
            ->sum('amount');

        $withdrawals = $venue->withdrawals()
            ->latest()
            ->get();

        return view(
            'admin.venue-finance',
            compact(
                'user',
                'adminUser',
                'venue',
                'venues',
                'balance',
                'gross',
                'withdrawals'
            )
        );
    }

    /**
     * Mengajukan penarikan saldo venue.
     */
    public function requestWithdrawal(Request $request)
    {
        $user = $this->user($request);

        abort_unless($user->role === 'venue_admin', 403);

        $data = $request->validate([
            'venue_id' => 'required|integer',
            'amount' => 'required|integer|min:10000',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:80',
            'account_holder' => 'required|string|max:160'
        ]);

        $venue = Venue::where('owner_admin_id', $user->id)->findOrFail($data['venue_id']);

        DB::transaction(function () use ($venue, $user, $data) {

            $locked = Venue::where('owner_admin_id', $user->id)->lockForUpdate()
                ->findOrFail($venue->id);

            $available = $this->balance($locked);

            if ($data['amount'] > $available) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Saldo tersedia tidak mencukupi. Saldo saat ini Rp ' .
                        number_format($available, 0, ',', '.')
                ]);
            }

            VenueWithdrawal::create([
                'venue_id' => $locked->id,
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_holder' => $data['account_holder'],
                'status' => 'Menunggu'
            ]);
        });

        return back()->with(
            'success',
            'Permintaan penarikan berhasil dikirim. Dana akan diproses oleh Superadmin.'
        );
    }

    /**
     * Menampilkan seluruh permintaan penarikan untuk Superadmin.
     */
    public function withdrawals(Request $request)
    {
        abort_unless(
            $request->session()->get('admin_role') === 'superadmin',
            403
        );

        $withdrawals = VenueWithdrawal::with([
            'venue',
            'user'
        ])
            ->latest()
            ->get();

        $adminUser = $this->user($request);

        return view(
            'admin.venue-withdrawals',
            compact('withdrawals', 'adminUser')
        );
    }

    /**
     * Memproses status permintaan penarikan.
     */
    public function processWithdrawal(
        Request $request,
        VenueWithdrawal $withdrawal
    ) {
        abort_unless(
            $request->session()->get('admin_role') === 'superadmin',
            403
        );

        $data = $request->validate([
            'action' => 'required|in:process,paid,reject',
            'admin_note' => 'nullable|string|max:1000'
        ]);

        $status = [
            'process' => 'Diproses',
            'paid' => 'Dibayar',
            'reject' => 'Ditolak'
        ][$data['action']];

        if (in_array($withdrawal->status, [
            'Dibayar',
            'Ditolak'
        ])) {
            return back()->with(
                'error',
                'Penarikan ini sudah final.'
            );
        }

        if (
            $data['action'] === 'paid' &&
            $withdrawal->status !== 'Diproses'
        ) {
            return back()->with(
                'error',
                'Proses penarikan harus disetujui terlebih dahulu.'
            );
        }

        $withdrawal->update([
            'status' => $status,
            'admin_note' => $data['admin_note']
                ?? $withdrawal->admin_note,
            'processed_by' => $request->session()->get('admin_user_id'),
            'processed_at' => now()
        ]);

        return back()->with(
            'success',
            'Status penarikan diperbarui menjadi ' . $status . '.'
        );
    }
}