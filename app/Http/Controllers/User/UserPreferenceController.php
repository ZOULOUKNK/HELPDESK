<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserPreferenceController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $settings = [
            'locale' => UserSetting::getForUser($user->id, 'locale', 'fr'),
            'timezone' => UserSetting::getForUser($user->id, 'timezone', 'Europe/Paris'),
            'date_format' => UserSetting::getForUser($user->id, 'date_format', 'd/m/Y'),
            'theme' => UserSetting::getForUser($user->id, 'theme', 'light'),
            'accent_color' => UserSetting::getForUser($user->id, 'accent_color', '#334155'),
            'compact_mode' => UserSetting::getForUser($user->id, 'compact_mode', '0'),
            'tickets_per_page' => UserSetting::getForUser($user->id, 'tickets_per_page', '15'),
            'home_page' => UserSetting::getForUser($user->id, 'home_page', 'dashboard'),
        ];

        return view('user.preferences.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'timezone' => ['required', 'string', 'max:100'],
            'date_format' => ['required', 'string', 'max:50'],
            'theme' => ['required', Rule::in(['light', 'dark'])],
            'accent_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'compact_mode' => ['nullable', 'boolean'],
            'tickets_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'home_page' => ['required', Rule::in(['dashboard', 'tickets.index'])],
        ]);

        $userId = Auth::id();

        UserSetting::setForUser($userId, 'locale', $validated['locale']);
        UserSetting::setForUser($userId, 'timezone', $validated['timezone']);
        UserSetting::setForUser($userId, 'date_format', $validated['date_format']);
        UserSetting::setForUser($userId, 'theme', $validated['theme']);
        UserSetting::setForUser($userId, 'accent_color', $validated['accent_color']);
        UserSetting::setForUser($userId, 'compact_mode', $request->boolean('compact_mode'));
        UserSetting::setForUser($userId, 'tickets_per_page', $validated['tickets_per_page']);
        UserSetting::setForUser($userId, 'home_page', $validated['home_page']);

        return back()->with('success', 'Préférences utilisateur enregistrées.');
    }
}