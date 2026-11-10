@extends('layouts.user')

@section('content')

<style>
    .venue-page {
        color: #153b2c;
        padding-bottom: 45px;
    }

    .venue-page * {
        box-sizing: border-box;
    }

    /* HERO */
    .venue-hero {
        position: relative;
        overflow: hidden;
        min-height: 230px;
        padding: 42px 45px;
        margin-bottom: 30px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        background:
            linear-gradient(90deg, rgba(0,65,42,.97), rgba(0,105,65,.85), rgba(0,90,55,.25)),
            url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?auto=format&fit=crop&w=1600&q=85')
            center 55% / cover;
        box-shadow: 0 12px 30px rgba(0,90,55,.14);
    }

    .venue-hero::before {
        content: '';
        position: absolute;
        width: 7px;
        height: 75%;
        left: 22px;
        top: 12%;
        border-radius: 10px;
        background: #a3ed69;
    }

    .venue-hero-content {
        position: relative;
        z-index: 2;
        max-width: 720px;
        color: white;
    }

    .venue-hero-label {
        display: inline-block;
        padding: 8px 16px;
        margin-bottom: 13px;
        background: rgba(135,230,155,.23);
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        color: #d7ffd9;
    }

    .venue-hero h1 {
        margin: 0 0 10px;
        color: white;
        font-size: clamp(27px, 4vw, 43px);
        font-weight: 800;
        letter-spacing: -.8px;
    }

    .venue-hero h1 span {
        color: #b5f56e;
    }

    .venue-hero p {
        margin: 0;
        color: rgba(255,255,255,.9);
        font-size: 15px;
        line-height: 1.7;
    }

    /* SECTION */
    .venue-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 20px;
    }

    .venue-section-heading h2 {
        margin: 0 0 5px;
        color: #123d2c;
        font-size: 23px;
        font-weight: 800;
    }

    .venue-section-heading p {
        margin: 0;
        color: #71847a;
        font-size: 14px;
    }

    .venue-count {
        padding: 10px 16px;
        background: #e2f6e9;
        color: #087a49;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    /* GRID */
    .venue-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 25px;
    }

    .venue-card {
        overflow: hidden;
        background: white;
        border: 1px solid #e5f0e9;
        border-radius: 22px;
        box-shadow: 0 7px 22px rgba(19,80,49,.07);
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .venue-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(19,80,49,.13);
    }

    /* PHOTO */
    .venue-photo {
        position: relative;
        height: 225px;
        overflow: hidden;
        background: linear-gradient(135deg,#d9f3e1,#a8dfbd);
    }

    .venue-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .4s ease;
    }

    .venue-card:hover .venue-photo img {
        transform: scale(1.05);
    }

    .venue-photo-placeholder {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #168653;
        font-size: 52px;
    }

    .venue-photo-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg,rgba(0,40,25,.38),transparent 55%);
        pointer-events: none;
    }

    .venue-photo-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        padding: 9px 14px;
        border-radius: 30px;
        background: #e7fff0;
        color: #087747;
        font-size: 12px;
        font-weight: 800;
        box-shadow: 0 3px 12px rgba(0,0,0,.1);
    }

    /* BODY */
    .venue-card-body {
        position: relative;
        overflow: hidden;
        padding: 22px;
        background: linear-gradient(145deg,#fff 65%,#effbf2);
    }

    .venue-card-body::after {
        content: '\f1e3';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 20px;
        bottom: 0;
        font-size: 75px;
        color: rgba(23,151,87,.07);
        pointer-events: none;
    }

    .venue-card-body > * {
        position: relative;
        z-index: 1;
    }

    .venue-name {
        margin: 0 0 13px;
        color: #153c2c;
        font-size: 21px;
        font-weight: 800;
    }

    .venue-info {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 11px;
        color: #667b70;
        font-size: 14px;
        line-height: 1.5;
    }

    .venue-info i {
        width: 17px;
        margin-top: 3px;
        color: #148653;
        text-align: center;
    }

    .venue-sports {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-bottom: 16px;
    }

    .venue-sport-tag {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 30px;
        background: #e4f7eb;
        color: #087847;
        font-size: 12px;
        font-weight: 700;
    }

    .venue-court-count {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 17px 0;
        padding: 12px 14px;
        border-radius: 12px;
        background: #eff9f2;
        color: #17633e;
        font-size: 14px;
        font-weight: 700;
    }

    .venue-court-count i {
        color: #148653;
        font-size: 17px;
    }

    .venue-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 20px;
        border: 1px solid #13864f;
        border-radius: 12px;
        background: #13864f;
        color: white !important;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none !important;
        transition: all .2s ease;
    }

    .venue-button:hover {
        background: #075d38;
        border-color: #075d38;
        transform: translateX(3px);
    }

    /* EMPTY STATE */
    .venue-empty {
        grid-column: 1 / -1;
        padding: 55px 20px;
        text-align: center;
        background: linear-gradient(135deg,#f0fbf3,#e0f5e7);
        border: 1px dashed #a9dabc;
        border-radius: 20px;
    }

    .venue-empty i {
        margin-bottom: 15px;
        color: #15905a;
        font-size: 48px;
    }

    .venue-empty h3 {
        margin: 0 0 8px;
        color: #145a39;
        font-size: 19px;
    }

    .venue-empty p {
        margin: 0;
        color: #71847a;
        font-size: 14px;
    }

    /* RESPONSIVE */
    @media(max-width: 850px) {
        .venue-grid {
            grid-template-columns: 1fr;
        }

        .venue-hero {
            padding: 35px 30px;
        }
    }

    @media(max-width: 550px) {
        .venue-hero {
            min-height: 200px;
            padding: 28px 24px;
            border-radius: 17px;
        }

        .venue-hero::before {
            left: 10px;
        }

        .venue-section-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .venue-photo {
            height: 190px;
        }

        .venue-card-body {
            padding: 18px;
        }

        .venue-name {
            font-size: 19px;
        }
    }

/* =========================================
   FIX FINAL: LAYOUT KARTU VENUE
   Memisahkan foto dan informasi venue
========================================= */

.venue-page .venue-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 25px !important;
    align-items: stretch !important;
}

