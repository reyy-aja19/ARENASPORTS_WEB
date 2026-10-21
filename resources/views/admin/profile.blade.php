@extends('layouts.admin', ['title' => 'Profil', 'heading' => 'Profil'])
@section('content')
@php
    $initial = strtoupper(substr($profile->name ?? 'A', 0, 1));
    $fallbackAdminPhoto = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22180%22 height=%22180%22 viewBox=%220 0 180 180%22%3E%3Crect width=%22180%22 height=%22180%22 rx=%2236%22 fill=%22%230fa741%22/%3E%3Ctext x=%2250%25%22 y=%2258%25%22 font-size=%2270%22 text-anchor=%22middle%22 fill=%22white%22 font-family=%22Arial%22%3E{$initial}%3C/text%3E%3C/svg%3E";
@endphp
<form class="form-card profile-card" method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
    @csrf
    <div class="profile-top">
        <div class="profile-photo preview-shell has-image">
            <img id="admin-photo-preview" src="{{ $profile->profile_photo ? asset($profile->profile_photo) : $fallbackAdminPhoto }}" alt="{{ $profile->name }}">
        </div>
        <div style="font-size:28px; font-weight:700;">Profil Admin</div>
        <div class="muted" style="margin-top:4px;">Foto admin bisa diganti langsung dari panel ini.</div>
    </div>
    <div class="form-group"><label>Foto Admin</label><input class="input-ui" name="profile_photo" type="file" accept="image/*" data-preview="admin-photo-preview"></div>
    <div class="form-group"><label>Nama Lengkap</label><div class="inline-form-icon"><div class="inline-icon"><i class="fa-regular fa-user"></i></div><input class="input-ui" name="name" value="{{ old('name', $profile->name) }}"></div></div>
    <div class="form-group"><label>Email</label><div class="inline-form-icon"><div class="inline-icon"><i class="fa-regular fa-envelope"></i></div><input class="input-ui" name="email" value="{{ old('email', $profile->email) }}"></div></div>
    <div class="form-group"><label>No Handphone</label><div class="inline-form-icon"><div class="inline-icon"><i class="fa-solid fa-phone"></i></div><input class="input-ui" name="phone" value="{{ old('phone', $profile->phone) }}"></div></div>
    <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green" type="submit">Simpan Profil</button></div>
</form>
@endsection
