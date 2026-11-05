<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'ARENASPORTS' }} · ARENASPORTS</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('user-ui.css') }}">
</head>

<body class="user-body">

<header class="u-top">

    <a class="u-brand"
       href="{{ (session('admin_logged_in') && session('admin_role') === 'user') ? route('user.home') : route('welcome') }}">

        <span class="u-logo">
            <i class="fa-solid fa-futbol"></i>
        </span>

        <span>
            ARENA<span class="u-green">SPORTS</span>
            <small>Find your game</small>
        </span>
    </a>

    <nav class="u-nav">

        <a class="{{ request()->routeIs('user.venues*') ? 'active' : '' }}"
           href="{{ route('user.venues') }}">
            Cari Venue
        </a>

        <a class="{{ request()->routeIs('user.courts*', 'courts.detail') ? 'active' : '' }}"
           href="{{ route('user.courts') }}">
            Cari Lapangan
        </a>

        <a class="{{ request()->routeIs('user.openmatches*') ? 'active' : '' }}"
           href="{{ route('user.openmatches') }}">
            Open Match
        </a>

        @if($user ?? false)

            <a class="{{ request()->routeIs('user.bookings*') ? 'active' : '' }}"
               href="{{ route('user.bookings') }}">
                Booking Saya
            </a>

            <a class="{{ request()->routeIs('user.rewards*') ? 'active' : '' }}"
               href="{{ route('user.rewards') }}">
                Tukar Poin
            </a>

            {{-- MENU DAFTARKAN VENUE --}}
            <a class="{{ request()->routeIs('user.venue-application*') ? 'active' : '' }}"
               href="{{ route('user.venue-application') }}">
                <i class="fa-solid fa-building"></i>
                Daftarkan Venue
            </a>

        @endif

    </nav>

    @if($user ?? false)

        <div class="u-user">

            <a class="u-points" href="{{ route('user.rewards') }}">
                <i class="fa-solid fa-coins"></i>
                {{ number_format($user->points ?? 0) }} poin
            </a>

            {{-- NOTIFIKASI --}}
            <div class="u-notif-wrap">

                <button class="u-notif-button"
                        type="button"
                        id="uNotifToggle"
                        aria-label="Notifikasi">

                    <i class="fa-regular fa-bell"></i>

                    @if($user->unreadNotifications()->count())
                        <span class="u-notif-badge">
                            {{ $user->unreadNotifications()->count() }}
                        </span>
                    @endif

                </button>

                <div class="u-notif-menu" id="uNotifMenu" hidden>

                    <div class="u-notif-head">
                        <strong>Notifikasi</strong>
                        <a href="{{ route('user.notifications') }}">Lihat semua</a>
                    </div>

                    @forelse($user->notifications()->latest()->take(5)->get() as $notification)

                        <form method="POST"
                              action="{{ route('user.notifications.read', $notification->id) }}"
                              class="u-notif-item {{ $notification->read_at ? '' : 'unread' }}">

                            @csrf

                            <button type="submit">

                                <strong>
                                    {{ $notification->data['title'] ?? 'Notifikasi ArenaSports' }}
                                </strong>

                                <span>
                                    {{ $notification->data['message'] ?? '' }}
                                </span>

                                <small>
                                    {{ $notification->created_at?->diffForHumans() }}
                                </small>

                            </button>

                        </form>

                    @empty

                        <div class="u-notif-empty">
                            Belum ada notifikasi.
                        </div>

                    @endforelse

                    @if($user->unreadNotifications()->exists())

                        <form method="POST"
                              action="{{ route('user.notifications.read-all') }}">

                            @csrf

                            <button class="u-notif-all" type="submit">
                                Tandai semua dibaca
                            </button>

                        </form>

                    @endif

                </div>

            </div>

            {{-- PROFIL --}}
            <a class="u-profile-link" href="{{ route('user.profile') }}">

                <span class="u-profile-name">
                    {{ $user->name }}
                </span>

                <span class="u-avatar" title="Profil">

                    @if($user->profile_photo)

                        <img
                            src="{{ str_starts_with($user->profile_photo, 'profiles/')
                                ? asset('storage/'.$user->profile_photo)
                                : asset($user->profile_photo) }}"
                            alt="Foto profil">

                    @else

                        <i class="fa-solid fa-user"></i>

                    @endif

                </span>

            </a>

            {{-- LOGOUT --}}
            <form action="{{ route('logout.perform') }}" method="POST">
                @csrf

                <button class="u-logout" title="Keluar">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </button>
            </form>

        </div>

    @else

        <div class="u-guest-actions">
            <a href="{{ route('login') }}">Login</a>
            <a class="u-btn small" href="{{ route('register') }}">Daftar</a>
        </div>

    @endif

</header>


