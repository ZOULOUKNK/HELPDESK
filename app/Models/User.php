<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Constantes métier
    |--------------------------------------------------------------------------
    */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENT = 'agent';
    public const ROLE_CLIENT = 'client';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'department',
        'phone',
        'bio',
        'avatar',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        /*
        | Le super admin fait partie du périmètre admin.
        */
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ], true);
    }

    public function isAgent(): bool
    {
        /*
        | Le support inclut :
        | - super_admin
        | - admin
        | - agent
        */
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_AGENT,
        ], true);
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function getInitialsAttribute(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        $firstInitial = mb_substr($parts[0] ?? '', 0, 1);
        $secondInitial = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';

        return mb_strtoupper($firstInitial . $secondInitial);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAgents($query)
    {
        return $query->whereIn('role', [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_AGENT,
        ]);
    }

    public function scopeClients($query)
    {
        return $query->where('role', self::ROLE_CLIENT);
    }
}