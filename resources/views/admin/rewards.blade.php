@extends('layouts.admin', ['title' => 'Poin Penukaran', 'heading' => 'Poin Penukaran'])
@section('content')
<form class="surface compact-form" action="{{ route('admin.rewards.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="grid-4">
        <div class="form-group"><label>Judul Reward</label><input class="input-ui" name="title"></div>
        <div class="form-group"><label>Poin</label><input class="input-ui" type="number" name="points_required"></div>
        <div class="form-group"><label>Badge</label><input class="input-ui" name="badge"></div>
        <div class="form-group"><label>Status</label><select class="select-ui" name="status"><option>Aktif</option><option>Nonaktif</option></select></div>
    </div>
    <div class="form-group"><label>Deskripsi</label><textarea class="textarea-ui" name="description"></textarea></div>
    <div class="form-group preview-shell"><label>Preview Gambar</label><img id="reward-create-preview" class="preview-img" style="display:none" alt="Preview Reward"></div>
    <div class="form-group"><label>Gambar Reward</label><input class="input-ui" type="file" accept="image/*" name="image" data-preview="reward-create-preview"></div>
    <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green">Tambah Reward</button></div>
</form>
<div class="reward-grid mt-16">
    @foreach($rewards as $reward)
    <div class="reward-card">
        @if($reward->image)<img class="reward-photo" src="{{ asset($reward->image) }}" alt="{{ $reward->title }}">@else<div class="reward-banner">{{ $reward->badge }}</div>@endif
        <div style="font-weight:700; font-size:13px;">{{ $reward->title }}</div>
        <div class="muted">{{ $reward->points_required }} Poin</div>
        <div class="muted">{{ $reward->status }}</div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px; flex-wrap:wrap;">
            <a class="btn-ui btn-green" href="{{ route('admin.rewards.edit', $reward) }}">Edit</a>
            <form method="POST" action="{{ route('admin.rewards.toggle', $reward) }}">@csrf<button class="btn-ui btn-gray">Toggle</button></form>
            <form method="POST" action="{{ route('admin.rewards.delete', $reward) }}" onsubmit="return confirm('Hapus reward ini?')">@csrf @method('DELETE')<button class="btn-ui btn-red">Hapus</button></form>
        </div>
    </div>
    @endforeach
</div>
<div class="data-table mt-16">
    <table>
        <thead><tr><th>No</th><th>Tanggal</th><th>User</th><th>Hadiah</th><th>Kode QR</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($redemptions as $i => $item)
            <tr><td>{{ $i + 1 }}</td><td>{{ optional($item->redeemed_at)->format('d M Y H:i') }}</td><td>{{ $item->user?->name }}</td><td>{{ $item->reward?->title }}</td><td>{{ $item->qr_code }}</td><td>{{ $item->status }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
