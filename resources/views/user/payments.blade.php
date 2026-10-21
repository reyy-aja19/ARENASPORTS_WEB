@extends('layouts.user')

@section('content')
<style>
  .payment-history{max-width:1100px;margin:0 auto;padding-bottom:28px}
  .payment-history-head{margin-bottom:22px}
  .payment-history-head span{display:block;color:#16846b;font-size:11px;font-weight:800;letter-spacing:.16em;margin-bottom:7px}
  .payment-history-head h2{margin:0 0 6px;font-size:clamp(24px,3vw,32px);font-weight:800}
  .payment-history-head p{margin:0;color:#687385}
  .payment-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
  .payment-ticket{background:#fff;border:1px solid #e3e8ed;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(25,42,60,.05);color:#1c2938}
  .payment-ticket-main{padding:20px}
  .payment-ticket-top{display:flex;align-items:center;gap:12px}
  .payment-ticket-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:13px;background:#e8f7f2;color:#13785f;font-size:18px}
  .payment-ticket-name{flex:1;min-width:0}
  .payment-ticket-name small{color:#718096;font-size:11px;letter-spacing:.1em;font-weight:800}
  .payment-ticket-name h3{font-size:17px;margin:4px 0 0;font-weight:800}
  .payment-ticket-status{font-size:11px;font-weight:800;border-radius:999px;padding:7px 10px;background:#fff2d6;color:#8b5b00;white-space:nowrap}
  .payment-ticket-status.paid{background:#e4f7ed;color:#167348}
  .payment-ticket-status.failed{background:#fde8e7;color:#a52d2d}
  .payment-ticket-meta{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:20px}
  .payment-ticket-meta small{display:block;color:#718096;font-size:11px;margin-bottom:5px}
  .payment-ticket-meta strong{font-size:13px;overflow-wrap:anywhere}
  .payment-ticket-amount{font-size:22px!important;color:#13785f}
  .payment-ticket-divider{border-top:1px dashed #cbd4dc;margin:0 20px}
  .payment-ticket-foot{padding:16px 20px;background:#fcfdfd;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
  .payment-ticket-foot small{display:block;color:#718096;font-size:11px;margin-bottom:4px}
  .payment-ticket-foot strong{font-size:12px;overflow-wrap:anywhere}
  .payment-ticket-link{display:inline-flex;align-items:center;gap:7px;border-radius:10px;background:#13785f;color:#fff;text-decoration:none;padding:10px 13px;font-weight:700;font-size:12px}
  .payment-history-empty{grid-column:1/-1;text-align:center;padding:42px 20px;background:#fff;border:1px dashed #cbd5df;border-radius:18px;color:#647184}
  .payment-history-empty strong{display:block;color:#1c2938;font-size:18px;margin:8px 0}
  @media(max-width:700px){.payment-list{grid-template-columns:1fr}}
  @media print{nav,header,footer,.sidebar,.u-sidebar,.u-topbar{display:none!important}.payment-ticket{box-shadow:none;break-inside:avoid}}
</style>

<div class="payment-history">
  <div class="payment-history-head">
    <span>ARENASPORTS · TRANSAKSI</span>
    <h2>Riwayat Booking</h2>
    <p>Semua transaksi booking beserta status Midtrans, nominal, waktu pembayaran, dan ID transaksi.</p>
  </div>

  <div class="payment-list">
    @forelse($payments as $payment)
      @php
        $status = (string)($payment->status ?? 'Menunggu');
        $normalized = strtolower($status);
        $isPaid = in_array($normalized, ['lunas','settlement','capture','success','selesai']);
        $isFailed = in_array($normalized, ['gagal','failed','deny','cancel','expire','expired']);
        $statusClass = $isPaid ? 'paid' : ($isFailed ? 'failed' : '');
      @endphp
      <article class="payment-ticket">
        <div class="payment-ticket-main">
          <div class="payment-ticket-top">
            <div class="payment-ticket-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="payment-ticket-name">
              <small>PAY-{{ str_pad((string)$payment->id, 5, '0', STR_PAD_LEFT) }}</small>
              <h3>{{ $payment->booking?->lapangan?->nama ?? 'Booking lapangan' }}</h3>
            </div>
            <span class="payment-ticket-status {{ $statusClass }}">{{ $isPaid ? 'LUNAS' : ($isFailed ? 'GAGAL' : $status) }}</span>
          </div>

          <div class="payment-ticket-meta">
            <div><small>TANGGAL BERMAIN</small><strong>{{ $payment->booking?->booking_date?->format('d M Y') ?? '-' }}</strong></div>
            <div><small>JAM BERMAIN</small><strong>{{ substr((string)($payment->booking?->start_time ?? ''), 0, 5) }}–{{ substr((string)($payment->booking?->end_time ?? ''), 0, 5) }}</strong></div>
            <div><small>METODE PEMBAYARAN</small><strong>{{ $payment->method ?? 'Belum dipilih' }}</strong></div>
            <div><small>WAKTU PEMBAYARAN</small><strong>{{ $payment->paid_at?->format('d M Y, H:i') ?? 'Belum tercatat' }}</strong></div>
            <div><small>ID TRANSAKSI</small><strong>{{ $payment->transaction_id ?: 'Belum tersedia' }}</strong></div>
            <div><small>NOMINAL</small><strong class="payment-ticket-amount">Rp {{ number_format((float)$payment->amount, 0, ',', '.') }}</strong></div>
          </div>
        </div>
        <div class="payment-ticket-divider"></div>
        <div class="payment-ticket-foot">
          <div><small>KODE BOOKING</small><strong>{{ $payment->booking ? 'AS-' . $payment->booking->booking_date?->format('Ymd') . '-' . str_pad((string)$payment->booking->id, 5, '0', STR_PAD_LEFT) : '-' }}</strong></div>
          <div class="no-print">
            @if($payment->booking)
              <a class="payment-ticket-link" href="{{ route('user.bookings') }}"><i class="fa-solid fa-ticket"></i> Lihat tiket booking</a>
            @else
              <button type="button" class="payment-ticket-link" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak</button>
            @endif
          </div>
        </div>
      </article>
    @empty
      <div class="payment-history-empty">
        <i class="fa-regular fa-credit-card" style="font-size:30px;color:#16846b"></i>
        <strong>Belum ada riwayat pembayaran</strong>
        Transaksi booking dan detail pembayarannya akan tampil di sini.
      </div>
    @endforelse
  </div>
</div>
@endsection
