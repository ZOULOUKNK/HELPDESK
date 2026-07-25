@extends('layouts.app')
@section('title','Tableau de bord')
@section('page-title','Tableau de bord')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Bonjour, {{ Auth::user()->name }} </h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ now()->translatedFormat('l d F Y') }}</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau ticket
        </a>
    </div>

    @if(Auth::user()->isAgent())

    {{-- KPIs agent --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
        $kpis = [
            ['l'=>'Ouverts',           'v'=>$stats['open_total'],     'bg'=>'bg-blue-50',   'text'=>'text-blue-900',   'sub'=>'text-blue-600'],
            ['l'=>'En cours',          'v'=>$stats['in_progress'],    'bg'=>'bg-amber-50',  'text'=>'text-amber-900',  'sub'=>'text-amber-600'],
            ['l'=>'Mes tickets',       'v'=>$stats['my_assigned'],    'bg'=>'bg-indigo-50', 'text'=>'text-indigo-900', 'sub'=>'text-indigo-600'],
            ['l'=>'Résolus auj.',      'v'=>$stats['resolved_today'], 'bg'=>'bg-green-50',  'text'=>'text-green-900',  'sub'=>'text-green-600'],
            ['l'=>'En retard',         'v'=>$stats['overdue'],        'bg'=>'bg-red-50',    'text'=>'text-red-900',    'sub'=>'text-red-600'],
            ['l'=>'Note moyenne',      'v'=>$stats['avg_rating']?:'-','bg'=>'bg-amber-50',  'text'=>'text-amber-900',  'sub'=>'text-amber-600'],
        ];
        @endphp
        @foreach($kpis as $k)
        <div class="{{ $k['bg'] }} rounded-xl p-4 border border-slate-200">
            <p class="text-2xl font-bold {{ $k['text'] }}">{{ $k['v'] }}</p>
            <p class="text-xs font-medium {{ $k['sub'] }} mt-1">{{ $k['l'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Tickets ouverts par priorité</h3>
            @php
            $pData = ['critical'=>['Critique','bg-red-500','bg-red-100'],'high'=>['Élevée','bg-orange-500','bg-orange-100'],'medium'=>['Moyenne','bg-amber-500','bg-amber-100'],'low'=>['Faible','bg-green-500','bg-green-100']];
            $maxP = max($byPriority->max() ?: 1, 1);
            @endphp
            @foreach($pData as $prio => [$label,$bar,$bg])
            @php $n = $byPriority[$prio] ?? 0; @endphp
            <div class="flex items-center gap-3 mb-3 last:mb-0">
                <span class="text-xs text-slate-600 w-16 flex-shrink-0">{{ $label }}</span>
                <div class="flex-1 {{ $bg }} rounded-full h-2 overflow-hidden">
                    <div class="{{ $bar }} h-full rounded-full transition-all" style="width:{{ $maxP>0 ? round($n/$maxP*100) : 0 }}%"></div>
                </div>
                <span class="text-xs font-bold text-slate-700 w-5 text-right">{{ $n }}</span>
            </div>
            @endforeach
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Répartition par statut</h3>
            @php
            $sData = ['open'=>['Ouvert','bg-blue-500'],'in_progress'=>['En cours','bg-amber-500'],'waiting_client'=>['Attente client','bg-orange-500'],'resolved'=>['Résolu','bg-green-500'],'closed'=>['Fermé','bg-slate-400']];
            $tot = max($byStatus->sum() ?: 1, 1);
            @endphp
            @foreach($sData as $status => [$label,$bar])
            @php $n = $byStatus[$status] ?? 0; $pct = round($n/$tot*100); @endphp
            <div class="flex items-center gap-3 mb-3 last:mb-0">
                <span class="text-xs text-slate-600 w-28 flex-shrink-0">{{ $label }}</span>
                <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="{{ $bar }} h-full rounded-full" style="width:{{ $pct }}%"></div>
                </div>
                <span class="text-xs text-slate-500 w-12 text-right">{{ $n }} ({{ $pct }}%)</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Agent workload --}}
    @if($agentLoad->count())
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Charge des agents</h3>
        <div class="divide-y divide-slate-100">
            @foreach($agentLoad as $ag)
            @php $load = min(($ag->open_count / 15) * 100, 100); $lc = $load>80?'bg-red-500':($load>50?'bg-amber-500':'bg-green-500'); @endphp
            <div class="py-3 flex items-center gap-4">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($ag->name,0,2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $ag->name }}</p>
                    <p class="text-xs text-slate-400">{{ ucfirst($ag->role) }}</p>
                </div>
                <span class="text-sm font-bold text-slate-700 w-6 text-right">{{ $ag->open_count }}</span>
                <div class="bg-slate-100 rounded-full h-2 w-24 flex-shrink-0">
                    <div class="{{ $lc }} h-full rounded-full" style="width:{{ $load }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @else
    {{-- Client KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach([['Total',$stats['total'],'text-slate-900'],['Ouverts',$stats['open'],'text-blue-700'],['En cours',$stats['pending'],'text-amber-700'],['Résolus',$stats['resolved'],'text-green-700']] as [$l,$v,$c])
        <div class="card p-5 text-center">
            <p class="text-3xl font-bold {{ $c }}">{{ $v }}</p>
            <p class="text-xs font-medium text-slate-500 mt-1">{{ $l }}</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Recent tickets --}}
    <div class="card">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-900">Tickets récents</h3>
            <a href="{{ route('tickets.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Voir tout →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentTickets as $ticket)
            <a href="{{ route('tickets.show',$ticket) }}"
               class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                        <span class="font-mono text-xs text-slate-400">{{ $ticket->reference }}</span>
                        <span class="status-{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
                        <span class="priority-{{ $ticket->priority }}">{{ $ticket->priorityLabel() }}</span>
                    </div>
                    <p class="text-sm font-medium text-slate-900 truncate">{{ $ticket->title }}</p>
                    @if(Auth::user()->isAgent())
                    <p class="text-xs text-slate-400 mt-0.5">
                        Par {{ $ticket->user->name }}
                        @if($ticket->agent) · Assigné à {{ $ticket->agent->name }}@endif
                    </p>
                    @endif
                </div>
                <span class="text-xs text-slate-400 whitespace-nowrap flex-shrink-0">{{ $ticket->updated_at->diffForHumans() }}</span>
                <svg class="w-4 h-4 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @empty
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-slate-400 mb-3">Aucun ticket pour le moment</p>
                <a href="{{ route('tickets.create') }}" class="btn-primary text-sm">Créer mon premier ticket</a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection