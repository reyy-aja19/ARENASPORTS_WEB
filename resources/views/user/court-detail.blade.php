
@extends('layouts.user')

@section('content')

{{-- HEADER HALAMAN --}}
<div class="u-section-head">
    <div>
        <h2>{{ $lapangan->nama }}</h2>
        <p>{{ __('Detail venue sebelum melakukan pemesanan.') }}</p>
    </div>

    <a class="u-btn ghost"
       href="{{ $lapangan->venue ? route('user.venues.show', $lapangan->venue) : route('user.venues') }}">
        {{ __('Kembali ke venue') }}
    </a>
</div>

{{-- PROSES FOTO LAPANGAN --}}
@php
    $courtPhotos = collect($lapangan->gallery_urls);
    $avgRating = $lapangan->reviews_avg_rating ?? $lapangan->rating;
    $priceRules = $lapangan->priceRules->sortByDesc('priority')->values();
@endphp


{{-- KARTU DETAIL DAN BOOKING --}}
<div class="u-cards"
     style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">

    {{-- ========================================= --}}
    {{-- KARTU DETAIL LAPANGAN --}}
    {{-- ========================================= --}}
    <article class="u-card">

        {{-- GALERI FOTO --}}
        <div class="u-card-img u-court-gallery" data-user-slider>

            <div class="u-gallery-track">

                @forelse($courtPhotos as $photo)
                    <img
                        class="u-gallery-slide {{ $loop->first ? 'active' : '' }}"
                        src="{{ $photo }}"
                        alt="{{ $lapangan->nama }} - Foto {{ $loop->iteration }}"
                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                    >
                @empty
                    <div class="u-gallery-empty">
                        <i class="fa-solid fa-futbol"></i>
                        <span>{{ __('Foto lapangan belum tersedia') }}</span>
                    </div>
                @endforelse

            </div>

            {{-- KONTROL SLIDER --}}
            @if($courtPhotos->count() > 1)

                <button
                    type="button"
                    class="u-gallery-arrow prev"
                    data-user-prev
                    aria-label="Foto sebelumnya">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button
                    type="button"
                    class="u-gallery-arrow next"
                    data-user-next
                    aria-label="Foto berikutnya">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                {{-- NOMOR FOTO --}}
                <span class="u-gallery-count">
                    <span data-user-current>1</span>
                    / {{ $courtPhotos->count() }}
                </span>

                {{-- DOTS SLIDER --}}
                <div class="u-gallery-dots">
                    @foreach($courtPhotos as $photo)
                        <button
                            type="button"
                            class="{{ $loop->first ? 'active' : '' }}"
                            data-user-dot="{{ $loop->index }}"
                            aria-label="Lihat foto {{ $loop->iteration }}">
                        </button>
                    @endforeach
                </div>

            @endif

        </div>


        {{-- INFORMASI LAPANGAN --}}
        <div class="u-card-body">

            <div class="u-row">
                <span class="u-tag">
                    {{ $lapangan->jenis }}
                </span>

                @if($lapangan->area_type)
                    <span class="u-tag" style="background:#f2f8f4;color:#29523a">{{ $lapangan->area_type }}</span>
                @endif

                <span class="u-meta">
                    ★ {{ $avgRating ? number_format((float) $avgRating, 1) : 'Baru' }}
                </span>
            </div>

            <h3>{{ $lapangan->nama }}</h3>

{{-- INFORMASI VENUE ASAL --}}
<div style="
    background:#f0fdf4;
    border:1px solid #bbf7d0;
    border-radius:10px;
    padding:12px;
    margin:12px 0;
">

    <div style="font-size:12px;color:#64748b;margin-bottom:5px">
        <i class="fa-solid fa-building"></i>
        {{ __('Lapangan ini berada di venue') }}
    </div>

    @if($lapangan->venue)

        <a href="{{ route('user.venues.show', $lapangan->venue) }}"
           style="
                color:#15803d;
                font-weight:700;
                text-decoration:none;
           ">
            {{ $lapangan->venue->name }}
            <i class="fa-solid fa-arrow-up-right-from-square"
               style="font-size:11px"></i>
        </a>

    @else

        <span style="color:#64748b">{{ __('Venue belum tersedia') }}</span>

    @endif

</div>

