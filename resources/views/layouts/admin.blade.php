<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin ArenaSports' }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('admin-ui.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @keyframes bellShake {
            0%, 100% { transform: rotate(0); }
            20%, 60% { transform: rotate(-15deg); }
            40%, 80% { transform: rotate(15deg); }
        }
        .bell-shake i {
            animation: bellShake 0.6s ease;
            color: #10b981 !important;
        }
        .notif-badge.badge-bounce {
            transform: scale(1.3);
            transition: transform 0.3s ease;
        }
    </style>
    @yield('head')
</head>
<body>
@php
    /*
     * Gunakan data pengguna dari controller jika tersedia.
     * Jika tidak, gunakan pengguna dari session yang sedang login.
     */
    if (empty($adminUser) && session()->has('admin_user_id')) {
        $adminUser = \App\Models\User::find(session('admin_user_id'));
    }
    $adminUser = $adminUser ?? auth()->user();

    $adminRole = $adminUser?->role
        ?? session('admin_role', '');

    $isSuperAdmin = $adminRole === 'superadmin';
    $isAdmin = in_array($adminRole, ['admin', 'superadmin', 'venue_admin'], true);
    $isVenueAdmin = $adminRole === 'venue_admin';

    $pendingReviewCount = $pendingReviewCount ?? 0;
    $pendingPaymentCount = $pendingPaymentCount ?? 0;
    $totalNotificationCount = $totalNotificationCount ?? 0;
    $notifications = $notifications ?? [];
@endphp

<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-circle">
                <i class="fa-regular fa-futbol"></i>
            </div>

            <div class="brand-copy">
                <div class="brand-title">ArenaSports</div>
                <div class="brand-sub">Admin Control Panel</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <span class="menu-icon"><i class="fa-solid fa-table-cells-large"></i></span>
                <span class="label">Dashboard</span>
            </a>

            @if($isSuperAdmin)
                <a class="sidebar-link {{ request()->routeIs('admin.venue-applications*') ? 'active' : '' }}" href="{{ route('admin.venue-applications') }}"><span class="menu-icon"><i class="fa-solid fa-store"></i></span><span class="label">Request Admin Venue</span></a>
                <a class="sidebar-link {{ request()->routeIs('admin.venue-withdrawals*') ? 'active' : '' }}" href="{{ route('admin.venue-withdrawals') }}"><span class="menu-icon"><i class="fa-solid fa-money-bill-transfer"></i></span><span class="label">Penarikan Venue</span></a>
                <a class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
                   href="{{ route('admin.users') }}">
                    <span class="menu-icon"><i class="fa-solid fa-circle-user"></i></span>
                    <span class="label">Management User</span>
                </a>
            @endif

            @if($isVenueAdmin)
                <a class="sidebar-link {{ request()->routeIs('admin.venue-register*') ? 'active' : '' }}" href="{{ route('admin.venue-register') }}"><span class="menu-icon"><i class="fa-solid fa-circle-plus"></i></span><span class="label">Daftarkan Venue Baru</span></a>
                <a class="sidebar-link {{ request()->routeIs('admin.venue-profile*') ? 'active' : '' }}" href="{{ route('admin.venue-profile') }}"><span class="menu-icon"><i class="fa-solid fa-store"></i></span><span class="label">Profil Venue</span></a>
                <a class="sidebar-link {{ request()->routeIs('admin.venue-finance*') ? 'active' : '' }}" href="{{ route('admin.venue-finance') }}"><span class="menu-icon"><i class="fa-solid fa-wallet"></i></span><span class="label">Saldo & Penarikan</span></a>
            @endif
            <a class="sidebar-link {{ request()->routeIs('admin.courts*') ? 'active' : '' }}"
               href="{{ route('admin.courts') }}">
                <span class="menu-icon"><i class="fa-solid fa-vector-square"></i></span>
                <span class="label">Management Lapangan</span>
            </a>
            @if($isAdmin)
    <a class="sidebar-link {{ request()->routeIs('admin.bookings*') ? 'active' : '' }}"
       href="{{ route('admin.bookings') }}">
        <span class="menu-icon">
            <i class="fa-solid fa-calendar-check"></i>
        </span>
        <span class="label">Manajemen Booking</span>
    </a>
