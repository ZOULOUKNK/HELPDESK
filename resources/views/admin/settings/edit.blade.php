@extends('layouts.app')

@section('title', 'Configuration')
@section('page-title', 'Configuration générale')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-700">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 font-medium">Configuration générale</span>
    </nav>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Paramètres généraux</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nom de l’application</label>
                    <input type="text" name="app_name" value="{{ old('app_name', $settings['app.name']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">URL de l’application</label>
                    <input type="text" name="app_url" value="{{ old('app_url', $settings['app.url']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Langue</label>
                    <select name="app_locale" class="form-input">
                        @foreach($availableLocales as $value => $label)
                            <option value="{{ $value }}" {{ old('app_locale', $settings['app.locale']) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Format de date</label>
                    <select name="app_date_format" class="form-input">
                        @foreach($availableDateFormats as $value => $preview)
                            <option value="{{ $value }}" {{ old('app_date_format', $settings['app.date_format']) === $value ? 'selected' : '' }}>
                                {{ $value }} — {{ $preview }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Fuseau horaire</label>
                    <select name="app_timezone" class="form-input">
                        @foreach($availableTimezones as $value => $label)
                            <option value="{{ $value }}" {{ old('app_timezone', $settings['app.timezone']) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Page d’accueil après connexion</label>
                    <select name="app_home_page" class="form-input">
                        @foreach($availableHomePages as $value => $label)
                            <option value="{{ $value }}" {{ old('app_home_page', $settings['app.home_page']) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Paramètres tickets</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Préfixe des tickets</label>
                    <input type="text" name="ticket_reference_prefix" value="{{ old('ticket_reference_prefix', $settings['ticket.reference_prefix']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Tickets par page</label>
                    <input type="number" name="ticket_per_page" value="{{ old('ticket_per_page', $settings['ticket.per_page']) }}" class="form-input">
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="ticket_auto_assign" value="1" {{ old('ticket_auto_assign', $settings['ticket.auto_assign']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Activer l’auto-assignation</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="ticket_auto_assign_least_loaded" value="1" {{ old('ticket_auto_assign_least_loaded', $settings['ticket.auto_assign_least_loaded']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Assigner au moins chargé</span>
                </label>

                <div>
                    <label class="form-label">Fermeture auto après X jours</label>
                    <input type="number" name="ticket_auto_close_days" value="{{ old('ticket_auto_close_days', $settings['ticket.auto_close_days']) }}" class="form-input">
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="ticket_allow_reopen" value="1" {{ old('ticket_allow_reopen', $settings['ticket.allow_reopen']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Autoriser la réouverture</span>
                </label>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">SLA</h2>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="form-label">Faible (h)</label>
                    <input type="number" name="sla_low_hours" value="{{ old('sla_low_hours', $settings['sla.low_hours']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Moyenne (h)</label>
                    <input type="number" name="sla_medium_hours" value="{{ old('sla_medium_hours', $settings['sla.medium_hours']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Haute (h)</label>
                    <input type="number" name="sla_high_hours" value="{{ old('sla_high_hours', $settings['sla.high_hours']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Critique (h)</label>
                    <input type="number" name="sla_critical_hours" value="{{ old('sla_critical_hours', $settings['sla.critical_hours']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Alerte avant dépassement (h)</label>
                    <input type="number" name="sla_alert_before_hours" value="{{ old('sla_alert_before_hours', $settings['sla.alert_before_hours']) }}" class="form-input">
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Satisfaction client</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="rating_enabled" value="1" {{ old('rating_enabled', $settings['rating.enabled']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Activer l’évaluation</span>
                </label>

                <div>
                    <label class="form-label">Note min</label>
                    <input type="number" name="rating_min" value="{{ old('rating_min', $settings['rating.min']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Note max</label>
                    <input type="number" name="rating_max" value="{{ old('rating_max', $settings['rating.max']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Commentaire obligatoire sous</label>
                    <input type="number" name="rating_comment_required_below" value="{{ old('rating_comment_required_below', $settings['rating.comment_required_below']) }}" class="form-input">
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Notifications</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notifications_email_enabled" value="1" {{ old('notifications_email_enabled', $settings['notifications.email_enabled']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Activer les emails</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notifications_on_ticket_created" value="1" {{ old('notifications_on_ticket_created', $settings['notifications.on_ticket_created']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Notification création ticket</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notifications_on_ticket_assigned" value="1" {{ old('notifications_on_ticket_assigned', $settings['notifications.on_ticket_assigned']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Notification assignation</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notifications_on_status_changed" value="1" {{ old('notifications_on_status_changed', $settings['notifications.on_status_changed']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Notification changement statut</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notifications_on_client_reply" value="1" {{ old('notifications_on_client_reply', $settings['notifications.on_client_reply']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Notification réponse client</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notifications_daily_admin_digest" value="1" {{ old('notifications_daily_admin_digest', $settings['notifications.daily_admin_digest']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Digest quotidien admin</span>
                </label>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Sécurité</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="security_public_registration" value="1" {{ old('security_public_registration', $settings['security.public_registration']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Autoriser l’inscription publique</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="security_password_require_special" value="1" {{ old('security_password_require_special', $settings['security.password_require_special']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Forcer caractère spécial</span>
                </label>

                <div>
                    <label class="form-label">Longueur minimale mot de passe</label>
                    <input type="number" name="security_password_min_length" value="{{ old('security_password_min_length', $settings['security.password_min_length']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Durée session (minutes)</label>
                    <input type="number" name="security_session_lifetime" value="{{ old('security_session_lifetime', $settings['security.session_lifetime']) }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">Désactiver comptes inactifs après X jours</label>
                    <input type="number" name="security_disable_inactive_after_days" value="{{ old('security_disable_inactive_after_days', $settings['security.disable_inactive_after_days']) }}" class="form-input">
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Interface</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Thème par défaut</label>
                    <select name="ui_default_theme" class="form-input">
                        <option value="light" {{ old('ui_default_theme', $settings['ui.default_theme']) === 'light' ? 'selected' : '' }}>Clair</option>
                        <option value="dark" {{ old('ui_default_theme', $settings['ui.default_theme']) === 'dark' ? 'selected' : '' }}>Sombre</option>
                    </select>
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="ui_show_badges" value="1" {{ old('ui_show_badges', $settings['ui.show_badges']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Afficher les badges</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="ui_show_avatars" value="1" {{ old('ui_show_avatars', $settings['ui.show_avatars']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Afficher les avatars</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="ui_compact_mode" value="1" {{ old('ui_compact_mode', $settings['ui.compact_mode']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Mode compact</span>
                </label>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Vue direction</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Période par défaut</label>
                    <select name="direction_default_period" class="form-input">
                        @foreach($availableDirectionPeriods as $value => $label)
                            <option value="{{ $value }}" {{ old('direction_default_period', $settings['direction.default_period']) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="direction_show_sla" value="1" {{ old('direction_show_sla', $settings['direction.show_sla']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Afficher SLA</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="direction_show_satisfaction" value="1" {{ old('direction_show_satisfaction', $settings['direction.show_satisfaction']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Afficher satisfaction</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="direction_show_agent_load" value="1" {{ old('direction_show_agent_load', $settings['direction.show_agent_load']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Afficher charge agents</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="direction_show_advanced_kpis" value="1" {{ old('direction_show_advanced_kpis', $settings['direction.show_advanced_kpis']) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Afficher KPI avancés</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="btn-secondary">Annuler</a>
            <button type="submit" class="btn-primary">Enregistrer la configuration</button>
        </div>
    </form>
</div>
@endsection