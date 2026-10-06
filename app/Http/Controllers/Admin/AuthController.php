<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** 5 essais ratés par e-mail et adresse IP → blocage 15 min ; 20 essais ratés par heure et par IP. */
    private const MAX_PER_ACCOUNT = 5;

    private const MAX_PER_IP = 20;

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()?->only('name', 'email')]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $accountKey = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        $ipKey = 'login-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($accountKey, self::MAX_PER_ACCOUNT) || RateLimiter::tooManyAttempts($ipKey, self::MAX_PER_IP)) {
            $wait = max(RateLimiter::availableIn($accountKey), RateLimiter::availableIn($ipKey));
            throw ValidationException::withMessages(['email' => 'Trop de tentatives. Réessayez dans '.max(1, (int) ceil($wait / 60)).' minute(s).'])->status(429);
        }

        // « Rester connecté » : cookie de connexion durable seulement si la case est cochée.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($accountKey, 15 * 60);
            RateLimiter::hit($ipKey, 60 * 60);
            Activity::log('Tentative de connexion échouée ('.Str::limit($credentials['email'], 60).', IP '.$request->ip().')');
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }
        RateLimiter::clear($accountKey);
        $request->session()->regenerate();
        Activity::log('Connexion à l’administration (IP '.$request->ip().')');

        return response()->json(['user' => Auth::user()->only('name', 'email')]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
