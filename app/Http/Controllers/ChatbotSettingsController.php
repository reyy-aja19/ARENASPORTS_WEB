<?php
namespace App\Http\Controllers;
use App\Models\ChatbotSetting; use Illuminate\Http\Request; use Illuminate\Http\RedirectResponse; use Illuminate\View\View;
class ChatbotSettingsController extends Controller {
 public function edit(): View { $settings=ChatbotSetting::first() ?? new ChatbotSetting(['provider'=>'openai','model'=>'gpt-4o-mini','is_enabled'=>false]); return view('admin.chatbot-settings',compact('settings')); }
 public function update(Request $r): RedirectResponse { $data=$r->validate(['provider'=>['required','in:openai,custom'],'model'=>['required','string','max:100'],'system_prompt'=>['nullable','string','max:10000'],'is_enabled'=>['nullable','boolean']]); $data['is_enabled']=$r->boolean('is_enabled'); $setting=ChatbotSetting::first(); if($setting) $setting->update($data); else ChatbotSetting::create($data); return back()->with('success','Pengaturan chatbot disimpan. Pastikan API key di .env sudah dikonfigurasi.'); }
}
