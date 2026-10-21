<?php
session_start();
include('includes/config.php');

$error = "";

if($_SERVER['REQUEST_METHOD']=='POST'){
    $nama     = trim($_POST['nama']);
$email    = trim($_POST['email']);  // <- ini diperbaiki
$no_hp    = trim($_POST['no_hp']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role     = "user";

    // cek email atau no hp sudah terdaftar
    $cek = mysqli_query($conn,"
        SELECT id_user FROM user 
        WHERE email='$email' OR no_hp='$no_hp'
    ");

    if(mysqli_num_rows($cek) > 0){
        $error = "Email atau No HP sudah terdaftar!";
    } else {
        mysqli_query($conn,"
            INSERT INTO user (nama, email, no_hp, password, role)
            VALUES ('$nama', '$email', '$no_hp', '$password', '$role')
        ");
        header("Location: login.php?success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi | Penyewaan Lapangan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    min-height:100vh;
    background:
        linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
        url('https://i.pinimg.com/736x/0c/ba/7c/0cba7c24fdf17ea9e8ff29cc738fe378.jpg');
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    font-family: 'Segoe UI', sans-serif;
}
.register-container{
    width:100%;
    max-width:420px;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(12px);
    border-radius:16px;
    padding:30px;
    box-shadow:0 20px 40px rgba(0,0,0,0.45);
    color:#fff;
}
.form-control{ border-radius:10px; }
.form-control:focus{ border-color:#28a745; box-shadow:none; }
.btn-success{ border-radius:10px; padding:10px; font-size:16px; }
a{ color:#9effc1; }
a:hover{ color:#caffdf; }
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="register-container">
    <h4 class="text-center mb-4 fw-bold">Registrasi Akun</h4>

    <?php if($error): ?>
        <div class="alert alert-danger text-center py-2">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">No HP</label>
            <input 
                type="text" 
                name="no_hp" 
                class="form-control" 
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success w-100 fw-semibold">
            Daftar
        </button>
    </form>

    <div class="text-center mt-3">
        <p class="mb-0">
            Sudah punya akun?
            <a href="login.php" class="fw-semibold text-decoration-none">Login</a>
        </p>
    </div>
</div>

</body>
</html>
