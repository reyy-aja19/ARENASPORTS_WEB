<?php

namespace App\Services;

use App\Models\ChatbotSetting;
use App\Models\Lapangan;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatbotService
{
    public function reply(string $message, ?float $lat = null, ?float $lng = null): string
    {
        $setting = ChatbotSetting::first();
        if ($setting && ! $setting->is_enabled) {
            return 'Maaf, asisten AI sedang tidak aktif. Silakan hubungi admin.';
        }

        $apiKey = (string) config('services.gemini.api_key');
        if ($apiKey === '') {
            return 'Maaf, layanan AI belum dikonfigurasi. Tambahkan GEMINI_API_KEY di file .env.';
        }

        $configuredModel = $setting?->model ?: (string) config('services.gemini.model', 'gemini-flash-lite-latest');
        $configuredModel = preg_replace('/[^a-zA-Z0-9._-]/', '', $configuredModel) ?: 'gemini-flash-lite-latest';

        $systemPrompt = trim((string) ($setting?->system_prompt ?? ''));
        if ($systemPrompt === '') {
            $systemPrompt = 'Anda adalah Arena Assistant, asisten resmi ArenaSports. Jawab dengan ramah, jelas, singkat, dan dalam Bahasa Indonesia. Bantu pengguna soal booking lapangan, pembayaran, venue, poin, open match, event, dan akun. Jika tidak yakin, sarankan pengguna menghubungi admin/venue terkait.';
        }

        $context = $this->buildContext($lat, $lng);
        $prompt = $systemPrompt . "\n\nKonteks data ArenaSports saat ini:\n" . $context . "\n\nPesan pengguna: " . $message . "\n\nJawab secara membantu dan ringkas.";

        $models = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-flash-lite-latest',
            'gemini-flash-latest',
            'gemini-3.5-flash-lite',
            'gemini-3.5-flash',
            'gemini-2.5-flash',
        ])));

        $lastError = null;
        foreach ($models as $model) {
            $result = $this->callGemini($apiKey, $model, $prompt);
            if ($result['ok'] === true) {
                return $result['text'];
            }
            $lastError = $result;
            $status = (int) ($result['status'] ?? 0);
            // Retry next model for not-found, unavailable, rate limit, or timeout/network errors.
            if (in_array($status, [404, 429, 503, 0], true)) {
                Log::warning('Gemini model unavailable, trying fallback', ['model' => $model, 'status' => $status]);
                continue;
            }
            break;
        }

        $status = (int) ($lastError['status'] ?? 0);
        $body = (string) ($lastError['body'] ?? '');
        Log::warning('Gemini chatbot error', ['status' => $status, 'body' => $body, 'model' => $configuredModel]);

        if ($status === 400 && str_contains($body, 'API_KEY_INVALID')) {
            return 'Maaf, API key Gemini tidak valid. Silakan periksa GEMINI_API_KEY di pengaturan server.';
        }
        if ($status === 404) {
            return 'Maaf, model Gemini yang dikonfigurasi tidak tersedia. Coba ganti model di Pengaturan Chatbot Admin (contoh: gemini-flash-lite-latest atau gemini-flash-latest).';
        }

        return 'Maaf, asisten AI sedang mengalami gangguan. Coba lagi sebentar ya.';
    }

    private function callGemini(string $apiKey, string $model, string $prompt): array
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = Http::timeout(20)->acceptJson()->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 500,
                ],
            ]);

            if (! $response->successful()) {
                return ['ok' => false, 'status' => $response->status(), 'body' => $response->body()];
            }

            $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));
            if ($text === '') {
                return ['ok' => false, 'status' => 200, 'body' => 'empty_text'];
            }

            return ['ok' => true, 'text' => $text];
        } catch (\Throwable $e) {
            Log::error('Gemini chatbot exception', ['message' => $e->getMessage(), 'model' => $model]);
            return ['ok' => false, 'status' => 0, 'body' => $e->getMessage()];
        }
    }

    private function buildContext(?float $lat, ?float $lng): string
    {
        $lines = [];
        $courts = Lapangan::with(['venue', 'priceRules'])->bookable()->orderBy('harga')->take(8)->get();
        if ($courts->isNotEmpty()) {
            $lines[] = 'Lapangan tersedia:';
            foreach ($courts as $c) {
                $lines[] = '- ' . $c->nama . ' (' . $c->jenis . ') - Rp ' . number_format((int) $c->harga, 0, ',', '.') . '/jam, venue: ' . ($c->venue?->name ?? '-') . ', lokasi: ' . ($c->lokasi ?? '-');
            }
        }

        $venues = Venue::where('status', 'Aktif')->take(8)->get(['id', 'name', 'city', 'address']);
        if ($venues->isNotEmpty()) {
            $lines[] = 'Venue aktif:';
            foreach ($venues as $v) {
                $lines[] = '- ' . $v->name . ' (' . ($v->city ?? '-') . ') ' . ($v->address ?? '');
            }
        }

        $lines[] = 'Fitur: booking lapangan, pembayaran Midtrans/tunai, poin reward (10 poin per transaksi berhasil), open match, event/turnamen, daftarkan venue.';
        return implode("\n", $lines);
    }
}