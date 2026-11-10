
@extends('layouts.user')

@section('content')

{{-- HEADER HALAMAN --}}
<div class="u-section-head">
    <div>
        <h2>{{ $lapangan->nama }}</h2>
        <p>Detail venue sebelum melakukan pemesanan.</p>
    </div>

    <a class="u-btn ghost"
       href="{{ $lapangan->venue ? route('user.venues.show', $lapangan->venue) : route('user.venues') }}">
        Kembali ke venue
    </a>
</div>

{{-- PROSES FOTO LAPANGAN --}}
@php
    $gallery = $lapangan->foto_gallery ?? [];

    // Mendukung gallery dalam bentuk JSON maupun array
    if (is_string($gallery)) {
        $gallery = json_decode($gallery, true) ?? [];
    }

    // Menggabungkan foto utama dan foto gallery
    $photos = collect(array_merge(
        [$lapangan->foto],
        is_array($gallery) ? $gallery : []
    ))
    ->filter(fn ($photo) => is_string($photo) && trim($photo) !== '')
    ->unique()
    ->values();

    // Membuat URL foto sesuai lokasi penyimpanan
    $courtPhotos = $photos->map(function ($photo) {
        $photo = trim($photo);

        // Jika foto menggunakan URL eksternal
        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }

        $path = ltrim($photo, '/');

        // Jika path sudah menggunakan storage/
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // Jika foto tersimpan di storage/app/public
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        }

        // Jika foto tersimpan di folder public
        return asset($path);
    })->values();
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
                        <span>Foto lapangan belum tersedia</span>
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

                <span class="u-meta">
                    ★ {{ number_format($lapangan->rating, 1) }}
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
        Lapangan ini berada di venue
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

        <span style="color:#64748b">
            Venue belum tersedia
        </span>

    @endif

</div>

<p class="u-meta">
    <i class="fa-solid fa-location-dot"></i>
    {{ $lapangan->lokasi }}
