@extends('layouts.user')
@section('content')
<div class="u-section-head"><div><h2>{{ $lapangan->nama }}</h2><p>Detail venue sebelum melakukan pemesanan.</p></div><a class="u-btn ghost" href="{{ $lapangan->venue ? route('user.venues.show',$lapangan->venue) : route('user.venues') }}">Kembali ke venue</a></div>
@php
    $courtPhotos = collect(array_merge([$lapangan->foto], $lapangan->foto_gallery ?? []))
        ->filter(fn ($photo) => $photo && file_exists(public_path($photo)))
        ->unique()->values();
@endphp
<div class="u-cards" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr))"><article class="u-card"><div class="u-card-img u-court-gallery" style="height:280px" data-user-slider>
    <div class="u-gallery-track">
        @forelse($courtPhotos as $photo)
            <img class="u-gallery-slide {{ $loop->first ? 'active' : '' }}" src="{{ asset($photo) }}" alt="{{ $lapangan->nama }} foto {{ $loop->iteration }}">
        @empty
            <div class="u-gallery-empty"><i class="fa-solid fa-futbol"></i><span>Foto lapangan belum tersedia</span></div>
        @endforelse
    </div>
    @if($courtPhotos->count() > 1)
        <button type="button" class="u-gallery-arrow prev" data-user-prev aria-label="Foto sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="u-gallery-arrow next" data-user-next aria-label="Foto berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
        <span class="u-gallery-count"><span data-user-current>1</span> / {{ $courtPhotos->count() }}</span>
        <div class="u-gallery-dots">@foreach($courtPhotos as $photo)<button type="button" class="{{ $loop->first ? 'active' : '' }}" data-user-dot="{{ $loop->index }}" aria-label="Lihat foto {{ $loop->iteration }}"></button>@endforeach</div>
    @endif