<p class="u-meta">
    <i class="fa-solid fa-location-dot"></i>
    {{ $lapangan->display_location }}
</p>

            <p>
                {{ $lapangan->deskripsi ?: __('Belum ada deskripsi untuk lapangan ini.') }}
            </p>

            @if($lapangan->court_facilities_list)
                <p><strong>{{ __('Fasilitas lapangan') }}</strong></p>
                <p>{{ implode(', ', $lapangan->court_facilities_list) }}</p>
            @endif

            @if($lapangan->floor_type)
                <p class="u-meta"><i class="fa-solid fa-layer-group"></i> {{ __('Jenis lantai') }}: {{ $lapangan->floor_type }}</p>
            @endif

            <p class="u-meta">
                <i class="fa-solid fa-store"></i>
                {{ __('Status operasional') }}:
                <strong style="color:{{ $lapangan->is_bookable ? '#087747' : '#94600b' }}">{{ $lapangan->operational_status ?: 'Beroperasi' }}</strong>
            </p>

            @if($lapangan->is_under_maintenance && $lapangan->maintenance_note)
                <p class="u-meta" style="color:#94600b"><i class="fa-solid fa-triangle-exclamation"></i> {{ $lapangan->maintenance_note }}</p>
            @endif

            @if($priceRules->count())
                <div style="margin-top:12px;padding:12px;border:1px solid #e2eee6;border-radius:12px;background:#f7fbf8">
                    <strong style="display:block;font-size:13px;color:#164b31;margin-bottom:8px"><i class="fa-solid fa-clock"></i> {{ __('Aturan harga') }}</strong>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        @foreach($priceRules as $rule)
                            <div style="display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#4a6055">
                                <span>
                                    {{ $rule->label ?: ucfirst($rule->day_type) }}
                                    · {{ substr((string) $rule->start_time, 0, 5) }}–{{ substr((string) $rule->end_time, 0, 5) }}
                                    @if($rule->day_type === 'custom' && $rule->days)
                                        · {{ implode(', ', $rule->days) }}
                                    @endif
                                </span>
                                <strong>Rp {{ number_format((int) $rule->price, 0, ',', '.') }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="u-price">
                Rp {{ number_format($lapangan->min_price, 0, ',', '.') }}
                <span class="u-meta">/ jam</span>
            </div>

        </div>

    </article>


    {{-- ========================================= --}}
    {{-- KARTU FORM BOOKING --}}
    {{-- ========================================= --}}
    <article class="u-card">

        <div class="u-card-body">

            <h3>{{ __('Booking lapangan') }}</h3>

            <p class="u-meta">
                {{ __('Pilih tanggal, waktu mulai, durasi, dan metode pembayaran. Jadwal akan diperiksa sebelum booking dibuat.') }}
            </p>

            @if(!$lapangan->is_bookable)
                <div style="padding:12px;border-radius:10px;background:#fff8e9;border:1px solid #f4e0b8;color:#855b16;font-size:13px;margin-bottom:12px">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ __('Lapangan ini sedang') }} {{ strtolower($lapangan->operational_status ?: 'tidak aktif') }} {{ __('dan belum dapat dipesan.') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('user.booking.store', $lapangan) }}"
                class="u-form">

                @csrf

                {{-- TANGGAL BOOKING --}}
                <div class="u-field full">
                    <label>{{ __('Tanggal bermain') }}</label>

                    <input
                        id="booking-date"
                        type="date"
                        name="booking_date"
                        min="{{ now()->toDateString() }}"
                        value="{{ old('booking_date', $bookingDraft['booking_date'] ?? now()->addDay()->toDateString()) }}"
                        required
                    >
                </div>


                {{-- JAM MULAI --}}
                <div class="u-field">
                    <label>{{ __('Jam mulai') }}</label>

                    <input
                        id="booking-start"
                        type="time"
                        name="start_time"
                        value="{{ old('start_time', $bookingDraft['start_time'] ?? '18:00') }}"
                        required
                    >
                </div>


                {{-- JAM SELESAI / DURASI --}}
                <div class="u-field">
                    <label>{{ __('Sampai Jam (Jam Selesai)') }}</label>

                    <select id="booking-end-time">
                        {{-- Opsi jam selesai diisi secara dinamis sesuai jam mulai --}}
                    </select>

                    <input type="hidden" id="booking-hours" name="hours" value="{{ old('hours', $bookingDraft['hours'] ?? 1) }}">

                    <small id="booking-end-display">
                        {{ __('Pilih jam mulai dan jam selesai bermain.') }}
                    </small>
                </div>


                {{-- KETERSEDIAAN JADWAL --}}
                <div class="u-field full">
                    <label>{{ __('Ketersediaan waktu') }}</label>

                    <div
                        id="availability-grid"
                        style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px">
                    </div>

                    <small style="display:flex;gap:14px;margin-top:8px;flex-wrap:wrap">
    <span><i style="color:#16a34a">●</i> {{ __('Tersedia') }}</span>
    <span><i style="color:#dc2626">●</i> {{ __('Sudah dipesan') }}</span>
    <span><i style="color:#9ca3af">●</i> {{ __('Waktu berlalu') }}</span>
