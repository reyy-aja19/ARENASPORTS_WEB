@extends('layouts.user')
@section('content')
<style>
.ticket-detail{max-width:900px;margin:0 auto;padding:10px 0 36px;color:#1d2b39}.ticket-paper{background:#fff;border:1px solid #e2e8ee;border-radius:22px;overflow:hidden;box-shadow:0 10px 35px rgba(25,42,60,.07)}.ticket-banner{padding:24px;background:#e8f7f1;display:flex;justify-content:space-between;gap:18px;align-items:center}.ticket-banner h1{margin:5px 0;font-size:28px}.ticket-banner p{margin:0;color:#617184}.ticket-code{font-weight:800;letter-spacing:.12em;color:#14785e}.ticket-content{display:grid;grid-template-columns:1fr 250px;gap:25px;padding:26px}.ticket-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.ticket-grid div{background:#f6f8fa;padding:14px;border-radius:12px}.ticket-grid small{display:block;color:#718096;margin-bottom:5px;font-size:11px}.ticket-grid strong{overflow-wrap:anywhere}.ticket-qr{text-align:center;border:1px dashed #cbd5df;border-radius:16px;padding:16px;align-self:start}.ticket-qr img{width:180px;height:180px;max-width:100%}.ticket-qr code{display:block;margin-top:10px;font-weight:800;overflow-wrap:anywhere}.ticket-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.ticket-btn{display:inline-flex;padding:11px 15px;border-radius:10px;background:#13785f;color:white;text-decoration:none;font-weight:700;border:0;cursor:pointer}.ticket-btn.alt{background:#edf5f2;color:#176e5b}.ticket-note{padding:15px 26px;background:#fcfdfd;border-top:1px dashed #cbd5df;color:#687385;font-size:13px}@media(max-width:650px){.ticket-content{grid-template-columns:1fr}.ticket-banner{align-items:flex-start;flex-direction:column}.ticket-grid{grid-template-columns:1fr 1fr}}@media print{.no-print,header,nav,footer,.u-chat{display:none!important}.ticket-detail{max-width:none}.ticket-paper{box-shadow:none}}
</style>
<div class="ticket-detail">
  <a class="ticket-btn alt no-print" href="{{ route('user.payments') }}">← Kembali ke Riwayat Booking</a>
  <section class="ticket-paper" style="margin-top:18px">
    <header class="ticket-banner"><div><div class="ticket-code">ARENASPORTS · E-TICKET</div><h1>{{ $booking->lapangan?->nama ?? 'Lapangan' }}</h1><p>Tunjukkan QR atau kode ini kepada petugas kasir.</p></div><strong style="color:#13785f">✓ LUNAS</strong></header>
    <div class="ticket-content">
      <div>
        <div class="ticket-grid">
          <div><small>KODE TIKET</small><strong>{{ $ticketCode }}</strong></div>
          <div><small>NAMA PEMESAN</small><strong>{{ $user->name }}</strong></div>
          <div><small>TANGGAL BERMAIN</small><strong>{{ $booking->booking_date?->format('d M Y') ?? '-' }}</strong></div>
          <div><small>JAM BERMAIN</small><strong>{{ substr((string) $booking->start_time, 0, 5) }}–{{ substr((string) $booking->end_time, 0, 5) }}</strong></div>
          <div><small>DURASI</small><strong>{{ $booking->hours ?? 1 }} jam</strong></div>
          <div><small>TOTAL DIBAYAR</small><strong>Rp {{ number_format((float) $booking->total_price, 0, ',', '.') }}</strong></div>
          <div><small>STATUS PEMBAYARAN</small><strong>{{ $booking->payment?->status ?? $booking->status }}</strong></div>
          <div><small>ID TRANSAKSI MIDTRANS</small><strong>{{ $booking->payment?->transaction_id ?? '-' }}</strong></div>
        </div>
        <div class="ticket-actions no-print"><button class="ticket-btn" onclick="window.print()">Cetak / Simpan PDF</button><a class="ticket-btn alt" href="{{ route('user.bookings') }}">Booking Saya</a></div>
      </div>
      <aside class="ticket-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($ticketCode) }}" alt="QR tiket {{ $ticketCode }}"><small>SCAN KODE TIKET</small><code>{{ $ticketCode }}</code></aside>
    </div>
    <div class="ticket-note">QR ini adalah identitas tiket booking. Petugas perlu mencocokkan kode dengan sistem ArenaSports sebelum menerima tiket.</div>
  </section>
</div>
@endsection
