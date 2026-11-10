@extends('layouts.user')

@section('content')
<style>
.om-chat{max-width:940px;margin:0 auto;background:#fff;border:1px solid #dcebe1;border-radius:22px;overflow:hidden;color:#20352a;box-shadow:0 16px 40px rgba(13,81,55,.09);position:relative}
.om-head{padding:20px 24px;background:linear-gradient(115deg,#0d5137,#178653);color:#fff;display:flex;justify-content:space-between;gap:12px;align-items:center;position:relative;overflow:hidden}
.om-head:after{content:"⚽";position:absolute;right:110px;top:-24px;font-size:100px;opacity:.1}
.om-head h2{margin:0;color:#fff;font-size:20px;position:relative;z-index:1}
.om-head p{margin:6px 0 0;color:#d8eee0;font-size:12px;position:relative;z-index:1}
.om-back{color:#fff;text-decoration:none;font-size:12px;font-weight:800;position:relative;z-index:1}
.om-messages{height:55vh;min-height:340px;max-height:640px;overflow:auto;padding:22px;background:radial-gradient(circle at 10% 10%,#e9f8ef 0,transparent 28%),#f7faf8;display:flex;flex-direction:column;gap:13px}
.om-msg{max-width:78%;align-self:flex-start;background:#fff;border:1px solid #e1ece4;border-radius:15px 15px 15px 4px;padding:11px 14px;box-shadow:0 3px 9px #193c250c}
.om-msg.mine{align-self:flex-end;background:#e5f5e9;border-color:#cce8d4;border-radius:15px 15px 4px 15px}
.om-meta{display:flex;gap:14px;justify-content:space-between;font-size:10px;color:#718078;margin-bottom:6px}
.om-text{font-size:13px;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
.om-attachment{display:block;max-width:min(360px,100%);max-height:300px;object-fit:contain;border-radius:11px;margin-top:8px;border:1px solid #dce9df;background:#fff}
.om-form{display:flex;gap:10px;padding:15px;border-top:1px solid #e8eee9;align-items:end;flex-wrap:wrap;background:#fff}
.om-form textarea{flex:1 1 300px;resize:vertical;min-height:46px;max-height:130px;border:1px solid #d9e4dc;border-radius:12px;padding:12px;font:inherit;font-size:13px}
.om-file{display:flex;align-items:center;gap:8px;padding:11px 13px;background:#eaf7ee;border:1px solid #cfe8d7;border-radius:11px;color:#145c3c;font-weight:700;font-size:12px;cursor:pointer}
.om-file input{max-width:160px;font-size:11px}
.om-form button{border:0;border-radius:11px;background:#178653;color:white;padding:13px 19px;font-weight:800;cursor:pointer}
.om-form button:hover{background:#0d5137}
.om-hint{width:100%;font-size:11px;color:#829087;margin:0}
.om-empty{text-align:center;color:#829087;margin:auto;font-size:13px}
@media(max-width:600px){.om-head{padding:16px}.om-messages{height:58vh;padding:13px}.om-msg{max-width:92%}.om-form{padding:10px}.om-form button{padding:12px 14px}.om-file{width:100%;justify-content:space-between}}
</style>

<div class="om-chat">
    <header class="om-head">
        <div>
            <h2><i class="fa-solid fa-comments"></i> Obrolan Open Match</h2>
            <p>{{ $openMatch->title }} · {{ $openMatch->tanggal?->format('d M Y') }} · {{ $openMatch->start_time }}–{{ $openMatch->end_time }}</p>
        </div>
        <a class="om-back" href="{{ route('user.openmatches') }}">Kembali</a>
    </header>

    <div class="om-messages" id="omMessages">
        @forelse($messages as $message)
            <div class="om-msg {{ (int)$message->user_id === (int)$user->id ? 'mine' : '' }}" data-id="{{ $message->id }}">
                <div class="om-meta">
                    <strong>{{ $message->user?->name ?? 'Pemain' }}</strong>
                    <span>{{ $message->created_at?->format('H:i') }}</span>
                </div>
                @if($message->message)
                    <div class="om-text">{{ $message->message }}</div>
                @endif
                @if($message->attachment_path)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->attachment_path) }}" target="_blank" rel="noopener">
                        <img class="om-attachment" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->attachment_path) }}" alt="Foto dari {{ $message->user?->name ?? 'Pemain' }}" loading="lazy">
                    </a>
                @endif
            </div>
        @empty
            <div class="om-empty" id="omEmpty">Belum ada pesan. Mulai obrolan dengan menyapa pemain lain!</div>
        @endforelse
    </div>

    <form class="om-form" method="POST" enctype="multipart/form-data" action="{{ route('user.openmatches.chat.send', $openMatch) }}">
        @csrf
        <textarea name="message" maxlength="2000" placeholder="Tulis pesan untuk pemain...">{{ old('message') }}</textarea>
        <label class="om-file"><i class="fa-regular fa-image"></i> Kirim foto
            <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp">
        </label>
        <button type="submit"><i class="fa-solid fa-paper-plane"></i> Kirim</button>
        <p class="om-hint">Format JPG, PNG, WEBP · Maksimal 5 MB. Bisa kirim pesan, foto, atau keduanya.</p>
        @error('attachment')<p class="om-hint" style="color:#c0392b">{{ $message }}</p>@enderror
        @error('message')<p class="om-hint" style="color:#c0392b">{{ $message }}</p>@enderror
    </form>
</div>

<script>
(() => {
    const box = document.getElementById('omMessages');
    if (!box) return;
    let lastId = Math.max(0, ...Array.from(box.querySelectorAll('[data-id]')).map(el => Number(el.dataset.id)));
    box.scrollTop = box.scrollHeight;

    async function refresh() {
        try {
            const response = await fetch(@json(route('user.openmatches.chat.messages', $openMatch)) + '?after=' + lastId, {headers:{'Accept':'application/json'}});
            if (!response.ok) return;
            const rows = await response.json();
            rows.forEach(message => {
                const el = document.createElement('div');
                el.className = 'om-msg' + (Number(message.user_id) === Number(@json($user->id)) ? ' mine' : '');
                el.dataset.id = message.id;
                const meta = document.createElement('div'); meta.className = 'om-meta';
                const name = document.createElement('strong'); name.textContent = message.user_name;
                const time = document.createElement('span'); time.textContent = message.created_at;
                meta.append(name,time); el.append(meta);
                if (message.message) { const body=document.createElement('div'); body.className='om-text'; body.textContent=message.message; el.append(body); }
                if (message.attachment_url) {
                    const link=document.createElement('a'); link.href=message.attachment_url; link.target='_blank'; link.rel='noopener';
                    const photo=document.createElement('img'); photo.className='om-attachment'; photo.src=message.attachment_url; photo.alt='Foto dari '+message.user_name; link.append(photo); el.append(link);
                }
                document.getElementById('omEmpty')?.remove();
                box.append(el); lastId=Math.max(lastId,Number(message.id));
            });
            if(rows.length) box.scrollTop=box.scrollHeight;
        } catch(error) { console.error('Gagal mengambil pesan:',error); }
    }
    window.setInterval(refresh,4000);
})();
</script>
@endsection
