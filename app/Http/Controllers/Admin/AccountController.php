<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Compte administrateur : nom, e-mail et mot de passe (le mot de passe actuel est exigé). */
class AccountController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'required|current_password',
            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $passwordChanged = $user->isDirty('password');
        $user->save();

        if ($passwordChanged) {
            // Les autres sessions ouvertes avec l'ancien mot de passe sont fermées.
            Auth::logoutOtherDevices($data['password']);
            $request->session()->regenerate();
        }
        Activity::log($passwordChanged ? 'Mot de passe modifié' : 'Compte modifié');

        return response()->json(['user' => $user->only('name', 'email')]);
    }
}
