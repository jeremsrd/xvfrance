@extends('layouts.app')

@section('title', 'Records du XV de France — Victoires, séries et statistiques')

@section('meta_description', 'Les records du XV de France de rugby depuis 1906 : plus larges victoires, plus lourdes défaites, plus longues séries, bilan par décennie et meilleurs marqueurs d\'essais.')

@section('breadcrumb')
    <x-breadcrumb :items="['Records' => null]" />
@endsection

@php
    $share = fn ($n, $total) => $total > 0 ? number_format($n / $total * 100, 2, '.', '') : 0;
    $streakCards = [
        ['key' => 'wins', 'label' => 'Victoires consécutives', 'accent' => 'border-t-emerald-500'],
        ['key' => 'unbeaten', 'label' => 'Matches sans défaite', 'accent' => 'border-t-bleu-france'],
        ['key' => 'losses', 'label' => 'Défaites consécutives', 'accent' => 'border-t-rouge-france'],
    ];
    $venueCards = [
        'home' => 'À domicile',
        'away' => 'À l\'extérieur',
        'neutral' => 'Terrain neutre',
    ];
@endphp

@section('content')

    {{-- En-tête --}}
    <x-page.hero kicker="Statistiques" title="Records"
                 subtitle="Les plus belles victoires, les pires défaites et les plus longues séries du XV de France, calculées sur l'ensemble de ses matches." />

    {{-- Écarts --}}
    <section class="py-12" aria-labelledby="ecarts">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="ecarts" class="text-2xl sm:text-3xl font-bold tracking-tight text-encre">Écarts au score</h2>
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @include('records.partials.match-ranking', [
                    'title' => 'Plus larges victoires',
                    'matches' => $biggestWins,
                    'value' => fn ($m) => '+' . $m->point_diff,
                    'tone' => 'emerald',
                ])
                @include('records.partials.match-ranking', [
                    'title' => 'Plus lourdes défaites',
                    'matches' => $heaviestDefeats,
                    'value' => fn ($m) => '−' . abs($m->point_diff),
                    'tone' => 'red',
                ])
            </div>
        </div>
    </section>

    {{-- Séries --}}
    <section class="bg-papier-2/60 py-12" aria-labelledby="series">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="series" class="text-2xl sm:text-3xl font-bold tracking-tight text-encre">Séries</h2>
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @foreach($streakCards as $card)
                    @php $streak = $streaks[$card['key']]; @endphp
                    <div class="rounded-xl border border-filet border-t-4 {{ $card['accent'] }} bg-white p-6 shadow-xs">
                        <div class="flex items-start justify-between gap-3">
                            <span class="font-display text-6xl font-bold tabular-nums text-encre">{{ $streak['length'] ?? 0 }}</span>
                            @if($streak['ongoing'] ?? false)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">En cours</span>
                            @endif
                        </div>
                        <div class="mt-1 font-medium text-texte">{{ $card['label'] }}</div>
                        @if($streak)
                            <div class="mt-4 border-t border-filet pt-4 text-sm text-texte-2">
                                Du <a href="{{ route('matches.show', $streak['from']) }}" class="font-medium text-bleu-france hover:underline">{{ $streak['from']->match_date->format('d/m/Y') }}</a>
                                ({{ $streak['from']->opponent->name }})
                                au <a href="{{ route('matches.show', $streak['to']) }}" class="font-medium text-bleu-france hover:underline">{{ $streak['to']->match_date->format('d/m/Y') }}</a>
                                ({{ $streak['to']->opponent->name }})
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Points --}}
    <section class="py-12" aria-labelledby="points">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="points" class="text-2xl sm:text-3xl font-bold tracking-tight text-encre">Points dans un match</h2>
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @include('records.partials.match-ranking', [
                    'title' => 'Plus de points marqués',
                    'matches' => $mostPointsScored,
                    'value' => fn ($m) => $m->france_score,
                ])
                @include('records.partials.match-ranking', [
                    'title' => 'Plus de points encaissés',
                    'matches' => $mostPointsConceded,
                    'value' => fn ($m) => $m->opponent_score,
                ])
            </div>
        </div>
    </section>

    {{-- Bilans --}}
    <section class="bg-papier-2/60 py-12" aria-labelledby="bilans">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="bilans" class="text-2xl sm:text-3xl font-bold tracking-tight text-encre">Bilans</h2>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($venueCards as $key => $label)
                    @php $venueRecord = $venueTypes[$key]; @endphp
                    @continue($venueRecord->total === 0)
                    <div class="rounded-xl border border-filet bg-white p-6 shadow-xs">
                        <div class="text-sm font-medium uppercase tracking-wider text-texte-2">{{ $label }}</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="font-display text-4xl font-bold tabular-nums text-encre">{{ $venueRecord->winPctLabel() }}</span>
                            <span class="text-sm text-texte-2">de victoires</span>
                        </div>
                        <div class="mt-1 text-sm text-texte-2">
                            {{ $venueRecord->total }} matches · {{ $venueRecord->wins }} V · {{ $venueRecord->draws }} N · {{ $venueRecord->losses }} D
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 overflow-x-auto rounded-xl border border-filet bg-white shadow-xs">
                <table class="w-full min-w-[36rem] text-sm">
                    <caption class="sr-only">Bilan du XV de France par décennie</caption>
                    <thead>
                        <tr class="border-b border-filet text-left text-xs uppercase tracking-wider text-texte-2">
                            <th scope="col" class="px-5 py-3 font-medium">Décennie</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Matches</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">V</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">N</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">D</th>
                            <th scope="col" class="w-1/3 px-5 py-3 font-medium">Répartition</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Victoires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-filet">
                        @foreach($decades as $decade => $decadeRecord)
                            <tr>
                                <th scope="row" class="px-5 py-3 text-left font-display text-lg font-semibold text-encre">{{ $decade }}–{{ $decade + 9 }}</th>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->total }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->wins }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->draws }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->losses }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex h-2.5 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                                        <span class="bg-gagne" style="width: {{ $share($decadeRecord->wins, $decadeRecord->total) }}%"></span>
                                        <span class="bg-egal" style="width: {{ $share($decadeRecord->draws, $decadeRecord->total) }}%"></span>
                                        <span class="bg-rouge-france" style="width: {{ $share($decadeRecord->losses, $decadeRecord->total) }}%"></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold tabular-nums text-encre">{{ $decadeRecord->winPctLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-texte-2">Le faible nombre de matches des années 1930 s'explique par l'exclusion de la France du Tournoi de 1932 à 1939.</p>
        </div>
    </section>

    {{-- Records individuels --}}
    <section class="py-12" aria-labelledby="joueurs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="joueurs" class="text-2xl sm:text-3xl font-bold tracking-tight text-encre">Records individuels</h2>
            <p class="mt-3 max-w-3xl rounded-lg bg-or/10 px-4 py-3 text-sm text-encre ring-1 ring-inset ring-or/30">
                Classements établis sur les {{ $detailedMatchCount }} matches dont la feuille de match complète est saisie.
                Ils s'enrichiront au fil de l'ajout des compositions historiques.
            </p>
            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                @include('records.partials.player-ranking', ['title' => 'Essais', 'rows' => $topTryScorers, 'unit' => ['essai', 'essais']])
                @include('records.partials.player-ranking', ['title' => 'Feuilles de match', 'rows' => $mostAppearances, 'unit' => ['match', 'matches']])
                @include('records.partials.player-ranking', ['title' => 'Capitanats', 'rows' => $mostCaptaincies, 'unit' => ['match', 'matches']])
            </div>
        </div>
    </section>

@endsection
