@extends('layouts.user')

@section('content')
<style>
    .pay-shell{max-width:760px;margin:0 auto;padding-bottom:40px}
    .pay-head{text-align:center;margin-bottom:22px}
    .pay-head span{display:inline-block;font-size:11px;letter-spacing:.16em;font-weight:800;color:#16846b;margin-bottom:8px}
    .pay-head h2{margin:0 0 8px;font-size:clamp(24px,3vw,32px);font-weight:800}
    .pay-head p{margin:0;color:#687385}
    .pay-card{background:#fff;border:1px solid #e3e8ed;border-radius:20px;box-shadow:0 8px 28px rgba(25,42,60,.05);overflow:hidden}
    .pay-body{padding:24px}
    .pay-amount{text-align:center;padding:22px;border-radius:16px;background:linear-gradient(135deg,#0f6b4d,#149b6d);color:#fff;margin-bottom:22px}
    .pay-amount small{display:block;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;margin-bottom:8px}
    .pay-amount strong{font-size:34px;font-weight:800;letter-spacing:-.5px}
    .pay-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .pay-item{padding:13px;border-radius:12px;background:#f7f9fb;min-width:0}
    .pay-item small{display:block;font-size:11px;color:#718096;margin-bottom:6px}
    .pay-item strong{display:block;font-size:14px;overflow-wrap:anywhere}
    .pay-methods{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0}
    .pay-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:#eef6ff;color:#2b5c8a;font-size:12px;font-weight:700}
    .pay-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}
    .pay-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:11px;padding:12px 18px;background:#13785f;color:#fff;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none}
    .pay-btn:hover{filter:brightness(.94);color:#fff}
    .pay-btn.secondary{background:#edf5f2;color:#176e5b}
    .pay-note{margin:16px 0 0;padding:13px 15px;border-radius:10px;background:#fff8e8;border:1px solid #f2dfb3;color:#805a12;font-size:12px;line-height:1.7}
    .pay-status{margin-top:14px;font-size:12px;color:#687385;text-align:center;min-height:18px}
    @media(max-width:600px){.pay-grid{grid-template-columns:1fr}.pay-amount strong{font-size:28px}.pay-actions .pay-btn{width:100%}}
</style>

<div class="pay-shell">
    <div class="pay-head">
        <span>ARENASPORTS · PEMBAYARAN</span>
        <h2>Pembayaran Booking</h2>
        <p>Pilih metode pembayaran pada jendela Midtrans. Halaman ini tidak akan berpindah.</p>
    </div>

    <div class="pay-card">
        <div class="pay-body">
            <div class="pay-amount">
                <small>{{ __('Total yang harus dibayar') }}</small>
                <strong>Rp {{ number_format($amount, 0, ',', '.') }}</strong>
            </div>

            <div class="pay-grid">
                <div class="pay-item">
                    <small>{{ __('VENUE') }}</small>
                    <strong>{{ $booking?->lapangan?->venue?->name ?? $booking?->lapangan?->nama ?? 'ArenaSports' }}</strong>
                </div>
                <div class="pay-item">
                    <small>{{ __('LAPANGAN') }}</small>
                    <strong>{{ $booking?->lapangan?->nama ?? '-' }}</strong>
                </div>
                <div class="pay-item">
                    <small>{{ __('TANGGAL BERMAIN') }}</small>
                    <strong>{{ $booking?->booking_date?->format('d M Y') ?? '-' }}</strong>
                </div>
                <div class="pay-item">
                    <small>{{ __('JAM BERMAIN') }}</small>
                    <strong>
                        {{ $booking ? substr((string) $booking->start_time, 0, 5) . '–' . substr((string) $booking->end_time, 0, 5) : '-' }}
                    </strong>
                </div>
                <div class="pay-item">
                    <small>{{ __('KODE PEMBAYARAN') }}</small>
                    <strong>PAY-{{ str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) }}</strong>
                </div>
                <div class="pay-item">
                    <small>{{ __('STATUS') }}</small>
                    <strong>{{ $payment->status ?? 'Menunggu' }}</strong>
                </div>
            </div>

            <div class="pay-methods">
                <span class="pay-chip"><i class="fa-solid fa-qrcode"></i> QRIS</span>
                <span class="pay-chip"><i class="fa-solid fa-wallet"></i> GoPay</span>
                <span class="pay-chip"><i class="fa-solid fa-mobile-screen"></i> DANA</span>
                <span class="pay-chip"><i class="fa-solid fa-building-columns"></i> Virtual Account</span>
            </div>

            <p class="pay-note">
                <i class="fa-solid fa-circle-info"></i>
                Di laptop/desktop, GoPay dan DANA ditampilkan sebagai QRIS untuk dipindai lewat aplikasi.
                Di ponsel, aplikasi GoPay/DANA dapat langsung terbuka dari halaman pembayaran.
            </p>

            <div class="pay-actions">
                <button type="button" class="pay-btn" id="payNow">
                    <i class="fa-solid fa-credit-card"></i>
                    {{ __('Buka Pembayaran') }}
                </button>
                <a class="pay-btn secondary" href="{{ route('user.bookings') }}">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ __('Kembali ke Booking Saya') }}
                </a>
            </div>

            <div class="pay-status" id="payStatus"></div>
        </div>
    </div>
</div>

<script
    src="{{ $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ $clientKey }}"></script>

<script>
(function () {
    const token = @json($payment->snap_token);
    const statusUrl = @json($statusUrl);
    const statusJsonUrl = @json($statusJsonUrl);
    const statusEl = document.getElementById('payStatus');
    const payButton = document.getElementById('payNow');
    let opening = false;

    function setStatus(message) {
        if (statusEl) statusEl.textContent = message;
    }

    async function pollPaymentStatus(attempts, delayMs, pendingMessage) {
        for (let i = 0; i < attempts; i++) {
            try {
                const response = await fetch(statusJsonUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.paid && data.redirect_url) {
                        setStatus('Pembayaran berhasil. Mengalihkan ke bukti pembayaran...');
                        window.location.href = data.redirect_url;
                        return true;
                    }
                }
            } catch (e) {
                console.warn('Status poll failed', e);
            }

            setStatus(pendingMessage + ' (' + (i + 1) + '/' + attempts + ')');
            await new Promise(resolve => setTimeout(resolve, delayMs));
        }

        window.location.href = statusUrl;
        return false;
    }

    function openSnap() {
        if (opening) return;
        opening = true;

        if (typeof window.snap === 'undefined' || !token) {
            setStatus('Halaman pembayaran belum siap. Muat ulang halaman ini.');
            opening = false;
            return;
        }

        setStatus('Membuka jendela pembayaran...');

        window.snap.pay(token, {
            onSuccess: function () {
                setStatus('Pembayaran berhasil. Memverifikasi status...');
                pollPaymentStatus(8, 2500, 'Memverifikasi pembayaran');
            },
            onPending: function () {
                setStatus('Pembayaran sedang diproses. Memeriksa status otomatis...');
                pollPaymentStatus(12, 4000, 'Menunggu konfirmasi pembayaran (DANA/QRIS/VA dapat butuh beberapa saat)');
            },
            onError: function (result) {
                opening = false;
                setStatus('Pembayaran gagal. Silakan coba lagi atau gunakan metode lain.');
                console.error('Midtrans error', result);
            },
            onClose: function () {
                opening = false;
                setStatus('Jendela pembayaran ditutup. Jika sudah bayar, tunggu sebentar lalu klik "Buka Pembayaran" atau muat ulang halaman.');
            }
        });
    }

    if (payButton) {
        payButton.addEventListener('click', openSnap);
    }

    window.addEventListener('load', function () {
        setTimeout(openSnap, 400);
    });
})();
</script>
@endsection
