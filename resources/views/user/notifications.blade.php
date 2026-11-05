@extends('layouts.user')
@section('content')
<div class="u-section-head"><div><h2>Riwayat Notifikasi</h2><p>Informasi booking, pembayaran, Open Match, dan reward akun kamu.</p></div>
@if($user->unreadNotifications()->exists())<form method="POST" action="{{ route('user.notifications.read-all') }}">@csrf<button class="u-btn ghost" type="submit"><i class="fa-solid fa-check-double"></i> Tandai semua dibaca</button></form>@endif
</div>
<div class="u-notif-list">
@forelse($notifications as $notification)
<div class="u-notif-history {{ $notification->read_at ? '' : 'unread' }}"><div class="u-notif-icon"><i class="fa-regular fa-bell"></i></div><div class="u-notif-copy"><strong>{{ $notification->data['title'] ?? 'Notifikasi ArenaSports' }}</strong><p>{{ $notification->data['message'] ?? '' }}</p><small>{{ $notification->created_at?->format('d M Y, H:i') }} · {{ $notification->created_at?->diffForHumans() }}</small></div><form method="POST" action="{{ route('user.notifications.read', $notification->id) }}">@csrf<button class="u-btn small" type="submit">{{ $notification->read_at ? 'Buka' : 'Baca' }}</button></form></div>
@empty<div class="u-empty"><i class="fa-regular fa-bell-slash"></i><strong>Belum ada notifikasi</strong><span>Notifikasi aktivitas kamu akan muncul di sini.</span></div>@endforelse
</div>
<div class="u-pagination">{{ $notifications->links() }}</div>
@endsection
