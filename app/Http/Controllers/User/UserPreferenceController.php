<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| Page « Mon compte »
|--------------------------------------------------------------------------
| Chaque utilisateur, quel que soit son rôle, gère ici son profil, son mot
| de passe et ses préférences d'affichage. Seules les préférences
| réellement appliquées par l'application sont proposées.
*/
class UserPreferenceController extends Controller
{
    /** Fuseaux proposés (les mêmes que dans les réglages globaux). */
    public const TIMEZONES = [
        'Indian/Antananarivo' => 'Madagascar (Antananarivo)',
        'Europe/Paris' => 'France (Paris)',
        'UTC' => 'UTC',
        'Europe/London' => 'Royaume-Uni (Londres)',
        'America/New_York' => 'États-Unis (New York)',
    ];

    /** Pages d'accueil possibles après connexion. */
    public const HOME_PAGES = [
        'dashboard' => 'Tableau de bord',
        'tickets.index' => 'Liste des tickets',
    ];

    public function edit()
    {
        $user = Auth::user();

        $settings = [
            'timezone' => UserSetting::getForUser($user->id, 'timezone', Setting::get('app.timezone', 'Europe/Paris')),
            'accent_color' => UserSetting::getForUser($user->id, 'accent_color', Setting::get('ui.color_primary', '#334155')),
            'tickets_per_page' => UserSetting::getForUser($user->id, 'tickets_per_page', Setting::get('ticket.per_page', '15')),
            'home_page' => self::homeRouteFor($user),
        ];

        return view('user.preferences.edit', [
            'user' => $user,
            'settings' => $settings,
            'timezones' => self::TIMEZONES,
            'homePages' => self::HOME_PAGES,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'timezone' => ['required', Rule::in(array_keys(self::TIMEZONES))],
            'accent_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'tickets_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'home_page' => ['required', Rule::in(array_keys(self::HOME_PAGES))],
        ]);

        foreach ($validated as $key => $value) {
            UserSetting::setForUser(Auth::id(), $key, $value);
        }

        return back()->with('success', 'Préférences enregistrées.');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        // Rôle, e-mail et statut restent gérés par l'administration.
        Auth::user()->update($validated);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        Auth::user()->update(['password' => Hash::make($request->password)]);

        // Les autres sessions ouvertes avec l'ancien mot de passe sont fermées.
        Auth::logoutOtherDevices($request->password);

        return back()->with('success', 'Mot de passe modifié.');
    }

    /**
     * Route de la page d'accueil choisie par l'utilisateur (après
     * connexion et sur « / »).
     */
    public static function homeRouteFor(?User $user): string
    {
        if (!$user) {
            return 'dashboard';
        }

        $home = UserSetting::getForUser($user->id, 'home_page', 'dashboard');

        return array_key_exists($home, self::HOME_PAGES) ? $home : 'dashboard';
    }
}
