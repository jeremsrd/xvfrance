@extends('layouts.app')

@section('title', 'Records du XV de France — Victoires, séries et statistiques')

@section('meta_description', 'Les records du XV de France de rugby depuis 1906 : plus larges victoires, plus lourdes défaites, plus longues séries, records face à chaque adversaire et bilan par décennie.')

@section('breadcrumb')
    <x-breadcrumb :items="['Records' => null]" />
@endsection

@php
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france';
    $score = fn ($m) => $m->france_score . '–' . $m->opponent_score;
    $period = fn ($streak) => $streak['from']->match_date->translatedFormat('M Y') . ' → ' . ($streak['ongoing'] ? 'en cours' : $streak['to']->match_date->translatedFormat('M Y'));

    $headline = array_filter([
        $biggestWins->first() ? [
            'label' => 'Plus large victoire',
            'value' => '+' . $biggestWins->first()->point_diff,
            'tone' => 'text-gagne',
            'detail' => $score($biggestWins->first()) . ' face à ' . $biggestWins->first()->opponent->name,
            'date' => $biggestWins->first()->match_date->format('d/m/Y'),
            'href' => route('matches.show', $biggestWins->first()),
        ] : null,
        $heaviestDefeats->first() ? [
            'label' => 'Plus lourde défaite',
            'value' => '−' . abs($heaviestDefeats->first()->point_diff),
            'tone' => 'text-perdu',
            'detail' => $score($heaviestDefeats->first()) . ' face à ' . $heaviestDefeats->first()->opponent->name,
            'date' => $heaviestDefeats->first()->match_date->format('d/m/Y'),
            'href' => route('matches.show', $heaviestDefeats->first()),
        ] : null,
        $streaks['wins'] ? [
            'label' => 'Victoires de rang',
            'value' => $streaks['wins']['length'],
            'tone' => 'text-encre',
            'detail' => 'du ' . $streaks['wins']['from']->match_date->format('d/m/Y') . ' (' . $streaks['wins']['from']->opponent->name . ')',
            'date' => $streaks['wins']['ongoing'] ? 'série en cours' : 'au ' . $streaks['wins']['to']->match_date->format('d/m/Y') . ' (' . $streaks['wins']['to']->opponent->name . ')',
            'href' => route('matches.show', $streaks['wins']['from']),
        ] : null,
        $bestYear ? [
            'label' => 'Meilleure année',
            'value' => $bestYear['year'],
            'tone' => 'text-or-2',
            'detail' => $bestYear['record']->wins . ' victoire' . ($bestYear['record']->wins > 1 ? 's' : '') . ' en ' . $bestYear['record']->total . ' matches',
            'date' => $bestYear['record']->winPctLabel(0) . ' de victoires',
            'href' => route('matches.index', ['decade' => intdiv($bestYear['year'], 10) * 10]),
        ] : null,
    ]);

    $scoreCards = [
        'mostScored' => ['Le plus de points marqués', fn ($m) => $m->france_score . ' points'],
        'mostConceded' => ['Le plus de points encaissés', fn ($m) => $m->opponent_score . ' points'],
        'highestAggregate' => ['Le match le plus prolifique', fn ($m) => ($m->france_score + $m->opponent_score) . ' points au total'],
        'mostScoredInDefeat' => ['Le plus de points dans une défaite', fn ($m) => $m->france_score . ' points marqués'],
        'mostConcededInWin' => ['Le plus de points encaissés dans une victoire', fn ($m) => $m->opponent_score . ' points encaissés'],
        'highestDraw' => ['Le nul le plus prolifique', fn ($m) => $m->france_score . ' partout'],
    ];

    $streakCards = [
        'wins' => ['Victoires consécutives', 'bg-gagne'],
        'unbeaten' => ['Matches sans défaite', 'bg-bleu-france'],
        'homeWins' => ['Victoires de rang à domicile', 'bg-or'],
        'losses' => ['Défaites consécutives', 'bg-perdu'],
    ];

    $venueCards = ['home' => 'En France', 'away' => 'Hors de France', 'neutral' => 'Terrain neutre'];

    $sections = ['phares' => 'Records phares', 'ecarts' => 'Écarts', 'scores' => 'Scores', 'series' => 'Séries', 'adversaires' => 'Par adversaire', 'joueurs' => 'Joueurs', 'epoques' => 'Époques'];
@endphp

