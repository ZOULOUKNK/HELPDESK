<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketHistory extends Model
{
    protected $fillable = [
        'ticket_id','user_id','action','old_value','new_value','comment',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match($this->action) {
            'created'          => 'Ticket créé',
            'status_changed'   => 'Statut modifié',
            'priority_changed' => 'Priorité modifiée',
            'assigned'         => 'Assigné',
            'replied'          => 'Réponse ajoutée',
            'resolved'         => 'Résolu',
            'closed'           => 'Fermé',
            'reopened'         => 'Rouvert',
            default            => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}