
@extends('layouts.user')

@section('content')

{{-- HEADER HALAMAN --}}
<div class="u-section-head">
    <div>
        <h2>Cari lapangan</h2>
        <p>Temukan venue futsal atau badminton yang cocok untukmu.</p>
    </div>
</div>


{{-- LOKASI TERDEKAT --}}
<div class="u-location-bar">
    <span>
        <i class="fa-solid fa-location-crosshairs"></i>
        Rekomendasi venue terdekat berdasarkan lokasi kamu.
    </span>

    <button class="u-btn ghost" type="button" id="nearbyButton">
        <i class="fa-solid fa-location-dot"></i>
        Cari terdekat
    </button>
</div>


{{-- FORM PENCARIAN --}}
<form class="u-search" method="GET">

    @if(request()->filled('lat') && request()->filled('lng'))
        <input type="hidden" name="lat" value="{{ request('lat') }}">
        <input type="hidden" name="lng" value="{{ request('lng') }}">
    @endif

    <input
        type="text"
        name="q"
        value="{{ request('q') }}"
        placeholder="Cari nama lapangan atau lokasi..."
    >

    <select name="jenis" style="max-width:180px">
        <option value="">Semua olahraga</option>

        <option value="Futsal" @selected(request('jenis') === 'Futsal')>
            Futsal
        </option>

        <option value="Badminton" @selected(request('jenis') === 'Badminton')>
            Badminton
        </option>
    </select>

    <button class="u-btn" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
        Cari
    </button>

</form>


{{-- DAFTAR LAPANGAN --}}
<div class="u-cards">

    @forelse($courts as $court)

        @php
            // Mengambil foto utama dan foto gallery
            $gallery = $court->foto_gallery ?? [];

            // Mendukung gallery berbentuk JSON maupun array
            if (is_string($gallery)) {
                $gallery = json_decode($gallery, true) ?? [];
            }

            // Memastikan gallery berbentuk array
            if (!is_array($gallery)) {
                $gallery = [];
            }

            // Menggabungkan foto utama dan gallery
            $courtPhotos = collect(array_merge(
                [$court->foto],
                $gallery
            ))
            ->filter(function ($photo) {
                return $photo
                    && is_string($photo)
                    && file_exists(public_path($photo));
            })
            ->unique()
            ->values();
        @endphp


        <article class="u-card">

            {{-- GALERI FOTO LAPANGAN --}}
            <div class="u-card-img u-court-gallery" data-user-slider>

                <div class="u-gallery-track">

                    @forelse($courtPhotos as $photo)

                        <img
                            class="u-gallery-slide {{ $loop->first ? 'active' : '' }}"
                            src="{{ asset($photo) }}"
                            alt="{{ $court->nama }} foto {{ $loop->iteration }}"
                        >

                    @empty

                        <div class="u-gallery-empty">
                            <i class="fa-solid fa-futbol"></i>
                        </div>

                    @endforelse

                </div>


                {{-- TOMBOL SLIDER --}}
                @if($courtPhotos->count() > 1)

                    <button
                        type="button"
                        class="u-gallery-arrow prev"
                        data-user-prev
                        aria-label="Foto sebelumnya"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <button
                        type="button"
                        class="u-gallery-arrow next"
                        data-user-next
                        aria-label="Foto berikutnya"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                    <span class="u-gallery-count">
                        <span data-user-current>1</span>
                        / {{ $courtPhotos->count() }}
                    </span>

                @endif

            </div>


            {{-- INFORMASI LAPANGAN --}}
            <div class="u-card-body">

                {{-- JENIS OLAHRAGA DAN RATING --}}
                <div class="u-row">

                    <span class="u-tag">
                        {{ $court->jenis }}
                    </span>

                    <span class="u-meta">
                        <i class="fa-solid fa-star" style="color:#efa927"></i>
                        {{ number_format($court->rating, 1) }}
                    </span>

                </div>


                {{-- NAMA LAPANGAN --}}
                <h3 style="margin-top:12px">
                    {{ $court->nama }}
                </h3>


                {{-- VENUE ASAL LAPANGAN --}}
                <div class="u-meta" style="margin-top:8px">

                    <i class="fa-solid fa-building"></i>

                    <span>
                        Venue:

                        @if($court->venue)

                            <a
                                href="{{ route('user.venues.show', $court->venue) }}"
                                style="
                                    color:#16a36b;
                                    font-weight:700;
                                    text-decoration:none;
                                "
                            >
                                {{ $court->venue->name }}
                            </a>

                        @else

                            <span style="color:#94a3b8">
                                Belum terhubung dengan venue
                            </span>

                        @endif

                    </span>

                </div>


                {{-- LOKASI LAPANGAN --}}
                <div class="u-meta" style="margin-top:7px">

                    <i class="fa-solid fa-location-dot"></i>

                    {{ $court->lokasi }}

                    @if(isset($court->distance_km) && $court->distance_km !== null)

                        <span class="u-tag" style="margin-left:5px">
                            {{ number_format($court->distance_km, 1) }} km
                        </span>

                    @endif

                </div>


                {{-- DESKRIPSI --}}
                <p class="u-meta">
                    {{ Str::limit(
                        $court->deskripsi ?? 'Lapangan olahraga tersedia untuk dipesan.',
                        100
                    ) }}
                </p>


                {{-- HARGA --}}
                <div class="u-price">

                    Rp {{ number_format($court->harga, 0, ',', '.') }}

                    <span class="u-meta">
                        / jam
                    </span>

                </div>


                {{-- DETAIL DAN BOOKING --}}
                <a
                    class="u-btn"
                    style="
                        display:block;
                        text-align:center;
                        text-decoration:none;
                        margin-top:14px;
                    "
                    href="{{ route('courts.detail', $court) }}"
                >
                    Lihat detail &amp; booking
                </a>

            </div>

        </article>

    @empty

        {{-- JIKA LAPANGAN TIDAK DITEMUKAN --}}
        <div class="u-panel u-empty">

            <i class="fa-solid fa-magnifying-glass"></i>

            <strong>Lapangan tidak ditemukan</strong>

            Coba kata kunci atau jenis olahraga lain.

        </div>

    @endforelse

</div>


{{-- JAVASCRIPT PENCARIAN LOKASI TERDEKAT --}}
<script>
document.getElementById('nearbyButton')?.addEventListener('click', function () {

    const button = this;

    button.disabled = true;
    button.textContent = 'Mencari lokasi…';

    if (!navigator.geolocation) {

        alert('Browser tidak mendukung lokasi.');

        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-location-dot"></i> Cari terdekat';

        return;
    }

    navigator.geolocation.getCurrentPosition(

        function (pos) {

            const url = new URL(window.location.href);

            url.searchParams.set('lat', pos.coords.latitude);
            url.searchParams.set('lng', pos.coords.longitude);

            window.location.href = url.toString();

        },

        function () {

            alert('Izin lokasi tidak tersedia. Izinkan akses lokasi lalu coba lagi.');

            button.disabled = false;

            button.innerHTML =
                '<i class="fa-solid fa-location-dot"></i> Cari terdekat';

        },

        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }

    );

});
</script>

@endsection
