@extends('layouts.app')

@section('title', 'Records du XV de France — Victoires, séries et statistiques')

@section('meta_description', 'Les records du XV de France de rugby depuis 1906 : plus larges victoires, plus lourdes défaites, plus longues séries, bilan par décennie et meilleurs marqueurs d\'essais.')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <span class="text-gray-700">Records</span>
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
    <section class="bg-bleu-france text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="flex items-center gap-3 text-sm font-medium uppercase tracking-[0.2em] text-blue-200">
                <span class="flex h-1 w-10 overflow-hidden rounded-full" aria-hidden="true">
                    <span class="flex-1 bg-blue-400"></span>
                    <span class="flex-1 bg-white"></span>
                    <span class="flex-1 bg-rouge-france"></span>
                </span>
                Statistiques
            </div>
            <h1 class="mt-4 font-display text-5xl sm:text-6xl font-bold uppercase leading-[0.9] tracking-tight">Records</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-blue-100">
                Les plus belles victoires, les pires défaites et les plus longues séries du XV de France,
                calculées sur l'ensemble de ses matches.
            </p>
        </div>
    </section>

    {{-- Écarts --}}
    <section class="py-12" aria-labelledby="ecarts">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="ecarts" class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Écarts au score</h2>
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
    <section class="bg-slate-50 py-12" aria-labelledby="series">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="series" class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Séries</h2>
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @foreach($streakCards as $card)
                    @php $streak = $streaks[$card['key']]; @endphp
                    <div class="rounded-xl border border-slate-200 border-t-4 {{ $card['accent'] }} bg-white p-6 shadow-xs">
                        <div class="flex items-start justify-between gap-3">
                            <span class="font-display text-6xl font-bold tabular-nums text-slate-900">{{ $streak['length'] ?? 0 }}</span>
                            @if($streak['ongoing'] ?? false)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">En cours</span>
                            @endif
                        </div>
                        <div class="mt-1 font-medium text-slate-700">{{ $card['label'] }}</div>
                        @if($streak)
                            <div class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="points" class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Points dans un match</h2>
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
    <section class="bg-slate-50 py-12" aria-labelledby="bilans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="bilans" class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Bilans</h2>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($venueCards as $key => $label)
                    @php $venueRecord = $venueTypes[$key]; @endphp
                    @continue($venueRecord->total === 0)
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                        <div class="text-sm font-medium uppercase tracking-wider text-slate-500">{{ $label }}</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="font-display text-4xl font-bold tabular-nums text-slate-900">{{ $venueRecord->winPctLabel() }}</span>
                            <span class="text-sm text-slate-500">de victoires</span>
                        </div>
                        <div class="mt-1 text-sm text-slate-600">
                            {{ $venueRecord->total }} matches · {{ $venueRecord->wins }} V · {{ $venueRecord->draws }} N · {{ $venueRecord->losses }} D
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
                <table class="w-full min-w-[36rem] text-sm">
                    <caption class="sr-only">Bilan du XV de France par décennie</caption>
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wider text-slate-500">
                            <th scope="col" class="px-5 py-3 font-medium">Décennie</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Matches</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">V</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">N</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">D</th>
                            <th scope="col" class="w-1/3 px-5 py-3 font-medium">Répartition</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Victoires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($decades as $decade => $decadeRecord)
                            <tr>
                                <th scope="row" class="px-5 py-3 text-left font-display text-lg font-semibold text-slate-900">{{ $decade }}–{{ $decade + 9 }}</th>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->total }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->wins }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->draws }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ $decadeRecord->losses }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex h-2.5 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                        <span class="bg-emerald-500" style="width: {{ $share($decadeRecord->wins, $decadeRecord->total) }}%"></span>
                                        <span class="bg-amber-300" style="width: {{ $share($decadeRecord->draws, $decadeRecord->total) }}%"></span>
                                        <span class="bg-rouge-france" style="width: {{ $share($decadeRecord->losses, $decadeRecord->total) }}%"></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold tabular-nums text-slate-900">{{ $decadeRecord->winPctLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-slate-500">Le faible nombre de matches des années 1930 s'explique par l'exclusion de la France du Tournoi de 1932 à 1939.</p>
        </div>
    </section>

    {{-- Records individuels --}}
    <section class="py-12" aria-labelledby="joueurs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="joueurs" class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Records individuels</h2>
            <p class="mt-3 max-w-3xl rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
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
