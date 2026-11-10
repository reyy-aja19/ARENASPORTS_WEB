<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071b19">

    <meta name="description" content="ArenaSports adalah platform untuk menemukan lapangan olahraga, melakukan booking, dan bergabung dengan komunitas olahraga.">

    <title>ArenaSports | Temukan Permainanmu</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --bg: #071b19;
            --panel: #102b26;
            --panel-light: #15362e;
            --lime: #c8f169;
            --green: #178653;
            --white: #f4f7f2;
            --muted: #aebfb6;
            --line: rgba(255,255,255,.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 90px;
        }

        body {
            background: var(--bg);
            color: var(--white);
            font-family: 'DM Sans', sans-serif;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, a {
            -webkit-tap-highlight-color: transparent;
        }

        .wrap {
            width: min(1180px, calc(100% - 48px));
            margin: auto;
        }

        /* NAVBAR */

        .nav {
            min-height: 82px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            font-family: Manrope, sans-serif;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.8px;
            white-space: nowrap;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: var(--lime);
            color: #09211b;
            display: grid;
            place-items: center;
            font-size: 21px;
        }

        .brand-sports {
            color: var(--lime);
        }

        .navlinks {
            display: flex;
            align-items: center;
            gap: 25px;
            color: #d1ddd6;
            font-size: 12px;
            font-weight: 600;
        }

        .navlinks a:hover {
            color: var(--lime);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 13px 19px;
            border-radius: 11px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            transition: .2s ease;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--lime);
            color: #0a211b;
        }

        .btn-primary:hover {
            background: #dcff94;
        }

        .btn-ghost {
            border-color: var(--line);
            background: rgba(255,255,255,.04);
            color: white;
        }

        /* HERO */

        .hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            min-height: 570px;
            border: 1px solid var(--line);
            border-radius: 27px;
            display: flex;
            align-items: center;
            background: var(--panel);
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -2;
            background:
                linear-gradient(
                    90deg,
                    rgba(5,23,20,.97) 0%,
                    rgba(5,23,20,.86) 43%,
                    rgba(5,23,20,.20) 100%
                ),
                url("https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=2000&q=85")
                center 45% / cover;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background: radial-gradient(
                circle at 75% 15%,
                rgba(200,241,105,.13),
                transparent 28%
            );
            pointer-events: none;
        }

        .hero-content {
            max-width: 690px;
            padding: 75px 7%;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--lime);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--lime);
            box-shadow: 0 0 0 5px rgba(200,241,105,.12);
        }

        .hero h1 {
            font: 800 clamp(42px, 6vw, 72px)/1.08 Manrope, sans-serif;
            letter-spacing: -3px;
            margin: 24px 0 20px;
        }

        .hero h1 span {
            color: var(--lime);
        }

        .hero-description {
            max-width: 510px;
            color: #d0dcd5;
            font-size: 15px;
            line-height: 1.9;
            margin-bottom: 28px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-buttons .btn {
            padding: 15px 20px;
        }

        .hero-note {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 32px;
            color: #c4d4cb;
            font-size: 11px;
        }

        .hero-note i {
            color: var(--lime);
        }

        .floating-card {
            position: absolute;
            right: 5%;
            bottom: 9%;
            width: 220px;
            padding: 19px;
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 17px;
            background: rgba(7,27,25,.82);
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 50px #00100d66;
        }

        .floating-card small {
            color: var(--lime);
            font-weight: 800;
            letter-spacing: 1px;
        }

        .floating-card h3 {
            font: 800 19px Manrope;
            margin: 13px 0 7px;
        }

        .floating-card p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        /* STATS */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--line);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            margin: 24px 0 90px;
        }

        .stat {
            min-height: 125px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: #0b211d;
        }

        .stat-icon {
            width: 49px;
            height: 49px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: rgba(200,241,105,.11);
            color: var(--lime);
            border-radius: 14px;
            font-size: 20px;
        }

        .stat strong {
            display: block;
            font: 800 16px Manrope;
        }

        .stat span {
            display: block;
            color: #91a69b;
            font-size: 11px;
            margin-top: 6px;
        }

        /* SECTION */

        .section {
            margin-bottom: 90px;
        }

        .section-heading {
            margin-bottom: 30px;
        }

        .section-heading h2 {
            font: 800 clamp(28px, 4vw, 39px)/1.2 Manrope;
            letter-spacing: -1.5px;
            margin-top: 13px;
        }

        .section-heading p {
            max-width: 650px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.9;
            margin-top: 12px;
        }

        /* ABOUT */

        .about-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 25px;
            align-items: stretch;
        }

        .about-main {
            padding: 34px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: linear-gradient(145deg, #12352c, #0d251f);
        }

        .about-main h3 {
            font: 800 23px Manrope;
            margin-bottom: 15px;
        }

        .about-main p {
            color: var(--muted);
            font-size: 13px;
            line-height: 2;
            margin-bottom: 13px;
        }

        .about-side {
            display: grid;
            gap: 13px;
        }

        .about-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255,255,255,.035);
        }

        .about-item i {
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(200,241,105,.12);
            color: var(--lime);
        }

        .about-item h4 {
            font: 700 14px Manrope;
            margin-bottom: 6px;
        }

        .about-item p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        /* FEATURES */

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .feature {
            min-height: 210px;
            padding: 26px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: linear-gradient(
                145deg,
                rgba(255,255,255,.055),
                rgba(255,255,255,.015)
            );
            transition: .2s ease;
        }

        .feature:hover {
            transform: translateY(-4px);
            border-color: rgba(200,241,105,.4);
        }

        .feature-icon {
            width: 47px;
            height: 47px;
            display: grid;
            place-items: center;
            background: var(--lime);
            color: #0b241c;
            border-radius: 14px;
            font-size: 20px;
        }

        .feature h3 {
            font: 800 16px Manrope;
            margin: 20px 0 9px;
        }

        .feature p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.9;
        }

        /* HOW IT WORKS */

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .step {
            position: relative;
            padding: 24px 19px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: var(--panel);
        }

        .step-number {
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--lime);
            color: #0b241c;
            font: 800 14px Manrope;
        }

        .step h3 {
            font: 800 14px Manrope;
            margin: 19px 0 9px;
        }

        .step p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.8;
        }

        /* CTA */

        .cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            padding: 38px;
            border-radius: 22px;
            background: var(--lime);
            color: #0b241c;
            margin-bottom: 85px;
        }

        .cta h2 {
            font: 800 clamp(23px, 3vw, 33px) Manrope;
            letter-spacing: -1px;
            margin-bottom: 9px;
        }

        .cta p {
            color: #34503a;
            font-size: 13px;
            line-height: 1.7;
        }

        .cta .btn {
            flex-shrink: 0;
            background: #0b241c;
            color: white;
        }

        /* FOOTER */

        .footer {
            border-top: 1px solid var(--line);
            background: #061512;
            padding: 60px 0 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1.2fr;
            gap: 35px;
            padding-bottom: 40px;
        }

        .footer-brand {
            font: 800 20px Manrope;
            margin-bottom: 15px;
        }

        .footer-brand span {
            color: var(--lime);
        }

        .footer-description {
            max-width: 280px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.9;
        }

        .footer-column h4 {
            font: 800 13px Manrope;
            margin-bottom: 18px;
            color: white;
        }

        .footer-links {
            display: grid;
            gap: 13px;
        }

        .footer-links a {
            color: var(--muted);
            font-size: 12px;
            transition: .2s;
        }

        .footer-links a:hover {
            color: var(--lime);
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .contact-item i {
            width: 17px;
            color: var(--lime);
            margin-top: 2px;
        }

        .socials {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 18px;
        }

        .socials span {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 11px;
            color: var(--lime);
            background: rgba(255,255,255,.035);
        }

        .footer-bottom {
            border-top: 1px solid var(--line);
            padding-top: 19px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            color: #82978c;
            font-size: 11px;
        }

        .footer-bottom strong {
            color: #dce9df;
        }

        /* RESPONSIVE */

        @media(max-width: 950px) {
            .navlinks {
                gap: 13px;
                font-size: 11px;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 700px) {
            .wrap {
                width: calc(100% - 30px);
            }

            .nav {
                min-height: 72px;
            }

            .navlinks {
                display: none;
            }

            .brand {
                font-size: 18px;
            }

            .brand-mark {
                width: 36px;
                height: 36px;
            }

            .nav-actions {
                gap: 6px;
            }

            .nav-actions .btn {
                padding: 10px 11px;
                font-size: 10px;
            }

            .hero {
                min-height: 630px;
                border-radius: 20px;
            }

            .hero::before {
                background:
                    linear-gradient(
                        90deg,
                        rgba(5,23,20,.94),
                        rgba(5,23,20,.55)
                    ),
                    url("https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1200&q=80")
                    center / cover;
            }

            .hero-content {
                padding: 45px 23px 190px;
            }

            .hero h1 {
                font-size: 43px;
                letter-spacing: -2px;
            }

            .hero-description {
                font-size: 13px;
            }

            .floating-card {
                left: 23px;
                right: 23px;
                bottom: 20px;
                width: auto;
            }

            .stats {
                grid-template-columns: 1fr;
                margin: 20px 0 65px;
            }

            .stat {
                min-height: 95px;
                padding: 17px 20px;
            }

            .section {
                margin-bottom: 65px;
            }

            .about-grid {
                grid-template-columns: 1fr;
            }

            .about-main {
                padding: 25px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .feature {
                min-height: unset;
            }

            .steps {
                grid-template-columns: 1fr 1fr;
            }

            .cta {
                padding: 27px 23px;
                align-items: flex-start;
                flex-direction: column;
                margin-bottom: 60px;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 30px 18px;
            }

            .footer-bottom {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media(max-width: 400px) {
            .steps {
                grid-template-columns: 1fr;
            }

            .brand {
                font-size: 16px;
            }

            .nav-actions .btn {
                padding: 9px;
                font-size: 9px;
            }

            .hero h1 {
                font-size: 37px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *, *::before, *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <header class="wrap nav">

        <a href="{{ route('welcome') }}" class="brand">
            <span class="brand-mark">
                <i class="fa-solid fa-futbol"></i>
            </span>

            <span>
                ARENA<span class="brand-sports">SPORTS</span>
            </span>
        </a>

        <nav class="navlinks">
            <a href="#tentang">Tentang Kami</a>
            <a href="#fitur">Fitur</a>
            <a href="#cara-kerja">Cara Kerja</a>
            <a href="{{ route('user.venues') }}">Venue</a>
            <a href="{{ route('user.openmatches') }}">Open Match</a>
        </nav>

        <div class="nav-actions">
            <a class="btn btn-ghost" href="{{ route('login') }}">
                Masuk
            </a>

            <a class="btn btn-primary" href="{{ route('register') }}">
                Daftar <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    </header>


    <main class="wrap">

        <!-- HERO -->
        <section class="hero">

            <div class="hero-content">

                <div class="eyebrow">
                    <span class="dot"></span>
                    YOUR GAME. YOUR COMMUNITY.
                </div>

                <h1>
                    Temukan lapangan.<br>
                    <span>Bangun momen.</span>
                </h1>

                <p class="hero-description">
                    ArenaSports hadir untuk memudahkanmu menemukan
                    lapangan olahraga, mengatur jadwal bermain,
                    melakukan booking, dan bertemu teman baru
                    dalam satu platform.
                </p>

                <div class="hero-buttons">

                    <a href="{{ route('user.venues') }}" class="btn btn-primary">
                        <i class="fa-solid fa-location-dot"></i>
                        Jelajahi Venue
                    </a>

                    <a href="{{ route('user.courts') }}" class="btn btn-ghost">
                        Lihat Lapangan
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                </div>

                <div class="hero-note">
                    <i class="fa-solid fa-circle-check"></i>
                    Satu platform untuk berbagai kebutuhan olahraga.
                </div>

            </div>

            <aside class="floating-card">

                <small>
                    <i class="fa-solid fa-bolt"></i>
                    ARENASPORTS MATCH
                </small>

                <h3>Find your team.</h3>

                <p>
                    Cari pertandingan terbuka, bergabung,
                    dan temukan teman bermain.
                </p>

            </aside>

        </section>


        <!-- INFORMASI UTAMA -->
        <section class="stats">

            <div class="stat">
                <div class="stat-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div>
                    <strong>Booking Praktis</strong>
                    <span>Atur jadwal bermain dengan mudah.</span>
                </div>
            </div>

            <div class="stat">
                <div class="stat-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>

                <div>
                    <strong>Pilihan Lapangan</strong>
                    <span>Temukan venue olahraga pilihanmu.</span>
                </div>
            </div>

            <div class="stat">
                <div class="stat-icon">
                    <i class="fa-solid fa-people-group"></i>
                </div>

                <div>
                    <strong>Main Bersama</strong>
                    <span>Terhubung melalui Open Match.</span>
                </div>
            </div>

        </section>


        <!-- TENTANG ARENASPORTS -->
        <section class="section" id="tentang">

            <div class="section-heading">

                <div class="eyebrow">
                    <span class="dot"></span>
                    ABOUT ARENASPORTS
                </div>

                <h2>
                    Mengenal lebih dekat<br>
                    ArenaSports.
                </h2>

                <p>
                    Bukan sekadar tempat mencari lapangan.
                    ArenaSports merupakan platform digital yang
                    menghubungkan pemain dengan fasilitas olahraga
                    dan komunitas untuk menciptakan pengalaman
                    bermain yang lebih praktis.
                </p>

            </div>

            <div class="about-grid">

                <div class="about-main">

                    <h3>Olahraga jadi lebih mudah dan terhubung.</h3>

                    <p>
                        ArenaSports dikembangkan untuk membantu pengguna
                        menemukan informasi lapangan olahraga dan
                        melakukan pemesanan jadwal secara lebih praktis.
                    </p>

                    <p>
                        Melalui fitur Open Match, pengguna juga dapat
                        menemukan pertandingan, bergabung dengan pemain
                        lain, dan berkomunikasi bersama peserta.
                    </p>

                    <p>
                        Kami ingin menjadikan aktivitas olahraga lebih
                        mudah diakses, terorganisasi, dan menyenangkan
                        bagi setiap pemain.
                    </p>

                </div>

                <div class="about-side">

                    <div class="about-item">
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <div>
                            <h4>Temukan Tempat Bermain</h4>
                            <p>
                                Jelajahi informasi venue dan lapangan
                                sesuai kebutuhan olahraga.
                            </p>
                        </div>
                    </div>

                    <div class="about-item">
                        <i class="fa-solid fa-credit-card"></i>

                        <div>
                            <h4>Proses Pemesanan</h4>
                            <p>
                                Atur jadwal dan lanjutkan proses
                                pemesanan melalui akun pengguna.
                            </p>
                        </div>
                    </div>

                    <div class="about-item">
                        <i class="fa-solid fa-users"></i>

                        <div>
                            <h4>Komunitas Pemain</h4>
                            <p>
                                Bergabung dalam Open Match dan
                                berinteraksi dengan pemain lainnya.
                            </p>
                        </div>
                    </div>

                </div>

            </div>

        </section>


        <!-- FITUR -->
        <section class="section" id="fitur">

            <div class="section-heading">

                <div class="eyebrow">
                    <span class="dot"></span>
                    OUR FEATURES
                </div>

                <h2>
                    Semua kebutuhan olahraga<br>
                    dalam satu platform.
                </h2>

                <p>
                    Nikmati berbagai fasilitas yang membantu
                    perjalanan olahragamu menjadi lebih mudah.
                </p>

            </div>

            <div class="features">

                <article class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>

                    <h3>Venue dan Lapangan</h3>

                    <p>
                        Temukan informasi tempat olahraga,
                        fasilitas, lokasi, dan pilihan lapangan
                        yang tersedia.
                    </p>
                </article>

                <article class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>

                    <h3>Booking Lapangan</h3>

                    <p>
                        Pilih lapangan, tentukan jadwal bermain,
                        dan lakukan pemesanan melalui akunmu.
                    </p>
                </article>

                <article class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>

                    <h3>Pembayaran Online</h3>

                    <p>
                        Nikmati proses pembayaran digital melalui
                        sistem pembayaran yang tersedia di platform.
                    </p>
                </article>

                <article class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-people-group"></i>
                    </div>

                    <h3>Open Match</h3>

                    <p>
                        Temukan pertandingan terbuka, bergabung
                        bersama pemain lain, dan isi kebutuhan tim.
                    </p>
                </article>

                <article class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-comments"></i>
                    </div>

                    <h3>Chat Antar Pemain</h3>

                    <p>
                        Berkomunikasi dengan peserta Open Match
                        untuk mengatur permainan bersama.
                    </p>
                </article>

                <article class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-trophy"></i>
                    </div>

                    <h3>Poin dan Reward</h3>

                    <p>
                        Kumpulkan poin dari aktivitas yang memenuhi
                        ketentuan dan nikmati reward yang tersedia.
                    </p>
                </article>

            </div>

        </section>


        <!-- CARA KERJA -->
        <section class="section" id="cara-kerja">

            <div class="section-heading">

                <div class="eyebrow">
                    <span class="dot"></span>
                    HOW IT WORKS
                </div>

                <h2>
                    Mulai bermain dalam<br>
                    beberapa langkah.
                </h2>

                <p>
                    Gunakan ArenaSports untuk merencanakan
                    kegiatan olahraga bersama teman.
                </p>

            </div>

            <div class="steps">

                <article class="step">
                    <div class="step-number">01</div>

                    <h3>Buat Akun</h3>

                    <p>
                        Daftarkan akun ArenaSports untuk
                        menggunakan layanan pemesanan.
                    </p>
                </article>

                <article class="step">
                    <div class="step-number">02</div>

                    <h3>Pilih Lapangan</h3>

                    <p>
                        Cari venue dan lapangan yang sesuai
                        dengan kebutuhanmu.
                    </p>
                </article>

                <article class="step">
                    <div class="step-number">03</div>

                    <h3>Booking dan Bayar</h3>

                    <p>
                        Tentukan jadwal, lakukan pemesanan,
                        dan selesaikan pembayaran.
                    </p>
                </article>

                <article class="step">
                    <div class="step-number">04</div>

                    <h3>Mulai Bermain</h3>

                    <p>
                        Nikmati permainan atau bergabung
                        dengan komunitas Open Match.
                    </p>
                </article>

            </div>

        </section>


        <!-- CTA -->
        <section class="cta">

            <div>
                <h2>Siap menemukan permainanmu?</h2>

                <p>
                    Bergabung dengan ArenaSports dan mulai
                    pengalaman olahraga yang lebih praktis.
                </p>
            </div>

            <a href="{{ route('register') }}" class="btn">
                Daftar Gratis
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </section>

    </main>


    <!-- FOOTER -->
    <footer class="footer">

        <div class="wrap">

            <div class="footer-grid">

                <!-- IDENTITAS -->
                <div>

                    <div class="footer-brand">
                        <i class="fa-solid fa-futbol"></i>
                        ARENA<span>SPORTS</span>
                    </div>

                    <p class="footer-description">
                        Platform digital untuk menemukan lapangan,
                        melakukan booking, dan membangun komunitas
                        olahraga bersama ArenaSports.
                    </p>

                    <div class="socials">
                        <span title="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </span>

                        <span title="TikTok">
                            <i class="fa-brands fa-tiktok"></i>
                        </span>

                        <span title="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </span>

                        <span title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </span>
                    </div>

                </div>


                <!-- NAVIGASI -->
                <div class="footer-column">

                    <h4>Navigasi</h4>

                    <div class="footer-links">
                        <a href="{{ route('welcome') }}">Beranda</a>
                        <a href="#tentang">Tentang Kami</a>
                        <a href="#fitur">Fitur</a>
                        <a href="#cara-kerja">Cara Kerja</a>
                    </div>

                </div>


                <!-- LAYANAN -->
                <div class="footer-column">

                    <h4>Layanan</h4>

                    <div class="footer-links">
                        <a href="{{ route('user.venues') }}">Cari Venue</a>
                        <a href="{{ route('user.courts') }}">Cari Lapangan</a>
                        <a href="{{ route('user.openmatches') }}">Open Match</a>
                        <a href="{{ route('login') }}">Akun Pengguna</a>
                    </div>

                </div>


                <!-- KONTAK -->
                <div class="footer-column">

                    <h4>Hubungi Kami</h4>

                    <div class="contact-item">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>085702710106</span>
                    </div>

                    <div class="contact-item">
                        <i class="fa-regular fa-envelope"></i>
                        <span>ArenaSports@gmail.com</span>
                    </div>

                    <div class="contact-item">
                        <i class="fa-brands fa-instagram"></i>
                        <span>@Arena_Sports</span>
                    </div>

                    <div class="contact-item">
                        <i class="fa-brands fa-tiktok"></i>
                        <span>@ArenaSports</span>
                    </div>

                    <div class="contact-item">
                        <i class="fa-brands fa-facebook"></i>
                        <span>Arena Sports</span>
                    </div>

                </div>

            </div>


            <!-- COPYRIGHT -->
            <div class="footer-bottom">

                <div>
                    &copy; {{ date('Y') }}
                    <strong>ArenaSports.</strong>
                    Seluruh hak cipta dilindungi.
                </div>

                <div>
                    Find Your Game. Build Your Community.
                </div>

            </div>

        </div>

    </footer>

</body>
</html>