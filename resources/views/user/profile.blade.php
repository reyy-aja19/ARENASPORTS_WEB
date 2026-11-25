@extends('layouts.user')

@section('content')
<style>
.pf-hero{display:flex;justify-content:space-between;gap:20px;align-items:flex-end;padding:28px 32px;border-radius:28px;background:linear-gradient(135deg,#163b2d,#0f2d22);color:#fff;margin-bottom:24px}
.pf-kicker{font-size:12px;font-weight:800;letter-spacing:.08em;color:#c8f169;margin-bottom:6px}
.pf-hero h1{margin:0;font-size:30px;line-height:1.1}
.pf-hero p{margin:8px 0 0;color:rgba(255,255,255,.82);max-width:640px}
.pf-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:24px}
.pf-stat{background:#fff;border:1px solid #e5ece7;border-radius:20px;padding:18px;box-shadow:0 8px 24px rgba(15,45,34,.04)}
.pf-stat .lbl{font-size:12px;color:#64748b;font-weight:700;margin-bottom:8px}
.pf-stat .val{font:800 24px/1 Manrope,DM Sans,sans-serif;color:#173326}
.pf-stat .sub{font-size:12px;color:#7b8794;margin-top:6px}
.pf-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(320px,.9fr);gap:22px}
.pf-card{background:#fff;border:1px solid #e5ece7;border-radius:24px;padding:24px;box-shadow:0 8px 24px rgba(15,45,34,.04)}
.pf-card h2{margin:0 0 6px;font:800 20px Manrope,DM Sans,sans-serif;color:#173326}
.pf-card p.lead{margin:0 0 20px;color:#64748b;font-size:13px;line-height:1.7}
.pf-profile-head{display:flex;gap:16px;align-items:center;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #edf2ee}
.pf-photo{width:88px;height:88px;border-radius:24px;overflow:hidden;background:#edf5ef;display:grid;place-items:center;color:#3f6b54;font-size:30px;flex-shrink:0;border:1px solid #dbe7df}
.pf-photo img{width:100%;height:100%;object-fit:cover;display:block}
.pf-name{font:800 22px Manrope,DM Sans,sans-serif;color:#173326;margin:0}
.pf-email{color:#64748b;font-size:13px;margin-top:4px}
.pf-badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.pf-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;background:#f2f7f3;color:#1f513d}
.pf-badge.gold{background:#fff7dd;color:#8a6500}
.pf-field{margin-bottom:14px}
.pf-field label{display:block;font-size:12px;font-weight:800;color:#334155;margin-bottom:7px}
.pf-field input{width:100%;box-sizing:border-box;padding:12px 14px;border-radius:14px;border:1px solid #dbe5de;background:#fff;font:inherit;color:#173326}
.pf-field input:focus{outline:none;border-color:#7db58f;box-shadow:0 0 0 4px rgba(125,181,143,.12)}
.pf-help{display:block;margin-top:6px;font-size:12px;color:#64748b;line-height:1.6}
.pf-remove{display:flex;gap:8px;align-items:center;margin-top:10px;font-size:12px;color:#64748b}
.pf-section-title{font:800 15px Manrope,DM Sans,sans-serif;color:#173326;margin:22px 0 14px;padding-top:22px;border-top:1px solid #edf2ee}
.pf-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:14px;padding:12px 18px;background:#1f513d;color:#fff;font-weight:800;cursor:pointer;text-decoration:none}
.pf-btn.ghost{background:#f2f7f3;color:#1f513d}
.pf-info{display:flex;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid #edf2ee;font-size:13px}
.pf-info .lbl{color:#64748b}.pf-info .val{font-weight:700;color:#173326;text-align:right}
.pf-note{margin-top:18px;padding:14px 16px;border-radius:16px;background:#f7fbf8;border:1px solid #e1ece4;display:flex;gap:12px;color:#355245;font-size:13px;line-height:1.7}
.pf-note i{color:#1f513d;margin-top:2px}
.pf-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
@media(max-width:1000px){.pf-grid{grid-template-columns:1fr}.pf-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.pf-hero{padding:22px}.pf-stats{grid-template-columns:1fr 1fr}.pf-card{padding:18px}.pf-profile-head{align-items:flex-start}}
</style>

@php
    $stats = $stats ?? [
        'bookings' => $user->bookings()->count(),
        'completed_bookings' => $user->bookings()->whereIn('status', ['Dikonfirmasi', 'Selesai'])->count(),
        'redemptions' => \App\Models\Redemption::where('user_id', $user->id)->count(),
        'reviews' => $user->reviews()->count(),
        'member_since' => $user->created_at,
    ];
@endphp

<section class="pf-hero">
    <div>
        <div class="pf-kicker">PROFIL PENGGUNA</div>
        <h1>Kelola akun ArenaSports kamu.</h1>
        <p>Perbarui informasi pribadi, keamanan akun, dan pantau aktivitas booking serta reward kamu di satu tempat.</p>
    </div>
    <div style="text-align:right">
        <div style="font-size:12px;color:rgba(255,255,255,.7);font-weight:700">Poin tersedia</div>
        <div style="font:800 34px/1 Manrope,DM Sans,sans-serif;color:#c8f169;margin-top:6px">{{ number_format($user->points) }}</div>
    </div>
</section>

<div class="pf-stats">
    <div class="pf-stat"><div class="lbl">Total Booking</div><div class="val">{{ number_format($stats['bookings'] ?? 0) }}</div><div class="sub">Semua booking kamu</div></div>
    <div class="pf-stat"><div class="lbl">Booking Selesai</div><div class="val">{{ number_format($stats['completed_bookings'] ?? 0) }}</div><div class="sub">Dikonfirmasi/selesai</div></div>
    <div class="pf-stat"><div class="lbl">Reward Ditukar</div><div class="val">{{ number_format($stats['redemptions'] ?? 0) }}</div><div class="sub">Riwayat penukaran</div></div>
    <div class="pf-stat"><div class="lbl">Ulasan</div><div class="val">{{ number_format($stats['reviews'] ?? 0) }}</div><div class="sub">Ulasan yang kamu berikan</div></div>
</div>

<div class="pf-grid">
    <section class="pf-card">
        <h2>Informasi Profil</h2>
        <p class="lead">Pastikan data akun kamu akurat agar notifikasi booking, WhatsApp, dan penukaran reward berjalan lancar.</p>

        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="pf-profile-head">
                <div class="pf-photo">
                    @if($user->profile_photo)
                        <img id="profilePreview" src="{{ str_starts_with($user->profile_photo, 'profiles/') ? asset('storage/'.$user->profile_photo) : asset($user->profile_photo) }}" alt="Foto profil">
                    @else
                        <div id="profilePlaceholder"><i class="fa-solid fa-user"></i></div>
                        <img id="profilePreview" src="" alt="Pratinjau foto profil" hidden>
                    @endif
                </div>
                <div>
                    <div class="pf-name">{{ $user->name }}</div>
                    <div class="pf-email">{{ $user->email ?: 'Belum menambahkan email' }}</div>
                    <div class="pf-badges">
                        <span class="pf-badge"><i class="fa-solid fa-user"></i> User ARENASPORTS</span>
                        <span class="pf-badge gold"><i class="fa-solid fa-coins"></i> {{ number_format($user->points) }} Poin</span>
                    </div>
                </div>
            </div>

            <div class="pf-field">
                <label for="profile_photo">Foto profil</label>
                <input id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                <input type="hidden" name="remove_photo" id="remove_photo" value="0">
                <small class="pf-help">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                @if($user->profile_photo)
                    <div id="photoRemoveWrap" style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <button type="button" id="markRemovePhotoBtn" class="pf-btn ghost" style="padding:9px 12px;border:1px solid #f1c7c7;background:#fff5f5;color:#b42318;font-size:12px;">
                            <i class="fa-regular fa-trash-can"></i> Hapus foto profil
                        </button>
                        <button type="button" id="undoRemovePhotoBtn" class="pf-btn ghost" style="display:none;padding:9px 12px;font-size:12px;">
                            <i class="fa-solid fa-rotate-left"></i> Batalkan hapus
                        </button>
                    </div>
                    <div id="photoRemoveNote" style="display:none;margin-top:10px;padding:10px 12px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12px;line-height:1.6;">
                        Foto akan dihapus saat kamu klik <strong>Simpan perubahan</strong>. Kamu masih bisa membatalkan sebelum menyimpan.
                    </div>
                @endif
            </div>

            <div class="pf-field">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
            </div>

            <div class="pf-field">
                <label for="email">Alamat email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="nama@email.com">
                <small class="pf-help">Email digunakan untuk menerima notifikasi aktivitas akun.</small>
            </div>

            <div class="pf-field">
                <label for="phone">Nomor WhatsApp</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="081234567890">
                <small class="pf-help">Nomor ini digunakan untuk notifikasi WhatsApp jika tersedia.</small>
            </div>

            <div class="pf-section-title">Ganti password (opsional)</div>
            <div class="pf-field">
                <label for="password">Password baru</label>
                <input id="password" type="password" name="password" minlength="8" autocomplete="new-password">
            </div>
            <div class="pf-field">
                <label for="password_confirmation">Konfirmasi password baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
            </div>

            <div class="pf-actions">
                <button class="pf-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan perubahan</button>
                <a class="pf-btn ghost" href="{{ route('user.bookings') }}"><i class="fa-solid fa-receipt"></i> Booking saya</a>
            </div>
        </form>
    </section>

    <aside class="pf-card">
        <h2>Ringkasan Akun</h2>
        <p class="lead">Informasi singkat aktivitas dan status akun kamu.</p>

        <div class="pf-info"><span class="lbl">Nama</span><span class="val">{{ $user->name }}</span></div>
        <div class="pf-info"><span class="lbl">Email</span><span class="val">{{ $user->email ?: '-' }}</span></div>
        <div class="pf-info"><span class="lbl">WhatsApp</span><span class="val">{{ $user->phone ?: '-' }}</span></div>
        <div class="pf-info"><span class="lbl">Status akun</span><span class="val"><span class="pf-badge">{{ $user->status ?: 'Aktif' }}</span></span></div>
        <div class="pf-info"><span class="lbl">Poin tersedia</span><span class="val">{{ number_format($user->points) }}</span></div>
        <div class="pf-info"><span class="lbl">Total booking</span><span class="val">{{ number_format($stats['bookings'] ?? 0) }}</span></div>
        <div class="pf-info"><span class="lbl">Reward ditukar</span><span class="val">{{ number_format($stats['redemptions'] ?? 0) }}</span></div>
        <div class="pf-info"><span class="lbl">Member sejak</span><span class="val">{{ optional($stats['member_since'] ?? $user->created_at)->format('d M Y') }}</span></div>

        <div class="pf-note">
            <i class="fa-solid fa-shield-halved"></i>
            <div><strong>Keamanan akun</strong><p style="margin:4px 0 0;">Jika email dan nomor WhatsApp sudah ditambahkan, ArenaSports dapat mengirimkan pemberitahuan saat akun digunakan untuk login atau aktivitas penting.</p></div>
        </div>

        <div class="pf-actions">
            <a class="pf-btn ghost" href="{{ route('user.rewards') }}"><i class="fa-solid fa-gift"></i> Lihat reward</a>
            <a class="pf-btn ghost" href="{{ route('user.notifications') }}"><i class="fa-regular fa-bell"></i> Notifikasi</a>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('profilePreview');
    const placeholder = document.getElementById('profilePlaceholder');
    const removeInput = document.getElementById('remove_photo');
    const markRemoveBtn = document.getElementById('markRemovePhotoBtn');
    const undoRemoveBtn = document.getElementById('undoRemovePhotoBtn');
    const removeNote = document.getElementById('photoRemoveNote');
    const originalSrc = preview ? preview.getAttribute('src') : '';
    const hadOriginal = !!(originalSrc && originalSrc.trim() !== '');

    const setRemoveState = (marked) => {
        if (removeInput) removeInput.value = marked ? '1' : '0';
        if (removeNote) removeNote.style.display = marked ? 'block' : 'none';
        if (markRemoveBtn) markRemoveBtn.style.display = marked ? 'none' : 'inline-flex';
        if (undoRemoveBtn) undoRemoveBtn.style.display = marked ? 'inline-flex' : 'none';
        if (marked) {
            if (preview) { preview.hidden = true; preview.removeAttribute('src'); }
            if (placeholder) placeholder.hidden = false;
        } else if (hadOriginal) {
            if (preview) { preview.src = originalSrc; preview.hidden = false; }
            if (placeholder) placeholder.hidden = true;
        }
    };

    if (markRemoveBtn) markRemoveBtn.addEventListener('click', () => {
        if (input) input.value = '';
        setRemoveState(true);
    });
    if (undoRemoveBtn) undoRemoveBtn.addEventListener('click', () => setRemoveState(false));

    if (!input || !preview) return;
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) return;
        if (removeInput) removeInput.value = '0';
        if (removeNote) removeNote.style.display = 'none';
        if (markRemoveBtn) markRemoveBtn.style.display = 'inline-flex';
        if (undoRemoveBtn) undoRemoveBtn.style.display = 'none';
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        if (placeholder) placeholder.hidden = true;
    });
})();
</script>
@endpush
