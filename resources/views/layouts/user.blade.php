<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'ARENASPORTS' }} · ARENASPORTS</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('user-ui.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @keyframes userBellShake {
            0%, 100% { transform: rotate(0); }
            20%, 60% { transform: rotate(-15deg); }
            40%, 80% { transform: rotate(15deg); }
        }
        .u-bell-shake i {
            animation: userBellShake 0.6s ease;
            color: #178653 !important;
        }
        .u-badge-bounce {
            transform: scale(1.3);
            transition: transform 0.3s ease;
        }
    </style>
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

            @if($user->role === 'venue_admin')
                <a href="{{ route('admin.dashboard') }}" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:10px; background:#0e5a36; color:#fff; font-size:12px; font-weight:700; text-decoration:none; margin-right:6px;">
                    <i class="fa-solid fa-chart-line"></i> Panel Admin Venue
                </a>
            @endif

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

                    <div id="uNotifItemsContainer">
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
                    </div>

                    <div class="u-notif-menu-actions" style="display:flex;gap:8px;padding:10px 12px;border-top:1px solid #edf1ed;">
                        <form id="uNotifReadAllForm" method="POST" action="{{ route('user.notifications.read-all') }}" style="flex:1;">
                            @csrf
                            <button class="u-notif-all" type="submit" style="width:100%;" {{ $user->unreadNotifications()->exists() ? '' : 'disabled' }}>
                                <i class="fa-solid fa-check-double"></i> Tandai sudah dibaca
                            </button>
                        </form>
                        <form method="POST" action="{{ route('user.notifications.clear') }}"
                              onsubmit="return confirm('Hapus seluruh notifikasi akun ini? Tindakan ini tidak dapat dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button class="u-notif-all" type="submit" style="color:#b42318;" {{ $user->notifications()->exists() ? '' : 'disabled' }}>
                                <i class="fa-regular fa-trash-can"></i> Clear
                            </button>
                        </form>
                    </div>

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
{{-- CHATBOT ARENA ASSISTANT --}}
<div class="u-chat" id="uChat">

    <button class="u-chat-toggle" id="uChatToggle" type="button" aria-label="Buka Arena Assistant" title="Arena Assistant">
        <i class="fa-solid fa-robot" aria-hidden="true"></i>
    </button>

    <section class="u-chat-panel" id="uChatPanel" hidden>

        {{-- HEADER --}}
        <div class="u-chat-head">

            <div class="u-chat-profile">
                <div class="u-chat-avatar">
                    <i class="fa-solid fa-robot"></i>
                </div>

                <div>
                    <strong>Arena Assistant</strong>
                    <small>
                        <span class="u-online-dot"></span>
                        Siap membantu kamu
                    </small>
                </div>
            </div>

            <button type="button" id="uChatClose" aria-label="Tutup chatbot">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        {{-- AREA CHAT --}}
        <div class="u-chat-body" id="uChatBody">

            <div class="u-chat-date">
                <span>Hari ini</span>
            </div>

            <div class="u-chat-message bot">
                <div class="u-chat-avatar small">
                    <i class="fa-solid fa-robot"></i>
                </div>

                <div class="u-chat-message-content">
                    <div class="u-chat-bubble">
                        Hai {{ ($user ?? null)?->name ?? 'Pengguna' }}! 👋

                        Selamat datang di ArenaSports.

                        Saya Arena Assistant. Saya siap membantu menjawab
                        pertanyaan seputar booking lapangan, pembayaran,
                        venue, poin, dan open match.

                        Ada yang bisa saya bantu?
                    </div>

                    <small class="u-chat-time">Sekarang</small>
                </div>
            </div>

            {{-- PILIHAN PERTANYAAN --}}
            <div class="u-chat-suggestions">

                <button type="button" data-faq="booking">
                    <i class="fa-solid fa-calendar-check"></i>
                    Cara booking
                </button>

                <button type="button" data-faq="payment">
                    <i class="fa-solid fa-wallet"></i>
                    Pembayaran
                </button>

                <button type="button" data-faq="points">
                    <i class="fa-solid fa-coins"></i>
                    Tukar poin
                </button>

                <button type="button" data-faq="match">
                    <i class="fa-solid fa-users"></i>
                    Open match
                </button>

            </div>

        </div>

        {{-- INPUT PESAN --}}
        <form class="u-chat-form" id="uChatForm">

            <input
                type="text"
                id="uChatInput"
                placeholder="Tulis pesan kamu..."
                autocomplete="off"
                maxlength="500"
                required
            >

            <button type="submit" id="uChatSend" aria-label="Kirim pesan">
                <i class="fa-solid fa-paper-plane"></i>
            </button>

        </form>

        <div class="u-chat-footer">
            ArenaSports Assistant · Bantuan otomatis
        </div>

    </section>

</div>





