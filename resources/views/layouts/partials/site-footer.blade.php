<style>
.as-site-footer{background:#081a14;color:#dce9df;margin-top:48px;font-family:inherit}
.as-site-footer .wrap{width:min(1180px,calc(100% - 40px));margin:0 auto;padding:48px 0 24px}
.as-site-footer .grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1.2fr;gap:35px;padding-bottom:34px}
.as-site-footer .brand{font:800 20px Manrope,DM Sans,sans-serif;margin-bottom:15px;color:#fff}
.as-site-footer .brand span{color:#c8f169}
.as-site-footer .desc{max-width:280px;color:#9fb3a8;font-size:12px;line-height:1.9;margin:0}
.as-site-footer h4{font:800 13px Manrope,DM Sans,sans-serif;margin:0 0 18px;color:#fff}
.as-site-footer .links{display:grid;gap:13px}
.as-site-footer .links a,.as-site-footer .contact{color:#9fb3a8;font-size:12px;text-decoration:none;line-height:1.6}
.as-site-footer .links a:hover{color:#c8f169}
.as-site-footer .contact{display:flex;gap:10px;margin-bottom:12px;align-items:flex-start}
.as-site-footer .contact i{width:17px;color:#c8f169;margin-top:2px}
.as-site-footer .socials{display:flex;flex-wrap:wrap;gap:9px;margin-top:18px}
.as-site-footer .socials span{width:37px;height:37px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.12);border-radius:11px;color:#c8f169;background:rgba(255,255,255,.035)}
.as-site-footer .bottom{border-top:1px solid rgba(255,255,255,.12);padding-top:19px;display:flex;align-items:center;justify-content:space-between;gap:15px;color:#82978c;font-size:11px}
.as-site-footer .bottom strong{color:#dce9df}
.as-site-footer .lang-switch{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.as-site-footer .lang-switch a{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.14);color:#dce9df;text-decoration:none;font-size:11px;font-weight:700;background:rgba(255,255,255,.03)}
.as-site-footer .lang-switch a.active{background:#c8f169;color:#102318;border-color:#c8f169}
.as-site-footer .lang-switch a:hover{border-color:#c8f169;color:#c8f169}
.as-site-footer .lang-switch a.active:hover{color:#102318}
@media(max-width:950px){.as-site-footer .grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:700px){.as-site-footer .wrap{width:calc(100% - 30px)}.as-site-footer .grid{grid-template-columns:1fr 1fr;gap:30px 18px}.as-site-footer .bottom{align-items:flex-start;flex-direction:column}}
@media(max-width:400px){.as-site-footer .grid{grid-template-columns:1fr}}
</style>

<footer class="as-site-footer">
    <div class="wrap">
        <div class="grid">
            <div>
                <div class="brand"><i class="fa-solid fa-futbol" style="color:#c8f169"></i> ARENA<span>SPORTS</span></div>
                <p class="desc">{{ __('Platform digital untuk menemukan lapangan, melakukan booking, dan membangun komunitas olahraga bersama ArenaSports.') }}</p>
                <div class="socials">
                    <span title="Instagram"><i class="fa-brands fa-instagram"></i></span>
                    <span title="TikTok"><i class="fa-brands fa-tiktok"></i></span>
                    <span title="Facebook"><i class="fa-brands fa-facebook-f"></i></span>
                    <span title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></span>
                </div>
            </div>

            <div>
                <h4>{{ __('Navigasi') }}</h4>
                <div class="links">
                    <a href="{{ route('welcome') }}">{{ __('Beranda') }}</a>
                    <a href="{{ route('welcome') }}#tentang">{{ __('Tentang Kami') }}</a>
                    <a href="{{ route('welcome') }}#fitur">{{ __('Fitur') }}</a>
                    <a href="{{ route('welcome') }}#cara-kerja">{{ __('Cara Kerja') }}</a>
                </div>
            </div>

            <div>
                <h4>{{ __('Layanan') }}</h4>
                <div class="links">
                    <a href="{{ route('user.venues') }}">{{ __('Cari Venue') }}</a>
                    <a href="{{ route('user.courts') }}">{{ __('Cari Lapangan') }}</a>
                    <a href="{{ route('user.openmatches') }}">{{ __('Open Match') }}</a>
                    <a href="{{ route('login') }}">{{ __('Akun Pengguna') }}</a>
                </div>
            </div>

            <div>
                <h4>{{ __('Kontak') }}</h4>
                <div class="contact"><i class="fa-brands fa-whatsapp"></i><span>085702710106</span></div>
                <div class="contact"><i class="fa-regular fa-envelope"></i><span>ArenaSports@gmail.com</span></div>

                @if(session('contact_success'))
                    <div style="margin:10px 0;padding:10px 12px;border-radius:10px;background:rgba(200,241,105,.12);color:#dff3c2;font-size:12px;line-height:1.5;">{{ session('contact_success') }}</div>
                @endif
                @if(session('contact_error'))
                    <div style="margin:10px 0;padding:10px 12px;border-radius:10px;background:rgba(255,120,120,.12);color:#ffd2d2;font-size:12px;line-height:1.5;">{{ session('contact_error') }}</div>
                @endif

                <form action="{{ route('contact.send') }}" method="POST" style="display:grid;gap:8px;margin-top:12px;">
                    @csrf
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Nama Anda') }}" required maxlength="120" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font:inherit;font-size:12px;">
                    <input type="text" name="contact" value="{{ old('contact') }}" placeholder="{{ __('Email / No. WhatsApp') }}" required maxlength="120" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font:inherit;font-size:12px;">
                    <textarea name="message" rows="3" placeholder="{{ __('Tulis keluhan, saran, atau pertanyaan...') }}" required maxlength="2000" style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font:inherit;font-size:12px;resize:vertical;">{{ old('message') }}</textarea>
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;" aria-hidden="true">
                    <button type="submit" style="border:0;border-radius:10px;padding:10px 14px;background:#c8f169;color:#102318;font-weight:800;font-size:12px;cursor:pointer;">{{ __('Kirim Pesan') }}</button>
                </form>
            </div>
        </div>

        <div class="bottom">
            <div>&copy; {{ date('Y') }} <strong>ArenaSports.</strong> {{ __('Seluruh hak cipta dilindungi.') }}</div>
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <div class="lang-switch" aria-label="{{ __('Bahasa') }}">
                    <span style="color:#82978c;font-size:11px;font-weight:700;">{{ __('Bahasa') }}:</span>
                    <a href="{{ route('lang.switch', 'id') }}" class="{{ app()->getLocale() === 'id' ? 'active' : '' }}">ID</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                </div>
                <div>Find Your Game. Build Your Community.</div>
            </div>
        </div>
    </div>
</footer>
