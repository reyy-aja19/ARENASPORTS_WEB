
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password · ARENASPORTS</title>
    <link rel="stylesheet" href="{{ asset('user-ui.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: 'DM Sans', sans-serif;
        }
        .reset-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            padding: 36px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,.09);
        }
        .reset-card h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 26px;
            margin-bottom: 10px;
        }
        .reset-card p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 22px;
        }
        .reset-card label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .reset-card input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            margin-bottom: 18px;
        }
        .reset-card button {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 10px;
            background: #073e2b;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        .reset-back {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #073e2b;
            text-decoration: none;
            font-weight: 600;
        }
        .reset-alert {
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .reset-success { background: #dcfce7; color: #166534; }
        .reset-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <main class="reset-card">
        <h1>Lupa Password?</h1>
        <p>Masukkan alamat email yang terhubung dengan akun ARENASPORTS. Kami akan mengirimkan tautan untuk mengatur ulang password kamu.</p>

        @if (session('status'))
            <div class="reset-alert reset-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="reset-alert reset-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <label for="email">Alamat Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="nama@email.com"
                required
                autofocus
            >

            <button type="submit">Kirim Tautan Reset</button>
        </form>

        <a href="{{ route('login') }}" class="reset-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
        </a>
    </main>
</body>
</html>
