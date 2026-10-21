<?php
session_start();

/* ===============================
   LOGOUT ADMIN
================================ */
if (isset($_GET['admin'])) {

    $_SESSION = [];
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

/* ===============================
   LOGOUT USER
================================ */

// hapus semua session user
$_SESSION = [];
session_unset();
session_destroy();

// hapus cookie session (penting)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

header("Location: index.php");
exit;
