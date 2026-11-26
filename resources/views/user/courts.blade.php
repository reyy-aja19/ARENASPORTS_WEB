@extends('layouts.user')

@section('content')

<div class="u-section-head">
    <div>
        <h2>{{ __('Cari lapangan') }}</h2>
        <p>{{ __('Temukan lapangan futsal atau badminton yang cocok untukmu. Filter harga, tanggal, dan jam sesuai kebutuhan.') }}</p>
    </div>
</div>

<form class="u-search" method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
    <div style="flex:1 1 220px;min-width:180px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Cari</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Nama lapangan, venue, atau lokasi...') }}">
    </div>

    <div style="flex:0 0 150px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Olahraga</label>
        <select name="jenis">
            <option value="">{{ __('Semua') }}</option>
            <option value="Futsal" @selected(request('jenis') === 'Futsal')>Futsal</option>
            <option value="Badminton" @selected(request('jenis') === 'Badminton')>Badminton</option>
        </select>
    </div>

    <div style="flex:0 0 140px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Harga min</label>
        <input type="number" name="min_price" min="0" value="{{ request('min_price') }}" placeholder="0">
    </div>

    <div style="flex:0 0 140px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Harga max</label>
        <input type="number" name="max_price" min="0" value="{{ request('max_price') }}" placeholder="500000">
    </div>

    <div style="flex:0 0 160px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Tanggal</label>
        <input type="date" name="date" min="{{ now()->toDateString() }}" value="{{ request('date') }}">
    </div>

    <div style="flex:0 0 120px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Jam</label>
        <input type="time" name="start_time" value="{{ request('start_time') }}">
    </div>

    <div style="flex:0 0 100px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Durasi</label>
        <select name="duration">
            @for($h = 1; $h <= 6; $h++)
                <option value="{{ $h }}" @selected((int) request('duration', 1) === $h)>{{ $h }} jam</option>
            @endfor
        </select>
    </div>

    <div style="flex:0 0 160px">
        <label style="display:block;font-size:12px;font-weight:700;color:#4a6055;margin-bottom:5px">Urutkan</label>
        <select name="sort">
            <option value="newest" @selected($sort === 'newest')>Terbaru</option>
            <option value="nearest" @selected($sort === 'nearest')>Terdekat</option>
            <option value="price_asc" @selected($sort === 'price_asc')>Termurah</option>
            <option value="price_desc" @selected($sort === 'price_desc')>Termahal</option>
            <option value="rating" @selected($sort === 'rating')>Rating tertinggi</option>
            <option value="popular" @selected($sort === 'popular')>Terpopuler</option>
        </select>
    </div>

    <button class="u-btn" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
        {{ __('Cari') }}
    </button>

    <a href="{{ route('user.courts') }}" class="u-btn" style="background:#eef4ef;color:#2f5740;text-decoration:none">
        Reset
    </a>
</form>

@if($date || $startTime || request('min_price') || request('max_price'))
    <div class="u-panel" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
        <div style="font-size:13px;color:#4a6055">
            <i class="fa-solid fa-filter" style="color:#12834e"></i>
            Menampilkan {{ $courts->count() }} lapangan
            @if($date) · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }} @endif
            @if($startTime) · mulai {{ $startTime }} ({{ $duration }} jam) @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="u-btn" id="use-location-btn" style="background:#eef4ef;color:#2f5740;font-size:12px">
                <i class="fa-solid fa-location-crosshairs"></i> Gunakan lokasi saya
            </button>
            @if(session('user_lat') && session('user_lng'))
                <a href="{{ route('user.courts', array_merge(request()->query(), ['clear_location' => 1])) }}" class="u-btn" style="background:#fff;color:#b23b3b;font-size:12px;border:1px solid #f1c7c7;text-decoration:none">
                    Hapus lokasi
                </a>
            @endif
        </div>
    </div>
@endif

