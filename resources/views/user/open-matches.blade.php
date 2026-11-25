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
.om-badge.type{background:#eef6ff;color:#2b5c8a}
.om-badge.status-open{background:#e4f7ed;color:#167348}
.om-badge.status-full{background:#fff2d6;color:#8b5b00}
.om-badge.status-done{background:#eef2f7;color:#475569}
.om-badge.status-cancelled{background:#fde8e7;color:#a52d2d}
.om-badge.status-draft{background:#f1f5f9;color:#64748b}

.om-capacity{margin:12px 0}
.om-capacity-head{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#526b5c;margin-bottom:6px}
.om-capacity-head strong{color:#1c3d2d}
.om-capacity-track{height:8px;border-radius:999px;background:#e8f0eb;overflow:hidden}
.om-capacity-track span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#22c55e,#168653)}

.om-participants{display:flex;flex-direction:column;gap:8px;margin-top:10px}
.om-participant{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;background:#f6faf7;border:1px solid #e1ece5;font-size:12px;color:#3d5a4c}
.om-participant strong{display:block;color:#1c3d2d;font-size:13px}
.om-participant small{color:#718078}
.om-role{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:800;white-space:nowrap}
.om-role.host{background:#dcfce7;color:#166534}
.om-role.opponent{background:#eef6ff;color:#2b5c8a}
.om-join-fields{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0}
.om-join-fields input{width:100%;box-sizing:border-box;border:1px solid #d7e2da;border-radius:10px;padding:10px 11px;font:inherit}
.om-status-note{padding:10px 12px;border-radius:10px;background:#f4f6f5;border:1px solid #e2e8e4;color:#5a6b60;font-size:12px;line-height:1.5;margin-top:10px}
.om-section-sub{font-size:13px;color:#6b7d71;margin:8px 0 14px}

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
        <h2>{{ __('Open Match Komunitas') }}</h2>
        <p>
            {{ __('Cari kawan tanding dan sparing seru yang dibuat oleh komunitas pemain ArenaSports.') }}
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
                <strong style="display:block;font-size:16px">{{ __('Buat Open Match') }}</strong>
                <small style="display:block;margin-top:5px;color:#15803d">
                    {{ __('Pilih booking, atur biaya patungan, lalu publikasikan.') }}
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
                        <label>Jenis pendaftaran</label>
                        <div class="om-mode">
                            <label><input type="radio" name="registration_type" value="Individu" {{ old('registration_type', 'Individu') === 'Individu' ? 'checked' : '' }}> Individu</label>
                            <label><input type="radio" name="registration_type" value="Kelompok" {{ old('registration_type') === 'Kelompok' ? 'checked' : '' }}> Kelompok</label>
                        </div>
                        <small id="registrationTypeHint">Individu: 1 orang = 1 slot. Kelompok: 1 kelompok = 1 slot dan wajib mengisi nama kelompok.</small>
                    </div>

                    <div class="full" id="groupHostFields" hidden>
                        <div class="om-form-grid" style="gap:12px">
                            <div>
                                <label for="host_team_name">Nama kelompok / tim host</label>
                                <input type="text" id="host_team_name" name="host_team_name" maxlength="150" value="{{ old('host_team_name') }}" placeholder="Contoh: Garuda FC">
                            </div>
                            <div>
                                <label for="host_member_count">Jumlah anggota kelompok host</label>
                                <input type="number" id="host_member_count" name="host_member_count" min="1" max="1000" value="{{ old('host_member_count', 1) }}">
                            </div>
                        </div>
                    </div>

                    <div class="full">
                        <label for="title">Judul pertandingan</label>
                        <input type="text" id="title" name="title" maxlength="255" value="{{ old('title') }}" required placeholder="Contoh: Cari lawan futsal malam ini">
                    </div>

                    <div>
                        <label for="jumlah_pemain" id="capacityLabel">Kapasitas slot pemain</label>
                        <input type="number" id="jumlah_pemain" name="jumlah_pemain" min="2" max="99" value="{{ old('jumlah_pemain', 10) }}" required>
                        <small id="capacityHint">Termasuk kamu sebagai host.</small>
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
<h3>{{ __('Open Match dari pengguna') }}</h3>
<p class="om-section-sub">{{ __('Pilih match yang cocok, lalu gabung sebagai individu atau kelompok. Komunikasi hanya melalui obrolan peserta.') }}</p>

<div class="u-cards">
    @forelse ($matches as $match)

        @php
            $currentUid = (int) (session('admin_user_id') ?? 0);
            $currentUserRole = session('admin_role');
            $hostUid = (int) ($match->booking?->user_id ?? 0);
            $isParticipant = $currentUid > 0 && $match->participants->contains(fn ($participant) => (int) $participant->id === $currentUid);
            $isCourtOwner = $currentUid > 0 && (
                (int) ($match->booking?->lapangan?->owner_admin_id ?? 0) === $currentUid ||
                (int) ($match->booking?->lapangan?->venue?->owner_admin_id ?? 0) === $currentUid ||
                ($currentUserRole === 'venue_admin' && empty($match->booking_id))
            );
            $isMatchMember = $currentUid > 0 && ($hostUid === $currentUid || $isParticipant);
            $canAccessChat = $isMatchMember || $isCourtOwner || $currentUserRole === 'superadmin';
            $isFull = $match->is_full;
            $canJoin = $match->status === 'Open' && ! $match->has_ended && ! $isFull;
            $venueName = $match->booking?->lapangan?->venue?->name;
            $venueAddress = $match->venue_address;
            $mapEmbed = $match->maps_embed_url;
            $mapsUrl = $match->maps_url;
            $percent = $match->jumlah_pemain > 0 ? min(100, round(($match->jumlah_bergabung / $match->jumlah_pemain) * 100)) : 0;
            $sortedParticipants = $match->participants->sortBy(fn ($participant) => strcasecmp((string) ($participant->pivot?->role ?? ''), 'Host') === 0 ? 0 : 1);
        @endphp

        <article class="u-card">
            <div class="u-card-body">

                <div class="u-row" style="flex-wrap:wrap;gap:6px">
                    <span class="u-tag">{{ $match->jenis }}</span>
                    <span class="om-badge type"><i class="fa-solid fa-user-group"></i> {{ $match->registration_type_label }}</span>
                    <span class="om-badge status-{{ $match->status_tone }}"><i class="fa-solid fa-circle-info"></i> {{ $match->display_status }}</span>
                </div>

                <h3 style="margin-top:12px">{{ $match->title }}</h3>

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
                        <span class="om-badge paid"><i class="fa-solid fa-wallet"></i> Rp {{ number_format((int) $match->biaya_per_pemain, 0, ',', '.') }} / {{ $match->is_group ? 'kelompok' : 'pemain' }}</span>
                    @endif
                    @if($match->level)
                        <span class="om-badge level"><i class="fa-solid fa-signal"></i> {{ $match->level }}</span>
                    @endif
                    <span class="om-badge open"><i class="fa-solid fa-users"></i> Sisa {{ $match->remaining_slots }} slot {{ $match->capacity_label }}</span>
                </div>

                @if ($match->deskripsi)
                    <p>{{ $match->deskripsi }}</p>
                @endif

                <div class="om-capacity">
                    <div class="om-capacity-head">
                        <strong>{{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }} slot {{ $match->capacity_label }}</strong>
                        <span>{{ $percent }}% terisi</span>
                    </div>
                    <div class="om-capacity-track"><span style="width: {{ $percent }}%"></span></div>
                </div>

                <div class="om-detail">
                    <span><i class="fa-solid fa-calendar-days"></i> {{ $match->tanggal?->format('d M Y') ?? 'Tanggal belum tersedia' }}</span>
                    <span><i class="fa-regular fa-clock"></i> {{ substr((string) $match->start_time, 0, 5) }}–{{ substr((string) $match->end_time, 0, 5) }}</span>
                    <span><i class="fa-solid fa-location-dot"></i> {{ $venueAddress ?: 'Lokasi belum tersedia' }}</span>
                    <span><i class="fa-solid fa-building"></i> {{ $venueName ?: 'Venue belum terdaftar' }}</span>
                </div>

                <div style="margin-top:12px">
                    <strong style="font-size:13px;color:#1c3d2d"><i class="fa-solid fa-users"></i> Detail Peserta</strong>
                    <div class="om-participants">
                        @forelse($sortedParticipants as $participant)
                            @php $isHostRole = strcasecmp((string) ($participant->pivot?->role ?? ''), 'Host') === 0; @endphp
                            <div class="om-participant">
                                <div>
                                    <strong>{{ $participant->name }}@if($isHostRole) <span style="font-weight:600;color:#718078">(Host)</span>@endif</strong>
                                    <small>
                                        {{ $isHostRole ? 'Pembuat match' : 'Pemain lawan' }}
                                        @if($participant->pivot?->team_name)
                                            · {{ $participant->pivot->team_name }}
                                        @endif
                                        @if($match->is_group)
                                            · {{ (int) ($participant->pivot?->member_count ?? 1) }} anggota
                                        @endif
                                    </small>
                                </div>
                                <span class="om-role {{ $isHostRole ? 'host' : 'opponent' }}">{{ $isHostRole ? 'HOST' : 'LAWAN' }}</span>
                            </div>
                        @empty
                            <div class="om-participant">
                                <div>
                                    <strong>{{ $match->booking?->user?->name ?? 'Pengguna ArenaSports' }}</strong>
                                    <small>Pembuat match</small>
                                </div>
                                <span class="om-role host">HOST</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if($isMatchMember)
                    <button type="button" class="u-btn om-joined" disabled><i class="fa-solid fa-check"></i> Sudah Bergabung sebagai {{ $match->is_group ? 'Kelompok' : 'Pemain' }}</button>
                @elseif($isFull)
                    <button type="button" class="u-btn om-full" disabled><i class="fa-solid fa-lock"></i> Slot Sudah Penuh</button>
                @elseif(! $canJoin)
                    <div class="om-status-note"><i class="fa-solid fa-circle-info"></i> Open Match ini berstatus <strong>{{ $match->display_status }}</strong> dan tidak dapat diikuti.</div>
                @else
                    <form method="POST" action="{{ route('user.openmatches.join', $match) }}" style="margin-top:12px">
                        @csrf
                        @if($match->is_group)
                            <div class="om-join-fields">
                                <div>
                                    <label for="team_name-{{ $match->id }}" style="display:block;font-size:12px;font-weight:700;color:#3d5a4c;margin-bottom:6px">Nama kelompok / tim</label>
                                    <input id="team_name-{{ $match->id }}" name="team_name" maxlength="150" required value="{{ old('team_name') }}" placeholder="Contoh: Garuda FC">
                                </div>
                                <div>
                                    <label for="member_count-{{ $match->id }}" style="display:block;font-size:12px;font-weight:700;color:#3d5a4c;margin-bottom:6px">Jumlah anggota</label>
                                    <input id="member_count-{{ $match->id }}" type="number" name="member_count" min="1" max="1000" required value="{{ old('member_count', 1) }}">
                                </div>
                            </div>
                        @endif
                        <button type="submit" class="u-btn" style="width:100%"><i class="fa-solid fa-user-plus"></i> Gabung sebagai {{ $match->is_group ? 'Kelompok Lawan' : 'Pemain Lawan' }}</button>
                    </form>
                @endif

                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px">
                    @if ($canAccessChat)
                        <a href="{{ route('user.openmatches.chat', $match) }}" class="u-btn" style="display:inline-flex;text-decoration:none;background:#14532d">
                            <i class="fa-solid fa-comments"></i> Obrolan Peserta
                        </a>
                    @else
                        <div class="om-status-note" style="margin-top:0;flex:1"><i class="fa-solid fa-lock"></i> Obrolan hanya untuk host dan peserta yang sudah bergabung.</div>
                    @endif
                </div>

                <details class="om-more">
                    <summary>Lihat Detail & Lokasi</summary>

                    <div class="om-detail">
                        <span><i class="fa-solid fa-user"></i> Host: {{ $match->booking?->user?->name ?? 'Pengguna ArenaSports' }}</span>
                        <span><i class="fa-solid fa-futbol"></i> {{ $match->jenis ?: 'Olahraga' }} · {{ $match->booking?->lapangan?->nama ?? 'Lapangan' }}</span>
                        <span><i class="fa-solid fa-wallet"></i> {{ $match->is_free ? 'Gratis' : 'Rp ' . number_format((int) $match->biaya_per_pemain, 0, ',', '.') . ' / ' . ($match->is_group ? 'kelompok' : 'pemain') }}</span>
                        <span><i class="fa-solid fa-signal"></i> Level: {{ $match->level ?: 'Umum' }}</span>
                    </div>

                    @if($mapEmbed)
                        <div class="om-map">
                            <iframe src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                            <div class="om-map-actions">
                                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps
                                </a>
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

@if($myHistoryMatches->isNotEmpty())
    <h3 style="margin-top:34px">Riwayat Open Match Saya</h3>
    <p class="om-section-sub">Match yang sudah selesai, dibatalkan, atau sudah lewat jadwalnya.</p>
    <div class="u-cards">
        @foreach($myHistoryMatches as $match)
            @php
                $currentUid = (int) (session('admin_user_id') ?? 0);
                $currentUserRole = session('admin_role');
                $hostUid = (int) ($match->booking?->user_id ?? 0);
                $isParticipant = $currentUid > 0 && $match->participants->contains(fn ($participant) => (int) $participant->id === $currentUid);
                $canAccessChat = $currentUid > 0 && (
                    $hostUid === $currentUid ||
                    $isParticipant ||
                    $currentUserRole === 'superadmin' ||
                    (int) ($match->booking?->lapangan?->owner_admin_id ?? 0) === $currentUid ||
                    (int) ($match->booking?->lapangan?->venue?->owner_admin_id ?? 0) === $currentUid
                );
            @endphp
            <article class="u-card">
                <div class="u-card-body">
                    <div class="u-row" style="flex-wrap:wrap;gap:6px">
                        <span class="u-tag">{{ $match->jenis }}</span>
                        <span class="om-badge type">{{ $match->registration_type_label }}</span>
                        <span class="om-badge status-{{ $match->status_tone }}">{{ $match->display_status }}</span>
                    </div>
                    <h3 style="margin-top:12px">{{ $match->title }}</h3>
                    <p class="u-meta">{{ $match->tanggal?->format('d M Y') }} · {{ substr((string) $match->start_time, 0, 5) }}–{{ substr((string) $match->end_time, 0, 5) }}</p>
                    <p class="u-meta">{{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }} slot {{ $match->capacity_label }} · {{ $match->booking?->lapangan?->nama ?? 'Lapangan' }}</p>
                    @if($canAccessChat)
                        <a href="{{ route('user.openmatches.chat', $match) }}" class="u-btn" style="display:inline-flex;text-decoration:none;background:#14532d;margin-top:8px">
                            <i class="fa-solid fa-comments"></i> Obrolan Peserta
                        </a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif

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
            $registrationCount = $event->registration_count;
            $quotaPercent = $event->kuota > 0 ? min(100, round(($registrationCount / $event->kuota) * 100)) : 0;
            $isClosed = ! $event->is_registration_open;
            $quotaUnit = $event->quota_unit;
        @endphp
        <article class="u-card" style="border-top: 4px solid #168653; display:flex; flex-direction:column; justify-content:space-between;">
            <div class="u-card-body">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:10px; flex-wrap:wrap">
                    <span class="u-tag" style="background:#e8f7ec; color:#146c43; font-weight:800; border:1px solid #c3e6cb; font-size:11px;">
                        <i class="fa-solid fa-shield-halved"></i> Event Resmi
                    </span>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <span class="u-tag">{{ $event->jenis }}</span>
                        <span class="om-badge type"><i class="fa-solid fa-user-group"></i> {{ $event->registration_type_label }}</span>
                        <span class="om-badge status-{{ $event->status_tone }}"><i class="fa-solid fa-circle-info"></i> {{ $event->display_status }}</span>
                    </div>
                </div>

                <h3 style="font-size:17px; margin-bottom:12px; color:#143e26;">{{ $event->title }}</h3>

                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
                    <span class="om-badge free"><i class="fa-solid fa-ticket"></i> {{ (int) $event->registration_fee > 0 ? 'Rp ' . number_format((int) $event->registration_fee, 0, ',', '.') . ' / ' . $quotaUnit : 'GRATIS' }}</span>
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
                            <strong>Kuota {{ $quotaUnit }}</strong><br>
                            <small style="color:#718078;">{{ $registrationCount }}/{{ $event->kuota }} {{ $quotaUnit }} terdaftar</small>
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

                <div class="om-capacity">
                    <div class="om-capacity-head">
                        <strong>{{ $registrationCount }}/{{ $event->kuota }} {{ $quotaUnit }}</strong>
                        <span>{{ $quotaPercent }}% terisi</span>
                    </div>
                    <div class="om-capacity-track"><span style="width: {{ $quotaPercent }}%"></span></div>
                </div>

                @if ($event->deskripsi)
                    <div style="background:#f8faf9; padding:12px 14px; border-radius:10px; font-size:12px; color:#4a5c52; line-height:1.6; margin-bottom:14px; border-left:3px solid #168653;">
                        {{ $event->deskripsi }}
                    </div>
                @endif

                <details class="om-more">
                    <summary>Detail, Peta & Peserta Terdaftar</summary>
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

                    <div style="margin-top:14px">
                        <strong style="font-size:13px;color:#1c3d2d"><i class="fa-solid fa-clipboard-list"></i> Peserta Terdaftar ({{ $registrationCount }})</strong>
                        <div class="om-participants">
                            @forelse($event->registrations as $registered)
                                <div class="om-participant">
                                    <div>
                                        <strong>{{ $registered->display_name }}@if($registered->is_individual) <span style="font-weight:600;color:#718078">(Individu)</span>@endif</strong>
                                        <small>
                                            @if($registered->is_individual)
                                                Pendaftar individu
                                            @else
                                                Tim · {{ (int) $registered->member_count }} anggota
                                            @endif
                                        </small>
                                    </div>
                                    <span class="om-role {{ in_array($registered->status, ['Terkonfirmasi', 'Dihubungi'], true) ? 'host' : 'opponent' }}">{{ strtoupper($registered->status) }}</span>
                                </div>
                            @empty
                                <p class="u-meta" style="margin:0">Belum ada peserta yang terdaftar.</p>
                            @endforelse
                        </div>
                    </div>

                    @if($registration)
                        <div class="ev-registered" style="margin-top:14px">
                            <strong><i class="fa-solid fa-circle-check"></i> {{ $registration->is_individual ? 'Pendaftaran' : 'Tim' }} {{ $registration->display_name }} sudah terdaftar</strong>
                            Status: {{ $registration->status }} · {{ $registration->is_individual ? 'Peserta individu' : $registration->member_count . ' anggota' }}
                            <br>
                            @if($event->whatsapp_number)
                                <a href="https://wa.me/{{ $event->whatsapp_number }}?text={{ urlencode('Halo, saya ' . $registration->captain_name . ' yang mendaftar event "' . $event->title . '". Mohon informasi teknis selanjutnya.') }}"
                                   target="_blank" rel="noopener"
                                   style="display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:8px 12px;border-radius:9px;background:#25d366;color:#0b3d1c;font-size:12px;font-weight:800;text-decoration:none">
                                    <i class="fa-brands fa-whatsapp"></i> Hubungi Penyelenggara
                                </a>
                            @endif
                        </div>
                    @elseif($isClosed)
                        <div class="ev-registered" style="margin-top:14px;background:#fdeceb;border-color:#f5c9c6;color:#8f2020">
                            <strong><i class="fa-solid fa-lock"></i> Pendaftaran {{ $event->display_status === 'Segera Dibuka' ? 'belum dibuka' : 'ditutup' }}</strong>
                            {{ $event->display_status === 'Segera Dibuka' ? 'Event ini akan segera dibuka untuk pendaftaran.' : 'Kuota penuh, event selesai, atau batas pendaftaran sudah lewat.' }}
                        </div>
                    @else
                        <form method="POST" action="{{ route('user.events.register', $event) }}" class="ev-form">
                            @csrf
                            @if(! $event->is_individual)
                                <div>
                                    <label for="team_name-{{ $event->id }}">Nama tim</label>
                                    <input id="team_name-{{ $event->id }}" name="team_name" maxlength="150" required value="{{ old('team_name') }}" placeholder="Contoh: Garuda FC">
                                </div>
                            @endif
                            <div>
                                <label for="captain_name-{{ $event->id }}">{{ $event->is_individual ? 'Nama peserta' : 'Nama ketua tim' }}</label>
                                <input id="captain_name-{{ $event->id }}" name="captain_name" maxlength="150" required value="{{ old('captain_name', $user?->name) }}">
                            </div>
                            <div>
                                <label for="captain_phone-{{ $event->id }}">WhatsApp {{ $event->is_individual ? 'peserta' : 'ketua' }}</label>
                                <input id="captain_phone-{{ $event->id }}" name="captain_phone" maxlength="30" required value="{{ old('captain_phone', $user?->phone) }}" placeholder="08xxxxxxxxxx">
                            </div>
                            @if(! $event->is_individual)
                                <div>
                                    <label for="member_count-{{ $event->id }}">Jumlah anggota</label>
                                    <input id="member_count-{{ $event->id }}" type="number" name="member_count" min="1" max="1000" required value="{{ old('member_count', 5) }}">
                                </div>
                            @endif
                            <div class="full">
                                <label for="notes-{{ $event->id }}">Catatan (opsional)</label>
                                <textarea id="notes-{{ $event->id }}" name="notes" maxlength="2000" placeholder="{{ $event->is_individual ? 'Kebutuhan khusus atau informasi tambahan.' : 'Kebutuhan khusus, nama anggota, dll.' }}">{{ old('notes') }}</textarea>
                            </div>
                            <label class="ev-check full" for="agree-{{ $event->id }}">
                                <input id="agree-{{ $event->id }}" type="checkbox" name="agree" value="1" required {{ old('agree') ? 'checked' : '' }}>
                                <span>Saya menyetujui data {{ $event->is_individual ? 'peserta' : 'tim' }} dibagikan ke penyelenggara dan akan melanjutkan konfirmasi via WhatsApp.</span>
                            </label>
                            <button type="submit" class="u-btn full" style="width:100%">
                                <i class="fa-solid fa-paper-plane"></i> {{ $event->is_individual ? 'Daftar sebagai Peserta' : 'Daftarkan Tim' }}
                            </button>
                        </form>
                    @endif
                </details>
            </div>

            <div style="padding:14px 18px; background:#fbfdfc; border-top:1px solid #edf4f0; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap">
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
    const registrationTypeRadios = document.querySelectorAll('input[name="registration_type"]');
    const costInput = document.getElementById('costInput');
    const costField = document.getElementById('biaya_per_pemain');
    const costHint = document.getElementById('costHint');
    const groupHostFields = document.getElementById('groupHostFields');
    const hostTeamName = document.getElementById('host_team_name');
    const hostMemberCount = document.getElementById('host_member_count');
    const capacityLabel = document.getElementById('capacityLabel');
    const capacityHint = document.getElementById('capacityHint');
    const registrationTypeHint = document.getElementById('registrationTypeHint');

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

        const type = document.querySelector('input[name="registration_type"]:checked')?.value || 'Individu';
        const unit = type === 'Kelompok' ? 'kelompok' : 'pemain';
        if (suggestion > 0) {
            costField.placeholder = String(suggestion);
            if (!costField.value) {
                costField.value = String(suggestion);
            }
            if (costHint) {
                costHint.textContent = 'Saran otomatis: ' + formatRupiah(suggestion) + ' / ' + unit + ' (' + formatRupiah(bookingTotal) + ' ÷ ' + players + ' slot). Bisa diubah.';
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

    function updateRegistrationType() {
        const type = document.querySelector('input[name="registration_type"]:checked')?.value || 'Individu';
        const isGroup = type === 'Kelompok';

        if (groupHostFields) groupHostFields.hidden = !isGroup;
        if (hostTeamName) hostTeamName.required = isGroup;
        if (hostMemberCount) hostMemberCount.required = isGroup;

        if (capacityLabel) capacityLabel.textContent = isGroup ? 'Kapasitas slot kelompok' : 'Kapasitas slot pemain';
        if (capacityHint) capacityHint.textContent = isGroup
            ? 'Termasuk kelompok host. 1 kelompok = 1 slot.'
            : 'Termasuk kamu sebagai host. 1 orang = 1 slot.';
        if (registrationTypeHint) registrationTypeHint.textContent = isGroup
            ? 'Kelompok: 1 kelompok = 1 slot. Peserta lawan akan mengisi nama kelompok dan jumlah anggota saat bergabung.'
            : 'Individu: 1 orang = 1 slot. Peserta lawan bergabung sebagai pemain individu.';

        if (costHint) {
            const players = Math.max(1, Number(playersInput?.value || 1));
            const suggestion = bookingTotal > 0 ? Math.ceil(bookingTotal / players) : 0;
            if (costHint.textContent.includes('Saran otomatis') && suggestion > 0) {
                costHint.textContent = 'Saran otomatis: ' + formatRupiah(suggestion) + ' / ' + (isGroup ? 'kelompok' : 'pemain') + ' (' + formatRupiah(bookingTotal) + ' ÷ ' + players + ' slot). Bisa diubah.';
            }
        }
    }

    if (bookingSelect) bookingSelect.addEventListener('change', updatePreview);
    if (playersInput) playersInput.addEventListener('input', function () { updateSuggestion(); updateRegistrationType(); });
    modeRadios.forEach(radio => radio.addEventListener('change', updateMode));
    registrationTypeRadios.forEach(radio => radio.addEventListener('change', updateRegistrationType));

    updatePreview();
    updateMode();
    updateRegistrationType();

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
