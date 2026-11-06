<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'is_vip',
        'default_priority',
        'default_category',
        'custom_sla_hours',
        'assigned_team',
        'accent_color',
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'custom_sla_hours' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}