<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Purge propre des tickets
        |--------------------------------------------------------------------------
        */
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TicketHistory::truncate();
        TicketReply::truncate();
        Ticket::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /*
        |--------------------------------------------------------------------------
        | Utilisateurs
        |--------------------------------------------------------------------------
        */
        $clients = User::where('role', User::ROLE_CLIENT)->get();

        $supportUsers = User::whereIn('role', [
            User::ROLE_AGENT,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
        ])->get();

        if ($clients->isEmpty()) {
            $this->command?->warn('Aucun client trouvé.');
            return;
        }

        if ($supportUsers->isEmpty()) {
            $this->command?->warn('Aucun utilisateur support trouvé.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Cas réalistes
        |--------------------------------------------------------------------------
        */
        $cases = [
            [
                'title' => 'Impossible de se connecter au VPN',
                'description' => 'Erreur "Authentication failed" lors de la connexion VPN depuis ce matin.',
                'category' => Ticket::CATEGORY_NETWORK,
                'priority' => Ticket::PRIORITY_HIGH,
            ],
            [
                'title' => 'Mot de passe expiré – impossible de le changer',
                'description' => 'Le système demande un changement de mot de passe mais la page renvoie une erreur.',
                'category' => Ticket::CATEGORY_ACCESS,
                'priority' => Ticket::PRIORITY_MEDIUM,
            ],
            [
                'title' => 'PC très lent au démarrage',
                'description' => 'Le poste met plus de 10 minutes à démarrer depuis la dernière mise à jour.',
                'category' => Ticket::CATEGORY_HARDWARE,
                'priority' => Ticket::PRIORITY_MEDIUM,
            ],
            [
                'title' => 'Écran externe non détecté',
                'description' => 'Le second écran n’est plus reconnu après branchement HDMI.',
                'category' => Ticket::CATEGORY_HARDWARE,
                'priority' => Ticket::PRIORITY_LOW,
            ],
            [
                'title' => 'Excel plante à l’ouverture d’un fichier',
                'description' => 'Fichier important impossible à ouvrir, Excel se ferme immédiatement.',
                'category' => Ticket::CATEGORY_SOFTWARE,
                'priority' => Ticket::PRIORITY_HIGH,
            ],
            [
                'title' => 'Outlook ne synchronise plus',
                'description' => 'Les nouveaux emails ne remontent plus depuis hier.',
                'category' => Ticket::CATEGORY_SOFTWARE,
                'priority' => Ticket::PRIORITY_HIGH,
            ],
            [
                'title' => 'Connexion WiFi instable',
                'description' => 'Déconnexions fréquentes toutes les 5 minutes.',
                'category' => Ticket::CATEGORY_NETWORK,
                'priority' => Ticket::PRIORITY_MEDIUM,
            ],
            [
                'title' => 'Erreur 500 sur le portail interne',
                'description' => 'Impossible d’accéder au dashboard interne, erreur serveur.',
                'category' => Ticket::CATEGORY_BUG,
                'priority' => Ticket::PRIORITY_CRITICAL,
            ],
            [
                'title' => 'Bouton "Valider" ne fonctionne pas',
                'description' => 'Aucune action lors du clic sur le bouton dans l’application métier.',
                'category' => Ticket::CATEGORY_BUG,
                'priority' => Ticket::PRIORITY_MEDIUM,
            ],
            [
                'title' => 'Demande d’accès au dossier partagé Finance',
                'description' => 'Besoin d’accès en lecture/écriture pour traitement mensuel.',
                'category' => Ticket::CATEGORY_ACCESS,
                'priority' => Ticket::PRIORITY_LOW,
            ],
            [
                'title' => 'Installation de Teams sur nouveau poste',
                'description' => 'Poste récemment livré sans certains logiciels.',
                'category' => Ticket::CATEGORY_SOFTWARE,
                'priority' => Ticket::PRIORITY_LOW,
            ],
            [
                'title' => 'Création d’un nouveau compte utilisateur',
                'description' => 'Arrivée d’un nouveau collaborateur demain.',
                'category' => Ticket::CATEGORY_ACCESS,
                'priority' => Ticket::PRIORITY_HIGH,
            ],
        ];

        $ticketCount = 200;

        for ($i = 0; $i < $ticketCount; $i++) {
            $case = collect($cases)->random();
            $client = $clients->random();
            $supportUser = fake()->boolean(85) ? $supportUsers->random() : null;

            $createdAt = now()
                ->subDays(fake()->numberBetween(0, 45))
                ->subHours(fake()->numberBetween(0, 23))
                ->subMinutes(fake()->numberBetween(0, 59));

            $status = fake()->randomElement([
                Ticket::STATUS_OPEN,
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_CLIENT,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ]);

            /*
            |--------------------------------------------------------------------------
            | SLA
            |--------------------------------------------------------------------------
            */
            $tmpTicket = new Ticket([
                'priority' => $case['priority'],
            ]);

            $dueAt = $createdAt->copy()->addHours($tmpTicket->slaHours());

            /*
            |--------------------------------------------------------------------------
            | Dates métier
            |--------------------------------------------------------------------------
            */
            $firstResponseAt = null;
            $resolvedAt = null;
            $closedAt = null;
            $updatedAt = $createdAt->copy()->addHours(fake()->numberBetween(1, 48));

            if (in_array($status, [
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_CLIENT,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ], true)) {
                $firstResponseAt = $createdAt->copy()->addHours(fake()->numberBetween(1, 8));
                $updatedAt = $firstResponseAt->copy();
            }

            if (in_array($status, Ticket::RESOLVED_STATUSES, true)) {
                if (fake()->boolean(70)) {
                    $resolvedAt = $createdAt->copy()->addHours(
                        fake()->numberBetween(2, max(3, $tmpTicket->slaHours() - 1))
                    );
                } else {
                    $resolvedAt = $dueAt->copy()->addHours(fake()->numberBetween(1, 24));
                }

                if ($resolvedAt->lessThanOrEqualTo($createdAt)) {
                    $resolvedAt = $createdAt->copy()->addHours(2);
                }

                $updatedAt = $resolvedAt->copy();
            }

            if ($status === Ticket::STATUS_CLOSED) {
                $closedAt = ($resolvedAt ?? $createdAt)->copy()->addHours(fake()->numberBetween(2, 24));
                $updatedAt = $closedAt->copy();
            }

            $ticket = Ticket::create([
                'reference' => Ticket::generateReference(),
                'user_id' => $client->id,
                'assigned_to' => $supportUser?->id,
                'title' => $case['title'],
                'description' => $case['description'],
                'status' => $status,
                'priority' => $case['priority'],
                'category' => $case['category'],
                'department' => $client->department ?? 'Non renseigné',
                'first_response_at' => $firstResponseAt?->toDateTimeString(),
                'resolved_at' => $resolvedAt?->toDateTimeString(),
                'closed_at' => $closedAt?->toDateTimeString(),
                'due_at' => $dueAt->toDateTimeString(),
                'satisfaction_rating' => in_array($status, Ticket::RESOLVED_STATUSES, true) && fake()->boolean(65)
                    ? fake()->numberBetween(3, 5)
                    : null,
                'satisfaction_comment' => in_array($status, Ticket::RESOLVED_STATUSES, true) && fake()->boolean(35)
                    ? fake()->randomElement([
                        'Problème résolu rapidement.',
                        'Support efficace et clair.',
                        'Merci pour la prise en charge.',
                        'Résolution satisfaisante.',
                    ])
                    : null,
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $updatedAt->toDateTimeString(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Historique
            |--------------------------------------------------------------------------
            | On ne force plus created_at / updated_at ici.
            | Laravel les gère automatiquement pour éviter les incompatibilités
            | de schéma MySQL.
            */
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $client->id,
                'action' => 'created',
                'new_value' => $ticket->reference,
            ]);

            if ($supportUser) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $supportUser->id,
                    'action' => 'assigned',
                    'old_value' => null,
                    'new_value' => $supportUser->name,
                ]);
            }

            if ($status !== Ticket::STATUS_OPEN) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $supportUser?->id ?? $client->id,
                    'action' => 'status_changed',
                    'old_value' => Ticket::STATUS_OPEN,
                    'new_value' => $status,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Réponses
            |--------------------------------------------------------------------------
            | Même logique : pas de timestamps forcés.
            */
            if ($supportUser) {
                TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $supportUser->id,
                    'body' => 'Prise en charge du ticket. Analyse en cours.',
                    'is_internal' => false,
                    'is_solution' => false,
                ]);

                if (in_array($status, [
                    Ticket::STATUS_WAITING_CLIENT,
                    Ticket::STATUS_RESOLVED,
                    Ticket::STATUS_CLOSED,
                ], true)) {
                    TicketReply::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $client->id,
                        'body' => 'Merci pour votre retour. Je vous confirme que le problème a bien été constaté.',
                        'is_internal' => false,
                        'is_solution' => false,
                    ]);
                }

                if (in_array($status, Ticket::RESOLVED_STATUSES, true)) {
                    TicketReply::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $supportUser->id,
                        'body' => 'Le problème a été corrigé. Merci de confirmer que tout est revenu à la normale.',
                        'is_internal' => false,
                        'is_solution' => true,
                    ]);
                }
            }
        }

        $this->command?->info("{$ticketCount} tickets réalistes et compatibles MySQL ont été générés.");
    }
}