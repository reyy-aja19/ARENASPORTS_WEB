@extends('layouts.admin', ['title' => 'Dashboard Admin', 'heading' => 'Dashboard'])

@section('content')
<div class="dashboard-hero surface dashboard-hero-clean">
    <div>
        <span class="eyebrow">Realtime Overview</span>
        <h2>ArenaSports — Admin Dashboard</h2>
    </div>
    <a href="{{ route('admin.reports') }}" class="btn-ui btn-green anim-click"><i class="fa-regular fa-file-lines"></i> Export Laporan</a>
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
        <div class="panel-title"><i class="fa-solid fa-users"></i> Grafik Data User</div>
        <div class="chart-wrap"><canvas id="chartUsers"></canvas></div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title"><i class="fa-solid fa-calendar-check"></i> Grafik Penyewaan Lapangan</div>
        <div class="chart-wrap"><canvas id="chartCourt"></canvas></div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title"><i class="fa-solid fa-circle-half-stroke"></i> Status User</div>
        <div class="chart-wrap"><canvas id="chartPie"></canvas></div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title"><i class="fa-solid fa-money-bill-trend-up"></i> Grafik Pendapatan</div>
        <div class="chart-wrap"><canvas id="chartRevenue"></canvas></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const labels = {{ \Illuminate\Support\Js::from($chartLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May']) }};
const green = '#10b04a';
const greenDark = '#0a7f34';

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: 'rgba(8,74,31,.92)', padding: 12, cornerRadius: 12, displayColors: false }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#6b806f' } },
        y: { beginAtZero: true, grid: { color: 'rgba(16,98,47,.10)' }, ticks: { color: '#6b806f', precision: 0 } }
    }
};

const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
        tooltip: { backgroundColor: 'rgba(8,74,31,.92)', padding: 12, cornerRadius: 12 }
    }
};

function makeGradient(ctx) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(16,176,74,.28)');
    gradient.addColorStop(1, 'rgba(16,176,74,.02)');
    return gradient;
}

function renderLineChart(id, data) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data,
                borderColor: green,
                backgroundColor: makeGradient(ctx),
                fill: true,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: greenDark,
                pointBorderWidth: 2,
                tension: .38
            }]
        },
        options: lineOptions
    });
}

renderLineChart('chartUsers', @json($chartUsers));
renderLineChart('chartCourt', @json($chartLapangan));
renderLineChart('chartRevenue', @json($chartRevenue));

new Chart(document.getElementById('chartPie'), {
    type: 'doughnut',
    data: {
        labels: ['User Aktif','User Nonaktif'],
        datasets: [{
            data: @json($userRatio),
            backgroundColor: ['#10b04a','#d1d5db'],
            borderColor: '#ffffff',
            borderWidth: 5,
            hoverOffset: 8
        }]
    },
    options: pieOptions
});
</script>
@endpush
