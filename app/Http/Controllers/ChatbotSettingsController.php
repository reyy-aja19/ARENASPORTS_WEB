<?php

namespace App\Http\Controllers;

use App\Models\ChatbotSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = ChatbotSetting::first() ?? new ChatbotSetting([
            'provider' => 'gemini',
            'model' => config('services.gemini.model', 'gemini-flash-lite-latest'),
            'is_enabled' => true,
        ]);

        return view('admin.chatbot-settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:gemini,openai,custom'],
            'model' => ['required', 'string', 'max:100'],
            'system_prompt' => ['nullable', 'string', 'max:10000'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $setting = ChatbotSetting::first();

        if ($setting) {
            $setting->update($data);
        } else {
            ChatbotSetting::create($data);
        }

        return back()->with('success', 'Pengaturan chatbot disimpan. Pastikan GEMINI_API_KEY di .env sudah dikonfigurasi.');
    }
}
