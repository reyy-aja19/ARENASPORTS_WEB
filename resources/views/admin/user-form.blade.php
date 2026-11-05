@extends('layouts.admin', ['title' => $mode === 'create' ? 'Tambah User' : 'Edit User', 'heading' => $mode === 'create' ? 'Tambah User' : 'Edit User'])

@section('content')
<form class="form-card" action="{{ $mode === 'create' ? route('admin.users.store') : route('admin.users.update', $user) }}" method="POST">
    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="grid-2-form">
        <div class="form-group">
            <label>Nama</label>
            <input class="input-ui" name="name" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input class="input-ui" type="email" name="email" value="{{ old('email', $user->email) }}" required>
        </div>
        <div class="form-group">
            <label>No HP</label>
            <input class="input-ui" name="phone" value="{{ old('phone', $user->phone) }}">
        </div>
        <div class="form-group">
            <label>Poin</label>
            <input class="input-ui" type="number" min="0" name="points" value="{{ old('points', $user->points ?? 0) }}">
        </div>
        <div class="form-group">
            <label>Role</label>
            <small class="form-help">Superadmin memiliki akses penuh, termasuk pengelolaan akun dan event Open Match.</small>
            <select class="select-ui" name="role">
                @php($roleOptions = ($mode === 'edit' && $user->role === 'venue_admin') ? ['venue_admin' => 'Admin Venue'] : ['user' => 'User', 'admin' => 'Admin', 'superadmin' => 'Superadmin'])
                @foreach($roleOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('role', $user->role ?? 'user') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select class="select-ui" name="status">
                @foreach(['Aktif', 'Nonaktif'] as $status)
                    <option value="{{ $status }}" {{ old('status', $user->status ?? 'Aktif') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>{{ $mode === 'create' ? 'Password' : 'Password Baru' }}</label>
        <input class="input-ui" type="password" name="password" {{ $mode === 'create' ? 'required' : '' }} placeholder="{{ $mode === 'edit' ? 'Kosongkan jika tidak diubah' : '' }}">
    </div>

    <div class="form-actions-right">
        <a class="btn-ui btn-gray" href="{{ route('admin.users') }}">Batal</a>
        <button class="btn-ui btn-green" type="submit">{{ $mode === 'create' ? 'Tambah User' : 'Update User' }}</button>
    </div>
</form>
@endsection
