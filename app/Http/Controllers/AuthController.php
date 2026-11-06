<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Affichage du formulaire de connexion
    |--------------------------------------------------------------------------
    */
    public function showLogin()
    {
        return view('auth.login');
    }

    /*
    |--------------------------------------------------------------------------
    | Connexion utilisateur
    |--------------------------------------------------------------------------
    | On valide les identifiants, on bloque les comptes inactifs
    | et on met à jour la dernière connexion après succès.
    */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Vérification préalable du compte
        |--------------------------------------------------------------------------
        | On vérifie l'existence du compte avant tentative d'authentification
        | afin de pouvoir traiter explicitement le cas des comptes inactifs.
        */
        $user = User::where('email', $validated['email'])->first();

        if ($user && !$user->is_active) {
            return back()
                ->withErrors([
                    'email' => 'Ce compte est désactivé.',
                ])
                ->onlyInput('email');
        }

        /*
        |--------------------------------------------------------------------------
        | Tentative de connexion
        |--------------------------------------------------------------------------
        | Le mode "remember" est pris en charge via la case à cocher.
        */
        if (Auth::attempt($validated, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /*
            | Mise à jour de la dernière connexion uniquement après authentification
            | réussie pour garder une trace fiable.
            */
            Auth::user()->update([
                'last_login_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors([
                'email' => 'Identifiants incorrects.',
            ])
            ->onlyInput('email');
    }

    /*
    |--------------------------------------------------------------------------
    | Affichage du formulaire d'inscription
    |--------------------------------------------------------------------------
    */
    public function showRegister()
    {
        return view('auth.register');
    }

    /*
    |--------------------------------------------------------------------------
    | Création de compte public
    |--------------------------------------------------------------------------
    | Toute inscription publique crée un compte client actif.
    */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],

            /*
            | Le hash est appliqué explicitement ici pour rendre la logique claire.
            */
            'password'  => Hash::make($validated['password']),

            /*
            | Une inscription publique ne doit jamais créer un agent ou un admin.
            */
            'role'      => User::ROLE_CLIENT,

            /*
            | Les comptes créés via l'inscription sont actifs par défaut.
            */
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Connexion immédiate après inscription
        |--------------------------------------------------------------------------
        | On connecte directement l'utilisateur puis on régénère la session.
        */
        Auth::login($user);
        $request->session()->regenerate();

        /*
        | On enregistre aussi la première connexion du compte fraîchement créé.
        */
        $user->update([
            'last_login_at' => now(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Compte créé avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | Déconnexion
    |--------------------------------------------------------------------------
    | On invalide la session puis on régénère le token CSRF.
    */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}