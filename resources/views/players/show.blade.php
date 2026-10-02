@extends('layouts.app')

@php
    $p = $player;
    $isFrench = $p->country?->code === 'FRA';
    $totals = $profile->totals();
    $record = $profile->record();
    $first = $profile->firstAppearance();
    $last = $profile->lastAppearance();
    $teamLabel = $isFrench ? 'le XV de France' : ($p->country ? $p->country->withArticle() : 'son équipe');
    $fr = fn ($n) => number_format($n, 0, ',', "\u{202F}");
    $bio = array_filter([
        'Naissance' => $p->birth_date
            ? ucfirst($p->birth_date->translatedFormat('j F Y')) . ($p->birth_city ? ' à ' . $p->birth_city : '') . ($p->birthCountry && $p->birthCountry->code !== $p->country?->code ? ' (' . $p->birthCountry->name . ')' : '')
            : ($p->birth_city ? $p->birth_city : null),
        'Décès' => $p->death_date ? ucfirst($p->death_date->translatedFormat('j F Y')) . ($p->birth_date ? ' (' . $p->birth_date->diffInYears($p->death_date) . ' ans)' : '') : null,
        'Âge' => $p->birth_date && !$p->death_date ? (int) $p->birth_date->diffInYears(now()) . ' ans' : null,
        'Taille' => $p->height_cm ? number_format($p->height_cm / 100, 2, ',', '') . ' m' : null,
        'Poids' => $p->weight_kg ? $p->weight_kg . ' kg' : null,
    ]);
    $resultTone = fn ($r) => match ($r) { 'Victoire' => 'bg-gagne', 'Défaite' => 'bg-perdu', default => 'bg-egal' };
    $resultLetter = fn ($r) => match ($r) { 'Victoire' => 'V', 'Défaite' => 'D', default => 'N' };
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2';
@endphp

@section('title', $p->fullName() . ' — ' . ($isFrench ? 'XV de France' : ($p->country?->name ?? 'Joueur')) . ' : matches, essais, points')

@section('meta_description', $p->fullName() . ($p->primary_position ? ', ' . mb_strtolower($p->primary_position->label()) : '') . ($p->country ? ' (' . $p->country->name . ')' : '') . ' : ' . $totals['matches'] . ' matches recensés' . ($totals['tries'] ? ', ' . $totals['tries'] . ' essais' : '') . ($totals['points'] ? ', ' . $totals['points'] . ' points' : '') . '.')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <a href="{{ route('players.index') }}" class="hover:text-bleu-france">Joueurs</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700">{{ $p->fullName() }}</span>
@endsection

