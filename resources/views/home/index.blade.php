@extends('layouts.app')

@section('title', 'XV de France — L\'histoire complète depuis 1906')

@php
    $fr = fn ($n, $d = 0) => number_format($n, $d, ',', "\u{202F}");
    $share = fn ($n) => $record->total > 0 ? number_format($n / $record->total * 100, 2, '.', '') : 0;
    $firstYear = $firstMatchDate ? \Illuminate\Support\Carbon::parse($firstMatchDate)->year : 1906;
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2';
@endphp

@section('content')
<div>

    {{-- Hero : identité + bilan historique --}}
    <section class="bg-bleu-france text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20 grid gap-10 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-7">
                <div class="flex items-center gap-3 text-sm font-medium uppercase tracking-[0.2em] text-blue-200">
                    <span class="flex h-1 w-10 overflow-hidden rounded-full" aria-hidden="true">
                        <span class="flex-1 bg-blue-400"></span>
                        <span class="flex-1 bg-white"></span>
                        <span class="flex-1 bg-rouge-france"></span>
                    </span>
                    Depuis {{ $firstYear }}
                </div>
                <h1 class="mt-4 font-display font-bold uppercase leading-[0.9] tracking-tight text-6xl sm:text-7xl lg:text-8xl">
                    XV de France
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-blue-100">
                    Tous les matches de l'équipe de France de rugby, les compositions complètes,
                    les marqueurs et les sélectionneurs.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('matches.index') }}"
                       class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-white px-5 font-semibold text-bleu-france motion-safe:transition-colors hover:bg-blue-50 {{ $focus }} focus-visible:ring-white focus-visible:ring-offset-bleu-france">
                        Explorer les matches
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="{{ route('players.index') }}"
                       class="inline-flex min-h-[44px] items-center rounded-md border border-white/40 px-5 font-semibold text-white motion-safe:transition-colors hover:bg-white/10 {{ $focus }} focus-visible:ring-white focus-visible:ring-offset-bleu-france">
                        Rechercher un joueur
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-xl border border-white/15 bg-white/5 p-6 sm:p-8">
                    <h2 class="font-sans text-sm font-medium uppercase tracking-[0.2em] text-blue-200">Bilan historique</h2>
                    <div class="mt-3 flex items-baseline gap-3">
                        <span class="font-display text-6xl font-bold tabular-nums">{{ $fr($record->winPct, 1) }}&nbsp;%</span>
                        <span class="text-blue-200">de victoires</span>
                    </div>

                    <div class="mt-6 flex h-3 overflow-hidden rounded-full bg-white/10" role="img"
                         aria-label="{{ $record->wins }} victoires, {{ $record->draws }} nuls, {{ $record->losses }} défaites sur {{ $record->total }} matches">
                        <span class="bg-emerald-400" style="width: {{ $share($record->wins) }}%"></span>
                        <span class="bg-amber-300" style="width: {{ $share($record->draws) }}%"></span>
                        <span class="bg-rouge-france" style="width: {{ $share($record->losses) }}%"></span>
                    </div>

                    <dl class="mt-6 grid grid-cols-4 gap-2 sm:gap-4 text-center">
                        <div>
                            <dt class="text-xs uppercase sm:tracking-wider text-blue-200">Matches</dt>
                            <dd class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $fr($record->total) }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center justify-center gap-1 text-xs uppercase sm:tracking-wider text-blue-200">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-400" aria-hidden="true"></span>Victoires
                            </dt>
                            <dd class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $fr($record->wins) }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center justify-center gap-1 text-xs uppercase sm:tracking-wider text-blue-200">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-amber-300" aria-hidden="true"></span>Nuls
                            </dt>
                            <dd class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $fr($record->draws) }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center justify-center gap-1 text-xs uppercase sm:tracking-wider text-blue-200">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-rouge-france" aria-hidden="true"></span>Défaites
                            </dt>
                            <dd class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $fr($record->losses) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- Dernier match --}}
    @if($latestMatch)
        @php
            $resultStyle = match ($latestMatch->result) {
                'Victoire' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                'Défaite' => 'bg-red-50 text-red-800 ring-red-200',
                default => 'bg-amber-50 text-amber-800 ring-amber-200',
            };
        @endphp
        <section class="bg-slate-50 py-12 lg:py-16" aria-labelledby="dernier-match">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 id="dernier-match" class="font-display text-2xl sm:text-3xl font-bold uppercase tracking-tight text-slate-900">Dernier match</h2>

                <a href="{{ route('matches.show', $latestMatch) }}"
                   class="group mt-6 block rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs motion-safe:transition-shadow hover:shadow-lg {{ $focus }} focus-visible:ring-bleu-france">
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                        <span>
                            {{ ucfirst($latestMatch->match_date->locale('fr')->translatedFormat('l j F Y')) }}
                            @if($latestMatch->edition?->competition)
                                <span class="mx-1.5 text-slate-300" aria-hidden="true">·</span>{{ $latestMatch->edition->competition->short_name }} {{ $latestMatch->edition->year }}
                            @endif
                        </span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $resultStyle }}">
                            {{ $latestMatch->result }}
                        </span>
                    </div>

                    <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-4 sm:gap-8">
                        <div class="text-right">
                            <span class="block text-3xl sm:text-4xl" aria-hidden="true">🇫🇷</span>
                            <span class="mt-1 block font-display text-xl sm:text-3xl font-semibold uppercase text-slate-900">France</span>
                        </div>
                        <div class="whitespace-nowrap font-display text-4xl sm:text-7xl font-bold tabular-nums text-slate-900">
                            {{ $latestMatch->france_score }}<span class="mx-1 sm:mx-2 text-slate-300">–</span>{{ $latestMatch->opponent_score }}
                        </div>
                        <div>
                            <span class="block text-3xl sm:text-4xl" aria-hidden="true">{{ $latestMatch->opponent->flag_emoji }}</span>
                            <span class="mt-1 block font-display text-xl sm:text-3xl font-semibold uppercase text-slate-900">{{ $latestMatch->opponent->name }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
                        <span class="text-slate-600">
                            @if($latestMatch->venue){{ $latestMatch->venue->name }}, {{ $latestMatch->venue->city }} · @endif
                            {{ $latestMatch->is_neutral ? 'Terrain neutre' : ($latestMatch->is_home ? 'Domicile' : 'Extérieur') }}
                        </span>
                        <span class="font-semibold text-bleu-france group-hover:underline">Voir la feuille de match →</span>
                    </div>
                </a>
            </div>
        </section>
    @endif

    {{-- Résultats précédents + repères --}}
    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-10 lg:grid-cols-3">

            @if($recentMatches->isNotEmpty())
            <div class="lg:col-span-2" aria-labelledby="resultats">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="resultats" class="font-display text-2xl sm:text-3xl font-bold uppercase tracking-tight text-slate-900">Résultats précédents</h2>
                    <a href="{{ route('matches.index') }}" class="rounded whitespace-nowrap text-sm font-semibold text-bleu-france hover:underline {{ $focus }} focus-visible:ring-bleu-france">
                        Tous les matches →
                    </a>
                </div>

                <ul class="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                    @foreach($recentMatches as $match)
                        @php
                            [$letter, $style] = match ($match->result) {
                                'Victoire' => ['V', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
                                'Défaite' => ['D', 'bg-red-50 text-red-800 ring-red-200'],
                                default => ['N', 'bg-amber-50 text-amber-800 ring-amber-200'],
                            };
                        @endphp
                        <li>
                            <a href="{{ route('matches.show', $match) }}"
                               class="grid grid-cols-[auto_1fr_auto] items-center gap-4 px-4 py-3 sm:px-5 motion-safe:transition-colors hover:bg-slate-50 {{ $focus }} focus-visible:ring-inset focus-visible:ring-bleu-france">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm font-bold ring-1 ring-inset {{ $style }}"
                                      title="{{ $match->result }}">
                                    {{ $letter }}<span class="sr-only"> — {{ $match->result }}</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 font-semibold text-slate-900">
                                        <span aria-hidden="true">{{ $match->opponent->flag_emoji }}</span>
                                        <span class="truncate">{{ $match->opponent->name }}</span>
                                    </span>
                                    <span class="block truncate text-sm text-slate-500">
                                        {{ $match->match_date->format('d/m/Y') }}
                                        · {{ $match->is_neutral ? 'Neutre' : ($match->is_home ? 'Domicile' : 'Extérieur') }}
                                        @if($match->edition?->competition)<span class="hidden sm:inline"> · {{ $match->edition->competition->short_name }}</span>@endif
                                    </span>
                                </span>
                                <span class="font-display text-2xl font-bold tabular-nums text-slate-900">
                                    {{ $match->france_score }}<span class="mx-1 text-slate-300">–</span>{{ $match->opponent_score }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <aside aria-labelledby="reperes">
                <h2 id="reperes" class="font-display text-2xl sm:text-3xl font-bold uppercase tracking-tight text-slate-900">Repères</h2>
                <div class="mt-6 space-y-4">
                    @if($biggestWin)
                        <a href="{{ route('matches.show', $biggestWin) }}"
                           class="block rounded-xl border border-slate-200 bg-white p-5 motion-safe:transition-shadow hover:shadow-md {{ $focus }} focus-visible:ring-bleu-france">
                            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Plus large victoire</div>
                            <div class="mt-1 font-display text-4xl font-bold tabular-nums text-slate-900">
                                {{ $biggestWin->france_score }}<span class="mx-1 text-slate-300">–</span>{{ $biggestWin->opponent_score }}
                            </div>
                            <div class="mt-1 text-sm text-slate-600">
                                <span aria-hidden="true">{{ $biggestWin->opponent->flag_emoji }}</span>
                                contre {{ $biggestWin->opponent->name }}, {{ $biggestWin->match_date->format('Y') }}
                            </div>
                        </a>
                    @endif
                    @if($mostFaced)
                        <a href="{{ route('opponents.show', $mostFaced) }}"
                           class="block rounded-xl border border-slate-200 bg-white p-5 motion-safe:transition-shadow hover:shadow-md {{ $focus }} focus-visible:ring-bleu-france">
                            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Adversaire le plus affronté</div>
                            <div class="mt-1 flex items-center gap-2 font-display text-4xl font-bold text-slate-900">
                                <span class="text-3xl" aria-hidden="true">{{ $mostFaced->flag_emoji }}</span>{{ $mostFaced->name }}
                            </div>
                            <div class="mt-1 text-sm text-slate-600">{{ $fr($mostFaced->matches_as_opponent_count) }} confrontations</div>
                        </a>
                    @endif
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Premier match référencé</div>
                        <div class="mt-1 font-display text-4xl font-bold tabular-nums text-slate-900">{{ $firstYear }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $fr($counts['opponents']) }} nations affrontées depuis</div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    {{-- Explorer --}}
    @php
        $sections = [
            ['route' => 'matches.index', 'label' => 'Matches', 'count' => $counts['matches'], 'unit' => 'matches',
             'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
            ['route' => 'players.index', 'label' => 'Joueurs', 'count' => $counts['players'], 'unit' => 'internationaux',
             'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['route' => 'opponents.index', 'label' => 'Adversaires', 'count' => $counts['opponents'], 'unit' => 'nations',
             'icon' => 'M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5'],
            ['route' => 'competitions.index', 'label' => 'Compétitions', 'count' => $counts['competitions'], 'unit' => 'compétitions',
             'icon' => 'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0'],
            ['route' => 'coaches.index', 'label' => 'Sélectionneurs', 'count' => $counts['coaches'], 'unit' => 'sélectionneurs',
             'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'],
        ];
    @endphp
    <section class="border-t border-slate-200 bg-slate-50 py-12 lg:py-16" aria-labelledby="explorer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="explorer" class="font-display text-2xl sm:text-3xl font-bold uppercase tracking-tight text-slate-900">Explorer</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
                @foreach($sections as $section)
                    <a href="{{ route($section['route']) }}"
                       class="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 motion-safe:transition hover:border-bleu-france hover:shadow-md {{ $focus }} focus-visible:ring-bleu-france">
                        <svg class="h-7 w-7 text-bleu-france" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $section['icon'] }}"/>
                        </svg>
                        <span class="mt-4 font-display text-2xl font-semibold uppercase text-slate-900 group-hover:text-bleu-france">{{ $section['label'] }}</span>
                        @if($section['count'] > 0)
                            <span class="mt-1 text-sm text-slate-500"><span class="tabular-nums">{{ $fr($section['count']) }}</span> {{ $section['unit'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>

</div>
@endsection
