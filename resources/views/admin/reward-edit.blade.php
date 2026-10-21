@extends('layouts.admin', ['title' => 'Edit Reward', 'heading' => 'Edit Reward'])

@section('content')
<form class="form-card" action="{{ route('admin.rewards.update', $reward) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid-2-form">
        <div class="form-group">
            <label>Judul Reward</label>
            <input class="input-ui" name="title" value="{{ old('title', $reward->title) }}" required>
        </div>
        <div class="form-group">
            <label>Poin</label>
            <input class="input-ui" type="number" name="points_required" value="{{ old('points_required', $reward->points_required) }}" required>
        </div>
        <div class="form-group">
            <label>Badge</label>
            <input class="input-ui" name="badge" value="{{ old('badge', $reward->badge) }}">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select class="select-ui" name="status">
                @foreach(['Aktif', 'Nonaktif'] as $status)
                    <option value="{{ $status }}" {{ old('status', $reward->status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>Deskripsi</label>
        <textarea class="textarea-ui" name="description">{{ old('description', $reward->description) }}</textarea>
    </div>

    <div class="form-group preview-shell {{ $reward->image ? 'has-image' : '' }}">
        <label>Foto Saat Ini</label>
        @if($reward->image)
            <img id="reward-preview-image" class="preview-img" src="{{ asset($reward->image) }}" alt="{{ $reward->title }}">
        @else
            <div id="reward-preview-placeholder" class="reward-banner">{{ $reward->badge ?: 'Reward' }}</div>
            <img id="reward-preview-image" class="preview-img" style="display:none" alt="Preview Reward">
        @endif
    </div>

    <div class="form-group">
        <label>Ganti Foto Reward</label>
        <input class="input-ui" type="file" accept="image/*" name="image" data-preview="reward-preview-image">
    </div>

    <div class="form-actions-right">
        <a class="btn-ui btn-gray" href="{{ route('admin.rewards') }}">Batal</a>
        <button class="btn-ui btn-green" type="submit">Update Reward</button>
    </div>
</form>
@endsection
