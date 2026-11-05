@extends('layouts.admin', ['title' => 'Edit Lapangan', 'heading' => 'Edit Lapangan'])
@section('content')
@php
    $fallbackCourtImage = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221200%22 height=%22675%22 viewBox=%220 0 1200 675%22%3E%3Crect width=%221200%22 height=%22675%22 rx=%2236%22 fill=%22%23e9f7ec%22/%3E%3Crect x=%2260%22 y=%2260%22 width=%221080%22 height=%22555%22 rx=%2228%22 fill=%22%230fa741%22 opacity=%22.15%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-family=%22Arial%22 font-size=%2254%22 text-anchor=%22middle%22 fill=%22%230f6e31%22%3EPreview Lapangan%3C/text%3E%3C/svg%3E";
    $defaultFacilities = ['Parkir motor','Toilet','Musholla','Jual minuman','Jual makanan','WiFi','Tribun','Lampu malam','Ruang tunggu'];
    $selectedFacilities = old('fasilitas', $court->fasilitas ?? []);
    $gallery = collect($court->foto_gallery ?? [])->filter()->values();
@endphp
<form class="form-card wide-form" action="{{ route('admin.courts.update', $court) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @if(session('admin_role') === 'venue_admin' && ($venues ?? collect())->count() > 1)
    <div class="form-group"><label>Venue</label><select class="select-ui" name="venue_id" required>@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected(old('venue_id', $court->venue_id ?? '') == $venue->id)>{{ $venue->name }}</option>@endforeach</select><small>Pilih venue tempat lapangan ini berada.</small></div>
    @elseif(session('admin_role') === 'venue_admin')
        <input type="hidden" name="venue_id" value="{{ ($venues ?? collect())->first()?->id }}">
    @endif
    @if(session('admin_role') === 'superadmin')
    <div class="form-group"><label>Pemilik Admin Venue (opsional)</label><select class="select-ui" name="owner_admin_id"><option value="">Belum ditugaskan</option>@foreach(($venueAdmins ?? []) as $venueAdmin)<option value="{{ $venueAdmin->id }}" {{ old('owner_admin_id', $court->owner_admin_id) == $venueAdmin->id ? 'selected' : '' }}>{{ $venueAdmin->name }} ({{ $venueAdmin->email }})</option>@endforeach</select><small>Venue yang belum ditugaskan hanya bisa dikelola superadmin.</small></div>
    @endif
    <div class="grid-2">
        <div class="form-group"><label>Nama Lapangan</label><input class="input-ui" name="nama" value="{{ old('nama', $court->nama) }}"></div>
        <div class="form-group"><label>Jenis Olahraga</label><select class="select-ui" name="jenis">@foreach(['Badminton','Futsal','Basket','Voli'] as $jenis)<option value="{{ $jenis }}" {{ old('jenis', $court->jenis) === $jenis ? 'selected' : '' }}>{{ $jenis }}</option>@endforeach</select></div>
        <div class="form-group"><label>Lokasi</label><input class="input-ui" name="lokasi" value="{{ old('lokasi', $court->lokasi) }}"></div>
        <div class="form-group"><label>Latitude (opsional, untuk rekomendasi terdekat)</label><input class="input-ui" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude', $court->latitude) }}" placeholder="-6.3270"></div>
        <div class="form-group"><label>Longitude (opsional)</label><input class="input-ui" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude', $court->longitude) }}" placeholder="108.3200"></div>
        <div class="form-group"><label>Harga per Jam</label><input class="input-ui" name="harga" type="number" value="{{ old('harga', $court->harga) }}"></div>
        <div class="form-group"><label>Rating</label><input class="input-ui" name="rating" type="number" min="0" max="5" step="0.1" value="{{ old('rating', $court->rating) }}"></div>
        <div class="form-group"><label>Status</label><select class="select-ui" name="status">@foreach(['Tersedia','Perawatan','Penuh'] as $status)<option value="{{ $status }}" {{ old('status', $court->status) === $status ? 'selected' : '' }}>{{ $status }}</option>@endforeach</select></div>
    </div>
    <div class="form-group"><label>Deskripsi</label><textarea class="textarea-ui" name="deskripsi">{{ old('deskripsi', $court->deskripsi) }}</textarea></div>

    <div class="form-group"><label>Fasilitas</label><div class="facility-checkboxes">@foreach($defaultFacilities as $facility)<label><input type="checkbox" name="fasilitas[]" value="{{ $facility }}" {{ in_array($facility, $selectedFacilities) ? 'checked' : '' }}> {{ $facility }}</label>@endforeach</div></div>

    <div class="form-group preview-shell {{ $court->foto ? 'has-image' : '' }}">
        <label>Foto Utama Saat Ini</label>
        <img id="court-preview-image" class="preview-img" src="{{ $court->foto ? asset($court->foto) : $fallbackCourtImage }}" alt="{{ $court->nama }}">
    </div>
    @if($gallery->count())
        <div class="form-group"><label>Slide Foto Tersimpan</label><div class="gallery-preview-row">@foreach($gallery as $photo)<img src="{{ asset($photo) }}" alt="Slide {{ $loop->iteration }}">@endforeach</div></div>
    @endif
    <div class="grid-2">
        <div class="form-group"><label>Ganti Foto Utama</label><input class="input-ui" name="foto" type="file" accept="image/*" data-preview="court-preview-image"></div>
        <div class="form-group"><label>Tambah Slide Foto</label><input class="input-ui" name="foto_gallery[]" type="file" accept="image/*" multiple></div>
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a class="btn-ui btn-gray" href="{{ route('admin.courts') }}">Batal</a>
        <button class="btn-ui btn-green" type="submit">Update</button>
    </div>
</form>
@endsection
