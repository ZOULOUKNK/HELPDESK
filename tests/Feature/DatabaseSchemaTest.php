<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests de non-régression des corrections de migrations (novembre 2026).
 */
class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_can_be_created_after_all_migrations(): void
    {
        // Sous SQLite, les clés étrangères pointaient vers users_old.
        $user = User::factory()->create();

        $ticket = Ticket::create([
            'reference' => 'TKT-TEST-1',
            'user_id' => $user->id,
            'title' => 'Imprimante en panne',
            'description' => 'Elle ne répond plus.',
        ]);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'user_id' => $user->id]);
    }

    public function test_no_foreign_key_targets_a_missing_table(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Contrôle propre à SQLite.');
        }

        $tables = array_column(DB::select("SELECT name FROM sqlite_master WHERE type = 'table'"), 'name');

        foreach ($tables as $table) {
            foreach (DB::select("PRAGMA foreign_key_list(\"$table\")") as $fk) {
                $this->assertContains($fk->table, $tables, "$table référence la table absente {$fk->table}");
            }
        }
    }

    public function test_customer_profiles_has_its_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('customer_profiles', [
            'user_id', 'is_vip', 'default_priority', 'default_category',
            'custom_sla_hours', 'assigned_team', 'accent_color',
        ]));
    }
}
