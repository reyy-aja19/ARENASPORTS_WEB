@extends('layouts.user')

@section('content')
<div class="u-section-head">
    <div>
        <h2>Profil saya</h2>
        <p>Kelola foto, informasi akun, dan keamanan akun ArenaSports.</p>
    </div>
</div>

<div class="u-grid-2">
    <section class="u-panel">
        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="as-profile-heading">
                <div class="as-profile-photo">
                    @if($user->profile_photo)
                        <img id="profilePreview" src="{{ str_starts_with($user->profile_photo, 'profiles/') ? asset('storage/'.$user->profile_photo) : asset($user->profile_photo) }}" alt="Foto profil">
                    @else
                        <div id="profilePlaceholder"><i class="fa-solid fa-user"></i></div>
                        <img id="profilePreview" src="" alt="Pratinjau foto profil" hidden>
                    @endif
                </div>
                <div class="as-profile-intro">
                    <h2>{{ $user->name }}</h2>
                    <p>{{ $user->email ?: 'Belum menambahkan email' }}</p>
                    <span class="u-tag">User ARENASPORTS</span>
                </div>
            </div>

            <div class="u-field" style="margin:18px 0 14px">
                <label for="profile_photo">Foto profil</label>
                <input id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                <small class="as-help">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                @if($user->profile_photo)
                    <label class="as-remove-photo"><input type="checkbox" name="remove_photo" value="1"> Hapus foto profil</label>
                @endif
            </div>

            <div class="u-field" style="margin-bottom:14px">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
            </div>

            <div class="u-field" style="margin-bottom:14px">
                <label for="email">Alamat email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="nama@email.com">
                <small class="as-help">Email digunakan untuk menerima notifikasi aktivitas akun.</small>
            </div>

            <div class="u-field" style="margin-bottom:14px">
                <label for="phone">Nomor WhatsApp</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="081234567890">
            </div>

            <hr style="border:0;border-top:1px solid var(--line);margin:22px 0">
            <h3 style="font:800 15px Manrope">Ganti password (opsional)</h3>
            <div class="u-field" style="margin-bottom:14px">
                <label for="password">Password baru</label>
                <input id="password" type="password" name="password" minlength="8" autocomplete="new-password">
            </div>
            <div class="u-field" style="margin-bottom:18px">
                <label for="password_confirmation">Konfirmasi password baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
            </div>

            <button class="u-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan perubahan</button>
        </form>
    </section>

    <section class="u-panel">
        <h2 style="font:800 18px Manrope;margin-top:0">Ringkasan akun</h2>
        <div class="u-row" style="padding:14px 0;border-bottom:1px solid var(--line)">
            <span class="u-meta">Booking dibuat</span><strong>{{ $user->bookings()->count() }}</strong>
        </div>
        <div class="u-row" style="padding:14px 0;border-bottom:1px solid var(--line)">
            <span class="u-meta">Poin tersedia</span><strong>{{ number_format($user->points) }}</strong>
        </div>
        <div class="u-row" style="padding:14px 0;border-bottom:1px solid var(--line)">
            <span class="u-meta">Status akun</span><span class="u-tag">{{ $user->status }}</span>
        </div>
        <div class="as-security-note">
            <i class="fa-solid fa-shield-halved"></i>
            <div><strong>Keamanan akun</strong><p>Jika email dan nomor WhatsApp sudah ditambahkan, ArenaSports dapat mengirimkan pemberitahuan saat akun digunakan untuk login atau mendaftar.</p></div>
        </div>
        <a class="u-btn ghost" style="margin-top:20px" href="{{ route('user.bookings') }}">Lihat booking saya</a>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('profilePreview');
    const placeholder = document.getElementById('profilePlaceholder');
    if (!input || !preview) return;
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) return;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        if (placeholder) placeholder.hidden = true;
    });
})();
</script>
@endpush
