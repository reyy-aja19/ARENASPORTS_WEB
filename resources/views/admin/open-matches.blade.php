@extends('layouts.admin', ['title' => 'Manajemen Event & Open Match', 'heading' => 'Manajemen Event & Open Match'])

@section('content')
<div class="openmatch-admin-shell">
    <div class="surface" style="padding:18px 20px;margin-bottom:18px;border-radius:16px;background:linear-gradient(115deg,#effaf1,#ffffff);border:1px solid #d9efdf"><strong><i class="fa-solid fa-calendar-star"></i> Event ArenaSports · khusus Superadmin</strong><p style="margin:7px 0 0;color:#52645a">Gunakan halaman ini untuk mengumumkan event resmi. Pembuatan dan pengelolaan event dibatasi melalui middleware Superadmin; pengguna hanya dapat melihat dan bergabung sesuai aturan Open Match.</p></div>
    <div class="openmatch-mobile-head">
        <div>
            <h2>Manajemen Event & Open Match</h2>
        </div>
        <button type="button" class="btn-ui btn-green" onclick="document.getElementById('openMatchForm').scrollIntoView({behavior:'smooth'})"><i class="fa-solid fa-plus"></i> Buat Open Match</button>
    </div>

    <div class="openmatch-filter-row">
        <div class="mini-search"><i class="fa-solid fa-magnifying-glass"></i><input data-local-filter type="text" placeholder="Cari match"></div>
        <select class="mini-select" data-local-filter-select="jenis"><option value="">Semua cabang</option><option>Futsal</option><option>Badminton</option><option>Basket</option><option>Voli</option></select>
        <select class="mini-select" data-local-filter-select="status"><option value="">Semua status</option><option>Open</option><option>Penuh</option><option>Selesai</option><option>Dibatalkan</option></select>
    </div>

    <form id="openMatchForm" class="surface compact-form openmatch-form-mobile" action="{{ route('admin.openmatches.store') }}" method="POST">
        @csrf
        <h3>Buat Event / Open Match Resmi</h3>
        <div class="openmatch-form-section">Detail Pertandingan Dasar</div>
        <div class="grid-4">
            <div class="form-group"><label>Judul Match</label><input class="input-ui" name="title" placeholder="Contoh: Tim kaciw" required></div>
            <div class="form-group"><label>Cabang Olahraga</label><select class="select-ui" name="jenis" required><option>Futsal</option><option>Badminton</option><option>Basket</option><option>Voli</option></select></div>
            <div class="form-group"><label>Jam Mulai</label><input class="input-ui" type="time" name="start_time" value="19:00"></div>
            <div class="form-group"><label>Jam Selesai</label><input class="input-ui" type="time" name="end_time" value="20:00"></div>
        </div>
        <div class="grid-4">
            <div class="form-group"><label>Tanggal</label><input class="input-ui" type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="form-group"><label>Slot Dibutuhkan</label><input class="input-ui" type="number" name="jumlah_pemain" value="5" min="1" max="99" required></div>
            <div class="form-group"><label>Sudah Join</label><input class="input-ui" type="number" name="jumlah_bergabung" value="0" min="0" max="99"></div>
            <div class="form-group"><label>Status</label><select class="select-ui" name="status" required><option>Open</option><option>Penuh</option><option>Selesai</option><option>Dibatalkan</option></select></div>
        </div>
        <div class="form-group"><label>Booking Terkait</label><select class="select-ui" name="booking_id"><option value="">Tanpa booking terkait</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->user?->name }} • {{ $booking->lapangan?->nama }} • {{ optional($booking->booking_date)->format('d M Y') }} {{ $booking->start_time }}-{{ $booking->end_time }}</option>@endforeach</select></div>
        <div class="form-group"><label>Deskripsi Tambahan</label><textarea class="textarea-ui" name="deskripsi" placeholder="Ketikkan detail lawan, level, aturan main, atau kebutuhan tambahan."></textarea></div>
        <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green"><i class="fa-solid fa-paper-plane"></i> Publikasikan Event</button></div>
    </form>

    <div class="openmatch-grid mobile-inspired mt-16">
        @forelse($matches as $match)
            @php
                $percent = $match->jumlah_pemain > 0 ? min(100, round(($match->jumlah_bergabung / $match->jumlah_pemain) * 100)) : 0;
                $statusClass = strtolower(str_replace(' ', '-', $match->status));
            @endphp
            <div class="openmatch-card mobile-match-card" data-search-item data-jenis="{{ $match->jenis }}" data-status="{{ $match->status }}">
                <div class="match-avatar"><i class="fa-solid fa-people-group"></i></div>
                <div class="openmatch-card-head">
                    <div><h3>{{ $match->title }}</h3><span class="openmatch-sport">{{ $match->jenis }}</span></div>
                    <span class="openmatch-status status-{{ $statusClass }}">{{ $match->status }}</span>
                </div>
                <div class="openmatch-meta mobile-meta">
                    <span><i class="fa-regular fa-calendar"></i> {{ optional($match->tanggal)->format('d/m/Y') }}</span>
                    <span><i class="fa-regular fa-clock"></i> {{ $match->start_time ?? '-' }} - {{ $match->end_time ?? '-' }}</span>
                    <span><i class="fa-solid fa-location-dot"></i> {{ $match->booking?->lapangan?->nama ?? 'Belum terkait booking' }}</span>
                </div>
                <div class="openmatch-progress"><div class="openmatch-progress-text"><strong>{{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }}</strong><span>slot terisi</span></div><div class="progress-track"><span style="width: {{ $percent }}%"></span></div></div>
                @if($match->deskripsi)<div class="openmatch-desc">{{ $match->deskripsi }}</div>@endif
                <details class="openmatch-edit"><summary>Edit</summary><form action="{{ route('admin.openmatches.update', $match) }}" method="POST" class="openmatch-inline-form">@csrf @method('PUT')<div class="grid-4"><div class="form-group"><label>Judul</label><input class="input-ui" name="title" value="{{ $match->title }}" required></div><div class="form-group"><label>Jenis</label><select class="select-ui" name="jenis">@foreach(['Futsal','Badminton','Basket','Voli'] as $jenis)<option value="{{ $jenis }}" {{ $match->jenis === $jenis ? 'selected' : '' }}>{{ $jenis }}</option>@endforeach</select></div><div class="form-group"><label>Tanggal</label><input class="input-ui" type="date" name="tanggal" value="{{ optional($match->tanggal)->format('Y-m-d') }}" required></div><div class="form-group"><label>Status</label><select class="select-ui" name="status">@foreach(['Open','Penuh','Selesai','Dibatalkan'] as $status)<option value="{{ $status }}" {{ $match->status === $status ? 'selected' : '' }}>{{ $status }}</option>@endforeach</select></div></div><div class="grid-4"><div class="form-group"><label>Mulai</label><input class="input-ui" type="time" name="start_time" value="{{ $match->start_time }}"></div><div class="form-group"><label>Selesai</label><input class="input-ui" type="time" name="end_time" value="{{ $match->end_time }}"></div><div class="form-group"><label>Target</label><input class="input-ui" type="number" name="jumlah_pemain" min="1" max="99" value="{{ $match->jumlah_pemain }}" required></div><div class="form-group"><label>Join</label><input class="input-ui" type="number" name="jumlah_bergabung" min="0" max="99" value="{{ $match->jumlah_bergabung }}"></div></div><div class="form-group"><label>Booking Terkait</label><select class="select-ui" name="booking_id"><option value="">Tanpa booking terkait</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}" {{ $match->booking_id == $booking->id ? 'selected' : '' }}>{{ $booking->user?->name }} • {{ $booking->lapangan?->nama }} • {{ optional($booking->booking_date)->format('d M Y') }}</option>@endforeach</select></div><div class="form-group"><label>Deskripsi</label><textarea class="textarea-ui" name="deskripsi">{{ $match->deskripsi }}</textarea></div><div class="btn-row wrap"><button class="btn-ui btn-green" type="submit">Simpan</button></div></form></details>
                <div class="btn-row wrap openmatch-actions"><form method="POST" action="{{ route('admin.openmatches.toggle', $match) }}">@csrf<button class="btn-ui btn-gray" type="submit">Ubah Status</button></form><form method="POST" action="{{ route('admin.openmatches.delete', $match) }}" onsubmit="return confirm('Hapus open match ini?')">@csrf @method('DELETE')<button class="btn-ui btn-red" type="submit">Hapus</button></form></div>
            </div>
        @empty
            <div class="center-box">Belum ada data open match.</div>
        @endforelse
    </div>
</div>
@endsection
