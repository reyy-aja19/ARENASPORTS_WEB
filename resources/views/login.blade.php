<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>

    <link rel="stylesheet" href="{{ asset('admin-ui.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .login-page {
            background: linear-gradient(135deg, #eaf7ee, #cfe8d5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .login-logo {
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            color: #167c3a;
        }

        .login-card {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            width: 320px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .login-btn {
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .login-btn:hover {
            transform: scale(1.03);
        }

        .error-box {
            background: #ffe5e5;
            color: #b30000;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 12px;
        }
    </style>
</head>

<body class="login-page">

<div class="login-wrap">

    <!-- LOGO -->
    <div class="login-logo">
        <div style="font-size:52px;">
            <i class="fa-regular fa-futbol"></i>
        </div>
        SPORTS FIELD<br>RENTAL
    </div>

    <!-- CARD -->
    <div class="login-card">

        {{-- ERROR --}}
        @if(session('error'))
            <div class="error-box">
                {{ session('error') }}
            </div>
        @endif

        {{-- FORM --}}
        <form action="{{ route('login.submit') }}" method="POST">
            @csrf

            <div class="form-group" style="text-align:left;">
                <label>Email / No HP</label>
                <input 
                    class="input-ui" 
                    type="text" 
                    name="email" 
                    value="{{ old('email') }}" 
                    placeholder="Masukkan email atau no hp"
                    required
                >
            </div>

            <div class="form-group" style="text-align:left;">
                <label>Password</label>
                <input 
                    class="input-ui" 
                    type="password" 
                    name="password" 
                    placeholder="Masukkan password"
                    required
                >
            </div>

            <button class="btn-ui btn-green login-btn" type="submit">
                MASUK
            </button>
        </form>

    </div>
</div>

</body>
</html>