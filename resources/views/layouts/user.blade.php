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
{{-- CHATBOT ARENA ASSISTANT --}}
<div class="u-chat" id="uChat">

    <button class="u-chat-toggle" id="uChatToggle" type="button">
        <i class="fa-solid fa-comments"></i>
        <span>Butuh bantuan?</span>
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
(function () {

    const toggle = document.getElementById('uChatToggle');
    const panel = document.getElementById('uChatPanel');
    const close = document.getElementById('uChatClose');
    const body = document.getElementById('uChatBody');
    const form = document.getElementById('uChatForm');
    const input = document.getElementById('uChatInput');

    if (!toggle || !panel || !form || !input || !body) return;

    // Buka chatbot
    toggle.addEventListener('click', function () {
        panel.hidden = !panel.hidden;

        if (!panel.hidden) {
            input.focus();
            body.scrollTop = body.scrollHeight;
        }
    });

    // Tutup chatbot
    close.addEventListener('click', function () {
        panel.hidden = true;
    });

    // Database jawaban chatbot
    const knowledge = [

        {
            keywords: [
                'booking', 'pesan lapangan', 'sewa lapangan',
                'reservasi', 'memesan lapangan', 'cara pesan',
                'cara booking', 'pesan tempat'
            ],

            answer:
                'Untuk melakukan booking lapangan, ikuti langkah berikut:\n\n' +
                '1. Buka menu Cari Lapangan.\n' +
                '2. Pilih venue yang kamu inginkan.\n' +
                '3. Pilih lapangan yang tersedia.\n' +
                '4. Tentukan tanggal, jam mulai, dan durasi bermain.\n' +
                '5. Lanjutkan ke proses pembayaran.\n\n' +
                'Setelah pembayaran berhasil, booking kamu akan tercatat di sistem.'
        },

        {
            keywords: [
                'pembayaran', 'bayar', 'metode bayar',
                'cara bayar', 'midtrans', 'transaksi',
                'pembayaran booking'
            ],

            answer:
                'Pembayaran booking diproses melalui Midtrans.\n\n' +
                'Setelah memilih lapangan dan jadwal, lanjutkan ke halaman pembayaran. ' +
                'Ikuti instruksi pembayaran yang tersedia sampai transaksi selesai.\n\n' +
                'Kamu juga bisa memeriksa status transaksi melalui menu Booking Saya.'
        },

        {
            keywords: [
                'poin', 'point', 'reward', 'hadiah',
                'tukar poin', 'menukar poin', 'saldo poin'
            ],

            answer:
                'ArenaSports menyediakan fitur poin dan reward untuk pengguna.\n\n' +
                'Cara menukar poin:\n' +
                '1. Buka menu Tukar Poin.\n' +
                '2. Pilih reward yang tersedia.\n' +
                '3. Pastikan jumlah poin mencukupi.\n' +
                '4. Lakukan penukaran.\n\n' +
                'Saldo poin akan disesuaikan setelah penukaran berhasil.'
        },

        {
            keywords: [
                'open match', 'openmatch', 'cari teman',
                'cari pemain', 'pemain lain', 'gabung match',
                'bergabung match'
            ],

            answer:
                'Open Match merupakan fitur untuk mencari pemain lain agar kamu bisa bermain bersama.\n\n' +
                'Kamu dapat membuka menu Open Match untuk melihat pertandingan yang tersedia.\n\n' +
                'Pembuatan Open Match memerlukan booking aktif pada tanggal mendatang.'
        },

        {
            keywords: [
                'venue', 'daftar venue', 'mendaftarkan venue',
                'pemilik venue', 'pengelola venue'
            ],

            answer:
                'Jika kamu ingin mendaftarkan venue ke ArenaSports, buka menu Daftarkan Venue.\n\n' +
                'Lengkapi formulir pendaftaran dan dokumen yang diminta. ' +
                'Pengajuan akan diproses oleh pengelola ArenaSports.'
        },

        {
            keywords: [
                'lapangan', 'cari lapangan', 'fasilitas lapangan',
                'jadwal lapangan', 'tersedia'
            ],

            answer:
                'Untuk mencari lapangan, buka menu Cari Lapangan.\n\n' +
                'Kamu dapat melihat informasi lapangan, venue, fasilitas, dan jadwal yang tersedia. ' +
                'Pilih lapangan yang sesuai dengan kebutuhanmu.'
        },

        {
            keywords: [
                'riwayat', 'booking saya', 'pesanan saya',
                'status booking', 'pesanan'
            ],

            answer:
                'Kamu bisa melihat riwayat pemesanan melalui menu Booking Saya.\n\n' +
                'Di sana kamu dapat memeriksa detail booking dan status transaksi.'
        },

        {
            keywords: [
                'akun', 'profil', 'ubah profil',
                'edit profil', 'ganti nama', 'foto profil'
            ],

            answer:
                'Untuk mengubah informasi akun, buka halaman Profil.\n\n' +
                'Di halaman tersebut kamu dapat memperbarui informasi pribadi dan foto profil.'
        },

        {
            keywords: [
                'login', 'masuk akun', 'tidak bisa login',
                'lupa password', 'kata sandi'
            ],

            answer:
                'Untuk masuk ke ArenaSports, gunakan email dan password yang sudah terdaftar.\n\n' +
                'Pastikan email dan password sudah benar. Jika masih mengalami kendala, silakan hubungi pengelola.'
        },

        {
            keywords: [
                'daftar', 'register', 'registrasi akun',
                'buat akun', 'akun baru'
            ],

            answer:
                'Untuk membuat akun ArenaSports:\n\n' +
                '1. Klik tombol Daftar.\n' +
                '2. Isi nama, email, dan password.\n' +
                '3. Lengkapi konfirmasi pendaftaran.\n' +
                '4. Ikuti instruksi yang ditampilkan sampai akun berhasil dibuat.'
        },

        {
            keywords: [
                'admin', 'customer service', 'cs',
                'hubungi', 'kontak', 'bantuan petugas'
            ],

            answer:
                'Jika kamu membutuhkan bantuan lebih lanjut, silakan hubungi admin atau pengelola venue terkait melalui kontak resmi yang tersedia.'
        }

    ];

    // Membuat pesan
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

        // Menggunakan textContent agar pesan tidak dieksekusi sebagai HTML
        bubble.textContent = message;

        const time = document.createElement('small');
        time.className = 'u-chat-time';

        time.textContent = new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });

        content.appendChild(bubble);
        content.appendChild(time);

        wrapper.appendChild(content);
        body.appendChild(wrapper);

        body.scrollTop = body.scrollHeight;
    }

    // Menampilkan indikator bot sedang mengetik
    function showTyping() {

        const typing = document.createElement('div');
        typing.className = 'u-chat-message bot';
        typing.id = 'uChatTyping';

        typing.innerHTML = `
            <div class="u-chat-avatar small">
                <i class="fa-solid fa-robot"></i>
            </div>

            <div class="u-chat-message-content">
                <div class="u-chat-bubble u-typing">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;

        body.appendChild(typing);
        body.scrollTop = body.scrollHeight;
    }

    // Menghapus indikator mengetik
    function removeTyping() {
        document.getElementById('uChatTyping')?.remove();
    }

    // Mencari jawaban berdasarkan kata kunci
    function getAnswer(message) {

        const text = message
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s]/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (/^(hai|halo|hello|hi|hei|pagi|siang|sore|malam)(\s|$)/.test(text)) {
            return 'Halo! 👋 Ada yang bisa Arena Assistant bantu hari ini?';
        }

        if (/\b(terima kasih|makasih|thanks|thank you|thx)\b/.test(text)) {
            return 'Sama-sama! 😊 Senang bisa membantu. Jangan ragu bertanya lagi jika kamu membutuhkan bantuan.';
        }

        if (/\b(bye|dadah|sampai jumpa)\b/.test(text)) {
            return 'Sampai jumpa! Terima kasih sudah menggunakan ArenaSports. Selamat berolahraga! ⚽';
        }

        for (const item of knowledge) {

            const matched = item.keywords.some(keyword => {

                return text.includes(keyword.toLowerCase());

            });

            if (matched) {
                return item.answer;
            }
        }

        return 'Maaf, saya belum memahami pertanyaan tersebut. 😅\n\n' +
            'Saat ini saya bisa membantu menjawab pertanyaan tentang:\n\n' +
            '• Booking lapangan\n' +
            '• Pembayaran\n' +
            '• Tukar poin dan reward\n' +
            '• Open Match\n' +
            '• Pendaftaran venue\n' +
            '• Profil dan akun\n\n' +
            'Coba gunakan kata kunci yang lebih spesifik, ya!';
    }

    // Mengirim pesan
    // Mengirim pesan
async function sendMessage(message) {

    message = message.trim();

    if (!message) return;

    addMessage(message, 'user');

    input.value = '';
    input.disabled = true;

    showTyping();

    let answer;
    const text = message.toLowerCase();

    const asksNearby =
        text.includes('terdekat') ||
        text.includes('dekat saya') ||
        text.includes('sekitar saya') ||
        text.includes('di sekitar');

    const asksOpen =
        text.includes('masih buka') ||
        text.includes('sedang buka') ||
        text.includes('yang buka') ||
        text.includes('buka sekarang');

    if (asksOpen && text.includes('venue')) {
    answer = await findNearbyVenues();
} else {
    answer = getAnswer(message);

    await new Promise(resolve => setTimeout(resolve, 900));
}

    removeTyping();

    addMessage(answer, 'bot');

    input.disabled = false;
    input.focus();
}

    // Submit form
    form.addEventListener('submit', function (e) {

        e.preventDefault();

        sendMessage(input.value);

    });

    // Pilihan pertanyaan cepat
    body.querySelectorAll('[data-faq]').forEach(function (button) {

        button.addEventListener('click', function () {

            const questions = {
                booking: 'Bagaimana cara booking lapangan?',
                payment: 'Bagaimana cara melakukan pembayaran?',
                points: 'Bagaimana cara menukar poin?',
                match: 'Apa itu open match?'
            };

            sendMessage(questions[this.dataset.faq]);

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