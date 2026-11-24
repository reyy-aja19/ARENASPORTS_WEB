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

.om-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border:1px solid #bbf7d0;border-radius:14px;background:linear-gradient(135deg,#dcfce7,#f0fdf4);color:#166534;cursor:pointer;text-align:left;transition:background .2s ease}
.om-toggle:hover{background:#bbf7d0}
.om-toggle-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:#16a34a;color:#fff;font-size:24px;flex-shrink:0}
.om-form-body{padding:24px 4px 4px}

.om-steps{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 20px}
.om-step{display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border-radius:999px;background:#f1f7f3;border:1px solid #dbeae1;font-size:11px;font-weight:700;color:#41695a}
.om-step b{display:inline-grid;place-items:center;width:19px;height:19px;border-radius:50%;background:#168653;color:#fff;font-size:10px}

.om-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.om-form-grid .full{grid-column:1/-1}
.om-form-grid label{display:block;font-size:12px;font-weight:700;color:#3d5a4c;margin-bottom:7px}
.om-form-grid input,.om-form-grid select,.om-form-grid textarea{width:100%;box-sizing:border-box;border:1px solid #d7e2da;border-radius:10px;padding:11px 12px;background:#fff;font:inherit}
.om-form-grid textarea{min-height:88px;resize:vertical}
.om-form-grid small{display:block;margin-top:6px;color:#7b8c83;font-size:11px}

.om-preview{margin-top:14px;padding:15px;border-radius:14px;border:1px dashed #c7ddd2;background:#f8fcfa;display:grid;grid-template-columns:1fr 1fr;gap:10px}
.om-preview[hidden]{display:none}
.om-preview div{font-size:12px;color:#4c6557}
.om-preview strong{display:block;color:#1c3d2d;font-size:13px}
.om-preview .full{grid-column:1/-1}

.om-cost-box{padding:14px;border-radius:12px;background:#f4faf6;border:1px solid #dcebe2}
.om-mode{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
.om-mode label{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid #d7e2da;border-radius:999px;background:#fff;font-size:12px;font-weight:700;cursor:pointer;margin:0}
.om-mode input{width:auto;margin:0}
.om-cost-input[hidden]{display:none}

.om-more{margin-top:14px;border-top:1px solid #e7efeb;padding-top:14px}
.om-more summary{cursor:pointer;font-weight:700;color:#146c43;font-size:13px;list-style:none;display:inline-flex;align-items:center;gap:7px}
.om-more summary::-webkit-details-marker{display:none}
.om-more summary:before{content:"\f0d7";font-family:"Font Awesome 6 Free";font-weight:900}
.om-more[open] summary:before{content:"\f0d8"}
.om-map{margin-top:14px;border-radius:14px;overflow:hidden;border:1px solid #dbe7e1;background:#eef4f1}
.om-map iframe{display:block;width:100%;height:230px;border:0}
.om-map-actions{display:flex;flex-wrap:wrap;gap:8px;padding:10px}
.om-map-actions a{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:9px;background:#fff;border:1px solid #d7e2da;color:#146c43;font-size:12px;font-weight:700;text-decoration:none}
.om-map-actions a.wa{background:#25d366;border-color:#25d366;color:#0b3d1c}

.om-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.03em}
.om-badge.free{background:#e4f7ed;color:#167348}
.om-badge.paid{background:#fff2d6;color:#8b5b00}
.om-badge.level{background:#eef6ff;color:#2b5c8a}
.om-badge.open{background:#e4f7ed;color:#167348}

.ev-flow{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:20px}
.ev-flow div{padding:13px;border-radius:12px;background:#fff;border:1px solid #e1ebe5;font-size:11px;color:#5a7264;line-height:1.55}
.ev-flow b{display:inline-grid;place-items:center;width:20px;height:20px;border-radius:50%;background:#168653;color:#fff;font-size:10px;margin-bottom:7px}
.ev-flow strong{display:block;color:#1c3d2d;font-size:12px;margin-bottom:3px}

.ev-registered{padding:11px 13px;border-radius:10px;background:#e9f9f1;border:1px solid #c8ead9;color:#156b45;font-size:12px;line-height:1.6;margin-bottom:12px}
.ev-form{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
.ev-form .full{grid-column:1/-1}
.ev-form label{display:block;font-size:12px;font-weight:700;color:#3d5a4c;margin-bottom:6px}
.ev-form input,.ev-form textarea{width:100%;box-sizing:border-box;border:1px solid #d7e2da;border-radius:10px;padding:10px 11px;font:inherit}
.ev-form textarea{min-height:72px;resize:vertical}
.ev-check{display:flex;align-items:flex-start;gap:8px;font-size:11px;color:#5a7264;font-weight:500;line-height:1.55}
.ev-check input{width:auto;margin-top:2px}

.ev-success{margin-bottom:22px;padding:18px 20px;border-radius:16px;background:#e9f9f1;border:1px solid #c1e8d5;color:#115c3d}
.ev-success strong{display:block;font-size:15px;margin-bottom:6px}
.ev-success a{display:inline-flex;align-items:center;gap:7px;margin-top:10px;padding:10px 14px;border-radius:9px;background:#25d366;color:#0b3d1c;font-size:12px;font-weight:800;text-decoration:none}

@media(max-width:760px){.om-form-grid{grid-template-columns:1fr}.om-preview{grid-template-columns:1fr}.ev-flow{grid-template-columns:1fr 1fr}.ev-form{grid-template-columns:1fr}}
@media(max-width:600px){.om-detail{grid-template-columns:1fr}.ev-flow{grid-template-columns:1fr}}
</style>

<div class="om-page">

{{-- JUDUL HALAMAN --}}
<div class="u-section-head">
    <div>
        <h2>Open Match Komunitas</h2>
        <p>
            Cari kawan tanding dan sparing seru yang dibuat oleh komunitas pemain ArenaSports.
        </p>
    </div>
</div>

@if(session('event_registration_success'))
    @php $registered = session('event_registration_success'); @endphp
    <div class="ev-success" id="eventRegistrationSuccess">
        <strong><i class="fa-solid fa-circle-check"></i> Pendaftaran tim {{ $registered['team_name'] }} tercatat!</strong>
        Data tim untuk event "{{ $registered['event_title'] }}" sudah disimpan.
        Lanjutkan konfirmasi teknis dan pembayaran ke WhatsApp penyelenggara.
        @if($registered['wa_url'])
            <br>
            <a href="{{ $registered['wa_url'] }}" target="_blank" rel="noopener" id="eventWaLink">
                <i class="fa-brands fa-whatsapp"></i> Lanjut ke WhatsApp {{ $registered['organizer'] }}
            </a>
        @endif
    </div>
@endif

{{-- KOTAK HIJAU UNTUK MEMBUKA FORM --}}
<div class="u-panel" style="margin-bottom:24px">

    <button
        type="button"
        id="toggleOpenMatch"
        class="om-toggle"
        aria-expanded="false"
        aria-controls="formOpenMatch"
    >
        <span style="display:flex;align-items:center;gap:14px">
            <span class="om-toggle-icon">+</span>
            <span>
                <strong style="display:block;font-size:16px">Buat Open Match</strong>
                <small style="display:block;margin-top:5px;color:#15803d">
                    Pilih booking, atur biaya patungan, lalu publikasikan.
                </small>
            </span>
        </span>
        <span id="iconOpenMatch" style="font-size:22px;flex-shrink:0">⌄</span>
    </button>

    {{-- FORM TERSEMBUNYI SECARA DEFAULT --}}
    <div id="formOpenMatch" hidden class="om-form-body">

        <h3 style="margin-top:0">Form Pembuatan Open Match</h3>

        <p class="u-meta">
            Pilih booking aktif milik Anda yang berstatus DP/Lunas dan belum memiliki Open Match.
        </p>

        @if ($eligibleBookings->isEmpty())

            <div style="padding:14px 16px;background:#fef9c3;border:1px solid #fde68a;border-radius:10px;color:#854d0e">
                <strong>Belum ada booking yang memenuhi syarat.</strong>
                <p style="margin:6px 0 0">
                    Buat booking lapangan terlebih dahulu dan pastikan status booking sudah DP atau Lunas.
                </p>
            </div>

        @else

            <form method="POST" action="{{ route('user.openmatches.store') }}" id="openMatchForm">
                @csrf

                <div class="om-form-grid">
                    <div class="full">
                        <label for="booking_id">Booking milik Anda</label>
                        <select name="booking_id" id="booking_id" required>
                            <option value="">-- Pilih booking lapangan --</option>
                            @foreach ($eligibleBookings as $booking)
                                <option
                                    value="{{ $booking->id }}"
                                    data-title="{{ $booking->lapangan?->nama }}"
                                    data-venue="{{ $booking->lapangan?->venue?->name ?? 'Venue' }}"
                                    data-field="{{ $booking->lapangan?->nama ?? 'Lapangan' }}"
                                    data-address="{{ $booking->lapangan?->venue?->address ?: $booking->lapangan?->lokasi }}"
                                    data-sport="{{ $booking->lapangan?->jenis ?? 'Olahraga' }}"
                                    data-date="{{ $booking->booking_date?->translatedFormat('d M Y') }}"
                                    data-time="{{ substr((string) $booking->start_time, 0, 5) }}–{{ substr((string) $booking->end_time, 0, 5) }}"
                                    data-total="{{ (int) $booking->total_price }}"
                                    {{ old('booking_id') == $booking->id ? 'selected' : '' }}
                                >
                                    {{ $booking->lapangan?->nama }}
                                    — {{ $booking->booking_date?->format('d M Y') }}
                                    {{ substr((string) $booking->start_time, 0, 5) }}–{{ substr((string) $booking->end_time, 0, 5) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="om-preview" id="bookingPreview" hidden>
                    <div class="full"><small style="margin:0;color:#7b8c83">VENUE</small><strong id="previewVenue">-</strong></div>
                    <div><small style="margin:0;color:#7b8c83">LAPANGAN</small><strong id="previewField">-</strong></div>
                    <div><small style="margin:0;color:#7b8c83">CABANG</small><strong id="previewSport">-</strong></div>
                    <div><small style="margin:0;color:#7b8c83">JADWAL</small><strong id="previewSchedule">-</strong></div>
                    <div><small style="margin:0;color:#7b8c83">TOTAL BOOKING</small><strong id="previewTotal">-</strong></div>
                    <div class="full"><small style="margin:0;color:#7b8c83">LOKASI</small><strong id="previewAddress">-</strong></div>
                </div>

                <div class="om-form-grid" style="margin-top:16px">
                    <div class="full">
                        <label for="title">Judul pertandingan</label>
                        <input type="text" id="title" name="title" maxlength="255" value="{{ old('title') }}" required placeholder="Contoh: Cari lawan futsal malam ini">
                    </div>

                    <div>
                        <label for="jumlah_pemain">Target total pemain</label>
                        <input type="number" id="jumlah_pemain" name="jumlah_pemain" min="2" max="99" value="{{ old('jumlah_pemain', 10) }}" required>
                        <small>Termasuk kamu sebagai host.</small>
                    </div>

                    <div>
                        <label for="level">Level permainan</label>
                        <select id="level" name="level">
                            @foreach(['Santai', 'Pemula', 'Menengah', 'Mahir', 'Kompetitif'] as $level)
                                <option value="{{ $level }}" {{ old('level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="full">
                        <label for="contact_phone">Kontak WhatsApp host</label>
                        <input type="text" id="contact_phone" name="contact_phone" maxlength="30" value="{{ old('contact_phone', $user?->phone) }}" placeholder="08xxxxxxxxxx">
                        <small>Dipakai pemain untuk menghubungi host terkait match.</small>
                    </div>

                    <div class="full">
                        <label>Biaya bermain</label>
                        <div class="om-cost-box">
                            <div class="om-mode">
                                <label><input type="radio" name="biaya_mode" value="Gratis" {{ old('biaya_mode', 'Gratis') === 'Gratis' ? 'checked' : '' }}> Gratis</label>
                                <label><input type="radio" name="biaya_mode" value="Patungan" {{ old('biaya_mode') === 'Patungan' ? 'checked' : '' }}> Patungan</label>
                            </div>

                            <div class="om-cost-input" id="costInput" hidden>
                                <label for="biaya_per_pemain" style="font-size:12px;font-weight:700;color:#3d5a4c;margin-bottom:7px">Biaya patungan per pemain</label>
                                <input type="number" id="biaya_per_pemain" name="biaya_per_pemain" min="0" max="10000000" value="{{ old('biaya_per_pemain') }}" placeholder="0" style="width:100%;box-sizing:border-box;border:1px solid #d7e2da;border-radius:10px;padding:11px 12px;font:inherit">
                                <small id="costHint" style="display:block;margin-top:6px;color:#7b8c83;font-size:11px">Otomatis dihitung dari total booking dibagi target pemain.</small>
                            </div>
                        </div>
                    </div>

                    <div class="full">
                        <label for="deskripsi">Deskripsi & aturan (opsional)</label>
                        <textarea id="deskripsi" name="deskripsi" maxlength="1500" placeholder="Contoh: Pakai sepatu futsal, datang 15 menit sebelum mulai, biaya dibayar di venue...">{{ old('deskripsi') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="u-btn" style="margin-top:18px;width:100%">
                    <i class="fa-solid fa-bullhorn"></i> Publikasikan Open Match
                </button>
            </form>

        @endif
    </div>
</div>

{{-- DAFTAR OPEN MATCH DARI PENGGUNA --}}
<h3>Open Match dari pengguna</h3>

<div class="u-cards">
    @forelse ($matches as $match)

        @php
            $currentUid = (int) (session('admin_user_id') ?? 0);
            $currentUserRole = session('admin_role');
            $hostUid = (int) ($match->booking?->user_id ?? 0);
            $isParticipant = $currentUid > 0 && \Illuminate\Support\Facades\DB::table('open_match_participants')->where('open_match_id', $match->id)->where('user_id', $currentUid)->exists();
            $isCourtOwner = $currentUid > 0 && (
                (int) ($match->booking?->lapangan?->owner_admin_id ?? 0) === $currentUid ||
                (int) ($match->booking?->lapangan?->venue?->owner_admin_id ?? 0) === $currentUid ||
                ($currentUserRole === 'venue_admin' && empty($match->booking_id))
            );
            $isMatchMember = $currentUid > 0 && ($hostUid === $currentUid || $isParticipant);
            $canAccessChat = $isMatchMember || $isCourtOwner || $currentUserRole === 'superadmin';
            $isFull = $match->status === 'Penuh' || $match->jumlah_bergabung >= $match->jumlah_pemain;
            $venueName = $match->booking?->lapangan?->venue?->name;
            $venueAddress = $match->venue_address;
            $mapEmbed = $match->maps_embed_url;
            $mapsUrl = $match->maps_url;
        @endphp

        <article class="u-card">
            <div class="u-card-body">

                <div class="u-row">
                    <span class="u-tag">{{ $match->jenis }}</span>
                    <span class="u-tag gold">{{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }}</span>
                </div>

                <h3>{{ $match->title }}</h3>

                <p class="u-meta">
                    {{ $match->tanggal?->format('d M Y') }}
                    · {{ substr((string) $match->start_time, 0, 5) }}–{{ substr((string) $match->end_time, 0, 5) }}
                </p>

                <p class="u-meta">
                    {{ $match->booking?->lapangan?->nama }}
                    @if($venueName) — {{ $venueName }} @endif
                </p>

                <div style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0">
                    @if($match->is_free)
                        <span class="om-badge free"><i class="fa-solid fa-gift"></i> GRATIS</span>
                    @else
                        <span class="om-badge paid"><i class="fa-solid fa-wallet"></i> Rp {{ number_format((int) $match->biaya_per_pemain, 0, ',', '.') }} / pemain</span>
                    @endif
                    @if($match->level)
                        <span class="om-badge level"><i class="fa-solid fa-signal"></i> {{ $match->level }}</span>
                    @endif
                    <span class="om-badge open"><i class="fa-solid fa-users"></i> Sisa {{ max(0, (int) $match->jumlah_pemain - (int) $match->jumlah_bergabung) }} slot</span>
                </div>

                @if ($match->deskripsi)
                    <p>{{ $match->deskripsi }}</p>
                @endif

                <div class="om-detail">
                    <span><i class="fa-solid fa-calendar-days"></i> {{ $match->tanggal?->format('d M Y') ?? 'Tanggal belum tersedia' }}</span>
                    <span><i class="fa-regular fa-clock"></i> {{ substr((string) $match->start_time, 0, 5) }}–{{ substr((string) $match->end_time, 0, 5) }}</span>
                    <span><i class="fa-solid fa-location-dot"></i> {{ $venueAddress ?: 'Lokasi belum tersedia' }}</span>
                    <span><i class="fa-solid fa-building"></i> {{ $venueName ?: 'Venue belum terdaftar' }}</span>
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

                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px">
                    @if ($canAccessChat)
                        <a href="{{ route('user.openmatches.chat', $match) }}" class="u-btn" style="display:inline-flex;text-decoration:none;background:#14532d">
                            <i class="fa-solid fa-comments"></i> Obrolan Pemain
                        </a>
                    @endif

                    @if($match->whatsapp_number)
                        <a href="https://wa.me/{{ $match->whatsapp_number }}?text={{ urlencode('Halo, saya ingin bertanya tentang Open Match "' . $match->title . '" di ArenaSports.') }}"
                           target="_blank" rel="noopener" class="u-btn" style="display:inline-flex;text-decoration:none;background:#25d366;color:#0b3d1c">
                            <i class="fa-brands fa-whatsapp"></i> Chat Host
                        </a>
                    @endif
                </div>

                <details class="om-more">
                    <summary>Lihat Detail & Lokasi</summary>

                    <div class="om-detail">
                        <span><i class="fa-solid fa-user"></i> Host: {{ $match->booking?->user?->name ?? 'Pengguna ArenaSports' }}</span>
                        <span><i class="fa-solid fa-futbol"></i> {{ $match->jenis ?: 'Olahraga' }} · {{ $match->booking?->lapangan?->nama ?? 'Lapangan' }}</span>
                        <span><i class="fa-solid fa-wallet"></i> {{ $match->is_free ? 'Gratis' : 'Rp ' . number_format((int) $match->biaya_per_pemain, 0, ',', '.') . ' / pemain' }}</span>
                        <span><i class="fa-solid fa-signal"></i> Level: {{ $match->level ?: 'Umum' }}</span>
                    </div>

                    @if($mapEmbed)
                        <div class="om-map">
                            <iframe src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                            <div class="om-map-actions">
                                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps
                                </a>
                                @if($match->whatsapp_number)
                                    <a class="wa" href="https://wa.me/{{ $match->whatsapp_number }}" target="_blank" rel="noopener">
                                        <i class="fa-brands fa-whatsapp"></i> WhatsApp Host
                                    </a>
                                @endif
                            </div>
                        </div>
                    @elseif($mapsUrl)
                        <div class="om-map">
                            <div class="om-map-actions">
                                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps
                                </a>
                            </div>
                        </div>
                    @endif
                </details>

            </div>
        </article>

    @empty

        <div class="u-panel u-empty">
            Belum ada Open Match yang tersedia.
        </div>

    @endforelse
</div>

{{-- EVENT RESMI VENUE ADMIN --}}
<div class="u-section-head" style="margin-top:42px; margin-bottom:16px;">
    <div>
        <h2 style="display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-trophy" style="color:#168653;"></i> Event & Turnamen Resmi
        </h2>
        <p>
            Kompetisi resmi dari pengelola venue rekanan ArenaSports. Daftarkan tim, lalu lanjutkan konfirmasi teknis ke penyelenggara.
        </p>
    </div>
</div>

<div class="ev-flow">
    <div><b>1</b><strong>Lihat detail event</strong>Jadwal, biaya, hadiah, dan lokasi.</div>
    <div><b>2</b><strong>Isi formulir tim</strong>Nama tim, ketua, dan jumlah anggota.</div>
    <div><b>3</b><strong>Lanjut WhatsApp</strong>Otomatis terhubung ke penyelenggara.</div>
    <div><b>4</b><strong>Konfirmasi & bayar</strong>Teknis dan pembayaran diatur penyelenggara.</div>
</div>

<div class="u-cards">
    @forelse ($events as $event)
        @php
            $venueName = $event->venue?->name ?? $event->creator?->venue?->name ?? ($event->creator?->name ?? 'Venue Partner');
            $venueAddress = $event->venue_address ?: $event->lokasi;
            $eventMapEmbed = $event->maps_embed_url;
            $eventMapsUrl = $event->maps_url;
            $registration = $eventRegistrations[$event->id] ?? null;
            $isClosed = ($event->registration_deadline && $event->registration_deadline->lt(today()))
                || ((int) $event->kuota > 0 && $event->registrations()->count() >= (int) $event->kuota);
        @endphp
        <article class="u-card" style="border-top: 4px solid #168653; display:flex; flex-direction:column; justify-content:space-between;">
            <div class="u-card-body">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:10px;">
                    <span class="u-tag" style="background:#e8f7ec; color:#146c43; font-weight:800; border:1px solid #c3e6cb; font-size:11px;">
                        <i class="fa-solid fa-shield-halved"></i> Event Resmi
                    </span>
                    <span class="u-tag">{{ $event->jenis }}</span>
                </div>

                <h3 style="font-size:17px; margin-bottom:12px; color:#143e26;">{{ $event->title }}</h3>

                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
                    <span class="om-badge free"><i class="fa-solid fa-ticket"></i> {{ (int) $event->registration_fee > 0 ? 'Rp ' . number_format((int) $event->registration_fee, 0, ',', '.') . ' / tim' : 'GRATIS' }}</span>
                    @if($event->prize)
                        <span class="om-badge paid"><i class="fa-solid fa-trophy"></i> Hadiah: {{ $event->prize }}</span>
                    @endif
                    @if($event->registration_deadline)
                        <span class="om-badge level"><i class="fa-regular fa-clock"></i> Daftar s/d {{ $event->registration_deadline->format('d M Y') }}</span>
                    @endif
                </div>

                <div class="om-detail" style="margin:12px 0;">
                    <span>
                        <i class="fa-regular fa-calendar"></i>
                        <div>
                            <strong>{{ $event->tanggal?->format('d M Y') }}</strong><br>
                            <small style="color:#718078;">{{ $event->start_time ? substr($event->start_time, 0, 5) : '08:00' }} - {{ $event->end_time ? substr($event->end_time, 0, 5) : 'Selesai' }} WIB</small>
                        </div>
                    </span>
                    <span>
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Lokasi</strong><br>
                            <small style="color:#718078;">{{ $venueAddress }}</small>
                        </div>
                    </span>
                    <span>
                        <i class="fa-solid fa-users"></i>
                        <div>
                            <strong>Kuota</strong><br>
                            <small style="color:#718078;">{{ $event->kuota }} peserta · {{ $event->registrations()->count() }} terdaftar</small>
                        </div>
                    </span>
                    <span>
                        <i class="fa-solid fa-store"></i>
                        <div>
                            <strong>Penyelenggara</strong><br>
                            <small style="color:#718078;">{{ $venueName }}</small>
                        </div>
                    </span>
                </div>

                @if ($event->deskripsi)
                    <div style="background:#f8faf9; padding:12px 14px; border-radius:10px; font-size:12px; color:#4a5c52; line-height:1.6; margin-bottom:14px; border-left:3px solid #168653;">
                        {{ $event->deskripsi }}
                    </div>
                @endif

                <details class="om-more">
                    <summary>Detail & Peta Lokasi</summary>
                    @if($eventMapEmbed)
                        <div class="om-map">
                            <iframe src="{{ $eventMapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                            <div class="om-map-actions">
                                <a href="{{ $eventMapsUrl }}" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps</a>
                            </div>
                        </div>
                    @elseif($eventMapsUrl)
                        <div class="om-map">
                            <div class="om-map-actions">
                                <a href="{{ $eventMapsUrl }}" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps</a>
                            </div>
                        </div>
                    @endif

                    @if($registration)
                        <div class="ev-registered" style="margin-top:14px">
                            <strong><i class="fa-solid fa-circle-check"></i> Tim {{ $registration->team_name }} sudah terdaftar</strong>
                            Status: {{ $registration->status }} · {{ $registration->member_count }} anggota
                            <br>
                            @if($event->whatsapp_number)
                                <a href="https://wa.me/{{ $event->whatsapp_number }}?text={{ urlencode('Halo, saya ketua tim ' . $registration->team_name . ' yang mendaftar event "' . $event->title . '". Mohon informasi teknis selanjutnya.') }}"
                                   target="_blank" rel="noopener"
                                   style="display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:8px 12px;border-radius:9px;background:#25d366;color:#0b3d1c;font-size:12px;font-weight:800;text-decoration:none">
                                    <i class="fa-brands fa-whatsapp"></i> Hubungi Penyelenggara
                                </a>
                            @endif
                        </div>
                    @elseif($isClosed)
                        <div class="ev-registered" style="margin-top:14px;background:#fdeceb;border-color:#f5c9c6;color:#8f2020">
                            <strong><i class="fa-solid fa-lock"></i> Pendaftaran ditutup</strong>
                            Kuota penuh atau batas pendaftaran sudah lewat.
                        </div>
                    @else
                        <form method="POST" action="{{ route('user.events.register', $event) }}" class="ev-form">
                            @csrf
                            <div>
                                <label for="team_name-{{ $event->id }}">Nama tim</label>
                                <input id="team_name-{{ $event->id }}" name="team_name" maxlength="150" required value="{{ old('team_name') }}" placeholder="Contoh: Garuda FC">
                            </div>
                            <div>
                                <label for="captain_name-{{ $event->id }}">Nama ketua tim</label>
                                <input id="captain_name-{{ $event->id }}" name="captain_name" maxlength="150" required value="{{ old('captain_name', $user?->name) }}">
                            </div>
                            <div>
                                <label for="captain_phone-{{ $event->id }}">WhatsApp ketua</label>
                                <input id="captain_phone-{{ $event->id }}" name="captain_phone" maxlength="30" required value="{{ old('captain_phone', $user?->phone) }}" placeholder="08xxxxxxxxxx">
                            </div>
                            <div>
                                <label for="member_count-{{ $event->id }}">Jumlah anggota</label>
                                <input id="member_count-{{ $event->id }}" type="number" name="member_count" min="1" max="1000" required value="{{ old('member_count', 5) }}">
                            </div>
                            <div class="full">
                                <label for="notes-{{ $event->id }}">Catatan (opsional)</label>
                                <textarea id="notes-{{ $event->id }}" name="notes" maxlength="2000" placeholder="Kebutuhan khusus, nama anggota, dll.">{{ old('notes') }}</textarea>
                            </div>
                            <label class="ev-check full" for="agree-{{ $event->id }}">
                                <input id="agree-{{ $event->id }}" type="checkbox" name="agree" value="1" required {{ old('agree') ? 'checked' : '' }}>
                                <span>Saya menyetujui data tim dibagikan ke penyelenggara dan akan melanjutkan konfirmasi via WhatsApp.</span>
                            </label>
                            <button type="submit" class="u-btn full" style="width:100%">
                                <i class="fa-solid fa-paper-plane"></i> Daftarkan Tim
                            </button>
                        </form>
                    @endif
                </details>
            </div>

            <div style="padding:14px 18px; background:#fbfdfc; border-top:1px solid #edf4f0; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                <span style="font-size:11px; color:#62796e;">
                    <i class="fa-solid fa-circle-check" style="color:#168653;"></i> Pendaftaran tercatat & lanjut WhatsApp
                </span>
                @if($event->whatsapp_number)
                    <a href="https://wa.me/{{ $event->whatsapp_number }}?text={{ urlencode('Halo ' . $venueName . ', saya ingin bertanya seputar event: ' . $event->title) }}" target="_blank" rel="noopener" class="u-btn u-btn-green" style="padding:8px 14px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa-brands fa-whatsapp"></i> Hubungi Penyelenggara
                    </a>
                @endif
            </div>
        </article>
    @empty
        <div class="u-panel u-empty" style="grid-column:1/-1; padding:32px 20px; text-align:center;">
            <i class="fa-solid fa-trophy" style="font-size:36px; color:#95b4a2; margin-bottom:10px; display:inline-block;"></i>
            <p style="font-size:14px; font-weight:600; color:#42584c; margin:0 0 4px;">Belum ada event resmi yang dipublikasikan saat ini.</p>
            <small style="color:#788f83;">Event turnamen terbaru dari Venue Admin rekanan akan ditampilkan di sini.</small>
        </div>
    @endforelse
</div>

{{-- INTERAKSI BUKA/TUTUP FORM --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('toggleOpenMatch');
    const form = document.getElementById('formOpenMatch');
    const icon = document.getElementById('iconOpenMatch');

    if (toggle && form && icon) {
        toggle.addEventListener('click', function () {
            const isOpening = form.hidden;
            form.hidden = !isOpening;
            toggle.setAttribute('aria-expanded', String(isOpening));
            icon.textContent = isOpening ? '⌃' : '⌄';
        });

        @if ($errors->any())
            form.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            icon.textContent = '⌃';
        @endif
    }

    const bookingSelect = document.getElementById('booking_id');
    const preview = document.getElementById('bookingPreview');
    const playersInput = document.getElementById('jumlah_pemain');
    const modeRadios = document.querySelectorAll('input[name="biaya_mode"]');
    const costInput = document.getElementById('costInput');
    const costField = document.getElementById('biaya_per_pemain');
    const costHint = document.getElementById('costHint');

    let bookingTotal = 0;

    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value || 0)));
    }

    function selectedBookingData() {
        if (!bookingSelect) return null;
        const option = bookingSelect.options[bookingSelect.selectedIndex];
        if (!option || !option.value) return null;
        return option.dataset;
    }

    function updatePreview() {
        const data = selectedBookingData();
        if (!preview) return;

        if (!data) {
            preview.hidden = true;
            bookingTotal = 0;
            return;
        }

        preview.hidden = false;
        bookingTotal = Number(data.total || 0);

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '-';
        };

        set('previewVenue', data.venue);
        set('previewField', data.field);
        set('previewSport', data.sport);
        set('previewSchedule', (data.date || '-') + ' · ' + (data.time || '-'));
        set('previewTotal', formatRupiah(bookingTotal));
        set('previewAddress', data.address);

        updateSuggestion();
    }

    function updateSuggestion() {
        if (!costField) return;

        const players = Math.max(1, Number(playersInput?.value || 1));
        const suggestion = bookingTotal > 0 ? Math.ceil(bookingTotal / players) : 0;

        if (suggestion > 0) {
            costField.placeholder = String(suggestion);
            if (!costField.value) {
                costField.value = String(suggestion);
            }
            if (costHint) {
                costHint.textContent = 'Saran otomatis: ' + formatRupiah(suggestion) + ' / pemain (' + formatRupiah(bookingTotal) + ' ÷ ' + players + ' pemain). Bisa diubah.';
            }
        } else if (costHint) {
            costHint.textContent = 'Masukkan nominal patungan per pemain.';
        }
    }

    function updateMode() {
        const mode = document.querySelector('input[name="biaya_mode"]:checked')?.value || 'Gratis';
        if (!costInput) return;

        costInput.hidden = mode !== 'Patungan';

        if (mode === 'Patungan') {
            updateSuggestion();
        } else if (costField) {
            costField.value = '';
        }
    }

    if (bookingSelect) bookingSelect.addEventListener('change', updatePreview);
    if (playersInput) playersInput.addEventListener('input', updateSuggestion);
    modeRadios.forEach(radio => radio.addEventListener('change', updateMode));

    updatePreview();
    updateMode();

    const waLink = document.getElementById('eventWaLink');
    if (waLink) {
        setTimeout(function () {
            try { window.open(waLink.href, '_blank'); } catch (e) { /* popup diblokir */ }
        }, 600);
    }
});
</script>

</div>
@endsection
