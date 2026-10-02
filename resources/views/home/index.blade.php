@extends('layouts.app')

@section('title', 'XV de France — L\'histoire complète depuis 1906')

@php
    use App\Support\FrenchDate;

    $fr = fn ($n, $d = 0) => number_format($n, $d, ',', "\u{202F}");
    $share = fn ($n) => $record->total > 0 ? number_format($n / $record->total * 100, 2, '.', '') : 0;
    $firstYear = $firstMatchDate ? \Illuminate\Support\Carbon::parse($firstMatchDate)->year : 1906;
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2';
    $explore = array_filter([
        ['matches.index', 'Matches', $counts['matches'], 'rencontres depuis ' . $firstYear, 'Chaque test, chaque Tournoi, chaque Coupe du monde.'],
        ['opponents.index', 'Adversaires', $counts['opponents'], 'nations affrontées', 'Le bilan face à chaque pays, des Fidji à la Nouvelle-Zélande.'],
        ['players.index', 'Joueurs', $counts['players'], 'Bleus recensés', 'Les Bleus et leurs adversaires, match après match.'],
        ['competitions.index', 'Compétitions', $counts['competitions'], 'compétitions', 'Le Tournoi, la Coupe du monde, les tournées.'],
        ['records.index', 'Records', null, null, 'Les plus larges victoires, les plus longues séries.'],
        ['venues.index', 'Stades', null, null, 'La carte de tous les terrains foulés par le XV.'],
    ]);
@endphp

