@extends('layouts.admin', ['title' => 'Manajemen Venue', 'heading' => 'Manajemen Venue'])

@section('content')
<div class="dashboard-hero surface dashboard-hero-clean" style="margin-bottom:20px;">
    <div>
        <span class="eyebrow">Superadmin</span>
        <h2>Manajemen Venue</h2>
        <p style="margin:5px 0 0;color:#5d7266;font-size:13px;">
            Kelola seluruh venue platform. Klik venue untuk melihat dan mengelola lapangan di dalamnya.
        </p>
    </div>
</div>

<div class="panel-card" style="padding:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <h3 style="margin:0;font-size:17px;color:#143e26;"><i class="fa-solid fa-building"></i> Daftar Venue</h3>
        <span style="font-size:12px;color:#64748b;">Total {{ $venues->count() }} venue</span>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Lapangan</th>
                    <th>Kota</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venues as $venue)
                    <tr>
                        <td>
                            <strong>{{ $venue->name }}</strong>
                            <div style="font-size:12px;color:#718078;">{{ \Illuminate\Support\Str::limit($venue->address, 60) }}</div>
                        </td>
                        <td>
                            {{ $venue->owner?->name ?? '-' }}
                            <div style="font-size:12px;color:#718078;">{{ $venue->owner?->email ?? '' }}</div>
                        </td>
                        <td>
                            <span class="status-badge {{ strtolower($venue->status) === 'aktif' ? 'status-active' : 'status-inactive' }}">
                                {{ $venue->status ?? '-' }}
                            </span>
                        </td>
                        <td><strong>{{ $venue->lapangans_count }}</strong> lapangan</td>
                        <td>{{ $venue->city ?? '-' }}</td>
                        <td>
                            <div class="btn-row wrap">
                                <a class="btn-ui btn-green" href="{{ route('admin.venues.manage.show', $venue) }}" style="padding:6px 12px;font-size:12px;">
                                    <i class="fa-solid fa-eye"></i> Detail & Lapangan
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:28px;color:#718078;">Belum ada venue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
