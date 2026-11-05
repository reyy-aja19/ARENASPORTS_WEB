<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteWhatsAppService
{
    public function configured(): bool
    {
        return filled(config('services.fonnte.token'));
    }

    public function send(string $target, string $message): void
    {
        $token = config('services.fonnte.token');

        if (!$token) {
            throw new RuntimeException('Fonnte belum dikonfigurasi. Isi FONNTE_TOKEN di file .env.');
        }

        $response = Http::timeout(20)
            ->withHeaders(['Authorization' => $token])
            ->asForm()
            ->post(config('services.fonnte.endpoint', 'https://api.fonnte.com/send'), [
                'target' => $target,
                'message' => $message,
            ]);

        if (!$response->successful() || !$response->json('status')) {
            throw new RuntimeException('WhatsApp gagal dikirim melalui Fonnte: '.($response->json('reason') ?? $response->body()));
        }
    }

    public function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }
}
