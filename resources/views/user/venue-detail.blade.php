@extends('layouts.user')

@section('content')
@if($venue->photo)
    <div style="margin-bottom:18px;border-radius:18px;overflow:hidden;max-height:340px"><img src="{{ asset($venue->photo) }}" alt="{{ $venue->name }}" style="width:100%;height:340px;object-fit:cover"></div>
@endif
@if(!empty($venue->gallery))
    <div class="u-cards" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:18px">@foreach($venue->gallery as $image)<div class="u-card" style="overflow:hidden"><img src="{{ asset($image) }}" alt="Foto {{ $venue->name }}" style="width:100%;height:140px;object-fit:cover"></div>@endforeach</div>
@endif
<div class="u-section-head">
    <div>
        <h2>{{ $venue->name }}</h2>
        <p>{{ $venue->address }} · {{ $venue->contact_phone }}</p>
        @if($venue->operating_hours)
            <p><i class="fa-regular fa-clock"></i> Jam operasional: {{ $venue->operating_hours }}</p>
        @endif
        @if($venue->description)
            <p>{{ $venue->description }}</p>
        @endif
        @if(!empty($venue->facilities))
            <p><strong>Fasilitas:</strong> {{ implode(', ', $venue->facilities) }}</p>
        @endif
        @if($venue->google_maps_url)
            <a href="{{ $venue->google_maps_url }}" target="_blank" rel="noopener">Lihat lokasi di Google Maps</a>
        @endif
    </div>
    <a class="u-btn ghost" href="{{ route('user.venues') }}">Kembali ke venue</a>
</div>

<div class="u-cards" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
    @forelse($courts as $court)
        <article class="u-card">
            @php
                $courtPhotos = collect(array_merge([$court->foto], $court->foto_gallery ?? []))
                    ->filter(fn ($photo) => $photo && file_exists(public_path($photo)))
                    ->unique()->values();
            @endphp
            <div class="u-card-img u-court-gallery" style="height:170px" data-user-slider>
                <div class="u-gallery-track">
                    @forelse($courtPhotos as $photo)
                        <img class="u-gallery-slide {{ $loop->first ? 'active' : '' }}" src="{{ asset($photo) }}" alt="{{ $court->nama }} foto {{ $loop->iteration }}">
                    @empty
                        <div class="u-gallery-empty"><i class="fa-solid fa-futbol"></i></div>
                    @endforelse
                </div>
                @if($courtPhotos->count() > 1)
                    <button type="button" class="u-gallery-arrow prev" data-user-prev aria-label="Foto sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="u-gallery-arrow next" data-user-next aria-label="Foto berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
                    <span class="u-gallery-count"><span data-user-current>1</span> / {{ $courtPhotos->count() }}</span>
                @endif
            </div>
            <div class="u-card-body">
                <span class="u-tag">{{ $court->jenis }}</span>
                <h3>{{ $court->nama }}</h3>
                <p>Rp {{ number_format($court->harga, 0, ',', '.') }} / jam</p>
                <a class="u-btn" href="{{ route('courts.detail', $court) }}">Pilih Lapangan</a>
            </div>
        </article>
    @empty
        <p>Venue ini belum memiliki lapangan yang tersedia.</p>
    @endforelse
</div>
@endsection
