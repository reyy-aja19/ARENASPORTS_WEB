@extends('layouts.admin', ['title' => 'Export Laporan', 'heading' => 'Export Laporan'])
@section('content')
<form class="form-card" method="GET" action="{{ route('admin.reports') }}">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <h2 style="margin:0;color:#0f2f1b;">Export Laporan Booking & Pembayaran</h2>
            <p style="margin:6px 0 0;color:#667085;">Gunakan filter di bawah, lalu unduh laporan hanya dari menu ini dalam format CSV atau Excel.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a class="btn-ui btn-green anim-click" href="{{ route('admin.reports.export.csv', request()->query()) }}"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
            <a class="btn-ui btn-green anim-click" href="{{ route('admin.reports.export.excel', request()->query()) }}"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
        </div>
    </div>

    <div class="form-group">
        <label>Rentang Tanggal</label>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <input class="input-ui" type="date" name="from" value="{{ $filters['from'] }}">
            <input class="input-ui" type="date" name="to" value="{{ $filters['to'] }}">
        </div>
    </div>
    <div class="form-group">
        <label>Status Transaksi</label>
        <select class="select-ui" name="type">
            @foreach(['Semua Transaksi','Lunas','Menunggu'] as $type)
            <option value="{{ $type }}" {{ $filters['type'] === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
        <a class="btn-ui btn-gray" href="{{ route('admin.reports') }}">Reset</a>
        <button class="btn-ui btn-green">Terapkan Filter</button>
    </div>

    <div class="data-table mt-16">
        <table>
            <thead><tr><th>Tanggal</th><th>User</th><th>Lapangan</th><th>Metode</th><th>Jumlah</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td>{{ optional($report->created_at)->format('d M Y') }}</td>
                    <td>{{ $report->user?->name ?? '-' }}</td>
                    <td>{{ $report->booking?->lapangan?->nama ?? '-' }}</td>
                    <td>{{ $report->method ?? '-' }}</td>
                    <td>Rp {{ number_format($report->amount,0,',','.') }}</td>
                    <td>{{ $report->status }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:#667085;padding:24px;">Belum ada data laporan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>
@endsection
