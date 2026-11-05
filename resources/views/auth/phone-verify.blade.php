<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verifikasi OTP · ARENASPORTS</title><link rel="stylesheet" href="{{ asset('user-ui.css') }}"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></head>
<body class="u-login-page"><main class="u-auth u-auth-premium">
<section class="u-auth-hero"><a class="u-brand" href="{{ route('welcome') }}"><span class="u-logo"><i class="fa-solid fa-futbol"></i></span><span>ARENA<span style="color:#a5efbf">SPORTS</span><small style="color:#c7e7d3">FIND YOUR GAME</small></span></a><div class="u-auth-copy"><span class="u-auth-kicker"><i class="fa-brands fa-whatsapp"></i> ONE TIME PASSWORD</span><h1>Satu langkah lagi.<br><em>Verifikasi akunmu.</em></h1><p>Masukkan kode enam digit yang dikirim ke WhatsApp kamu.</p></div><div class="u-auth-footnote">© {{ date('Y') }} ArenaSports · Play more, worry less.</div></section>
<section class="u-auth-form"><div class="u-auth-card"><span class="u-form-kicker">KODE VERIFIKASI</span><h2>Masukkan OTP</h2><p class="u-login-subtitle">Kode dikirim ke nomor {{ session('phone_otp_pending.phone') }} dan berlaku selama 5 menit.</p>
@if(session('success'))<div class="u-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="u-alert error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('phone.verify') }}">@csrf
<div class="u-field"><label for="otp">Kode OTP 6 digit</label><input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6" placeholder="123456" required autocomplete="one-time-code" style="letter-spacing:10px;text-align:center;font-size:24px;font-weight:800"></div>
<button class="u-btn u-login-submit" type="submit">Verifikasi dan lanjutkan <i class="fa-solid fa-arrow-right"></i></button></form>
<p class="u-register-prompt"><a href="{{ session('phone_otp_pending.purpose') === 'register' ? route('phone.register') : route('phone.login') }}">Kirim kode baru</a></p><p class="u-register-prompt"><a href="{{ route('login') }}">Kembali ke login</a></p>
</div></section></main></body></html>