@section('content')
    <x-page.hero kicker="Statistiques" title="Records"
                 :subtitle="'Les plus belles victoires, les pires défaites et les plus longues séries du XV de France, établies sur ses ' . $decades->sum('total') . ' matches depuis ' . ($firstMatch?->match_date->year ?? 1906) . '.'">
        <nav class="mt-8 flex flex-wrap gap-2" aria-label="Sections de la page">
            @foreach($sections as $id => $label)
                <a href="#{{ $id }}" class="rounded-full border border-white/20 px-3.5 py-1.5 text-sm font-medium text-blue-100 hover:border-white/50 hover:text-white {{ $focus }}">{{ $label }}</a>
            @endforeach
        </nav>
    </x-page.hero>

    {{-- ═══ Records phares ═══ --}}
    <section id="phares" class="scroll-mt-4 py-12 lg:py-16" aria-labelledby="phares-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="phares-titre" class="sr-only">Records phares</h2>
            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach($headline as $card)
                    <li>
                        <a href="{{ $card['href'] }}" class="group flex h-full flex-col rounded-2xl border border-filet bg-white p-4 hover:border-encre/30 sm:p-6 {{ $focus }}">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-texte-2 sm:text-xs sm:tracking-[0.18em]">{{ $card['label'] }}</span>
                            <span class="mt-3 font-display text-5xl sm:text-7xl font-bold leading-none tabular-nums {{ $card['tone'] }}">{{ $card['value'] }}</span>
                            <span class="mt-3 font-serif text-sm text-encre group-hover:underline sm:mt-4 sm:text-base">{{ $card['detail'] }}</span>
                            <span class="mt-1 text-xs text-texte-2 sm:text-sm">{{ $card['date'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ═══ Écarts au score ═══ --}}
    <section id="ecarts" class="scroll-mt-4 border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="ecarts-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="ecarts-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Écarts au score</h2>
            <p class="mt-2 max-w-2xl font-serif text-texte-2">Les dix plus larges victoires et les dix plus lourdes défaites de l'histoire du XV de France.</p>
            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                @include('records.partials.margin-ranking', ['title' => 'Plus larges victoires', 'matches' => $biggestWins, 'tone' => 'gagne'])
                @include('records.partials.margin-ranking', ['title' => 'Plus lourdes défaites', 'matches' => $heaviestDefeats, 'tone' => 'perdu'])
            </div>
        </div>
    </section>

    {{-- ═══ Records de score ═══ --}}
    <section id="scores" class="scroll-mt-4 border-t border-filet py-12 lg:py-16" aria-labelledby="scores-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="scores-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Records de score</h2>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($scoreCards as $key => [$label, $value])
                    @continue(!$scoreRecords[$key])
                    @php $match = $scoreRecords[$key]; @endphp
                    <li>
                        <a href="{{ route('matches.show', $match) }}" class="group flex h-full items-start justify-between gap-4 rounded-2xl border border-filet bg-white p-5 hover:border-encre/30 {{ $focus }}">
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold uppercase tracking-[0.14em] text-texte-2">{{ $label }}</span>
                                <span class="mt-2 block font-display text-xl font-bold uppercase leading-tight text-encre group-hover:underline">
                                    <span aria-hidden="true">{{ $match->opponent->flag_emoji }}</span> France {{ $score($match) }} {{ $match->opponent->name }}
                                </span>
                                <span class="mt-1 block text-sm text-texte-2">{{ $value($match) }} · {{ $match->match_date->format('d/m/Y') }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ═══ Séries ═══ --}}
    <section id="series" class="scroll-mt-4 bg-encre py-12 text-white lg:py-16" aria-labelledby="series-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="series-titre" class="font-display text-3xl font-bold uppercase tracking-tight">Séries</h2>
            <ul class="mt-8 grid gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($streakCards as $key => [$label, $accent])
                    @php $streak = $streaks[$key]; @endphp
                    <li class="bg-encre p-6">
                        <span class="block h-1 w-10 rounded-full {{ $accent }}" aria-hidden="true"></span>
                        @if($streak)
                            <span class="mt-4 block font-display text-6xl font-bold leading-none tabular-nums">{{ $streak['length'] }}</span>
                            <span class="mt-2 block font-semibold">{{ $label }}</span>
                            <span class="mt-3 block text-sm text-blue-200">
                                <a href="{{ route('matches.show', $streak['from']) }}" class="underline decoration-white/30 underline-offset-2 hover:decoration-white {{ $focus }}">{{ $streak['from']->match_date->translatedFormat('M Y') }}</a>
                                →
                                @if($streak['ongoing'])
                                    <span class="rounded-full bg-white/15 px-2 py-0.5 text-xs font-semibold text-white">en cours</span>
                                @else
                                    <a href="{{ route('matches.show', $streak['to']) }}" class="underline decoration-white/30 underline-offset-2 hover:decoration-white {{ $focus }}">{{ $streak['to']->match_date->translatedFormat('M Y') }}</a>
                                @endif
                            </span>
                            <span class="mt-1 block text-xs text-blue-300">{{ $streak['from']->opponent->name }} → {{ $streak['to']->opponent->name }}</span>
                        @else
                            <span class="mt-4 block font-display text-6xl font-bold leading-none text-white/40">—</span>
                            <span class="mt-2 block font-semibold">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ═══ Face à chaque adversaire ═══ --}}
    <section id="adversaires" class="scroll-mt-4 py-12 lg:py-16" aria-labelledby="adversaires-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="adversaires-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Face à chaque adversaire</h2>
            <p class="mt-2 max-w-2xl font-serif text-texte-2">La première victoire, la plus large et la plus lourde défaite contre chacune des nations affrontées.</p>

            <div class="mt-8 overflow-x-auto rounded-2xl border border-filet bg-white">
                <table class="w-full min-w-[44rem] text-sm">
                    <thead class="border-b border-filet bg-papier text-left text-xs font-semibold uppercase tracking-[0.12em] text-texte-2">
                        <tr>
                            <th scope="col" class="px-5 py-3">Nation</th>
                            <th scope="col" class="px-3 py-3 text-right">Matches</th>
                            <th scope="col" class="px-5 py-3">Première victoire</th>
                            <th scope="col" class="px-5 py-3">Plus large victoire</th>
                            <th scope="col" class="px-5 py-3">Plus lourde défaite</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-filet">
                        @foreach($opponentRecords as $row)
                            <tr class="hover:bg-papier/60">
                                <th scope="row" class="px-5 py-3 text-left font-semibold">
                                    <a href="{{ route('opponents.show', $row->opponent) }}" class="text-encre hover:underline {{ $focus }}"><span aria-hidden="true">{{ $row->opponent->flag_emoji }}</span> {{ $row->opponent->name }}</a>
                                </th>
                                <td class="px-3 py-3 text-right font-display text-lg font-bold tabular-nums text-encre">{{ $row->total }}</td>
                                @foreach([[$row->firstWin, 'text-encre'], [$row->biggestWin, 'text-gagne'], [$row->heaviestDefeat, 'text-perdu']] as [$match, $tone])
                                    <td class="px-5 py-3 tabular-nums">
                                        @if($match)
                                            <a href="{{ route('matches.show', $match) }}" class="group {{ $focus }}">
                                                <b class="font-display text-lg {{ $tone }} group-hover:underline">{{ $score($match) }}</b>
                                                <span class="text-texte-2">{{ $match->match_date->year }}</span>
                                            </a>
                                        @else
                                            <span class="text-texte-2/60">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═══ Records individuels ═══ --}}
    <section id="joueurs" class="scroll-mt-4 border-t border-filet py-12 lg:py-16" aria-labelledby="joueurs-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="joueurs-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Records individuels</h2>
            <div class="mt-4 flex max-w-3xl gap-3 rounded-xl border border-or/40 bg-or/10 px-4 py-3 text-sm text-encre">
                <svg class="mt-0.5 size-4 shrink-0 text-or-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd"/></svg>
                <p>
                    <b>Site en construction, saisie des données en cours.</b>
                    Ces classements ne portent que sur les {{ $detailedMatchCount }} matches dont la composition et les marqueurs sont déjà saisis :
                    ils ne reflètent pas encore les records historiques et s'enrichiront au fil de la saisie.
                </p>
            </div>
            <div class="mt-8 grid gap-6 lg:grid-cols-3">
                @include('records.partials.player-ranking', ['title' => 'Essais', 'rows' => $topTryScorers, 'unit' => ['essai', 'essais']])
                @include('records.partials.player-ranking', ['title' => 'Feuilles de match', 'rows' => $mostAppearances, 'unit' => ['match', 'matches']])
                @include('records.partials.player-ranking', ['title' => 'Capitanats', 'rows' => $mostCaptaincies, 'unit' => ['match', 'matches']])
            </div>
        </div>
    </section>

    {{-- ═══ À travers les époques ═══ --}}
    <section id="epoques" class="scroll-mt-4 border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="epoques-titre">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="epoques-titre" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">À travers les époques</h2>
            <p class="mt-2 max-w-2xl font-serif text-texte-2">Le pourcentage de victoires du XV de France, décennie par décennie.</p>

            <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_16rem]">
                {{-- Histogramme : une barre par décennie, hauteur = % de victoires --}}
                <figure>
                    <div class="relative h-64 border-b border-filet">
                        <div class="pointer-events-none absolute inset-x-0 top-1/2 z-10 border-t border-dashed border-white/70 mix-blend-difference" aria-hidden="true">
                            <span class="absolute -top-5 left-0 text-xs text-texte-2 mix-blend-normal">50 %</span>
                        </div>
                        <ol class="relative flex h-full items-end gap-1 sm:gap-2" aria-label="Pourcentage de victoires par décennie">
                            @foreach($decades as $decade => $r)
                                <li class="group relative flex h-full flex-1 flex-col justify-end" tabindex="0"
                                    aria-label="Années {{ $decade }} : {{ $r->winPctLabel(0) }} de victoires ({{ $r->wins }} sur {{ $r->total }} matches)">
                                    <span class="mb-1 hidden text-center text-xs font-semibold tabular-nums text-encre sm:block">{{ round($r->winPct) }}</span>
                                    <span class="block rounded-t bg-encre group-hover:bg-bleu-france group-focus:bg-bleu-france" style="height: {{ max($r->winPct, 0.6) }}%"></span>
                                    <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-max -translate-x-1/2 rounded-lg bg-encre px-3 py-2 text-xs text-white shadow-lg group-hover:block group-focus:block">
                                        <b class="block font-display text-sm uppercase">Années {{ $decade }}</b>
                                        {{ $r->winPctLabel(0) }} · {{ $r->wins }} V · {{ $r->draws }} N · {{ $r->losses }} D
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="mt-2 flex gap-1 sm:gap-2" aria-hidden="true">
                        @foreach($decades->keys() as $decade)
                            <span class="flex-1 text-center text-[10px] tabular-nums text-texte-2 sm:text-xs"><span class="sm:hidden">{{ substr($decade, 2) }}</span><span class="hidden sm:inline">{{ $decade }}</span></span>
                        @endforeach
                    </div>
                    <figcaption class="mt-3 text-xs text-texte-2">Le faible nombre de matches des années 1930 s'explique par l'exclusion de la France du Tournoi de 1932 à 1939.</figcaption>

                    <details class="mt-6 rounded-xl border border-filet">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-encre">Voir le tableau par décennie</summary>
                        <table class="w-full text-sm tabular-nums">
                            <thead class="border-y border-filet bg-papier text-xs uppercase tracking-[0.12em] text-texte-2">
                                <tr>
                                    <th scope="col" class="px-4 py-2 text-left">Décennie</th>
                                    <th scope="col" class="px-3 py-2 text-right">M</th>
                                    <th scope="col" class="px-3 py-2 text-right">V</th>
                                    <th scope="col" class="px-3 py-2 text-right">N</th>
                                    <th scope="col" class="px-3 py-2 text-right">D</th>
                                    <th scope="col" class="px-4 py-2 text-right">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-filet">
                                @foreach($decades as $decade => $r)
                                    <tr>
                                        <th scope="row" class="px-4 py-2 text-left font-semibold text-encre">{{ $decade }}–{{ $decade + 9 }}</th>
                                        <td class="px-3 py-2 text-right">{{ $r->total }}</td>
                                        <td class="px-3 py-2 text-right text-gagne">{{ $r->wins }}</td>
                                        <td class="px-3 py-2 text-right text-egal">{{ $r->draws }}</td>
                                        <td class="px-3 py-2 text-right text-perdu">{{ $r->losses }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-encre">{{ $r->winPctLabel(0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                </figure>

                {{-- Domicile / extérieur --}}
                <div class="space-y-4">
                    @foreach($venueCards as $key => $label)
                        @php $r = $venueTypes[$key]; @endphp
                        @continue($r->total === 0)
                        <div class="rounded-2xl border border-filet bg-papier p-5">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $label }}</span>
                            <span class="mt-1 block font-display text-5xl font-bold tabular-nums text-encre">{{ $r->winPctLabel(0) }}</span>
                            <span class="text-sm text-texte-2">de victoires · {{ $r->total }} matches</span>
                            <span class="mt-1 block text-sm tabular-nums"><b class="text-gagne">{{ $r->wins }}</b> V · <b class="text-egal">{{ $r->draws }}</b> N · <b class="text-perdu">{{ $r->losses }}</b> D</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
