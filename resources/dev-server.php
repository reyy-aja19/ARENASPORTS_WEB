<?php

/*
 * Router pengembangan untuk server statis Laravel.
 *
 * Tanpa header `Access-Control-Allow-Origin`, renderer CanvasKit pada
 * aplikasi Flutter Web tidak dapat meng-cat gambar lintas-asal dari
 * server ini, sehingga seluruh gambar aplikasi tidak tampil di browser.
 *
 * Router ini men-stream file yang benar-benar ada langsung dari
 * `public/` dan menyertakan header CORS. Rute aplikasi (Laravel) tetap
 * ditangani framework seperti biasa, sehingga CORS API tidak dobel.
 *
 * Cara pakai dari root proyek web:
 *   php -S 127.0.0.1:8000 -t public resources/dev-server.php
 */

$publicPath = realpath(dirname(__DIR__).DIRECTORY_SEPARATOR.'public');

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''
);

// Cegah path traversal eksplisit.
if ($uri !== '/' && strpos($uri, '..') !== false) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

if ($uri !== '/' && $publicPath !== false) {
    $file = $publicPath.$uri;

    if (is_file($file)) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'json' => 'application/json',
            'js' => 'text/javascript',
            'css' => 'text/css',
            'html' => 'text/html',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            default => mime_content_type($file) ?: 'application/octet-stream',
        };

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: '.$mime);
        header('Content-Length: '.filesize($file));
        header('Cache-Control: public, max-age=604800');
        readfile($file);

        return true; // Respons selesai di sini; aplikasi tidak dijalankan.
    }
}

require $publicPath.DIRECTORY_SEPARATOR.'index.php';
