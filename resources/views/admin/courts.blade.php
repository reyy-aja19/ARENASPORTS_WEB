@extends('layouts.admin', ['title' => 'Manajemen Lapangan', 'heading' => 'Management Lapangan'])
@section('content')
<div class="section-actions">
    <div class="muted">Total lapangan: {{ $courts->count() }}</div>
    <a class="btn-ui btn-green" href="{{ route('admin.courts.create') }}"><i class="fa-solid fa-plus"></i> Tambah Lapangan</a>
</div>

<div class="court-list">
    @forelse($courts as $court)
        @php
            $gallery = collect(array_merge([$court->foto], $court->foto_gallery ?? []))->filter()->unique()->values();
            $facilities = collect($court->fasilitas ?? [])->filter()->values();
            $fallbackIcon = strtolower($court->jenis) === 'futsal' ? 'fa-futbol' : 'fa-table-tennis-paddle-ball';
        @endphp
        <div class="court-card court-card-modern {{ $loop->first ? 'featured' : '' }}" data-search-item>
            <div class="court-slider" data-slider>
                <div class="court-slide-track">
                    @forelse($gallery as $photo)
                        <img class="court-photo slide-photo {{ $loop->first ? 'active' : '' }}" src="{{ asset($photo) }}" alt="{{ $court->nama }}">
                    @empty
                        <div class="court-image {{ strtolower($court->jenis) === 'futsal' ? 'futsal' : '' }} slide-photo active">
                            <i class="fa-solid {{ $fallbackIcon }} court-empty-icon"></i>
                        </div>
                    @endforelse
                </div>
                @if($gallery->count() > 1)
                    <button type="button" class="slider-btn prev" data-slide-prev><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="slider-btn next" data-slide-next><i class="fa-solid fa-chevron-right"></i></button>
                    <div class="slider-count"><span data-slide-current>1</span>/{{ $gallery->count() }}</div>
                @endif
            </div>

            <div class="court-info" style="flex:1;">
                <h3>{{ $court->nama }}</h3>
                <div class="court-meta">
                    <span><i class="fa-solid fa-star" style="color:#f4c542"></i> {{ $court->rating }}</span>
                    <span><i class="fa-solid fa-location-dot"></i> {{ $court->lokasi }}</span>
                    <span>{{ $court->jenis }}</span>
                    <span>Status: {{ $court->status }}</span>
                </div>
                <div class="muted" style="margin-top:8px;">Rp {{ number_format($court->harga,0,',','.') }}/jam</div>
                @if($court->deskripsi)
                    <div class="muted" style="margin-top:6px;">{{ $court->deskripsi }}</div>
                @endif

                <div class="facility-list">
                    @forelse($facilities as $facility)
                        <span class="facility-chip"><i class="fa-solid fa-circle-check"></i> {{ $facility }}</span>
                    @empty
                        <span class="facility-chip muted-chip">Belum ada fasilitas</span>
                    @endforelse
                </div>
            </div>

            <div class="btn-col">
                <a class="btn-ui btn-green" href="{{ route('admin.courts.edit', $court) }}"><i class="fa-solid fa-pen"></i> Edit</a>
                <form method="POST" action="{{ route('admin.courts.delete', $court) }}" onsubmit="return confirm('Hapus lapangan ini?')">
                    @csrf @method('DELETE')
                    <button class="btn-ui btn-red" type="submit"><i class="fa-solid fa-trash"></i> Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <div class="center-box">Belum ada data lapangan.</div>
    @endforelse
</div>
@endsection
