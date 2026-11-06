<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['message', 'ticket_id', 'user_id'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
