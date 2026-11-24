@extends('layouts.admin', [
    'title' => ($isVenueAdmin ?? false) ? 'Dashboard Admin Venue' : 'Dashboard Super Admin',
    'heading' => ($isVenueAdmin ?? false) ? 'Dashboard Venue' : 'Dashboard Platform'
])

@section('content')
<div class="dashboard-hero surface dashboard-hero-clean" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; background:linear-gradient(135deg, {{ ($isVenueAdmin ?? false) ? '#0d5633, #15803d' : '#0a3622, #0d5633' }}); color:#fff; padding:24px 28px; border-radius:20px; box-shadow:0 12px 28px rgba(10,54,34,0.12);">
    <div>
        <span class="eyebrow" style="background:rgba(255,255,255,0.18); color:#bbf7d0; padding:4px 12px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase;">
            {{ ($isVenueAdmin ?? false) ? 'Panel Pengelola Venue' : 'Pusat Kontrol Super Admin' }}
        </span>
        <h2 style="color:#ffffff; margin:8px 0 4px; font-size:24px; font-weight:800;">
            {{ ($isVenueAdmin ?? false) ? 'Selamat Datang, Pengelola ' . ($adminUser->name ?? 'Venue') : 'Ringkasan Ekosistem ArenaSports' }}
        </h2>
        <p style="margin:0; color:#dcfce7; font-size:13px;">
            {{ ($isVenueAdmin ?? false)
                ? 'Pantau booking, pembayaran, pendapatan bersih, dan operasional seluruh venue/cabang Anda.'
                : 'Pantau performa venue, booking, komisi platform, dan konfirmasi pembayaran secara realtime.' }}
        </p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        @if($isVenueAdmin ?? false)
            <a href="{{ route('admin.venue-profile') }}" class="btn-ui btn-gray" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3); text-decoration:none;">
                <i class="fa-solid fa-store"></i> Profil Venue
            </a>
            <a href="{{ route('admin.venue-finance') }}" class="btn-ui btn-green" style="background:#a3e635; color:#14532d; font-weight:800; text-decoration:none;">
                <i class="fa-solid fa-wallet"></i> Saldo & Penarikan
            </a>
        @else
            <a href="{{ route('admin.venue-applications') }}" class="btn-ui btn-gray" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3); text-decoration:none;">
                <i class="fa-solid fa-file-signature"></i> Verifikasi Venue
            </a>
            <a href="{{ route('admin.users') }}" class="btn-ui btn-green" style="background:#a3e635; color:#14532d; font-weight:800; text-decoration:none;">
                <i class="fa-solid fa-users"></i> Manajemen User
            </a>
        @endif
    </div>
</div>

<div class="stats-grid">
    @foreach($stats as $stat)
        <div class="stat-card anim-click">
            <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div class="label">{{ $stat['label'] }}</div>
            <div class="value">{{ $stat['value'] }}</div>
        </div>
    @endforeach
</div>

<div class="card-grid-2 dashboard-charts">
    <div class="panel-card highlight anim-click">
        <div class="panel-title">
            <i class="fa-solid fa-calendar-check"></i>
            {{ ($isSuper ?? false) ? 'Booking Platform per Bulan' : 'Booking Venue per Bulan' }}
        </div>
        <div class="chart-wrap"><canvas id="chartBookings"></canvas></div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title">
            <i class="fa-solid fa-money-bill-trend-up"></i>
            {{ ($isSuper ?? false) ? 'Pendapatan Kotor vs Komisi Platform' : 'Pendapatan Kotor vs Bersih' }}
        </div>
        <div class="chart-wrap"><canvas id="chartRevenue"></canvas></div>
    </div>

    <div class="panel-card anim-click" style="grid-column: 1 / -1;">
        <div class="panel-title"><i class="fa-solid fa-circle-half-stroke"></i> Status Booking</div>
        <div class="chart-wrap" style="max-width:520px;margin:0 auto;"><canvas id="chartStatus"></canvas></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const labels = @json($chartLabels ?? []);
const bookings = @json($chartBookings ?? []);
const revenue = @json($chartRevenue ?? []);
const gross = @json($chartGross ?? []);
const statusLabels = @json($bookingStatusLabels ?? []);
const statusValues = @json($bookingStatusValues ?? []);
const isSuper = @json($isSuper ?? false);

const moneyTick = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);

new Chart(document.getElementById('chartBookings'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Booking',
            data: bookings,
            borderColor: '#10b04a',
            backgroundColor: 'rgba(16,176,74,.12)',
            fill: true,
            tension: .35,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#0a7f34',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: isSuper ? 'Pendapatan Kotor (GMV)' : 'Pendapatan Kotor',
                data: gross,
                backgroundColor: 'rgba(16,176,74,.35)',
                borderColor: '#10b04a',
                borderWidth: 1,
                borderRadius: 8,
            },
            {
                label: isSuper ? 'Komisi Platform' : 'Pendapatan Bersih',
                data: revenue,
                backgroundColor: 'rgba(10,127,52,.85)',
                borderColor: '#0a7f34',
                borderWidth: 1,
                borderRadius: 8,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: (ctx) => ctx.dataset.label + ': ' + moneyTick(ctx.raw)
                }
            }
        },
        scales: {
            x: { grid: { display: false } },
            y: {
                beginAtZero: true,
                ticks: { callback: (v) => moneyTick(v) }
            }
        }
    }
});

new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#ef4444'],
            borderColor: '#fff',
            borderWidth: 4,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
@endpush