<div class="u-cards">
    @forelse($courts as $court)
        @php
            $courtPhotos = $court->gallery_urls;
            $rating = $court->reviews_avg_rating ?? $court->rating;
            $isBookable = $court->is_bookable;
            $price = $court->effective_price ?? $court->min_price;
        @endphp

        <article class="u-card">
            <div class="u-card-img u-court-gallery" data-user-slider>
                <div class="u-gallery-track">
                    @forelse($courtPhotos as $photo)
                        <img
                            class="u-gallery-slide {{ $loop->first ? 'active' : '' }}"
                            src="{{ $photo }}"
                            alt="{{ $court->nama }} foto {{ $loop->iteration }}"
                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                        >
                    @empty
                        <div class="u-gallery-empty">
                            <i class="fa-solid fa-futbol"></i>
                        </div>
                    @endforelse
                </div>

                @if(count($courtPhotos) > 1)
                    <button type="button" class="u-gallery-arrow prev" data-user-prev aria-label="Foto sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="u-gallery-arrow next" data-user-next aria-label="Foto berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
                    <span class="u-gallery-count"><span data-user-current>1</span> / {{ count($courtPhotos) }}</span>
                @endif

                <span class="u-tag" style="position:absolute;top:10px;left:10px;background:#ffffffee;color:{{ $isBookable ? '#087747' : '#94600b' }};font-weight:800;font-size:11px;z-index:2">
                    {{ $court->operational_status ?: 'Beroperasi' }}
                </span>
            </div>

            <div class="u-card-body">
                <div class="u-row">
                    <span class="u-tag">{{ $court->jenis }}</span>
                    @if($court->area_type)
                        <span class="u-tag" style="background:#f2f8f4;color:#29523a">{{ $court->area_type }}</span>
                    @endif
                    @if($court->floor_type)
                        <span class="u-tag" style="background:#f2f8f4;color:#29523a">{{ $court->floor_type }}</span>
                    @endif
                    <span class="u-meta" style="margin-left:auto">
                        <i class="fa-solid fa-star" style="color:#efa927"></i>
                        {{ $rating ? number_format((float) $rating, 1) : 'Baru' }}
                    </span>
                </div>

                <h3 style="margin-top:12px">{{ $court->nama }}</h3>

                <div class="u-meta" style="margin-top:8px">
                    <i class="fa-solid fa-building"></i>
                    <span>
                        {{ __('Venue:') }}
                        @if($court->venue)
                            <a href="{{ route('user.venues.show', $court->venue) }}" style="color:#16a36b;font-weight:700;text-decoration:none;">
                                {{ $court->venue->name }}
                            </a>
                        @else
                            <span style="color:#94a3b8">{{ __('Belum terhubung dengan venue') }}</span>
                        @endif
                    </span>
                </div>

                <div class="u-meta" style="margin-top:7px">
                    <i class="fa-solid fa-location-dot"></i>
                    {{ $court->display_location ?: 'Lokasi belum diisi' }}
                    @if(isset($court->distance_km) && $court->distance_km !== null)
                        <span class="u-tag" style="margin-left:5px">{{ number_format($court->distance_km, 1) }} km</span>
                    @endif
                </div>

                @if($court->court_facilities_list)
                    <div class="u-meta" style="margin-top:7px">
                        <i class="fa-solid fa-list-check"></i>
                        {{ Str::limit(implode(', ', $court->court_facilities_list), 80) }}
                    </div>
                @endif

                <div class="u-row" style="margin-top:8px;gap:6px">
                    <span class="u-meta"><i class="fa-solid fa-calendar-check"></i> {{ (int) $court->bookings_count }} booking</span>
                </div>

                <p class="u-meta">{{ Str::limit($court->deskripsi ?? 'Lapangan olahraga tersedia untuk dipesan.', 100) }}</p>

                <div class="u-price">
                    Rp {{ number_format((int) $price, 0, ',', '.') }}
                    <span class="u-meta">{{ $date && $startTime ? '/ total ' . $duration . ' jam' : '/ jam' }}</span>
                </div>

                @if($isBookable)
                    <a class="u-btn" style="display:block;text-align:center;text-decoration:none;margin-top:14px;" href="{{ route('courts.detail', $court) }}">
                        {{ __('Lihat detail & booking') }}
                    </a>
                @else
                    <span class="u-btn" style="display:block;text-align:center;text-decoration:none;margin-top:14px;background:#e7ece9;color:#6b7b71">
                        {{ __('Belum dapat dipesan') }}
                    </span>
                @endif
            </div>
        </article>
    @empty
        <div class="u-panel u-empty">
            <i class="fa-solid fa-magnifying-glass"></i>
            <strong>{{ __('Lapangan tidak ditemukan') }}</strong>
            {{ __('Coba ubah kata kunci, harga, tanggal, jam, atau jenis olahraga.') }}
        </div>
    @endforelse
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('use-location-btn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung deteksi lokasi.');
            return;
        }

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi...';

        navigator.geolocation.getCurrentPosition(function (position) {
            const url = new URL(window.location.href);
            url.searchParams.set('lat', position.coords.latitude.toFixed(6));
            url.searchParams.set('lng', position.coords.longitude.toFixed(6));
            window.location.href = url.toString();
        }, function () {
            btn.disabled = false;
            btn.innerHTML = original;
            alert('Gagal mendeteksi lokasi. Pastikan izin lokasi diaktifkan.');
        }, { enableHighAccuracy: true, timeout: 10000 });
    });
});
</script>
@endsection
