<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Avant migration (ou pendant « composer install »), la base SQLite
 * n'existe pas : les réglages doivent renvoyer leur valeur par défaut
 * au lieu de lever une exception.
 */
class SettingsWithoutDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.absente' => [
                'driver' => 'sqlite',
                'database' => storage_path('framework/testing/base-absente.sqlite'),
                'prefix' => '',
            ],
            'database.default' => 'absente',
        ]);
        DB::purge('absente');
    }

    public function test_setting_returns_default(): void
    {
        $this->assertSame('fr', Setting::get('app.locale', 'fr'));
        $this->assertTrue(Setting::getBool('ui.show_badges', true));
    }

    public function test_user_setting_returns_default(): void
    {
        $this->assertSame('light', UserSetting::getForUser(1, 'theme', 'light'));
    }
}
