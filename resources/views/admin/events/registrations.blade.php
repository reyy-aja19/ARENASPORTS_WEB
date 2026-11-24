@extends('layouts.admin', [
    'title' => 'Pendaftar Event',
    'heading' => 'Pendaftar Event'
])

@section('content')
@php
    $statusStyles = [
        'Baru' => 'background:#fff2d6;color:#8b5b00',
        'Dihubungi' => 'background:#eef6ff;color:#2b5c8a',
        'Terkonfirmasi' => 'background:#e4f7ed;color:#167348',
        'Ditolak' => 'background:#fde8e7;color:#a52d2d',
    ];
@endphp

<div class="dashboard-hero surface dashboard-hero-clean" style="margin-bottom:20px;">
    <div>
        <span class="eyebrow">Turnamen & Kompetisi</span>
        <h2>{{ $event->title }}</h2>
        <p style="margin:5px 0 0; color:#5d7266; font-size:13px;">
            {{ $event->jenis }} · {{ $event->tanggal?->format('d M Y') }}
            @if($event->start_time)
                · {{ substr($event->start_time, 0, 5) }}–{{ $event->end_time ? substr($event->end_time, 0, 5) : 'selesai' }}
            @endif
            · {{ $event->venue?->name ?? $event->lokasi }}
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('admin.events.index') }}" class="btn-ui btn-gray">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('admin.events.edit', $event) }}" class="btn-ui btn-green">
            <i class="fa-solid fa-pen"></i> Edit Event
        </a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="label">Total Pendaftar</div>
        <div class="value">{{ $registrations->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#10b04a;"><i class="fa-solid fa-circle-check"></i></div>
        <div class="label">Terkonfirmasi</div>
        <div class="value">{{ $registrations->where('status', 'Terkonfirmasi')->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#b45309;"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="label">Baru / Dihubungi</div>
        <div class="value">{{ $registrations->whereIn('status', ['Baru', 'Dihubungi'])->count() }}</div>
    </div>
</div>

<div class="panel-card" style="padding:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
        <h3 style="margin:0;font-size:17px;color:#143e26;">
            <i class="fa-solid fa-clipboard-list"></i> Daftar Tim Terdaftar
        </h3>
        <span style="font-size:12px;color:#64748b;">
            Kuota {{ $event->kuota }} tim · Pendaftaran {{ $event->registration_deadline?->format('d M Y') ?? 'sampai hari acara' }}
        </span>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tim</th>
                    <th>Ketua & Kontak</th>
                    <th>Anggota</th>
                    <th>Catatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $i => $registration)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $registration->team_name }}</strong>
                            <br><small style="color:#718078;">Daftar {{ $registration->created_at?->format('d M Y, H:i') }}</small>
                        </td>
                        <td>
                            <strong>{{ $registration->captain_name }}</strong>
                            <br><small style="color:#64748b;">{{ $registration->captain_phone }}</small>
                            @if($registration->user)
                                <br><small style="color:#94a3b8;">Akun: {{ $registration->user->name }}</small>
                            @endif
                        </td>
                        <td>{{ $registration->member_count }} orang</td>
                        <td style="max-width:220px;">{{ $registration->notes ?: '—' }}</td>
                        <td>
                            <span style="display:inline-block;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:800;{{ $statusStyles[$registration->status] ?? 'background:#edf1f5;color:#536273' }}">
                                {{ strtoupper($registration->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-row wrap">
                                @if($registration->whatsapp_number)
                                    <a class="btn-ui btn-green" target="_blank" rel="noopener"
                                       href="https://wa.me/{{ $registration->whatsapp_number }}?text={{ urlencode('Halo ' . $registration->captain_name . ', kami dari penyelenggara event ' . $event->title . '. Terima kasih telah mendaftarkan tim ' . $registration->team_name . '.') }}"
                                       style="padding:6px 12px;font-size:12px;">
                                        <i class="fa-brands fa-whatsapp"></i> Chat
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('admin.events.registrations.status', [$event, $registration]) }}">
                                    @csrf
                                    <select class="select-ui" name="status" onchange="this.form.submit()" style="padding:6px 10px;font-size:12px;">
                                        @foreach(['Baru', 'Dihubungi', 'Terkonfirmasi', 'Ditolak'] as $status)
                                            <option value="{{ $status }}" @selected($registration->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:35px;color:#718078;">
                            <i class="fa-regular fa-user" style="font-size:32px;margin-bottom:10px;display:block;color:#a1b3a8;"></i>
                            Belum ada tim yang mendaftar untuk event ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
