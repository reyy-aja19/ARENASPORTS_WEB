@extends('layouts.user')

@section('content')
<style>
.om-page{position:relative}
.om-page:before{content:"";position:fixed;right:-100px;top:160px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,#d8f4e2 0,rgba(216,244,226,0) 70%);pointer-events:none;z-index:-1}
.om-page .u-panel{border-color:#dcece1;box-shadow:0 10px 28px rgba(13,81,55,.06)}
.om-page .u-card{border-radius:18px;border-color:#dcece1;box-shadow:0 8px 22px rgba(13,81,55,.06);transition:transform .2s,box-shadow .2s}
.om-page .u-card:hover{transform:translateY(-3px);box-shadow:0 14px 28px rgba(13,81,55,.11)}
.om-detail{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin:14px 0}
.om-detail span{display:flex;gap:8px;align-items:flex-start;padding:10px;border-radius:10px;background:#f0f8f2;color:#526b5c;font-size:12px;line-height:1.45}
.om-detail i{color:#168653;margin-top:2px}
.om-joined{background:#e5e7eb!important;color:#6b7280!important;cursor:not-allowed!important}
.om-full{background:#f3f4f6!important;color:#6b7280!important;cursor:not-allowed!important}
@media(max-width:600px){.om-detail{grid-template-columns:1fr}}
</style>
<div class="om-page">

{{-- EVENT ARENASPORTS --}}
<div class="panel-card" style="margin-bottom:24px">
    <div class="panel-title">Event ArenaSports</div>

    @forelse ($events as $event)
        <article style="padding:18px;margin-top:14px;border:1px solid #e0ece3;border-radius:12px">
            <h3>{{ $event->title }}</h3>

            <p><strong>Jenis:</strong> {{ $event->jenis }}</p>

            <p>
                <strong>Tanggal:</strong>
                {{ \Illuminate\Support\Carbon::parse($event->tanggal)->format('d/m/Y') }}
            </p>

            <p>
                <strong>Waktu:</strong>
                {{ $event->start_time ?: 'Belum ditentukan' }}
                @if ($event->end_time)
                    – {{ $event->end_time }}
                @endif
            </p>

            <p><strong>Lokasi:</strong> {{ $event->lokasi }}</p>
            <p><strong>Kuota:</strong> {{ $event->kuota }} peserta</p>

            @if ($event->deskripsi)
                <p>{{ $event->deskripsi }}</p>
            @endif
        </article>
    @empty
        <p>Belum ada Event yang dipublikasikan.</p>
    @endforelse
</div>

{{-- JUDUL HALAMAN --}}
<div class="u-section-head">
    <div>
        <h2>Open Match &amp; Event</h2>
        <p>
            Open Match dibuat oleh pengguna.
            Event resmi diterbitkan oleh Admin/Superadmin.
        </p>
    </div>
</div>

{{-- KOTAK HIJAU UNTUK MEMBUKA FORM --}}
<div class="u-panel" style="margin-bottom:24px">

    <button
        type="button"
        id="toggleOpenMatch"
        aria-expanded="false"
        aria-controls="formOpenMatch"
        style="
            width:100%;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:18px 20px;
            border:1px solid #bbf7d0;
            border-radius:12px;
            background:#dcfce7;
            color:#166534;
            cursor:pointer;
            text-align:left;
            transition:background .2s ease;
        "
        onmouseover="this.style.background='#bbf7d0'"
        onmouseout="this.style.background='#dcfce7'"
    >
        <span style="display:flex;align-items:center;gap:14px">
            <span
                style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    width:44px;
                    height:44px;
                    border-radius:10px;
                    background:#16a34a;
                    color:white;
                    font-size:25px;
                    flex-shrink:0;
                "
            >+</span>

            <span>
                <strong style="display:block;font-size:16px">
                    Buat Open Match
                </strong>

                <small style="display:block;margin-top:5px;color:#15803d">
                    Klik di sini untuk membuat pertandingan baru.
                </small>
            </span>
        </span>

        <span
            id="iconOpenMatch"
            style="font-size:22px;flex-shrink:0"
        >⌄</span>
    </button>

    {{-- FORM TERSEMBUNYI SECARA DEFAULT --}}
    <div id="formOpenMatch" hidden style="padding:22px 4px 4px">

        <h3 style="margin-top:0">Form Pembuatan Open Match</h3>

        <p class="u-meta">
            Pilih booking aktif milik Anda yang berstatus DP/Lunas
            dan belum memiliki Open Match.
        </p>

        @if ($eligibleBookings->isEmpty())

            <div
                style="
                    padding:14px 16px;
                    background:#fef9c3;
                    border:1px solid #fde68a;
                    border-radius:10px;
                    color:#854d0e;
                "
            >
                <strong>Belum ada booking yang memenuhi syarat.</strong>
                <p style="margin:6px 0 0">
                    Buat booking lapangan terlebih dahulu dan pastikan
                    status booking sudah DP atau Lunas.
                </p>
            </div>

        @else

            <form
                method="POST"
                action="{{ route('user.openmatches.store') }}"
                class="u-form"
            >
                @csrf

                <div class="u-field full">
                    <label for="booking_id">Booking milik Anda</label>

                    <select name="booking_id" id="booking_id" required>
                        <option value="">-- Pilih booking lapangan --</option>

                        @foreach ($eligibleBookings as $booking)
                            <option
                                value="{{ $booking->id }}"
                                {{ old('booking_id') == $booking->id ? 'selected' : '' }}
                            >
                                {{ $booking->lapangan?->nama }}
                                — {{ $booking->booking_date?->format('d M Y') }}
                                {{ $booking->start_time }}–{{ $booking->end_time }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="u-field full">
                    <label for="title">Judul pertandingan</label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="255"
                        value="{{ old('title') }}"
                        required
                        placeholder="Contoh: Cari lawan futsal malam ini"
                    >
                </div>

                <div class="u-field">
                    <label for="jumlah_pemain">Total pemain</label>

                    <input
                        type="number"
                        id="jumlah_pemain"
                        name="jumlah_pemain"
                        min="2"
                        max="99"
                        value="{{ old('jumlah_pemain', 10) }}"
                        required
                    >
                </div>

                <div class="u-field full">
                    <label for="deskripsi">Deskripsi (opsional)</label>

                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        maxlength="1500"
                        placeholder="Level permainan, biaya patungan, aturan..."
                    >{{ old('deskripsi') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="u-btn"
                    style="grid-column:1/-1"
                >
                    Publikasikan Open Match
                </button>
            </form>

        @endif
    </div>
</div>

{{-- DAFTAR OPEN MATCH DARI PENGGUNA --}}
<h3>Open Match dari pengguna</h3>

<div class="u-cards">
    @forelse ($matches as $match)

        <article class="u-card">
            <div class="u-card-body">

                <div class="u-row">
                    <span class="u-tag">{{ $match->jenis }}</span>

                    <span class="u-tag gold">
                        {{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }}
                    </span>
                </div>

                <h3>{{ $match->title }}</h3>

                <p class="u-meta">
                    {{ $match->tanggal?->format('d M Y') }}
                    · {{ $match->start_time }}–{{ $match->end_time }}
                </p>

                <p class="u-meta">
                    {{ $match->booking?->lapangan?->nama }}
                    — {{ $match->booking?->lapangan?->lokasi }}
                </p>

                @if ($match->deskripsi)
                    <p>{{ $match->deskripsi }}</p>
                @endif

                @php
                    $currentUid = (int) (session('admin_user_id') ?? 0);
                    $hostUid = (int) ($match->booking?->user_id ?? 0);
                    $isMatchMember = $currentUid > 0 && ($hostUid === $currentUid || \Illuminate\Support\Facades\DB::table('open_match_participants')->where('open_match_id', $match->id)->where('user_id', $currentUid)->exists());
                    $isFull = $match->status === 'Penuh' || $match->jumlah_bergabung >= $match->jumlah_pemain;
                    $venueName = $match->booking?->lapangan?->venue?->name;
                    $venueAddress = $match->booking?->lapangan?->venue?->address ?: $match->booking?->lapangan?->lokasi;
                @endphp
                <div class="om-detail">
                    <span><i class="fa-solid fa-calendar-days"></i> {{ $match->tanggal?->format('d M Y') ?? 'Tanggal belum tersedia' }}</span>
                    <span><i class="fa-regular fa-clock"></i> {{ $match->start_time }}–{{ $match->end_time }}</span>
                    <span><i class="fa-solid fa-location-dot"></i> {{ $venueAddress ?: 'Lokasi belum tersedia' }}</span>
                    <span><i class="fa-solid fa-building"></i> {{ $venueName ?: 'Venue belum terdaftar' }}</span>
                    <span><i class="fa-solid fa-futbol"></i> {{ $match->jenis ?: 'Olahraga' }} · {{ $match->booking?->lapangan?->nama ?? 'Lapangan' }}</span>
                    <span><i class="fa-solid fa-users"></i> {{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }} pemain bergabung</span>
                </div>

                @if($isMatchMember)
                    <button type="button" class="u-btn om-joined" disabled><i class="fa-solid fa-check"></i> Sudah Bergabung</button>
                @elseif($isFull)
                    <button type="button" class="u-btn om-full" disabled><i class="fa-solid fa-lock"></i> Pemain Sudah Penuh</button>
                @else
                    <form method="POST" action="{{ route('user.openmatches.join', $match) }}">
                        @csrf
                        <button type="submit" class="u-btn"><i class="fa-solid fa-user-plus"></i> Gabung Open Match</button>
                    </form>
                @endif

                @if ($isMatchMember)
                    <a href="{{ route('user.openmatches.chat', $match) }}" class="u-btn" style="display:inline-flex;margin-top:10px;text-decoration:none;background:#14532d">
                        <i class="fa-solid fa-comments"></i> Obrolan Pemain
                    </a>
                @endif

            </div>
        </article>

    @empty

        <div class="u-panel u-empty">
            Belum ada Open Match yang tersedia.
        </div>

    @endforelse
</div>

{{-- EVENT RESMI --}}
<h3 style="margin-top:28px">Event resmi ArenaSports</h3>

<div class="u-cards">
    @forelse ($events as $event)

        <article class="u-card">
            <div class="u-card-body">

                <span class="u-tag">{{ $event->jenis }}</span>

                <h3>{{ $event->title }}</h3>

                <p class="u-meta">
                    {{ $event->tanggal?->format('d M Y') }}
                    · {{ $event->start_time ?? 'Waktu menyusul' }}
                </p>

                <p class="u-meta">
                    <i class="fa-solid fa-location-dot"></i>
                    {{ $event->lokasi }}
                </p>

                @if ($event->deskripsi)
                    <p>{{ $event->deskripsi }}</p>
                @endif

                <span class="u-meta">
                    Kuota {{ $event->kuota }} peserta ·
                    Hubungi penyelenggara untuk konfirmasi keikutsertaan.
                </span>

            </div>
        </article>

    @empty

        <div class="u-panel u-empty">
            Belum ada event resmi yang dipublikasikan Admin/Superadmin.
        </div>

    @endforelse
</div>

{{-- INTERAKSI BUKA/TUTUP FORM --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('toggleOpenMatch');
    const form = document.getElementById('formOpenMatch');
    const icon = document.getElementById('iconOpenMatch');

    if (!toggle || !form || !icon) {
        return;
    }

    toggle.addEventListener('click', function () {
        const isOpening = form.hidden;

        form.hidden = !isOpening;
        toggle.setAttribute('aria-expanded', String(isOpening));
        icon.textContent = isOpening ? '⌃' : '⌄';
    });

    // Jika validasi Laravel gagal, buka kembali form secara otomatis.
    @if ($errors->any())
        form.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        icon.textContent = '⌃';
    @endif
});
</script>

</div>
@endsection