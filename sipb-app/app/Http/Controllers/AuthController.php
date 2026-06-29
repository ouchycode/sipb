<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $data['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $throttleKey = $this->throttleKey($request, $login);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()
                ->with('error', $this->lockoutMessage(RateLimiter::availableIn($throttleKey)))
                ->onlyInput('login');
        }

        if (! Auth::attempt([$field => $login, 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300);

            return back()
                ->with('error', 'Email/Username atau password tidak sesuai.')
                ->onlyInput('login');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended('/admin')->with('success', 'Berhasil masuk sebagai admin.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Sesi admin sudah keluar.');
    }

    private function throttleKey(Request $request, string $login): string
    {
        return Str::lower($login).'|'.$request->ip();
    }

    private function lockoutMessage(int $seconds): string
    {
        $wait = $seconds >= 60
            ? ceil($seconds / 60).' menit'
            : $seconds.' detik';

        return "Terlalu banyak percobaan login. Coba lagi dalam {$wait}.";
    }
}
