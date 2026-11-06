<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        /*
        |--------------------------------------------------------------------------
        | Réglages globaux
        |--------------------------------------------------------------------------
        */
        $globalLocale = Setting::get('app.locale', config('app.locale', 'fr'));
        $globalTimezone = Setting::get('app.timezone', config('app.timezone', 'Europe/Paris'));

        Config::set('app.locale', $globalLocale);
        Config::set('app.timezone', $globalTimezone);

        app()->setLocale($globalLocale);
        date_default_timezone_set($globalTimezone);
        Carbon::setLocale($globalLocale);

        /*
        |--------------------------------------------------------------------------
        | Partage des couleurs globales
        |--------------------------------------------------------------------------
        */
        View::composer('*', function ($view) {
            $user = Auth::user();

            $locale = $user
                ? UserSetting::getForUser($user->id, 'locale', Setting::get('app.locale', 'fr'))
                : Setting::get('app.locale', 'fr');

            $timezone = $user
                ? UserSetting::getForUser($user->id, 'timezone', Setting::get('app.timezone', 'Europe/Paris'))
                : Setting::get('app.timezone', 'Europe/Paris');

            app()->setLocale($locale);
            date_default_timezone_set($timezone);
            Carbon::setLocale($locale);

            $theme = $user
                ? UserSetting::getForUser($user->id, 'theme', Setting::get('ui.default_theme', 'light'))
                : Setting::get('ui.default_theme', 'light');

            $accentColor = $user
                ? UserSetting::getForUser($user->id, 'accent_color', Setting::get('ui.color_primary', '#334155'))
                : Setting::get('ui.color_primary', '#334155');

            $sidebarColor = Setting::get('ui.color_sidebar', '#ffffff');
            $headerColor = Setting::get('ui.color_header', '#ffffff');
            $successColor = Setting::get('ui.color_success', '#16a34a');
            $warningColor = Setting::get('ui.color_warning', '#d97706');
            $dangerColor = Setting::get('ui.color_danger', '#dc2626');

            $view->with('uiConfig', [
                'theme' => $theme,
                'accent_color' => $accentColor,
                'sidebar_color' => $sidebarColor,
                'header_color' => $headerColor,
                'success_color' => $successColor,
                'warning_color' => $warningColor,
                'danger_color' => $dangerColor,
                'show_badges' => Setting::getBool('ui.show_badges', true),
                'show_avatars' => Setting::getBool('ui.show_avatars', true),
                'compact_mode' => $user
                    ? UserSetting::getBoolForUser($user->id, 'compact_mode', Setting::getBool('ui.compact_mode', false))
                    : Setting::getBool('ui.compact_mode', false),
                'date_format' => $user
                    ? UserSetting::getForUser($user->id, 'date_format', Setting::get('app.date_format', 'd/m/Y'))
                    : Setting::get('app.date_format', 'd/m/Y'),
            ]);
        });
    }
}