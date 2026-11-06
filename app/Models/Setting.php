<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!static::tableIsAvailable()) {
            return $default;
        }

        $setting = static::query()
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
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