@section('content')

    {{-- ═══ Bandeau ═══ --}}
    <section class="relative overflow-hidden bg-encre text-white">
        <span class="pointer-events-none absolute -bottom-24 -right-10 select-none font-display text-[22rem] font-bold leading-none text-white/[0.03] sm:text-[30rem]" aria-hidden="true">XV</span>
        <div class="relative mx-auto grid max-w-6xl gap-12 px-4 pb-14 pt-10 sm:px-6 lg:grid-cols-12 lg:items-center lg:px-8 lg:pb-20 lg:pt-14">
            <div class="lg:col-span-7">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">L'almanach du rugby français · depuis {{ $firstYear }}</p>
                <h1 class="mt-4 font-display font-bold uppercase leading-[0.88] tracking-tight">
                    <span class="block text-7xl sm:text-8xl lg:text-9xl">XV</span>
                    <span class="block font-serif text-4xl font-normal normal-case italic tracking-normal text-blue-100 sm:text-5xl">de France</span>
                </h1>
                <p class="mt-6 max-w-xl font-serif text-lg leading-relaxed text-blue-100">
                    Tous les matches de l'équipe de France de rugby depuis le {{ FrenchDate::long(\Illuminate\Support\Carbon::parse($firstMatchDate ?? '1906-01-01')) }} :
                    scores, compositions, marqueurs, adversaires et records.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('matches.index') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-full bg-white px-6 font-semibold text-encre motion-safe:transition-colors hover:bg-blue-50 {{ $focus }} focus-visible:ring-white focus-visible:ring-offset-encre">
                        Explorer les matches <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('players.index') }}" class="inline-flex min-h-[44px] items-center rounded-full border border-white/30 px-6 font-semibold text-white motion-safe:transition-colors hover:bg-white/10 {{ $focus }} focus-visible:ring-white focus-visible:ring-offset-encre">
                        Rechercher un joueur
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6 sm:p-8">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Bilan historique</h2>
                    <div class="mt-3 flex items-baseline gap-3">
                        <span class="font-display text-7xl font-bold leading-none tabular-nums">{{ $record->winPctLabel() }}</span>
                    </div>
                    <p class="mt-1 text-blue-200">de victoires en {{ $fr($record->total) }} matches</p>
                    <div class="mt-6 flex h-2.5 overflow-hidden rounded-full bg-white/10" role="img"
                         aria-label="{{ $record->wins }} victoires, {{ $record->draws }} nuls, {{ $record->losses }} défaites">
                        <span class="bg-emerald-400" style="width: {{ $share($record->wins) }}%"></span>
                        <span class="bg-amber-300" style="width: {{ $share($record->draws) }}%"></span>
                        <span class="bg-rouge-france" style="width: {{ $share($record->losses) }}%"></span>
                    </div>
                    <dl class="mt-6 grid grid-cols-3 gap-3 text-center">
                        @foreach([['Victoires', $record->wins, 'bg-emerald-400'], ['Nuls', $record->draws, 'bg-amber-300'], ['Défaites', $record->losses, 'bg-rouge-france']] as [$label, $value, $dot])
                            <div>
                                <dt class="flex items-center justify-center gap-1.5 text-xs uppercase tracking-wider text-blue-200"><span class="h-2 w-2 rounded-full {{ $dot }}" aria-hidden="true"></span>{{ $label }}</dt>
                                <dd class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $fr($value) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ Dernier match ═══ --}}
    @if($latestMatch)
        @php
            $lm = $latestMatch;
            $tone = match ($lm->result) { 'Victoire' => 'text-gagne bg-gagne/10 ring-gagne/25', 'Défaite' => 'text-perdu bg-perdu/10 ring-perdu/25', default => 'text-egal bg-egal/10 ring-egal/25' };
        @endphp
        <section class="py-12 lg:py-16" aria-labelledby="dernier-match">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 id="dernier-match" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Dernier match</h2>
                <a href="{{ route('matches.show', $lm) }}" class="group mt-6 block overflow-hidden rounded-2xl border border-filet bg-white shadow-xs motion-safe:transition-shadow hover:shadow-lg {{ $focus }} focus-visible:ring-bleu-france">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-filet px-6 py-3 text-sm text-texte-2 sm:px-8">
                        <span>
                            {{ ucfirst(FrenchDate::long($lm->match_date, weekday: true)) }}
                            @if($lm->edition?->competition)<span class="mx-1.5 text-filet" aria-hidden="true">·</span>{{ $lm->edition->competition->name }} {{ $lm->edition->year }}@endif
                        </span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $tone }}">{{ $lm->result === 'Nul' ? 'Match nul' : $lm->result . ' de la France' }}</span>
                    </div>
                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 px-6 py-8 sm:gap-10 sm:px-8">
                        <div class="text-right">
                            <span class="block text-3xl sm:text-5xl" aria-hidden="true">{{ $lm->home_team_flag }}</span>
                            <span class="mt-1 block truncate font-display text-xl font-bold uppercase text-encre sm:text-4xl">{{ $lm->home_team_name }}</span>
                        </div>
                        <div class="whitespace-nowrap font-display text-5xl font-bold tabular-nums text-encre sm:text-8xl">
                            {{ $lm->home_score }}<span class="mx-1 text-filet sm:mx-3">–</span>{{ $lm->away_score }}
                        </div>
                        <div>
                            <span class="block text-3xl sm:text-5xl" aria-hidden="true">{{ $lm->away_team_flag }}</span>
                            <span class="mt-1 block truncate font-display text-xl font-bold uppercase text-encre sm:text-4xl">{{ $lm->away_team_name }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-papier px-6 py-3 text-sm sm:px-8">
                        <span class="text-texte-2">@if($lm->venue){{ $lm->venue->name }}, {{ $lm->venue->city }}@endif</span>
                        <span class="font-semibold text-bleu-france group-hover:underline">Voir la feuille de match →</span>
                    </div>
                </a>
            </div>
        </section>
    @endif

    {{-- ═══ Derniers résultats + Ce jour-là ═══ --}}
    <section class="border-t border-filet py-12 lg:py-16">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:px-8">
            @if($recentMatches->isNotEmpty())
                <div class="lg:col-span-7" aria-labelledby="resultats">
                    <div class="flex items-end justify-between gap-4">
                        <h2 id="resultats" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Résultats précédents</h2>
                        <a href="{{ route('matches.index') }}" class="whitespace-nowrap text-sm font-semibold text-bleu-france hover:underline">Tous les matches →</a>
                    </div>
                    <ol class="mt-6 divide-y divide-filet overflow-hidden rounded-2xl border border-filet bg-white">
                        @foreach($recentMatches as $match)
                            <li><x-match.row :match="$match" /></li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <aside class="lg:col-span-5" aria-labelledby="ce-jour-la">
                <h2 id="ce-jour-la" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Ce jour-là</h2>
                <p class="mt-1 font-serif text-texte-2">
                    {{ $onThisDay['exact'] ? 'Le ' . FrenchDate::long(now(), year: false) . ' dans l\'histoire du XV' : 'Autour du ' . FrenchDate::long(now(), year: false) . ' dans l\'histoire du XV' }}
                </p>
                @if($onThisDay['matches']->isEmpty())
                    <p class="mt-6 rounded-2xl border border-dashed border-filet p-6 font-serif text-texte-2">Le XV de France n'a jamais joué à cette période de l'année.</p>
                @else
                    <ol class="mt-6 space-y-3">
                        @foreach($onThisDay['matches'] as $m)
                            @php $years = now()->year - $m->match_date->year; @endphp
                            <li>
                                <a href="{{ route('matches.show', $m) }}" class="group flex items-center gap-4 rounded-2xl border border-filet bg-white p-4 hover:border-encre/30 {{ $focus }} focus-visible:ring-bleu-france">
                                    <span class="w-16 shrink-0 text-center">
                                        <span class="block font-display text-3xl font-bold leading-none tabular-nums text-encre">{{ $m->match_date->year }}</span>
                                        <span class="mt-1 block text-[11px] text-texte-2">il y a {{ $years }} an{{ $years > 1 ? 's' : '' }}</span>
                                    </span>
                                    <span class="min-w-0 border-l border-filet pl-4">
                                        <span class="block truncate font-semibold text-encre group-hover:underline">
                                            {{ $m->home_team_name }} <span class="font-display tabular-nums">{{ $m->home_score }}–{{ $m->away_score }}</span> {{ $m->away_team_name }}
                                        </span>
                                        <span class="block truncate text-xs text-texte-2">
                                            {{ FrenchDate::long($m->match_date) }}@if($m->edition?->competition) · {{ $m->edition->competition->short_name }}@endif
                                        </span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif

                {{-- Repères --}}
                <dl class="mt-8 grid grid-cols-2 gap-3">
                    @if($biggestWin)
                        <div class="rounded-2xl border border-filet bg-white p-4">
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-texte-2">Plus large victoire</dt>
                            <dd class="mt-1">
                                <a href="{{ route('matches.show', $biggestWin) }}" class="font-display text-3xl font-bold tabular-nums text-encre hover:underline">{{ $biggestWin->france_score }}–{{ $biggestWin->opponent_score }}</a>
                                <span class="block text-xs text-texte-2">face {{ $biggestWin->opponent->withArticle('à') }}, {{ $biggestWin->match_date->year }}</span>
                            </dd>
                        </div>
                    @endif
                    @if($mostFaced)
                        <div class="rounded-2xl border border-filet bg-white p-4">
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-texte-2">Adversaire n° 1</dt>
                            <dd class="mt-1">
                                <a href="{{ route('opponents.show', $mostFaced) }}" class="font-display text-3xl font-bold text-encre hover:underline">{{ $mostFaced->name }}</a>
                                <span class="block text-xs text-texte-2">{{ $fr($mostFaced->matches_as_opponent_count) }} confrontations</span>
                            </dd>
                        </div>
                    @endif
                </dl>
            </aside>
        </div>
    </section>

    {{-- ═══ Explorer ═══ --}}
    <section class="border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="explorer">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="explorer" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Explorer l'almanach</h2>
            <ul class="mt-8 grid gap-px overflow-hidden rounded-2xl border border-filet bg-filet sm:grid-cols-2 lg:grid-cols-3">
                @foreach($explore as [$route, $label, $count, $unit, $text])
                    <li class="bg-white">
                        <a href="{{ route($route) }}" class="group flex h-full flex-col p-6 hover:bg-papier {{ $focus }} focus-visible:ring-inset focus-visible:ring-bleu-france">
                            <span class="flex items-baseline justify-between gap-4">
                                <span class="font-display text-2xl font-bold uppercase text-encre group-hover:text-bleu-france">{{ $label }}</span>
                                <span class="text-bleu-france opacity-0 motion-safe:transition-opacity group-hover:opacity-100" aria-hidden="true">→</span>
                            </span>
                            @if($count)
                                <span class="mt-3 font-display text-5xl font-bold leading-none tabular-nums text-encre">{{ $fr($count) }}</span>
                                <span class="mt-1 text-sm text-texte-2">{{ $unit }}</span>
                            @endif
                            <span class="mt-3 font-serif text-texte-2">{{ $text }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endsection
