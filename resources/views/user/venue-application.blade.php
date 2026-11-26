
@php
    $layout = $layout ?? (request()->routeIs('admin.*') || session('admin_role') === 'venue_admin' ? 'layouts.admin' : 'layouts.user');
@endphp
@extends($layout, [
    'title' => 'Daftarkan Venue Baru',
    'heading' => 'Daftarkan Venue Baru'
])

@section('content')
@php
    $revisionStatuses = ['revision', 'Perlu Revisi'];
    $rejectedStatuses = ['rejected', 'Ditolak'];
    $approvedStatuses = ['approved', 'Disetujui'];

    $isVenueAdmin = ($user->role === 'venue_admin') || (session('admin_role') === 'venue_admin');
    $isAdminFlow = $isAdminFlow ?? ($isVenueAdmin || request()->routeIs('admin.*'));

    // Pengajuan aktif yang sedang diverifikasi atau perlu revisi
    $hasPendingApplication = $activeApplication && in_array($activeApplication->status, ['pending', 'Menunggu'], true);
    $isRevision = $activeApplication && in_array($activeApplication->status, $revisionStatuses, true);

    // Jika venue_admin dan tidak ada pengajuan pending, mereka boleh daftarkan venue baru
    if ($isVenueAdmin) {
        $canSubmit = !$hasPendingApplication;
        $isNewVenueBranch = !$isRevision;
    } else {
        $canSubmit = !$application ||
            in_array($application->status, array_merge($revisionStatuses, $rejectedStatuses), true);
        $isNewVenueBranch = false;
    }

    // Dokumen tersimpan hanya berlaku saat mode revisi
    $existingDocuments = ($isRevision && $activeApplication) ? $activeApplication->documents : collect();

    $hasIdentityDocument = $isRevision &&
        $existingDocuments->contains('document_type', 'Identitas Pemohon');

    $hasOwnershipDocument = $isRevision &&
        $existingDocuments->contains('document_type', 'Bukti Kepemilikan');

    $hasLeaseDocument = $isRevision &&
        $existingDocuments->contains('document_type', 'Perjanjian Sewa');

    $hasAuthorizationDocument = $isRevision &&
        $existingDocuments->contains('document_type', 'Surat Kuasa/Penunjukan');

    $hasExteriorPhoto = $isRevision &&
        $existingDocuments->contains('document_type', 'Foto Tampak Depan');

    $hasInteriorPhoto = $isRevision &&
        $existingDocuments->contains('document_type', 'Foto Bagian Dalam');

    // Sumber data prefill
    $sourceApp = $isRevision ? $activeApplication : ($isNewVenueBranch ? null : $application);
    $companySource = $isRevision ? $activeApplication : ($latestApplication ?? $application);

    $selectedOwnership = old(
        'ownership_type',
        $sourceApp->ownership_type ?? ''
    );

    $selectedEntityType = old(
        'entity_type',
        $sourceApp->entity_type ?? $companySource->entity_type ?? ''
    );

    $selectedBusinessType = old(
        'business_type',
        $sourceApp->business_type ?? $companySource->business_type ?? ''
    );

    $selectedOperatingDays = old(
        'operating_days',
        $sourceApp->operating_days ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']
    );

    if (!is_array($selectedOperatingDays)) {
        $selectedOperatingDays = [];
    }

    $openingTime = old(
        'opening_time',
        $sourceApp?->opening_time ? substr((string) $sourceApp->opening_time, 0, 5) : ''
    );

    $closingTime = old(
        'closing_time',
        $sourceApp?->closing_time ? substr((string) $sourceApp->closing_time, 0, 5) : ''
    );

    $facilityValues = old('facilities', $sourceApp->facilities ?? [
        'Parkir motor',
        'Parkir mobil',
        'Toilet',
        'Musholla',
        'Ruang ganti',
        'Ruang tunggu',
        'Tribun',
        'Lampu malam',
        'WiFi',
        'Kantin',
        'Penyewaan perlengkapan'
    ]);

    $sportsValues = old('sports', $sourceApp->sports ?? []);

    if (!is_array($facilityValues)) {
        $facilityValues = [];
    }

    if (!is_array($sportsValues)) {
        $sportsValues = [];
    }
@endphp

