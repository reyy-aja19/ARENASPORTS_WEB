@extends('layouts.user')

@section('content')
<style>
    .success-shell{max-width:760px;margin:0 auto;padding-bottom:40px}
    .success-card{background:#fff;border:1px solid #e3e8ed;border-radius:22px;box-shadow:0 12px 36px rgba(25,42,60,.07);overflow:hidden}
    .success-hero{padding:36px 24px 28px;text-align:center;background:linear-gradient(160deg,#e9f9f1,#f8fffc);border-bottom:1px solid #e6f2ea}
    .success-icon{width:78px;height:78px;margin:0 auto 16px;display:grid;place-items:center;border-radius:50%;background:#16a06a;color:#fff;font-size:34px;box-shadow:0 10px 24px rgba(22,160,106,.28)}
    .success-hero h2{margin:0 0 8px;font-size:clamp(24px,3vw,30px);font-weight:800;color:#113a28}
    .success-hero p{margin:0;color:#537567;font-size:14px}
    .success-amount{margin:18px auto 0;display:inline-flex;align-items:baseline;gap:8px;padding:12px 20px;border-radius:14px;background:#fff;border:1px solid #cfeadd;color:#0f6b4d;font-weight:800;font-size:26px}
    .success-amount small{font-size:12px;font-weight:700;color:#5b7a6c}
    .success-body{padding:24px}
    .success-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .success-item{padding:13px;border-radius:12px;background:#f7f9fb;min-width:0}
    .success-item small{display:block;font-size:11px;color:#718096;margin-bottom:6px}
    .success-item strong{display:block;font-size:14px;overflow-wrap:anywhere}
    .success-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px}
    .success-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:11px;padding:12px 18px;background:#13785f;color:#fff;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none}
    .success-btn:hover{filter:brightness(.94);color:#fff}
    .success-btn.secondary{background:#edf5f2;color:#176e5b}
    .success-note{margin:18px 0 0;padding:13px 15px;border-radius:10px;background:#eef6ff;border:1px solid #cfe3f8;color:#2b5c8a;font-size:12px;line-height:1.7}
    @media(max-width:600px){.success-grid{grid-template-columns:1fr}.success-amount{font-size:22px}.success-actions .success-btn{width:100%}}
</style>

<div class="success-shell">
    <div class="success-card">
        <div class="success-hero">
            <div class="success-icon"><i class="fa-solid fa-check"></i></div>
            <h2>{{ __('Pembayaran Berhasil!') }}</h2>
            <p>{{ __('Pembayaran sejumlah') }}</p>

            <div class="success-amount">
                <small>Rp</small>
                {{ number_format($amount, 0, ',', '.') }}
                <small>{{ __('selesai') }}</small>
            </div>
        </div>

        <div class="success-body">
            <div class="success-grid">
                <div class="success-item">
                    <small>{{ __('VENUE') }}</small>
                    <strong>{{ $booking?->lapangan?->venue?->name ?? $booking?->lapangan?->nama ?? 'ArenaSports' }}</strong>
                </div>
                <div class="success-item">
                    <small>{{ __('LAPANGAN') }}</small>
                    <strong>{{ $booking?->lapangan?->nama ?? '-' }}</strong>
                </div>
                <div class="success-item">
                    <small>{{ __('TANGGAL BERMAIN') }}</small>
                    <strong>{{ $booking?->booking_date?->format('d M Y') ?? '-' }}</strong>
                </div>
                <div class="success-item">
                    <small>{{ __('JAM BERMAIN') }}</small>
                    <strong>
                        {{ $booking ? substr((string) $booking->start_time, 0, 5) . '–' . substr((string) $booking->end_time, 0, 5) : '-' }}
                    </strong>
                </div>
                <div class="success-item">
                    <small>{{ __('METODE PEMBAYARAN') }}</small>
                    <strong>{{ $payment->method ?? 'Midtrans' }}</strong>
                </div>
                <div class="success-item">
                    <small>{{ __('ID TRANSAKSI') }}</small>
                    <strong>{{ $payment->transaction_id ?: ($payment->midtrans_order_id ?: '-') }}</strong>
                </div>
                <div class="success-item">
                    <small>{{ __('WAKTU PEMBAYARAN') }}</small>
                    <strong>{{ $payment->paid_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i') }}</strong>
                </div>
                <div class="success-item">
                    <small>{{ __('KODE PEMBAYARAN') }}</small>
                    <strong>PAY-{{ str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) }}</strong>
                </div>
            </div>

            <div class="success-actions">
                @if($booking)
                    <a class="success-btn" href="{{ route('user.bookings.ticket', $booking) }}">
                        <i class="fa-solid fa-ticket"></i>
                        {{ __('Lihat Tiket & QR') }}
                    </a>
                @endif

                <a class="success-btn secondary" href="{{ route('user.bookings') }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    {{ __('Booking Saya') }}
                </a>
            </div>

            <p class="success-note">
                <i class="fa-solid fa-circle-info"></i>
                {{ __('Tunjukkan tiket dan QR code kepada petugas venue saat tiba. Simpan halaman ini sebagai bukti pembayaran.') }}
            </p>
        </div>
    </div>
</div>
@endsection
