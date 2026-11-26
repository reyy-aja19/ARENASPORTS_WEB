@extends('layouts.user')

@section('content')
<style>
    .vd-page{color:#183e2d;padding-bottom:40px}
    .vd-hero{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:26px;align-items:stretch;margin-bottom:24px}
    .vd-cover-wrap{position:relative;min-height:310px;border-radius:24px;overflow:hidden;background:linear-gradient(135deg,#d9f2e1,#b9e6c8)}
    .vd-cover-track{display:flex;height:100%;transition:transform .35s ease}
    .vd-cover-slide{min-width:100%;height:100%}
    .vd-cover-slide img{width:100%;height:100%;object-fit:cover;display:block}
    .vd-cover-empty{height:100%;min-height:310px;display:grid;place-items:center;color:#168b50;font-size:64px}
    .vd-cover-arrow{position:absolute;top:50%;transform:translateY(-50%);width:38px;height:38px;border:0;border-radius:50%;background:#ffffffd9;color:#0f6b3d;display:grid;place-items:center;cursor:pointer;box-shadow:0 4px 14px #0002;z-index:2}
    .vd-cover-arrow.prev{left:12px}.vd-cover-arrow.next{right:12px}
    .vd-cover-dots{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;z-index:2}
    .vd-cover-dots button{width:8px;height:8px;border:0;border-radius:50%;background:#ffffff99;cursor:pointer}
    .vd-cover-dots button.active{background:#fff;width:20px;border-radius:20px}
    .vd-hero-body{padding:28px;border-radius:24px;background:linear-gradient(135deg,#075b38,#11824e);color:#fff;display:flex;flex-direction:column;justify-content:center}
    .vd-eyebrow{display:inline-flex;width:max-content;gap:7px;align-items:center;padding:7px 12px;border-radius:30px;background:#ffffff1f;color:#e3ffeb;font-size:12px;font-weight:800;margin-bottom:12px}
    .vd-hero h1{margin:0 0 10px;color:#fff;font-size:clamp(26px,3vw,38px);font-weight:850;line-height:1.12}
    .vd-managed{margin:0 0 10px;color:#d9f7e3;font-size:13px}
    .vd-hero p.vd-desc{margin:0;color:#e3f7e9;line-height:1.7;font-size:14px}
    .vd-facts{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
    .vd-fact{display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border-radius:10px;background:#ffffff17;color:#fff;font-size:12px}
    .vd-fact.ok{background:#c8f16922;color:#e9ffd2}
    .vd-fact.closed{background:#ffb4b422;color:#ffe0e0}
    .vd-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
    .vd-back{display:inline-flex;gap:8px;align-items:center;padding:10px 14px;border:1px solid #cfe5d5;border-radius:10px;color:#087747;text-decoration:none;font-size:13px;font-weight:750;background:#f3fbf5}
    .vd-section-head{display:flex;justify-content:space-between;align-items:end;gap:12px;margin:26px 0 15px}
    .vd-section-head h2{margin:0;color:#164b31;font-size:22px;font-weight:850}
    .vd-section-head p{margin:5px 0 0;color:#748279;font-size:13px}
    .vd-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
    .vd-info{padding:16px;border:1px solid #e0eee4;border-radius:15px;background:#fff;box-shadow:0 6px 20px #164b3109}
    .vd-info i{color:#14864f;font-size:17px;margin-bottom:8px}
    .vd-info strong{display:block;color:#214c34;font-size:12px;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em}
    .vd-info span{color:#62756a;font-size:13px;line-height:1.55;word-break:break-word}
    .vd-info a{color:#087747;font-weight:750;text-decoration:none}
    .vd-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-top:14px}
    .vd-gallery button{border:0;padding:0;border-radius:13px;overflow:hidden;cursor:pointer;background:#dff3e6;aspect-ratio:4/3}
    .vd-gallery img{width:100%;height:100%;object-fit:cover;transition:transform .25s}
    .vd-gallery button:hover img{transform:scale(1.05)}
    .vd-company{margin-top:16px;padding:20px;border-radius:18px;background:#f7fbf8;border:1px solid #dcece2}
    .vd-company h3{margin:0 0 12px;font-size:17px;color:#164b31}
    .vd-company-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
    .vd-company-grid small{display:block;color:#7b8b81;font-size:11px;font-weight:700;text-transform:uppercase;margin-bottom:3px}
    .vd-company-grid span{font-size:13px;color:#2c4a38;line-height:1.5}
    .vd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px}
    .vd-card{overflow:hidden;border:1px solid #e2eee6;border-radius:18px;background:#fff;box-shadow:0 8px 24px #164b310d;display:flex;flex-direction:column}
    .vd-photo{position:relative;height:180px;background:linear-gradient(135deg,#e2f6e8,#c1e9ce)}
    .vd-photo img{width:100%;height:100%;object-fit:cover;display:block}
    .vd-photo-empty{height:100%;display:grid;place-items:center;color:#168b50;font-size:40px}
    .vd-photo-badge{position:absolute;top:10px;left:10px;padding:6px 10px;border-radius:30px;background:#ffffffed;color:#0b7040;font-size:11px;font-weight:800}
    .vd-photo-badge.warn{background:#fff3d6;color:#94600b}
    .vd-card-body{padding:17px;display:flex;flex-direction:column;gap:8px;flex:1}
    .vd-chip{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:30px;background:#e3f7e9;color:#087747;font-size:11px;font-weight:800}
    .vd-chip.repair{background:#fff3dc;color:#94600b}
    .vd-chip.full{background:#eef0f2;color:#626d75}
    .vd-card-body h3{margin:4px 0 0;color:#19452f;font-size:18px;font-weight:800}
    .vd-card-meta{display:flex;gap:8px;align-items:flex-start;color:#718078;font-size:12px;line-height:1.5}
    .vd-card-meta i{width:15px;color:#168b50;margin-top:2px}
    .vd-card-row{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
    .vd-price{font-size:17px;font-weight:850;color:#087747;margin-top:auto}
    .vd-price small{font-size:11px;font-weight:600;color:#78877e}
    .vd-note{padding:11px 12px;border-radius:10px;background:#fff8e9;border:1px solid #f4e0b8;color:#855b16;font-size:12px;line-height:1.5}
    .vd-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;border-radius:10px;background:#12834e;color:#fff!important;text-decoration:none;font-size:13px;font-weight:800}
    .vd-btn:hover{background:#075b38}
    .vd-btn.disabled{background:#d7e3dc;color:#708077!important;cursor:not-allowed}
    .vd-empty{padding:30px;text-align:center;border:1px dashed #b9d9c2;background:#f3fbf5;border-radius:15px;color:#748279;grid-column:1/-1}
    @media(max-width:820px){.vd-hero{grid-template-columns:1fr}.vd-cover-wrap{min-height:240px}.vd-hero-body{padding:22px}.vd-section-head{align-items:flex-start;flex-direction:column}}
</style>

@php
    $venueTitle = $venue->name ?: 'Venue ArenaSports';
    $availableCount = $courts->filter(fn ($court) => $court->is_bookable)->count();
    $gallery = $venue->photo_gallery_urls;
    $isOperating = $venue->is_operating;
@endphp

<div class="vd-page">
    <section class="vd-hero">
        <div class="vd-cover-wrap" data-venue-slider>
            @if(count($gallery))
                <div class="vd-cover-track">
                    @foreach($gallery as $image)
                        <div class="vd-cover-slide"><img src="{{ $image }}" alt="Foto {{ $venueTitle }} {{ $loop->iteration }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}"></div>
                    @endforeach
                </div>
                @if(count($gallery) > 1)
                    <button type="button" class="vd-cover-arrow prev" data-venue-prev aria-label="Foto sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="vd-cover-arrow next" data-venue-next aria-label="Foto berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
                    <div class="vd-cover-dots">
                        @foreach($gallery as $image)
                            <button type="button" data-venue-dot="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" aria-label="Foto {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="vd-cover-empty"><i class="fa-solid fa-building"></i></div>
            @endif
        </div>

        <div class="vd-hero-body">
            <span class="vd-eyebrow"><i class="fa-solid fa-location-dot"></i> {{ __('Profil Venue') }}</span>
            <h1>{{ $venueTitle }}</h1>

            @if($venue->managed_by)
                <p class="vd-managed"><i class="fa-solid fa-user-tie"></i> {{ __('Dikelola oleh') }} <strong>{{ $venue->managed_by }}</strong></p>
            @endif

            <p class="vd-desc">{{ $venue->description ?: 'Temukan lapangan olahraga yang tersedia di venue ini.' }}</p>

            <div class="vd-facts">
                <span class="vd-fact"><i class="fa-solid fa-futbol"></i> {{ implode(', ', $venue->sports ?? []) ?: 'Olahraga belum ditentukan' }}</span>
                <span class="vd-fact"><i class="fa-solid fa-layer-group"></i> {{ $courts->count() }} lapangan</span>
                <span class="vd-fact"><i class="fa-solid fa-circle-check"></i> {{ $availableCount }} siap dipesan</span>
                <span class="vd-fact {{ $isOperating ? 'ok' : 'closed' }}"><i class="fa-solid fa-store"></i> {{ $venue->operational_status ?: 'Beroperasi' }}</span>
                <span class="vd-fact"><i class="fa-regular fa-clock"></i> {{ $venue->operating_hours_text }}</span>
            </div>

            <div class="vd-actions">
                <a class="vd-back" href="{{ route('user.venues') }}"><i class="fa-solid fa-arrow-left"></i> {{ __('Kembali ke daftar venue') }}</a>
                @if($venue->google_maps_url)
                    <a class="vd-back" href="{{ $venue->google_maps_url }}" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-map-location-dot"></i> {{ __('Buka peta') }}</a>
                @endif
            </div>
        </div>
    </section>

    <div class="vd-section-head">
        <div>
            <h2>{{ __('Informasi Venue') }}</h2>
            <p>{{ __('Lokasi, jam operasional, kontak, dan fasilitas venue.') }}</p>
        </div>
    </div>

    <div class="vd-info-grid">
        <div class="vd-info"><i class="fa-solid fa-location-dot"></i><strong>{{ __('Alamat venue') }}</strong><span>{{ $venue->address ?: __('Belum diisi') }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-phone"></i><strong>{{ __('Kontak venue') }}</strong><span>{{ $venue->contact_phone ?: __('Belum diisi') }}</span></div>
        <div class="vd-info"><i class="fa-regular fa-clock"></i><strong>{{ __('Jam operasional') }}</strong><span>{{ $venue->operating_hours_text }}</span></div>
        <div class="vd-info"><i class="fa-regular fa-calendar-days"></i><strong>{{ __('Hari operasional') }}</strong><span>{{ $venue->operating_days_text }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-store"></i><strong>{{ __('Status operasional') }}</strong><span>{{ $venue->operational_status ?: 'Beroperasi' }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-city"></i><strong>{{ __('Kota / Provinsi') }}</strong><span>{{ collect([$venue->city, $venue->province])->filter()->implode(', ') ?: __('Belum diisi') }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-list-check"></i><strong>{{ __('Fasilitas venue') }}</strong><span>{{ implode(', ', $venue->facilities ?? []) ?: __('Belum diinformasikan') }}</span></div>
        <div class="vd-info"><i class="fa-solid fa-map"></i><strong>{{ __('Lokasi peta') }}</strong><span>@if($venue->google_maps_url)<a href="{{ $venue->google_maps_url }}" target="_blank" rel="noopener noreferrer">{{ __('Buka Google Maps') }}</a>@elseif($venue->latitude && $venue->longitude){{ $venue->latitude }}, {{ $venue->longitude }}@else {{ __('Belum tersedia') }} @endif</span></div>
    </div>

    @if($venue->company_information_available)
        <div class="vd-company">
            <h3><i class="fa-solid fa-building"></i> {{ __('Informasi Perusahaan') }}</h3>
            <div class="vd-company-grid">
                @if($venue->company_name)<div><small>{{ __('Nama perusahaan') }}</small><span>{{ $venue->company_name }}</span></div>@endif
                @if($venue->business_type)<div><small>{{ __('Jenis usaha') }}</small><span>{{ $venue->business_type }}</span></div>@endif
                @if($venue->nib)<div><small>{{ __('NIB') }}</small><span>{{ $venue->nib }}</span></div>@endif
                @if($venue->company_phone)<div><small>{{ __('Telepon perusahaan') }}</small><span>{{ $venue->company_phone }}</span></div>@endif
                @if($venue->company_address)<div><small>{{ __('Alamat perusahaan') }}</small><span>{{ $venue->company_address }}</span></div>@endif
            </div>
            @if($venue->company_description)
                <p style="margin:12px 0 0;color:#556b5e;font-size:13px;line-height:1.7">{{ $venue->company_description }}</p>
            @endif
        </div>
    @endif

    @if(count($gallery) > 1)
        <div class="vd-section-head">
            <div>
                <h2>{{ __('Galeri Venue') }}</h2>
                <p>{{ __('Geser foto atau klik untuk melihat lebih dekat.') }}</p>
            </div>
        </div>
        <div class="vd-gallery">
            @foreach($gallery as $index => $image)
                <button type="button" data-gallery-open="{{ $index }}"><img src="{{ $image }}" alt="Galeri {{ $venueTitle }} {{ $loop->iteration }}" loading="lazy"></button>
            @endforeach
        </div>
    @endif

    <div class="vd-section-head">
        <div>
            <h2>{{ __('Daftar Lapangan') }}</h2>
            <p>{{ __('Pilih lapangan yang ingin kamu gunakan. Status operasional dan ketersediaan jadwal ditampilkan terpisah.') }}</p>
        </div>
    </div>

    <div class="vd-grid">
        @forelse($courts as $court)
            @php
                $courtPhotos = $court->gallery_urls;
                $isMaintenance = $court->is_under_maintenance;
                $canBook = $court->is_bookable && $isOperating;
                $rating = $court->reviews_avg_rating;
            @endphp
            <article class="vd-card">
                <div class="vd-photo">
                    @if(count($courtPhotos))
                        <img src="{{ $courtPhotos[0] }}" alt="{{ $court->nama }}" loading="lazy">
                    @else
                        <div class="vd-photo-empty"><i class="fa-solid fa-futbol"></i></div>
                    @endif
                    <span class="vd-photo-badge {{ $canBook ? '' : 'warn' }}">{{ $court->operational_status ?: 'Beroperasi' }}</span>
                </div>
                <div class="vd-card-body">
                    <div class="vd-card-row">
                        <span class="vd-chip">{{ $court->jenis }}</span>
                        @if($court->area_type)<span class="vd-chip">{{ $court->area_type }}</span>@endif
                        @if($court->floor_type)<span class="vd-chip">{{ $court->floor_type }}</span>@endif
                    </div>
                    <h3>{{ $court->nama }}</h3>
                    <p class="vd-card-meta"><i class="fa-solid fa-location-dot"></i><span>{{ $court->display_location }}</span></p>
                    <div class="vd-card-row" style="color:#718078;font-size:12px">
                        <span><i class="fa-solid fa-star" style="color:#efa927"></i> {{ $rating ? number_format((float) $rating, 1) : 'Belum ada rating' }}</span>
                        <span>· {{ (int) $court->bookings_count }} booking</span>
                    </div>
                    @if($court->court_facilities_list)
                        <p class="vd-card-meta"><i class="fa-solid fa-list-check"></i><span>{{ implode(', ', array_slice($court->court_facilities_list, 0, 4)) }}</span></p>
                    @endif
                    @if($isMaintenance)
                        <div class="vd-note"><strong><i class="fa-solid fa-triangle-exclamation"></i> {{ $court->operational_status }}</strong><br>{{ $court->maintenance_note ?: 'Lapangan sedang dalam perawatan/perbaikan.' }}</div>
                    @elseif(!$canBook)
                        <div class="vd-note">Lapangan ini sementara belum dapat dipesan.</div>
                    @endif
                    <div class="vd-price">Rp {{ number_format($court->min_price, 0, ',', '.') }} <small>/ jam</small></div>
                    @if($canBook)
                        <a class="vd-btn" href="{{ route('courts.detail', $court) }}">{{ __('Lihat Detail & Booking') }} <i class="fa-solid fa-arrow-right"></i></a>
                    @else
                        <span class="vd-btn disabled"><i class="fa-solid fa-ban"></i> {{ __('Belum dapat dipesan') }}</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="vd-empty"><i class="fa-solid fa-futbol" style="font-size:30px;color:#168b50"></i><p>{{ __('Venue ini belum memiliki lapangan.') }}</p></div>
        @endforelse
    </div>
</div>

<div id="vd-lightbox" style="display:none;position:fixed;inset:0;z-index:9999;background:#000000e6;align-items:center;justify-content:center;padding:20px">
    <button type="button" id="vd-lightbox-close" style="position:absolute;top:18px;right:20px;border:0;background:#ffffff22;color:#fff;width:42px;height:42px;border-radius:50%;font-size:20px;cursor:pointer">&times;</button>
    <img id="vd-lightbox-img" src="" alt="Foto venue" style="max-width:min(1100px,95vw);max-height:88vh;border-radius:14px;object-fit:contain">
</div>

<script>
(() => {
    const slider = document.querySelector('[data-venue-slider]');
    if (slider) {
        const track = slider.querySelector('.vd-cover-track');
        const slides = slider.querySelectorAll('.vd-cover-slide');
        const dots = slider.querySelectorAll('[data-venue-dot]');
        let index = 0;

        const show = (next) => {
            if (!track || slides.length < 2) return;
            index = (next + slides.length) % slides.length;
            track.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
        };

        slider.querySelector('[data-venue-prev]')?.addEventListener('click', () => show(index - 1));
        slider.querySelector('[data-venue-next]')?.addEventListener('click', () => show(index + 1));
        dots.forEach(dot => dot.addEventListener('click', () => show(Number(dot.dataset.venueDot))));

        let startX = null;
        slider.addEventListener('touchstart', e => startX = e.touches[0].clientX, {passive: true});
        slider.addEventListener('touchend', e => {
            if (startX === null) return;
            const delta = e.changedTouches[0].clientX - startX;
            if (Math.abs(delta) > 40) show(index + (delta < 0 ? 1 : -1));
            startX = null;
        }, {passive: true});
    }

    const images = @json($gallery);
    const lightbox = document.getElementById('vd-lightbox');
    const lightboxImg = document.getElementById('vd-lightbox-img');

    const openLightbox = (src) => {
        if (!lightbox || !lightboxImg) return;
        lightboxImg.src = src;
        lightbox.style.display = 'flex';
    };

    document.querySelectorAll('[data-gallery-open]').forEach(button => {
        button.addEventListener('click', () => openLightbox(images[Number(button.dataset.galleryOpen)]));
    });

    document.getElementById('vd-lightbox-close')?.addEventListener('click', () => lightbox.style.display = 'none');
    lightbox?.addEventListener('click', e => { if (e.target === lightbox) lightbox.style.display = 'none'; });
})();
</script>
@endsection
