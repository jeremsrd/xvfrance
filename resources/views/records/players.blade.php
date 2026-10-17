@extends('layouts.app')

@section('title', 'Records des joueurs du XV de France — Sélections, essais et points')

@section('meta_description', 'Les records individuels du XV de France de rugby depuis 1906 : joueurs les plus sélectionnés, meilleurs marqueurs d\'essais et de points, capitaines, et les recordmen successifs de chaque record.')

@section('breadcrumb')
    <x-breadcrumb :items="['Records' => route('records.index'), 'Joueurs' => null]" />
@endsection

@php
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france';

    $sections = ['classements' => 'Classements', 'recordmen' => 'Recordmen'];

    // Onglets des recordmen successifs : libellé, unité, formulation du record franchi
    $tabs = [
        'selections' => ['Sélections', ['sélection', 'sélections'], fn ($n) => "avec sa {$n}<sup>e</sup> sélection"],
        'essais' => ['Essais', ['essai', 'essais'], fn ($n) => $n === 1 ? 'avec son premier essai' : "avec son {$n}<sup>e</sup> essai"],
        'points' => ['Points', ['point', 'points'], fn ($n) => "en le portant à {$n} points"],
    ];
@endphp

@section('content')
    <x-page.hero kicker="Statistiques" title="Records des joueurs"
                 subtitle="Les joueurs les plus sélectionnés, les meilleurs marqueurs d'essais et de points du XV de France, et ceux qui ont détenu chaque record depuis 1906.">
        <nav class="mt-8 flex flex-wrap gap-2" aria-label="Sections de la page">
            @foreach($sections as $id => $label)
                <a href="#{{ $id }}" class="rounded-full border border-white/20 px-3.5 py-1.5 text-sm font-medium text-blue-100 hover:border-white/50 hover:text-white {{ $focus }}">{{ $label }}</a>
            @endforeach
            <a href="{{ route('records.index') }}" class="rounded-full bg-white px-3.5 py-1.5 text-sm font-semibold text-encre hover:bg-blue-50 {{ $focus }}">Records de l'équipe →</a>
        </nav>
    </x-page.hero>

    {{-- ═══ Classements ═══ --}}
    <section id="classements" class="scroll-mt-4 py-12 lg:py-16" aria-labelledby="classements-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="classements-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Classements</h2>
            <p class="mt-2 max-w-2xl font-serif text-texte-2">
                Remplaçants restés sur le banc exclus, points selon le barème de chaque époque.
                @if($detailedMatchCount < $matchCount)
                    Calculés sur les {{ $detailedMatchCount }} matches dont la feuille de match est saisie, sur {{ $matchCount }}.
                @endif
            </p>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @include('records.partials.player-ranking', ['title' => 'Sélections', 'rows' => $mostAppearances, 'unit' => ['sélection', 'sélections']])
                @include('records.partials.player-ranking', ['title' => 'Essais', 'rows' => $topTryScorers, 'unit' => ['essai', 'essais']])
                @include('records.partials.player-ranking', ['title' => 'Points', 'rows' => $topPointScorers, 'unit' => ['point', 'points']])
                @include('records.partials.player-ranking', ['title' => 'Capitanats', 'rows' => $mostCaptaincies, 'unit' => ['match', 'matches']])
            </div>
        </div>
    </section>

    {{-- ═══ Recordmen successifs, un onglet par record (#recordmen-essais ouvre directement l'onglet) ═══ --}}
    <section id="recordmen" class="scroll-mt-4 border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="recordmen-titre"
             x-data="{ tab: 'selections', show(key) { this.tab = key; history.replaceState(null, '', '#recordmen-' + key) } }"
             x-init="const key = location.hash.replace('#recordmen-', ''); if (@js(array_keys($tabs)).includes(key)) { tab = key; $el.scrollIntoView() }">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="recordmen-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Recordmen</h2>
            <p class="mt-2 max-w-2xl font-serif text-texte-2">Chaque record individuel du XV de France, de main en main depuis 1906.</p>

            <div class="mt-8 inline-flex rounded-full bg-papier-2 p-1" role="tablist" aria-label="Record">
                @foreach($tabs as $key => [$label])
                    <button type="button" role="tab" id="onglet-{{ $key }}" aria-controls="recordmen-{{ $key }}"
                            @click="show('{{ $key }}')" :aria-selected="tab === '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-encre text-white shadow-sm' : 'text-texte-2 hover:text-encre'"
                            class="min-h-[40px] rounded-full px-4 text-sm font-semibold motion-safe:transition-colors {{ $focus }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @foreach($tabs as $key => [$label, $unit, $reached])
                <div id="recordmen-{{ $key }}" role="tabpanel" aria-labelledby="onglet-{{ $key }}"
                     x-show="tab === '{{ $key }}'" @if(!$loop->first) x-cloak @endif>
                    @if($recordHistories[$key]->isNotEmpty())
                        @include('records.partials.record-history', ['history' => $recordHistories[$key], 'unit' => $unit, 'reached' => $reached])
                    @else
                        <p class="mt-6 text-sm text-texte-2">Pas encore de données.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endsection
