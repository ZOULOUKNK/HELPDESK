<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function preferences(array $overrides = []): array
    {
        return array_merge([
            'timezone' => 'Indian/Antananarivo',
            'accent_color' => '#0d9488',
            'tickets_per_page' => 10,
            'home_page' => 'tickets.index',
        ], $overrides);
    }

    public function test_every_role_can_open_my_account_page(): void
    {
        foreach ([User::ROLE_CLIENT, User::ROLE_AGENT, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('preferences.edit'))
                ->assertOk()
                ->assertSee('Mon compte');
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('preferences.edit'))->assertRedirect(route('login'));
    }

    public function test_preferences_are_saved_and_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('preferences.update'), $this->preferences())
            ->assertSessionHasNoErrors();

        $this->assertSame('Indian/Antananarivo', UserSetting::getForUser($user->id, 'timezone'));
        $this->assertSame('10', UserSetting::getForUser($user->id, 'tickets_per_page'));

        $this->actingAs($user)->put(route('preferences.update'), $this->preferences([
            'timezone' => 'Mars/Olympus',
            'accent_color' => 'red;}</style><script>',
            'tickets_per_page' => 1000,
            'home_page' => 'admin.users.index',
        ]))->assertSessionHasErrors(['timezone', 'accent_color', 'tickets_per_page', 'home_page']);
    }

    public function test_accent_color_is_applied_to_the_layout(): void
    {
        $user = User::factory()->create();
        UserSetting::setForUser($user->id, 'accent_color', '#123abc');

        $this->actingAs($user)->get(route('dashboard'))->assertSee('--color-primary: #123abc', false);
    }

    public function test_tickets_per_page_preference_is_used(): void
    {
        $client = User::factory()->create();
        foreach (range(1, 7) as $i) {
            Ticket::create([
                'reference' => "TKT-PAGE-$i", 'user_id' => $client->id,
                'title' => "Ticket $i", 'description' => 'Description du ticket.',
            ]);
        }
        UserSetting::setForUser($client->id, 'tickets_per_page', 5);

        $this->actingAs($client)->get(route('tickets.index'))
            ->assertOk()
            ->assertViewHas('tickets', fn ($tickets) => $tickets->perPage() === 5 && $tickets->count() === 5);
    }

    public function test_home_page_preference_is_used_after_login_and_on_root(): void
    {
        $user = User::factory()->create();
        UserSetting::setForUser($user->id, 'home_page', 'tickets.index');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('tickets.index'));
        $this->get('/')->assertRedirect(route('tickets.index'));
    }

    public function test_profile_update_cannot_change_role_or_email(): void
    {
        $user = User::factory()->create(['email' => 'claire@test.mg']);

        $this->actingAs($user)->put(route('preferences.profile'), [
            'name' => 'Claire D.',
            'phone' => '034 00 000 00',
            'email' => 'pirate@test.mg',
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Claire D.', $user->name);
        $this->assertSame('034 00 000 00', $user->phone);
        $this->assertSame('claire@test.mg', $user->email);
        $this->assertSame(User::ROLE_CLIENT, $user->role);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('preferences.password'), [
            'current_password' => 'mauvais',
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->actingAs($user)->put(route('preferences.password'), [
            'current_password' => 'password',
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('nouveau-mdp-123', $user->fresh()->password));
    }
}
