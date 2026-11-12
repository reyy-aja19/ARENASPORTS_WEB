
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $mode === 'register' ? 'Daftar' : 'Login' }} WhatsApp · ARENASPORTS</title>

    <link rel="stylesheet" href="{{ asset('user-ui.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    {{-- Cloudflare Turnstile --}}
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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
                <i class="fa-brands fa-whatsapp"></i> WHATSAPP OTP
            </span>

            <h1>Masuk lebih mudah.<br><em>Akun tetap aman.</em></h1>

            <p>Gunakan nomor WhatsApp aktif untuk menerima kode verifikasi sekali pakai.</p>
        </div>

        <div class="u-auth-footnote">
            © {{ date('Y') }} ArenaSports · Play more, worry less.
        </div>
    </section>

    <section class="u-auth-form">
        <div class="u-auth-card">

            <span class="u-form-kicker">VERIFIKASI NOMOR</span>

            <h2>{{ $mode === 'register' ? 'Daftar dengan WhatsApp' : 'Login dengan WhatsApp' }}</h2>

            <p class="u-login-subtitle">
                Kami akan mengirimkan kode OTP 6 digit ke WhatsApp kamu.
            </p>

            @if(session('success'))
                <div class="u-alert success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->has('captcha'))
                <div class="u-alert error">
                    <i class="fa-solid fa-shield-halved"></i>
                    {{ $errors->first('captcha') }}
                </div>
            @elseif($errors->any())
                <div class="u-alert error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST"
                  action="{{ $mode === 'register' ? route('phone.register.send') : route('phone.login.send') }}">
                @csrf

                @if($mode === 'register')
                    <div class="u-field">
                        <label for="name">Nama lengkap</label>
                        <input
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            maxlength="255"
                            autocomplete="name">
                    </div>
                @endif

                <div class="u-field">
                    <label for="phone">Nomor WhatsApp</label>

                    <div class="u-input-icon">
                        <i class="fa-brands fa-whatsapp"></i>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone') }}"
                            placeholder="081234567890"
                            required
                            autocomplete="tel">
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

                <button class="u-btn u-login-submit" type="submit">
                    Kirim kode OTP
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <p class="u-register-prompt">
                <a href="{{ route('login') }}">Kembali ke login email</a>
            </p>

            @if($mode === 'login')
                <p class="u-register-prompt">
                    Belum punya akun?
                    <a href="{{ route('phone.register') }}">Daftar dengan WhatsApp</a>
                </p>
            @else
                <p class="u-register-prompt">
                    Sudah punya akun?
                    <a href="{{ route('phone.login') }}">Login dengan WhatsApp</a>
                </p>
            @endif

        </div>
    </section>

</main>
</body>
</html>
