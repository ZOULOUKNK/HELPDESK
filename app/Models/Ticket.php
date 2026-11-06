<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_CLIENT = 'waiting_client';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const CATEGORY_BUG = 'bug';
    public const CATEGORY_FEATURE_REQUEST = 'feature_request';
    public const CATEGORY_ACCESS = 'access';
    public const CATEGORY_HARDWARE = 'hardware';
    public const CATEGORY_SOFTWARE = 'software';
    public const CATEGORY_NETWORK = 'network';
    public const CATEGORY_OTHER = 'other';

    public const RESOLVED_STATUSES = [
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    public const CATEGORIES = [
        self::CATEGORY_BUG,
        self::CATEGORY_FEATURE_REQUEST,
        self::CATEGORY_ACCESS,
        self::CATEGORY_HARDWARE,
        self::CATEGORY_SOFTWARE,
        self::CATEGORY_NETWORK,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'category',
        'department',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'due_at',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at'       => 'datetime',
        'closed_at'         => 'datetime',
        'due_at'            => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function publicReplies()
    {
        return $this->hasMany(TicketReply::class)
            ->where('is_internal', false)
            ->orderBy('created_at');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function histories()
    {
        return $this->hasMany(TicketHistory::class)->orderBy('created_at');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNotIn('status', self::RESOLVED_STATUSES);
    }

    public function scopeForClient(Builder $q, User $user): Builder
    {
        return $q->where('user_id', $user->id);
    }

    public function scopeForAgent(Builder $q, User $user): Builder
    {
        return $q->where('assigned_to', $user->id);
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', self::RESOLVED_STATUSES);
    }

    public static function generateReference(): string
    {
        $prefix = Setting::get('ticket.reference_prefix', 'TKT');
        $year = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%05d', strtoupper($prefix), $year, $count);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, self::RESOLVED_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && !$this->isResolved();
    }

    public function slaHours(): int
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => Setting::getInt('sla.critical_hours', 4),
            self::PRIORITY_HIGH     => Setting::getInt('sla.high_hours', 8),
            self::PRIORITY_MEDIUM   => Setting::getInt('sla.medium_hours', 24),
            self::PRIORITY_LOW      => Setting::getInt('sla.low_hours', 72),
            default                 => 48,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN           => 'Ouvert',
            self::STATUS_IN_PROGRESS    => 'En cours',
            self::STATUS_WAITING_CLIENT => 'Attente client',
            self::STATUS_RESOLVED       => 'Résolu',
            self::STATUS_CLOSED         => 'Fermé',
            default                     => ucfirst($this->status),
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'Critique',
            self::PRIORITY_HIGH     => 'Élevée',
            self::PRIORITY_MEDIUM   => 'Moyenne',
            self::PRIORITY_LOW      => 'Faible',
            default                 => ucfirst($this->priority),
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_BUG             => 'Bug',
            self::CATEGORY_FEATURE_REQUEST => 'Fonctionnalité',
            self::CATEGORY_ACCESS          => 'Accès / Droits',
            self::CATEGORY_HARDWARE        => 'Matériel',
            self::CATEGORY_SOFTWARE        => 'Logiciel',
            self::CATEGORY_NETWORK         => 'Réseau',
            self::CATEGORY_OTHER           => 'Autre',
            default                        => ucfirst($this->category),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN           => 'blue',
            self::STATUS_IN_PROGRESS    => 'amber',
            self::STATUS_WAITING_CLIENT => 'orange',
            self::STATUS_RESOLVED       => 'green',
            self::STATUS_CLOSED         => 'slate',
            default                     => 'slate',
        };
    }
}