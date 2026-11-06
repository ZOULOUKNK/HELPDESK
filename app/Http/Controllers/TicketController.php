<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\CustomerProfile;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        // Préférence personnelle (Mon compte), sinon réglage global.
        $perPage = max(5, min(100, (int) UserSetting::getForUser(
            $user->id,
            'tickets_per_page',
            Setting::get('ticket.per_page', 15)
        )));

        $query = Ticket::with(['user', 'agent', 'tags']);

        if ($user->isClient()) {
            $query->where('user_id', $user->id);
        } elseif ($user->role === User::ROLE_AGENT) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereNull('assigned_to');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'none') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $allowedSorts = ['created_at', 'updated_at', 'priority', 'status', 'title'];
        $sortField = in_array($request->get('sort'), $allowedSorts, true)
            ? $request->get('sort')
            : 'created_at';

        $sortDir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $tickets = $query
            ->orderBy($sortField, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $stats = $this->getStats($user);

        return view('tickets.index', compact('tickets', 'stats'));
    }

    public function create()
    {
        $tags = Tag::orderBy('name')->get();
        return view('tickets.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'priority'    => 'nullable|in:low,medium,high,critical',
            'category'    => 'nullable|in:bug,feature_request,access,hardware,software,network,other',
            'files.*'     => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
        ]);

        $user = Auth::user();
        $profile = CustomerProfile::where('user_id', $user->id)->first();

        $priority = $validated['priority']
            ?? $profile?->default_priority
            ?? Ticket::PRIORITY_MEDIUM;

        $category = $validated['category']
            ?? $profile?->default_category
            ?? Ticket::CATEGORY_OTHER;

        $slaHours = $profile?->custom_sla_hours
            ?? match ($priority) {
                Ticket::PRIORITY_CRITICAL => Setting::getInt('sla.critical_hours', 4),
                Ticket::PRIORITY_HIGH     => Setting::getInt('sla.high_hours', 8),
                Ticket::PRIORITY_MEDIUM   => Setting::getInt('sla.medium_hours', 24),
                Ticket::PRIORITY_LOW      => Setting::getInt('sla.low_hours', 72),
                default                   => 48,
            };

        $ticket = Ticket::create([
            'reference'   => Ticket::generateReference(),
            'user_id'     => $user->id,
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'priority'    => $priority,
            'category'    => $category,
            'department'  => $user->department,
            'status'      => Ticket::STATUS_OPEN,
            'due_at'      => now()->addHours($slaHours),
        ]);

        if (!empty($validated['tags'])) {
            $ticket->tags()->sync($validated['tags']);
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeAttachment($file, $ticket, null);
            }
        }

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'action'    => 'created',
            'new_value' => $ticket->reference,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->reference} créé avec succès.");
    }

    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        if ($user->isClient() && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->load(['user', 'agent', 'tags', 'attachments']);

        $replies = $user->isAgent()
            ? $ticket->replies()->with(['user', 'attachments'])->get()
            : $ticket->publicReplies()->with(['user', 'attachments'])->get();

        $histories = $ticket->histories()->with('user')->get();

        $agents = $user->isAgent()
            ? User::whereIn('role', [User::ROLE_AGENT, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->orderBy('name')->get()
            : collect();

        $tags = $user->isAgent()
            ? Tag::orderBy('name')->get()
            : collect();

        return view('tickets.show', compact('ticket', 'replies', 'histories', 'agents', 'tags'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($user->isClient() && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'body'    => 'required|string|min:2',
            'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $isInternal = $user->isAgent() && $request->boolean('is_internal');
        $isSolution = $user->isAgent() && $request->boolean('is_solution');

        $reply = TicketReply::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => Auth::id(),
            'body'        => $request->body,
            'is_internal' => $isInternal,
            'is_solution' => $isSolution,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeAttachment($file, $ticket, $reply->id);
            }
        }

        if ($user->isAgent() && !$ticket->first_response_at) {
            $ticket->update(['first_response_at' => now()]);
        }

        if ($user->isAgent() && !$isInternal) {
            if ($isSolution) {
                $this->changeStatus($ticket, Ticket::STATUS_RESOLVED, Auth::id());
            } elseif ($ticket->status === Ticket::STATUS_OPEN) {
                $this->changeStatus($ticket, Ticket::STATUS_IN_PROGRESS, Auth::id());
            } elseif ($ticket->status === Ticket::STATUS_WAITING_CLIENT) {
                $this->changeStatus($ticket, Ticket::STATUS_IN_PROGRESS, Auth::id());
            }
        } elseif ($user->isClient() && $ticket->status === Ticket::STATUS_WAITING_CLIENT) {
            $this->changeStatus($ticket, Ticket::STATUS_IN_PROGRESS, Auth::id());
        }

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'action'    => 'replied',
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Réponse ajoutée.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        if (!Auth::user()->isAgent()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,waiting_client,resolved,closed',
        ]);

        $this->changeStatus($ticket, $request->status, Auth::id());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Statut mis à jour.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        if (!Auth::user()->isAgent()) {
            abort(403);
        }

        // Seul un membre du support actif peut recevoir un ticket.
        $request->validate([
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->whereIn('role', [User::ROLE_AGENT, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                    ->where('is_active', true),
            ],
        ]);

        $old = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $request->assigned_to ?: null]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'action'    => 'assigned',
            'old_value' => $old ? User::find($old)?->name : null,
            'new_value' => $request->assigned_to
                ? User::find($request->assigned_to)?->name
                : 'Non assigné',
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket assigné.');
    }

    public function updatePriority(Request $request, Ticket $ticket)
    {
        if (!Auth::user()->isAgent()) {
            abort(403);
        }

        $request->validate([
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $old = $ticket->priority;
        $ticket->update(['priority' => $request->priority]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'action'    => 'priority_changed',
            'old_value' => $old,
            'new_value' => $request->priority,
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Priorité mise à jour.');
    }

    public function rate(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if (!$user->isClient() || $ticket->user_id !== $user->id) {
            abort(403);
        }

        if (!$ticket->isResolved()) {
            abort(422);
        }

        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $ticket->update([
            'satisfaction_rating'  => $request->rating,
            'satisfaction_comment' => $request->comment,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Merci pour votre évaluation !');
    }

    private function changeStatus(Ticket $ticket, string $newStatus, int $userId): void
    {
        $old = $ticket->status;

        if ($old === $newStatus) {
            return;
        }

        $updates = ['status' => $newStatus];

        if ($newStatus === Ticket::STATUS_RESOLVED && !$ticket->resolved_at) {
            $updates['resolved_at'] = now();
        }

        if ($newStatus === Ticket::STATUS_CLOSED && !$ticket->closed_at) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $userId,
            'action'    => 'status_changed',
            'old_value' => $old,
            'new_value' => $newStatus,
        ]);
    }

    private function storeAttachment($file, Ticket $ticket, ?int $replyId): void
    {
        // Disque privé : servi uniquement par AttachmentController, après contrôle d'accès.
        $path = $file->store("attachments/ticket-{$ticket->id}", 'local');

        Attachment::create([
            'ticket_id'       => $ticket->id,
            'ticket_reply_id' => $replyId,
            'user_id'         => Auth::id(),
            'filename'        => $file->getClientOriginalName(),
            'path'            => $path,
            'mime_type'       => $file->getMimeType(),
            'size'            => $file->getSize(),
        ]);
    }

    private function getStats(User $user): array
    {
        $base = Ticket::query();

        if ($user->isClient()) {
            $base->where('user_id', $user->id);
        } elseif ($user->role === User::ROLE_AGENT) {
            $base->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereNull('assigned_to');
            });
        }

        return [
            'total'       => (clone $base)->count(),
            'open'        => (clone $base)->where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress' => (clone $base)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved'    => (clone $base)->whereIn('status', Ticket::RESOLVED_STATUSES)->count(),
            'critical'    => (clone $base)->where('priority', Ticket::PRIORITY_CRITICAL)
                                          ->whereNotIn('status', Ticket::RESOLVED_STATUSES)->count(),
            'overdue'     => (clone $base)->overdue()->count(),
        ];
    }
}