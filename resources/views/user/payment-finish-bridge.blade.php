<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran - ArenaSports</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#f5f7fa; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { background:#fff; max-width:520px; width:100%; border-radius:16px; padding:28px; box-shadow:0 8px 30px rgba(0,0,0,.06); text-align:center; }
        .icon { width:64px; height:64px; border-radius:50%; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; font-size:30px; }
        .icon.pending { background:#fff7ed; color:#ea580c; }
        .icon.error { background:#fef2f2; color:#dc2626; }
        h1 { font-size:22px; margin:0 0 10px; color:#111827; }
        p { margin:0 0 18px; color:#4b5563; line-height:1.6; }
        .actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
        .btn { display:inline-block; padding:11px 16px; border-radius:10px; text-decoration:none; font-weight:600; font-size:14px; }
        .btn-primary { background:#00A32A; color:#fff; }
        .btn-secondary { background:#eef2f7; color:#111827; }
        .muted { font-size:12px; color:#6b7280; margin-top:16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon {{ $pending ? 'pending' : 'error' }}">{{ $pending ? '...' : '!' }}</div>
        <h1>{{ $pending ? 'Pembayaran Sedang Diproses' : 'Status Pembayaran' }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ $loginUrl }}">Login ke ArenaSports</a>
            <a class="btn btn-secondary" href="{{ url('/') }}">Ke Beranda</a>
        </div>
        <p class="muted">Jika kamu membuka halaman ini dari aplikasi ArenaSports, kamu bisa kembali ke aplikasi dan menunggu status diperbarui otomatis.</p>
    </div>
</body>
</html>
