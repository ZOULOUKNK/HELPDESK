<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketHistory;

class ClearTickets extends Command
{
    protected $signature = 'tickets:clear';
    protected $description = 'Supprimer tous les tickets et données liées';

    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        TicketHistory::truncate();
        TicketReply::truncate();
        Ticket::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Tous les tickets ont été supprimés.');
    }
}