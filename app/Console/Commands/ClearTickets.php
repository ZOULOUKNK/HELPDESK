<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketHistory;

class ClearTickets extends Command
{
    protected $signature = 'tickets:clear';
    protected $description = 'Supprimer tous les tickets et données liées';

    public function handle()
    {
        Schema::disableForeignKeyConstraints();

        TicketHistory::truncate();
        TicketReply::truncate();
        Ticket::truncate();

        Schema::enableForeignKeyConstraints();

        $this->info('Tous les tickets ont été supprimés.');
    }
}