</p>

            <p>
                {{ $lapangan->deskripsi ?: 'Belum ada deskripsi untuk lapangan ini.' }}
            </p>

            @if($lapangan->fasilitas)
                <p><strong>Fasilitas</strong></p>

                <p>
                    {{ implode(', ', (array) $lapangan->fasilitas) }}
                </p>
            @endif

            <div class="u-price">
                Rp {{ number_format($lapangan->harga, 0, ',', '.') }}
                <span class="u-meta">/ jam</span>
            </div>

        </div>

    </article>


    {{-- ========================================= --}}
    {{-- KARTU FORM BOOKING --}}
    {{-- ========================================= --}}
    <article class="u-card">

        <div class="u-card-body">

            <h3>Booking lapangan</h3>

            <p class="u-meta">
                Pilih tanggal, waktu mulai, durasi, dan metode pembayaran.
                Jadwal akan diperiksa sebelum booking dibuat.
            </p>

            <form
                method="POST"
                action="{{ route('user.booking.store', $lapangan) }}"
                class="u-form">

                @csrf

                {{-- TANGGAL BOOKING --}}
                <div class="u-field full">
                    <label>Tanggal bermain</label>

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
                    <label>Jam mulai</label>

                    <input
                        id="booking-start"
                        type="time"
                        name="start_time"
                        value="{{ old('start_time', $bookingDraft['start_time'] ?? '18:00') }}"
                        required
                    >
                </div>


                {{-- DURASI --}}
                <div class="u-field">
                    <label>Durasi</label>

                    <select id="booking-hours" name="hours">
                        @for($i = 1; $i <= 8; $i++)
                            <option
                                value="{{ $i }}"
                                @selected(old('hours', $bookingDraft['hours'] ?? 1) == $i)>
                                {{ $i }} jam
                            </option>
                        @endfor
                    </select>

                    <small id="booking-end-display">
                        Jam selesai dihitung otomatis.
                    </small>
                </div>


                {{-- KETERSEDIAAN JADWAL --}}
                <div class="u-field full">
                    <label>Ketersediaan waktu</label>

                    <div
                        id="availability-grid"
                        style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px">
                    </div>

                    <small style="display:flex;gap:14px;margin-top:8px">
                        <span>
                            <i style="color:#16a34a">●</i>
                            Tersedia
                        </span>

                        <span>
                            <i style="color:#dc2626">●</i>
                            Sudah dipesan
                        </span>
                    </small>
                </div>


                {{-- METODE PEMBAYARAN --}}
                <div class="u-field full">
                    <label>Metode pembayaran</label>

                    <select name="payment_method" required>

                        <option
                            value="Midtrans"
                            @selected(old('payment_method', $bookingDraft['payment_method'] ?? 'Midtrans') === 'Midtrans')>
                            Pembayaran Online melalui Midtrans
                        </option>

                        <option
                            value="Cash"
                            @selected(old('payment_method') === 'Cash')>
                            Cash / Bayar di Tempat
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
                    style="grid-column:1/-1">
                    Booking &amp; Bayar Sekarang
                </button>

            </form>

        </div>

    </article>

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
    const grid = document.getElementById('availability-grid');
    const endDisplay = document.getElementById('booking-end-display');

    const submitButton = document.querySelector(
        '.u-form button[type=submit]'
    );

    if (!dateInput || !startInput || !hoursInput || !grid) {
        return;
    }

    const url = @json(route('user.courts.availability', $lapangan));
    const today = @json(now()->toDateString());

    let intervals = [];

    // Mengubah jam menjadi menit
    const toMinutes = (time) => {
        const [h, m] = time.split(':').map(Number);
        return h * 60 + m;
    };


    // Menampilkan jadwal tersedia dan sudah dipesan
    function render() {

        grid.innerHTML = '';

        for (let h = 0; h < 23; h++) {

            const time = String(h).padStart(2, '0') + ':00';

            const begin = h * 60;

            const end = begin + (Number(hoursInput.value) * 60);

            const past =
                dateInput.value === today &&
                begin <= new Date().getHours() * 60 + new Date().getMinutes();

            const conflict =
                end >= 24 * 60 ||
                past ||
                intervals.some(x =>
                    begin < toMinutes(x.end_time) &&
                    end > toMinutes(x.start_time)
                );

            const btn = document.createElement('button');

            btn.type = 'button';
            btn.textContent = time;

            btn.style.cssText =
                'padding:9px 5px;' +
                'border:1px solid ' + (conflict ? '#dc2626' : '#16a34a') + ';' +
                'border-radius:9px;' +
                'background:' + (conflict ? '#fee2e2' : '#dcfce7') + ';' +
                'color:' + (conflict ? '#991b1b' : '#166534') + ';' +
                'cursor:' + (conflict ? 'not-allowed' : 'pointer');

            btn.disabled = conflict;

            if (!conflict) {

                btn.addEventListener('click', () => {

                    startInput.value = time;

                    updateEnd();

                    grid.querySelectorAll('button').forEach(b => {
                        b.style.outline = 'none';
                    });

                    btn.style.outline = '2px solid #14532d';

                });

            }

            grid.appendChild(btn);

        }

    }


    // Menghitung jam selesai
    function updateEnd() {

        if (!startInput.value) return;

        const end =
            toMinutes(startInput.value) +
            Number(hoursInput.value) * 60;

        const invalid =
            end >= 24 * 60 ||
            intervals.some(x =>
                toMinutes(startInput.value) < toMinutes(x.end_time) &&
                end > toMinutes(x.start_time)
            );

        if (endDisplay) {

            endDisplay.textContent =
                end >= 24 * 60
                    ? 'Jam selesai melewati tengah malam. Pilih jam mulai atau durasi lain.'
                    : 'Jam selesai otomatis: ' +
                        String(Math.floor(end / 60)).padStart(2, '0') +
                        ':' +
                        String(end % 60).padStart(2, '0');

        }

        if (submitButton) {
            submitButton.disabled = invalid;
        }

    }


    // Mengambil data jadwal dari Laravel
    async function load() {

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
                throw new Error('Gagal mengambil jadwal');
            }

            const data = await response.json();

            intervals = data.bookings || [];

            render();

            updateEnd();

        } catch (error) {

            grid.innerHTML =
                '<small>Jadwal tidak dapat dimuat. Silakan muat ulang halaman.</small>';

        }

    }


    // Event perubahan tanggal
    dateInput.addEventListener('change', load);


    // Event perubahan durasi
    hoursInput.addEventListener('change', () => {

        render();

        updateEnd();

    });


    // Event perubahan jam mulai
    startInput.addEventListener('change', () => {

        const chosen = toMinutes(startInput.value);

        const end = chosen + Number(hoursInput.value) * 60;

        const conflict =
            end >= 24 * 60 ||
            intervals.some(x =>
                chosen < toMinutes(x.end_time) &&
                end > toMinutes(x.start_time)
            );

        if (conflict) {

            alert(
                'Rentang waktu tersebut sudah dipesan atau melewati tengah malam. Pilih waktu lain.'
            );

            startInput.value = '';

        }

        updateEnd();

    });


    // Muat jadwal pertama kali
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
