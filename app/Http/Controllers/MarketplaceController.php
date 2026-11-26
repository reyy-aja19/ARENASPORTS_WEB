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
        $user = User::whereKey($request->session()->get('admin_user_id'))->whereIn('role', ['user', 'venue_admin'])->where('status', 'Aktif')->first();

        // Handle penyimpanan / reset koordinat lokasi GPS di session pengguna (tanpa terpampang di URL)
        if ($request->has('clear_location')) {
            $request->session()->forget(['user_lat', 'user_lng']);
            return redirect()->route('user.venues', $request->except(['clear_location', 'lat', 'lng']));
        }

        if ($request->filled('lat') && $request->filled('lng') && is_numeric($request->lat) && is_numeric($request->lng)) {
            $request->session()->put('user_lat', (float) $request->lat);
            $request->session()->put('user_lng', (float) $request->lng);
            // Redirect bersih tanpa memaparkan koordinat di parameter URL browser
            return redirect()->route('user.venues', $request->except(['lat', 'lng']));
        }

        $query = Venue::withCount([
            'lapangans' => function ($query) {
                $query->operational();
            }
        ])->where('status', 'Aktif');

        // Filter pencarian nama / kota / alamat
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('province', 'like', "%{$q}%");
            });
        }

        $venues = $query->latest()->get();

        // Cek koordinat GPS user dari session
        $sessLat = $request->session()->get('user_lat');
        $sessLng = $request->session()->get('user_lng');

        if (
            is_numeric($sessLat) &&
            is_numeric($sessLng)
        ) {
            $lat = (float) $sessLat;
            $lng = (float) $sessLng;

            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $venues = $venues->map(function ($venue) use ($lat, $lng) {
                    $venue->distance_km = null;

                    $vLat = $venue->latitude;
                    $vLng = $venue->longitude;

                    if (is_numeric($vLat) && is_numeric($vLng)) {
                        $vLat = (float) $vLat;
                        $vLng = (float) $vLng;

                        if ($vLat >= -90 && $vLat <= 90 && $vLng >= -180 && $vLng <= 180) {
                            $dLat = deg2rad($vLat - $lat);
                            $dLng = deg2rad($vLng - $lng);

                            $a = sin($dLat / 2) ** 2
                                + cos(deg2rad($lat))
                                * cos(deg2rad($vLat))
                                * sin($dLng / 2) ** 2;

                            $venue->distance_km = 6371 * 2 * atan2(
                                sqrt($a),
                                sqrt(max(0, 1 - $a))
                            );
                        }
                    }

                    return $venue;
                })->sortBy(function ($venue) {
                    return $venue->distance_km ?? INF;
                })->values();
            }
        }

        return view('user.venues', compact('user', 'venues'));
    }

    /**
     * Menampilkan detail venue dan lapangan yang tersedia secara publik.
     */
    public function venueDetail(Request $request, Venue $venue)
    {
        $user = User::whereKey($request->session()->get('admin_user_id'))->whereIn('role', ['user', 'venue_admin'])->where('status', 'Aktif')->first();

        abort_unless($venue->status === 'Aktif', 404);

        $venue->loadMissing('owner');

        $courts = $venue->lapangans()
            ->with(['priceRules', 'venue'])
            ->withCount(['bookings' => function ($query) {
                $query->whereIn('status', ['Lunas', 'Selesai']);
            }])
            ->withAvg(['reviews' => function ($query) {
                $query->where('is_visible', true);
            }], 'rating')
            ->orderBy('nama')
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

        abort_unless(in_array($user->role, ['venue_admin', 'superadmin'], true), 403);

        $adminUser = $user;

        if ($user->role === 'superadmin') {
            $venues = Venue::with('owner')->orderBy('name')->get();
            $venue = Venue::with('owner')
                ->when($request->integer('venue_id'), fn ($q, $id) => $q->whereKey($id))
                ->orderBy('id')
                ->firstOrFail();
        } else {
            $venue = $this->venue($user, $request->integer('venue_id') ?: null);
            $venues = $user->venues()->orderBy('name')->get();
        }

        return view('admin.venue-profile', compact('venue', 'venues', 'user', 'adminUser'));
    }

    /**
     * Memperbarui profil venue.
     */
    public function venueProfileUpdate(Request $request)
    {
        $user = $this->user($request);

        abort_unless(in_array($user->role, ['venue_admin', 'superadmin'], true), 403);

        if ($user->role === 'superadmin') {
            $venue = Venue::findOrFail($request->integer('venue_id'));
        } else {
            $venue = $this->venue($user, $request->integer('venue_id') ?: null);
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'required|string|max:1000',
            'contact_phone' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:3000',
            'google_maps_url' => 'nullable|url|max:500',
            'operating_hours' => 'nullable|string|max:150',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'operating_days.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'operational_status' => 'required|in:Beroperasi,Tutup Sementara,Tutup Permanen',
            'sports' => 'required|array|min:1',
            'sports.*' => 'in:Badminton,Futsal',
            'entity_type' => 'nullable|in:Badan Usaha,Perorangan',
            'company_name' => 'nullable|string|max:180',
            'business_type' => 'nullable|string|max:80',
            'nib' => 'nullable|string|max:100',
            'company_address' => 'nullable|string|max:1000',
            'company_phone' => 'nullable|string|max:30',
            'company_description' => 'nullable|string|max:3000',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'facilities' => 'nullable|array|max:30',
            'facilities.*' => 'string|max:80',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_files' => 'nullable|array|max:20',
            'gallery_files.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_gallery' => 'nullable|array',
            'remove_gallery.*' => 'string|max:500',
            'cover_photo' => 'nullable|string|max:500',
        ]);

        $gallery = collect(is_array($venue->gallery) ? $venue->gallery : [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->values()
            ->all();

        foreach (($data['remove_gallery'] ?? []) as $path) {
            $this->deleteVenueImage($path);
            $gallery = array_values(array_filter($gallery, fn ($item) => $item !== $path));

            if ($venue->photo === $path) {
                $data['photo'] = null;
            }
        }

        $coverPhoto = trim((string) ($data['cover_photo'] ?? ''));
        if ($coverPhoto !== '' && in_array($coverPhoto, $gallery, true)) {
            if ($venue->photo && $venue->photo !== $coverPhoto) {
                $gallery[] = $venue->photo;
            }

            $data['photo'] = $coverPhoto;
            $gallery = array_values(array_filter($gallery, fn ($item) => $item !== $coverPhoto));
        }

        if ($request->hasFile('photo')) {
            if ($venue->photo) {
                $gallery[] = $venue->photo;
            }

            $newPhotoPath = $request->file('photo')->store('venues', 'public');
            $data['photo'] = 'storage/' . $newPhotoPath;
        }

        foreach (($request->file('gallery_files') ?? []) as $file) {
            $gallery[] = 'storage/' . $file->store('venues', 'public');
        }

        unset($data['gallery_files'], $data['remove_gallery'], $data['cover_photo'], $data['photo_file']);
        $data['gallery'] = array_values(array_unique($gallery));

        if (!empty($data['opening_time']) && !empty($data['closing_time'])) {
            $data['operating_hours'] = $data['opening_time'] . ' – ' . $data['closing_time'];
        }

        $venue->update($data);

        return back()->with(
            'success',
            'Profil venue berhasil diperbarui.'
        );
    }

    private function deleteVenueImage(?string $path): void
    {
        $relative = $this->venueImageRelativePath($path);

        if ($relative !== null && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function venueImageRelativePath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'venues/')) {
            return $path;
        }

        return null;
    }

    /**
     * Menampilkan formulir pengajuan venue.
     */
    public function applicationForm(Request $request)
    {
        $user = $this->user($request);
        abort_unless(in_array($user->role, ['user', 'venue_admin'], true), 403);

        // Jika venue_admin mengakses dari link user biasa, arahkan ke rute admin panel
        if ($user->role === 'venue_admin' && !$request->routeIs('admin.*')) {
            return redirect()->route('admin.venue-register');
        }

        $isAdminFlow = $request->routeIs('admin.*') || $user->role === 'venue_admin';

        // Pengajuan aktif yang masih pending atau sedang revisi
        $activeApplication = $user->venueApplications()
            ->with(['documents', 'histories.actor'])
            ->whereIn('status', ['pending', 'revision', 'Menunggu', 'Perlu Revisi'])
            ->latest()
            ->first();

        // Pengajuan terakhir untuk referensi riwayat
        $latestApplication = $user->venueApplications()
            ->with(['documents', 'histories.actor'])
            ->latest()
            ->first();

        $application = $activeApplication ?: $latestApplication;
        $adminUser = $user;
        $layout = $isAdminFlow ? 'layouts.admin' : 'layouts.user';

        return view('user.venue-application', compact(
            'user',
            'adminUser',
            'application',
            'activeApplication',
            'latestApplication',
            'layout',
            'isAdminFlow'
        ));
    }

   
    public function applicationStore(Request $request)
    {
        $user = $this->user($request);

        abort_unless(
            in_array($user->role, ['user', 'venue_admin'], true),
            403
        );

        $activeApplication = $user->venueApplications()
            ->whereIn('status', [
                'pending',
                'revision',
                'Menunggu',
                'Perlu Revisi'
            ])
            ->latest()
            ->first();

        $isRevision = $activeApplication && in_array(
            $activeApplication->status,
            ['revision', 'Perlu Revisi'],
            true
        );

        if ($activeApplication && !$isRevision) {
            return back()->with(
                'error',
                'Pengajuan masih menunggu pemeriksaan Superadmin.'
            );
        }

        // Memeriksa apakah dokumen lama masih tersedia.
        $hasDocument = function (string $documentType) use ($activeApplication) {
            if (!$activeApplication) {
                return false;
            }

            return $activeApplication->documents()
                ->where('document_type', $documentType)
                ->exists();
        };

        $relationship = $request->input('applicant_relationship');
        $ownershipType = $request->input('ownership_type');

        $ownershipDocumentType = $ownershipType === 'Sewa'
            ? 'Perjanjian Sewa'
            : 'Bukti Kepemilikan';

        $needsAuthorization = in_array(
            $relationship,
            ['manager', 'representative'],
            true
        );

        $hasIdentity = $hasDocument('Identitas Pemohon');
        $hasOwnership = $hasDocument($ownershipDocumentType);
        $hasAuthorization = $hasDocument('Surat Kuasa/Penunjukan');
        $hasExterior = $hasDocument('Foto Tampak Depan');
        $hasInterior = $hasDocument('Foto Bagian Dalam');

        $data = $request->validate([
            // Identitas pemohon
            'applicant_phone' => 'required|string|max:30',
            'applicant_relationship' => 'required|in:owner,manager,representative',
            'identity_number' => 'nullable|string|max:80',
            'owner_address' => 'required|string|max:1000',

            // Pernyataan tanggung jawab
            'accepted_terms' => 'accepted',

            // Jenis usaha
            'entity_type' => 'required|in:Badan Usaha,Perorangan',
            'business_type' => 'required_if:entity_type,Badan Usaha|nullable|string|max:80',
            'company_name' => 'required_if:entity_type,Badan Usaha|nullable|string|max:180',
            'nib' => 'nullable|string|max:100',
            'company_address' => 'required_if:entity_type,Badan Usaha|nullable|string|max:1000',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'company_phone' => 'required_if:entity_type,Badan Usaha|nullable|string|max:30',
            'company_description' => 'nullable|string|max:3000',

            // Informasi venue
            'venue_name' => 'required|string|max:150',
            'address' => 'required|string|max:1000',
            'contact_phone' => 'required|string|max:30',
            'description' => 'required|string|max:3000',
            'sports' => 'required|array|min:1',
            'sports.*' => 'required|in:Badminton,Futsal',
            'ownership_type' => 'required|in:Milik Sendiri,Sewa',
            'google_maps_url' => 'nullable|url|max:500',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'operating_days.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'facilities' => 'nullable|array|max:30',
            'facilities.*' => 'string|max:80',

            // Dokumen identitas
            'identity_document' => [
                $hasIdentity ? 'nullable' : 'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],

            // Bukti kepemilikan atau perjanjian sewa
            'ownership_document' => [
                $ownershipType === 'Milik Sendiri' && !$hasOwnership
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],

            'lease_document' => [
                $ownershipType === 'Sewa' && !$hasOwnership
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],

            // Surat kuasa untuk pengelola/perwakilan
            'authorization_document' => [
                $needsAuthorization && !$hasAuthorization
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],

            'business_license' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],

            // Foto venue: hanya tampak depan dan bagian dalam.
            'venue_exterior_photo' => [
                $hasExterior ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],

            'venue_interior_photo' => [
                $hasInterior ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],
        ], [
            'entity_type.required' => 'Pilih jenis usaha terlebih dahulu.',
            'business_type.required_if' => 'Jenis badan usaha wajib diisi untuk perusahaan.',
            'company_name.required_if' => 'Nama perusahaan wajib diisi untuk badan usaha.',
            'company_address.required_if' => 'Alamat perusahaan wajib diisi untuk badan usaha.',
            'company_phone.required_if' => 'Telepon perusahaan wajib diisi untuk badan usaha.',
            'applicant_relationship.required' => 'Pilih hubungan kamu dengan usaha.',
            'applicant_relationship.in' => 'Hubungan pemohon tidak valid.',
            'accepted_terms.accepted' => 'Kamu harus menyetujui pernyataan tanggung jawab.',
            'authorization_document.required' => 'Surat kuasa atau surat penunjukan wajib dilampirkan.',
            'ownership_document.required' => 'Bukti kepemilikan wajib dilampirkan.',
            'lease_document.required' => 'Perjanjian sewa wajib dilampirkan.',
        ]);

        if (
            blank($data['google_maps_url'] ?? null)
            && (blank($data['latitude'] ?? null) || blank($data['longitude'] ?? null))
        ) {
            throw ValidationException::withMessages([
                'google_maps_url' => 'Isi link Google Maps atau titik koordinat venue.',
            ]);
        }

        $hasOpening = filled($data['opening_time'] ?? null);
        $hasClosing = filled($data['closing_time'] ?? null);

        if ($hasOpening !== $hasClosing) {
            throw ValidationException::withMessages([
                'opening_time' => 'Jam buka dan jam tutup harus diisi bersamaan.',
            ]);
        }

        DB::transaction(function () use (
            $user,
            $data,
            $activeApplication,
            $isRevision
        ) {
            $application = $activeApplication
                ?: new VenueApplication(['user_id' => $user->id]);

            $oldStatus = $activeApplication?->status;
            $entityType = $data['entity_type'];

            $application->fill([
                'user_id' => $user->id,
                'applicant_phone' => $data['applicant_phone'],
                'applicant_relationship' => $data['applicant_relationship'],
                'responsibility_agreed_at' => now(),

                'owner_address' => $data['owner_address'],
                'identity_number' => $data['identity_number'] ?? null,

                'entity_type' => $entityType,
                'company_name' => $data['company_name'] ?? null,
                'business_type' => $entityType === 'Perorangan'
                    ? 'Perorangan'
                    : ($data['business_type'] ?? null),
                'nib' => $data['nib'] ?? null,
                'company_address' => $data['company_address'] ?? null,
                'city' => $data['city'],
                'province' => $data['province'],
                'company_phone' => $data['company_phone'] ?? null,
                'company_description' => $data['company_description'] ?? null,

                'venue_name' => $data['venue_name'],
                'address' => $data['address'],
                'contact_phone' => $data['contact_phone'],
                'description' => $data['description'],
                'sports' => $data['sports'],
                'ownership_type' => $data['ownership_type'],
                'google_maps_url' => $data['google_maps_url'] ?? null,
                'opening_time' => $data['opening_time'] ?? null,
                'closing_time' => $data['closing_time'] ?? null,
                'operating_days' => !empty($data['operating_days'])
                    ? array_values(array_unique($data['operating_days']))
                    : null,
                'operating_hours' => ($data['opening_time'] ?? null) && ($data['closing_time'] ?? null)
                    ? $data['opening_time'] . ' – ' . $data['closing_time']
                    : null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'facilities' => array_values(
                    array_unique($data['facilities'] ?? [])
                ),

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
                'authorization_document' => 'Surat Kuasa/Penunjukan',
                'business_license' => 'NIB atau Izin Usaha',
                'venue_exterior_photo' => 'Foto Tampak Depan',
                'venue_interior_photo' => 'Foto Bagian Dalam',
            ];

            foreach ($documentFields as $field => $label) {
                if (!empty($data[$field])) {
                    $this->storeApplicationDocument(
                        $application,
                        $label,
                        $data[$field]
                    );
                }
            }

            $application->histories()->create([
                'actor_id' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => 'pending',
                'note' => $isRevision
                    ? 'Pemohon mengirim perbaikan pengajuan.'
                    : 'Pengajuan dikirim.',
            ]);
        });

        $targetRoute = ($request->routeIs('admin.*') || $user->role === 'venue_admin')
            ? 'admin.venue-register'
            : 'user.venue-application';

        $user->notify(new ArenaUserNotification(
            'Pengajuan Admin Venue Dikirim',
            'Pengajuan venue berhasil dikirim dan sedang menunggu pemeriksaan Superadmin.',
            route($targetRoute, [], false)
        ));

        User::where('role', 'superadmin')
            ->where('status', 'Aktif')
            ->get()
            ->each(function (User $superadmin) {
                $superadmin->notify(new ArenaUserNotification(
                    'Pengajuan Venue Baru',
                    'Ada pengajuan Admin Venue baru yang perlu diperiksa.',
                    route('admin.venue-applications', [], false)
                ));
            });

        return redirect()
            ->route($targetRoute)
            ->with(
                'success',
                'Pengajuan venue berhasil dikirim dan menunggu verifikasi Superadmin.'
            );
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
                    ->where('document_type', 'Foto Bagian Dalam')
                    ->get();
                foreach ($galleryDocuments as $galleryDocument) {
                    if (!Storage::disk('local')->exists($galleryDocument->file_path)) continue;
                    $galleryPath = Storage::disk('public')->putFile('venues', new \Illuminate\Http\File(Storage::disk('local')->path($galleryDocument->file_path)));
                    if ($galleryPath) $gallery[] = 'storage/' . $galleryPath;
                }

                $isPerorangan = $lockedApplication->entity_type === 'Perorangan'
                    || $lockedApplication->business_type === 'Perorangan';

                Venue::create([
                    'owner_admin_id' => $user->id,
                    'venue_application_id' => $lockedApplication->id,
                    'entity_type' => $lockedApplication->entity_type,
                    'company_name' => $isPerorangan ? null : $lockedApplication->company_name,
                    'name' => $lockedApplication->venue_name,
                    'slug' => $slug,
                    'address' => $lockedApplication->address,
                    'contact_phone' => $lockedApplication->contact_phone,
                    'description' => $lockedApplication->description,
                    'sports' => $lockedApplication->sports,
                    'facilities' => $lockedApplication->facilities,
                    'business_type' => $lockedApplication->business_type,
                    'nib' => $isPerorangan ? null : $lockedApplication->nib,
                    'company_address' => $isPerorangan ? null : $lockedApplication->company_address,
                    'company_phone' => $isPerorangan ? null : $lockedApplication->company_phone,
                    'company_description' => $isPerorangan ? null : $lockedApplication->company_description,
                    'photo' => $photo ? 'storage/' . $photo : null,
                    'gallery' => $gallery,
                    'google_maps_url' => $lockedApplication->google_maps_url,
                    'opening_time' => $lockedApplication->opening_time,
                    'closing_time' => $lockedApplication->closing_time,
                    'operating_days' => $lockedApplication->operating_days,
                    'operating_hours' => $lockedApplication->operating_hours,
                    'operational_status' => 'Beroperasi',
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
    
/**
 * Mengambil jadwal booking pada tanggal tertentu.
 */
public function availability(
    Request $request,
    Lapangan $lapangan
) {
    $lapangan->loadMissing(['venue', 'priceRules']);

    abort_unless(
        !$lapangan->venue_id || $lapangan->venue?->status === 'Aktif',
        404
    );

    $data = $request->validate([
        'date' => 'required|date|after_or_equal:today',
        'start_time' => 'nullable|date_format:H:i',
        'hours' => 'nullable|integer|min:1|max:8',
    ]);

    $bookings = Booking::where('lapangan_id', $lapangan->id)
        ->whereDate('booking_date', $data['date'])
        ->whereIn('status', [
            'Menunggu',
            'DP',
            'Lunas'
        ])
        ->get([
            'start_time',
            'end_time',
            'hours',
            'status'
        ]);

    $venue = $lapangan->venue;
    $pricing = app(\App\Services\CourtPricingService::class);
    $quote = null;

    if (!empty($data['start_time']) && !empty($data['hours'])) {
        $quote = $pricing->quote(
            $lapangan,
            $data['date'],
            $data['start_time'],
            (int) $data['hours']
        );
    }

    $isOperatingDay = $pricing->isOperatingDay($venue, $data['date']);
    $withinHours = empty($data['start_time'])
        ? true
        : $pricing->withinOperatingHours($venue, $data['start_time'], (int) ($data['hours'] ?? 1));

    return response()->json([
        'bookings' => $bookings,
        'opening' => $venue?->opening_time ? substr((string) $venue->opening_time, 0, 5) : '07:00',
        'closing' => $venue?->closing_time ? substr((string) $venue->closing_time, 0, 5) : '23:00',
        'operating_days' => $venue?->operating_days_list ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
        'operational_status' => $lapangan->operational_status ?: 'Beroperasi',
        'is_operating_day' => $isOperatingDay,
        'within_operating_hours' => $withinHours,
        'quote' => $quote,
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

        $withdrawal->loadMissing('user', 'venue');
        if ($withdrawal->user) {
            $withdrawal->user->notify(new ArenaUserNotification(
                'Status Penarikan Diperbarui',
                'Permintaan penarikan dana venue ' . ($withdrawal->venue?->name ?? '') .
                    ' sebesar Rp ' . number_format((int) $withdrawal->amount, 0, ',', '.') .
                    ' kini berstatus ' . $status . '.',
                route('admin.venue-finance', [], false)
            ));
        }

        return back()->with(
            'success',
            'Status penarikan diperbarui menjadi ' . $status . '.'
        );
    }
}