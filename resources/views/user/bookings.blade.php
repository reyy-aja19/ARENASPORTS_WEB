@extends('layouts.user')

@section('content')
<style>
  .ticket-page{max-width:1100px;margin:0 auto;padding-bottom:28px}
  .ticket-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:22px}
  .ticket-eyebrow{display:block;font-size:11px;letter-spacing:.16em;font-weight:800;color:#16846b;margin-bottom:7px}
  .ticket-heading h2{margin:0 0 6px;font-size:clamp(24px,3vw,32px);font-weight:800}
  .ticket-heading p{margin:0;color:#687385}
  .ticket-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:22px}
  .ticket-summary-item{display:flex;align-items:center;gap:12px;background:var(--surface,#fff);border:1px solid #e7ebef;border-radius:16px;padding:16px}
  .ticket-summary-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:12px;background:#e8f7f2;color:#13785f;font-size:18px}
  .ticket-summary-item small{display:block;color:#6b7280;font-size:12px;margin-bottom:3px}
  .ticket-summary-item strong{font-size:22px}
  .ticket-list{display:grid;gap:18px}
  .ticket-card{background:#fff;border:1px solid #e3e8ed;border-radius:20px;overflow:hidden;box-shadow:0 8px 28px rgba(25,42,60,.05);color:#1c2938}
  .ticket-main{padding:22px}
  .ticket-top{display:flex;align-items:flex-start;gap:14px}
  .ticket-sport-icon{width:48px;height:48px;flex:0 0 48px;display:grid;place-items:center;border-radius:14px;background:#e8f7f2;color:#13785f;font-size:20px}
  .ticket-title{flex:1;min-width:0}
  .ticket-code{font-size:11px;letter-spacing:.12em;font-weight:800;color:#718096}
  .ticket-title h3{margin:5px 0;font-size:20px;font-weight:800}
  .ticket-title p{margin:0;color:#687385;font-size:13px}
  .ticket-status{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;border-radius:999px;padding:7px 11px;font-size:12px;font-weight:800;background:#fff2d6;color:#8b5b00}
  .ticket-status.paid{background:#e4f7ed;color:#167348}
  .ticket-status.failed{background:#fde8e7;color:#a52d2d}
  .ticket-status.neutral{background:#edf1f5;color:#536273}
  .ticket-fields{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:22px}
  .ticket-field{padding:13px;border-radius:12px;background:#f7f9fb;min-width:0}
  .ticket-field small{display:block;font-size:11px;color:#718096;margin-bottom:7px}
  .ticket-field strong{display:block;font-size:14px;overflow-wrap:anywhere}
  .ticket-divider{position:relative;border-top:1px dashed #cbd4dc;margin:0 22px}
  .ticket-divider:before,.ticket-divider:after{content:"";position:absolute;top:-10px;width:20px;height:20px;background:#f4f6f8;border-radius:50%;border:1px solid #e3e8ed}
  .ticket-divider:before{left:-33px}.ticket-divider:after{right:-33px}
  .ticket-payment{padding:20px 22px;background:#fcfdfd}
  .ticket-payment-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
  .ticket-payment-head h4{margin:0;font-size:14px;font-weight:800}
  .ticket-payment-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
  .ticket-payment-grid small{display:block;color:#718096;font-size:11px;margin-bottom:5px}
  .ticket-payment-grid strong{font-size:13px;overflow-wrap:anywhere}
  .ticket-total{font-size:20px!important;color:#13785f}
  .ticket-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:18px}
  .ticket-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:11px 15px;background:#13785f;color:white;text-decoration:none;font-weight:700;font-size:13px;cursor:pointer}
  .ticket-btn.secondary{background:#edf5f2;color:#176e5b}
  .ticket-review{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid #e8edf0}
  .ticket-review label{font-size:12px;font-weight:700}
  .ticket-review select,.ticket-review input{min-width:0;border:1px solid #d9e0e6;border-radius:9px;padding:10px;background:#fff}
  .ticket-review input{flex:1}
  .ticket-empty{padding:42px 20px;text-align:center;background:#fff;border:1px dashed #cbd5df;border-radius:18px;color:#647184}
  .ticket-empty i{font-size:30px;color:#16846b;margin-bottom:12px}
  .ticket-empty strong{display:block;color:#1c2938;font-size:18px;margin-bottom:7px}
  @media(max-width:760px){.ticket-heading{align-items:flex-start;flex-direction:column}.ticket-summary{grid-template-columns:1fr}.ticket-fields{grid-template-columns:repeat(2,minmax(0,1fr))}.ticket-payment-grid{grid-template-columns:1fr 1fr}.ticket-top{flex-wrap:wrap}.ticket-status{margin-left:62px}.ticket-main{padding:17px}.ticket-payment{padding:17px}.ticket-divider{margin:0 17px}}
  @media(max-width:420px){.ticket-fields,.ticket-payment-grid{grid-template-columns:1fr 1fr}.ticket-field{padding:10px}.ticket-title h3{font-size:17px}.ticket-review{align-items:stretch;flex-direction:column}.ticket-review select,.ticket-review input{width:100%}}
  @media print{
    body{background:#fff!important}
    .no-print,nav,header,footer,.sidebar,.u-sidebar,.u-topbar{display:none!important}
    .ticket-page{max-width:none;padding:0}
    .ticket-card{box-shadow:none;break-inside:avoid;border:1px solid #bbb;margin-bottom:16px}
    .ticket-card details{display:block}
    .ticket-card *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .ticket-actions form,.ticket-review{display:none!important}
  }
</style>

<div class="ticket-page">
  <div class="ticket-heading">
    <div>
      <span class="ticket-eyebrow">ARENASPORTS · AREA PERSONAL</span>
      <h2>Booking Saya</h2>
      <p>Jadwal bermain dan tiket untuk booking yang sudah lunas.</p>
    </div>
    <a class="ticket-btn no-print" href="{{ route('user.courts') }}"><i class="fa-solid fa-plus"></i> Booking baru</a>
  </div>

  <div class="ticket-summary">
    <div class="ticket-summary-item">
      <span class="ticket-summary-icon"><i class="fa-regular fa-calendar-check"></i></span>
      <div><small>Total booking</small><strong>{{ $bookings->count() }}</strong></div>
    </div>
    <div class="ticket-summary-item">
      <span class="ticket-summary-icon"><i class="fa-solid fa-hourglass-half"></i></span>
      <div><small>Menunggu pembayaran</small><strong>{{ $bookings->filter(fn($b) => in_array($b->payment?->status ?? $b->status, ['Menunggu','DP','Pending','pending','unpaid']))->count() }}</strong></div>
    </div>
    <div class="ticket-summary-item">
      <span class="ticket-summary-icon"><i class="fa-solid fa-circle-check"></i></span>
      <div><small>Lunas / selesai</small><strong>{{ $bookings->filter(fn($b) => in_array($b->payment?->status ?? $b->status, ['Lunas','Selesai','settlement','capture','success']))->count() }}</strong></div>
    </div>
  </div>

  <div class="ticket-list">
    @forelse($bookings as $booking)
      @php
        $payment = $booking->payment;
        $paymentStatus = $payment?->status ?? $booking->status ?? 'Menunggu';
        $isPaid = in_array(strtolower((string)$paymentStatus), ['lunas','selesai','settlement','capture','success']);
        $isFailed = in_array(strtolower((string)$paymentStatus), ['gagal','failed','deny','cancel','expire','expired']);
        $statusClass = $isPaid ? 'paid' : ($isFailed ? 'failed' : 'neutral');
        $method = $payment?->method ?? $booking->payment_method ?? 'Belum dipilih';
        $bookingCode = 'AS-' . now()->parse($booking->booking_date)->format('Ymd') . '-' . str_pad((string)$booking->id, 5, '0', STR_PAD_LEFT);
      @endphp

      <article class="ticket-card">
        <div class="ticket-main">
          <div class="ticket-top">
            <div class="ticket-sport-icon"><i class="fa-solid fa-ticket"></i></div>
            <div class="ticket-title">
              <span class="ticket-code">E-TICKET · {{ $bookingCode }}</span>
              <h3>{{ $booking->lapangan?->nama ?? 'Lapangan' }}</h3>
              <p>{{ $booking->lapangan?->jenis ?? 'Olahraga' }} · {{ $booking->lapangan?->lokasi ?? 'Lokasi venue' }}</p>
            </div>
            <span class="ticket-status {{ $statusClass }}">
              <i class="fa-solid {{ $isPaid ? 'fa-circle-check' : ($isFailed ? 'fa-circle-xmark' : 'fa-clock') }}"></i>
              {{ $isPaid ? 'LUNAS' : ($isFailed ? 'GAGAL' : $paymentStatus) }}
            </span>
          </div>

          <div class="ticket-fields">
            <div class="ticket-field"><small>TANGGAL BERMAIN</small><strong><i class="fa-regular fa-calendar"></i> {{ $booking->booking_date?->format('d M Y') ?? '-' }}</strong></div>
            <div class="ticket-field"><small>JAM BERMAIN</small><strong><i class="fa-regular fa-clock"></i> {{ substr((string)$booking->start_time, 0, 5) }}–{{ substr((string)$booking->end_time, 0, 5) }}</strong></div>
            <div class="ticket-field"><small>DURASI</small><strong>{{ $booking->hours ?? 1 }} jam</strong></div>
            <div class="ticket-field"><small>TOTAL BOOKING</small><strong class="ticket-total">Rp {{ number_format((float)$booking->total_price, 0, ',', '.') }}</strong></div>
          </div>

          <div class="ticket-actions no-print">
            @if($payment && !$isPaid && !$isFailed)
              <form method="POST" action="{{ route('user.midtrans.checkout', $booking) }}">@csrf
                <button class="ticket-btn" type="submit"><i class="fa-solid fa-credit-card"></i> Lanjutkan pembayaran</button>
              </form>
            @endif
            <button class="ticket-btn secondary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
          </div>

          @if(in_array($booking->status, ['Lunas','Selesai']))
            <form method="POST" action="{{ route('user.reviews.store', $booking) }}" class="ticket-review no-print">
              @csrf
              <label for="rating-{{ $booking->id }}">Ulasan lapangan</label>
              <select id="rating-{{ $booking->id }}" name="rating" required>
                <option value="5">★★★★★ — Sangat puas</option><option value="4">★★★★ — Puas</option><option value="3">★★★ — Cukup</option><option value="2">★★ — Kurang</option><option value="1">★ — Tidak puas</option>
              </select>
              <input name="message" maxlength="1000" placeholder="Ceritakan pengalamanmu..." required>
              <button class="ticket-btn" type="submit">Kirim ulasan</button>
            </form>
          @endif
        </div>

        <div class="ticket-divider"></div>

        <div class="ticket-payment">
          <div class="ticket-payment-head">
            <h4><i class="fa-solid fa-receipt"></i> Rincian pembayaran</h4>
            <span class="ticket-code">PAY-{{ str_pad((string)($payment?->id ?? 0), 5, '0', STR_PAD_LEFT) }}</span>
          </div>
          <div class="ticket-payment-grid">
            <div><small>METODE</small><strong>{{ $method }}</strong></div>
            <div><small>STATUS PEMBAYARAN</small><strong>{{ $paymentStatus }}</strong></div>
            <div><small>NOMINAL DIBAYAR</small><strong class="ticket-total">Rp {{ number_format((float)($payment?->amount ?? $booking->total_price), 0, ',', '.') }}</strong></div>
            <div><small>WAKTU PEMBAYARAN</small><strong>{{ $payment?->paid_at?->format('d M Y, H:i') ?? 'Belum tercatat' }}</strong></div>
            <div><small>ID TRANSAKSI MIDTRANS</small><strong>{{ $payment?->transaction_id ?: 'Belum tersedia' }}</strong></div>
            <div><small>NAMA PEMESAN</small><strong>{{ $booking->user?->name ?? $user->name ?? '-' }}</strong></div>
          </div>
        </div>
      </article>
    @empty
      <div class="ticket-empty">
        <i class="fa-regular fa-calendar-xmark"></i>
        <strong>Belum ada booking lunas</strong>
        Booking akan tampil di sini setelah pembayaran Midtrans berhasil. Untuk melihat semua transaksi dan status pembayaran, buka Riwayat Booking.
        <div style="margin-top:16px"><a class="ticket-btn" href="{{ route('user.courts') }}">Cari lapangan</a></div>
      </div>
    @endforelse
  </div>
</div>
@endsection
