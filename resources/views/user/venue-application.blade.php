
@extends('layouts.user')

@section('content')
@php
    $revisionStatuses = ['revision', 'Perlu Revisi'];
    $rejectedStatuses = ['rejected', 'Ditolak'];
    $approvedStatuses = ['approved', 'Disetujui'];

    $canSubmit = !$application ||
        in_array($application->status, array_merge($revisionStatuses, $rejectedStatuses), true);

    $isRevision = $application &&
        in_array($application->status, $revisionStatuses, true);

    $existingDocuments = $application?->documents ?? collect();

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

    $hasCourtPhotos = $isRevision &&
        $existingDocuments->contains('document_type', 'Foto Fasilitas Lapangan');

    $selectedOwnership = old(
        'ownership_type',
        $application->ownership_type ?? ''
    );

    $facilityValues = old('facilities', $application->facilities ?? [
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

    $sportsValues = old('sports', $application->sports ?? []);

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

    <div class="va-head">
        <h2>Pengajuan Admin Venue</h2>
        <p>
            Lengkapi informasi usaha dan dokumen pendukung.
            Superadmin akan memeriksa kelayakan venue sebelum memberikan akses Admin Venue.
        </p>
    </div>

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
    @if($application)
        <article class="va-card">
            <h3 class="va-section-title">Status Pengajuan Terakhir</h3>

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
                    ][$application->status] ?? $application->status
                }}
            </span>

            <p><strong>{{ $application->venue_name }}</strong></p>
            <p class="va-muted">{{ $application->address }}</p>

            @if($application->admin_note)
                <p>
                    <strong>Catatan Superadmin:</strong>
                    {{ $application->admin_note }}
                </p>
            @endif

            @if($application->documents->isNotEmpty())
                <div class="va-existing-docs">
                    <strong>Dokumen yang sudah tersimpan:</strong>
                    <ul>
                        @foreach($application->documents as $document)
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

            @if($application->histories->isNotEmpty())
                <div class="va-muted">
                    <strong>Riwayat:</strong>
                    <ul>
                        @foreach($application->histories as $history)
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
    @endif

    {{-- FORM HANYA UNTUK PENGAJUAN BARU, REVISI, ATAU DITOLAK --}}
    @if($canSubmit)

        <form
            method="POST"
            action="{{ route('user.venue-application.store') }}"
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

            {{-- 2. DATA PERUSAHAAN --}}
            <section class="va-card">
                <h3 class="va-section-title">2. Data Perusahaan atau Badan Usaha</h3>

                <div class="va-grid">

                    <div class="va-field">
                        <label>Alamat pemilik usaha</label>
                        <textarea name="owner_address" rows="2" required>{{ old('owner_address', $application->owner_address ?? '') }}</textarea>
                    </div>

                    <div class="va-field">
                        <label>Nomor identitas (opsional)</label>
                        <input
                            name="identity_number"
                            value="{{ old('identity_number', $application->identity_number ?? '') }}"
                            maxlength="80"
                        >
                    </div>

                    <div class="va-field">
                        <label>Nama perusahaan / usaha</label>
                        <input
                            name="company_name"
                            value="{{ old('company_name', $application->company_name ?? '') }}"
                            maxlength="180"
                            required
                        >
                    </div>

                    <div class="va-field">
                        <label>Jenis badan usaha</label>
                        <select name="business_type" required>
                            <option value="">Pilih jenis usaha</option>

                            @foreach(['Perorangan','CV','PT','Koperasi','Yayasan','Lainnya'] as $type)
                                <option
                                    value="{{ $type }}"
                                    @selected(old('business_type', $application->business_type ?? '') === $type)
                                >
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="va-field">
                        <label>Nomor NIB (jika tersedia)</label>
                        <input
                            name="nib"
                            value="{{ old('nib', $application->nib ?? '') }}"
                            maxlength="80"
                        >
                    </div>

                    <div class="va-field">
                        <label>Nomor telepon perusahaan</label>
                        <input
                            name="company_phone"
                            value="{{ old('company_phone', $application->company_phone ?? '') }}"
                            maxlength="30"
                            required
                        >
                    </div>

                    <div class="va-field">
                        <label>Kota</label>
                        <input
                            name="city"
                            value="{{ old('city', $application->city ?? '') }}"
                            required
                        >
                    </div>

                    <div class="va-field">
                        <label>Provinsi</label>
                        <input
                            name="province"
                            value="{{ old('province', $application->province ?? '') }}"
                            required
                        >
                    </div>

                    <div class="va-field full">
                        <label>Alamat perusahaan</label>
                        <textarea name="company_address" rows="2" required>{{ old('company_address', $application->company_address ?? '') }}</textarea>
                    </div>

                    <div class="va-field full">
                        <label>Deskripsi perusahaan</label>
                        <textarea name="company_description" rows="2">{{ old('company_description', $application->company_description ?? '') }}</textarea>
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
                        <label>
                            Jam operasional <span class="va-required-note">*</span>
                        </label>
                        <input
                            name="operating_hours"
                            value="{{ old('operating_hours', $application->operating_hours ?? '07.00–23.00 WIB') }}"
                            placeholder="Contoh: 07.00–23.00 WIB"
                            maxlength="150"
                            required
                        >
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
                            Deskripsi venue dan fasilitas
                            <span class="va-required-note">*</span>
                        </label>
                        <textarea
                            name="description"
                            rows="4"
                            maxlength="3000"
                            placeholder="Jelaskan jumlah lapangan, fasilitas, area parkir, ruang ganti, toilet, dan fasilitas lainnya."
                            required
                        >{{ old('description', $application->description ?? '') }}</textarea>
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

                    <div class="va-field full">
                        <label>
                            Foto fasilitas dan lapangan
                            <span class="va-required-note">*</span>
                        </label>

                        <input
                            type="file"
                            name="court_photos[]"
                            class="js-scan-input"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            @if(!$hasCourtPhotos) required @endif
                        >

                        <small>
                            Unggah minimal satu foto dan maksimal delapan foto.
                            Setiap file maksimal 5 MB.
                            @if($hasCourtPhotos)
                                Kosongkan jika foto sebelumnya masih berlaku.
                            @endif
                        </small>
                    </div>

                </div>
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

                <button type="submit" class="u-btn" style="width:100%;margin-top:20px">
                    {{ $isRevision ? 'Kirim Ulang Pengajuan Setelah Revisi' : 'Kirim Pengajuan untuk Diverifikasi' }}
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
