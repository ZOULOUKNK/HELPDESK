<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Liste des utilisateurs
    |--------------------------------------------------------------------------
    | Cette page sert à l'administration des comptes :
    | recherche, filtres, statistiques, suivi rapide.
    */
    public function index(Request $request)
    {
        $query = User::withCount([
            'tickets as tickets_count',
            'assignedTickets as assigned_count',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Recherche texte
        |--------------------------------------------------------------------------
        | On recherche sur les champs les plus utiles côté administration.
        */
        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtres simples
        |--------------------------------------------------------------------------
        | - role   : admin / agent / client
        | - status : actif / inactif
        */
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        /*
        |--------------------------------------------------------------------------
        | Ordre d'affichage
        |--------------------------------------------------------------------------
        | On garde un tri stable : rôle puis nom.
        */
        $users = $query
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistiques globales
        |--------------------------------------------------------------------------
        | Utiles pour les cartes de synthèse sur la page admin.
        */
        $stats = [
            'total'    => User::count(),
            'admins'   => User::where('role', User::ROLE_ADMIN)->count(),
            'agents'   => User::where('role', User::ROLE_AGENT)->count(),
            'clients'  => User::where('role', User::ROLE_CLIENT)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /*
    |--------------------------------------------------------------------------
    | Formulaire de création
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('admin.users.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Création d'un utilisateur
    |--------------------------------------------------------------------------
    | On valide les champs de base puis on crée un compte complet.
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'confirmed', 'min:8'],
            'role'       => ['required', Rule::in([
                User::ROLE_CLIENT,
                User::ROLE_AGENT,
                User::ROLE_ADMIN,
            ])],
            'department' => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'bio'        => ['nullable', 'string', 'max:500'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],

            /*
            | Le modèle User possède déjà un cast "hashed" sur password.
            | Hash::make reste conservé ici pour un comportement explicite.
            */
            'password'   => Hash::make($validated['password']),

            'role'       => $validated['role'],
            'department' => $validated['department'] ?? null,
            'phone'      => $validated['phone'] ?? null,
            'bio'        => $validated['bio'] ?? null,

            /*
            | Par défaut, un compte créé depuis l'admin est actif sauf si
            | l'interface demande explicitement le contraire.
            */
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | Formulaire d'édition
    |--------------------------------------------------------------------------
    | On prépare aussi quelques statistiques liées à l'utilisateur.
    */
    public function edit(User $user)
    {
        $ticketStats = [
            'total' => $user->tickets()->count(),

            'open' => $user->tickets()
                ->whereNotIn('status', Ticket::RESOLVED_STATUSES)
                ->count(),

            'resolved' => $user->tickets()
                ->whereIn('status', Ticket::RESOLVED_STATUSES)
                ->count(),

            'assigned' => $user->assignedTickets()
                ->whereNotIn('status', Ticket::RESOLVED_STATUSES)
                ->count(),

            'last_login' => $user->last_login_at,
        ];

        return view('admin.users.edit', compact('user', 'ticketStats'));
    }

    /*
    |--------------------------------------------------------------------------
    | Mise à jour d'un utilisateur
    |--------------------------------------------------------------------------
    | On conserve le compte existant et on met à jour uniquement
    | les champs autorisés.
    */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'       => ['required', Rule::in([
                User::ROLE_CLIENT,
                User::ROLE_AGENT,
                User::ROLE_ADMIN,
            ])],
            'department' => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'bio'        => ['nullable', 'string', 'max:500'],
            'is_active'  => ['nullable', 'boolean'],
            'password'   => ['nullable', 'confirmed', 'min:8'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Protection métier : ne pas rétrograder le dernier admin actif
        |--------------------------------------------------------------------------
        | On empêche la perte du dernier administrateur actif du système.
        */
        if (
            $user->isAdmin()
            && $user->is_active
            && ($validated['role'] !== User::ROLE_ADMIN || !$request->boolean('is_active'))
        ) {
            $activeAdmins = User::where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeAdmins <= 1) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'role' => 'Impossible de modifier ou désactiver le dernier administrateur actif.',
                    ]);
            }
        }

        $data = [
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'department' => $validated['department'] ?? null,
            'phone'      => $validated['phone'] ?? null,
            'bio'        => $validated['bio'] ?? null,

            /*
            | Sans case cochée, boolean() retourne false.
            | Le comportement est volontaire ici pour permettre la désactivation.
            */
            'is_active'  => $request->boolean('is_active'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Mise à jour du mot de passe uniquement si fourni
        |--------------------------------------------------------------------------
        */
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur {$user->name} mis à jour.");
    }

    /*
    |--------------------------------------------------------------------------
    | Activer / désactiver un compte
    |--------------------------------------------------------------------------
    | On protège le dernier administrateur actif.
    */
    public function toggleActive(User $user)
    {
        /*
        | Empêcher la désactivation du dernier admin actif.
        */
        if ($user->isAdmin() && $user->is_active) {
            $activeAdmins = User::where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeAdmins <= 1) {
                return back()->withErrors([
                    'error' => 'Impossible de désactiver le dernier administrateur actif.',
                ]);
            }
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Compte de {$user->name} {$status}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Réinitialisation du mot de passe
    |--------------------------------------------------------------------------
    | Action directe depuis l'administration.
    */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe réinitialisé avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | Suppression d'un utilisateur
    |--------------------------------------------------------------------------
    | Règles de sécurité :
    | - impossible de supprimer son propre compte
    | - impossible de supprimer le dernier administrateur
    */
    public function destroy(User $user)
    {
        /*
        | Un administrateur ne doit pas pouvoir supprimer son propre compte
        | depuis l'interface d'administration.
        */
        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'error' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ]);
        }

        /*
        | Protection du dernier administrateur.
        | Ici on compte tous les admins car la suppression retire totalement le compte.
        */
        if ($user->isAdmin()) {
            $adminCount = User::where('role', User::ROLE_ADMIN)->count();

            if ($adminCount <= 1) {
                return back()->withErrors([
                    'error' => 'Impossible de supprimer le dernier administrateur.',
                ]);
            }
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur {$name} supprimé.");
    }
}