@endif

            <a class="sidebar-link {{ request()->routeIs('admin.reviews*') ? 'active' : '' }}"
               href="{{ route('admin.reviews') }}">
                <span class="menu-icon"><i class="fa-regular fa-message"></i></span>
                <span class="label">Review Komentar</span>

                @if($pendingReviewCount > 0)
                    <span class="menu-badge">{{ $pendingReviewCount }}</span>
                @endif
            </a>

            @if($isSuperAdmin)
                <a class="sidebar-link {{ request()->routeIs('admin.rewards*') ? 'active' : '' }}"
                   href="{{ route('admin.rewards') }}">
                    <span class="menu-icon"><i class="fa-solid fa-gift"></i></span>
                    <span class="label">Poin & Voucher</span>
                </a>
            @endif

            <a class="sidebar-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}"
               href="{{ route('admin.payments') }}">
                <span class="menu-icon"><i class="fa-solid fa-wallet"></i></span>
                <span class="label">Pembayaran</span>

                @if($pendingPaymentCount > 0)
                    <span class="menu-badge">{{ $pendingPaymentCount }}</span>
                @endif
            </a>

            <a class="sidebar-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}"
               href="{{ route('admin.reports') }}">
                <span class="menu-icon"><i class="fa-regular fa-file-lines"></i></span>
                <span class="label">Export Laporan</span>
            </a>

            @if($isAdmin)
                <a class="sidebar-link {{ request()->routeIs('admin.openmatches*') ? 'active' : '' }}"
                   href="{{ route('admin.openmatches') }}">
                    <span class="menu-icon"><i class="fa-solid fa-users"></i></span>
                    <span class="label">Open Match</span>
                </a>

                <a class="sidebar-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}"
                   href="{{ route('admin.events.index') }}">
                    <span class="menu-icon"><i class="fa-solid fa-calendar-days"></i></span>
                    <span class="label">Manajemen Event</span>
                </a>
            @endif

            @if($isSuperAdmin)
                <a class="sidebar-link {{ request()->routeIs('admin.control-center') ? 'active' : '' }}"
                   href="{{ route('admin.control-center') }}">
                    <span class="menu-icon"><i class="fa-solid fa-sliders"></i></span>
                    <span class="label">Pusat Kendali</span>
                </a>
            @endif

            @if($isSuperAdmin)
                <a class="sidebar-link {{ request()->routeIs('admin.chatbot.*') ? 'active' : '' }}"
                   href="{{ route('admin.chatbot.edit') }}">
                    <span class="menu-icon"><i class="fa-solid fa-robot"></i></span>
                    <span class="label">Pengaturan Chatbot AI</span>
                </a>
            @endif

            <a class="sidebar-link {{ request()->routeIs('admin.profile*') ? 'active' : '' }}"
               href="{{ route('admin.profile') }}">
                <span class="menu-icon"><i class="fa-regular fa-id-card"></i></span>
                <span class="label">Profil</span>
            </a>

            <a class="sidebar-link logout {{ request()->routeIs('admin.logout') ? 'active' : '' }}"
               href="{{ route('admin.logout') }}">
                <span class="menu-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                <span class="label">Log Out</span>
            </a>
        </nav>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <form class="search-wrap search-clean"
                  action=""
                  method="GET"
                  id="adminGlobalSearch">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search..."
                    data-global-search
                    autocomplete="off"
                >

                <button type="button" id="adminSearchClear" aria-label="Hapus Pencarian" style="display: none; background: transparent; border: none; cursor: pointer; color: #94a3b8; padding: 0 8px; font-size: 14px;">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <button type="submit" aria-label="Search">
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="topbar-right">
                <a href="{{ route('user.home') }}" class="btn-ui btn-gray" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:12px; padding:7px 12px; border-radius:10px;" title="Lihat aplikasi dari sisi pemain/pembeli">
                    <i class="fa-solid fa-store"></i> Mode Pengunjung
                </a>

                <div class="notification-wrap">
                    <button
                        class="topbar-chip bell-chip anim-click"
                        type="button"
                        aria-label="Notifikasi"
                        data-notification-toggle
                    >
                        <i class="fa-regular fa-bell"></i>

                        @if($totalNotificationCount > 0)
                            <span class="notif-badge">{{ $totalNotificationCount }}</span>
                        @endif
                    </button>

                    <div class="notification-panel" id="notificationPanel">
                        <div class="notification-title">Notifikasi</div>

                        <div class="notification-list">
                            @forelse($notifications as $notification)
                                <a
                                    href="{{ $notification['url'] ?? '#' }}"
                                    class="notification-item anim-click"
                                >
                                    <div class="notification-icon">
                                        <i class="{{ $notification['icon'] ?? 'fa-regular fa-bell' }}"></i>
                                    </div>

                                    <div class="notification-copy">
                                        <strong>{{ $notification['label'] ?? 'Notifikasi' }}</strong>
                                        <span>{{ $notification['meta'] ?? '' }}</span>
                                    </div>

                                    <small>{{ $notification['time'] ?? '' }}</small>
                                </a>
                            @empty
                                <div class="notification-empty">
                                    Belum ada notifikasi baru.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.profile') }}" class="topbar-profile anim-click">
                    <div class="avatar avatar-photo">
                        @if($adminUser?->profile_photo_url)
                            <img
                                src="{{ $adminUser->profile_photo_url }}"
                                alt="{{ $adminUser->name ?? 'Admin' }}"
                            >
                        @elseif($adminUser?->profile_photo)
                            <img
                                src="{{ str_starts_with($adminUser->profile_photo, 'profiles/') ? asset('storage/'.$adminUser->profile_photo) : asset($adminUser->profile_photo) }}"
                                alt="{{ $adminUser->name ?? 'Admin' }}"
                            >
                        @else
                            <i class="fa-solid fa-user"></i>
                        @endif
                    </div>

                    <div class="user-meta">
                        <strong>{{ $adminUser?->name ?? 'Admin' }}</strong>
                        {{ $adminUser?->email ?? 'Belum login' }}
                    </div>
                </a>
            </div>
        </header>

        <section class="content">
            <div class="page-head">
                <div class="page-title-wrap">
                    <h1 class="page-title">{{ $heading ?? 'Dashboard' }}</h1>
                </div>
            </div>

            @if(session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="flash flash-error">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="flash flash-error">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll(
        '.anim-click, .btn-ui, .sidebar-link, .court-card, .panel-card, ' +
        '.stat-card, .reward-card, .review-card, .data-table tbody tr'
    ).forEach(function (el) {
        el.addEventListener('click', function () {
            el.classList.remove('clicked');
            void el.offsetWidth;
            el.classList.add('clicked');
        });
    });

    const notificationWrap = document.querySelector('.notification-wrap');
    const notificationToggle = document.querySelector('[data-notification-toggle]');
    const notificationPanel = document.getElementById('notificationPanel');

    if (notificationToggle && notificationPanel && notificationWrap) {
        notificationToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            notificationWrap.classList.toggle('is-open');
        });

        notificationPanel.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        notificationPanel.addEventListener('wheel', function (event) {
            event.stopPropagation();
        }, { passive: true });

        document.addEventListener('click', function () {
            notificationWrap.classList.remove('is-open');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                notificationWrap.classList.remove('is-open');
            }
        });
    }

    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;

            if (!previewId || !this.files || !this.files[0]) {
                return;
            }

            const preview = document.getElementById(previewId);

            if (preview) {
                if (preview.dataset.objectUrl) {
                    URL.revokeObjectURL(preview.dataset.objectUrl);
                }

                const objectUrl = URL.createObjectURL(this.files[0]);
                preview.src = objectUrl;
                preview.dataset.objectUrl = objectUrl;
                preview.closest('.preview-shell')?.classList.add('has-image');
            }
        });
    });

    const searchInput = document.querySelector('[data-global-search]');
    const searchForm = document.getElementById('adminGlobalSearch');
    const searchClear = document.getElementById('adminSearchClear');

    const localInputs = document.querySelectorAll(
        '[data-local-filter], [data-global-search]'
    );

    function normalize(value) {
        return (value || '').toString().toLowerCase().trim();
    }

    function applyFilter() {
        const q = normalize(searchInput?.value);
        if (searchClear) {
            searchClear.style.display = q ? 'inline-block' : 'none';
        }

        const jenis = normalize(
            document.querySelector('[data-local-filter-select="jenis"]')?.value
        );
        const status = normalize(
            document.querySelector('[data-local-filter-select="status"]')?.value
        );

        // Targetkan seluruh komponen tabel / kartu tanpa memecah row accordion perpanjangan
        const items = document.querySelectorAll(
            '[data-search-item], .data-table tbody tr:not([id^="extension-"]):not(.extension-row), ' +
            '.marketplace-table tbody tr, .cc-table tbody tr, .cc-item, .court-card, ' +
            '.reward-card, .review-card, .openmatch-card, .va-card, .event-card, .admin-event-card'
        );

        items.forEach(function (item) {
            const text = normalize(item.innerText);
            const matchText = !q || text.includes(q);
            const matchJenis = !jenis ||
                normalize(item.dataset.jenis).includes(jenis) ||
                text.includes(jenis);
            const matchStatus = !status ||
                normalize(item.dataset.status).includes(status) ||
                text.includes(status);

            item.style.display = (matchText && matchJenis && matchStatus) ? '' : 'none';
        });
    }

    if (searchClear && searchInput) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            applyFilter();
            searchInput.focus();
        });
    }

    localInputs.forEach(function (input) {
        input.addEventListener('input', applyFilter);
        input.addEventListener('change', applyFilter);
    });

    document.querySelectorAll('[data-local-filter-select]').forEach(function (input) {
        input.addEventListener('change', applyFilter);
    });

    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            const hasLocalItems = document.querySelectorAll(
                '[data-search-item], .data-table tbody tr:not([id^="extension-"]), .marketplace-table tbody tr, .cc-item, .court-card, .reward-card, .review-card, .openmatch-card'
            ).length > 0;

            if (hasLocalItems) {
                event.preventDefault();
                applyFilter();
            }
        });
    }

    applyFilter();

    // Realtime Notifications Polling (setiap 6 detik)
    let currentNotifCount = {{ (int) $totalNotificationCount }};
    const notifToggleBtn = document.querySelector('[data-notification-toggle]');
    const notifPanelList = document.querySelector('.notification-list');

    function pollAdminNotifications() {
        fetch('{{ route('admin.api.notifications') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (data) {
            const newCount = Number(data.totalCount || 0);

            // Jika jumlah notifikasi bertambah, aktifkan efek lonceng bergetar
            if (newCount > currentNotifCount && notifToggleBtn) {
                notifToggleBtn.classList.remove('bell-shake');
                void notifToggleBtn.offsetWidth;
                notifToggleBtn.classList.add('bell-shake');
            }

            // Update badge notifikasi
            let badge = notifToggleBtn ? notifToggleBtn.querySelector('.notif-badge') : null;
            if (newCount > 0) {
                if (!badge && notifToggleBtn) {
                    badge = document.createElement('span');
                    badge.className = 'notif-badge';
                    notifToggleBtn.appendChild(badge);
                }
                if (badge) {
                    badge.textContent = newCount;
                    if (newCount > currentNotifCount) {
                        badge.classList.add('badge-bounce');
                        setTimeout(() => badge.classList.remove('badge-bounce'), 400);
                    }
                }
            } else if (badge) {
                badge.remove();
            }

            // Perbarui list notifikasi jika data items ada
            if (notifPanelList && Array.isArray(data.notifications)) {
                if (data.notifications.length === 0) {
                    notifPanelList.innerHTML = '<div class="notification-empty">Belum ada notifikasi baru.</div>';
                } else {
                    let html = '';
                    data.notifications.forEach(function (n) {
                        const icon = n.icon || 'fa-solid fa-bell';
                        const label = n.label || 'Notifikasi';
                        const meta = n.meta || '';
                        const time = n.time || '';
                        const url = n.url || '#';

                        html += '<a href="' + url + '" class="notification-item anim-click">' +
                            '<div class="notification-icon"><i class="' + icon + '"></i></div>' +
                            '<div class="notification-copy">' +
                                '<strong>' + label + '</strong>' +
                                '<span>' + meta + '</span>' +
                            '</div>' +
                            '<small>' + time + '</small>' +
                        '</a>';
                    });
                    notifPanelList.innerHTML = html;
                }
            }

            currentNotifCount = newCount;
        })
        .catch(function (err) {
            // Silently fail on connection drop
        });
    }

    setInterval(pollAdminNotifications, 6000);

    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        const slides = Array.from(slider.querySelectorAll('.slide-photo'));
        const current = slider.querySelector('[data-slide-current]');
        let index = 0;

        function show(next) {
            if (!slides.length) return;

            index = (next + slides.length) % slides.length;

            slides.forEach(function (slide, i) {
                slide.classList.toggle('active', i === index);
            });

            if (current) {
                current.textContent = index + 1;
            }
        }

        slider.querySelector('[data-slide-prev]')
            ?.addEventListener('click', function () {
                show(index - 1);
            });

        slider.querySelector('[data-slide-next]')
            ?.addEventListener('click', function () {
                show(index + 1);
            });
    });

    const notifList = document.querySelector('.notification-list');

    if (notifList) {
        notifList.addEventListener('wheel', function (event) {
            const atTop = notifList.scrollTop === 0;
            const atBottom =
                notifList.scrollTop + notifList.clientHeight >=
                notifList.scrollHeight - 1;

            if (
                (event.deltaY < 0 && !atTop) ||
                (event.deltaY > 0 && !atBottom)
            ) {
                event.stopPropagation();
            }
        }, { passive: true });
    }
});
</script>

@stack('scripts')
</body>
</html>