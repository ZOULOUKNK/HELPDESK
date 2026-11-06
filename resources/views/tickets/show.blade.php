@extends('layouts.app')

@section('title', $ticket->reference)
@section('page-title', $ticket->reference)

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Préparation des données de vue
    |--------------------------------------------------------------------------
    | On calcule ici quelques variables d'affichage pour éviter de répéter
    | la logique plusieurs fois dans le template.
    */
    $currentUser = Auth::user();

    // Pièces jointes du message d'origine uniquement.
    $originalAttachments = $ticket->attachments->whereNull('ticket_reply_id');

    // Le formulaire de réponse reste ouvert pour les agents/admins,
    // même si le ticket est terminé. Côté client, il disparaît après résolution.
    $canReply = !$ticket->isResolved() || $currentUser->isAgent();

    // Le formulaire de satisfaction ne doit apparaître qu'une seule fois,
    // uniquement pour le client propriétaire du ticket.
    $canRate = $ticket->isResolved()
        && $currentUser->isClient()
        && $ticket->user_id === $currentUser->id
        && !$ticket->satisfaction_rating;

    /*
    |--------------------------------------------------------------------------
    | Listes métier pour les sélecteurs
    |--------------------------------------------------------------------------
    | On centralise les libellés dans la vue pour rendre les boucles plus lisibles.
    */
    $statusOptions = [
        \App\Models\Ticket::STATUS_OPEN => 'Ouvert',
        \App\Models\Ticket::STATUS_IN_PROGRESS => 'En cours',
        \App\Models\Ticket::STATUS_WAITING_CLIENT => 'Attente client',
        \App\Models\Ticket::STATUS_RESOLVED => 'Résolu',
        \App\Models\Ticket::STATUS_CLOSED => 'Fermé',
    ];

    $priorityOptions = [
        \App\Models\Ticket::PRIORITY_LOW => 'Faible',
        \App\Models\Ticket::PRIORITY_MEDIUM => 'Moyenne',
        \App\Models\Ticket::PRIORITY_HIGH => 'Élevée',
        \App\Models\Ticket::PRIORITY_CRITICAL => 'Critique',
    ];
@endphp

