<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    private const CACHE_KEY = 'helpdesk.settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allValues();

        return $all === null ? $default : ($all[$key] ?? $default);
    }

    /**
     * Tous les réglages, lus en une seule requête puis gardés pour le
     * reste de la requête HTTP. Le layout lit une quinzaine de réglages
     * pour chaque vue : sans ce cache, une page déclenchait 150 à 200
     * requêtes. Renvoie null tant que la table n'existe pas.
     */
    protected static function allValues(): ?array
    {
        $app = app();

        if ($app->bound(self::CACHE_KEY)) {
            return $app->make(self::CACHE_KEY);
        }

        if (!static::tableIsAvailable()) {
            return null; // non mémorisé : la table peut apparaître après migration
        }

        $values = static::query()->pluck('value', 'key')->all();
        $app->instance(self::CACHE_KEY, $values);

        return $values;
    }

    public static function forgetCache(): void
    {
        app()->forgetInstance(self::CACHE_KEY);
    }

    public static function set(string $key, mixed $value): void
    {
        if (!static::tableIsAvailable()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
        static::forgetCache();
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return filter_var(static::get($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    /**
     * Indique si la table est utilisable. Renvoie false au lieu de planter
     * quand la base n'existe pas encore (composer install, premier
     * lancement avant « php artisan migrate »).
     */
    protected static function tableIsAvailable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
