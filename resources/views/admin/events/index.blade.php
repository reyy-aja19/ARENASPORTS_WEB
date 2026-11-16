@extends('layouts.admin', ['title' => 'Manajemen Event Olahraga', 'heading' => 'Manajemen Event Olahraga'])

@section('content')
<div class="dashboard-hero surface dashboard-hero-clean" style="margin-bottom:20px;">
    <div>
        <span class="eyebrow">Turnamen & Kompetisi</span>
        <h2>Manajemen Event Olahraga</h2>
        <p style="margin:5px 0 0; color:#5d7266; font-size:13px;">
            Publikasikan turnamen dan event resmi venue Anda. Event yang dipublikasikan akan otomatis muncul di aplikasi user dan mengirim notifikasi secara realtime.
        </p>
    </div>
    <a href="{{ route('admin.events.create') }}" class="btn-ui btn-green anim-click">
        <i class="fa-solid fa-plus"></i> Buat Event Baru
    </a>
</div>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="label">Total Event</div>
        <div class="value">{{ $events->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b04a;"><i class="fa-solid fa-bullhorn"></i></div>
        <div class="label">Event Publikasi (Aktif)</div>
        <div class="value">{{ $events->where('status', 'Publikasi')->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#b45309;"><i class="fa-solid fa-file-pen"></i></div>
        <div class="label">Event Draft</div>
        <div class="value">{{ $events->where('status', 'Draft')->count() }}</div>
    </div>
</div>

<div class="panel-card" style="padding:22px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
        <h3 style="margin:0; font-size:17px; color:#143e26;"><i class="fa-solid fa-trophy"></i> Daftar Event Resmi</h3>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <select class="select-ui" data-local-filter-select="jenis" style="padding:8px 12px; font-size:13px;">
                <option value="">Semua Cabang</option>
                <option value="Futsal">Futsal</option>
                <option value="Badminton">Badminton</option>
                <option value="Basket">Basket</option>
                <option value="Voli">Voli</option>
                <option value="Tenis">Tenis</option>
            </select>
            <select class="select-ui" data-local-filter-select="status" style="padding:8px 12px; font-size:13px;">
                <option value="">Semua Status</option>
                <option value="Publikasi">Publikasi</option>
                <option value="Draft">Draft</option>
                <option value="Dibatalkan">Dibatalkan</option>
            </select>
        </div>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Event</th>
                    <th>Cabang</th>
                    <th>Jadwal</th>
                    <th>Lokasi Venue</th>
                    <th>Kuota</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $i => $event)
                    @php
                        $statusClass = match($event->status) {
                            'Publikasi' => 'status-active',
                            'Draft' => 'status-inactive',
                            default => 'status-inactive',
                        };
                    @endphp
                    <tr data-search-item data-jenis="{{ $event->jenis }}" data-status="{{ $event->status }}">
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $event->title }}</strong>
                            @if($event->creator)
                                <br><small style="color:#718078;">Oleh: {{ $event->creator->name }}</small>
                            @endif
                        </td>
                        <td>
                            <span style="font-weight:600; color:#143e26;">{{ $event->jenis }}</span>
                        </td>
                        <td>
                            <i class="fa-regular fa-calendar"></i> {{ optional($event->tanggal)->format('d M Y') }}
                            @if($event->start_time)
                                <br><small style="color:#64748b;"><i class="fa-regular fa-clock"></i> {{ substr($event->start_time, 0, 5) }} - {{ $event->end_time ? substr($event->end_time, 0, 5) : 'Selesai' }}</small>
                            @endif
                        </td>
                        <td>
                            <i class="fa-solid fa-location-dot" style="color:#10b04a;"></i> {{ $event->lokasi }}
                        </td>
                        <td>
                            <strong>{{ $event->kuota }}</strong> <span style="font-size:12px; color:#64748b;">peserta</span>
                        </td>
                        <td>
                            <span class="{{ $statusClass }}">
                                {{ $event->status }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-row wrap">
                                <a class="btn-ui btn-green" href="{{ route('admin.events.edit', $event) }}" style="padding:6px 12px; font-size:12px;">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus event ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ui btn-red" type="submit" style="padding:6px 12px; font-size:12px;">
                                        <i class="fa-solid fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:35px; color:#718078;">
                            <i class="fa-regular fa-calendar-xmark" style="font-size:32px; margin-bottom:10px; display:block; color:#a1b3a8;"></i>
                            Belum ada event olahraga yang dibuat. Klik tombol <strong>+ Buat Event Baru</strong> di atas untuk mempublikasikan turnamen.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
