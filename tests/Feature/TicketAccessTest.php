<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAccessTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(User $owner): Ticket
    {
        return Ticket::create([
            'reference' => 'TKT-'.uniqid(),
            'user_id' => $owner->id,
            'title' => 'Accès VPN',
            'description' => 'Impossible de se connecter au VPN.',
        ]);
    }

    public function test_client_can_create_and_view_own_ticket(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->post(route('tickets.store'), [
            'title' => 'Écran noir',
            'description' => 'Le poste ne démarre plus depuis ce matin.',
        ]);

        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.show', $ticket));
        $this->actingAs($client)->get(route('tickets.show', $ticket))->assertOk();
    }

    public function test_client_cannot_view_or_reply_to_another_clients_ticket(): void
    {
        $ticket = $this->ticketFor(User::factory()->create());
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('tickets.show', $ticket))->assertForbidden();
        $this->actingAs($other)->post(route('tickets.reply', $ticket), ['body' => 'Coucou'])->assertForbidden();
    }

    public function test_client_cannot_change_status(): void
    {
        $client = User::factory()->create();
        $ticket = $this->ticketFor($client);

        $this->actingAs($client)
            ->post(route('tickets.status', $ticket), ['status' => 'closed'])
            ->assertForbidden();
    }

    public function test_ticket_can_be_assigned_to_an_agent(): void
    {
        $agent = User::factory()->role(User::ROLE_AGENT)->create();
        $ticket = $this->ticketFor(User::factory()->create());

        $this->actingAs($agent)->post(route('tickets.assign', $ticket), ['assigned_to' => $agent->id]);

        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
    }

    public function test_ticket_cannot_be_assigned_to_a_client_or_inactive_agent(): void
    {
        $agent = User::factory()->role(User::ROLE_AGENT)->create();
        $client = User::factory()->create();
        $inactiveAgent = User::factory()->role(User::ROLE_AGENT)->inactive()->create();
        $ticket = $this->ticketFor($client);

        foreach ([$client, $inactiveAgent] as $target) {
            $this->actingAs($agent)
                ->post(route('tickets.assign', $ticket), ['assigned_to' => $target->id])
                ->assertSessionHasErrors('assigned_to');
        }

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'faux']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429);
        $this->assertGuest();
    }
}