.venue-page .venue-card {
    display: flex !important;
    flex-direction: column !important;
    position: relative !important;
    overflow: hidden !important;
    height: auto !important;
    min-width: 0 !important;
    background: #ffffff !important;
    border: 1px solid #dceee2 !important;
    border-radius: 22px !important;
    isolation: isolate;
}

/* FOTO SELALU DI ATAS */
.venue-page .venue-photo {
    display: block !important;
    position: relative !important;
    flex: 0 0 250px !important;
    width: 100% !important;
    height: 250px !important;
    min-height: 250px !important;
    max-height: 250px !important;
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #d9f3e1, #a8dfbd);
}

.venue-page .venue-photo img {
    display: block !important;
    position: relative !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center center !important;
    margin: 0 !important;
}

/* INFORMASI SELALU DI BAGIAN PUTIH */
.venue-page .venue-card-body {
    display: block !important;
    position: relative !important;
    inset: auto !important;
    flex: 1 1 auto !important;
    width: 100% !important;
    height: auto !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 23px !important;
    transform: none !important;
    overflow: hidden !important;
    background: linear-gradient(145deg, #ffffff 65%, #effbf2) !important;
    border: 0 !important;
    border-radius: 0 !important;
    z-index: 2 !important;
}

.venue-page .venue-card-body > * {
    display: block;
    position: relative !important;
    inset: auto !important;
    transform: none !important;
    z-index: 2 !important;
}

.venue-page .venue-card-body .venue-sports {
    display: flex !important;
}

.venue-page .venue-card-body .venue-info {
    display: flex !important;
}

.venue-page .venue-card-body .venue-court-count {
    display: flex !important;
}

.venue-page .venue-card-body .venue-button {
    display: inline-flex !important;
    width: 100% !important;
    min-height: 48px !important;
    margin-top: 5px !important;
}

.venue-page .venue-card-body::after {
    z-index: 0 !important;
    pointer-events: none !important;
}

/* ORNAMEN HIJAU HALUS */
.venue-page {
    position: relative;
    isolation: isolate;
}

.venue-page::before {
    content: "";
    position: absolute;
    top: 280px;
    right: -35px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(37, 171, 99, .10),
        rgba(37, 171, 99, 0)
    );
    pointer-events: none;
    z-index: -1;
}

