<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\VenueApplication;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VenueApplicationController extends BaseApiController
{
    private const ACTIVE_STATUSES = ['pending', 'revision', 'Menunggu', 'Perlu Revisi'];

    public function show(Request $request)
    {
        $user = $this->currentUser($request);

        $active = $this->activeApplication($user);
        $latest = $user->venueApplications()
            ->with(['documents', 'histories.actor'])
            ->latest()
            ->first();

        $application = $active ?: $latest;

        return $this->ok([
            'has_application' => $application !== null,
            'is_active' => $active !== null,
            'can_submit' => $active === null || in_array($active->status, ['revision', 'Perlu Revisi'], true),
            'application' => $application ? $this->applicationPayload($application) : null,
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->currentUser($request);

        $active = $this->activeApplication($user);
        $isRevision = $active && in_array($active->status, ['revision', 'Perlu Revisi'], true);

        if ($active && ! $isRevision) {
            return $this->fail('Pengajuan masih menunggu pemeriksaan Superadmin.', 422);
        }

        if ($user->role !== 'user') {
            return $this->fail('Pengajuan venue melalui aplikasi mobile hanya untuk akun pengguna.', 403);
        }

        $hasDocument = function (string $documentType) use ($active): bool {
            if (! $active) {
                return false;
            }

            return $active->documents()->where('document_type', $documentType)->exists();
        };

        $relationship = $request->input('applicant_relationship');
        $ownershipType = $request->input('ownership_type');
        $ownershipDocumentType = $ownershipType === 'Sewa' ? 'Perjanjian Sewa' : 'Bukti Kepemilikan';
        $needsAuthorization = in_array($relationship, ['manager', 'representative'], true);

        $data = $request->validate([
            'applicant_phone' => ['required', 'string', 'max:30'],
            'applicant_relationship' => ['required', 'in:owner,manager,representative'],
            'identity_number' => ['nullable', 'string', 'max:80'],
            'owner_address' => ['required', 'string', 'max:1000'],
            'accepted_terms' => ['accepted'],

            'company_name' => ['required', 'string', 'max:180'],
            'business_type' => ['required', 'string', 'max:80'],
            'nib' => ['nullable', 'string', 'max:100'],
            'company_address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_description' => ['nullable', 'string', 'max:3000'],

            'venue_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'description' => ['required', 'string', 'max:3000'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => ['required', 'in:Badminton,Futsal,Basketball,Tenis,Mini Soccer'],
            'ownership_type' => ['required', 'in:Milik Sendiri,Sewa'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'operating_hours' => ['required', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'facilities' => ['nullable', 'array', 'max:30'],
            'facilities.*' => ['string', 'max:80'],

            'identity_document' => [$hasDocument('Identitas Pemohon') ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'ownership_document' => [$ownershipType === 'Milik Sendiri' && ! $hasDocument($ownershipDocumentType) ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'lease_document' => [$ownershipType === 'Sewa' && ! $hasDocument($ownershipDocumentType) ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'authorization_document' => [$needsAuthorization && ! $hasDocument('Surat Kuasa/Penunjukan') ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'business_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'venue_exterior_photo' => [$hasDocument('Foto Tampak Depan') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'venue_interior_photo' => [$hasDocument('Foto Bagian Dalam') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'court_photos' => [$hasDocument('Foto Fasilitas Lapangan') ? 'nullable' : 'required', 'array', 'min:1', 'max:8'],
            'court_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'applicant_relationship.required' => 'Pilih hubungan kamu dengan usaha.',
            'accepted_terms.accepted' => 'Kamu harus menyetujui pernyataan tanggung jawab.',
            'authorization_document.required' => 'Surat kuasa atau surat penunjukan wajib dilampirkan.',
            'ownership_document.required' => 'Bukti kepemilikan wajib dilampirkan.',
            'lease_document.required' => 'Perjanjian sewa wajib dilampirkan.',
        ]);

        $application = DB::transaction(function () use ($request, $user, $data, $active, $isRevision) {
            $application = $active ?: new VenueApplication(['user_id' => $user->id]);
            $oldStatus = $active?->status;

            $application->fill([
                'user_id' => $user->id,
                'applicant_phone' => $data['applicant_phone'],
                'applicant_relationship' => $data['applicant_relationship'],
                'responsibility_agreed_at' => now(),
                'owner_address' => $data['owner_address'],
                'identity_number' => $data['identity_number'] ?? null,
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
                'authorization_document' => 'Surat Kuasa/Penunjukan',
                'business_license' => 'NIB atau Izin Usaha',
                'venue_exterior_photo' => 'Foto Tampak Depan',
                'venue_interior_photo' => 'Foto Bagian Dalam',
            ];

            foreach ($documentFields as $field => $label) {
                if ($request->hasFile($field)) {
                    $this->storeDocument($application, $label, $request->file($field));
                }
            }

            foreach (($request->file('court_photos') ?? []) as $photo) {
                $this->storeDocument($application, 'Foto Fasilitas Lapangan', $photo);
            }

            $application->histories()->create([
                'actor_id' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => 'pending',
                'note' => $isRevision
                    ? 'Pemohon mengirim perbaikan pengajuan.'
                    : 'Pengajuan dikirim melalui aplikasi mobile.',
            ]);

            return $application;
        });

        $user->notify(new ArenaUserNotification(
            'Pengajuan Admin Venue Dikirim',
            'Pengajuan venue berhasil dikirim dan sedang menunggu pemeriksaan Superadmin.',
            route('user.venue-application', [], false)
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

        $application->load(['documents', 'histories.actor']);

        return $this->ok([
            'application' => $this->applicationPayload($application),
        ], 'Pengajuan venue berhasil dikirim dan menunggu verifikasi Superadmin.', 201);
    }

    private function activeApplication(User $user): ?VenueApplication
    {
        return $user->venueApplications()
            ->with(['documents', 'histories.actor'])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->latest()
            ->first();
    }

    private function applicationPayload(VenueApplication $application): array
    {
        return [
            'id' => (int) $application->id,
            'status' => $application->status,
            'admin_note' => $application->admin_note,
            'venue_name' => $application->venue_name,
            'address' => $application->address,
            'contact_phone' => $application->contact_phone,
            'description' => $application->description,
            'sports' => $application->sports ?? [],
            'facilities' => $application->facilities ?? [],
            'ownership_type' => $application->ownership_type,
            'operating_hours' => $application->operating_hours,
            'google_maps_url' => $application->google_maps_url,
            'city' => $application->city,
            'province' => $application->province,
            'company_name' => $application->company_name,
            'business_type' => $application->business_type,
            'applicant_phone' => $application->applicant_phone,
            'applicant_relationship' => $application->applicant_relationship,
            'created_at' => $application->created_at?->toIso8601String(),
            'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            'documents' => $application->documents->map(fn ($document) => [
                'id' => (int) $document->id,
                'document_type' => $document->document_type,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'file_size' => (int) $document->file_size,
            ])->values()->all(),
            'histories' => $application->histories->map(fn ($history) => [
                'id' => (int) $history->id,
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'note' => $history->note,
                'actor_name' => $history->actor?->name,
                'created_at' => $history->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function storeDocument(VenueApplication $application, string $documentType, $file): void
    {
        $path = Storage::disk('local')->putFile('venue-applications/'.$application->id, $file);

        if (! $path) {
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
}
