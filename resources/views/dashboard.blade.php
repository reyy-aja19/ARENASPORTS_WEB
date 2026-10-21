<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="0;url={{ route('admin.dashboard') }}">
<title>Redirect Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card p-4 shadow-sm">
        <h3>Dashboard lama sudah dipindahkan</h3>
        <p>Silakan buka dashboard admin versi baru.</p>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-success">Buka Dashboard Admin</a>
    </div>
</div>
</body>
</html>
