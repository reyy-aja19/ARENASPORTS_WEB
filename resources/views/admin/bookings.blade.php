@extends('layouts.admin', ['title' => 'Manajemen Booking', 'heading' => 'Management Booking'])
@section('content')
<form class="surface compact-form" action="{{ route('admin.bookings.store') }}" method="POST">
    @csrf
    <div class="grid-4">
        <div class="form-group"><label>User</label><select class="select-ui" name="user_id">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
        <div class="form-group"><label>Lapangan</label><select class="select-ui" name="lapangan_id">@foreach($courts as $court)<option value="{{ $court->id }}">{{ $court->nama }}</option>@endforeach</select></div>
        <div class="form-group"><label>Metode Pembayaran</label><select class="select-ui" name="payment_method"><option>Transfer</option><option>Virtual Account</option><option>E-Wallet</option><option>Cash</option></select></div>
        <div class="form-group"><label>Tanggal</label><input class="input-ui" type="date" name="booking_date" value="{{ date('Y-m-d', strtotime('+1 day')) }}"></div>
        <div class="form-group"><label>Jam Mulai</label><input class="input-ui" type="time" name="start_time" value="08:00"></div>
        <div class="form-group"><label>Jam Selesai</label><input class="input-ui" type="time" name="end_time" value="09:00"></div>
        <div class="form-group"><label>Durasi (jam)</label><input class="input-ui" type="number" min="1" name="hours" value="1"></div>
        <div class="form-group"><label>Status</label><select class="select-ui" name="status"><option>Menunggu</option><option>Dibatalkan</option></select></div>
    </div>
    <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green" type="submit">Tambah Booking</button></div>
</form>
<div class="data-table mt-16">
    <table>
        <thead><tr><th>No</th><th>Nama</th><th>Pembayaran</th><th>Booking</th><th>Waktu</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @foreach($bookings as $i => $booking)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $booking->user?->name }}</td>
                <td>{{ $booking->payment_method }}</td>
                <td>{{ $booking->lapangan?->nama }}</td>
                <td>{{ $booking->booking_date->format('d M Y') }} {{ $booking->start_time }}-{{ $booking->end_time }}</td>
                <td>Rp {{ number_format($booking->total_price,0,',','.') }}</td>
                <td>{{ $booking->status }}</td>
                <td><div class="btn-row wrap"><form method="POST" action="{{ route('admin.bookings.delete', $booking) }}" onsubmit="return confirm('Hapus booking ini?')">@csrf @method('DELETE')<button class="btn-ui btn-red">Hapus</button></form></div></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
