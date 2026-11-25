<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FonnteWhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request, FonnteWhatsAppService $fonnte): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:0'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'contact.required' => 'Email atau nomor WhatsApp wajib diisi.',
            'message.required' => 'Pesan wajib diisi.',
        ]);

        // Honeypot: bot mengisi field tersembunyi.
        if ($request->filled('website')) {
            return back()->with('contact_success', 'Pesan Anda sudah diterima. Terima kasih.');
        }

        $superadmins = User::where('role', 'superadmin')->get();
        $targetEmail = $superadmins->pluck('email')->filter()->first()
            ?: config('mail.from.address');

        $subject = '[ArenaSports Kontak] Pesan dari ' . $validated['name'];
        $body = "Pesan kontak baru dari website ArenaSports\n\n"
            . "Nama: {$validated['name']}\n"
            . "Kontak: {$validated['contact']}\n"
            . "Halaman: " . url()->previous() . "\n"
            . "Waktu: " . now()->format('d M Y H:i:s') . "\n\n"
            . "Pesan:\n{$validated['message']}\n";

        $emailSent = false;
        $waSent = false;

        if ($targetEmail) {
            try {
                Mail::raw($body, function ($mail) use ($targetEmail, $subject) {
                    $mail->to($targetEmail)->subject($subject);
                });
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::warning('Contact email failed', ['error' => $e->getMessage()]);
            }
        }

        $superadminPhone = $superadmins->pluck('phone')->filter()->first();
        if ($superadminPhone && $fonnte->configured()) {
            try {
                $waMessage = "Pesan Kontak ArenaSports\n"
                    . "Nama: {$validated['name']}\n"
                    . "Kontak: {$validated['contact']}\n"
                    . "Pesan: {$validated['message']}";
                $fonnte->send($superadminPhone, $waMessage);
                $waSent = true;
            } catch (\Throwable $e) {
                Log::warning('Contact WA failed', ['error' => $e->getMessage()]);
            }
        }

        if (! $emailSent && ! $waSent) {
            Log::info('Contact message received but no delivery channel configured', [
                'name' => $validated['name'],
                'contact' => $validated['contact'],
                'message' => $validated['message'],
            ]);

            return back()
                ->withInput()
                ->with('contact_error', 'Pesan belum dapat dikirim saat ini. Silakan hubungi WhatsApp/email ArenaSports langsung.');
        }

        return back()->with('contact_success', 'Terima kasih! Pesan Anda sudah dikirim ke tim ArenaSports.');
    }
}
