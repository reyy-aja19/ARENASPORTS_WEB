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
                    <td>
                        @php
                            $paymentStatus = strtolower((string) $payment->status);
                            $isPaid = in_array($paymentStatus, ['lunas', 'settlement', 'capture', 'success', 'paid'], true);
                            $isPending = in_array($paymentStatus, ['menunggu', 'pending'], true);
                        @endphp
                        @if($isPaid)
                            <span class="status-active">Lunas</span>
                        @elseif($isPending)
                            <span class="status-inactive">Menunggu</span>
                        @else
                            <span class="status-inactive">{{ $payment->status }}</span>
                        @endif
                    </td>
                    <td>
                        @if(strtolower((string) $payment->method) === 'cash' && $payment->status === 'Menunggu')
                            <form method="POST" action="{{ route('admin.payments.verify', $payment) }}"
                                  onsubmit="return confirm('Konfirmasi bahwa uang tunai sudah diterima?')">
                                @csrf
                                <button type="submit" class="btn-ui btn-green">Konfirmasi Cash</button>
                            </form>
                        @elseif(strtolower((string) $payment->method) === 'cash' && $isPaid)
                            <span class="status-active">Cash diterima</span>
                        @elseif(strtolower((string) $payment->method) !== 'cash' && $isPaid)
                            <span class="status-active">Pembayaran Midtrans terverifikasi</span>
                        @elseif(strtolower((string) $payment->method) !== 'cash')
                            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
                                <span style="font-size:12px;color:#64748b;">Status Midtrans: {{ $payment->status }}</span>
                                <form method="POST" action="{{ route('admin.payments.sync', $payment) }}">
                                    @csrf
                                    <button type="submit" class="btn-ui btn-gray" style="padding:6px 12px;font-size:12px;">Cek Status Midtrans</button>
                                </form>
                            </div>
                        @else
                            <span>-</span>
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
