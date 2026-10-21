@extends('layouts.admin', ['title' => 'Pembayaran', 'heading' => 'Pembayaran'])
@section('content')
<div class="data-table">
    <table>
        <thead><tr><th>No</th><th>Nama User</th><th>Lapangan</th><th>Jadwal</th><th>Total</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr></thead>
        <tbody>
            @foreach($payments as $i => $payment)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $payment->user?->name }}</td>
                <td>{{ $payment->booking?->lapangan?->nama }}</td>
                <td>{{ optional($payment->booking?->booking_date)->format('d M Y') }} {{ $payment->booking?->start_time }}</td>
                <td>Rp {{ number_format($payment->amount,0,',','.') }}</td>
                <td>{{ $payment->status }}</td>
                <td>@if($payment->proof_image)<a href="{{ asset($payment->proof_image) }}" target="_blank">Periksa</a>@endif</td>
                <td>@if($payment->status === 'Lunas')<span class="status-active">Lunas otomatis via Midtrans</span>@else<span>Menunggu notifikasi Midtrans</span>@endif</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
