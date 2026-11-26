@extends('layouts.admin', ['title' => 'Detail Venue', 'heading' => 'Detail Venue'])

@section('content')
<div class="dashboard-hero surface dashboard-hero-clean" style="margin-bottom:20px;">
    <div>
        <span class="eyebrow">Manajemen Venue</span>
        <h2>{{ $venue->name }}</h2>
        <p style="margin:5px 0 0;color:#5d7266;font-size:13px;">
            {{ $venue->address ?: 'Alamat belum diisi' }}
            @if($venue->owner) · Owner: {{ $venue->owner->name }} @endif
        </p>
    </div>
    <a href="{{ route('admin.venues.manage') }}" class="btn-ui btn-gray">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-vector-square"></i></div><div class="label">Total Lapangan</div><div class="value">{{ $courts->count() }}</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div><div class="label">Lapangan Aktif</div><div class="value">{{ $courts->filter(fn ($court) => $court->is_bookable)->count() }}</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-store"></i></div><div class="label">Status Venue</div><div class="value" style="font-size:18px;">{{ $venue->operational_status ?: ($venue->status ?? '-') }}</div></div>
</div>

<div class="panel-card" style="padding:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <h3 style="margin:0;font-size:17px;color:#143e26;"><i class="fa-solid fa-futbol"></i> Lapangan di Venue Ini</h3>
        <a class="btn-ui btn-green" href="{{ route('admin.courts.create') }}" style="padding:8px 14px;font-size:12px;">
            <i class="fa-solid fa-plus"></i> Tambah Lapangan
        </a>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>Lapangan</th>
                    <th>Jenis</th>
                    <th>Harga/Jam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courts as $court)
                    <tr>
                        <td>
                            <strong>{{ $court->nama }}</strong>
                            <div style="font-size:12px;color:#718078;">{{ $court->display_location }}</div>
                        </td>
                        <td>
                            {{ $court->jenis }}
                            @if($court->area_type)
                                <div style="font-size:11px;color:#718078;">{{ $court->area_type }}{{ $court->floor_type ? ' · ' . $court->floor_type : '' }}</div>
                            @endif
                        </td>
                        <td>Rp {{ number_format((float) $court->min_price, 0, ',', '.') }}</td>
                        <td><span class="status-badge {{ $court->is_bookable ? 'status-active' : 'status-inactive' }}">{{ $court->operational_status ?: 'Beroperasi' }}</span></td>
                        <td>
                            <div class="btn-row wrap">
                                <a class="btn-ui btn-green" href="{{ route('admin.courts.edit', $court) }}" style="padding:6px 12px;font-size:12px;">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:28px;color:#718078;">Belum ada lapangan di venue ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
