@extends('layouts.user')
@section('content')
<div class="u-section-head"><div><h2>Temukan Venue</h2><p>Pilih toko olahraga terlebih dahulu, lalu lihat lapangan yang tersedia di dalamnya.</p></div></div>
<div class="u-cards" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
@forelse($venues as $venue)<article class="u-card"><div class="u-card-img" style="height:190px">@if($venue->photo)<img src="{{ asset($venue->photo) }}" alt="{{ $venue->name }}">@else<i class="fa-solid fa-store"></i>@endif</div><div class="u-card-body"><span class="u-tag">{{ implode(', ',$venue->sports ?? []) }}</span><h3>{{ $venue->name }}</h3><p class="u-meta"><i class="fa-solid fa-location-dot"></i> {{ $venue->address }}</p><p>{{ $venue->lapangans_count }} lapangan tersedia</p><a class="u-btn" href="{{ route('user.venues.show',$venue) }}">Lihat Venue</a></div></article>@empty<p>Belum ada venue aktif.</p>@endforelse
</div>
@endsection
