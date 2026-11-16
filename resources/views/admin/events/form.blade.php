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
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Status Event <span style="color:#dc2626;">*</span></label>
                <select class="select-ui" name="status" required>
                    <option value="Publikasi" @selected(old('status', $event->status) === 'Publikasi')>Publikasi (Aktif & Diumumkan)</option>
                    <option value="Draft" @selected(old('status', $event->status) === 'Draft')>Draft (Belum Diumumkan)</option>
                    <option value="Dibatalkan" @selected(old('status', $event->status) === 'Dibatalkan')>Dibatalkan</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Tanggal Pelaksanaan <span style="color:#dc2626;">*</span></label>
                <input class="input-ui" type="date" name="tanggal" required min="{{ now()->toDateString() }}" value="{{ old('tanggal', $event->tanggal?->format('Y-m-d') ?: now()->toDateString()) }}">
            </div>

            <div class="form-group">
                <label style="font-weight:700; color:#274333; margin-bottom:6px; display:block;">Kuota Peserta / Tim <span style="color:#dc2626;">*</span></label>
                <input class="input-ui" type="number" name="kuota" min="1" max="10000" required value="{{ old('kuota', $event->kuota ?? 20) }}" placeholder="Jumlah peserta/tim maksimal">
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
@endsection
