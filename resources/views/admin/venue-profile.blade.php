
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

    <div class="venue-profile-header">
        <h2>Profil Venue</h2>
        <p>Kelola informasi dan identitas venue olahraga Anda.</p>
    </div>

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
            action="{{ route('admin.venue-profile.update') }}"
            class="venue-form-grid"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="venue_id" value="{{ $venue->id }}">

            <div class="venue-form-group full-width"><label for="company_name">Nama Perusahaan / Usaha</label><input type="text" id="company_name" name="company_name" value="{{ old('company_name', $venue->company_name) }}" maxlength="180"></div>
            <div class="venue-form-group"><label for="business_type">Jenis Badan Usaha</label><input type="text" id="business_type" name="business_type" value="{{ old('business_type', $venue->business_type) }}" maxlength="80"></div>
            <div class="venue-form-group"><label for="nib">Nomor NIB</label><input type="text" id="nib" name="nib" value="{{ old('nib', $venue->nib) }}" maxlength="100"></div>
            <div class="venue-form-group full-width"><label for="company_address">Alamat Perusahaan</label><textarea id="company_address" name="company_address" rows="2">{{ old('company_address', $venue->company_address) }}</textarea></div>
            <div class="venue-form-group"><label for="company_phone">Telepon Perusahaan</label><input type="text" id="company_phone" name="company_phone" value="{{ old('company_phone', $venue->company_phone) }}" maxlength="30"></div>
            <div class="venue-form-group full-width"><label for="company_description">Deskripsi Perusahaan</label><textarea id="company_description" name="company_description" rows="3">{{ old('company_description', $venue->company_description) }}</textarea></div>

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
                    Alamat Lengkap <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    value="{{ old('address', $venue->address) }}"
                    maxlength="255"
                    placeholder="Masukkan alamat lengkap venue"
                    required
                >

                @error('address')
                    <span class="venue-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="venue-form-group">
                <label for="operating_hours">Jam Operasional</label>

                <input
                    type="text"
                    id="operating_hours"
                    name="operating_hours"
                    value="{{ old('operating_hours', $venue->operating_hours) }}"
                    maxlength="150"
                    placeholder="Contoh: 07.00–23.00 WIB"
                >

                @error('operating_hours')
                    <span class="venue-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="venue-form-group">
                <label for="google_maps_url">Link Google Maps</label>

                <input
                    type="url"
                    id="google_maps_url"
                    name="google_maps_url"
                    value="{{ old('google_maps_url', $venue->google_maps_url) }}"
                    maxlength="500"
                    placeholder="https://maps.google.com/..."
                >

                <span class="venue-form-help">
                    Masukkan tautan lokasi venue dari Google Maps.
                </span>

                @error('google_maps_url')
                    <span class="venue-field-error">{{ $message }}</span>
                @enderror
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

                <div class="venue-sports-description">
                    Pilih jenis olahraga yang tersedia di venue Anda.
                </div>

                @php
                    $selectedSports = old('sports', $venue->sports ?? []);

                    if (!is_array($selectedSports)) {
                        $selectedSports = [];
                    }
                @endphp

                <div class="venue-sports-grid">
                    @foreach([
                        'Badminton',
                        'Futsal',
                        'Basketball',
                        'Tenis',
                        'Mini Soccer'
                    ] as $sport)

                        <div class="venue-sport-option">
                            <input
                                type="checkbox"
                                id="sport_{{ Str::slug($sport) }}"
                                name="sports[]"
                                value="{{ $sport }}"
                                @checked(in_array($sport, $selectedSports))
                            >

                            <label for="sport_{{ Str::slug($sport) }}">
                                {{ $sport }}
                            </label>
                        </div>

                    @endforeach
                </div>

                @error('sports')
                    <span class="venue-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="venue-sports-section">
                <div class="venue-sports-title">Fasilitas Venue</div>
                <div class="venue-sports-description">Pilih fasilitas yang tersedia di lokasi.</div>
                @php $selectedFacilities = old('facilities', $venue->facilities ?? []); @endphp
                <div class="venue-sports-grid">
                    @foreach(['Parkir motor','Parkir mobil','Toilet','Musholla','Ruang ganti','Ruang tunggu','Tribun','Lampu malam','WiFi','Kantin','Penyewaan perlengkapan'] as $facility)
                        <div class="venue-sport-option"><input type="checkbox" id="facility_{{ Str::slug($facility) }}" name="facilities[]" value="{{ $facility }}" @checked(in_array($facility, $selectedFacilities))><label for="facility_{{ Str::slug($facility) }}">{{ $facility }}</label></div>
                    @endforeach
                </div>
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

@endsection