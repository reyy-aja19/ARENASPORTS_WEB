@extends('layouts.admin', ['title' => 'Kontrol Global ArenaSports', 'heading' => 'Kontrol Global ArenaSports'])

@section('head')
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
@endsection

@section('content')
<style>
.cc-wrap{--cc-green:#16834a;--cc-ink:#17251d;--cc-muted:#718078;color:var(--cc-ink)}
.cc-hero{padding:24px;border-radius:20px;background:linear-gradient(125deg,#102c20,#17683d);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}
.cc-hero h2{margin:0 0 7px;font-size:clamp(22px,3vw,30px)}.cc-hero p{margin:0;max-width:690px;color:#d4e9dc;line-height:1.6}.cc-hero-mark{font-size:40px;opacity:.9}
.cc-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:20px}.cc-stat,.cc-panel{background:#fff;border:1px solid #e2eae5;border-radius:16px;padding:18px;box-shadow:0 5px 20px #102c2008}.cc-stat small{display:block;color:var(--cc-muted);margin-bottom:6px}.cc-stat strong{font-size:26px}.cc-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:6px;border:1px solid #e0e8e3;background:#f4f8f5;border-radius:14px}.cc-tab{border:0;background:transparent;padding:12px 17px;border-radius:10px;font-weight:700;color:#53645a;cursor:pointer}.cc-tab.active{background:#17683d;color:#fff;box-shadow:0 3px 10px #17683d30}.cc-panel h3{margin:0 0 5px}.cc-muted{color:var(--cc-muted);margin:0 0 18px}.cc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.cc-field{display:flex;flex-direction:column;gap:6px}.cc-field.full{grid-column:1/-1}.cc-field label{font-size:13px;font-weight:700;color:#405248}.cc-field input,.cc-field select,.cc-field textarea{width:100%;box-sizing:border-box;border:1px solid #d7e2da;border-radius:10px;padding:11px 12px;background:#fff;font:inherit}.cc-field textarea{min-height:86px;resize:vertical}.cc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:15px}.cc-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:10px;padding:10px 14px;background:#17683d;color:white;text-decoration:none;font-weight:700;cursor:pointer}.cc-btn.secondary{background:#edf5ef;color:#17683d}.cc-btn.danger{background:#fff0f0;color:#b42318}.cc-table-wrap{overflow:auto;margin-top:18px}.cc-table{width:100%;border-collapse:collapse;min-width:760px}.cc-table th,.cc-table td{text-align:left;padding:12px;border-bottom:1px solid #edf1ee;vertical-align:top}.cc-table th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#728077;background:#f8faf8}.cc-table td{font-size:14px}.cc-tag{display:inline-block;border-radius:99px;background:#eaf7ee;color:#17683d;padding:5px 9px;font-size:12px;font-weight:700}.cc-empty{padding:26px;text-align:center;border:1px dashed #ccd9d0;border-radius:12px;color:#718078}.cc-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px}.cc-item{border:1px solid #e3eae5;border-radius:14px;padding:15px}.cc-item h4{margin:0 0 7px;font-size:16px}.cc-item p{margin:5px 0;color:#65756b;font-size:13px;line-height:1.5}.cc-item .cc-actions{margin-top:12px}.cc-item details{margin-top:12px}.cc-item summary{cursor:pointer;color:#17683d;font-weight:700}.cc-divider{height:1px;background:#e7eee9;margin:22px 0}
@media(max-width:800px){.cc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cc-list{grid-template-columns:1fr}.cc-hero-mark{display:none}}
@media(max-width:560px){.cc-stats{grid-template-columns:1fr}.cc-grid{grid-template-columns:1fr}.cc-hero{padding:19px}.cc-tab{flex:1;padding:11px 9px;font-size:13px}.cc-panel{padding:14px}}
</style>
<div class="cc-wrap">
    <section class="cc-hero">
        <div><h2>Kontrol global Superadmin</h2><p>Satu ruang kerja dengan dua fungsi terpisah: kelola Open Match komunitas dan Event resmi. Admin Venue tidak memiliki akses ke halaman ini dan hanya dapat mengelola data venue miliknya sendiri.</p></div>
        <div class="cc-hero-mark"><i class="fa-solid fa-shield-halved"></i></div>
    </section>
    <div class="cc-stats">
        <div class="cc-stat"><small>Total Open Match</small><strong>{{ $matches->count() }}</strong></div>
        <div class="cc-stat"><small>Match sedang terbuka</small><strong>{{ $matches->where('status','Open')->count() }}</strong></div>
        <div class="cc-stat"><small>Total Event resmi</small><strong>{{ $events->count() }}</strong></div>
    </div>
    <div id="control-center-tabs-root"></div>

    <section class="cc-pane" data-cc-pane="matches">
        <div class="cc-panel">
            <h3>Buat Open Match</h3><p class="cc-muted">Buat pertandingan terbuka untuk pengguna. Booking terkait bersifat opsional.</p>
            <form action="{{ route('admin.openmatches.store') }}" method="POST">
                @csrf<input type="hidden" name="_control_tab" value="matches">
                <div class="cc-grid">
                    <div class="cc-field"><label>Judul match</label><input name="title" maxlength="255" required placeholder="Contoh: Futsal Jumat Malam"></div>
                    <div class="cc-field"><label>Cabang olahraga</label><select name="jenis" required>@foreach(['Futsal','Badminton','Basket','Voli','Tenis','Sepak Bola','Lainnya'] as $jenis)<option>{{ $jenis }}</option>@endforeach</select></div>
                    <div class="cc-field"><label>Tanggal</label><input type="date" name="tanggal" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div>
                    <div class="cc-field"><label>Jam mulai</label><input type="time" name="start_time" value="19:00"></div>
                    <div class="cc-field"><label>Jam selesai</label><input type="time" name="end_time" value="20:00"></div>
                    <div class="cc-field"><label>Target pemain</label><input type="number" name="jumlah_pemain" min="1" max="99" value="10" required></div>
                    <div class="cc-field"><label>Pemain bergabung</label><input type="number" name="jumlah_bergabung" min="0" max="99" value="0"></div>
                    <div class="cc-field"><label>Status</label><select name="status" required><option>Open</option><option>Penuh</option><option>Selesai</option><option>Dibatalkan</option></select></div>
                    <div class="cc-field"><label>Booking terkait</label><select name="booking_id"><option value="">Tanpa booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">#{{ $booking->id }} · {{ $booking->lapangan?->nama }} · {{ $booking->booking_date?->format('d/m/Y') }}</option>@endforeach</select></div>
                    <div class="cc-field full"><label>Deskripsi</label><textarea name="deskripsi" maxlength="5000" placeholder="Level pemain, aturan, perlengkapan, dan informasi tambahan"></textarea></div>
                </div>
                <div class="cc-actions"><button class="cc-btn" type="submit"><i class="fa-solid fa-plus"></i> Publikasikan Open Match</button></div>
            </form>
            <div class="cc-divider"></div><h3>Daftar Open Match</h3>
            @if($matches->isEmpty())<div class="cc-empty">Belum ada Open Match.</div>@else
            <div class="cc-list">
                @foreach($matches as $match)
                <article class="cc-item">
                    <h4>{{ $match->title }}</h4><span class="cc-tag">{{ $match->status }}</span>
                    <p><strong>{{ $match->jenis }}</strong> · {{ $match->tanggal?->format('d M Y') }} · {{ $match->start_time ?? 'Jam belum ditentukan' }}–{{ $match->end_time ?? '-' }}</p>
                    <p>Venue: {{ $match->booking?->lapangan?->nama ?? 'Belum ditautkan' }} · Pemain {{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }}</p>
                    @if($match->deskripsi)<p>{{ $match->deskripsi }}</p>@endif
                    <details><summary>Edit data</summary><form action="{{ route('admin.openmatches.update',$match) }}" method="POST" class="cc-grid" style="margin-top:12px">@csrf @method('PUT')
                        <div class="cc-field"><label>Judul</label><input name="title" value="{{ $match->title }}" required></div>
                        <div class="cc-field"><label>Cabang</label><input name="jenis" value="{{ $match->jenis }}" required></div>
                        <div class="cc-field"><label>Tanggal</label><input type="date" name="tanggal" value="{{ $match->tanggal?->format('Y-m-d') }}" required></div>
                        <div class="cc-field"><label>Mulai</label><input type="time" name="start_time" value="{{ $match->start_time }}"></div>
                        <div class="cc-field"><label>Selesai</label><input type="time" name="end_time" value="{{ $match->end_time }}"></div>
                        <div class="cc-field"><label>Target pemain</label><input type="number" name="jumlah_pemain" min="1" max="99" value="{{ $match->jumlah_pemain }}" required></div>
                        <div class="cc-field"><label>Bergabung</label><input type="number" name="jumlah_bergabung" min="0" max="99" value="{{ $match->jumlah_bergabung }}"></div>
                        <div class="cc-field"><label>Status</label><select name="status">@foreach(['Open','Penuh','Selesai','Dibatalkan'] as $status)<option @selected($match->status===$status)>{{ $status }}</option>@endforeach</select></div>
                        <div class="cc-field"><label>Booking</label><select name="booking_id"><option value="">Tanpa booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}" @selected($match->booking_id==$booking->id)>#{{ $booking->id }} · {{ $booking->lapangan?->nama }}</option>@endforeach</select></div>
                        <div class="cc-field full"><label>Deskripsi</label><textarea name="deskripsi">{{ $match->deskripsi }}</textarea></div>
                        <div class="cc-field full"><button class="cc-btn" type="submit">Simpan perubahan</button></div>
                    </form></details>
                    <div class="cc-actions"><form action="{{ route('admin.openmatches.toggle',$match) }}" method="POST">@csrf<button class="cc-btn secondary" type="submit">Ubah status</button></form><form action="{{ route('admin.openmatches.delete',$match) }}" method="POST" onsubmit="return confirm('Hapus Open Match ini?')">@csrf @method('DELETE')<button class="cc-btn danger" type="submit">Hapus</button></form></div>
                </article>
                @endforeach
            </div>@endif
        </div>
    </section>

    <section class="cc-pane" data-cc-pane="events" hidden>
        <div class="cc-panel">
            <h3>Buat Event Resmi</h3><p class="cc-muted">Event resmi dikelola terpisah dari Open Match, tetapi tetap dari halaman kontrol global yang sama.</p>
            <form action="{{ route('admin.events.store') }}" method="POST">@csrf<input type="hidden" name="_control_tab" value="events">
                <div class="cc-grid">
                    <div class="cc-field"><label>Nama event</label><input name="title" maxlength="255" required placeholder="Contoh: ArenaSports Cup 2026"></div>
                    <div class="cc-field"><label>Cabang olahraga</label><select name="jenis" required>@foreach(['Futsal','Badminton','Basket','Voli','Tenis','Sepak Bola','Lainnya'] as $jenis)<option>{{ $jenis }}</option>@endforeach</select></div>
                    <div class="cc-field"><label>Tanggal</label><input type="date" name="tanggal" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div>
                    <div class="cc-field"><label>Jam mulai</label><input type="time" name="start_time"></div>
                    <div class="cc-field"><label>Jam selesai</label><input type="time" name="end_time"></div>
                    <div class="cc-field"><label>Kuota peserta</label><input type="number" name="kuota" min="1" max="10000" value="32" required></div>
                    <div class="cc-field"><label>Lokasi</label><input name="lokasi" maxlength="255" required placeholder="Nama venue / alamat"></div>
                    <div class="cc-field"><label>Status</label><select name="status"><option>Draft</option><option>Publikasi</option><option>Dibatalkan</option></select></div>
                    <div class="cc-field full"><label>Deskripsi</label><textarea name="deskripsi" maxlength="5000" placeholder="Informasi event, syarat peserta, dan ketentuan"></textarea></div>
                </div>
                <div class="cc-actions"><button class="cc-btn" type="submit"><i class="fa-solid fa-calendar-plus"></i> Simpan Event</button></div>
            </form>
            <div class="cc-divider"></div><h3>Daftar Event Resmi</h3>
            @if($events->isEmpty())<div class="cc-empty">Belum ada Event resmi.</div>@else
            <div class="cc-table-wrap"><table class="cc-table"><thead><tr><th>Event</th><th>Cabang & tanggal</th><th>Lokasi</th><th>Kuota</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @foreach($events as $event)<tr><td><strong>{{ $event->title }}</strong><br><small>Dibuat oleh {{ $event->creator?->name ?? 'Superadmin' }}</small></td><td>{{ $event->jenis }}<br>{{ $event->tanggal?->format('d M Y') }}<br><small>{{ $event->start_time ?? '-' }}–{{ $event->end_time ?? '-' }}</small></td><td>{{ $event->lokasi }}</td><td>{{ $event->kuota }}</td><td><span class="cc-tag">{{ $event->status }}</span></td><td><div class="cc-actions" style="margin-top:0"><a class="cc-btn secondary" href="{{ route('admin.events.edit',$event) }}">Edit</a><form action="{{ route('admin.events.destroy',$event) }}" method="POST" onsubmit="return confirm('Hapus event ini?')">@csrf @method('DELETE')<button class="cc-btn danger" type="submit">Hapus</button></form></div></td></tr>@endforeach
            </tbody></table></div>@endif
        </div>
    </section>
</div>
<script>
(function(){
 const tabs=document.getElementById('control-center-tabs-root');
 if(tabs && !tabs.children.length){
   const name=new URLSearchParams(window.location.search).get('tab')==='events'?'events':'matches';
   document.querySelectorAll('[data-cc-pane]').forEach(p=>p.hidden=p.dataset.ccPane!==name);
 }
})();
</script>
@endsection
