@extends('layouts.app')
@section('title','Nouveau ticket')
@section('page-title','Soumettre un ticket')

@section('content')
<div class="max-w-3xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('tickets.index') }}" class="hover:text-slate-700">Tickets</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 font-medium">Nouveau ticket</span>
    </nav>

    <div class="card p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Décrire votre problème</h2>
            <p class="text-sm text-slate-500 mt-1">Plus votre description est précise, plus nous pourrons vous aider rapidement.</p>
        </div>

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="form-label" for="title">Sujet <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                       class="form-input @error('title') border-red-400 @enderror"
                       placeholder="Ex : Impossible de me connecter à l'application RH">
                @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="priority">Priorité <span class="text-red-500">*</span></label>
                    <select id="priority" name="priority" class="form-input" onchange="updateSla(this.value)">
                        <option value="low"      {{ old('priority')==='low'      ?'selected':'' }}>🟢 Faible</option>
                        <option value="medium"   {{ old('priority','medium')==='medium'?'selected':'' }}>🟡 Moyenne</option>
                        <option value="high"     {{ old('priority')==='high'     ?'selected':'' }}>🟠 Élevée</option>
                        <option value="critical" {{ old('priority')==='critical' ?'selected':'' }}>🔴 Critique</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="category">Catégorie <span class="text-red-500">*</span></label>
                    <select id="category" name="category" class="form-input">
                        <option value="bug">🐛 Bug / Erreur</option>
                        <option value="feature_request">✨ Fonctionnalité</option>
                        <option value="access">🔑 Accès / Droits</option>
                        <option value="hardware">💻 Matériel</option>
                        <option value="software">📦 Logiciel</option>
                        <option value="network">🌐 Réseau</option>
                        <option value="other">❓ Autre</option>
                    </select>
                </div>
            </div>

            <div id="sla-bar"
                 class="flex items-center gap-2 px-3 py-2.5 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Délai de réponse garanti : <strong id="sla-label">24 heures</strong>
            </div>

            <div>
                <label class="form-label" for="description">Description détaillée <span class="text-red-500">*</span></label>
                <textarea id="description" name="description" rows="7" required
                          class="form-input resize-none @error('description') border-red-400 @enderror"
                          placeholder="Décrivez précisément :&#10;• Quand le problème survient-il ?&#10;• Quelles étapes avez-vous effectuées ?&#10;• Quel message d'erreur apparaît ?&#10;• Depuis quand ce problème existe-t-il ?">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            @if($tags->count())
            <div>
                <label class="form-label">Étiquettes</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array($tag->id, old('tags',[])) ? 'checked' : '' }}>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium border"
                              style="background:{{ $tag->color }}18;color:{{ $tag->color }};border-color:{{ $tag->color }}44">
                            {{ $tag->name }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div x-data="{
                files:[],
                dragging:false,
                addF(e){ [...e.target.files].forEach(f=>this.files.push(f)) },
                dropF(e){ this.dragging=false; [...e.dataTransfer.files].forEach(f=>this.files.push(f)) },
                removeF(i){ this.files.splice(i,1) },
                fmt(b){ return b<1024?b+' o':b<1048576?(b/1024).toFixed(1)+' Ko':(b/1048576).toFixed(1)+' Mo' }
            }">
                <label class="form-label">Pièces jointes</label>
                <div @dragover.prevent="dragging=true" @dragleave="dragging=false" @drop.prevent="dropF($event)"
                     :class="dragging?'border-blue-400 bg-blue-50':'border-slate-300 bg-slate-50'"
                     class="border-2 border-dashed rounded-xl p-5 text-center transition-colors">
                    <svg class="w-7 h-7 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm text-slate-500">
                        Glissez vos fichiers ou
                        <label class="text-blue-600 font-medium cursor-pointer hover:text-blue-800">
                            parcourir
                            <input type="file" name="files[]" multiple class="hidden" @change="addF($event)">
                        </label>
                    </p>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG, PDF, DOC, XLS, ZIP — Max 10 Mo</p>
                </div>
                <template x-for="(f,i) in files" :key="i">
                    <div class="flex items-center gap-3 mt-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="flex-1 truncate text-slate-700" x-text="f.name"></span>
                        <span class="text-slate-400 text-xs" x-text="fmt(f.size)"></span>
                        <button type="button" @click="removeF(i)" class="text-slate-400 hover:text-red-500 transition-colors">✕</button>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                <a href="{{ route('tickets.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Soumettre le ticket
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const slaMap = {
    critical:{t:'4 heures',c:'red'},
    high:{t:'8 heures',c:'orange'},
    medium:{t:'24 heures',c:'blue'},
    low:{t:'72 heures',c:'green'}
};
function updateSla(val) {
    const s = slaMap[val] || slaMap.medium;
    document.getElementById('sla-label').textContent = s.t;
    const bar = document.getElementById('sla-bar');
    bar.className = `flex items-center gap-2 px-3 py-2.5 bg-${s.c}-50 border border-${s.c}-200 rounded-lg text-sm text-${s.c}-700`;
}
</script>
@endpush