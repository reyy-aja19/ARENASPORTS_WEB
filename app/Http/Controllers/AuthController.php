<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('admin_logged_in')) return $this->redirectByRole($request);
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) return back()->withInput($request->only('email'))->with('error', 'Email atau password salah.');
        if ($user->status !== 'Aktif') return back()->withInput($request->only('email'))->with('error', 'Akun sedang nonaktif. Hubungi pengelola.');
        $request->session()->regenerate();
        $request->session()->put(['admin_logged_in' => true, 'admin_user_id' => $user->id, 'admin_role' => $user->role]);
        return $this->redirectByRole($request);
    }

    public function showRegister(Request $request)
    {
        if ($request->session()->get('admin_logged_in')) return $this->redirectByRole($request);
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],'phone'=>['nullable','string','max:50'],'password'=>['required','string','min:8','confirmed']]);
        $user = User::create(['name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone'] ?? null,'password'=>Hash::make($data['password']),'role'=>'user','status'=>'Aktif','points'=>0]);
        $request->session()->regenerate();
        $request->session()->put(['admin_logged_in'=>true,'admin_user_id'=>$user->id,'admin_role'=>'user']);
        return redirect()->route('user.home')->with('success','Akun berhasil dibuat. Selamat datang di ARENASPORTS!');
    }


    public function redirectToGoogle()
    {
        abort_if(!config('services.google.client_id') || !config('services.google.client_secret'), 503, 'Google OAuth belum dikonfigurasi di .env.');
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try { $google = Socialite::driver('google')->user(); }
        catch (\Throwable $e) { return redirect()->route('login')->with('error', 'Login Google gagal. Periksa konfigurasi OAuth dan callback URL.'); }
        if (!$google->getEmail()) return redirect()->route('login')->with('error', 'Google tidak memberikan alamat email.');
        $user = User::where('email', $google->getEmail())->first();
        if (!$user) $user = User::create(['name'=>$google->getName() ?: 'Pengguna ArenaSports','email'=>$google->getEmail(),'password'=>Hash::make(Str::random(40)),'role'=>'user','status'=>'Aktif','points'=>0]);
        if ($user->status !== 'Aktif') return redirect()->route('login')->with('error','Akun sedang nonaktif.');
        $request->session()->regenerate(); $request->session()->put(['admin_logged_in'=>true,'admin_user_id'=>$user->id,'admin_role'=>$user->role]);
        return $this->redirectByRole($request);
    }

    private function redirectByRole(Request $request)
    {
        return $request->session()->get('admin_role') === 'user' ? redirect()->route('user.home') : redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Kamu berhasil keluar dari akun.');
    }
}
