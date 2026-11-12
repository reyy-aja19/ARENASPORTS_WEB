
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Password Baru · ARENASPORTS</title>
    <link rel="stylesheet" href="{{ asset('user-ui.css') }}">
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
        .reset-alert {
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 14px;
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <main class="reset-card">
        <h1>Atur Password Baru</h1>
        <p>Buat password baru untuk mengamankan akun ARENASPORTS kamu.</p>

        @if ($errors->any())
            <div class="reset-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Alamat Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $email) }}"
                required
                readonly
            >

            <label for="password">Password Baru</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Minimal 8 karakter"
                minlength="8"
                required
                autocomplete="new-password"
            >

            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Ulangi password baru"
                minlength="8"
                required
                autocomplete="new-password"
            >

            <button type="submit">Simpan Password Baru</button>
        </form>
    </main>
</body>
</html>