@media (max-width: 850px) {
    .venue-page .venue-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 550px) {
    .venue-page .venue-photo {
        flex-basis: 210px !important;
        height: 210px !important;
        min-height: 210px !important;
        max-height: 210px !important;
    }

    .venue-page .venue-card-body {
        padding: 18px !important;
    }
}

</style>

<div class="venue-page">

    {{-- HERO BANNER --}}
    <section class="venue-hero">
        <div class="venue-hero-content">
            <span class="venue-hero-label">
                <i class="fa-solid fa-store"></i>
                ArenaSports Marketplace
            </span>

            <h1>Temukan <span>Venue</span></h1>

            <p>
                Temukan tempat olahraga favoritmu, pilih venue terbaik,
                dan lihat berbagai lapangan yang tersedia di dalamnya.
            </p>
        </div>
    </section>

    {{-- JUDUL SECTION --}}
    <div class="venue-section-heading">
        <div>
            <h2>
                <i class="fa-solid fa-location-dot" style="color:#168653"></i>
                Venue Pilihan
            </h2>
            <p>
                Pilih toko olahraga terlebih dahulu, lalu lihat lapangan
                yang tersedia di dalamnya.
            </p>
        </div>

        <span class="venue-count">
            <i class="fa-solid fa-store"></i>
            {{ $venues->count() }} Venue tersedia
        </span>
    </div>

    {{-- DAFTAR VENUE --}}
    <div class="venue-grid">

        @forelse($venues as $venue)

            @php
                $sports = $venue->sports ?? [];
                if (!is_array($sports)) {
                    $sports = [];
                }
            @endphp

            <article class="venue-card">

                {{-- FOTO VENUE --}}
                <div class="venue-photo">

                    @if($venue->photo)
                        <img
                            src="{{ asset($venue->photo) }}"
                            alt="Foto {{ $venue->name }}"
                            loading="lazy"
                        >
                    @else
                        <div class="venue-photo-placeholder">
                            <i class="fa-solid fa-store"></i>
                        </div>
                    @endif

                    <div class="venue-photo-overlay"></div>

                    <span class="venue-photo-badge">
                        <i class="fa-solid fa-check-circle"></i>
                        Venue ArenaSports
                    </span>

                </div>

                {{-- INFORMASI VENUE --}}
                <div class="venue-card-body">

                    <h3 class="venue-name">
                        {{ $venue->company_name ?: $venue->name }}
                    </h3>
                    @if($venue->company_name && $venue->name !== $venue->company_name)
                        <p style="margin:-7px 0 12px;color:#71847a;font-size:13px;">
                            <i class="fa-solid fa-location-crosshairs"></i>
                            {{ $venue->name }}
                        </p>
                    @endif

                    <div class="venue-info">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>
                            {{ $venue->address ?: 'Alamat belum tersedia' }}
                        </span>
                    </div>

                    {{-- JENIS OLAHRAGA --}}
                    @if(count($sports))
                        <div class="venue-sports">
                            @foreach($sports as $sport)
                                <span class="venue-sport-tag">
                                    <i class="fa-solid fa-medal"></i>
                                    {{ $sport }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="venue-info">
                            <i class="fa-solid fa-info-circle"></i>
                            <span>Informasi olahraga belum tersedia.</span>
                        </div>
                    @endif

                    {{-- JUMLAH LAPANGAN --}}
                    <div class="venue-court-count">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>
                            {{ $venue->lapangans_count ?? 0 }}
                            Lapangan di venue ini
                        </span>
                    </div>

                    {{-- TOMBOL DETAIL --}}
                    <a
                        class="venue-button"
                        href="{{ route('user.venues.show', $venue) }}"
                    >
                        Lihat Venue
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                </div>
            </article>

        @empty

            <div class="venue-empty">
                <i class="fa-solid fa-store-slash"></i>
                <h3>Belum Ada Venue Aktif</h3>
                <p>
                    Saat ini belum ada venue yang tersedia.
                    Silakan kunjungi kembali nanti.
                </p>
            </div>

        @endforelse

    </div>

</div>

@endsection