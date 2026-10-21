@extends('layouts.admin', ['title' => 'Review Komentar', 'heading' => 'Review Komentar'])
@section('content')
<div class="stack-list">
@foreach($reviews as $review)
<div class="review-card wide">
    <div class="review-header">
        <div class="review-avatar">{{ strtoupper(substr($review->user?->name ?? 'U',0,1)) }}</div>
        <div style="flex:1;">
            <div style="font-weight:700;">{{ $review->user?->name }}</div>
            <div style="font-weight:700; margin-top:2px;">{{ $review->lapangan?->nama }}</div>
            <div class="stars">{{ str_repeat('★', (int)$review->rating) }}</div>
        </div>
        <div class="muted">{{ $review->is_visible ? 'Tampil' : 'Disembunyikan' }}</div>
    </div>
    <div class="review-message">{{ $review->message }}</div>
    @if($review->reply_message)<div class="reply-box">Balasan admin: {{ $review->reply_message }}</div>@endif
    <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" class="mt-12">@csrf<div class="btn-row stretch"><input class="input-ui" name="reply_message" placeholder="Tulis balasan admin"><button class="btn-ui btn-green">Simpan Balasan</button></div></form>
    <div class="btn-row mt-12 wrap">
        <form method="POST" action="{{ route('admin.reviews.toggle', $review) }}">@csrf<button class="btn-ui btn-gray">{{ $review->is_visible ? 'Sembunyikan' : 'Tampilkan' }}</button></form>
        <form method="POST" action="{{ route('admin.reviews.delete', $review) }}" onsubmit="return confirm('Hapus review ini?')">@csrf @method('DELETE')<button class="btn-ui btn-red">Hapus</button></form>
    </div>
</div>
@endforeach
</div>
@endsection
