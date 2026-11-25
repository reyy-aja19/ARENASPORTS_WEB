@extends('layouts.admin', [
    'title' => $event->exists ? 'Edit Event Olahraga' : 'Buat Event Olahraga Baru',
    'heading' => $event->exists ? 'Edit Event Olahraga' : 'Buat Event Olahraga Baru'
])

@section('content')
<div class="panel-card" style="padding:26px; max-width:900px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #edf2ee;">
        <div>
            <h3 style="margin:0; font-size:18px; color:#143e26;">
                <i class="fa-solid fa-trophy"></i> {{ $event->exists ? 'Edit Data Event' : 'Formulir Publikasi Event Olahraga' }}
            </h3>
            <p style="margin:4px 0 0; color:#718078; font-size:13px;">
                Event resmi yang dipublikasikan akan terlihat oleh seluruh pengguna di aplikasi dan menu Open Match.
            </p>
        </div>
        <a href="{{ route('admin.events.index') }}" class="btn-ui btn-gray" style="padding:8px 14px; font-size:12px;">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}">
        @csrf
        @if($event->exists)
            @method('PUT')
        @endif

        <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:18px; margin-bottom:18px;">
            <div class="form-group" style="grid-column:1/-1;">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Nama Event / Turnamen <span style="color:#dc2626;">*</span></label>
                <input class="input-ui" name="title" required maxlength="255" value="{{ old('title', $event->title) }}" placeholder="Contoh: ArenaSports Futsal Championship 2026">
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Cabang Olahraga <span style="color:#dc2626;">*</span></label>
                <select class="select-ui" name="jenis" required>
                    @foreach(['Futsal', 'Badminton', 'Basket', 'Voli', 'Tenis', 'Sepak Bola', 'Lainnya'] as $cbg)
                        <option value="{{ $cbg }}" @selected(old('jenis', $event->jenis) === $cbg)>{{ $cbg }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Jenis Pendaftaran <span style="color:#dc2626;">*</span></label>
                <select class="select-ui" name="registration_type" id="registrationType" required>
                    <option value="Tim" @selected(old('registration_type', $event->registration_type) === 'Tim')>Tim / Kelompok</option>
                    <option value="Individu" @selected(old('registration_type', $event->registration_type) === 'Individu')>Individu</option>
                </select>
                <small style="color:#718078; margin-top:4px; display:block;">Menentukan apakah kuota dihitung per tim atau per peserta individu.</small>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Status Event <span style="color:#dc2626;">*</span></label>
                <select class="select-ui" name="status" required>
                    <option value="Publikasi" @selected(old('status', $event->status) === 'Publikasi')>Publikasi (Dibuka untuk pendaftaran)</option>
                    <option value="Draft" @selected(old('status', $event->status) === 'Draft')>Draft (Segera Dibuka)</option>
                    <option value="Dibatalkan" @selected(old('status', $event->status) === 'Dibatalkan')>Dibatalkan</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Tanggal Pelaksanaan <span style="color:#dc2626;">*</span></label>
                <input class="input-ui" type="date" name="tanggal" required min="{{ now()->toDateString() }}" value="{{ old('tanggal', $event->tanggal?->format('Y-m-d') ?: now()->toDateString()) }}">
            </div>

            <div class="form-group">
                <label id="quotaLabel" style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Kuota Tim <span style="color:#dc2626;">*</span></label>
                <input class="input-ui" type="number" name="kuota" min="1" max="10000" required value="{{ old('kuota', $event->kuota ?? 20) }}" placeholder="Jumlah maksimal">
            </div>

            <div class="form-group">
                <label id="feeLabel" style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Biaya Registrasi per Tim</label>
                <input class="input-ui" type="number" name="registration_fee" min="0" max="999999999" value="{{ old('registration_fee', $event->registration_fee ?? 0) }}" placeholder="0 = gratis">
                <small id="feeHelp" style="color:#718078; margin-top:4px; display:block;">Isi 0 jika event gratis.</small>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Hadiah / Prize Pool</label>
                <input class="input-ui" name="prize" maxlength="255" value="{{ old('prize', $event->prize) }}" placeholder="Contoh: Juara 1 Rp 5.000.000 + trofi">
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">WhatsApp Penyelenggara</label>
                <input class="input-ui" name="contact_phone" maxlength="30" value="{{ old('contact_phone', $event->contact_phone) }}" placeholder="08xxxxxxxxxx">
                <small style="color:#718078; margin-top:4px; display:block;">Nomor ini dipakai untuk tombol "Lanjut WhatsApp" setelah user mendaftar.</small>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Batas Pendaftaran</label>
                <input class="input-ui" type="date" name="registration_deadline" min="{{ now()->toDateString() }}" value="{{ old('registration_deadline', $event->registration_deadline?->format('Y-m-d')) }}">
                <small style="color:#718078; margin-top:4px; display:block;">Kosongkan jika pendaftaran dibuka sampai hari acara.</small>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Venue Terkait (untuk peta)</label>
                <select class="select-ui" name="venue_id">
                    <option value="">— Tidak dikaitkan —</option>
                    @foreach(($venues ?? collect()) as $v)
                        <option value="{{ $v->id }}" @selected((string) old('venue_id', $event->venue_id) === (string) $v->id)>
                            {{ $v->name }}
                        </option>
                    @endforeach
                </select>
                <small style="color:#718078; margin-top:4px; display:block;">Pilih venue agar koordinat/peta lokasi tampil di halaman user.</small>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Jam Mulai (Opsional)</label>
                <input class="input-ui" type="time" name="start_time" value="{{ old('start_time', $event->start_time ? substr($event->start_time, 0, 5) : '08:00') }}">
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Jam Selesai (Opsional)</label>
                <input class="input-ui" type="time" name="end_time" value="{{ old('end_time', $event->end_time ? substr($event->end_time, 0, 5) : '18:00') }}">
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Lokasi Pelaksanaan Venue <span style="color:#dc2626;">*</span></label>
                @if(isset($venues) && $venues->isNotEmpty())
                    <input class="input-ui" name="lokasi" required value="{{ old('lokasi', $event->lokasi ?: $venues->first()->name) }}" list="venueList" placeholder="Nama tempat olahraga / alamat lengkap">
                    <datalist id="venueList">
                        @foreach($venues as $v)
                            <option value="{{ $v->name }} - {{ $v->address }}"></option>
                        @endforeach
                    </datalist>
                    <small style="color:#718078; margin-top:4px; display:block;">Pilih venue Anda dari daftar atau ketikkan alamat lengkap.</small>
                @else
                    <input class="input-ui" name="lokasi" required value="{{ old('lokasi', $event->lokasi) }}" placeholder="Nama tempat olahraga / alamat lengkap venue">
                @endif
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Deskripsi, Syarat & Ketentuan</label>
                <textarea class="textarea-ui" name="deskripsi" rows="5" placeholder="Tuliskan informasi turnamen, aturan permainan, biaya registrasi (jika ada), hadiah, atau kontak panitia.">{{ old('deskripsi', $event->deskripsi) }}</textarea>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:16px; border-top:1px solid #edf2ee;">
            <a href="{{ route('admin.events.index') }}" class="btn-ui btn-gray">Batal</a>
            <button class="btn-ui btn-green" type="submit">
                <i class="fa-solid fa-floppy-disk"></i> {{ $event->exists ? 'Simpan Perubahan Event' : 'Publikasikan Event' }}
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('registrationType');
    const quotaLabel = document.getElementById('quotaLabel');
    const feeLabel = document.getElementById('feeLabel');
    const feeHelp = document.getElementById('feeHelp');

    function syncEventType() {
        const isIndividual = typeSelect && typeSelect.value === 'Individu';
        if (quotaLabel) quotaLabel.innerHTML = (isIndividual ? 'Kuota Peserta' : 'Kuota Tim') + ' <span style="color:#dc2626;">*</span>';
        if (feeLabel) feeLabel.textContent = isIndividual ? 'Biaya Registrasi per Peserta' : 'Biaya Registrasi per Tim';
        if (feeHelp) feeHelp.textContent = isIndividual ? 'Isi 0 jika event gratis untuk peserta.' : 'Isi 0 jika event gratis untuk tim.';
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', syncEventType);
        syncEventType();
    }
});
</script>
@endsection