</small>
                </div>


                {{-- METODE PEMBAYARAN --}}
                <div class="u-field full">
                    <label>{{ __('Metode pembayaran') }}</label>

                    <select name="payment_method" required>

                        <option
                            value="Midtrans"
                            @selected(old('payment_method', $bookingDraft['payment_method'] ?? 'Midtrans') === 'Midtrans')>
                            {{ __('Pembayaran Online melalui Midtrans') }}
                        </option>

                        <option
                            value="Cash"
                            @selected(old('payment_method') === 'Cash')>
                            {{ __('Cash / Bayar di Tempat') }}
                        </option>

                    </select>

                    <small>
                        Midtrans diproses secara online. Cash dibayar langsung
                        di venue dan baru dinyatakan lunas setelah Admin Venue
                        mengonfirmasi penerimaan uang.
                    </small>
                </div>


                {{-- TOMBOL BOOKING --}}
                <button
                    type="submit"
                    class="u-btn"
                    style="grid-column:1/-1"
                    @disabled(!$lapangan->is_bookable)>
                    {{ __('Booking & Bayar Sekarang') }}
                </button>

            </form>

        </div>

    </article>

</div>

{{-- ========================================= --}}
{{-- ULASAN & KOMENTAR PENGGUNA --}}
{{-- ========================================= --}}
@php
    $reviews = $lapangan->reviews ?? collect();
    $avgRating = $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : round((float) ($lapangan->reviews_avg_rating ?? $lapangan->rating ?? 0), 1);
@endphp