<style>
    .va-wrap{max-width:980px;margin:0 auto;padding-bottom:36px}
    .va-head{margin-bottom:22px}
    .va-head h2{margin:0 0 7px;font-size:27px;font-weight:800}
    .va-head p,.va-muted{color:#64748b}
    .va-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:22px;margin-bottom:18px}
    .va-section-title{font-size:17px;font-weight:800;margin:0 0 16px;color:#16352e}
    .va-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
    .va-field{display:flex;flex-direction:column;gap:7px}
    .va-field.full{grid-column:1/-1}
    .va-field label{font-size:13px;font-weight:700;color:#334155}
    .va-field input,.va-field textarea,.va-field select{width:100%;border:1px solid #d5dee7;border-radius:10px;padding:11px 12px;background:#fff;color:#1e293b}
    .va-field input[type=file]{padding:9px;background:#f8fafc}
    .va-field small{font-size:11px;color:#64748b;line-height:1.5}
    .va-sports{display:flex;gap:13px;flex-wrap:wrap}
    .va-sports label{font-weight:500;display:flex;align-items:center;gap:6px}
    .va-alert{padding:15px;border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;margin-bottom:18px}
    .va-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:13px;border-radius:10px;margin-bottom:16px}
    .va-docs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .va-status{display:inline-block;padding:6px 10px;border-radius:999px;background:#fff7df;color:#8a5b00;font-size:12px;font-weight:800}

    .va-facility-list{
        display:flex;
        flex-direction:column;
        gap:10px;
        margin-top:12px;
    }

    .va-facility-row{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .va-facility-row input{
        flex:1;
        min-width:0;
    }

    .va-remove-facility{
        border:none;
        background:#fee2e2;
        color:#dc2626;
        padding:11px 14px;
        border-radius:10px;
        cursor:pointer;
    }

    .va-add-facility{
        margin-top:12px;
        padding:10px 15px;
        border:1px dashed #16352e;
        border-radius:10px;
        background:#f0fdf4;
        color:#16352e;
        font-weight:700;
        cursor:pointer;
    }

    .va-add-facility:hover{
        background:#dcfce7;
    }

    .va-scan-preview{
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        margin-top:10px;
    }

    .va-scan-item{
        width:130px;
        padding:9px;
        border:1px solid #e2e8f0;
        border-radius:10px;
        background:#f8fafc;
        overflow:hidden;
        font-size:12px;
    }

    .va-scan-item img{
        width:100%;
        height:90px;
        object-fit:cover;
        border-radius:7px;
        margin-bottom:7px;
    }

    .va-scan-item .va-file-icon{
        height:90px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:35px;
        color:#dc2626;
    }

    .va-scan-name{
        overflow-wrap:anywhere;
        font-weight:600;
    }

    .va-scan-item small{
        display:block;
        margin-top:4px;
    }

    .va-required-note{
        color:#dc2626;
    }

    .va-existing-docs{
        background:#f8fafc;
        border:1px solid #e2e8f0;
        border-radius:10px;
        padding:12px 15px;
        margin-bottom:15px;
    }

    .va-existing-docs ul{
        margin:8px 0 0 18px;
        padding:0;
    }

    .va-existing-docs li{
        margin-bottom:5px;
        font-size:12px;
        color:#475569;
    }

    .va-terms{
        display:flex;
        align-items:flex-start;
        gap:10px;
        margin-top:16px;
        font-size:13px;
        line-height:1.6;
        color:#334155;
    }

    .va-terms input{
        width:17px;
        height:17px;
        flex-shrink:0;
        margin-top:2px;
    }

    @media(max-width:650px){
        .va-grid,.va-docs{grid-template-columns:1fr}
        .va-card{padding:16px}
    }
</style>

<div class="va-wrap">

    @if(!($isAdminFlow ?? false))
    <div class="va-head">
        <h2>Pengajuan Admin Venue</h2>
        <p>
            Lengkapi informasi usaha dan dokumen pendukung.
            Superadmin akan memeriksa kelayakan venue sebelum memberikan akses Admin Venue.
        </p>
    </div>
    @endif

    @if(session('success'))
        <div class="va-alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="va-error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="va-error">
            <strong>Periksa kembali formulir:</strong>
            <ul style="margin:8px 0 0 18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STATUS PENGAJUAN --}}
    @if($activeApplication)
        <article class="va-card">
            <h3 class="va-section-title">
                {{ $isVenueAdmin ? 'Status Pengajuan Cabang/Venue Baru' : 'Status Pengajuan Terakhir' }}
            </h3>

            <span class="va-status">
                {{
                    [
                        'pending' => 'Menunggu Verifikasi',
                        'revision' => 'Perlu Revisi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'Perlu Revisi' => 'Perlu Revisi',
                        'Disetujui' => 'Disetujui',
                        'Ditolak' => 'Ditolak'
                    ][$activeApplication->status] ?? $activeApplication->status
                }}
            </span>

            <p><strong>{{ $activeApplication->venue_name }}</strong></p>
            <p class="va-muted">{{ $activeApplication->address }}</p>

            @if($activeApplication->admin_note)
                <p>
                    <strong>Catatan Superadmin:</strong>
                    {{ $activeApplication->admin_note }}
                </p>
            @endif

            @if($activeApplication->documents->isNotEmpty())
                <div class="va-existing-docs">
                    <strong>Dokumen yang sudah tersimpan:</strong>
                    <ul>
                        @foreach($activeApplication->documents as $document)
                            <li>
                                {{ $document->document_type }} —
                                {{ $document->original_name }}
                            </li>
                        @endforeach
                    </ul>
                    @if($isRevision)
                        <small>
                            Dokumen yang sudah tersimpan dapat digunakan kembali.
                            Unggah dokumen baru hanya jika ingin menggantinya atau menambah foto.
                        </small>
                    @endif
                </div>
            @endif

            @if($activeApplication->histories->isNotEmpty())
                <div class="va-muted">
                    <strong>Riwayat:</strong>
                    <ul>
                        @foreach($activeApplication->histories as $history)
                            <li>
                                {{ $history->created_at?->format('d-m-Y H:i') }}
                                —
                                {{ $history->from_status ?: 'Baru' }}
                                →
                                {{ $history->to_status }}.
                                {{ $history->note }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </article>
    @elseif($isVenueAdmin)
        <article class="va-card" style="border-left: 4px solid #16834a; background: #f6fbf8;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:12px; background:#e2f4ea; color:#16834a; display:grid; place-items:center; font-size:18px; flex-shrink:0;">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div>
                    <h4 style="margin:0; font-size:16px; color:#143e26; font-weight:750;">Pendaftaran Cabang / Venue Baru</h4>
                    <p style="margin:4px 0 0; color:#537361; font-size:13px; line-height:1.5;">
                        Sebagai Admin Venue, Anda dapat mendaftarkan venue atau cabang olahraga baru. Lengkapi formulir di bawah ini untuk diverifikasi oleh Superadmin.
                    </p>
                </div>
            </div>
        </article>
    @elseif($application && in_array($application->status, $approvedStatuses, true))
        <article class="va-card">
            <h3 class="va-section-title">Status Pengajuan Terakhir</h3>
            <span class="va-status" style="background:#eaf7ee; color:#16804a;">Disetujui</span>
            <p><strong>{{ $application->venue_name }}</strong></p>
            <p class="va-muted">{{ $application->address }}</p>
            <p class="va-muted" style="margin-top:10px;">
                Pengajuan venue Anda telah disetujui. Akun Anda telah aktif sebagai Admin Venue.
            </p>
        </article>
    @endif

    {{-- FORM HANYA UNTUK PENGAJUAN BARU, REVISI, ATAU DITOLAK --}}
    @if($canSubmit)

        <form
            method="POST"
            action="{{ ($isAdminFlow ?? false) ? route('admin.venue-register.store') : route('user.venue-application.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            {{-- 1. IDENTITAS PEMOHON --}}
            <section class="va-card">
                <h3 class="va-section-title">1. Identitas Pemohon</h3>

                <div class="va-grid">

                    <div class="va-field">
                        <label>Nama pemohon</label>
                        <input value="{{ $user->name }}" readonly>
                    </div>

                    <div class="va-field">
                        <label>Email akun</label>
                        <input value="{{ $user->email }}" readonly>
                    </div>

                    <div class="va-field">
                        <label>Nomor WhatsApp pemohon</label>
                        <input
                            name="applicant_phone"
                            value="{{ old('applicant_phone', $application->applicant_phone ?? $user->phone) }}"
                            maxlength="30"
                            required
                        >
                    </div>

                    <div class="va-field">
                        <label>
                            Hubungan pemohon dengan usaha
                            <span class="va-required-note">*</span>
                        </label>

                        <select
                            name="applicant_relationship"
                            id="applicant-relationship"
                            data-has-existing="{{ $hasAuthorizationDocument ? '1' : '0' }}"
                            required
                        >
                            <option value="">Pilih hubungan dengan usaha</option>

                            <option
                                value="owner"
                                @selected(old('applicant_relationship', $application->applicant_relationship ?? '') === 'owner')
                            >
                                Pemilik usaha
                            </option>

                            <option
                                value="manager"
                                @selected(old('applicant_relationship', $application->applicant_relationship ?? '') === 'manager')
                            >
                                Pengelola usaha
                            </option>

                            <option
                                value="representative"
                                @selected(old('applicant_relationship', $application->applicant_relationship ?? '') === 'representative')
                            >
                                Perwakilan pemilik usaha
                            </option>
                        </select>

                        <small>
                            Jika kamu bukan pemilik usaha, lampirkan surat kuasa atau surat penunjukan.
                        </small>
                    </div>

                    <div class="va-field full" id="authorization-field">
                        <label>
                            Surat kuasa atau surat penunjukan
                            <span class="va-required-note" id="authorization-required">*</span>
                        </label>

                        <input
                            type="file"
                            name="authorization_document"
                            id="authorization-document"
                            class="js-scan-input"
                            accept=".pdf,.jpg,.jpeg,.png"
                            data-has-existing="{{ $hasAuthorizationDocument ? '1' : '0' }}"
                        >

                        <small id="authorization-help">
                            Wajib untuk pengelola atau perwakilan usaha.
                            PDF, JPG, atau PNG. Maksimal 5 MB.
                        </small>
                    </div>

                    <div class="va-field">
                        <label>
                            Foto KTP atau identitas resmi
                            <span class="va-required-note">*</span>
                        </label>

                        <input
                            type="file"
                            name="identity_document"
                            class="js-scan-input"
                            accept=".pdf,.jpg,.jpeg,.png"
                            @if(!$hasIdentityDocument) required @endif
                        >

                        <small>
                            PDF, JPG, atau PNG. Maksimal 5 MB.
                            @if($hasIdentityDocument)
                                Kosongkan jika tidak ingin mengganti dokumen sebelumnya.
                            @endif
                        </small>
                    </div>

                </div>
            </section>

                        {{-- 2. DATA USAHA --}}
            <section class="va-card">
                <h3 class="va-section-title">2. Data Usaha</h3>

                <p class="va-muted" style="font-size:13px;margin-top:-6px">
                    Data perusahaan bersifat opsional untuk venue perorangan. Pilih jenis usaha terlebih dahulu.
                </p>

                <div class="va-grid">

                    <div class="va-field">
                        <label>
                            Jenis usaha <span class="va-required-note">*</span>
                        </label>
                        <select name="entity_type" id="entity-type" required>
                            <option value="">Pilih jenis usaha</option>
                            <option value="Perorangan" @selected($selectedEntityType === 'Perorangan')>Perorangan</option>
                            <option value="Badan Usaha" @selected($selectedEntityType === 'Badan Usaha')>Badan Usaha</option>
                        </select>
                        <small>Pilih Perorangan jika venue tidak dimiliki perusahaan berbadan hukum.</small>
                    </div>

                    <div class="va-field js-company-field">
                        <label>Jenis badan usaha</label>
                        <select name="business_type" id="business-type">
                            <option value="">Pilih jenis badan usaha</option>
                            @foreach(['CV','PT','Koperasi','Yayasan','Lainnya'] as $type)
                                <option value="{{ $type }}" @selected($selectedBusinessType === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <small>Wajib untuk badan usaha.</small>
                    </div>

                    <div class="va-field js-company-field">
                        <label>Nama perusahaan / badan usaha</label>
                        <input
                            name="company_name"
                            value="{{ old('company_name', $sourceApp->company_name ?? $companySource->company_name ?? '') }}"
                            maxlength="180"
                            placeholder="Contoh: PT Arena Sports Indonesia"
                        >
                    </div>

                    <div class="va-field js-company-field">
                        <label>Nomor NIB / izin usaha (opsional)</label>
                        <input
                            name="nib"
                            value="{{ old('nib', $sourceApp->nib ?? $companySource->nib ?? '') }}"
                            maxlength="100"
                            placeholder="Isi jika tersedia"
                        >
                    </div>

                    <div class="va-field js-company-field">
                        <label>Telepon perusahaan</label>
                        <input
                            name="company_phone"
                            value="{{ old('company_phone', $sourceApp->company_phone ?? $companySource->company_phone ?? '') }}"
                            maxlength="30"
                        >
                    </div>

                    <div class="va-field">
                        <label>Kota</label>
                        <input name="city" value="{{ old('city', $sourceApp->city ?? '') }}" required maxlength="100">
                    </div>

                    <div class="va-field">
                        <label>Provinsi</label>
                        <input name="province" value="{{ old('province', $sourceApp->province ?? '') }}" required maxlength="100">
                    </div>

                    <div class="va-field full">
                        <label>Alamat pemilik usaha <span class="va-required-note">*</span></label>
                        <textarea name="owner_address" rows="2" required>{{ old('owner_address', $sourceApp->owner_address ?? '') }}</textarea>
                        <small>Alamat ini terpisah dari alamat venue.</small>
                    </div>

                    <div class="va-field">
                        <label>Nomor identitas pemilik (NIK, opsional)</label>
                        <input name="identity_number" value="{{ old('identity_number', $sourceApp->identity_number ?? '') }}" maxlength="80">
                    </div>

                    <div class="va-field full js-company-field">
                        <label>Alamat perusahaan</label>
                        <textarea name="company_address" rows="2">{{ old('company_address', $sourceApp->company_address ?? $companySource->company_address ?? '') }}</textarea>
                        <small>Alamat kantor/usaha. Berbeda dengan alamat venue.</small>
                    </div>

                    <div class="va-field full js-company-field">
                        <label>Deskripsi perusahaan (opsional)</label>
                        <textarea name="company_description" rows="2">{{ old('company_description', $sourceApp->company_description ?? $companySource->company_description ?? '') }}</textarea>
                    </div>

                </div>
            </section>

{{-- 3. INFORMASI VENUE --}}
            <section class="va-card">
                <h3 class="va-section-title">3. Informasi Venue</h3>

                <div class="va-grid">

                    <div class="va-field">
                        <label>
                            Nama venue <span class="va-required-note">*</span>
                        </label>
                        <input
                            name="venue_name"
                            value="{{ old('venue_name', $application->venue_name ?? '') }}"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="va-field">
                        <label>Jam buka (opsional)</label>
                        <input type="time" name="opening_time" value="{{ $openingTime }}">
                        <small>Kosongkan jika belum ditentukan. Jam dipakai untuk validasi booking.</small>
                    </div>

                    <div class="va-field">
                        <label>Jam tutup (opsional)</label>
                        <input type="time" name="closing_time" value="{{ $closingTime }}">
                    </div>

                    <div class="va-field full">
                        <label>Hari operasional</label>
                        <div class="va-sports">
                            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $day)
                                <label>
                                    <input type="checkbox" name="operating_days[]" value="{{ $day }}" @checked(in_array($day, $selectedOperatingDays, true))>
                                    {{ $day }}
                                </label>
                            @endforeach
                        </div>
                        <small>Kosongkan untuk dianggap beroperasi setiap hari.</small>
                    </div>

                    <div class="va-field">
                        <label>
                            Nomor telepon venue <span class="va-required-note">*</span>
                        </label>
                        <input
                            name="contact_phone"
                            value="{{ old('contact_phone', $application->contact_phone ?? '') }}"
                            maxlength="30"
                            required
                        >
                    </div>

                    <div class="va-field full">
                        <label>
                            Alamat lengkap venue <span class="va-required-note">*</span>
                        </label>
                        <textarea
                            name="address"
                            rows="3"
                            maxlength="1000"
                            required
                        >{{ old('address', $application->address ?? '') }}</textarea>
                    </div>

                    <div class="va-field full">
                        <label>Link lokasi Google Maps</label>
                        <input
                            type="url"
                            name="google_maps_url"
                            value="{{ old('google_maps_url', $application->google_maps_url ?? '') }}"
                            placeholder="https://maps.google.com/..."
                        >
                    </div>

                    <div class="va-field">
                        <label>Latitude (opsional)</label>
                        <input
                            type="number"
                            step="any"
                            name="latitude"
                            value="{{ old('latitude', $application->latitude ?? '') }}"
                        >
                    </div>

                    <div class="va-field">
                        <label>Longitude (opsional)</label>
                        <input
                            type="number"
                            step="any"
                            name="longitude"
                            value="{{ old('longitude', $application->longitude ?? '') }}"
                        >
                    </div>

                    <div class="va-field full">
                        <label>
                            Deskripsi singkat venue
                            <span class="va-required-note">*</span>
                        </label>
                        <textarea
                            name="description"
                            rows="4"
                            maxlength="3000"
                            placeholder="Ceritakan keunggulan venue, fasilitas, area parkir, ruang ganti, dan informasi lain yang penting bagi penyewa."
                            required
                        >{{ old('description', $application->description ?? '') }}</textarea>
                        <small>Jumlah lapangan akan dihitung otomatis setelah lapangan ditambahkan.</small>
                    </div>

                    {{-- JENIS OLAHRAGA --}}
                    <div class="va-field full">
                        <label>
                            Jenis olahraga yang tersedia
                            <span class="va-required-note">*</span>
                        </label>

                        <div class="va-sports">
                            @foreach(['Badminton','Futsal'] as $sport)
                                <label>
                                    <input
                                        type="checkbox"
                                        name="sports[]"
                                        value="{{ $sport }}"
                                        @checked(in_array($sport, $sportsValues, true))
                                    >
                                    {{ $sport }}
                                </label>
                            @endforeach
                        </div>

                        <small>Pilih minimal satu jenis olahraga.</small>
                    </div>

                    {{-- FASILITAS DINAMIS --}}
                    <div class="va-field full">

                        <label>Fasilitas venue</label>

                        <small>
                            Tambahkan fasilitas yang tersedia di venue.
                            Kamu dapat menambah atau menghapus fasilitas sesuai kebutuhan.
                        </small>

                        <div class="va-facility-list" id="facility-list">

                            @foreach($facilityValues as $facility)
                                <div class="va-facility-row">

                                    <input
                                        type="text"
                                        name="facilities[]"
                                        value="{{ $facility }}"
                                        placeholder="Contoh: Parkir motor"
                                        maxlength="80"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="va-remove-facility"
                                        title="Hapus fasilitas"
                                        onclick="removeFacility(this)"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>

                                </div>
                            @endforeach

                        </div>

                        <button
                            type="button"
                            class="va-add-facility"
                            id="add-facility"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Tambah Fasilitas
                        </button>

                    </div>

                </div>
            </section>

            {{-- 4. KEPEMILIKAN TEMPAT --}}
            <section class="va-card">
                <h3 class="va-section-title">4. Status Kepemilikan Tempat</h3>

                <div class="va-grid">

                    <div class="va-field">
                        <label>
                            Status tempat <span class="va-required-note">*</span>
                        </label>

                        <select name="ownership_type" id="ownership-type" required>
                            <option value="">Pilih status kepemilikan</option>

                            <option
                                value="Milik Sendiri"
                                @selected($selectedOwnership === 'Milik Sendiri')
                            >
                                Milik sendiri
                            </option>

                            <option
                                value="Sewa"
                                @selected($selectedOwnership === 'Sewa')
                            >
                                Tempat sewa
                            </option>
                        </select>
                    </div>

                    <div class="va-field">
                        <label id="ownership-label">
                            Bukti kepemilikan tempat
                        </label>

                        <input
                            type="file"
                            name="ownership_document"
                            id="ownership-document"
                            class="js-scan-input"
                            accept=".pdf,.jpg,.jpeg,.png"
                            data-has-existing="{{ $hasOwnershipDocument ? '1' : '0' }}"
                        >

                        <small id="ownership-help">
                            Pilih status tempat terlebih dahulu.
                        </small>
                    </div>

                    <div class="va-field full">
                        <label>Surat perjanjian sewa (khusus tempat sewa)</label>

                        <input
                            type="file"
                            name="lease_document"
                            id="lease-document"
                            class="js-scan-input"
                            accept=".pdf,.jpg,.jpeg,.png"
                            data-has-existing="{{ $hasLeaseDocument ? '1' : '0' }}"
                        >

                        <small id="lease-help">
                            Wajib jika status tempat adalah sewa.
                            Jika milik sendiri, bagian ini tidak perlu diisi.
                        </small>
                    </div>

                    <div class="va-field full">
                        <label>NIB atau izin usaha (jika tersedia)</label>

                        <input
                            type="file"
                            name="business_license"
                            class="js-scan-input"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >

                        <small>
                            Dokumen pendukung tambahan, tidak wajib.
                            Maksimal 5 MB.
                        </small>
                    </div>

                </div>
            </section>

            {{-- 5. FOTO VENUE --}}
            <section class="va-card">
                <h3 class="va-section-title">5. Foto Kondisi Venue</h3>

                <div class="va-docs">

                    <div class="va-field">
                        <label>
                            Foto tampak depan venue
                            <span class="va-required-note">*</span>
                        </label>

                        <input
                            type="file"
                            name="venue_exterior_photo"
                            class="js-scan-input"
                            accept="image/jpeg,image/png,image/webp"
                            @if(!$hasExteriorPhoto) required @endif
                        >

                        <small>
                            Foto papan nama atau tampak depan lokasi. Maksimal 5 MB.
                            @if($hasExteriorPhoto)
                                Kosongkan jika foto sebelumnya masih berlaku.
                            @endif
                        </small>
                    </div>

                    <div class="va-field">
                        <label>
                            Foto bagian dalam venue
                            <span class="va-required-note">*</span>
                        </label>

                        <input
                            type="file"
                            name="venue_interior_photo"
                            class="js-scan-input"
                            accept="image/jpeg,image/png,image/webp"
                            @if(!$hasInteriorPhoto) required @endif
                        >

                        <small>
                            Foto area utama di dalam tempat olahraga. Maksimal 5 MB.
                            @if($hasInteriorPhoto)
                                Kosongkan jika foto sebelumnya masih berlaku.
                            @endif
                        </small>
                    </div>

                </div>

                <p class="va-muted" style="font-size:12px;margin:10px 0 0">
                    <i class="fa-solid fa-circle-info"></i>
                    Foto fasilitas dan area lapangan dapat ditambahkan oleh pengelola setelah pengajuan disetujui.
                </p>
            </section>

            {{-- 6. PERNYATAAN TANGGUNG JAWAB --}}
            <section class="va-card">

                <h3 class="va-section-title">6. Pernyataan dan Persetujuan</h3>

                <p class="va-muted" style="font-size:13px">
                    Dengan mengirim pengajuan, pemohon menyatakan bahwa:
                </p>

                <ul class="va-muted" style="font-size:13px;line-height:1.8;padding-left:20px">
                    <li>Seluruh informasi dan dokumen yang diberikan adalah benar dan dapat dipertanggungjawabkan.</li>
                    <li>Pemohon memiliki hak atau izin yang sah untuk mengajukan pengelolaan venue tersebut.</li>
                    <li>Pemohon bersedia bertanggung jawab atas keabsahan dokumen dan informasi yang disampaikan.</li>
                    <li>Pemohon memahami bahwa Superadmin berhak memeriksa, meminta revisi, atau menolak pengajuan.</li>
                </ul>

                <label class="va-terms">
                    <input
                        type="checkbox"
                        name="accepted_terms"
                        value="1"
                        @checked(old('accepted_terms'))
                        required
                    >

                    <span>
                        Saya telah membaca, memahami, dan menyetujui seluruh pernyataan tanggung jawab di atas.
                        <span class="va-required-note">*</span>
                    </span>
                </label>

                <button type="submit" class="btn-ui btn-green {{ ($isAdminFlow ?? false) ? '' : 'u-btn' }}" style="width:100%;margin-top:20px;padding:14px 20px;font-size:15px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:10px;cursor:pointer;">
                    <i class="fa-solid fa-paper-plane"></i>
                    {{ $isRevision ? 'Kirim Ulang Pengajuan Setelah Revisi' : (($isNewVenueBranch ?? false) ? 'Kirim Pengajuan Venue Baru untuk Diverifikasi' : 'Kirim Pengajuan untuk Diverifikasi') }}
                </button>

            </section>

        </form>

    @else

        <section class="va-card">
            @if($application && in_array($application->status, $approvedStatuses, true))
                <p class="va-muted">
                    Pengajuan venue kamu telah disetujui.
                    Kamu sudah dapat menggunakan akses Admin Venue.
                </p>
            @else
                <p class="va-muted">
                    Pengajuan masih menunggu proses verifikasi Superadmin.
                    Kamu belum dapat mengirim pengajuan baru.
                </p>
            @endif
        </section>

    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ==================================
       STATUS KEPEMILIKAN TEMPAT
    ================================== */

    const type = document.getElementById('ownership-type');
    const ownership = document.getElementById('ownership-document');
    const lease = document.getElementById('lease-document');
    const label = document.getElementById('ownership-label');
    const help = document.getElementById('ownership-help');
    const leaseHelp = document.getElementById('lease-help');

    function updateOwnershipFields() {

        if (!type || !ownership || !lease) return;

        const isOwn = type.value === 'Milik Sendiri';
        const isRent = type.value === 'Sewa';

        const hasOwnership = ownership.dataset.hasExisting === '1';
        const hasLease = lease.dataset.hasExisting === '1';

        ownership.required = isOwn && !hasOwnership;
        lease.required = isRent && !hasLease;

        ownership.disabled = !isOwn;
        lease.disabled = !isRent;

        if (label) {
            label.textContent = isOwn
                ? 'Bukti kepemilikan tempat *'
                : 'Bukti kepemilikan (tidak diperlukan untuk tempat sewa)';
        }

        if (help) {
            help.textContent = isOwn
                ? (hasOwnership
                    ? 'Dokumen kepemilikan sebelumnya sudah tersimpan. Unggah hanya jika ingin menambahkan dokumen baru.'
                    : 'Unggah sertifikat, bukti kepemilikan, atau dokumen sah lain. Maksimal 5 MB.')
                : 'Pilih status tempat milik sendiri untuk mengisi bagian ini.';
        }

        if (leaseHelp) {
            leaseHelp.textContent = isRent
                ? (hasLease
                    ? 'Perjanjian sewa sebelumnya sudah tersimpan. Unggah hanya jika ingin menambahkan dokumen baru.'
                    : 'Wajib untuk tempat sewa. Maksimal 5 MB.')
                : 'Pilih status tempat sewa untuk mengisi bagian ini.';
        }
    }

    if (type) {
        type.addEventListener('change', updateOwnershipFields);
        updateOwnershipFields();
    }


    /* ==================================
       JENIS USAHA (PERORANGAN / BADAN USAHA)
    ================================== */

    const entityType = document.getElementById('entity-type');
    const companyFields = document.querySelectorAll('.js-company-field');

    function updateEntityFields() {
        if (!entityType) return;

        const isCompany = entityType.value === 'Badan Usaha';
        const isPerorangan = entityType.value === 'Perorangan';

        companyFields.forEach(function (field) {
            field.style.display = isCompany ? '' : 'none';

            field.querySelectorAll('input, textarea, select').forEach(function (input) {
                input.disabled = !isCompany;
            });
        });

        if (isPerorangan) {
            const companyName = document.querySelector('input[name="company_name"]');
            const companyPhone = document.querySelector('input[name="company_phone"]');
            const companyAddress = document.querySelector('textarea[name="company_address"]');

            [companyName, companyPhone, companyAddress].forEach(function (input) {
                if (input) input.value = '';
            });
        }
    }

    if (entityType) {
        entityType.addEventListener('change', updateEntityFields);
        updateEntityFields();
    }


    /* ==================================
       SURAT KUASA / PENUNJUKAN
    ================================== */

    const relationship = document.getElementById('applicant-relationship');
    const authorization = document.getElementById('authorization-document');
    const authorizationField = document.getElementById('authorization-field');
    const authorizationRequired = document.getElementById('authorization-required');
    const authorizationHelp = document.getElementById('authorization-help');

    function updateAuthorizationField() {

        if (!relationship || !authorization) return;

        const needsAuthorization = [
            'manager',
            'representative'
        ].includes(relationship.value);

        const hasExisting = authorization.dataset.hasExisting === '1';

        authorization.disabled = !needsAuthorization;
        authorization.required = needsAuthorization && !hasExisting;

        if (authorizationField) {
            authorizationField.style.opacity = needsAuthorization ? '1' : '0.75';
        }

        if (authorizationRequired) {
            authorizationRequired.style.display = needsAuthorization ? 'inline' : 'none';
        }

        if (authorizationHelp) {
            authorizationHelp.textContent = !needsAuthorization
                ? 'Dokumen ini tidak diperlukan jika pemohon merupakan pemilik usaha.'
                : hasExisting
                    ? 'Surat kuasa atau penunjukan sebelumnya sudah tersimpan. Unggah dokumen baru jika ingin menggantinya atau menambahkan dokumen.'
                    : 'Wajib untuk pengelola atau perwakilan usaha. PDF, JPG, atau PNG. Maksimal 5 MB.';
        }
    }

    if (relationship) {
        relationship.addEventListener('change', updateAuthorizationField);
        updateAuthorizationField();
    }


    /* ==================================
       DINAMIS TAMBAH FASILITAS
    ================================== */

    const facilityList = document.getElementById('facility-list');
    const addFacilityButton = document.getElementById('add-facility');

    window.removeFacility = function (button) {

        if (!facilityList) return;

        const rows = facilityList.querySelectorAll('.va-facility-row');

        if (rows.length <= 1) {
            alert('Minimal harus ada satu fasilitas.');
            return;
        }

        button.closest('.va-facility-row').remove();
    };

    if (addFacilityButton && facilityList) {

        addFacilityButton.addEventListener('click', function () {

            const row = document.createElement('div');
            row.className = 'va-facility-row';

            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'facilities[]';
            input.placeholder = 'Masukkan nama fasilitas';
            input.maxLength = 80;
            input.required = true;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'va-remove-facility';
            button.title = 'Hapus fasilitas';
            button.innerHTML = '<i class="fa-solid fa-trash"></i>';
            button.addEventListener('click', function () {
                window.removeFacility(button);
            });

            row.appendChild(input);
            row.appendChild(button);

            facilityList.appendChild(row);
            input.focus();
        });
    }


    /* ==================================
       PREVIEW DOKUMEN DAN FOTO
    ================================== */

    document.querySelectorAll('.js-scan-input').forEach(function (input) {

        const preview = document.createElement('div');
        preview.className = 'va-scan-preview';

        input.insertAdjacentElement('afterend', preview);

        input.addEventListener('change', function () {

            preview.innerHTML = '';

            Array.from(input.files).forEach(function (file) {

                const item = document.createElement('div');
                item.className = 'va-scan-item';

                if (file.type.startsWith('image/')) {

                    const image = document.createElement('img');
                    const objectUrl = URL.createObjectURL(file);

                    image.src = objectUrl;
                    image.alt = file.name;

                    image.onload = function () {
                        URL.revokeObjectURL(objectUrl);
                    };

                    image.onerror = function () {
                        URL.revokeObjectURL(objectUrl);
                    };

                    item.appendChild(image);

                } else {

                    const icon = document.createElement('div');
                    icon.className = 'va-file-icon';
                    icon.innerHTML = '<i class="fa-solid fa-file-pdf"></i>';

                    item.appendChild(icon);
                }

                const filename = document.createElement('div');
                filename.className = 'va-scan-name';
                filename.textContent = file.name;

                const size = document.createElement('small');
                size.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                item.appendChild(filename);
                item.appendChild(size);

                preview.appendChild(item);
            });
        });
    });

});
</script>
@endpush

@endsection
