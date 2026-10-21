@extends('layouts.admin', ['title' => 'Manajemen User', 'heading' => 'Management User'])
@section('content')
<div class="section-actions clean-actions">
    <div></div>
    <a class="btn-ui btn-green" href="{{ route('admin.users.create') }}"><i class="fa-solid fa-plus"></i> Tambah User</a>
</div>
<div class="data-table">
    <table>
        <thead>
            <tr><th>No</th><th>Nama</th><th>Email/no.hp</th><th>Jumlah Booking</th><th>Poin</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @foreach($users as $i => $user)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}<br>{{ $user->phone }}</td>
                <td>{{ $user->bookings_count }}</td>
                <td>{{ $user->points }}</td>
                <td>{{ ucfirst($user->role) }}</td>
                <td><span class="{{ $user->status === 'Aktif' ? 'status-active' : 'status-inactive' }}">{{ $user->status }}</span></td>
                <td>
                    <div class="btn-row wrap">
                        <a class="btn-ui btn-green" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}">@csrf<button class="btn-ui btn-gray">{{ $user->status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                        @if($user->role !== 'admin')
                        <form method="POST" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('Hapus user ini?')">@csrf @method('DELETE')<button class="btn-ui btn-red">Hapus</button></form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
