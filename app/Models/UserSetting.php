<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getForUser(int $userId, string $key, mixed $default = null): mixed
    {
        if (!static::tableIsAvailable()) {
            return $default;
        }

        $setting = static::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    public static function setForUser(int $userId, string $key, mixed $value): void
    {
        if (!static::tableIsAvailable()) {
            return;
        }

        static::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'key' => $key,
            ],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            ]
        );
    }

    public static function getBoolForUser(int $userId, string $key, bool $default = false): bool
    {
        return filter_var(static::getForUser($userId, $key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Indique si la table est utilisable. Renvoie false au lieu de planter
     * quand la base n'existe pas encore (composer install, premier
     * lancement avant « php artisan migrate »).
     */
    protected static function tableIsAvailable(): bool
    {
        try {
            return Schema::hasTable('user_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
