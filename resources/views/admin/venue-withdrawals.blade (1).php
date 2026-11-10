@extends('layouts.admin', ['title' => 'Penarikan Dana Venue', 'heading' => 'Penarikan Dana Venue'])

@section('content')
@php
    $allWithdrawals = collect($withdrawals);
    $counts = [
        'Menunggu' => $allWithdrawals->where('status', 'Menunggu')->count(),
        'Diproses' => $allWithdrawals->where('status', 'Diproses')->count(),
        'Dibayar' => $allWithdrawals->where('status', 'Dibayar')->count(),
        'Ditolak' => $allWithdrawals->where('status', 'Ditolak')->count(),
    ];
    $totalAmount = $allWithdrawals->where('status', 'Dibayar')->sum('amount');
    $statusClass = ['Menunggu' => 'waiting', 'Diproses' => 'processing', 'Dibayar' => 'paid', 'Ditolak' => 'rejected'];
    $statusIcon = ['Menunggu' => 'fa-clock', 'Diproses' => 'fa-spinner', 'Dibayar' => 'fa-circle-check', 'Ditolak' => 'fa-circle-xmark'];
@endphp

<style>
.vw-page{display:grid;gap:18px;color:#17231f}
.vw-hero{background:linear-gradient(120deg,#102d25,#17634a);color:#fff;border-radius:20px;padding:25px 28px;display:flex;justify-content:space-between;align-items:center;gap:18px}
.vw-hero h2{margin:0 0 7px;font-size:22px;color:#fff}.vw-hero p{margin:0;color:#d2e8dd;font-size:13px;line-height:1.6;max-width:600px}
.vw-hero-total{text-align:right;white-space:nowrap}.vw-hero-total strong{display:block;font-size:27px}.vw-hero-total span{font-size:11px;color:#d2e8dd}
.vw-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.vw-stat{background:#fff;border:1px solid #e7ece9;border-radius:15px;padding:15px;display:flex;align-items:center;gap:11px}
.vw-stat-icon{width:39px;height:39px;border-radius:12px;background:#eef7f1;color:#17634a;display:grid;place-items:center;flex:none}
.vw-stat strong{display:block;font-size:21px}.vw-stat span{display:block;font-size:11px;color:#78867e;margin-top:3px}
.vw-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.vw-toolbar p{margin:0;color:#77857c;font-size:12px}
.vw-list{display:grid;gap:13px}
.vw-card{background:#fff;border:1px solid #e6ece8;border-radius:16px;overflow:hidden;box-shadow:0 3px 12px #17231f06}
.vw-card-head{padding:16px 19px;border-bottom:1px solid #edf1ee;display:flex;justify-content:space-between;align-items:center;gap:12px}
.vw-venue{display:flex;align-items:center;gap:11px;min-width:0}.vw-venue-icon{width:42px;height:42px;border-radius:12px;background:#edf7f1;color:#17634a;display:grid;place-items:center;flex:none}
.vw-venue h3{margin:0 0 4px;font-size:15px}.vw-venue p{margin:0;color:#7a8980;font-size:11px;overflow-wrap:anywhere}
.vw-badge{display:inline-flex;align-items:center;gap:6px;border-radius:99px;padding:7px 10px;font-size:10px;font-weight:800;white-space:nowrap}
.vw-badge.waiting{background:#fff7e6;color:#a15c00}.vw-badge.processing{background:#eef4ff;color:#315eb4}.vw-badge.paid{background:#eaf7ef;color:#16804a}.vw-badge.rejected{background:#fff0ef;color:#c43226}
.vw-card-body{padding:17px 19px;display:grid;grid-template-columns:1fr 1fr 1.1fr;gap:12px}
.vw-info{background:#f7f9f8;border:1px solid #eff2f0;border-radius:11px;padding:12px;min-width:0}
.vw-info small{display:block;color:#839087;font-size:10px;margin-bottom:6px}.vw-info strong{display:block;color:#34483d;font-size:12px;line-height:1.5;overflow-wrap:anywhere}
.vw-amount strong{font-size:19px;color:#17634a}
.vw-actions{padding:14px 19px;border-top:1px solid #edf1ee;background:#fcfdfc;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.vw-note{height:38px;min-width:210px;border:1px solid #dce5df;border-radius:9px;padding:0 11px;font:inherit;font-size:11px}
.vw-action-form{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.vw-btn{border:0;border-radius:9px;padding:10px 13px;background:#17634a;color:#fff;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:7px;cursor:pointer}
.vw-btn.light{background:#edf3ef;color:#42564a}.vw-btn.danger{background:#fff0ef;color:#b42318}.vw-final-note{font-size:11px;color:#718078;line-height:1.5}
.vw-empty{padding:45px 20px;text-align:center;background:#fff;border:1px dashed #d9e3dc;border-radius:16px;color:#77857c}.vw-empty i{font-size:27px;color:#a5b3aa;margin-bottom:10px}.vw-empty strong{display:block;color:#34483d;margin-bottom:5px}
@media(max-width:850px){.vw-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.vw-card-body{grid-template-columns:1fr 1fr}.vw-info:last-child{grid-column:1/-1}}
@media(max-width:580px){.vw-hero{padding:20px;align-items:flex-start}.vw-hero h2{font-size:18px}.vw-hero-total strong{font-size:20px}.vw-stats{gap:8px}.vw-stat{padding:10px;gap:8px}.vw-stat-icon{width:33px;height:33px}.vw-stat strong{font-size:18px}.vw-card-head{padding:14px;align-items:flex-start;flex-direction:column}.vw-card-body{padding:13px;grid-template-columns:1fr}.vw-info:last-child{grid-column:auto}.vw-actions{padding:13px}.vw-action-form{width:100%}.vw-note{width:100%;min-width:0}.vw-btn{flex:1;justify-content:center}}
</style>

<div class="vw-page">
    <section class="vw-hero">
        <div>
            <h2>Kelola Penarikan Dana Mitra</h2>
            <p>Periksa permintaan pencairan saldo dari Admin Venue. Ubah status ke diproses setelah pemeriksaan, lalu tandai dibayar setelah transfer benar-benar dilakukan.</p>
        </div>
        <div class="vw-hero-total">
            <strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong>
            <span>Total penarikan yang telah dibayar</span>
        </div>
    </section>

    <section class="vw-stats">
        <div class="vw-stat"><div class="vw-stat-icon"><i class="fa-regular fa-clock"></i></div><div><strong>{{ $counts['Menunggu'] }}</strong><span>Menunggu pemeriksaan</span></div></div>
        <div class="vw-stat"><div class="vw-stat-icon"><i class="fa-solid fa-spinner"></i></div><div><strong>{{ $counts['Diproses'] }}</strong><span>Sedang diproses</span></div></div>
        <div class="vw-stat"><div class="vw-stat-icon"><i class="fa-solid fa-circle-check"></i></div><div><strong>{{ $counts['Dibayar'] }}</strong><span>Sudah dibayar</span></div></div>
        <div class="vw-stat"><div class="vw-stat-icon"><i class="fa-solid fa-circle-xmark"></i></div><div><strong>{{ $counts['Ditolak'] }}</strong><span>Ditolak</span></div></div>
    </section>

    <div class="vw-toolbar">
        <p><i class="fa-solid fa-circle-info"></i> Pastikan nomor rekening dan nama pemilik sesuai sebelum melakukan transfer.</p>
        <p>{{ $allWithdrawals->count() }} permintaan tercatat</p>
    </div>

    <section class="vw-list">
        @forelse($withdrawals as $w)
            @php
                $status = $w->status ?: 'Menunggu';
                $class = $statusClass[$status] ?? 'waiting';
                $icon = $statusIcon[$status] ?? 'fa-circle-info';
                $isFinal = in_array($status, ['Dibayar', 'Ditolak'], true);
            @endphp
            <article class="vw-card">
                <header class="vw-card-head">
                    <div class="vw-venue">
                        <div class="vw-venue-icon"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <h3>{{ $w->venue?->name ?? 'Venue tidak tersedia' }}</h3>
                            <p>{{ $w->user?->name ?? 'Pemilik tidak tersedia' }} · {{ $w->user?->email ?? '-' }}</p>
                        </div>
                    </div>
                    <span class="vw-badge {{ $class }}"><i class="fa-solid {{ $icon }}"></i> {{ $status }}</span>
                </header>

                <div class="vw-card-body">
                    <div class="vw-info vw-amount"><small>Jumlah penarikan</small><strong>Rp {{ number_format($w->amount, 0, ',', '.') }}</strong></div>
                    <div class="vw-info"><small>Informasi bank</small><strong>{{ $w->bank_name ?: '-' }}</strong></div>
                    <div class="vw-info"><small>Nomor rekening</small><strong>{{ $w->account_number ?: '-' }}</strong></div>
                    <div class="vw-info"><small>Nama pemilik rekening</small><strong>{{ $w->account_holder ?: '-' }}</strong></div>
                    <div class="vw-info"><small>Tanggal permintaan</small><strong>{{ $w->created_at?->format('d M Y, H:i') ?? '-' }}</strong></div>
                    <div class="vw-info"><small>Terakhir diproses</small><strong>{{ $w->processed_at?->format('d M Y, H:i') ?? 'Belum diproses' }}</strong></div>
                </div>

                <footer class="vw-actions">
                    @if(!$isFinal)
                        <form class="vw-action-form" method="POST" action="{{ route('admin.venue-withdrawals.process', $w) }}">
                            @csrf
                            <input class="vw-note" name="admin_note" placeholder="Catatan untuk pemilik venue..." maxlength="1000" value="{{ old('admin_note') }}">
                            @if($status === 'Menunggu')
                                <button class="vw-btn light" type="submit" name="action" value="process"><i class="fa-solid fa-play"></i> Proses permintaan</button>
                            @elseif($status === 'Diproses')
                                <button class="vw-btn" type="submit" name="action" value="paid" onclick="return confirm('Pastikan transfer dana sudah berhasil dilakukan. Tandai penarikan sebagai dibayar?')"><i class="fa-solid fa-check"></i> Tandai dibayar</button>
                            @endif
                            <button class="vw-btn danger" type="submit" name="action" value="reject" onclick="return confirm('Yakin ingin menolak permintaan penarikan ini?')"><i class="fa-solid fa-xmark"></i> Tolak</button>
                        </form>
                    @else
                        <div class="vw-final-note"><strong>Catatan Superadmin:</strong> {{ $w->admin_note ?: 'Tidak ada catatan.' }}</div>
                    @endif
                </footer>
            </article>
        @empty
            <div class="vw-empty"><i class="fa-solid fa-money-bill-transfer"></i><strong>Belum ada permintaan penarikan</strong>Permintaan dari Admin Venue akan muncul di halaman ini.</div>
        @endforelse
    </section>
</div>
@endsection
