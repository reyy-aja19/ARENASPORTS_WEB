@extends('layouts.admin', ['title' => 'Notifikasi', 'heading' => 'Notifikasi'])

@section('content')
<style>
    .an-page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:20px}
    .an-page-head p{margin:6px 0 0;color:#6b7280;font-size:13px}
    .an-actions{display:flex;gap:8px;flex-wrap:wrap}
    .an-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid #dce8e0;background:#fff;color:#17633e;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none}
    .an-btn:hover{background:#f2f8f4}
    .an-btn.danger{color:#b42318;border-color:#f0d8d5}
    .an-btn:disabled{opacity:.45;cursor:not-allowed}
    .an-list{display:grid;gap:12px}
    .an-card{display:flex;align-items:flex-start;gap:14px;padding:16px;border:1px solid #e6eee8;border-radius:14px;background:#fff;box-shadow:0 4px 14px rgba(20,55,35,.03)}
    .an-card.unread{border-color:#b9ddc6;background:#fbfefc}
    .an-icon{flex:0 0 42px;width:42px;height:42px;display:grid;place-items:center;border-radius:12px;background:#edf7f0;color:#187044}
    .an-content{flex:1;min-width:0}
    .an-title{display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:#183d2c;font-size:14px;font-weight:800}
    .an-tag{padding:3px 7px;border-radius:20px;background:#e7f5eb;color:#187044;font-size:10px;font-weight:800}
    .an-content p{margin:6px 0 8px;color:#637269;font-size:13px;line-height:1.6;overflow-wrap:anywhere}
    .an-content small{color:#8a968e;font-size:11px}
    .an-controls{display:flex;gap:7px;flex-wrap:wrap;align-items:center}
    .an-controls form{margin:0}
    .an-empty{padding:44px 18px;border:1px dashed #d8e5dc;border-radius:16px;background:#fff;text-align:center;color:#829087}
    .an-empty i{display:block;margin-bottom:10px;color:#8ab69a;font-size:30px}
    .an-empty strong{display:block;margin-bottom:4px;color:#254b37;font-size:15px}

    /* Pagination */
    .an-pagination nav{display:flex;justify-content:center;margin-top:20px}
    .an-pagination .pagination{display:flex;flex-wrap:wrap;gap:6px;list-style:none;padding:0;margin:0}
    .an-pagination .page-item .page-link,
    .an-pagination .page-item span{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:10px;border:1px solid #dce8e0;background:#fff;color:#17633e;font-size:13px;font-weight:700;text-decoration:none}
    .an-pagination .page-item.active .page-link,
    .an-pagination .page-item.active span{background:#17633e;color:#fff;border-color:#17633e}
    .an-pagination .page-item.disabled .page-link,
    .an-pagination .page-item.disabled span{opacity:.45;color:#8a968e}
    .an-pagination .page-item .page-link:hover{background:#f2f8f4}
    @media(max-width:640px){.an-card{flex-wrap:wrap}.an-controls{width:100%;padding-left:56px}.an-actions{width:100%}.an-actions form{flex:1}.an-actions .an-btn{width:100%;justify-content:center}}
</style>

<div class="an-page-head">
    <div>
        <h2 style="margin:0;color:#123b2b;font-size:24px;font-weight:800;">Riwayat Notifikasi</h2>
        <p>Kelola notifikasi akun admin/venue Anda.</p>
    </div>
    <div class="an-actions">
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button class="an-btn" type="submit" {{ $adminUser->unreadNotifications()->exists() ? '' : 'disabled' }}>
                <i class="fa-solid fa-check-double"></i> Tandai semua dibaca
            </button>
        </form>
        <form method="POST" action="{{ route('admin.notifications.clear') }}" onsubmit="return confirm('Hapus semua notifikasi? Tindakan ini tidak dapat dibatalkan.');">
            @csrf
            @method('DELETE')
            <button class="an-btn danger" type="submit" {{ $adminUser->notifications()->exists() ? '' : 'disabled' }}>
                <i class="fa-regular fa-trash-can"></i> Hapus semua
            </button>
        </form>
    </div>
</div>

<div class="an-list">
    @forelse($items as $notification)
        <article class="an-card {{ $notification->read_at ? '' : 'unread' }}">
            <div class="an-icon"><i class="fa-regular fa-bell"></i></div>
            <div class="an-content">
                <div class="an-title">
                    {{ $notification->data['title'] ?? 'Notifikasi ArenaSports' }}
                    @if(!$notification->read_at)<span class="an-tag">BELUM DIBACA</span>@endif
                </div>
                <p>{{ $notification->data['message'] ?? '' }}</p>
                <small><i class="fa-regular fa-clock"></i> {{ $notification->created_at?->format('d M Y, H:i') }} Â· {{ $notification->created_at?->diffForHumans() }}</small>
            </div>
            <div class="an-controls">
                @if(!$notification->read_at)
                    <form method="POST" action="{{ route('admin.notifications.mark-read', $notification->id) }}">
                        @csrf
                        <button class="an-btn" type="submit"><i class="fa-solid fa-check"></i> Tandai dibaca</button>
                    </form>
                @endif
                @if(!empty($notification->data['url']))
                    <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                        @csrf
                        <button class="an-btn" type="submit"><i class="fa-regular fa-folder-open"></i> Buka</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.notifications.delete', $notification->id) }}" onsubmit="return confirm('Hapus notifikasi ini?');">
                    @csrf
                    @method('DELETE')
                    <button class="an-btn danger" type="submit"><i class="fa-regular fa-trash-can"></i> Hapus</button>
                </form>
            </div>
        </article>
    @empty
        <div class="an-empty">
            <i class="fa-regular fa-bell-slash"></i>
            <strong>Belum ada notifikasi</strong>
            <span>Notifikasi aktivitas admin akan muncul di sini.</span>
        </div>
    @endforelse
</div>

<div class="an-pagination">
    {{ $items->links() }}
</div>
@endsection