@section('content')
<div class="bg-papier text-texte">

    {{-- ═══ Bandeau ═══ --}}
    <header class="relative overflow-hidden bg-encre text-white">
        <div class="pointer-events-none absolute inset-x-0 top-0 flex h-1" aria-hidden="true">
            <span class="flex-1 bg-bleu-france"></span><span class="flex-1 bg-white"></span><span class="flex-1 bg-rouge-france"></span>
        </div>
        {{-- Numéro géant en filigrane --}}
        @if($profile->favouriteJersey())
            <span class="pointer-events-none absolute -right-6 -top-10 select-none font-display text-[18rem] font-bold leading-none text-white/[0.04] sm:text-[24rem]" aria-hidden="true">{{ $profile->favouriteJersey() }}</span>
        @endif

        <div class="relative mx-auto flex max-w-6xl flex-col gap-8 px-4 py-10 sm:flex-row sm:items-center sm:px-6 lg:px-8 lg:py-14">
            <div class="shrink-0">
                @if($p->photo_path)
                    <img src="{{ $p->photo_path }}" alt="Portrait de {{ $p->fullName() }}" class="h-40 w-40 rounded-2xl object-cover ring-1 ring-white/20">
                @else
                    <x-player.shirt :number="$profile->favouriteJersey()" :france="$isFrench" class="h-32 w-32 drop-shadow-xl sm:h-40 sm:w-40" />
                @endif
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-blue-200">
                    @if($p->country)
                        <span><span aria-hidden="true">{{ $p->country->flag_emoji }}</span> {{ $isFrench ? 'XV de France' : $p->country->name }}</span>
                    @endif
                    @if($p->primary_position)
                        <span aria-hidden="true">·</span><span>{{ $p->primary_position->label() }}</span>
                    @endif
                    @if($p->cap_number)
                        <span aria-hidden="true">·</span><span>Sélectionné n° {{ $fr($p->cap_number) }}</span>
                    @endif
                </div>
                <h1 class="mt-2 leading-none">
                    <span class="block font-serif text-2xl font-normal normal-case italic tracking-normal text-blue-100 sm:text-3xl">{{ $p->first_name }}</span>
                    <span class="mt-1 block break-words font-display text-5xl font-bold uppercase tracking-tight sm:text-7xl">{{ $p->last_name }}</span>
                </h1>
                @if($p->nickname)
                    <p class="mt-2 font-serif text-lg italic text-blue-200">« {{ $p->nickname }} »</p>
                @endif
                @if($p->isDeceased())
                    <p class="mt-2 text-sm text-blue-200">† {{ $p->death_date->translatedFormat('j F Y') }}</p>
                @endif

                @if($first)
                    <p class="mt-4 max-w-2xl text-blue-100">
                        @if($first === $last)
                            Un match recensé avec {{ $teamLabel }},
                            le {{ $first['match']->match_date->translatedFormat('j F Y') }}.
                        @else
                            Recensé avec {{ $teamLabel }} du
                            {{ $first['match']->match_date->translatedFormat('j F Y') }} au {{ $last['match']->match_date->translatedFormat('j F Y') }}.
                        @endif
                    </p>
                @endif
            </div>
        </div>
    </header>

    {{-- ═══ Chiffres clés ═══ --}}
    @if($profile->hasAppearances())
        @php
            $stats = array_filter([
                ['value' => $totals['matches'], 'label' => 'Matches', 'sub' => $totals['starts'] . ' titulaire' . ($totals['starts'] > 1 ? 's' : '')],
                ['value' => $totals['tries'], 'label' => 'Essais', 'sub' => null],
                ['value' => $totals['points'], 'label' => 'Points', 'sub' => null],
                $totals['captaincies'] ? ['value' => $totals['captaincies'], 'label' => 'Capitanat' . ($totals['captaincies'] > 1 ? 's' : ''), 'sub' => null, 'gold' => true] : null,
                $totals['minutes'] !== null && $totals['minutesKnown'] * 2 >= $totals['matches'] ? ['value' => $fr($totals['minutes']), 'label' => 'Minutes', 'sub' => $totals['minutesKnown'] < $totals['matches'] ? 'sur ' . $totals['minutesKnown'] . ' match' . ($totals['minutesKnown'] > 1 ? 's' : '') : null] : null,
                ($totals['yellow'] || $totals['red']) ? ['value' => $totals['yellow'] + $totals['red'], 'label' => 'Cartons', 'sub' => trim(($totals['yellow'] ? $totals['yellow'] . ' jaune' . ($totals['yellow'] > 1 ? 's' : '') : '') . ' ' . ($totals['red'] ? $totals['red'] . ' rouge' . ($totals['red'] > 1 ? 's' : '') : ''))] : null,
            ]);
            $share = fn ($n) => $record->total ? round($n / $record->total * 100, 2) : 0;
        @endphp
        <section class="border-b border-filet bg-white">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-6 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach($stats as $stat)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $stat['label'] }}</dt>
                            <dd class="mt-1 font-display text-4xl font-bold tabular-nums {{ !empty($stat['gold']) ? 'text-or-2' : 'text-encre' }}">{{ $stat['value'] }}</dd>
                            @if($stat['sub'])<dd class="text-xs text-texte-2">{{ $stat['sub'] }}</dd>@endif
                        </div>
                    @endforeach
                    <div class="col-span-2 sm:col-span-3 lg:col-span-6">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Bilan avec {{ $teamLabel }}</dt>
                        <dd class="mt-2 flex flex-wrap items-center gap-x-6 gap-y-2">
                            <span class="font-display text-2xl font-bold tabular-nums text-encre">
                                <span class="text-gagne">{{ $record->wins }} V</span> · <span class="text-egal">{{ $record->draws }} N</span> · <span class="text-perdu">{{ $record->losses }} D</span>
                            </span>
                            <span class="flex h-2 min-w-40 flex-1 overflow-hidden rounded-full bg-papier-2" role="img" aria-label="{{ $record->wins }} victoires, {{ $record->draws }} nuls, {{ $record->losses }} défaites">
                                <span class="bg-gagne" style="width: {{ $share($record->wins) }}%"></span>
                                <span class="bg-egal" style="width: {{ $share($record->draws) }}%"></span>
                                <span class="bg-perdu" style="width: {{ $share($record->losses) }}%"></span>
                            </span>
                            <span class="text-sm text-texte-2">{{ $record->winPctLabel(0) }} de victoires</span>
                        </dd>
                    </div>
                </dl>
                <p class="mt-6 text-xs text-texte-2">
                    Statistiques établies sur les feuilles de match saisies dans nos archives. Elles ne couvrent pas encore toute la carrière du joueur.
                </p>
            </div>
        </section>
    @endif

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,8fr)_minmax(0,4fr)] lg:px-8 lg:py-16">

        {{-- ═══ Parcours ═══ --}}
        <section aria-labelledby="parcours">
            <h2 id="parcours" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Parcours</h2>

            @if(!$profile->hasAppearances())
                <p class="mt-4 rounded-2xl border border-dashed border-filet bg-white/60 p-6 font-serif text-texte-2">
                    Aucune feuille de match saisie ne mentionne encore {{ $p->fullName() }}.
                </p>
            @else
                <div class="mt-6 space-y-8">
                    @foreach($profile->byYear() as $year => $appearances)
                        <div>
                            <h3 class="flex items-baseline gap-3 border-b border-filet pb-2">
                                <span class="font-display text-2xl font-bold tabular-nums text-encre">{{ $year }}</span>
                                <span class="text-sm text-texte-2">{{ $appearances->count() }} match{{ $appearances->count() > 1 ? 'es' : '' }}</span>
                            </h3>
                            <ol class="mt-2 divide-y divide-filet">
                                @foreach($appearances as $a)
                                    @php $m = $a['match']; $row = $a['row']; @endphp
                                    <li>
                                        <a href="{{ route('matches.show', $m) }}" class="grid grid-cols-[2.25rem_minmax(0,1fr)_auto] items-center gap-3 rounded-lg px-2 py-3 hover:bg-white {{ $focus }} focus-visible:ring-bleu-france">
                                            <span class="relative">
                                                <x-match.jersey :number="$row['jersey']" :france="$a['forFrance']" size="md" class="{{ $a['forFrance'] ? '' : 'ring-encre/25' }}" />
                                                @if($row['captain'])<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-or font-display text-[10px] font-bold text-encre" title="Capitaine">C</span>@endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="flex items-center gap-2">
                                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-[10px] font-bold text-white {{ $resultTone($a['result']) }}" title="{{ $a['result'] }}">{{ $resultLetter($a['result']) }}</span>
                                                    <span class="truncate font-semibold text-encre">
                                                        {{ $m->home_team_name }} <span class="font-display tabular-nums">{{ $m->home_score }}–{{ $m->away_score }}</span> {{ $m->away_team_name }}
                                                    </span>
                                                </span>
                                                <span class="mt-0.5 block truncate text-xs text-texte-2">
                                                    {{ $m->match_date->translatedFormat('j F') }}
                                                    @if($m->edition?->competition) · {{ $m->edition->competition->short_name }}@endif
                                                    · {{ $row['starter'] ? 'Titulaire' : 'Remplaçant' }}{{ $row['position'] ? ', ' . mb_strtolower($row['position']->label()) : '' }}
                                                </span>
                                            </span>
                                            <span class="flex items-center gap-3 text-sm tabular-nums text-texte-2">
                                                @if($row['minutes'] !== null)<span class="hidden sm:inline">{{ $row['minutes'] }}'</span>@endif
                                                @if($row['tries'])<span class="flex items-center gap-0.5 font-semibold text-encre"><x-match.event-icon type="essai" class="h-4 w-4" />{{ $row['tries'] }}</span>@endif
                                                @if($row['points'])<span class="font-display text-base font-bold text-encre">{{ $row['points'] }}<span class="ml-0.5 text-xs font-normal text-texte-2">pts</span></span>@endif
                                                @foreach($row['cards'] as $card)<x-match.event-icon :type="$card['type']->value" class="h-4 w-4" />@endforeach
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ═══ Colonne latérale ═══ --}}
        <aside class="space-y-8">
            @if($bio)
                <div class="rounded-2xl border border-filet bg-white p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Biographie</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        @foreach($bio as $label => $value)
                            <div class="flex justify-between gap-4"><dt class="text-texte-2">{{ $label }}</dt><dd class="text-right font-medium text-encre">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if($profile->positions()->count() > 0)
                @php $maxPos = $profile->positions()->max('count'); @endphp
                <div class="rounded-2xl border border-filet bg-white p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Postes occupés</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach($profile->positions() as $pos)
                            <li>
                                <div class="flex justify-between text-sm"><span class="font-medium text-encre">{{ $pos['label'] }}</span><span class="tabular-nums text-texte-2">{{ $pos['count'] }}</span></div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-papier-2"><span class="block h-full rounded-full bg-bleu-france" style="width: {{ round($pos['count'] / $maxPos * 100) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($profile->opponents()->count() > 0)
                <div class="rounded-2xl border border-filet bg-white p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $isFrench ? 'Adversaires' : 'Face à' }}</h2>
                    <ul class="mt-3 divide-y divide-filet text-sm">
                        @foreach($profile->opponents() as $opp)
                            <li class="flex items-center justify-between gap-3 py-2">
                                @if($opp['code'])
                                    <a href="{{ route('opponents.show', $opp['code']) }}" class="truncate font-medium text-encre hover:underline"><span aria-hidden="true">{{ $opp['flag'] }}</span> {{ $opp['name'] }}</a>
                                @else
                                    <span class="truncate font-medium text-encre"><span aria-hidden="true">{{ $opp['flag'] }}</span> {{ $opp['name'] }}</span>
                                @endif
                                <span class="shrink-0 tabular-nums text-texte-2">{{ $opp['count'] }} match{{ $opp['count'] > 1 ? 'es' : '' }} ·@if($opp['record']->wins) <span class="text-gagne">{{ $opp['record']->wins }} V</span>@endif @if($opp['record']->draws) <span class="text-egal">{{ $opp['record']->draws }} N</span>@endif @if($opp['record']->losses) <span class="text-perdu">{{ $opp['record']->losses }} D</span>@endif</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($profile->teammates()->count() > 0)
                <div class="rounded-2xl border border-filet bg-white p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Coéquipiers les plus fréquents</h2>
                    <ul class="mt-3 divide-y divide-filet text-sm">
                        @foreach($profile->teammates() as $mate)
                            <li>
                                <a href="{{ route('players.show', $mate['player']) }}" class="flex items-center justify-between gap-3 py-2 hover:underline">
                                    <span class="truncate font-medium text-encre">{{ $mate['player']->fullName() }}</span>
                                    <span class="shrink-0 tabular-nums text-texte-2">{{ $mate['count'] }} match{{ $mate['count'] > 1 ? 'es' : '' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
