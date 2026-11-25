@extends('layouts.user')

@section('content')
<style>
    .notif-page { width: 100%; }
    .notif-page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; flex-wrap:wrap; margin-bottom:22px; }
    .notif-page-head h2 { margin:0 0 6px; color:#123b2b; font-size:clamp(22px,3vw,30px); font-weight:800; }
    .notif-page-head p { margin:0; color:#718078; font-size:13px; }
    .notif-page-actions { display:flex; gap:9px; flex-wrap:wrap; }
    .notif-action { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:40px; padding:9px 14px; border:1px solid #dce8e0; border-radius:11px; background:#fff; color:#17633e; font-size:12px; font-weight:750; cursor:pointer; text-decoration:none; }
    .notif-action:hover { background:#f2f8f4; }
    .notif-action.danger { color:#b42318; border-color:#f0d8d5; }
    .notif-action:disabled { opacity:.45; cursor:not-allowed; }
    .notif-feedback { margin:0 0 16px; padding:12px 15px; border-radius:11px; background:#edf8f0; color:#17633e; font-size:13px; }
    .notif-history-list { display:grid; gap:11px; }
    .notif-history-card { display:flex; align-items:flex-start; gap:14px; padding:17px; border:1px solid #e6eee8; border-radius:15px; background:#fff; box-shadow:0 5px 18px rgba(20,55,35,.035); }
    .notif-history-card.unread { border-color:#b9ddc6; background:#fbfefc; }
    .notif-history-icon { flex:0 0 42px; width:42px; height:42px; display:grid; place-items:center; border-radius:12px; background:#edf7f0; color:#187044; }
    .notif-history-content { flex:1; min-width:0; }
    .notif-history-title { display:flex; align-items:center; gap:8px; flex-wrap:wrap; color:#183d2c; font-size:14px; font-weight:800; }
    .notif-unread-tag { padding:3px 7px; border-radius:20px; background:#e7f5eb; color:#187044; font-size:10px; font-weight:800; }
    .notif-history-content p { margin:6px 0 8px; color:#637269; font-size:13px; line-height:1.6; overflow-wrap:anywhere; }
    .notif-history-content small { color:#8a968e; font-size:11px; }
    .notif-history-controls { display:flex; gap:7px; align-items:center; flex-wrap:wrap; }
    .notif-history-controls form { margin:0; }
    .notif-empty-state { padding:48px 18px; border:1px dashed #d8e5dc; border-radius:16px; background:#fff; text-align:center; color:#829087; }
    .notif-empty-state i { display:block; margin-bottom:12px; color:#8ab69a; font-size:30px; }
    .notif-empty-state strong { display:block; margin-bottom:5px; color:#254b37; font-size:15px; }
    .notif-pagination { margin-top:20px; }
    @media(max-width:640px) { .notif-history-card { flex-wrap:wrap; padding:14px; } .notif-history-controls { width:100%; padding-left:56px; } .notif-page-actions { width:100%; } .notif-page-actions form { flex:1; } .notif-page-actions .notif-action { width:100%; } }
</style>

<div class="notif-page">
    <div class="notif-page-head">
        <div>
            <h2>{{ __('Riwayat Notifikasi') }}</h2>
            <p>{{ __('Kelola informasi booking, pembayaran, Open Match, dan reward akun kamu.') }}</p>
        </div>
        <div class="notif-page-actions">
            <form method="POST" action="{{ route('user.notifications.read-all') }}">
                @csrf
                <button class="notif-action" type="submit" {{ $user->unreadNotifications()->exists() ? '' : 'disabled' }}>
                    <i class="fa-solid fa-check-double"></i> {{ __('Tandai semua sudah dibaca') }}
                </button>
            </form>
            <form method="POST" action="{{ route('user.notifications.clear') }}"
                  onsubmit="return confirm('{{ __('Hapus seluruh notifikasi akun ini? Tindakan ini tidak dapat dibatalkan.') }}');">
                @csrf
                @method('DELETE')
                <button class="notif-action danger" type="submit" {{ $user->notifications()->exists() ? '' : 'disabled' }}>
                    <i class="fa-regular fa-trash-can"></i> {{ __('Clear semua') }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="notif-feedback" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif

    <div class="notif-history-list">
        @forelse($notifications as $notification)
            <article class="notif-history-card {{ $notification->read_at ? '' : 'unread' }}">
                <div class="notif-history-icon"><i class="fa-regular fa-bell"></i></div>
                <div class="notif-history-content">
                    <div class="notif-history-title">
                        {{ $notification->data['title'] ?? 'Notifikasi ArenaSports' }}
                        @if(!$notification->read_at)<span class="notif-unread-tag">{{ __('BELUM DIBACA') }}</span>@endif
                    </div>
                    <p>{{ $notification->data['message'] ?? '' }}</p>
                    <small><i class="fa-regular fa-clock"></i> {{ $notification->created_at?->format('d M Y, H:i') }} · {{ $notification->created_at?->diffForHumans() }}</small>
                </div>
                <div class="notif-history-controls">
                    @if(!$notification->read_at)
                        <form method="POST" action="{{ route('user.notifications.mark-read', $notification->id) }}">
                            @csrf
                            <button class="notif-action" type="submit"><i class="fa-solid fa-check"></i> {{ __('Tandai dibaca') }}</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('user.notifications.read', $notification->id) }}">
                        @csrf
                        <button class="notif-action" type="submit"><i class="fa-regular fa-folder-open"></i> {{ __('Buka') }}</button>
                    </form>
                    <form method="POST" action="{{ route('user.notifications.delete', $notification->id) }}" onsubmit="return confirm('{{ __('Hapus notifikasi ini?') }}');">
                        @csrf
                        @method('DELETE')
                        <button class="notif-action danger" type="submit"><i class="fa-regular fa-trash-can"></i> {{ __('Hapus') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="notif-empty-state">
                <i class="fa-regular fa-bell-slash"></i>
                <strong>{{ __('Belum ada notifikasi') }}</strong>
                <span>{{ __('Notifikasi aktivitas kamu akan muncul di sini.') }}</span>
            </div>
        @endforelse
    </div>

    <div class="notif-pagination">{{ $notifications->links() }}</div>
    <style>
        .notif-pagination nav{display:flex;justify-content:center;margin-top:20px}
        .notif-pagination .pagination{display:flex;flex-wrap:wrap;gap:6px;list-style:none;padding:0;margin:0}
        .notif-pagination .page-item .page-link,.notif-pagination .page-item span{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:10px;border:1px solid #dce8e0;background:#fff;color:#17633e;font-size:13px;font-weight:700;text-decoration:none}
        .notif-pagination .page-item.active .page-link,.notif-pagination .page-item.active span{background:#17633e;color:#fff;border-color:#17633e}
        .notif-pagination .page-item.disabled .page-link,.notif-pagination .page-item.disabled span{opacity:.45;color:#8a968e}
        .notif-pagination .page-item .page-link:hover{background:#f2f8f4}
    </style>
</div>
@endsection
