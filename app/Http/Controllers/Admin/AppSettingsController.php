<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppSettingsController extends Controller
{
    public function edit()
    {
        $availableLocales = [
            'fr' => 'Français',
            'en' => 'English',
        ];

        $availableDateFormats = [
            'd/m/Y' => '31/12/2026',
            'd/m/Y H:i' => '31/12/2026 14:30',
            'Y-m-d' => '2026-12-31',
            'd F Y' => '31 décembre 2026',
            'l d F Y' => 'jeudi 31 décembre 2026',
        ];

        $availableTimezones = [
            'Indian/Antananarivo' => 'Indian/Antananarivo (Madagascar)',
            'Europe/Paris' => 'Europe/Paris',
            'UTC' => 'UTC',
            'Europe/London' => 'Europe/London',
            'America/New_York' => 'America/New_York',
        ];

        $availableHomePages = [
            'dashboard' => 'Tableau de bord',
            'tickets.index' => 'Liste des tickets',
            'admin.stats' => 'Vue direction',
        ];

        $availableDirectionPeriods = [
            '7' => '7 jours',
            '30' => '30 jours',
            '90' => '90 jours',
        ];

        $settings = [
            'app.name' => Setting::get('app.name', 'Helpdesk'),
            'app.url' => Setting::get('app.url', config('app.url')),
            'app.locale' => Setting::get('app.locale', 'fr'),
            'app.timezone' => Setting::get('app.timezone', 'Europe/Paris'),
            'app.date_format' => Setting::get('app.date_format', 'd/m/Y'),
            'app.home_page' => Setting::get('app.home_page', 'dashboard'),

            'ticket.reference_prefix' => Setting::get('ticket.reference_prefix', 'TKT'),
            'ticket.per_page' => Setting::get('ticket.per_page', '15'),
            'ticket.auto_assign' => Setting::get('ticket.auto_assign', '0'),
            'ticket.auto_assign_least_loaded' => Setting::get('ticket.auto_assign_least_loaded', '0'),
            'ticket.auto_close_days' => Setting::get('ticket.auto_close_days', '7'),
            'ticket.allow_reopen' => Setting::get('ticket.allow_reopen', '1'),

            'sla.low_hours' => Setting::get('sla.low_hours', '72'),
            'sla.medium_hours' => Setting::get('sla.medium_hours', '24'),
            'sla.high_hours' => Setting::get('sla.high_hours', '8'),
            'sla.critical_hours' => Setting::get('sla.critical_hours', '4'),
            'sla.alert_before_hours' => Setting::get('sla.alert_before_hours', '2'),

            'rating.enabled' => Setting::get('rating.enabled', '1'),
            'rating.min' => Setting::get('rating.min', '1'),
            'rating.max' => Setting::get('rating.max', '5'),
            'rating.comment_required_below' => Setting::get('rating.comment_required_below', '3'),

            'notifications.email_enabled' => Setting::get('notifications.email_enabled', '0'),
            'notifications.on_ticket_created' => Setting::get('notifications.on_ticket_created', '1'),
            'notifications.on_ticket_assigned' => Setting::get('notifications.on_ticket_assigned', '1'),
            'notifications.on_status_changed' => Setting::get('notifications.on_status_changed', '1'),
            'notifications.on_client_reply' => Setting::get('notifications.on_client_reply', '1'),
            'notifications.daily_admin_digest' => Setting::get('notifications.daily_admin_digest', '0'),

            'security.public_registration' => Setting::get('security.public_registration', '1'),
            'security.password_min_length' => Setting::get('security.password_min_length', '8'),
            'security.password_require_special' => Setting::get('security.password_require_special', '0'),
            'security.session_lifetime' => Setting::get('security.session_lifetime', '120'),
            'security.disable_inactive_after_days' => Setting::get('security.disable_inactive_after_days', '90'),

            'ui.default_theme' => Setting::get('ui.default_theme', 'light'),
            'ui.color_primary' => Setting::get('ui.color_primary', '#334155'),
            'ui.color_sidebar' => Setting::get('ui.color_sidebar', '#ffffff'),
            'ui.color_header' => Setting::get('ui.color_header', '#ffffff'),
            'ui.color_success' => Setting::get('ui.color_success', '#16a34a'),
            'ui.color_warning' => Setting::get('ui.color_warning', '#d97706'),
            'ui.color_danger' => Setting::get('ui.color_danger', '#dc2626'),
            'ui.show_badges' => Setting::get('ui.show_badges', '1'),
            'ui.show_avatars' => Setting::get('ui.show_avatars', '1'),
            'ui.compact_mode' => Setting::get('ui.compact_mode', '0'),

            'direction.default_period' => Setting::get('direction.default_period', '30'),
            'direction.show_sla' => Setting::get('direction.show_sla', '1'),
            'direction.show_satisfaction' => Setting::get('direction.show_satisfaction', '1'),
            'direction.show_agent_load' => Setting::get('direction.show_agent_load', '1'),
            'direction.show_advanced_kpis' => Setting::get('direction.show_advanced_kpis', '1'),
        ];

        return view('admin.settings.edit', compact(
            'settings',
            'availableLocales',
            'availableDateFormats',
            'availableTimezones',
            'availableHomePages',
            'availableDirectionPeriods'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url', 'max:255'],
            'app_locale' => ['required', Rule::in(['fr', 'en'])],
            'app_date_format' => ['required', Rule::in([
                'd/m/Y',
                'd/m/Y H:i',
                'Y-m-d',
                'd F Y',
                'l d F Y',
            ])],
            'app_timezone' => ['required', Rule::in([
                'Indian/Antananarivo',
                'Europe/Paris',
                'UTC',
                'Europe/London',
                'America/New_York',
            ])],
            'app_home_page' => ['required', Rule::in([
                'dashboard',
                'tickets.index',
                'admin.stats',
            ])],

            'ticket_reference_prefix' => ['required', 'string', 'max:10'],
            'ticket_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'ticket_auto_assign' => ['nullable', 'boolean'],
            'ticket_auto_assign_least_loaded' => ['nullable', 'boolean'],
            'ticket_auto_close_days' => ['required', 'integer', 'min:1', 'max:365'],
            'ticket_allow_reopen' => ['nullable', 'boolean'],

            'sla_low_hours' => ['required', 'integer', 'min:1', 'max:999'],
            'sla_medium_hours' => ['required', 'integer', 'min:1', 'max:999'],
            'sla_high_hours' => ['required', 'integer', 'min:1', 'max:999'],
            'sla_critical_hours' => ['required', 'integer', 'min:1', 'max:999'],
            'sla_alert_before_hours' => ['required', 'integer', 'min:1', 'max:999'],

            'rating_enabled' => ['nullable', 'boolean'],
            'rating_min' => ['required', 'integer', 'min:1', 'max:10'],
            'rating_max' => ['required', 'integer', 'min:1', 'max:10'],
            'rating_comment_required_below' => ['required', 'integer', 'min:1', 'max:10'],

            'notifications_email_enabled' => ['nullable', 'boolean'],
            'notifications_on_ticket_created' => ['nullable', 'boolean'],
            'notifications_on_ticket_assigned' => ['nullable', 'boolean'],
            'notifications_on_status_changed' => ['nullable', 'boolean'],
            'notifications_on_client_reply' => ['nullable', 'boolean'],
            'notifications_daily_admin_digest' => ['nullable', 'boolean'],

            'security_public_registration' => ['nullable', 'boolean'],
            'security_password_min_length' => ['required', 'integer', 'min:6', 'max:64'],
            'security_password_require_special' => ['nullable', 'boolean'],
            'security_session_lifetime' => ['required', 'integer', 'min:5', 'max:1440'],
            'security_disable_inactive_after_days' => ['required', 'integer', 'min:1', 'max:3650'],

            'ui_default_theme' => ['required', Rule::in(['light', 'dark'])],
            'ui_color_primary' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'ui_color_sidebar' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'ui_color_header' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'ui_color_success' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'ui_color_warning' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'ui_color_danger' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'ui_show_badges' => ['nullable', 'boolean'],
            'ui_show_avatars' => ['nullable', 'boolean'],
            'ui_compact_mode' => ['nullable', 'boolean'],

            'direction_default_period' => ['required', Rule::in(['7', '30', '90'])],
            'direction_show_sla' => ['nullable', 'boolean'],
            'direction_show_satisfaction' => ['nullable', 'boolean'],
            'direction_show_agent_load' => ['nullable', 'boolean'],
            'direction_show_advanced_kpis' => ['nullable', 'boolean'],
        ]);

        Setting::set('app.name', $validated['app_name']);
        Setting::set('app.url', $validated['app_url']);
        Setting::set('app.locale', $validated['app_locale']);
        Setting::set('app.date_format', $validated['app_date_format']);
        Setting::set('app.timezone', $validated['app_timezone']);
        Setting::set('app.home_page', $validated['app_home_page']);

        Setting::set('ticket.reference_prefix', $validated['ticket_reference_prefix']);
        Setting::set('ticket.per_page', $validated['ticket_per_page']);
        Setting::set('ticket.auto_assign', $request->boolean('ticket_auto_assign'));
        Setting::set('ticket.auto_assign_least_loaded', $request->boolean('ticket_auto_assign_least_loaded'));
        Setting::set('ticket.auto_close_days', $validated['ticket_auto_close_days']);
        Setting::set('ticket.allow_reopen', $request->boolean('ticket_allow_reopen'));

        Setting::set('sla.low_hours', $validated['sla_low_hours']);
        Setting::set('sla.medium_hours', $validated['sla_medium_hours']);
        Setting::set('sla.high_hours', $validated['sla_high_hours']);
        Setting::set('sla.critical_hours', $validated['sla_critical_hours']);
        Setting::set('sla.alert_before_hours', $validated['sla_alert_before_hours']);

        Setting::set('rating.enabled', $request->boolean('rating_enabled'));
        Setting::set('rating.min', $validated['rating_min']);
        Setting::set('rating.max', $validated['rating_max']);
        Setting::set('rating.comment_required_below', $validated['rating_comment_required_below']);

        Setting::set('notifications.email_enabled', $request->boolean('notifications_email_enabled'));
        Setting::set('notifications.on_ticket_created', $request->boolean('notifications_on_ticket_created'));
        Setting::set('notifications.on_ticket_assigned', $request->boolean('notifications_on_ticket_assigned'));
        Setting::set('notifications.on_status_changed', $request->boolean('notifications_on_status_changed'));
        Setting::set('notifications.on_client_reply', $request->boolean('notifications_on_client_reply'));
        Setting::set('notifications.daily_admin_digest', $request->boolean('notifications_daily_admin_digest'));

        Setting::set('security.public_registration', $request->boolean('security_public_registration'));
        Setting::set('security.password_min_length', $validated['security_password_min_length']);
        Setting::set('security.password_require_special', $request->boolean('security_password_require_special'));
        Setting::set('security.session_lifetime', $validated['security_session_lifetime']);
        Setting::set('security.disable_inactive_after_days', $validated['security_disable_inactive_after_days']);

        Setting::set('ui.default_theme', $validated['ui_default_theme']);
        Setting::set('ui.color_primary', $validated['ui_color_primary']);
        Setting::set('ui.color_sidebar', $validated['ui_color_sidebar']);
        Setting::set('ui.color_header', $validated['ui_color_header']);
        Setting::set('ui.color_success', $validated['ui_color_success']);
        Setting::set('ui.color_warning', $validated['ui_color_warning']);
        Setting::set('ui.color_danger', $validated['ui_color_danger']);
        Setting::set('ui.show_badges', $request->boolean('ui_show_badges'));
        Setting::set('ui.show_avatars', $request->boolean('ui_show_avatars'));
        Setting::set('ui.compact_mode', $request->boolean('ui_compact_mode'));

        Setting::set('direction.default_period', $validated['direction_default_period']);
        Setting::set('direction.show_sla', $request->boolean('direction_show_sla'));
        Setting::set('direction.show_satisfaction', $request->boolean('direction_show_satisfaction'));
        Setting::set('direction.show_agent_load', $request->boolean('direction_show_agent_load'));
        Setting::set('direction.show_advanced_kpis', $request->boolean('direction_show_advanced_kpis'));

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Configuration globale enregistrée en base.');
    }
}