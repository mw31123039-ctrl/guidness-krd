<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth', ['mode' => 'login']);
    }

    public function registerPage()
    {
        return Inertia::render('Auth', ['mode' => 'register']);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['boolean']]);
        $key = 'login:'.hash('sha256', strtolower($credentials['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => __('roadcare.throttled', ['seconds' => RateLimiter::availableIn($key)])]);
        }
        if (! Auth::attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => __('roadcare.invalid_login')]);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->intended($request->user()->role === 'government' ? '/government' : '/dashboard');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'lowercase', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->symbols()],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['required', Rule::in(config('roadcare.cities'))],
        ]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
