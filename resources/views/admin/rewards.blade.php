@extends('layouts.admin', ['title' => 'Poin Penukaran', 'heading' => 'Poin & Voucher'])
@section('content')
@php
    $adminId = (int) session('admin_user_id');
    $canManageReward = fn ($reward) => ($isSuperadmin ?? false) || (int) $reward->owner_admin_id === $adminId;
@endphp

@if($errors->any())
    <div class="flash flash-error" style="margin-bottom:14px;">
        <strong>Gagal menyimpan reward:</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form class="surface compact-form" action="{{ route('admin.rewards.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="grid-4">
        <div class="form-group">
            <label>Judul Reward</label>
            <input class="input-ui" name="title" value="{{ old('title') }}" required maxlength="255">
        </div>
        <div class="form-group">
            <label>Poin</label>
            <input class="input-ui" type="number" name="points_required" value="{{ old('points_required') }}" required min="0" step="1">
        </div>
        <div class="form-group">
            <label>Badge</label>
            <input class="input-ui" name="badge" value="{{ old('badge') }}" maxlength="50" placeholder="Contoh: voucher">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select class="select-ui" name="status" required>
                <option value="Aktif" @selected(old('status', 'Aktif') === 'Aktif')>Aktif</option>
                <option value="Nonaktif" @selected(old('status') === 'Nonaktif')>Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="grid-2-form">
        <div class="form-group">
            <label>Berlaku di Venue</label>
            <select class="select-ui" name="venue_id">
                <option value="">Global / Semua venue</option>
                @foreach(($venues ?? collect()) as $venue)
                    <option value="{{ $venue->id }}" @selected((string) old('venue_id') === (string) $venue->id)>{{ $venue->name }}</option>
                @endforeach
            </select>
            <small class="muted">Kosongkan jika reward ini berlaku umum. Jika dipilih, user akan melihat reward ini berlaku di venue tersebut.</small>
        </div>
        <div class="form-group">
            <label>Gambar Reward</label>
            <input class="input-ui" type="file" accept="image/*" name="image" data-preview="reward-create-preview">
        </div>
    </div>
    <div class="form-group">
        <label>Deskripsi</label>
        <textarea class="textarea-ui" name="description" placeholder="Jelaskan manfaat reward, syarat penggunaan, atau cara penukaran.">{{ old('description') }}</textarea>
    </div>
    <div class="form-group preview-shell"><label>Preview Gambar</label><img id="reward-create-preview" class="preview-img" style="display:none" alt="Preview Reward"></div>
    <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green" type="submit">Tambah Reward</button></div>
</form>

<div class="reward-grid mt-16">
    @forelse($rewards as $reward)
        @php $manageable = $canManageReward($reward); @endphp
        <div class="reward-card">
            @if($reward->image)
                <img class="reward-photo" src="{{ asset($reward->image) }}" alt="{{ $reward->title }}">
            @else
                <div class="reward-banner">{{ $reward->badge ?: 'Reward' }}</div>
            @endif
            <div style="font-weight:700; font-size:13px;">{{ $reward->title }}</div>
            <div class="muted">{{ number_format($reward->points_required) }} Poin</div>
            <div class="muted">Status: {{ $reward->status }}</div>
            <div class="muted">Berlaku: {{ $reward->venue?->name ?? 'Global / Semua venue' }}</div>
            <div class="muted">Pemilik: {{ $reward->owner?->name ?? 'ArenaSports' }}</div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px; flex-wrap:wrap;">
                @if($manageable)
                    <a class="btn-ui btn-green" href="{{ route('admin.rewards.edit', $reward) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.rewards.toggle', $reward) }}">@csrf<button class="btn-ui btn-gray" type="submit">Toggle</button></form>
                    <form method="POST" action="{{ route('admin.rewards.delete', $reward) }}" onsubmit="return confirm('Hapus reward ini?')">@csrf @method('DELETE')<button class="btn-ui btn-red" type="submit">Hapus</button></form>
                @else
                    <span class="muted" style="font-size:12px;">Reward milik admin lain</span>
                @endif
            </div>
        </div>
    @empty
        <div class="surface" style="padding:18px;">Belum ada reward.</div>
    @endforelse
</div>

<div class="data-table mt-16">
    <table>
        <thead><tr><th>No</th><th>Tanggal</th><th>User</th><th>Hadiah</th><th>Berlaku di</th><th>Kode QR</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($redemptions as $i => $item)
            @php
                $adminId = (int) session('admin_user_id');
                $canVerify = ($isSuperadmin ?? false) || (int) ($item->owner_admin_id ?? $item->reward?->owner_admin_id) === $adminId;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ optional($item->redeemed_at)->format('d M Y H:i') }}
                    @if($item->verified_at)
                        <div class="muted" style="font-size:11px;">Diverifikasi: {{ $item->verified_at->format('d M Y H:i') }}</div>
                    @endif
                </td>
                <td>{{ $item->user?->name }}</td>
                <td>{{ $item->reward?->title }}</td>
                <td>{{ $item->venue?->name ?? $item->reward?->venue?->name ?? 'Global' }}</td>
                <td>
                    @if($item->qr_code)
                        @php
                            $qrText = "ArenaSports Penukaran Reward\n"
                                . "Hadiah: " . ($item->reward?->title ?? 'Reward') . "\n"
                                . "Kode: " . ($item->qr_code ?? '-') . "\n"
                                . "Berlaku: " . ($item->venue?->name ?? $item->reward?->venue?->name ?? 'Global') . "\n"
                                . "User: " . ($item->user?->name ?? '-');
                        @endphp
                        <div style="background:#fff;padding:6px;border:1px solid #e5e7eb;border-radius:8px;display:inline-block;margin-bottom:4px">
                            <img loading="lazy" width="96" height="96" style="display:block;width:96px;height:96px;image-rendering:pixelated" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=10&ecc=M&data={{ urlencode($qrText) }}" alt="QR {{ $item->qr_code }}">
                        </div>
                        <div style="font-size:11px;color:#475569;word-break:break-all">{{ $item->qr_code }}</div>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->status }}</td>
                <td>
                    @if($canVerify && $item->status !== 'Diverifikasi')
                        <form method="POST" action="{{ route('admin.redemptions.verify', $item) }}" onsubmit="return confirm('Verifikasi penukaran reward ini?')">
                            @csrf
                            <button class="btn-ui btn-green" type="submit">Verifikasi</button>
                        </form>
                    @elseif($item->status === 'Diverifikasi')
                        <span class="muted" style="font-size:12px;">Sudah diverifikasi</span>
                    @else
                        <span class="muted" style="font-size:12px;">-</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="muted">Belum ada riwayat penukaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