<div class="max-w-5xl mx-auto space-y-5">

    {{-- Fil d’Ariane de navigation --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('tickets.index') }}" class="hover:text-slate-700">Tickets</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 font-medium">{{ $ticket->reference }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- COLONNE PRINCIPALE                                                --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- En-tête du ticket --}}
            <div class="card p-5">
                <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ $ticket->title }}</h2>

                {{-- Badges de contexte ticket --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="status-{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
                    <span class="priority-{{ $ticket->priority }}">{{ $ticket->priorityLabel() }}</span>
                    <span class="badge bg-slate-100 text-slate-600">{{ $ticket->categoryLabel() }}</span>

                    @if($ticket->isOverdue())
                        {{-- Indication visuelle immédiate si le SLA est dépassé --}}
                        <span class="badge bg-red-100 text-red-700">En retard</span>
                    @endif

                    @foreach($ticket->tags as $tag)
                        {{-- Chaque tag garde sa couleur métier --}}
                        <span
                            class="badge text-xs"
                            style="background:{{ $tag->color }}18;color:{{ $tag->color }};border:1px solid {{ $tag->color }}44"
                        >
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>

                {{-- Message original du demandeur --}}
                <div class="flex gap-4">
                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-600 flex-shrink-0">
                        {{-- On utilise l’accessor initials du modèle User pour un affichage cohérent --}}
                        {{ $ticket->user->initials }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-900">{{ $ticket->user->name }}</span>
                            <span class="text-xs text-slate-400">{{ $ticket->created_at->format('d/m/Y à H:i') }}</span>
                        </div>

                        <div class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">
                            {{ $ticket->description }}
                        </div>

                        @if($originalAttachments->count())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($originalAttachments as $attachment)
                                    <a
                                        href="{{ route('attachments.download', $attachment) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs text-slate-700 transition-colors"
                                    >
                                        <span>📎 {{ $attachment->filename }}</span>
                                        @if(method_exists($attachment, 'formattedSize'))
                                            <span class="text-slate-400">({{ $attachment->formattedSize() }})</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Thread des réponses --}}
            @forelse($replies as $reply)
                <div
                    class="card p-5
                    {{ $reply->is_internal ? 'border-amber-200 bg-amber-50/30' : '' }}
                    {{ $reply->is_solution ? 'border-green-200 bg-green-50/30' : '' }}"
                >
                    @if($reply->is_internal)
                        {{-- Note réservée aux agents/admins --}}
                        <p class="flex items-center gap-1.5 text-xs font-medium text-amber-700 mb-3 pb-2 border-b border-amber-200">
                            Note interne — visible par les agents uniquement
                        </p>
                    @endif

                    @if($reply->is_solution)
                        {{-- Réponse marquée comme solution finale --}}
                        <p class="flex items-center gap-1.5 text-xs font-medium text-green-700 mb-3 pb-2 border-b border-green-200">
                            Réponse marquée comme solution
                        </p>
                    @endif

                    <div class="flex gap-4">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                            {{ $reply->user->isAgent() ? 'bg-blue-100 text-blue-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $reply->user->initials }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-sm font-semibold text-slate-900">{{ $reply->user->name }}</span>

                                @if($reply->user->isAgent())
                                    <span class="badge bg-blue-50 text-blue-600">Agent</span>
                                @endif

                                <span class="text-xs text-slate-400">{{ $reply->created_at->format('d/m/Y à H:i') }}</span>
                            </div>

                            <div class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">
                                {{ $reply->body }}
                            </div>

                            @if($reply->attachments->count())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($reply->attachments as $attachment)
                                        <a
                                            href="{{ route('attachments.download', $attachment) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-slate-200 hover:border-slate-300 rounded-lg text-xs text-slate-700 transition-colors"
                                        >
                                            <span>📎 {{ $attachment->filename }}</span>
                                            @if(method_exists($attachment, 'formattedSize'))
                                                <span class="text-slate-400">({{ $attachment->formattedSize() }})</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                {{-- Cas explicite pour éviter un espace vide si aucune réponse n’existe encore --}}
                <div class="card p-5">
                    <p class="text-sm text-slate-500">Aucune réponse pour le moment.</p>
                </div>
            @endforelse

            {{-- Formulaire de réponse --}}
            @if($canReply)
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">
                        {{ $currentUser->isAgent() ? 'Répondre / Note interne' : 'Ajouter une réponse' }}
                    </h3>

                    <form
                        method="POST"
                        action="{{ route('tickets.reply', $ticket) }}"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf

                        {{-- Affichage des erreurs de validation du formulaire de réponse --}}
                        @if($errors->any())
                            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                <ul class="space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($currentUser->isAgent())
                            <div class="flex gap-6 pb-3 border-b border-slate-200">
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input
                                        type="checkbox"
                                        name="is_internal"
                                        value="1"
                                        class="rounded border-slate-300 text-amber-500 focus:ring-amber-400"
                                        {{ old('is_internal') ? 'checked' : '' }}
                                    >
                                    <span class="text-slate-700">Note interne</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input
                                        type="checkbox"
                                        name="is_solution"
                                        value="1"
                                        class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                                        {{ old('is_solution') ? 'checked' : '' }}
                                    >
                                    <span class="text-slate-700">Marquer comme solution</span>
                                </label>
                            </div>
                        @endif

                        <textarea
                            name="body"
                            rows="5"
                            required
                            class="form-input resize-none"
                            placeholder="Votre réponse…"
                        >{{ old('body') }}</textarea>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-100 cursor-pointer transition-colors">
                                <span>📎 Joindre un fichier</span>
                                <input type="file" name="files[]" multiple class="hidden">
                            </label>

                            <button type="submit" class="btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Bloc d’évaluation de satisfaction --}}
            @if($canRate)
                <div class="card p-5 border-green-200 bg-green-50/30">
                    <h3 class="text-sm font-semibold text-green-900 mb-1">Votre ticket est résolu</h3>
                    <p class="text-sm text-green-700 mb-4">Donnez votre avis sur la qualité du support.</p>

                    <form method="POST" action="{{ route('tickets.rate', $ticket) }}" x-data="{ rating: {{ old('rating', 0) }} }">
                        @csrf

                        <div class="flex items-center gap-1 mb-4">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $i }}" class="hidden" x-model="rating">
                                    <svg
                                        class="w-9 h-9 transition-colors"
                                        :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-slate-200'"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                        aria-hidden="true"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </label>
                            @endfor

                            <span
                                class="ml-2 text-sm text-slate-500"
                                x-show="rating > 0"
                                x-text="['','Très insatisfait','Insatisfait','Neutre','Satisfait','Très satisfait'][rating]"
                            ></span>
                        </div>

                        <textarea
                            name="comment"
                            rows="2"
                            class="form-input resize-none mb-3"
                            placeholder="Commentaire optionnel…"
                        >{{ old('comment') }}</textarea>

                        <button type="submit" class="btn-primary" :disabled="rating === 0">
                            Envoyer mon évaluation
                        </button>
                    </form>
                </div>
            @endif

            {{-- Affichage de la note déjà donnée --}}
            @if($ticket->satisfaction_rating)
                <div class="card p-4 bg-amber-50/30 border-amber-200">
                    <div class="flex items-center gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            <svg
                                class="w-4 h-4 {{ $i <= $ticket->satisfaction_rating ? 'text-amber-400' : 'text-slate-200' }}"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                aria-hidden="true"
                            >
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor

                        <span class="text-xs text-slate-500 ml-1">{{ $ticket->satisfaction_rating }}/5</span>
                    </div>

                    @if($ticket->satisfaction_comment)
                        <p class="text-sm text-slate-600 italic mt-2">"{{ $ticket->satisfaction_comment }}"</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- SIDEBAR                                                           --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="space-y-4">

            {{-- Informations générales du ticket --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Informations</h3>

                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Référence</span>
                        <span class="font-mono font-medium text-slate-700 text-xs">{{ $ticket->reference }}</span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Créé le</span>
                        <span class="text-slate-700">{{ $ticket->created_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Mis à jour</span>
                        <span class="text-slate-700">{{ $ticket->updated_at->diffForHumans() }}</span>
                    </div>

                    @if($ticket->due_at)
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Échéance SLA</span>
                            <span class="font-medium {{ $ticket->isOverdue() ? 'text-red-600' : 'text-slate-700' }}">
                                {{ $ticket->due_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    @endif

                    @if($ticket->first_response_at)
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">1ère réponse</span>
                            <span class="text-slate-700">{{ $ticket->first_response_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif

                    @if($ticket->resolved_at)
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Résolu le</span>
                            <span class="text-green-700 font-medium">{{ $ticket->resolved_at->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if($ticket->agent)
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Assigné à</span>
                            <span class="text-slate-700">{{ $ticket->agent->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bloc demandeur --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Demandeur</h3>

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-600">
                        {{ $ticket->user->initials }}
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $ticket->user->name }}</p>
                        <p class="text-xs text-slate-400">{{ $ticket->user->email }}</p>
                    </div>
                </div>
            </div>

            @if($currentUser->isAgent())

                {{-- Gestion du statut --}}
                <div class="card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Statut</h3>

                    <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex gap-2">
                        @csrf

                        <select name="status" class="form-input flex-1 text-sm">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ $ticket->status === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn-primary px-3">OK</button>
                    </form>
                </div>

                {{-- Gestion de l’assignation --}}
                <div class="card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Assignation</h3>

                    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="space-y-2">
                        @csrf

                        <select name="assigned_to" class="form-input text-sm">
                            <option value="">— Non assigné —</option>

                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                                    {{ $agent->name }}{{ $agent->id === $currentUser->id ? ' (moi)' : '' }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="w-full btn-secondary text-sm">Assigner</button>
                    </form>
                </div>

                {{-- Gestion de la priorité --}}
                <div class="card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Priorité</h3>

                    <form method="POST" action="{{ route('tickets.priority', $ticket) }}" class="flex gap-2">
                        @csrf

                        <select name="priority" class="form-input flex-1 text-sm">
                            @foreach($priorityOptions as $value => $label)
                                <option value="{{ $value }}" {{ $ticket->priority === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn-primary px-3">OK</button>
                    </form>
                </div>
            @endif

            {{-- Historique des actions --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Historique</h3>

                <div class="space-y-0">
                    @forelse($histories as $history)
                        <div class="flex gap-3 text-xs">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-2 h-2 rounded-full bg-blue-400 mt-0.5"></div>
                                @if(!$loop->last)
                                    <div class="w-px flex-1 bg-slate-200 my-1"></div>
                                @endif
                            </div>

                            <div class="pb-3 flex-1 min-w-0">
                                <p class="font-medium text-slate-700">{{ $history->actionLabel() }}</p>

                                @if($history->old_value && $history->new_value)
                                    <p class="text-slate-400 mt-0.5">{{ $history->old_value }} → {{ $history->new_value }}</p>
                                @endif

                                <p class="text-slate-400 mt-0.5">
                                    {{ $history->user->name }} · {{ $history->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucun historique disponible.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection