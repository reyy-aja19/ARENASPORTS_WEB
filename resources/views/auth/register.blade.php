
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#073e2b">

    <title>Daftar - ARENASPORTS</title>

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

    <style>
        .u-register-card .u-field {
            margin: 13px 0;
        }

        .u-register-card .u-field input {
            min-height: 44px;
        }

        .u-register-card .u-field label {
            margin-bottom: 6px;
        }

        .u-register-card .u-register-submit {
            margin-top: 8px;
        }

        .u-register-card .u-register-options {
            display: grid;
            gap: 9px;
            margin-top: 12px;
        }

        .u-register-card .u-register-options a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 45px;
            padding: 11px;
            border: 1px solid #dfe9e2;
            border-radius: 11px;
            color: #35483d;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
        }

        .u-register-card .u-register-options a:hover {
            background: #f3faf5;
        }

        .u-register-card .u-register-login {
            text-align: center;
            margin: 17px 0 0;
            font-size: 12px;
        }

        @media(max-width: 650px) {
            .u-register-card {
                padding: 22px 19px;
            }
        }
    </style>
</head>

<body class="u-login-page">

<main class="u-auth u-auth-premium">

    <!-- PANEL KIRI -->
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
                JOIN OUR COMMUNITY
            </span>

            <h1>
                Mulai perjalananmu.<br>
                <em>Temukan permainan.</em>
            </h1>

            <p>
                Bergabung dengan ArenaSports dan nikmati kemudahan
                mencari lapangan, melakukan booking, serta bermain
                bersama teman.
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
            &copy; {{ date('Y') }} ArenaSports - Play more, worry less.
        </div>

    </section>

    <!-- PANEL KANAN -->
    <section class="u-auth-form">

        <div class="u-auth-card u-register-card">

            <div class="u-auth-mobile-brand">
                ARENA<span>SPORTS</span>
            </div>

            <span class="u-form-kicker">
                BUAT AKUN BARU
            </span>

            <h2>Daftar ArenaSports</h2>

            <p>
                Buat akun dan mulai pengalaman olahraga yang lebih mudah.
            </p>

            {{-- Pesan error --}}
            @if(session('error'))
                <div class="u-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Pesan berhasil --}}
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
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.submit') }}">
                @csrf

                {{-- Nama lengkap --}}
                <div class="u-field">
                    <label for="name">Nama lengkap</label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama lengkap"
                        required
                        autocomplete="name"
                    >
                </div>

                {{-- Email --}}
                <div class="u-field">
                    <label for="email">Alamat email</label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="nama@email.com"
                        required
                        autocomplete="email"
                    >
                </div>

                {{-- Nomor HP --}}
                <div class="u-field">
                    <label for="phone">Nomor HP (opsional)</label>

                    <input
                        id="phone"
                        type="tel"
                        name="phone"
                        value="{{ old('phone') }}"
                        placeholder="08xxxxxxxxxx"
                        autocomplete="tel"
                    >
                </div>

                {{-- Password --}}
                <div class="u-field">
                    <label for="password">
                        Password (minimal 8 karakter)
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Buat password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>

                {{-- Konfirmasi password --}}
                <div class="u-field">
                    <label for="password_confirmation">
                        Konfirmasi password
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>

                {{-- Cloudflare Turnstile CAPTCHA --}}
                <div style="display:flex; justify-content:center; margin:18px 0;">
                    <div
                        class="cf-turnstile"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-theme="light">
                    </div>
                </div>

                {{-- Tombol daftar --}}
                <button
                    class="u-btn u-register-submit"
                    type="submit">
                    Buat akun ArenaSports
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

            </form>

            <div class="u-login-divider">
                <span>atau daftar dengan</span>
            </div>

            <div class="u-register-options">

                {{-- Daftar Google --}}
                <a href="{{ route('auth.google.redirect') }}">
                    <i class="fa-brands fa-google"></i>
                    Daftar dengan Google
                </a>

                {{-- Daftar WhatsApp --}}
                <a href="{{ route('phone.register') }}">
                    <i class="fa-brands fa-whatsapp"></i>
                    Daftar dengan WhatsApp
                </a>

            </div>

            <p class="u-register-login">
                Sudah punya akun?
                <a href="{{ route('login') }}">Masuk sekarang</a>
            </p>

            <div class="u-login-security">
                <i class="fa-solid fa-shield-halved"></i>
                Informasi akun kamu dilindungi.
            </div>

        </div>

    </section>

</main>

</body>
</html>
