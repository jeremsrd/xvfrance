@section('title', 'Tous les matches du XV de France depuis 1906')

@section('meta_description', 'Les ' . $allCount . ' matches du XV de France depuis 1906 : recherche par adversaire, compétition, décennie et résultat.')

@section('breadcrumb')
    <x-breadcrumb :items="['Matches' => null]" />
@endsection

@php
    $fr = fn ($n) => number_format($n, 0, ',', "\u{202F}");
    $field = 'h-11 w-full rounded-full border border-filet bg-white px-4 text-sm text-encre shadow-xs focus:border-bleu-france focus:outline-hidden focus:ring-2 focus:ring-bleu-france/30';
    $share = fn ($n) => $summary->total ? round($n / $summary->total * 100, 2) : 0;
@endphp

<div>
    <x-page.hero kicker="Les archives" title="Les matches"
                 :subtitle="'Les ' . $fr($allCount) . ' rencontres du XV de France, du premier test de 1906 à aujourd\'hui.'">
        <div class="mt-8 flex flex-wrap items-end gap-x-10 gap-y-4" aria-live="polite">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-300">{{ $filtered ? 'Sélection' : 'Bilan' }}</div>
                <div class="mt-1 font-display text-4xl font-bold tabular-nums">{{ $fr($summary->total) }} <span class="text-xl font-semibold text-blue-200">match{{ $summary->total > 1 ? 'es' : '' }}</span></div>
            </div>
            @if($summary->total)
                <div class="min-w-64 flex-1 sm:max-w-md">
                    <div class="flex justify-between text-sm tabular-nums">
                        <span><span class="font-semibold text-emerald-300">{{ $summary->wins }}</span> <span class="text-blue-200">V</span></span>
                        <span><span class="font-semibold text-amber-200">{{ $summary->draws }}</span> <span class="text-blue-200">N</span></span>
                        <span><span class="font-semibold text-red-300">{{ $summary->losses }}</span> <span class="text-blue-200">D</span></span>
                        <span class="text-blue-100">{{ $summary->winPctLabel() }}</span>
                    </div>
                    <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                        <span class="bg-emerald-400" style="width: {{ $share($summary->wins) }}%"></span>
                        <span class="bg-amber-300" style="width: {{ $share($summary->draws) }}%"></span>
                        <span class="bg-rouge-france" style="width: {{ $share($summary->losses) }}%"></span>
                    </div>
                </div>
            @endif
        </div>
    </x-page.hero>

    {{-- ═══ Filtres ═══ --}}
    <section class="border-b border-filet bg-papier-2/60" aria-label="Filtres">
        <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))]">
                <label class="relative col-span-2 block lg:col-span-1">
                    <span class="sr-only">Rechercher un adversaire</span>
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-texte-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Adversaire (ex. Galles)" class="{{ $field }} pl-10">
                </label>
                <label>
                    <span class="sr-only">Compétition</span>
                    <select wire:model.live="competition" class="{{ $field }}">
                        <option value="">Toutes les compétitions</option>
                        @foreach($competitions as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Décennie</span>
                    <select wire:model.live="decade" class="{{ $field }}">
                        <option value="">Toutes les époques</option>
                        @for($d = 2020; $d >= 1900; $d -= 10)
                            <option value="{{ $d }}">Années {{ $d }}</option>
                        @endfor
                    </select>
                </label>
                <label>
                    <span class="sr-only">Résultat</span>
                    <select wire:model.live="result" class="{{ $field }}">
                        <option value="">Tous les résultats</option>
                        <option value="victoire">Victoires</option>
                        <option value="nul">Nuls</option>
                        <option value="defaite">Défaites</option>
                    </select>
                </label>
                <label>
                    <span class="sr-only">Lieu</span>
                    <select wire:model.live="location" class="{{ $field }}">
                        <option value="">Tous les lieux</option>
                        <option value="domicile">À domicile</option>
                        <option value="exterieur">À l'extérieur</option>
                    </select>
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm text-texte-2">
                    Trier par
                    <select wire:model.live="order" class="h-9 rounded-full border border-filet bg-white px-3 text-sm text-encre focus:border-bleu-france focus:outline-hidden focus:ring-2 focus:ring-bleu-france/30">
                        <option value="recent">les plus récents</option>
                        <option value="ancien">les plus anciens</option>
                        <option value="points">le plus de points marqués</option>
                    </select>
                </label>
                @if($filtered || $order !== 'recent')
                    <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-bleu-france hover:underline focus-visible:outline-hidden focus-visible:underline">
                        Effacer les filtres
                    </button>
                @endif
            </div>
        </div>
    </section>

    {{-- ═══ Liste ═══ --}}
    <section id="liste" class="scroll-mt-4 py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8" wire:loading.class="opacity-60" wire:target="search,competition,decade,result,location,order,gotoPage,nextPage,previousPage">
            @if($matches->isEmpty())
                <div class="rounded-2xl border border-dashed border-filet p-10 text-center">
                    <p class="font-display text-2xl font-bold uppercase text-encre">Aucun match</p>
                    <p class="mt-1 font-serif text-texte-2">Aucune rencontre ne correspond à ces critères.</p>
                </div>
            @else
                @php $byYear = $order !== 'points'; $currentYear = null; @endphp
                <div class="overflow-hidden rounded-2xl border border-filet bg-white">
                    @foreach($matches as $match)
                        @if($byYear && $match->match_date->year !== $currentYear)
                            @php $currentYear = $match->match_date->year; @endphp
                            <div class="flex items-baseline gap-3 border-b border-filet bg-papier px-5 py-2 {{ $loop->first ? '' : 'border-t' }}">
                                <span class="font-display text-xl font-bold tabular-nums text-encre">{{ $currentYear }}</span>
                            </div>
                        @endif
                        <x-match.row :match="$match" :venue="true" class="{{ $loop->last ? '' : 'border-b border-filet' }}" wire:key="m-{{ $match->id }}" />
                    @endforeach
                </div>
                <div class="mt-6">{{ $matches->links() }}</div>
            @endif
        </div>
    </section>
</div>
