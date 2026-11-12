
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#073e2b">

    <title>Masuk · ARENASPORTS</title>

    <link rel="stylesheet" href="{{ asset('user-ui.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- Cloudflare Turnstile CAPTCHA --}}
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer>
    </script>
</head>

<body class="u-login-page">

<main class="u-auth u-auth-premium">

    <section class="u-auth-hero">

        <a class="u-brand" href="{{ route('welcome') }}">
            <span class="u-logo">
                <i class="fa-solid fa-futbol"></i>
            </span>

            <span>
                ARENA<span style="color:#a5efbf">SPORTS</span>
                <small style="color:#c7e7d3">FIND YOUR GAME</small>
            </span>
        </a>

        <div class="u-auth-copy">

            <span class="u-auth-kicker">
                <i class="fa-solid fa-bolt"></i>
                YOUR GAME STARTS HERE
            </span>

            <h1>
                Temukan lapangan.<br>
                <em>Bangun momen.</em>
            </h1>

            <p>
                Satu tempat untuk mencari venue, mengatur jadwal bermain,
                menemukan teman satu tim, dan menikmati setiap pertandingan.
            </p>

            <div class="u-auth-benefits">
                <span>
                    <i class="fa-solid fa-location-dot"></i>
                    Venue pilihan
                </span>

                <span>
                    <i class="fa-solid fa-calendar-check"></i>
                    Booking mudah
                </span>

                <span>
                    <i class="fa-solid fa-trophy"></i>
                    Poin &amp; reward
                </span>
            </div>

        </div>

        <div class="u-auth-footnote">
            © {{ date('Y') }} ArenaSports · Play more, worry less.
        </div>

    </section>

    <section class="u-auth-form">

        <div class="u-auth-card">

            <div class="u-auth-mobile-brand">
                ARENA<span>SPORTS</span>
            </div>

            <span class="u-form-kicker">
                SELAMAT DATANG KEMBALI
            </span>

            <h2>Masuk ke akunmu</h2>

            <p class="u-login-subtitle">
                Lanjutkan perjalanan olahraga bersama ArenaSports.
            </p>

            {{-- Pesan error dan success --}}
            @if(session('error'))
                <div class="u-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="u-alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Pesan error CAPTCHA --}}
            @if($errors->has('captcha'))
                <div class="u-alert error">
                    <i class="fa-solid fa-shield-halved"></i>
                    {{ $errors->first('captcha') }}
                </div>
            @endif

            {{-- Pesan error validasi lainnya --}}
            @if($errors->any() && !$errors->has('captcha'))
                <div class="u-alert error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                {{-- Email --}}
                <div class="u-field">
                    <label for="email">Alamat email</label>

                    <div class="u-input-icon">
                        <i class="fa-regular fa-envelope"></i>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="nama@email.com"
                            required
                            autocomplete="email"
                            autofocus>
                    </div>
                </div>

                {{-- Password --}}
<div class="u-field">
    <label for="password">Password</label>

    <div class="u-input-icon">
        <i class="fa-solid fa-lock"></i>

        <input
            id="password"
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
            autocomplete="current-password">

        <button
            class="u-password-toggle"
            type="button"
            id="togglePassword"
            aria-label="Tampilkan password">
            <i class="fa-regular fa-eye"></i>
        </button>
    </div>

    <div style="display:flex; justify-content:flex-end; margin-top:10px;">
        <a href="{{ route('password.request') }}"
           style="font-size:14px; color:#073e2b; font-weight:600; text-decoration:none;">
            Lupa Password?
        </a>
    </div>
</div>

                {{-- Cloudflare Turnstile CAPTCHA --}}
                <div style="display:flex; justify-content:center; margin:18px 0;">
                    <div
                        class="cf-turnstile"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-theme="light">
                    </div>
                </div>

                {{-- Tombol Login --}}
                <button class="u-btn u-login-submit" type="submit">
                    Masuk ke ArenaSports
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

            </form>

            <div class="u-login-divider">
                <span>atau lanjutkan dengan</span>
            </div>

            {{-- Login WhatsApp --}}
            <a class="u-google-login" href="{{ route('phone.login') }}">
                <i class="fa-brands fa-whatsapp"></i>
                Masuk dengan nomor WhatsApp
            </a>

            {{-- Login Google --}}
            <a class="u-google-login" href="{{ route('auth.google.redirect') }}">
                <i class="fa-brands fa-google"></i>
                Masuk dengan Google
            </a>

            <p class="u-register-prompt">
                Belum punya akun?
                <a href="{{ route('register') }}">Daftar gratis</a>
            </p>

            <div class="u-login-security">
                <i class="fa-solid fa-shield-halved"></i>
                Akun dan informasi kamu dilindungi.
            </div>

            <p class="u-login-admin-note">
                Admin Venue dan Superadmin juga masuk melalui halaman ini.
            </p>

        </div>

    </section>

</main>

{{-- Script tampilkan/sembunyikan password --}}
<script>
    document.getElementById('togglePassword')?.addEventListener('click', function () {

        const input = document.getElementById('password');
        const icon = this.querySelector('i');

        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';

        icon.className = show
            ? 'fa-regular fa-eye-slash'
            : 'fa-regular fa-eye';

        this.setAttribute(
            'aria-label',
            show ? 'Sembunyikan password' : 'Tampilkan password'
        );

    });
</script>

</body>
</html>
