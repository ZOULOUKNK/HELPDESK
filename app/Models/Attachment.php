<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'ticket_id','ticket_reply_id','user_id','filename','path','mime_type','size',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function reply()
    {
        return $this->belongsTo(TicketReply::class, 'ticket_reply_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function formattedSize(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024)    return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        return round($bytes / 1048576, 1) . ' Mo';
    }
}