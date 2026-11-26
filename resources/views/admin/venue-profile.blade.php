
@extends('layouts.admin', [
    'title' => 'Profil Venue',
    'heading' => 'Profil Venue'
])

@section('content')

<style>
    .venue-profile-wrapper {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 30px;
        color: #193b2b;
    }

    .venue-profile-header {
        margin-bottom: 25px;
    }

    .venue-profile-header h2 {
        font-size: 27px;
        font-weight: 800;
        color: #126b38;
        margin: 0 0 8px;
    }

    .venue-profile-header p {
        color: #718078;
        font-size: 14px;
        margin: 0;
    }

    .venue-profile-card {
        background: #ffffff;
        border: 1px solid #e8f0eb;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 8px 30px rgba(20, 75, 43, 0.06);
    }

    .venue-section-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 25px;
        padding-bottom: 18px;
        border-bottom: 1px solid #edf2ee;
    }

    .venue-section-icon {
        width: 43px;
        height: 43px;
        border-radius: 12px;
        background: #e8f8ed;
        color: #07883e;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .venue-section-heading h3 {
        font-size: 18px;
        font-weight: 750;
        color: #174d2d;
        margin: 0 0 4px;
    }

    .venue-section-heading p {
        font-size: 13px;
        color: #849189;
        margin: 0;
    }

    .venue-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .venue-form-group {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .venue-form-group.full-width {
        grid-column: 1 / -1;
    }

    .venue-form-group label {
        font-size: 13px;
        font-weight: 700;
        color: #334c3c;
        margin-bottom: 9px;
    }

    .venue-form-group label .required {
        color: #e04b4b;
    }

    .venue-form-group input,
    .venue-form-group textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #dce7df;
        background: #fbfdfb;
        color: #253b2e;
        border-radius: 11px;
        padding: 13px 15px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        transition: all .2s ease;
    }

    .venue-form-group input {
        height: 47px;
    }

    .venue-form-group textarea {
        min-height: 125px;
        resize: vertical;
        line-height: 1.6;
    }

    .venue-form-group input::placeholder,
    .venue-form-group textarea::placeholder {
        color: #a4b0a8;
    }

    .venue-form-group input:focus,
    .venue-form-group textarea:focus {
        border-color: #0b9949;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(11, 153, 73, 0.09);
    }

    .venue-form-help {
        font-size: 12px;
        color: #89978e;
        margin-top: 7px;
    }

    .venue-sports-section {
        grid-column: 1 / -1;
        margin-top: 2px;
    }

    .venue-sports-title {
        font-size: 14px;
        font-weight: 750;
        color: #334c3c;
        margin-bottom: 6px;
    }

    .venue-sports-description {
        font-size: 12px;
        color: #89978e;
        margin-bottom: 15px;
    }

    .venue-sports-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .venue-sport-option {
        position: relative;
    }

    .venue-sport-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .venue-sport-option label {
        min-height: 55px;
        padding: 12px 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        border: 1px solid #dce7df;
        border-radius: 11px;
        background: #fbfdfb;
        color: #53675a;
        font-size: 13px;
        font-weight: 650;
        cursor: pointer;
        transition: all .2s ease;
    }

    .venue-sport-option label:hover {
        border-color: #0b9949;
        color: #07883e;
    }

    .venue-sport-option input:checked + label {
        background: #e9f8ee;
        color: #087d39;
        border: 1px solid #0b9949;
        box-shadow: 0 0 0 2px rgba(11, 153, 73, .08);
    }

    .venue-sport-option input:focus-visible + label {
        outline: 3px solid rgba(11, 153, 73, .25);
        outline-offset: 2px;
    }

    .venue-form-footer {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        border-top: 1px solid #edf2ee;
        padding-top: 23px;
        margin-top: 5px;
    }

    .venue-footer-note {
        font-size: 12px;
        color: #89978e;
    }

    .venue-save-button {
        border: none;
        border-radius: 11px;
        background: linear-gradient(135deg, #0baa50, #07833c);
        color: #ffffff;
        padding: 14px 23px;
        font-size: 14px;
        font-weight: 750;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        box-shadow: 0 5px 13px rgba(8, 145, 63, .18);
        transition: all .2s ease;
    }

    .venue-save-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(8, 145, 63, .25);
    }

    .venue-save-button:active {
        transform: translateY(0);
    }

    .venue-alert {
        padding: 14px 17px;
        border-radius: 11px;
        margin-bottom: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .venue-alert-success {
        background: #e9f8ee;
        color: #087d39;
        border: 1px solid #c6ebd2;
    }

    .venue-alert-error {
        background: #fff0f0;
        color: #bd3030;
        border: 1px solid #f4cccc;
    }

    .venue-field-error {
        color: #d13e3e;
        font-size: 12px;
        margin-top: 6px;
    }

    .venue-photo-upload {
        grid-column: 1 / -1;
        padding: 18px;
        border: 1px dashed #9ac9a8;
        border-radius: 14px;
        background: #f5fbf6;
    }

    .venue-photo-preview {
        width: 100%;
        max-width: 420px;
        height: 210px;
        object-fit: cover;
        display: block;
        margin: 12px 0;
        border-radius: 12px;
        border: 1px solid #dcebe0;
        background: #e8f7ec;
    }

    .venue-photo-upload input[type="file"] {
        display: block;
        width: 100%;
        margin-top: 10px;
        padding: 10px;
        border: 1px solid #dce7df;
        border-radius: 9px;
        background: #fff;
    }

    .venue-photo-upload .venue-form-help {
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .venue-sports-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 650px) {
        .venue-profile-card {
            padding: 20px 16px;
            border-radius: 15px;
        }

        .venue-profile-header h2 {
            font-size: 23px;
        }

        .venue-form-grid {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .venue-form-group.full-width,
        .venue-sports-section,
        .venue-form-footer {
            grid-column: 1;
        }

        .venue-sports-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .venue-form-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .venue-save-button {
            width: 100%;
        }
    }
</style>

<div class="venue-profile-wrapper">

    <p style="margin:0 0 20px;color:#718078;font-size:14px;">Kelola informasi dan identitas venue olahraga Anda.</p>

    @if(session('success'))
        <div class="venue-alert venue-alert-success">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="venue-alert venue-alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="venue-alert venue-alert-error">
            <strong>Periksa kembali data berikut:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(isset($venues) && $venues->count() > 1)
        <form method="GET" action="{{ route('admin.venue-profile') }}" style="margin:0 0 16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <label for="venue_id">Pilih venue:</label>
            <select id="venue_id" name="venue_id" onchange="this.form.submit()" style="padding:10px;border:1px solid #d5dee7;border-radius:8px">
                @foreach($venues as $item)<option value="{{ $item->id }}" @selected($venue->id === $item->id)>{{ $item->name }}</option>@endforeach
            </select>
        </form>
    @endif

@php
    $galleryPaths = collect(is_array($venue->gallery) ? $venue->gallery : [])
        ->filter(fn ($item) => is_string($item) && trim($item) !== '')
        ->values();

    if ($venue->photo && !$galleryPaths->contains($venue->photo)) {
        $galleryPaths->prepend($venue->photo);
    }

    $galleryPaths = $galleryPaths->unique()->values();

    $pathToUrl = function (?string $path) use ($venue) {
        if (!$path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'uploads/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    };

    $selectedOperatingDays = old('operating_days', $venue->operating_days_list ?? []);
    if (!is_array($selectedOperatingDays)) {
        $selectedOperatingDays = [];
    }

    $openingTime = old('opening_time', $venue->opening_time ? substr((string) $venue->opening_time, 0, 5) : '');
    $closingTime = old('closing_time', $venue->closing_time ? substr((string) $venue->closing_time, 0, 5) : '');
    $selectedEntity = old('entity_type', $venue->entity_type ?: ($venue->company_name ? 'Badan Usaha' : 'Perorangan'));
    $selectedStatus = old('operational_status', $venue->operational_status ?: 'Beroperasi');
@endphp

{{-- Ringkasan profil venue --}}
<div class="venue-profile-card" style="margin-bottom:20px;padding:0;overflow:hidden;">
    <div style="display:grid;grid-template-columns:minmax(0,340px) minmax(0,1fr);gap:0;align-items:stretch;">
        <div style="position:relative;min-height:210px;background:linear-gradient(135deg,#d9f2e1,#b9e6c8);">
            @if($venue->photo)
                <img src="{{ $pathToUrl($venue->photo) }}" alt="Foto utama {{ $venue->name }}" style="width:100%;height:100%;object-fit:cover;display:block;min-height:210px;">
            @else
                <div style="height:100%;min-height:210px;display:grid;place-items:center;color:#168b50;font-size:44px;">
                    <i class="fa-solid fa-building"></i>
                </div>
            @endif
            <span style="position:absolute;top:14px;left:14px;padding:7px 12px;border-radius:30px;background:#ffffffed;color:#0b7040;font-size:12px;font-weight:800;">
                Foto Utama
            </span>
        </div>

        <div style="padding:24px 26px;display:flex;flex-direction:column;justify-content:center;gap:10px;">
            <span style="display:inline-flex;width:max-content;align-items:center;gap:7px;padding:6px 11px;border-radius:30px;background:#e8f8ed;color:#07883e;font-size:12px;font-weight:800;">
                <i class="fa-solid fa-location-dot"></i> Profil Venue
            </span>
            <h2 style="margin:0;font-size:26px;color:#126b38;font-weight:850;">{{ $venue->name }}</h2>

            @if($venue->managed_by)
                <p style="margin:0;color:#5f7268;font-size:13px;">
                    <i class="fa-solid fa-user-tie"></i> Dikelola oleh <strong>{{ $venue->managed_by }}</strong>
                </p>
            @endif

            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
                <span style="padding:7px 11px;border-radius:9px;background:#f2f8f4;color:#29523a;font-size:12px;">
                    <i class="fa-solid fa-futbol"></i> {{ implode(', ', $venue->sports ?? []) ?: 'Olahraga belum diisi' }}
                </span>
                <span style="padding:7px 11px;border-radius:9px;background:#f2f8f4;color:#29523a;font-size:12px;">
                    <i class="fa-regular fa-clock"></i> {{ $venue->operating_hours_text }}
                </span>
                <span style="padding:7px 11px;border-radius:9px;background:{{ $venue->is_operating ? '#e3f7e9' : '#fdecec' }};color:{{ $venue->is_operating ? '#087747' : '#b23b3b' }};font-size:12px;font-weight:700;">
                    <i class="fa-solid fa-store"></i> {{ $venue->operational_status ?: 'Beroperasi' }}
                </span>
                <span style="padding:7px 11px;border-radius:9px;background:#f2f8f4;color:#29523a;font-size:12px;">
                    <i class="fa-regular fa-calendar-days"></i> {{ $venue->operating_days_text }}
                </span>
            </div>

            <p style="margin:4px 0 0;color:#6b7d72;font-size:13px;line-height:1.6;">
                {{ $venue->address ?: 'Alamat venue belum diisi.' }}
            </p>

            @if($venue->company_information_available)
                <p style="margin:0;color:#6b7d72;font-size:12px;">
                    <i class="fa-solid fa-briefcase"></i> {{ $venue->company_name ?: $venue->business_type }}
                </p>
            @endif
        </div>
    </div>
</div>

<div class="venue-profile-card">

    <div class="venue-section-heading">
        <div class="venue-section-icon">▣</div>
        <div>
            <h3>Informasi Venue</h3>
            <p>Lengkapi informasi venue agar mudah ditemukan oleh pelanggan.</p>
        </div>
    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ route('admin.venue-profile.update') }}"
        class="venue-form-grid"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="venue_id" value="{{ $venue->id }}">

        {{-- ================= IDENTITAS VENUE ================= --}}
        <div class="venue-form-group full-width">
            <div style="display:flex;align-items:center;gap:10px;padding:14px 0 6px;border-top:1px dashed #e3ede6;margin-top:4px;">
                <span style="width:32px;height:32px;border-radius:9px;background:#e8f8ed;color:#07883e;display:grid;place-items:center;">
                    <i class="fa-solid fa-building"></i>
                </span>
                <div>
                    <strong style="display:block;color:#164b31;font-size:15px;">Identitas Venue</strong>
                    <small style="color:#7b8b81;">Nama venue selalu menjadi judul utama, bukan nama perusahaan.</small>
                </div>
            </div>
        </div>

        <div class="venue-form-group">
            <label for="name">
                Nama Venue <span class="required">*</span>
            </label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $venue->name) }}"
                maxlength="150"
                placeholder="Contoh: Venue Rajasinga"
                required
            >
            @error('name')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group">
            <label for="contact_phone">Nomor Kontak Venue</label>
            <input
                type="text"
                id="contact_phone"
                name="contact_phone"
                value="{{ old('contact_phone', $venue->contact_phone) }}"
                maxlength="30"
                placeholder="Contoh: 081234567890"
            >
            @error('contact_phone')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group full-width">
            <label for="address">
                Alamat Lengkap Venue <span class="required">*</span>
            </label>
            <input
                type="text"
                id="address"
                name="address"
                value="{{ old('address', $venue->address) }}"
                maxlength="1000"
                placeholder="Masukkan alamat lengkap venue"
                required
            >
            <span class="venue-form-help">Alamat venue berbeda dengan alamat perusahaan.</span>
            @error('address')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group">
            <label for="city">Kota</label>
            <input type="text" id="city" name="city" value="{{ old('city', $venue->city) }}" maxlength="100">
        </div>

        <div class="venue-form-group">
            <label for="province">Provinsi</label>
            <input type="text" id="province" name="province" value="{{ old('province', $venue->province) }}" maxlength="100">
        </div>

        <div class="venue-form-group full-width">
            <label for="description">Deskripsi Venue</label>
            <textarea
                id="description"
                name="description"
                rows="5"
                placeholder="Ceritakan fasilitas, keunggulan, dan informasi venue Anda..."
            >{{ old('description', $venue->description) }}</textarea>
            @error('description')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-sports-section">
            <div class="venue-sports-title">Jenis Olahraga</div>
            <div class="venue-sports-description">Pilih jenis olahraga yang tersedia di venue.</div>
            @php
                $selectedSports = old('sports', $venue->sports ?? []);
                if (!is_array($selectedSports)) {
                    $selectedSports = [];
                }
            @endphp
            <div class="venue-sports-grid">
                @foreach(['Badminton', 'Futsal'] as $sport)
                    <div class="venue-sport-option">
                        <input
                            type="checkbox"
                            id="sport_{{ Str::slug($sport) }}"
                            name="sports[]"
                            value="{{ $sport }}"
                            @checked(in_array($sport, $selectedSports))
                        >
                        <label for="sport_{{ Str::slug($sport) }}">{{ $sport }}</label>
                    </div>
                @endforeach
            </div>
            @error('sports')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-sports-section">
            <div class="venue-sports-title">Fasilitas Venue</div>
            <div class="venue-sports-description">Fasilitas umum yang tersedia di area venue.</div>
            @php $selectedFacilities = old('facilities', $venue->facilities ?? []); @endphp
            <div class="venue-sports-grid">
                @foreach(['Parkir motor','Parkir mobil','Toilet','Musholla','Ruang ganti','Ruang tunggu','Tribun','Lampu malam','WiFi','Kantin','Penyewaan perlengkapan'] as $facility)
                    <div class="venue-sport-option">
                        <input type="checkbox" id="facility_{{ Str::slug($facility) }}" name="facilities[]" value="{{ $facility }}" @checked(in_array($facility, $selectedFacilities))>
                        <label for="facility_{{ Str::slug($facility) }}">{{ $facility }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ================= OPERASIONAL ================= --}}
        <div class="venue-form-group full-width">
            <div style="display:flex;align-items:center;gap:10px;padding:18px 0 6px;border-top:1px dashed #e3ede6;margin-top:12px;">
                <span style="width:32px;height:32px;border-radius:9px;background:#e8f8ed;color:#07883e;display:grid;place-items:center;">
                    <i class="fa-regular fa-clock"></i>
                </span>
                <div>
                    <strong style="display:block;color:#164b31;font-size:15px;">Status &amp; Jam Operasional</strong>
                    <small style="color:#7b8b81;">Status operasional berbeda dari ketersediaan jadwal lapangan.</small>
                </div>
            </div>
        </div>

        <div class="venue-form-group">
            <label for="operational_status">Status Operasional <span class="required">*</span></label>
            <select id="operational_status" name="operational_status" required>
                <option value="Beroperasi" @selected($selectedStatus === 'Beroperasi')>Beroperasi</option>
                <option value="Tutup Sementara" @selected($selectedStatus === 'Tutup Sementara')>Tutup Sementara</option>
                <option value="Tutup Permanen" @selected($selectedStatus === 'Tutup Permanen')>Tutup Permanen</option>
            </select>
            @error('operational_status')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group">
            <label for="opening_time">Jam Buka</label>
            <input type="time" id="opening_time" name="opening_time" value="{{ $openingTime }}">
        </div>

        <div class="venue-form-group">
            <label for="closing_time">Jam Tutup</label>
            <input type="time" id="closing_time" name="closing_time" value="{{ $closingTime }}">
        </div>

        <div class="venue-form-group full-width">
            <label>Hari Operasional</label>
            <div class="venue-sports-grid">
                @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $day)
                    <div class="venue-sport-option">
                        <input type="checkbox" id="day_{{ Str::slug($day) }}" name="operating_days[]" value="{{ $day }}" @checked(in_array($day, $selectedOperatingDays, true))>
                        <label for="day_{{ Str::slug($day) }}">{{ $day }}</label>
                    </div>
                @endforeach
            </div>
            <span class="venue-form-help">Kosongkan untuk dianggap beroperasi setiap hari.</span>
        </div>

        {{-- ================= LOKASI PETA ================= --}}
        <div class="venue-form-group full-width">
            <div style="display:flex;align-items:center;gap:10px;padding:18px 0 6px;border-top:1px dashed #e3ede6;margin-top:12px;">
                <span style="width:32px;height:32px;border-radius:9px;background:#e8f8ed;color:#07883e;display:grid;place-items:center;">
                    <i class="fa-solid fa-map-location-dot"></i>
                </span>
                <div>
                    <strong style="display:block;color:#164b31;font-size:15px;">Lokasi Peta</strong>
                    <small style="color:#7b8b81;">Tautan dan koordinat untuk pencarian venue terdekat.</small>
                </div>
            </div>
        </div>

        <div class="venue-form-group full-width">
            <label for="google_maps_url">Link Google Maps</label>
            <input
                type="url"
                id="google_maps_url"
                name="google_maps_url"
                value="{{ old('google_maps_url', $venue->google_maps_url) }}"
                maxlength="500"
                placeholder="https://maps.google.com/..."
            >
            @error('google_maps_url')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group">
            <label for="latitude">Latitude</label>
            <input type="number" step="any" id="latitude" name="latitude" value="{{ old('latitude', $venue->latitude) }}" placeholder="Contoh: -6.331234">
            @error('latitude')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group">
            <label for="longitude">Longitude</label>
            <input type="number" step="any" id="longitude" name="longitude" value="{{ old('longitude', $venue->longitude) }}" placeholder="Contoh: 108.324567">
            @error('longitude')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- ================= INFORMASI PERUSAHAAN ================= --}}
        <div class="venue-form-group full-width">
            <div style="display:flex;align-items:center;gap:10px;padding:18px 0 6px;border-top:1px dashed #e3ede6;margin-top:12px;">
                <span style="width:32px;height:32px;border-radius:9px;background:#eef4ff;color:#3566b8;display:grid;place-items:center;">
                    <i class="fa-solid fa-briefcase"></i>
                </span>
                <div>
                    <strong style="display:block;color:#164b31;font-size:15px;">Informasi Perusahaan <span style="font-weight:600;color:#7b8b81;">(opsional)</span></strong>
                    <small style="color:#7b8b81;">Terpisah dari identitas venue. Kosongkan jika dikelola perorangan.</small>
                </div>
            </div>
        </div>

        <div class="venue-form-group">
            <label for="entity_type">Jenis Usaha</label>
            <select id="entity_type" name="entity_type">
                <option value="Perorangan" @selected($selectedEntity === 'Perorangan')>Perorangan</option>
                <option value="Badan Usaha" @selected($selectedEntity === 'Badan Usaha')>Badan Usaha</option>
            </select>
        </div>

        <div class="venue-form-group js-company-field">
            <label for="business_type">Jenis Badan Usaha</label>
            <input type="text" id="business_type" name="business_type" value="{{ old('business_type', $venue->business_type) }}" maxlength="80">
        </div>

        <div class="venue-form-group full-width js-company-field">
            <label for="company_name">Nama Perusahaan / Usaha</label>
            <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $venue->company_name) }}" maxlength="180">
        </div>

        <div class="venue-form-group js-company-field">
            <label for="nib">Nomor NIB</label>
            <input type="text" id="nib" name="nib" value="{{ old('nib', $venue->nib) }}" maxlength="100">
        </div>

        <div class="venue-form-group js-company-field">
            <label for="company_phone">Telepon Perusahaan</label>
            <input type="text" id="company_phone" name="company_phone" value="{{ old('company_phone', $venue->company_phone) }}" maxlength="30">
        </div>

        <div class="venue-form-group full-width js-company-field">
            <label for="company_address">Alamat Perusahaan</label>
            <textarea id="company_address" name="company_address" rows="2">{{ old('company_address', $venue->company_address) }}</textarea>
            <span class="venue-form-help">Alamat perusahaan berbeda dengan alamat venue.</span>
        </div>

        <div class="venue-form-group full-width js-company-field">
            <label for="company_description">Deskripsi Perusahaan</label>
            <textarea id="company_description" name="company_description" rows="3">{{ old('company_description', $venue->company_description) }}</textarea>
        </div>

        {{-- ================= GALERI FOTO ================= --}}
        <div class="venue-form-group full-width">
            <div style="display:flex;align-items:center;gap:10px;padding:18px 0 6px;border-top:1px dashed #e3ede6;margin-top:12px;">
                <span style="width:32px;height:32px;border-radius:9px;background:#e8f8ed;color:#07883e;display:grid;place-items:center;">
                    <i class="fa-solid fa-images"></i>
                </span>
                <div>
                    <strong style="display:block;color:#164b31;font-size:15px;">Galeri Foto Venue</strong>
                    <small style="color:#7b8b81;">Foto utama menjadi cover. Pilih salah satu foto sebagai cover, atau unggah foto baru.</small>
                </div>
            </div>
        </div>

        @if($galleryPaths->count())
            <div class="venue-form-group full-width">
                <label>Foto Tersimpan</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-top:8px;">
                    @foreach($galleryPaths as $path)
                        @php $isCover = $venue->photo === $path; @endphp
                        <div style="position:relative;border:2px solid {{ $isCover ? '#12834e' : '#e2eee6' }};border-radius:14px;overflow:hidden;background:#f4fbf6;">
                            <img src="{{ $pathToUrl($path) }}" alt="Foto venue" style="width:100%;height:120px;object-fit:cover;display:block;">
                            <label style="display:flex;align-items:center;gap:6px;padding:8px;font-size:11px;color:#29523a;font-weight:700;cursor:pointer;">
                                <input type="radio" name="cover_photo" value="{{ $path }}" @checked($isCover)>
                                {{ $isCover ? 'Cover aktif' : 'Jadikan cover' }}
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;padding:0 8px 8px;font-size:11px;color:#b23b3b;font-weight:700;cursor:pointer;">
                                <input type="checkbox" name="remove_gallery[]" value="{{ $path }}">
                                Hapus foto
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="venue-form-group full-width">
                <div style="padding:22px;border:1px dashed #b9d9c2;border-radius:14px;background:#f3fbf5;text-align:center;color:#748279;">
                    <i class="fa-solid fa-image" style="font-size:28px;color:#168b50;"></i>
                    <p style="margin:8px 0 0;">Belum ada foto galeri. Unggah foto utama dan beberapa sudut venue.</p>
                </div>
            </div>
        @endif

        <div class="venue-form-group">
            <label for="photo">Unggah Foto Utama Baru</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
            <span class="venue-form-help">Foto baru akan menjadi cover. Format JPG/PNG/WEBP maksimal 5 MB.</span>
            @error('photo')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-group">
            <label for="gallery_files">Unggah Foto Galeri Tambahan</label>
            <input type="file" id="gallery_files" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple>
            <span class="venue-form-help">Bisa pilih beberapa foto sekaligus (sudut venue, fasilitas, area parkir). Maksimal 20 foto.</span>
            @error('gallery_files')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
            @error('gallery_files.*')
                <span class="venue-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="venue-form-footer">
            <div class="venue-footer-note">
                <span style="color:#e04b4b;">*</span>
                Wajib diisi. Pastikan informasi venue sudah benar.
            </div>

            <button type="submit" class="venue-save-button">
                <span>✓</span>
                Simpan Profil Venue
            </button>
        </div>

    </form>
</div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const entity = document.getElementById('entity_type');
    const companyFields = document.querySelectorAll('.js-company-field');

    function updateCompanyFields() {
        if (!entity) return;

        const isCompany = entity.value === 'Badan Usaha';

        companyFields.forEach(function (field) {
            field.style.display = isCompany ? '' : 'none';

            field.querySelectorAll('input, textarea, select').forEach(function (input) {
                input.disabled = !isCompany;
            });
        });
    }

    if (entity) {
        entity.addEventListener('change', updateCompanyFields);
        updateCompanyFields();
    }

    const input = document.getElementById('photo');
    if (!input) return;

    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            this.value = '';
            alert('Pilih file gambar JPG, PNG, atau WEBP.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            this.value = '';
            alert('Ukuran foto maksimal 5 MB.');
        }
    });
});
</script>

@endsection