</div><div class="u-card-body"><div class="u-row"><span class="u-tag">{{ $lapangan->jenis }}</span><span class="u-meta">★ {{ number_format($lapangan->rating,1) }}</span></div><h3>{{ $lapangan->nama }}</h3><p class="u-meta"><i class="fa-solid fa-location-dot"></i> {{ $lapangan->lokasi }}</p><p>{{ $lapangan->deskripsi ?: 'Belum ada deskripsi untuk lapangan ini.' }}</p>@if($lapangan->fasilitas)<p><strong>Fasilitas</strong></p><p>{{ implode(', ', (array)$lapangan->fasilitas) }}</p>@endif<div class="u-price">Rp {{ number_format($lapangan->harga,0,',','.') }} <span class="u-meta">/ jam</span></div></div></article><article class="u-card"><div class="u-card-body"><h3>Booking lapangan</h3><p class="u-meta">Pilih tanggal, waktu mulai, durasi, dan metode pembayaran. Jadwal akan diperiksa sebelum booking dibuat.</p><form method="POST" action="{{ route('user.booking.store',$lapangan) }}" class="u-form">@csrf<div class="u-field full"><label>Tanggal bermain</label><input id="booking-date" type="date" name="booking_date" min="{{ now()->toDateString() }}" value="{{ old('booking_date', $bookingDraft['booking_date'] ?? now()->addDay()->toDateString()) }}" required></div><div class="u-field"><label>Jam mulai</label><input id="booking-start" type="time" name="start_time" value="{{ old('start_time', $bookingDraft['start_time'] ?? '18:00') }}" required></div><div class="u-field"><label>Durasi</label><select id="booking-hours" name="hours">@for($i=1;$i<=8;$i++)<option value="{{ $i }}" @selected(old('hours', $bookingDraft['hours'] ?? 1)==$i)>{{ $i }} jam</option>@endfor</select><small id="booking-end-display">Jam selesai dihitung otomatis.</small></div><div class="u-field full"><label>Ketersediaan waktu</label><div id="availability-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px"></div><small style="display:flex;gap:14px;margin-top:8px"><span><i style="color:#16a34a">●</i> Tersedia</span><span><i style="color:#dc2626">●</i> Sudah dipesan</span></small></div><div class="u-field full"><label>Metode pembayaran</label><select name="payment_method" required><option value="Midtrans" @selected(old('payment_method', $bookingDraft['payment_method'] ?? 'Midtrans') === 'Midtrans')>Pembayaran Online melalui Midtrans</option><option value="Cash" @selected(old('payment_method') === 'Cash')>Cash / Bayar di Tempat</option></select><small>Midtrans diproses secara online. Cash dibayar langsung di venue dan baru dinyatakan lunas setelah Admin Venue mengonfirmasi penerimaan uang.</small></div><button class="u-btn" style="grid-column:1/-1">Booking &amp; Bayar Sekarang</button></form></div></article></div>
@push('scripts')
<script>
(() => {
 const dateInput=document.getElementById('booking-date'), startInput=document.getElementById('booking-start'), hoursInput=document.getElementById('booking-hours'), grid=document.getElementById('availability-grid'), endDisplay=document.getElementById('booking-end-display'), submitButton=document.querySelector('.u-form button[type=submit], .u-form button:not([type])');
 if(!dateInput||!startInput||!hoursInput||!grid)return;
 const url=@json(route('user.courts.availability',$lapangan)); const today=@json(now()->toDateString()); let intervals=[];
 const toMinutes=t=>{const [h,m]=t.split(':').map(Number);return h*60+m};
 function render(){grid.innerHTML='';for(let h=0;h<23;h++){const time=String(h).padStart(2,'0')+':00',begin=h*60,end=begin+(Number(hoursInput.value)*60);const past=dateInput.value===today&&begin<=new Date().getHours()*60+new Date().getMinutes();const conflict=end>=24*60||past||intervals.some(x=>begin<toMinutes(x.end_time)&&end>toMinutes(x.start_time));const btn=document.createElement('button');btn.type='button';btn.textContent=time;btn.style.cssText='padding:9px 5px;border:1px solid '+(conflict?'#dc2626':'#16a34a')+';border-radius:9px;background:'+(conflict?'#fee2e2':'#dcfce7')+';color:'+(conflict?'#991b1b':'#166534')+';cursor:'+(conflict?'not-allowed':'pointer');btn.disabled=conflict;if(!conflict)btn.addEventListener('click',()=>{startInput.value=time;updateEnd();grid.querySelectorAll('button').forEach(b=>b.style.outline='none');btn.style.outline='2px solid #14532d'});grid.appendChild(btn);}}
 function updateEnd(){if(!startInput.value)return;const end=toMinutes(startInput.value)+Number(hoursInput.value)*60;const invalid=end>=24*60||intervals.some(x=>toMinutes(startInput.value)<toMinutes(x.end_time)&&end>toMinutes(x.start_time));if(endDisplay)endDisplay.textContent=end>=24*60?'Jam selesai melewati tengah malam. Pilih jam mulai atau durasi lain.':'Jam selesai otomatis: '+String(Math.floor(end/60)).padStart(2,'0')+':'+String(end%60).padStart(2,'0');if(submitButton)submitButton.disabled=invalid;}
 async function load(){grid.innerHTML='<small>Memuat jadwal...</small>';try{const r=await fetch(url+'?date='+encodeURIComponent(dateInput.value),{headers:{'Accept':'application/json'}});if(!r.ok)throw new Error();const data=await r.json();intervals=data.bookings||[];render();updateEnd();}catch(e){grid.innerHTML='<small>Jadwal tidak dapat dimuat. Silakan muat ulang halaman.</small>';}}
 dateInput.addEventListener('change',load);hoursInput.addEventListener('change',()=>{render();updateEnd();});startInput.addEventListener('change',()=>{const chosen=toMinutes(startInput.value),end=chosen+Number(hoursInput.value)*60;if(end>=24*60||intervals.some(x=>chosen<toMinutes(x.end_time)&&end>toMinutes(x.start_time))){alert('Rentang waktu tersebut sudah dipesan atau melewati tengah malam. Pilih waktu lain.');startInput.value='';}updateEnd();});load();
})();
</script>
@endpush
@endsection