{{-- KONTEN UTAMA --}}
<main class="u-main">

    @if(session('success'))
        <div class="u-alert success">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="u-alert error">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ session('error') ?? $errors->first() }}
        </div>
    @endif

    @yield('content')

</main>


{{-- CHATBOT --}}
<div class="u-chat" id="uChat">

    <button class="u-chat-toggle"
            id="uChatToggle"
            aria-label="Buka chatbot">

        <i class="fa-solid fa-comments"></i>
        <span>Butuh bantuan?</span>

    </button>

    <section class="u-chat-panel" id="uChatPanel" hidden>

        <div class="u-chat-head">

            <span>
                <i class="fa-solid fa-robot"></i>
                Arena Assistant
            </span>

            <button type="button" id="uChatClose" aria-label="Tutup">
                ×
            </button>

        </div>

        <div class="u-chat-body" id="uChatBody">

            <div class="u-chat-bubble">
                Hai {{ ($user ?? null)?->name ?? 'Pengguna' }}!
                Aku bisa bantu jawab pertanyaan umum soal booking,
                pembayaran, dan poin.
            </div>

            <div class="u-chat-choices">

                <button type="button" data-faq="booking">
                    Cara booking lapangan
                </button>

                <button type="button" data-faq="payment">
                    Cara pembayaran
                </button>

                <button type="button" data-faq="points">
                    Cara tukar poin
                </button>

                <button type="button" data-faq="match">
                    Tentang open match
                </button>

            </div>

        </div>

    </section>

</div>


{{-- FOOTER --}}
<footer class="u-footer">
    © {{ date('Y') }} ARENASPORTS · Main lebih mudah, booking lebih cepat.
</footer>


{{-- SCRIPT NOTIFIKASI --}}
<script>
(() => {
    const button = document.getElementById('uNotifToggle');
    const menu = document.getElementById('uNotifMenu');

    if (!button || !menu) return;

    button.addEventListener('click', () => {
        menu.hidden = !menu.hidden;
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.u-notif-wrap')) {
            menu.hidden = true;
        }
    });
})();
</script>


{{-- SCRIPT CHATBOT --}}
<script>
(function() {

    const toggle = document.getElementById('uChatToggle');
    const panel = document.getElementById('uChatPanel');
    const close = document.getElementById('uChatClose');
    const body = document.getElementById('uChatBody');

    if (!toggle || !panel) return;

    toggle.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
    });

    close.addEventListener('click', () => {
        panel.hidden = true;
    });

    const answers = {

        booking: 'Buka Cari Lapangan, pilih venue, tentukan tanggal, jam mulai, durasi, dan metode pembayaran. Jadwal yang sudah terpakai akan ditolak sistem.',

        payment: 'Buka Riwayat Booking untuk melihat detail transaksi dan status pembayaran. Pembayaran diproses otomatis melalui Midtrans.',

        points: 'Poin dapat ditukar di menu Tukar Poin. Pastikan saldo poin mencukupi; poin yang digunakan akan dipotong dari saldo.',

        match: 'Open Match dipakai untuk mencari pemain lain. Pembuatannya memerlukan booking aktif pada tanggal mendatang.'

    };

    body.querySelectorAll('[data-faq]').forEach(btn => {

        btn.addEventListener('click', () => {

            const bubble = document.createElement('div');

            bubble.className = 'u-chat-bubble';

            bubble.textContent =
                answers[btn.dataset.faq] ||
                'Silakan hubungi pengelola untuk bantuan lebih lanjut.';

            body.appendChild(bubble);

            body.scrollTop = body.scrollHeight;

        });

    });

})();
</script>


{{-- SCRIPT SLIDER GALERI --}}
<script>
(function () {

    document.querySelectorAll('[data-user-slider]').forEach(function (slider) {

        const slides = Array.from(
            slider.querySelectorAll('.u-gallery-slide')
        );

        const current = slider.querySelector('[data-user-current]');

        const dots = Array.from(
            slider.querySelectorAll('[data-user-dot]')
        );

        let index = 0;

        if (slides.length < 2) return;

        function show(next) {

            index = (next + slides.length) % slides.length;

            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });

            if (current) {
                current.textContent = index + 1;
            }

        }

        slider.querySelector('[data-user-prev]')
            ?.addEventListener('click', () => show(index - 1));

        slider.querySelector('[data-user-next]')
            ?.addEventListener('click', () => show(index + 1));

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                show(Number(dot.dataset.userDot));
            });
        });

        let touchStart = 0;

        slider.addEventListener('touchstart', e => {

            touchStart = e.changedTouches[0].screenX;

        }, { passive: true });

        slider.addEventListener('touchend', e => {

            const delta =
                e.changedTouches[0].screenX - touchStart;

            if (Math.abs(delta) > 45) {
                show(index + (delta < 0 ? 1 : -1));
            }

        }, { passive: true });

    });

})();
</script>


@stack('scripts')

</body>
</html>