<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_setting_is_read_back_immediately(): void
    {
        $this->assertSame('TKT', Setting::get('ticket.reference_prefix', 'TKT'));

        Setting::set('ticket.reference_prefix', 'SUP');

        $this->assertSame('SUP', Setting::get('ticket.reference_prefix', 'TKT'));
    }

    public function test_changed_user_setting_is_read_back_immediately(): void
    {
        $user = User::factory()->create();
        $this->assertSame('light', UserSetting::getForUser($user->id, 'theme', 'light'));

        UserSetting::setForUser($user->id, 'theme', 'dark');

        $this->assertSame('dark', UserSetting::getForUser($user->id, 'theme', 'light'));
    }

    public function test_dashboard_does_not_reload_settings_for_every_view(): void
    {
        $this->seed();
        $admin = User::where('email', 'superadmin@helpdesk.fr')->firstOrFail();
        $this->actingAs($admin);

        DB::enableQueryLog();
        $this->get(route('dashboard'))->assertOk();
        $count = count(DB::getQueryLog());

        // Avant le cache : environ 175 requêtes, dont 154 pour les réglages.
        $this->assertLessThan(60, $count, "$count requêtes pour le tableau de bord");
    }
}
