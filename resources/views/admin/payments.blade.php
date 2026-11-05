@extends('layouts.admin', ['title' => 'Pembayaran', 'heading' => 'Pembayaran'])

@section('content')
<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama User</th>
                <th>Lapangan</th>
                <th>Jadwal</th>
                <th>Metode</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $i => $payment)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $payment->user?->name }}</td>
                    <td>{{ $payment->booking?->lapangan?->nama }}</td>
                    <td>
                        {{ optional($payment->booking?->booking_date)->format('d M Y') }}
                        {{ $payment->booking?->start_time }}
                    </td>
                    <td>{{ $payment->method }}</td>
                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td>{{ $payment->status }}</td>
                    <td>
                        @if(strtolower((string) $payment->method) === 'cash' && $payment->status === 'Menunggu')
                            <form method="POST" action="{{ route('admin.payments.verify', $payment) }}"
                                  onsubmit="return confirm('Konfirmasi bahwa uang tunai sudah diterima?')">
                                @csrf
                                <button type="submit" class="btn-ui btn-green">
                                    Konfirmasi Cash
                                </button>
                            </form>
                        @elseif(strtolower((string) $payment->method) === 'cash' && $payment->status === 'Lunas')
                            <span class="status-active">Cash diterima</span>
                        @else
                            <span>Menunggu notifikasi resmi Midtrans</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada transaksi pembayaran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
