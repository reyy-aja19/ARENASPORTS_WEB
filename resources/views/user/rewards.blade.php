@extends('layouts.user')
@section('content')
<style>
.rv-hero{display:flex;justify-content:space-between;gap:20px;align-items:center;padding:28px 32px;border-radius:28px;background:linear-gradient(135deg,#163b2d,#0f2d22);color:#fff;margin-bottom:24px}
.rv-kicker{font-size:12px;font-weight:800;letter-spacing:.08em;color:#c8f169;margin-bottom:6px}
.rv-hero h1{margin:0;font-size:30px;line-height:1.1}
.rv-hero p{margin:8px 0 0;color:rgba(255,255,255,.8);max-width:640px}
.rv-points{min-width:160px;text-align:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:18px}
.rv-points strong{display:block;font-size:34px;line-height:1;color:#fff}
.rv-points span{display:block;margin-top:6px;font-size:11px;font-weight:800;letter-spacing:.08em;color:#c8f169}
.rv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px}
.rv-card{background:#fff;border:1px solid #e5ece7;border-radius:22px;overflow:hidden;box-shadow:0 8px 24px rgba(15,45,34,.04)}
.rv-img{height:160px;background:linear-gradient(135deg,#edf5ef,#dfece3);display:flex;align-items:center;justify-content:center;color:#3f6b54;font-size:34px}
.rv-img img{width:100%;height:100%;object-fit:cover;display:block}
.rv-body{padding:18px}
.rv-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
.rv-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;background:#f2f7f3;color:#1f513d}
.rv-badge.gold{background:#fff7dd;color:#8a6500}
.rv-badge.global{background:#eef4ff;color:#3552a4}
.rv-body h3{margin:0 0 8px;font-size:18px;color:#173326}
.rv-body p{margin:0;color:#64748b;font-size:13px;line-height:1.7}
.rv-meta{font-size:12px;color:#6b7280;margin-top:10px}
.rv-btn{width:100%;margin-top:14px;border:0;border-radius:12px;padding:12px 14px;background:#1f513d;color:#fff;font-weight:800;cursor:pointer}
.rv-btn:disabled{opacity:.45;cursor:not-allowed}
.rv-history{background:#fff;border:1px solid #e5ece7;border-radius:22px;overflow:hidden}
.rv-table{width:100%;border-collapse:collapse}
.rv-table th,.rv-table td{padding:14px 16px;text-align:left;border-bottom:1px solid #edf2ee;font-size:13px;vertical-align:top}
.rv-table th{font-size:12px;color:#64748b;font-weight:800;background:#f8fbf9}
.rv-status{display:inline-flex;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:800;background:#ecfdf3;color:#166534}
.rv-status.pending{background:#fff7ed;color:#9a3412}
.rv-code{font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px;color:#334155;word-break:break-all}
@media(max-width:760px){.rv-hero{flex-direction:column;align-items:flex-start}.rv-points{width:100%}.rv-table thead{display:none}.rv-table,.rv-table tbody,.rv-table tr,.rv-table td{display:block;width:100%}.rv-table tr{border-bottom:1px solid #edf2ee}.rv-table td{border:0;padding:8px 16px}.rv-table td::before{content:attr(data-label);display:block;font-size:11px;font-weight:800;color:#64748b;margin-bottom:4px;text-transform:uppercase}}
</style>

<section class="rv-hero">
    <div>
        <div class="rv-kicker">ARENA REWARDS</div>
        <h1>Poin kamu, hadiahmu.</h1>
        <p>Tukarkan poin yang terkumpul dengan reward dari ArenaSports atau venue partner. Reward dengan label venue hanya dapat diverifikasi oleh venue terkait.</p>
    </div>
    <div class="rv-points">
        <strong>{{ number_format($user->points) }}</strong>
        <span>POIN TERSEDIA</span>
    </div>
</section>

<section class="u-section">
    <div class="u-section-head">
        <div>
            <h2>Hadiah tersedia</h2>
            <p>Poin hanya berlaku di dalam aplikasi dan tidak dapat diuangkan. Cek label venue sebelum menukar.</p>
        </div>
    </div>
    <div class="rv-grid">
        @forelse($rewards as $reward)
            @php $venueName = $reward->venue?->name; @endphp
            <article class="rv-card">
                <div class="rv-img">
                    @if($reward->image && file_exists(public_path($reward->image)))
                        <img src="{{ asset($reward->image) }}" alt="{{ $reward->title }}">
                    @else
                        <i class="fa-solid fa-gift"></i>
                    @endif
                </div>
                <div class="rv-body">
                    <div class="rv-badges">
                        <span class="rv-badge gold"><i class="fa-solid fa-coins"></i> {{ number_format($reward->points_required) }} poin</span>
                        <span class="rv-badge {{ $venueName ? '' : 'global' }}"><i class="fa-solid fa-location-dot"></i> {{ $venueName ?: 'Global' }}</span>
                    </div>
                    <h3>{{ $reward->title }}</h3>
                    <p>{{ $reward->description ?: 'Reward dapat ditukarkan sesuai syarat yang berlaku.' }}</p>
                    <div class="rv-meta">Penyedia: <strong>{{ $reward->provider_label }}</strong></div>
                    <div class="rv-meta">Berlaku di: <strong>{{ $venueName ?: 'Semua venue / ArenaSports' }}</strong></div>
                    <form method="POST" action="{{ route('user.rewards.redeem', $reward) }}" onsubmit="return confirm('Tukar {{ number_format($reward->points_required) }} poin untuk hadiah ini?')">
                        @csrf
                        <button class="rv-btn" {{ $user->points < $reward->points_required ? 'disabled' : '' }}>Tukar hadiah</button>
                    </form>
                    @if($user->points < $reward->points_required)
                        <div class="rv-meta">Poin belum mencukupi.</div>
                    @endif
                </div>
            </article>
        @empty
            <div class="u-panel u-empty"><strong>Belum ada hadiah aktif</strong>Silakan cek kembali nanti.</div>
        @endforelse
    </div>
</section>

<section class="u-section">
    <div class="u-section-head">
        <div>
            <h2>Riwayat penukaran</h2>
            <p>Hadiah yang pernah kamu tukarkan, lengkap dengan venue berlaku dan kode verifikasi.</p>
        </div>
    </div>
    <div class="rv-history">
        <table class="rv-table">
            <thead>
                <tr>
                    <th>Hadiah</th>
                    <th>Berlaku di</th>
                    <th>Tanggal</th>
                    <th>Kode / QR</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($redemptions as $item)
                    @php
                        $venueName = $item->venue?->name ?? $item->reward?->venue?->name ?? 'Global / ArenaSports';
                        $qrText = "ArenaSports Penukaran Reward\n"
                            . "Hadiah: " . ($item->reward?->title ?? 'Reward') . "\n"
                            . "Kode: " . ($item->qr_code ?? '-') . "\n"
                            . "Berlaku: " . $venueName . "\n"
                            . "Status: " . ($item->status ?? '-');
                    @endphp
                    <tr>
                        <td data-label="Hadiah">
                            <strong>{{ $item->reward?->title ?? 'Reward' }}</strong>
                            <div class="rv-meta">{{ number_format($item->reward?->points_required ?? 0) }} poin</div>
                        </td>
                        <td data-label="Berlaku di">{{ $venueName }}</td>
                        <td data-label="Tanggal">
                            {{ $item->redeemed_at?->format('d M Y H:i') ?? '-' }}
                            @if($item->verified_at)
                                <div class="rv-meta">Diverifikasi: {{ $item->verified_at->format('d M Y H:i') }}</div>
                            @endif
                        </td>
                        <td data-label="Kode / QR">
                            <div class="rv-code">{{ $item->qr_code ?? '-' }}</div>
                            @if($item->qr_code)
                                <div style="margin-top:10px;display:inline-block;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:12px">
                                    <img loading="lazy" width="140" height="140" style="display:block;width:140px;height:140px;max-width:100%;image-rendering:pixelated" alt="QR penukaran {{ $item->qr_code }}" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&ecc=M&data={{ urlencode($qrText) }}">
                                </div>
                                <small style="display:block;color:#687385;margin-top:6px">Tunjukkan QR/kode ini ke venue terkait saat verifikasi.</small>
                            @endif
                        </td>
                        <td data-label="Status">
                            <span class="rv-status {{ in_array($item->status, ['Menunggu','Pending']) ? 'pending' : '' }}">{{ $item->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="u-meta" style="padding:24px 16px;">Belum ada penukaran reward.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
