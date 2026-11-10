@extends('layouts.user')

@section('content')
<style>
    .vd-page{color:#183e2d;padding-bottom:36px}
    .vd-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1.4fr) minmax(220px,.8fr);gap:22px;align-items:center;padding:28px;border-radius:22px;background:linear-gradient(120deg,#075b38,#168451);color:#fff;margin-bottom:25px}
    .vd-hero h1{margin:8px 0;color:#fff;font-size:clamp(25px,3vw,35px);font-weight:850}
    .vd-hero p{margin:0;color:#e3f7e9;line-height:1.65}
    .vd-hero .vd-cover{width:100%;height:190px;border-radius:16px;object-fit:cover;border:3px solid #ffffff40}
    .vd-eyebrow{display:inline-block;padding:7px 12px;border-radius:30px;background:#ffffff20;color:#e3ffeb;font-size:12px;font-weight:800}
    .vd-facts{display:flex;flex-wrap:wrap;gap:9px;margin-top:16px}
    .vd-fact{padding:8px 11px;border-radius:10px;background:#ffffff18;color:#fff;font-size:12px}
    .vd-section-head{display:flex;justify-content:space-between;align-items:end;gap:12px;margin:22px 0 15px}
    .vd-section-head h2{margin:0;color:#164b31;font-size:22px;font-weight:850}
    .vd-section-head p{margin:5px 0 0;color:#748279;font-size:13px}
    .vd-back{display:inline-flex;gap:8px;align-items:center;padding:10px 14px;border:1px solid #cfe5d5;border-radius:10px;color:#087747;text-decoration:none;font-size:13px;font-weight:750;background:#f3fbf5}
    .vd-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin:18px 0}
    .vd-info{padding:15px;border:1px solid #e0eee4;border-radius:14px;background:#fff}
    .vd-info i{color:#14864f;font-size:17px;margin-bottom:8px}
    .vd-info strong{display:block;color:#214c34;font-size:13px;margin-bottom:4px}
    .vd-info span{color:#748279;font-size:12px;line-height:1.5}
    .vd-gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:18px 0}
    .vd-gallery img{width:100%;height:125px;object-fit:cover;border-radius:12px}
    .vd-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px}
    .vd-card{overflow:hidden;border:1px solid #e2eee6;border-radius:17px;background:#fff;box-shadow:0 6px 20px #164b310d}
    .vd-photo{height:175px;background:linear-gradient(135deg,#e2f6e8,#c1e9ce)}
    .vd-photo img{width:100%;height:100%;object-fit:cover}
    .vd-photo-empty{height:100%;display:grid;place-items:center;color:#168b50;font-size:40px}
    .vd-card-body{padding:17px}
    .vd-card-body h3{margin:9px 0;color:#19452f;font-size:18px;font-weight:800}
    .vd-chip{display:inline-flex;padding:6px 10px;border-radius:30px;background:#e3f7e9;color:#087747;font-size:11px;font-weight:800}
    .vd-chip.repair{background:#fff3dc;color:#94600b}
    .vd-chip.full{background:#eef0f2;color:#626d75}
    .vd-card-meta{display:flex;gap:8px;align-items:flex-start;margin:9px 0;color:#718078;font-size:12px;line-height:1.5}
    .vd-card-meta i{width:15px;color:#168b50;margin-top:2px}
    .vd-price{font-size:17px;font-weight:850;color:#087747;margin:13px 0}
    .vd-note{padding:11px 12px;border-radius:10px;background:#fff8e9;border:1px solid #f4e0b8;color:#855b16;font-size:12px;line-height:1.5;margin:12px 0}
    .vd-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;border-radius:10px;background:#12834e;color:#fff!important;text-decoration:none;font-size:13px;font-weight:800}
    .vd-btn:hover{background:#075b38}
    .vd-empty{padding:30px;text-align:center;border:1px dashed #b9d9c2;background:#f3fbf5;border-radius:15px;color:#748279;grid-column:1/-1}
    @media(max-width:680px){.vd-hero{grid-template-columns:1fr;padding:22px}.vd-hero .vd-cover{height:160px}.vd-section-head{align-items:flex-start;flex-direction:column}}
</style>

@php
    $companyTitle = $venue->company_name ?: $venue->name;
    $availableCount = $courts->where('status', 'Tersedia')->count();
@endphp

<div class="vd-page">
    <section class="vd-hero">
        <div>
            <span class="vd-eyebrow"><i class="fa-solid fa-building"></i> Profil perusahaan / venue</span>
            <h1>{{ $companyTitle }}</h1>
            @if($venue->name && $venue->name !== $companyTitle)
                <p><i class="fa-solid fa-location-crosshairs"></i> Cabang / lokasi: {{ $venue->name }}</p>
            @endif
            <p style="margin-top:8px">{{ $venue->description ?: $venue->company_description ?: 'Temukan lapangan olahraga yang tersedia di lokasi ini.' }}</p>
            <div class="vd-facts">
                <span class="vd-fact"><i class="fa-solid fa-futbol"></i> {{ implode(', ', $venue->sports ?? []) ?: 'Olahraga belum ditentukan' }}</span>
                <span class="vd-fact"><i class="fa-solid fa-layer-group"></i> {{ $courts->count() }} lapangan</span>
                <span class="vd-fact"><i class="fa-solid fa-circle-check"></i> {{ $availableCount }} tersedia</span>
            </div>
        </div>
        @if($venue->photo)
            <img class="vd-cover" src="{{ asset($venue->photo) }}" alt="Foto {{ $companyTitle }}">
        @else
            <div class="vd-cover" style="display:grid;place-items:center;background:#ffffff18;color:#dff9e6;font-size:70px"><i class="fa-solid fa-building"></i></div>
        @endif
    </section>

    <div class="vd-section-head">
        <div>
            <h2>Informasi Venue</h2>
            <p>Informasi perusahaan, alamat, dan fasilitas lokasi.</p>
        </div>
        <a class="vd-back" href="{{ route('user.venues') }}"><i class="fa-solid fa-arrow-left"></i> Kembali ke daftar venue</a>
    </div>

    <div class="vd-info-grid">
        <div class="vd-info"><i class="fa-solid fa-location-dot"></i><strong>Alamat</strong><span>{{ $venue->address ?: 'Belum diisi' }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-phone"></i><strong>Kontak</strong><span>{{ $venue->contact_phone ?: $venue->company_phone ?: 'Belum diisi' }}</span></div>
        <div class="vd-info"><i class="fa-regular fa-clock"></i><strong>Jam operasional</strong><span>{{ $venue->operating_hours ?: 'Belum diinformasikan' }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-map"></i><strong>Lokasi peta</strong><span>@if($venue->google_maps_url)<a href="{{ $venue->google_maps_url }}" target="_blank" rel="noopener noreferrer" style="color:#087747;font-weight:750">Buka Google Maps</a>@else Belum tersedia @endif</span></div>
        <div class="vd-info"><i class="fa-solid fa-building"></i><strong>Nama perusahaan</strong><span>{{ $venue->company_name ?: $venue->name }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-list-check"></i><strong>Fasilitas</strong><span>{{ implode(', ', $venue->facilities ?? []) ?: 'Belum diinformasikan' }}</span></div>
    </div>

    @if(!empty($venue->gallery))
        <div class="vd-gallery">
            @foreach($venue->gallery as $image)
                <img src="{{ asset($image) }}" alt="Galeri {{ $companyTitle }}" loading="lazy">
            @endforeach
        </div>
    @endif

    <div class="vd-section-head">
        <div>
            <h2>Daftar Lapangan</h2>
            <p>Pilih lapangan yang ingin kamu gunakan. Status perbaikan ditampilkan agar kamu mengetahui kondisi terbaru.</p>
        </div>
    </div>

    <div class="vd-grid">
        @forelse($courts as $court)
            @php
                $photos = collect(array_merge([$court->foto], $court->foto_gallery ?? []))->filter()->unique()->values();
                $isRepair = in_array($court->status, ['Perawatan', 'Perbaikan'], true);
            @endphp
            <article class="vd-card">
                <div class="vd-photo">
                    @if($photos->isNotEmpty())
                        <img src="{{ asset($photos->first()) }}" alt="{{ $court->nama }}" loading="lazy">
                    @else
                        <div class="vd-photo-empty"><i class="fa-solid fa-futbol"></i></div>
                    @endif
                </div>
                <div class="vd-card-body">
                    <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;flex-wrap:wrap">
                        <span class="vd-chip">{{ $court->jenis }}</span>
                        @if($court->status === 'Tersedia')
                            <span class="vd-chip">Tersedia</span>
                        @elseif($isRepair)
                            <span class="vd-chip repair"><i class="fa-solid fa-screwdriver-wrench"></i> {{ $court->status }}</span>
                        @else
                            <span class="vd-chip full">{{ $court->status ?: 'Tidak tersedia' }}</span>
                        @endif
                    </div>
                    <h3>{{ $court->nama }}</h3>
                    <p class="vd-card-meta"><i class="fa-solid fa-location-dot"></i><span>{{ $court->lokasi ?: $venue->address }}</span></p>
                    @if($court->deskripsi)
                        <p style="color:#718078;font-size:12px;line-height:1.6">{{ $court->deskripsi }}</p>
                    @endif
                    @if($isRepair)
                        <div class="vd-note"><strong><i class="fa-solid fa-triangle-exclamation"></i> Informasi perbaikan</strong><br>{{ $court->maintenance_note ?: 'Lapangan sedang dalam perawatan/perbaikan dan belum dapat dipesan.' }}</div>
                    @elseif($court->status !== 'Tersedia')
                        <div class="vd-note">Lapangan ini sementara belum dapat dipesan.</div>
                    @else
                        <div class="vd-price">Rp {{ number_format($court->harga,0,',','.') }} <span style="font-size:11px;font-weight:600;color:#78877e">/ jam</span></div>
                        <a class="vd-btn" href="{{ route('courts.detail', $court) }}">Pilih Lapangan <i class="fa-solid fa-arrow-right"></i></a>
                    @endif
                </div>
            </article>
        @empty
            <div class="vd-empty"><i class="fa-solid fa-futbol" style="font-size:30px;color:#168b50"></i><p>Venue ini belum memiliki lapangan.</p></div>
        @endforelse
    </div>
</div>
@endsection
