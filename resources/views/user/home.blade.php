@extends('layouts.user')

@section('content')

<style>
    /* =========================================
       ARENASPORTS USER HOME
    ========================================= */

    .uh-page {
        position: relative;
        isolation: isolate;
        color: #183d2c;
        padding: 8px 0 45px;
    }

    .uh-page *,
    .uh-page *::before,
    .uh-page *::after {
        box-sizing: border-box;
    }

    /* ORNAMEN HIJAU */
    .uh-page::before {
        content: "";
        position: absolute;
        top: 250px;
        left: -90px;
        width: 230px;
        height: 230px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(35,166,95,.10), transparent 70%);
        z-index: -1;
        pointer-events: none;
    }

    .uh-page::after {
        content: "";
        position: absolute;
        top: 650px;
        right: -90px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(35,166,95,.09), transparent 70%);
        z-index: -1;
        pointer-events: none;
    }

    /* HERO */
    .uh-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;
        padding: 36px 40px;
        border-radius: 23px;
        background: linear-gradient(110deg, #064f32, #0b7748 65%, #35a76a);
        color: #fff;
        box-shadow: 0 12px 28px rgba(7,91,56,.12);
    }

    .uh-hero::after {
        content: "⚽";
        position: absolute;
        right: 18%;
        bottom: -55px;
        font-size: 190px;
        opacity: .08;
        transform: rotate(-18deg);
        pointer-events: none;
    }

    .uh-hero-content {
        position: relative;
        z-index: 1;
        max-width: 720px;
    }

    .uh-kicker {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 30px;
        background: rgba(255,255,255,.13);
        color: #e2ffeb;
        font-size: 12px;
        font-weight: 800;
    }

    .uh-hero h1 {
        margin: 12px 0 8px;
        color: #fff;
        font-size: clamp(28px,4vw,42px);
        font-weight: 900;
        line-height: 1.15;
    }

    .uh-hero p {
        max-width: 620px;
        margin: 0 0 20px;
        color: #e0f6e7;
        font-size: 14px;
        line-height: 1.7;
    }

    .uh-hero-cta {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 12px 18px;
        border-radius: 11px;
        background: #fff;
        color: #087344 !important;
        text-decoration: none !important;
        font-size: 13px;
        font-weight: 850;
        transition: .2s;
    }

    .uh-hero-cta:hover {
        background: #e4f8e9;
        transform: translateY(-2px);
    }

    .uh-hero-visual {
        position: relative;
        z-index: 1;
        display: grid;
        place-items: center;
        width: 112px;
        height: 112px;
        flex: 0 0 112px;
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 28px;
        background: rgba(255,255,255,.12);
        color: #e6ffed;
        font-size: 55px;
        transform: rotate(5deg);
    }

    /* STATISTIK */
    .uh-stats {
        display: grid;
        grid-template-columns: repeat(3,minmax(0,1fr));
        gap: 16px;
        margin: 22px 0 32px;
    }

    .uh-stat {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 19px;
        border: 1px solid #e1eee5;
        border-radius: 17px;
        background: linear-gradient(145deg,#fff,#f1faf4);
        box-shadow: 0 5px 16px rgba(24,75,48,.06);
    }

    .uh-stat-icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 14px;
        background: #def4e5;
        color: #0c854b;
        font-size: 19px;
    }

    .uh-stat small {
        display: block;
        color: #78867d;
        font-size: 12px;
    }

    .uh-stat strong {
        display: block;
        margin-top: 3px;
        color: #164b31;
        font-size: 23px;
        font-weight: 900;
    }

    /* SECTION */
    .uh-section {
        margin: 30px 0;
    }

    .uh-section-head {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 17px;
    }

    .uh-section-head h2 {
        margin: 0;
        color: #164b31;
        font-size: 22px;
        font-weight: 850;
    }

    .uh-section-head p {
        margin: 5px 0 0;
        color: #78867d;
        font-size: 13px;
        line-height: 1.5;
    }

    .uh-link {
        color: #087747 !important;
        text-decoration: none !important;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .uh-link:hover {
        color: #064f32 !important;
    }

    /* =========================================
       VENUE CARD - FIX FOTO DAN INFORMASI
    ========================================= */

    .uh-page .uh-cards {
        display: grid !important;
        grid-template-columns: repeat(2,minmax(0,1fr)) !important;
        align-items: stretch !important;
        gap: 22px !important;
    }

    .uh-page .uh-card {
        display: flex !important;
        flex-direction: column !important;
        position: relative !important;
        min-width: 0 !important;
        height: auto !important;
        overflow: hidden !important;
        border: 1px solid #dceee2 !important;
        border-radius: 21px !important;
        background: #fff !important;
        box-shadow: 0 7px 22px rgba(19,80,49,.07) !important;
        transition: transform .25s ease, box-shadow .25s ease;
        isolation: isolate;
    }

    .uh-page .uh-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(19,80,49,.13) !important;
    }

    /* FOTO VENUE */
    .uh-page .uh-card-img {
        display: block !important;
        position: relative !important;
        flex: 0 0 245px !important;
        width: 100% !important;
        height: 245px !important;
        min-height: 245px !important;
        max-height: 245px !important;
        overflow: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
        background: linear-gradient(135deg,#d9f3e1,#a8dfbd) !important;
        color: #168b50;
    }

    .uh-page .uh-card-img img {
        display: block !important;
        position: relative !important;
        inset: auto !important;
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        max-height: none !important;
        object-fit: cover !important;
        object-position: center center !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        transform: none;
        transition: transform .4s ease;
    }

    .uh-page .uh-card:hover .uh-card-img img {
        transform: scale(1.04);
    }

    .uh-photo-label {
        position: absolute !important;
        top: 15px !important;
        left: 15px !important;
        z-index: 3 !important;
        display: inline-flex !important;
        align-items: center;
        gap: 6px;
        padding: 9px 13px;
        border-radius: 30px;
        background: #e7fff0;
        color: #087747;
        font-size: 11px;
        font-weight: 850;
        box-shadow: 0 3px 12px rgba(0,0,0,.12);
    }

    .uh-photo-placeholder {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #168b50;
        font-size: 55px;
    }

    /* INFORMASI DI BAWAH FOTO */
    .uh-page .uh-card-body {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        position: relative !important;
        inset: auto !important;
        flex: 1 1 auto !important;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 22px !important;
        overflow: hidden !important;
        transform: none !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: linear-gradient(145deg,#fff 65%,#effbf2) !important;
        z-index: 2 !important;
    }

    .uh-page .uh-card-body > * {
        position: relative !important;
        inset: auto !important;
        transform: none !important;
        z-index: 1 !important;
    }

    .uh-page .uh-card-body h3 {
        display: block !important;
        margin: 10px 0 12px !important;
        color: #153c2c !important;
        font-size: 20px !important;
        line-height: 1.35 !important;
        font-weight: 850 !important;
    }

    .uh-page .uh-tag {
        display: inline-flex !important;
        align-items: center;
        align-self: flex-start;
        width: fit-content;
        max-width: 100%;
        padding: 7px 12px;
        border-radius: 30px;
        background: #e2f6e8;
        color: #087747;
        font-size: 11px;
        line-height: 1.4;
        font-weight: 800;
    }

    .uh-page .uh-meta {
        display: flex !important;
        align-items: flex-start !important;
        gap: 9px !important;
        margin: 7px 0 !important;
        color: #718078 !important;
        font-size: 13px !important;
        line-height: 1.55 !important;
    }

    .uh-page .uh-meta i {
        flex: 0 0 15px;
        margin-top: 3px;
        color: #168b50 !important;
        text-align: center;
    }

    .uh-court-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 13px 0;
        padding: 12px 14px;
        border-radius: 12px;
        background: #eff9f2;
        color: #17633e;
        font-size: 13px;
        font-weight: 800;
    }

    .uh-court-info i {
        color: #148653;
        font-size: 17px;
    }

    .uh-page .uh-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 9px !important;
        width: 100% !important;
        min-height: 49px !important;
        margin-top: auto !important;
        padding: 12px 16px !important;
        border: 0 !important;
        border-radius: 12px !important;
        background: #13864f !important;
        color: #fff !important;
        text-decoration: none !important;
        font-size: 13px !important;
        font-weight: 850 !important;
        transition: background .2s, transform .2s;
    }

    .uh-page .uh-btn:hover {
        background: #075d38 !important;
        transform: translateY(-2px) !important;
    }

    /* PANEL BOOKING DAN OPEN MATCH */
    .uh-columns {
        display: grid;
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .uh-panel {
        min-width: 0;
        padding: 21px;
        border: 1px solid #e1eee5;
        border-radius: 19px;
        background: linear-gradient(145deg,#fff,#f5fbf6);
        box-shadow: 0 5px 17px rgba(24,75,48,.06);
    }

    .uh-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 13px 0;
        border-bottom: 1px solid #eaf1ec;
    }

    .uh-row:last-child {
        border-bottom: 0;
    }

    .uh-row strong {
        color: #214b34;
        font-size: 13px;
    }

    .uh-empty {
        padding: 23px 17px;
        text-align: center;
        border: 1px dashed #b9d9c2;
        border-radius: 13px;
        background: #f3fbf5;
        color: #78867d;
        font-size: 13px;
    }

    .uh-joined {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex: 0 0 auto;
        padding: 7px 10px;
        border-radius: 30px;
        background: #e5e7eb;
        color: #667078;
        font-size: 10px;
        font-weight: 800;
    }

    .uh-available {
        display: inline-flex;
        align-items: center;
        flex: 0 0 auto;
        padding: 7px 10px;
        border-radius: 30px;
        background: #e2f6e8;
        color: #087747;
        font-size: 10px;
        font-weight: 800;
    }

    /* RESPONSIVE */
    @media(max-width: 850px) {
        .uh-page .uh-cards {
            grid-template-columns: 1fr !important;
        }

        .uh-stats {
            grid-template-columns: 1fr;
        }

        .uh-columns {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width: 550px) {
        .uh-hero {
            padding: 27px 22px;
            border-radius: 17px;
        }

        .uh-hero-visual {
            display: none;
        }

        .uh-section-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .uh-page .uh-card-img {
            flex-basis: 205px !important;
            height: 205px !important;
            min-height: 205px !important;
            max-height: 205px !important;
        }

        .uh-page .uh-card-body {
            padding: 18px !important;
        }

        .uh-page .uh-card-body h3 {
            font-size: 18px !important;
        }

        .uh-panel {
            padding: 16px;
        }
    }

    
</style>

<div class="uh-page">

    {{-- HERO --}}
    <section class="uh-hero">
        <div class="uh-hero-content">
            <span class="uh-kicker">
                <i class="fa-solid fa-futbol"></i>
                ARENASPORTS · FIND YOUR GAME
            </span>

            <h1>
                {{ __('Halo,') }} {{ $user->name }}!
                <br>{{ __('Siap bermain hari ini?') }}
            </h1>

            <p>
                {{ __('Temukan venue olahraga, pilih lapangan yang sesuai, booking jadwal favoritmu, dan ajak pemain lain bergabung lewat Open Match.') }}
            </p>

            <a class="uh-hero-cta" href="{{ route('user.venues') }}">
                {{ __('Jelajahi Venue') }}
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="uh-hero-visual">
            <i class="fa-solid fa-person-running"></i>
        </div>
    </section>

    {{-- BANNER STATUS PENGAJUAN VENUE JIKA ADA --}}
    @if(isset($pendingVenueApp) && $pendingVenueApp)
        <div style="background:linear-gradient(110deg, #fefce8, #fffbeb); border:1px solid #fde047; border-radius:18px; padding:18px 24px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; box-shadow:0 6px 20px rgba(234,179,8,.08);">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:44px; height:44px; border-radius:12px; background:#facc15; color:#713f12; display:grid; place-items:center; font-size:20px; flex-shrink:0;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <strong style="color:#713f12; font-size:15px; display:block;">Pengajuan Venue "{{ $pendingVenueApp->venue_name }}" Sedang Ditinjau</strong>
                    <span style="color:#854d0e; font-size:13px;">Status: <strong>{{ ucfirst($pendingVenueApp->status) }}</strong>. Tim Superadmin sedang memverifikasi data dan dokumen venuemu.</span>
                </div>
            </div>
            <a href="{{ route('user.venue-application') }}" class="u-btn" style="background:#ca8a04; color:#fff; text-decoration:none; padding:10px 18px; font-size:13px; border-radius:10px; font-weight:700;">
                {{ __('Lihat Detail Status') }}
            </a>
        </div>
    @endif

    {{-- HIGHLIGHT TIKET JADWAL MAIN TERDEKAT --}}
    @if(isset($upcomingBooking) && $upcomingBooking)
        <div style="background:linear-gradient(115deg, #0b5133, #157347); border-radius:20px; padding:22px 28px; margin-bottom:26px; color:#fff; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:18px; box-shadow:0 12px 30px rgba(11,81,51,.25);">
            <div style="display:flex; align-items:center; gap:18px;">
                <div style="width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.18); display:grid; place-items:center; font-size:24px; flex-shrink:0; border:1px solid rgba(255,255,255,.3);">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <div>
                    <span style="display:inline-block; font-size:11px; font-weight:800; background:#bbf7d0; color:#14532d; padding:4px 10px; border-radius:20px; margin-bottom:6px; letter-spacing:.5px;">{{ __('JADWAL SEWA MENDATANG') }}</span>
                    <h3 style="margin:0 0 4px; font-size:18px; color:#fff;">{{ $upcomingBooking->lapangan?->nama ?? 'Lapangan' }}</h3>
                    <p style="margin:0; font-size:13px; color:#dcfce7;">
                        <i class="fa-regular fa-calendar"></i> {{ $upcomingBooking->booking_date?->format('d M Y') }} &nbsp;·&nbsp;
                        <i class="fa-regular fa-clock"></i> {{ substr($upcomingBooking->start_time, 0, 5) }} - {{ substr($upcomingBooking->end_time, 0, 5) }} WIB &nbsp;·&nbsp;
                        <i class="fa-solid fa-location-dot"></i> {{ $upcomingBooking->lapangan?->venue?->name ?? ($upcomingBooking->lapangan?->lokasi ?? 'Venue ArenaSports') }}
                    </p>
                </div>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('user.bookings') }}" class="u-btn" style="background:#fff; color:#0b5133; text-decoration:none; padding:10px 18px; font-size:13px; border-radius:10px; font-weight:800;">
                    {{ __('Tiket Saya') }}
                </a>
            </div>
        </div>
    @endif

    {{-- QUICK ACTIONS GRID --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:30px;">
        <a href="{{ route('user.venues') }}" style="background:#fff; border:1px solid #dce8df; border-radius:16px; padding:18px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; box-shadow:0 4px 14px rgba(0,0,0,.03);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 24px rgba(22,134,70,.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(0,0,0,.03)';">
            <div style="width:44px; height:44px; border-radius:12px; background:#e8f7ed; color:#168653; display:grid; place-items:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-location-crosshairs"></i>
            </div>
            <div>
                <strong style="display:block; font-size:14px; color:#123b28;">{{ __('Venue Terdekat') }}</strong>
                <small style="color:#71847a; font-size:11px;">{{ __('Cari dengan GPS') }}</small>
            </div>
        </a>

        <a href="{{ route('user.courts') }}" style="background:#fff; border:1px solid #dce8df; border-radius:16px; padding:18px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; box-shadow:0 4px 14px rgba(0,0,0,.03);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 24px rgba(22,134,70,.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(0,0,0,.03)';">
            <div style="width:44px; height:44px; border-radius:12px; background:#e0f2fe; color:#0284c7; display:grid; place-items:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-futbol"></i>
            </div>
            <div>
                <strong style="display:block; font-size:14px; color:#123b28;">{{ __('Sewa Lapangan') }}</strong>
                <small style="color:#71847a; font-size:11px;">{{ __('Futsal, Badminton, dll') }}</small>
            </div>
        </a>

        <a href="{{ route('user.openmatches') }}" style="background:#fff; border:1px solid #dce8df; border-radius:16px; padding:18px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; box-shadow:0 4px 14px rgba(0,0,0,.03);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 24px rgba(22,134,70,.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(0,0,0,.03)';">
            <div style="width:44px; height:44px; border-radius:12px; background:#fef3c7; color:#d97706; display:grid; place-items:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <strong style="display:block; font-size:14px; color:#123b28;">{{ __('Open Match') }}</strong>
                <small style="color:#71847a; font-size:11px;">Gabung teman main</small>
            </div>
        </a>

        <a href="{{ route('user.openmatches') }}" style="background:#fff; border:1px solid #dce8df; border-radius:16px; padding:18px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; box-shadow:0 4px 14px rgba(0,0,0,.03);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 24px rgba(22,134,70,.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(0,0,0,.03)';">
            <div style="width:44px; height:44px; border-radius:12px; background:#fae8ff; color:#a855f7; display:grid; place-items:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div>
                <strong style="display:block; font-size:14px; color:#123b28;">Event Resmi</strong>
                <small style="color:#71847a; font-size:11px;">Turnamen berhadiah</small>
            </div>
        </a>
    </div>

    {{-- STATISTIK --}}
    <section class="uh-stats">

        <div class="uh-stat">
            <span class="uh-stat-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </span>
            <div>
                <small>Total booking kamu</small>
                <strong>{{ $user->bookings()->count() }}</strong>
            </div>
        </div>

        <div class="uh-stat">
            <span class="uh-stat-icon">
                <i class="fa-solid fa-coins"></i>
            </span>
            <div>
                <small>Saldo poin</small>
                <strong>
                    {{ number_format($user->points) }}
                    <small style="display:inline;color:#168b50">poin</small>
                </strong>
            </div>
        </div>

        <div class="uh-stat">
            <span class="uh-stat-icon">
                <i class="fa-solid fa-building"></i>
            </span>
            <div>
                <small>Venue aktif</small>
                <strong>{{ $venueCount }}</strong>
            </div>
        </div>

    </section>

    {{-- VENUE PILIHAN --}}
    <section class="uh-section">

        <div class="uh-section-head">
            <div>
                <h2>
                    <i class="fa-solid fa-store" style="color:#168b50"></i>
                    Venue pilihan
                </h2>
                <p>
                    Pilih venue untuk melihat
                    lapangan yang tersedia di dalamnya.
                </p>
            </div>

            <a class="uh-link" href="{{ route('user.venues') }}">
                Lihat semua
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="uh-cards">

            @forelse($venues as $venue)

                @php
                    $sports = $venue->sports ?? [];

                    if (is_string($sports)) {
                        $decodedSports = json_decode($sports, true);
                        $sports = is_array($decodedSports)
                            ? $decodedSports
                            : [$sports];
                    }

                    if (!is_array($sports)) {
                        $sports = [];
                    }

                    $sports = array_filter($sports, fn($sport) => is_string($sport) && trim($sport) !== '');

                    $venueDisplayName = $venue->name ?: ($venue->company_name ?: 'Venue ArenaSports');
                @endphp

                <article class="uh-card">

                    {{-- FOTO VENUE --}}
                    <div class="uh-card-img">

                        @if($venue->photo)
                            <img
                                src="{{ asset($venue->photo) }}"
                                alt="Foto {{ $venueDisplayName }}"
                                loading="lazy"
                            >
                        @else
                            <div class="uh-photo-placeholder">
                                <i class="fa-solid fa-building"></i>
                            </div>
                        @endif

                        <span class="uh-photo-label">
                            <i class="fa-solid fa-circle-check"></i>
                            Venue ArenaSports
                        </span>

                    </div>

                    {{-- INFORMASI VENUE --}}
                    <div class="uh-card-body">

                        <span class="uh-tag">
                            @if(count($sports))
                                {{ implode(', ', $sports) }}
                            @else
                                Venue olahraga
                            @endif
                        </span>

                        <h3>{{ $venueDisplayName }}</h3>

                        @if($venue->company_name && $venue->name !== $venue->company_name)
                            <p class="uh-meta">
                                <i class="fa-solid fa-building"></i>
                                <span>{{ $venue->company_name }}</span>
                            </p>
                        @endif

                        <p class="uh-meta">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>{{ $venue->address ?: 'Alamat belum tersedia' }}</span>
                        </p>

                        <div class="uh-court-info">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>
                                {{ $venue->lapangans_count ?? 0 }}
                                Lapangan terdaftar
                            </span>
                        </div>

                        <a class="uh-btn" href="{{ route('user.venues.show', $venue) }}">
                            Lihat Venue
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </article>

            @empty

                <div class="uh-empty" style="grid-column:1/-1">
                    <i class="fa-solid fa-store" style="font-size:30px;color:#168b50;margin-bottom:10px"></i>
                    <p>Belum ada venue aktif yang tersedia.</p>
                </div>

            @endforelse

        </div>

    </section>

    {{-- HIGHLIGHT EVENT RESMI DARI VENUE ADMIN --}}
    @if(isset($upcomingEvents) && $upcomingEvents->isNotEmpty())
        <section class="uh-section" style="margin-top:20px;">
            <div class="uh-section-head">
                <div>
                    <h2>
                        <i class="fa-solid fa-trophy" style="color:#d97706"></i>
                        Event &amp; Turnamen Resmi
                    </h2>
                    <p>Kompetisi olahraga resmi berhadiah dari mitra venue ArenaSports.</p>
                </div>
                <a class="uh-link" href="{{ route('user.openmatches') }}">
                    Lihat semua event
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
                @foreach($upcomingEvents as $ev)
                    <div style="background:#fff; border:1px solid #e0ece4; border-radius:18px; padding:20px; box-shadow:0 8px 24px rgba(0,0,0,.04); display:flex; flex-direction:column; justify-content:space-between; border-top:4px solid #168653;">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:10px;">
                                <span style="font-size:11px; font-weight:800; background:#e8f7ec; color:#146c43; padding:4px 8px; border-radius:20px;">
                                    <i class="fa-solid fa-shield-halved"></i> Resmi
                                </span>
                                <span style="font-size:11px; font-weight:700; background:#f3f4f6; color:#4b5563; padding:4px 8px; border-radius:20px;">
                                    {{ $ev->jenis }}
                                </span>
                            </div>
                            <h3 style="margin:0 0 8px; font-size:16px; color:#143e26;">{{ $ev->title }}</h3>
                            <p style="margin:0 0 12px; font-size:12px; color:#6b7280; line-height:1.5;">
                                <i class="fa-regular fa-calendar"></i> {{ $ev->tanggal?->format('d M Y') }} &nbsp;·&nbsp;
                                <i class="fa-solid fa-location-dot"></i> {{ $ev->lokasi }}
                            </p>
                            @if($ev->deskripsi)
                                <p style="margin:0 0 12px; font-size:12px; color:#374151; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    {{ $ev->deskripsi }}
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('user.openmatches') }}" style="display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border-radius:10px; background:#168653; color:#fff; font-size:12px; font-weight:700; text-decoration:none; text-align:center;">
                            Detail &amp; Pendaftaran
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- BOOKING DAN OPEN MATCH --}}
    <section class="uh-columns">

        {{-- BOOKING TERBARU --}}
        <div class="uh-panel">

            <div class="uh-section-head">
                <div>
                    <h2>
                        <i class="fa-solid fa-ticket" style="color:#168b50"></i>
                        Booking terbaru
                    </h2>
                    <p>Ringkasan pemesananmu.</p>
                </div>

                <a class="uh-link" href="{{ route('user.bookings') }}">
                    Semua
                </a>
            </div>

            @forelse($bookings as $booking)

                <div class="uh-row">
                    <div>
                        <strong>
                            {{ $booking->lapangan?->nama ?? 'Lapangan' }}
                        </strong>

                        <p class="uh-meta">
                            <i class="fa-regular fa-calendar"></i>
                            {{ $booking->booking_date?->format('d M Y') }}
                            ·
                            {{ substr($booking->start_time,0,5) }}
                            –
                            {{ substr($booking->end_time,0,5) }}
                        </p>
                    </div>

                    <span class="uh-tag">
                        {{ $booking->status }}
                    </span>
                </div>

            @empty

                <div class="uh-empty">
                    Belum ada booking. Yuk cari lapangan.
                </div>

            @endforelse

        </div>

        {{-- OPEN MATCH --}}
        <div class="uh-panel">

            <div class="uh-section-head">
                <div>
                    <h2>
                        <i class="fa-solid fa-people-group" style="color:#168b50"></i>
                        Open Match
                    </h2>
                    <p>Temukan teman bermain.</p>
                </div>

                <a class="uh-link" href="{{ route('user.openmatches') }}">
                    Jelajahi
                </a>
            </div>

            @forelse($matches as $match)

                @php
                    $joined = in_array(
                        (int)$match->id,
                        array_map('intval', $joinedMatchIds ?? []),
                        true
                    ) || (int)($match->booking?->user_id ?? 0) === (int)$user->id;
                @endphp

                <div class="uh-row">

                    <div style="min-width:0">
                        <strong>{{ $match->title }}</strong>

                        <p class="uh-meta">
                            <i class="fa-solid fa-medal"></i>
                            {{ $match->jenis }}
                        </p>

                        <p class="uh-meta">
                            <i class="fa-regular fa-calendar"></i>
                            {{ $match->tanggal?->format('d M Y') }}
                            ·
                            {{ substr($match->start_time,0,5) }}
                            –
                            {{ substr($match->end_time,0,5) }}
                        </p>

                        <p class="uh-meta">
                            <i class="fa-solid fa-location-dot"></i>
                            {{ $match->booking?->lapangan?->lokasi ?: ($match->booking?->lapangan?->venue?->address ?? 'Lokasi belum tersedia') }}
                        </p>
                    </div>

                    @if($joined)
                        <span class="uh-joined">
                            <i class="fa-solid fa-check"></i>
                            Sudah bergabung
                        </span>
                    @else
                        <span class="uh-available">
                            {{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }}
                        </span>
                    @endif

                </div>

            @empty

                <div class="uh-empty">
                    Belum ada Open Match yang tersedia.
                </div>

            @endforelse

        </div>

    </section>

</div>

@endsection