<div class="u-card" style="margin-top:28px; padding:24px 28px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px; border-bottom:1px solid #e5ede7; padding-bottom:16px;">
        <div>
            <h3 style="margin:0 0 6px; font-size:20px; color:#143e26; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-comments" style="color:#168653;"></i>
                {{ __('Ulasan & Penilaian Pemain') }}
            </h3>
            <p style="margin:0; color:#62796e; font-size:13px;">
                Pengalaman nyata dari para pemain yang pernah menyewa {{ $lapangan->nama }}.
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:12px; background:#f0faf3; border:1px solid #cce8d5; border-radius:14px; padding:10px 18px;">
            <div style="font-size:28px; font-weight:850; color:#14532d; line-height:1;">
                ★ {{ number_format($avgRating, 1) }}
            </div>
            <div style="font-size:12px; color:#476554;">
                <div style="color:#f59e0b; font-size:13px;">
                    @for($s = 1; $s <= 5; $s++)
                        <i class="fa-{{ $s <= round($avgRating) ? 'solid' : 'regular' }} fa-star"></i>
                    @endfor
                </div>
                <span>Berdasarkan {{ $reviews->count() }} ulasan</span>
            </div>
        </div>
    </div>

    @forelse($reviews as $rev)
        <div style="padding:16px 0; border-bottom:1px solid #f1f5f2; display:flex; gap:16px; align-items:flex-start;">
            <div style="width:44px; height:44px; border-radius:50%; background:#168653; color:#fff; display:grid; place-items:center; font-weight:750; font-size:16px; flex-shrink:0;">
                {{ strtoupper(substr($rev->user?->name ?? 'P', 0, 1)) }}
            </div>

            <div style="flex:1; min-width:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:4px;">
                    <strong style="color:#163e28; font-size:14px;">{{ $rev->user?->name ?? 'Pemain ArenaSports' }}</strong>
                    <small style="color:#8ca295; font-size:11px;">
                        <i class="fa-regular fa-clock"></i> {{ $rev->created_at?->diffForHumans() ?? '-' }}
                    </small>
                </div>

                <div style="color:#f59e0b; font-size:12px; margin-bottom:6px;">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa-{{ $i <= (int)$rev->rating ? 'solid' : 'regular' }} fa-star"></i>
                    @endfor
                    <span style="color:#506a5a; font-weight:700; margin-left:6px; font-size:11px;">({{ $rev->rating }}/5)</span>
                </div>

                <p style="margin:0 0 8px; color:#364e41; font-size:13px; line-height:1.6;">
                    {{ $rev->message }}
                </p>

                @if($rev->reply_message)
                    <div style="background:#f4faf6; border-left:3px solid #168653; padding:10px 14px; border-radius:8px; margin-top:8px;">
                        <strong style="color:#166534; font-size:12px; display:block; margin-bottom:3px;">
                            <i class="fa-solid fa-reply"></i> Tanggapan Pengelola Venue:
                        </strong>
                        <p style="margin:0; font-size:12px; color:#476554; line-height:1.5;">
                            {{ $rev->reply_message }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div style="text-align:center; padding:35px 20px; color:#789283;">
            <i class="fa-regular fa-comment-dots" style="font-size:36px; color:#a3b8ac; margin-bottom:10px; display:inline-block;"></i>
            <p style="font-size:14px; font-weight:600; margin:0 0 4px; color:#395043;">Belum ada ulasan untuk lapangan ini.</p>
            <small style="font-size:12px;">Jadilah yang pertama memberikan penilaian setelah selesai bermain!</small>
        </div>
    @endforelse
</div>

{{-- ========================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================= --}}
@push('scripts')

<script>


// ========================================
// 1. JAVASCRIPT KETERSEDIAAN JADWAL BOOKING
// ========================================

(() => {
    const dateInput = document.getElementById('booking-date');
    const startInput = document.getElementById('booking-start');
    const hoursInput = document.getElementById('booking-hours');
    const endTimeSelect = document.getElementById('booking-end-time');
    const grid = document.getElementById('availability-grid');
    const endDisplay = document.getElementById('booking-end-display');

    const submitButton = document.querySelector(
        '.u-form button[type="submit"]'
    );

    if (!dateInput || !startInput || !hoursInput || !endTimeSelect || !grid) {
        return;
    }

    const url = @json(route('user.courts.availability', $lapangan));
    const today = @json(now()->toDateString());

    let intervals = [];
    let opening = 7 * 60;
    let closing = 23 * 60;
    let loading = false;

    // Mengubah format HH:mm menjadi menit.
    function toMinutes(time) {
        if (!time) return 0;
        const [hour, minute] = time.substring(0, 5).split(':').map(Number);
        return hour * 60 + minute;
    }

    // Format menit ke string HH:mm
    function formatTime(minutes) {
        const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
        const minute = String(minutes % 60).padStart(2, '0');
        return `${hour}:${minute}`;
    }

    // Memeriksa apakah dua rentang waktu saling berbenturan.
    function isOverlapping(startA, endA, startB, endB) {
        return startA < endB && endA > startB;
    }

    // Memeriksa apakah satu slot benar-benar sudah dipesan.
    function isBooked(start, end) {
        return intervals.some(booking => {
            return isOverlapping(
                start,
                end,
                toMinutes(booking.start_time),
                toMinutes(booking.end_time)
            );
        });
    }

    // Memeriksa apakah waktu sudah berlalu.
    function isPast(start) {
        if (dateInput.value !== today) {
            return false;
        }

        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        return start <= currentMinutes;
    }

    // Perbarui opsi dropdown Jam Selesai berdasarkan Jam Mulai
    function updateEndTimeOptions() {
        if (!startInput.value) {
            endTimeSelect.innerHTML = '<option value="">Pilih jam mulai terlebih dahulu</option>';
            return;
        }

        const currentStartMin = toMinutes(startInput.value);
        const prevSelectedHours = Number(hoursInput.value) || 1;
        let selectedFound = false;

        endTimeSelect.innerHTML = '';

        for (let h = 1; h <= 8; h++) {
            const endMin = currentStartMin + h * 60;
            if (endMin > closing) break;

            const timeStr = formatTime(endMin);
            const opt = document.createElement('option');
            opt.value = h;
            opt.textContent = `${timeStr} (${h} jam bermain)`;

            if (h === prevSelectedHours) {
                opt.selected = true;
                selectedFound = true;
            }

            endTimeSelect.appendChild(opt);
        }

        if (!selectedFound && endTimeSelect.options.length > 0) {
            endTimeSelect.options[0].selected = true;
            hoursInput.value = endTimeSelect.options[0].value;
        } else if (selectedFound) {
            hoursInput.value = prevSelectedHours;
        }
    }

    // Memeriksa apakah rentang booking yang dipilih valid.
    function validateSelection() {
        if (!startInput.value) {
            return {
                valid: false,
                message: 'Silakan pilih jam mulai.'
            };
        }

        const start = toMinutes(startInput.value);
        const end = start + Number(hoursInput.value) * 60;

        if (start < opening || start >= closing) {
            return {
                valid: false,
                message: 'Jam mulai harus berada pada pukul 07.00–22.00.'
            };
        }

        if (end > closing) {
            return {
                valid: false,
                message: 'Jam selesai melewati batas operasional pukul 23.00.'
            };
        }

        if (isPast(start)) {
            return {
                valid: false,
                message: 'Jam mulai tersebut sudah berlalu.'
            };
        }

        const conflict = intervals.some(booking => {
            return isOverlapping(
                start,
                end,
                toMinutes(booking.start_time),
                toMinutes(booking.end_time)
            );
        });

        if (conflict) {
            return {
                valid: false,
                message: 'Rentang waktu yang dipilih berbenturan dengan jadwal booking lain.'
            };
        }

        return {
            valid: true,
            message: 'Jadwal tersedia.'
        };
    }

    // Membuat tampilan jadwal per jam.
    function render() {
        grid.innerHTML = '';

        const currentStartMin = startInput.value ? toMinutes(startInput.value) : -1;
        const currentEndMin = currentStartMin >= 0 ? currentStartMin + (Number(hoursInput.value) || 1) * 60 : -1;

        for (let start = opening; start < closing; start += 60) {
            const end = start + 60;
            const time = formatTime(start);

            const booked = isBooked(start, end);
            const past = isPast(start);
            const isSelectedRange = currentStartMin >= 0 && start >= currentStartMin && start < currentEndMin;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = time;

            let borderColor;
            let backgroundColor;
            let textColor;

            if (booked) {
                borderColor = '#dc2626';
                backgroundColor = '#fee2e2';
                textColor = '#991b1b';
                btn.disabled = true;
                btn.title = 'Waktu ini sudah dipesan';
            } else if (past) {
                borderColor = '#d1d5db';
                backgroundColor = '#f3f4f6';
                textColor = '#9ca3af';
                btn.disabled = true;
                btn.title = 'Waktu sudah berlalu';
            } else if (isSelectedRange) {
                borderColor = '#15803d';
                backgroundColor = '#bbf7d0';
                textColor = '#14532d';
                btn.disabled = false;
                btn.title = 'Termasuk dalam jam bermain yang kamu pilih';
            } else {
                borderColor = '#16a34a';
                backgroundColor = '#dcfce7';
                textColor = '#166534';
                btn.disabled = false;
                btn.title = 'Klik untuk pilih jam mulai';
            }

            btn.style.cssText = `
                padding: 9px 5px;
                border: 1px solid ${borderColor};
                border-radius: 9px;
                background: ${backgroundColor};
                color: ${textColor};
                cursor: ${btn.disabled ? 'not-allowed' : 'pointer'};
                font-weight: 700;
                transition: all .2s ease;
            `;

            if (!btn.disabled) {
                btn.addEventListener('click', () => {
                    startInput.value = time;
                    updateEndTimeOptions();
                    render();
                    updateEnd();
                });
            }

            if (startInput.value === time) {
                btn.style.outline = '2px solid #14532d';
                btn.style.outlineOffset = '2px';
            }

            grid.appendChild(btn);
        }
    }

    // Menghitung jam selesai dan memvalidasi durasi booking.
    function updateEnd() {
        if (!startInput.value) {
            endDisplay.textContent = 'Silakan pilih jam mulai.';
            if (submitButton) submitButton.disabled = loading;
            return;
        }

        const start = toMinutes(startInput.value);
        const end = start + Number(hoursInput.value) * 60;
        const endTimeStr = formatTime(end);

        const result = validateSelection();

        if (result.valid) {
            endDisplay.textContent = `Bermain dari jam ${startInput.value} sampai ${endTimeStr} (${hoursInput.value} jam) — ${result.message}`;
            endDisplay.style.color = '#15803d';
        } else {
            endDisplay.textContent = `Bermain dari jam ${startInput.value} sampai ${endTimeStr} (${hoursInput.value} jam) — ${result.message}`;
            endDisplay.style.color = '#dc2626';
        }

        if (submitButton) {
            submitButton.disabled = loading || !result.valid;
        }
    }

    // Mengambil jadwal booking dari server.
    async function load() {
        loading = true;

        if (submitButton) {
            submitButton.disabled = true;
        }

        grid.innerHTML = '<small>Memuat jadwal...</small>';

        try {
            const response = await fetch(
                url + '?date=' + encodeURIComponent(dateInput.value),
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error('Gagal mengambil jadwal.');
            }

            const data = await response.json();

            intervals = data.bookings || [];
            opening = toMinutes(data.opening || '07:00');
            closing = toMinutes(data.closing || '23:00');

            loading = false;

            updateEndTimeOptions();
            render();
            updateEnd();

        } catch (error) {
            intervals = [];
            loading = true;

            grid.innerHTML =
                '<small style="color:#dc2626">Jadwal gagal dimuat. Silakan ubah tanggal atau muat ulang halaman.</small>';

            endDisplay.textContent =
                'Booking tidak dapat dilakukan karena jadwal gagal dimuat.';

            if (submitButton) {
                submitButton.disabled = true;
            }
        }
    }

    // Event ketika tanggal berubah.
    dateInput.addEventListener('change', () => {
        load();
    });

    // Event ketika jam selesai (dropdown) diubah oleh user.
    endTimeSelect.addEventListener('change', () => {
        hoursInput.value = endTimeSelect.value;
        render();
        updateEnd();
    });

    // Event ketika jam mulai diubah secara manual.
    startInput.addEventListener('change', () => {
        updateEndTimeOptions();
        render();
        updateEnd();
    });

    // Memuat jadwal pertama kali.
    load();
})();



// ========================================
// 2. JAVASCRIPT SLIDER FOTO LAPANGAN
// ========================================

document.querySelectorAll('[data-user-slider]').forEach((slider) => {

    const slides = [
        ...slider.querySelectorAll('.u-gallery-slide')
    ];

    const dots = [
        ...slider.querySelectorAll('[data-user-dot]')
    ];

    const current = slider.querySelector('[data-user-current]');

    const prev = slider.querySelector('[data-user-prev]');

    const next = slider.querySelector('[data-user-next]');

    // Tidak menjalankan slider jika foto hanya satu
    if (slides.length <= 1) {
        return;
    }

    let index = 0;

    let autoSlide;


    // Menampilkan foto berdasarkan index
    function showSlide(position) {

        index = (position + slides.length) % slides.length;

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


    // Slider otomatis setiap 4 detik
    function startAutoSlide() {

        clearInterval(autoSlide);

        autoSlide = setInterval(() => {

            showSlide(index + 1);

        }, 4000);

    }


    // Mengulang timer otomatis
    function resetAutoSlide() {

        startAutoSlide();

    }


    // Tombol foto sebelumnya
    prev?.addEventListener('click', () => {

        showSlide(index - 1);

        resetAutoSlide();

    });


    // Tombol foto berikutnya
    next?.addEventListener('click', () => {

        showSlide(index + 1);

        resetAutoSlide();

    });


    // Tombol dots
    dots.forEach((dot, i) => {

        dot.addEventListener('click', () => {

            showSlide(i);

            resetAutoSlide();

        });

    });


    // Berhenti otomatis ketika mouse berada di galeri
    slider.addEventListener('mouseenter', () => {

        clearInterval(autoSlide);

    });


    // Jalankan kembali ketika mouse keluar
    slider.addEventListener('mouseleave', startAutoSlide);


    // Tampilkan foto pertama
    showSlide(0);

    // Jalankan autoplay
    startAutoSlide();

});

</script>

@endpush

@endsection