{{-- SCRIPT NOTIFIKASI REALTIME --}}
<script>
(() => {
    const button = document.getElementById('uNotifToggle');
    const menu = document.getElementById('uNotifMenu');
    const itemsContainer = document.getElementById('uNotifItemsContainer');
    const markAllWrap = document.getElementById('uNotifMarkAllWrap');

    if (!button || !menu) return;

    button.addEventListener('click', () => {
        menu.hidden = !menu.hidden;
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.u-notif-wrap')) {
            menu.hidden = true;
        }
    });

    @if($user ?? false)
    let currentUserNotifCount = {{ (int) $user->unreadNotifications()->count() }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function pollUserNotifications() {
        fetch('{{ route('user.api.notifications') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            const count = Number(data.unreadCount || 0);

            // Jika ada notifikasi baru masuk, berikan efek visual lonceng bergetar
            if (count > currentUserNotifCount) {
                button.classList.remove('u-bell-shake');
                void button.offsetWidth;
                button.classList.add('u-bell-shake');
            }

            // Perbarui badge
            let badge = button.querySelector('.u-notif-badge');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'u-notif-badge';
                    button.appendChild(badge);
                }
                badge.textContent = count;
                if (count > currentUserNotifCount) {
                    badge.classList.add('u-badge-bounce');
                    setTimeout(() => badge.classList.remove('u-badge-bounce'), 400);
                }
                if (markAllWrap) markAllWrap.style.display = '';
            } else {
                if (badge) badge.remove();
                if (markAllWrap) markAllWrap.style.display = 'none';
            }

            // Perbarui daftar dropdown items
            if (itemsContainer && Array.isArray(data.notifications)) {
                if (data.notifications.length === 0) {
                    itemsContainer.innerHTML = '<div class="u-notif-empty">Belum ada notifikasi.</div>';
                } else {
                    let html = '';
                    data.notifications.forEach(n => {
                        const unreadClass = n.is_read ? '' : 'unread';
                        html += `
                        <form method="POST" action="${n.read_url}" class="u-notif-item ${unreadClass}">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit">
                                <strong>${n.title || 'Notifikasi ArenaSports'}</strong>
                                <span>${n.message || ''}</span>
                                <small>${n.time || ''}</small>
                            </button>
                        </form>`;
                    });
                    itemsContainer.innerHTML = html;
                }
            }

            currentUserNotifCount = count;
        })
        .catch(err => {
            // Silently fail on connection drop
        });
    }

    // Polling setiap 6 detik
    setInterval(pollUserNotifications, 6000);
    @endif
})();
</script>


{{-- SCRIPT CHATBOT --}}
<script>
(function () {
    const toggle = document.getElementById('uChatToggle');
    const panel = document.getElementById('uChatPanel');
    const close = document.getElementById('uChatClose');
    const body = document.getElementById('uChatBody');
    const form = document.getElementById('uChatForm');
    const input = document.getElementById('uChatInput');
    if (!toggle || !panel || !form || !input || !body) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const replyUrl = @json(route('chatbot.reply'));
    let busy = false;

    toggle.addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        if (!panel.hidden) { input.focus(); body.scrollTop = body.scrollHeight; }
    });
    close.addEventListener('click', function () { panel.hidden = true; });

    function addMessage(message, type) {
        const wrapper = document.createElement('div');
        wrapper.className = 'u-chat-message ' + type;
        if (type === 'bot') {
            const avatar = document.createElement('div');
            avatar.className = 'u-chat-avatar small';
            avatar.innerHTML = '<i class="fa-solid fa-robot"></i>';
            wrapper.appendChild(avatar);
        }
        const content = document.createElement('div');
        content.className = 'u-chat-message-content';
        const bubble = document.createElement('div');
        bubble.className = 'u-chat-bubble';
        bubble.textContent = String(message ?? '');
        const time = document.createElement('small');
        time.className = 'u-chat-time';
        time.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        content.appendChild(bubble);
        content.appendChild(time);
        wrapper.appendChild(content);
        body.appendChild(wrapper);
        body.scrollTop = body.scrollHeight;
    }

    function showTyping() {
        const typing = document.createElement('div');
        typing.className = 'u-chat-message bot';
        typing.id = 'uChatTyping';
        typing.innerHTML = '<div class="u-chat-avatar small"><i class="fa-solid fa-robot"></i></div><div class="u-chat-message-content"><div class="u-chat-bubble u-typing"><span></span><span></span><span></span></div></div>';
        body.appendChild(typing);
        body.scrollTop = body.scrollHeight;
    }
    function removeTyping() { document.getElementById('uChatTyping')?.remove(); }

    async function sendMessage(message) {
        message = (message || '').trim();
        if (!message || busy) return;
        busy = true;
        addMessage(message, 'user');
        input.value = '';
        input.disabled = true;
        showTyping();
        try {
            const res = await fetch(replyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message })
            });
            const data = await res.json().catch(() => ({}));
            removeTyping();
            if (!res.ok) {
                addMessage(data.message || data.reply || 'Maaf, asisten AI sedang tidak bisa merespons. Coba lagi.', 'bot');
            } else {
                addMessage(data.reply || 'Maaf, saya belum bisa menjawab itu.', 'bot');
            }
        } catch (e) {
            removeTyping();
            addMessage('Maaf, koneksi ke asisten AI gagal. Periksa jaringan lalu coba lagi.', 'bot');
        } finally {
            busy = false;
            input.disabled = false;
            input.focus();
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendMessage(input.value);
    });

    body.querySelectorAll('[data-faq]').forEach(function (button) {
        button.addEventListener('click', function () {
            const questions = {
                booking: 'Bagaimana cara booking lapangan?',
                payment: 'Bagaimana cara melakukan pembayaran?',
                points: 'Bagaimana cara menukar poin?',
                match: 'Apa itu open match dan cara ikutnya?'
            };
            sendMessage(questions[this.dataset.faq] || this.textContent.trim());
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


@include('layouts.partials.site-footer')

@stack('scripts')

</body>
</html>