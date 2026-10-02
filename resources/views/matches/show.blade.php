@extends('layouts.app')

@php
    use App\Enums\TeamSide;

    $m = $rugbyMatch;
    $teams = $sheet->teams();
    [$home, $away] = $teams;
    $resultLabel = match ($m->result) {
        'Victoire' => 'Victoire de la France',
        'Défaite' => 'Défaite de la France',
        default => 'Match nul',
    };
    $resultTone = match ($m->result) {
        'Victoire' => 'bg-gagne/15 text-emerald-200 ring-emerald-300/30',
        'Défaite' => 'bg-perdu/20 text-red-200 ring-red-300/30',
        default => 'bg-egal/20 text-amber-200 ring-amber-300/30',
    };
    // Affiche un couple [france, adversaire] dans l'ordre domicile – extérieur
    $homeAway = fn (array $score) => $m->is_home ? $score : [$score[1], $score[0]];
    $halfTime = $sheet->halfTimeScore();
    $timeline = $sheet->timeline();
    $competition = $m->edition?->competition;
    $facts = array_filter([
        'Stade' => $m->venue ? $m->venue->name . ', ' . $m->venue->city : null,
        'Arbitre' => $m->referee ? $m->referee . ($m->refereeCountry ? ' (' . $m->refereeCountry->name . ')' : '') : null,
        'Affluence' => $m->attendance ? number_format($m->attendance, 0, ',', "\u{202F}") . ' spectateurs' : null,
        'Coup d\'envoi' => $m->kickoff_time ? substr($m->kickoff_time, 0, 5) : null,
        'Météo' => $m->weather,
    ]);
    $sections = array_filter([
        'compositions' => $sheet->hasLineups() ? 'Compositions' : null,
        'chronologie' => count($timeline) ? 'Chronologie' : null,
        'face-a-face' => 'Face-à-face',
    ]);
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2';
@endphp

@section('title', $home['name'] . ' ' . $home['score'] . '-' . $away['score'] . ' ' . $away['name'] . ' (' . $m->match_date->format('d/m/Y') . ') — Feuille de match')

@section('meta_description', $resultLabel . ' ' . $m->france_score . '-' . $m->opponent_score . ' face à ' . $m->opponent->name . ' le ' . $m->match_date->translatedFormat('j F Y') . ($m->venue ? ' à ' . $m->venue->city : '') . ' : composition, marqueurs et chronologie du match.')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <a href="{{ route('matches.index') }}" class="hover:text-bleu-france">Matches</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700">{{ $home['name'] }} – {{ $away['name'] }}, {{ $m->match_date->format('d/m/Y') }}</span>
@endsection

@section('content')
<div class="bg-papier text-texte">

    {{-- ═══ Tableau d'affichage ═══ --}}
    <header class="relative overflow-hidden bg-encre text-white">
        <div class="pointer-events-none absolute inset-x-0 top-0 flex h-1" aria-hidden="true">
            <span class="flex-1 bg-bleu-france"></span><span class="flex-1 bg-white"></span><span class="flex-1 bg-rouge-france"></span>
        </div>

        <div class="mx-auto max-w-6xl px-4 pb-10 pt-10 sm:px-6 lg:px-8 lg:pb-14 lg:pt-14">
            {{-- Contexte --}}
            <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center text-sm text-blue-200">
                @if($competition)
                    <a href="{{ route('editions.show', $m->edition) }}" class="font-semibold uppercase tracking-[0.18em] text-white hover:underline {{ $focus }} focus-visible:ring-white focus-visible:ring-offset-encre">
                        {{ $competition->name }} {{ $m->edition->year }}
                    </a>
                    @if($m->stage && $m->stage !== \App\Enums\MatchStage::JOURNEE && $m->stage !== \App\Enums\MatchStage::TEST)
                        <span aria-hidden="true">·</span><span>{{ $m->stage->label() }}</span>
                    @endif
                @else
                    <span class="font-semibold uppercase tracking-[0.18em] text-white">Test-match</span>
                @endif
                <span aria-hidden="true">·</span>
                <span>Match n° {{ number_format($franceMatchNumber, 0, ',', "\u{202F}") }} du XV de France</span>
            </div>
            <p class="mt-2 text-center font-serif text-lg italic text-blue-100">
                {{ ucfirst($m->match_date->translatedFormat('l j F Y')) }}@if($m->venue), {{ $m->venue->city }}@endif
            </p>

            {{-- Score --}}
            <div class="mt-8 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:gap-8">
                @foreach([$home, $away] as $i => $team)
                    @if($i === 1)
                        <div class="text-center">
                            <div class="flex items-baseline justify-center gap-2 font-display font-bold leading-none tabular-nums sm:gap-4">
                                <span class="text-5xl sm:text-8xl lg:text-9xl {{ $home['score'] >= $away['score'] ? 'text-white' : 'text-blue-300/70' }}">{{ $home['score'] }}</span>
                                <span class="text-3xl text-blue-300/50 sm:text-6xl" aria-hidden="true">–</span>
                                <span class="text-5xl sm:text-8xl lg:text-9xl {{ $away['score'] >= $home['score'] ? 'text-white' : 'text-blue-300/70' }}">{{ $away['score'] }}</span>
                            </div>
                            @if($halfTime)
                                @php [$htHome, $htAway] = $homeAway($halfTime); @endphp
                                <div class="mt-2 text-xs uppercase tracking-widest text-blue-200">Mi-temps {{ $htHome }}–{{ $htAway }}</div>
                            @endif
                        </div>
                    @endif
                    <div class="{{ $i === 0 ? 'text-right' : 'text-left' }} min-w-0">
                        <div class="text-3xl sm:text-6xl" aria-hidden="true">{{ $team['flag'] }}</div>
                        @if($team['isFrance'])
                            <div class="mt-2 font-display text-base font-bold uppercase leading-tight sm:text-4xl">France</div>
                        @else
                            <a href="{{ route('opponents.show', $m->opponent->code) }}" class="mt-2 inline-block max-w-full break-words font-display text-base font-bold uppercase leading-tight hover:underline sm:text-4xl {{ $focus }} focus-visible:ring-white focus-visible:ring-offset-encre">{{ $team['name'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-center">
                <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold ring-1 ring-inset {{ $resultTone }}">
                    {{ $resultLabel }}@if($m->point_diff !== 0)<span class="font-display tabular-nums opacity-80">{{ $m->point_diff > 0 ? '+' : '−' }}{{ abs($m->point_diff) }}</span>@endif
                </span>
            </div>

            {{-- Marqueurs --}}
            @if($sheet->hasEvents())
                <div class="mx-auto mt-8 grid max-w-4xl grid-cols-2 gap-4 border-t border-white/10 pt-6 text-xs sm:gap-12 sm:text-sm">
                    @foreach([$home, $away] as $i => $team)
                        <dl class="min-w-0 space-y-2 break-words {{ $i === 0 ? 'text-right' : 'text-left' }}">
                            @forelse($sheet->scorers($team['side']) as $group)
                                <div>
                                    <dt class="inline font-semibold text-blue-200">{{ $group['label'] }} :</dt>
                                    <dd class="inline text-white/90">
                                        @foreach($group['scorers'] as $scorer)
                                            <span>{{ $scorer['name'] }}@if($scorer['minutes']) <span class="text-blue-300/80 tabular-nums">({{ implode("', ", $scorer['minutes']) }}')</span>@elseif($scorer['count'] > 1) <span class="text-blue-300/80">×{{ $scorer['count'] }}</span>@endif</span>@if(!$loop->last), @endif
                                        @endforeach
                                    </dd>
                                </div>
                            @empty
                                <p class="text-blue-300/70">Aucun point saisi</p>
                            @endforelse
                        </dl>
                    @endforeach
                </div>
            @endif

            {{-- Faits --}}
            @if($facts)
                <dl class="mx-auto mt-8 flex max-w-4xl flex-wrap justify-center gap-x-8 gap-y-3 text-center text-sm">
                    @foreach($facts as $label => $value)
                        <div>
                            <dt class="text-[11px] uppercase tracking-[0.18em] text-blue-300">{{ $label }}</dt>
                            <dd class="mt-0.5 text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </header>

    {{-- ═══ Sommaire de la page ═══ --}}
    @if(count($sections) > 1)
        <nav class="sticky top-0 z-20 border-b border-filet bg-papier/90 backdrop-blur" aria-label="Sections de la feuille de match">
            <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
                @foreach($sections as $anchor => $label)
                    <a href="#{{ $anchor }}" class="whitespace-nowrap border-b-2 border-transparent px-3 py-3 text-sm font-semibold text-texte-2 hover:border-bleu-france hover:text-encre {{ $focus }} focus-visible:ring-bleu-france">{{ $label }}</a>
                @endforeach
            </div>
        </nav>
    @endif

    {{-- ═══ Feuille non saisie ═══ --}}
    @unless($sheet->isDetailed())
        <section class="mx-auto max-w-6xl px-4 pt-12 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start gap-4 rounded-2xl border border-dashed border-filet bg-white/60 p-6 sm:flex-row sm:items-center sm:p-8">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-papier-2 text-encre-3">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 4h6M9 4a2 2 0 0 0-2 2v0h10v0a2 2 0 0 0-2-2M9 4V3h6v1M7 6H5v15h14V6h-2M9 12h6M9 16h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <h2 class="font-display text-xl font-bold uppercase text-encre">Feuille de match à compléter</h2>
                    <p class="mt-1 max-w-2xl font-serif text-texte-2">
                        La composition des équipes et les marqueurs de ce match n'ont pas encore été saisis dans nos archives.
                        Le score, le lieu et l'historique des confrontations sont, eux, connus.
                    </p>
                </div>
            </div>
        </section>
    @endunless

    {{-- ═══ Compositions ═══ --}}
    @if($sheet->hasLineups())
        <section id="compositions" class="scroll-mt-16 py-12 lg:py-16" x-data="{ tab: '{{ $m->is_home ? 'home' : 'away' }}' }">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Compositions</h2>
                    <div class="inline-flex rounded-full bg-papier-2 p-1" role="tablist" aria-label="Équipe">
                        @foreach(['home' => $home, 'away' => $away] as $key => $team)
                            <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                    :class="tab === '{{ $key }}' ? 'bg-encre text-white shadow-sm' : 'text-texte-2 hover:text-encre'"
                                    class="min-h-[40px] rounded-full px-4 text-sm font-semibold motion-safe:transition-colors {{ $focus }} focus-visible:ring-bleu-france">
                                <span aria-hidden="true">{{ $team['flag'] }}</span> {{ $team['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @foreach(['home' => $home, 'away' => $away] as $key => $team)
                    @php $lineup = $sheet->lineup($team['side']); @endphp
                    <div x-show="tab === '{{ $key }}'" @if($key !== ($m->is_home ? 'home' : 'away')) x-cloak @endif role="tabpanel" class="mt-8">
                        @if(empty($lineup['starters']) && empty($lineup['bench']))
                            <p class="rounded-xl border border-dashed border-filet p-6 font-serif text-texte-2">La composition de {{ $team['isFrance'] ? 'la France' : $team['name'] }} n'est pas encore saisie pour ce match.</p>
                        @else
                            <div class="grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                                <x-match.pitch :starters="$lineup['starters']" :france="$team['isFrance']" :sheet="$sheet" :side="$team['side']" />

                                <div class="space-y-6">
                                    @foreach(['Titulaires' => $lineup['starters'], 'Remplaçants' => $lineup['bench']] as $title => $rows)
                                        @continue(empty($rows))
                                        <div>
                                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $title }}</h3>
                                            <ul class="divide-y divide-filet overflow-hidden rounded-xl border border-filet bg-white">
                                                @foreach($rows as $row)
                                                    <li>
                                                        <a href="{{ route('players.show', $row['player']) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-papier {{ $focus }} focus-visible:ring-inset focus-visible:ring-bleu-france">
                                                            <x-match.jersey :number="$row['jersey']" :france="$team['isFrance']" size="sm" class="{{ $team['isFrance'] ? '' : 'ring-encre/25' }}" />
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block truncate font-semibold text-encre">
                                                                    {{ $row['player']->first_name }} {{ $row['player']->last_name }}
                                                                    @if($row['captain'])<span class="ml-1 rounded bg-or/20 px-1.5 py-0.5 align-middle text-[10px] font-bold uppercase tracking-wide text-or-2">Capitaine</span>@endif
                                                                </span>
                                                                @if($row['position'])<span class="block text-xs text-texte-2">{{ $row['position']->label() }}</span>@endif
                                                            </span>
                                                            <span class="flex shrink-0 items-center gap-2 text-xs text-texte-2 tabular-nums">
                                                                @if($row['tries'])
                                                                    <span class="flex items-center gap-0.5 text-encre" title="{{ $row['tries'] }} essai(s)"><x-match.event-icon type="essai" class="h-4 w-4" />@if($row['tries'] > 1)<b>{{ $row['tries'] }}</b>@endif</span>
                                                                @endif
                                                                @if($row['points'] && $row['points'] !== $row['tries'] * \App\Enums\EventType::ESSAI->points($m->match_date))
                                                                    <span class="font-semibold text-encre">{{ $row['points'] }} pts</span>
                                                                @endif
                                                                @foreach($row['cards'] as $card)
                                                                    <span class="flex items-center" title="{{ $card['type']->label() }}{{ $card['minute'] ? ' (' . $card['minute'] . '\')' : '' }}"><x-match.event-icon :type="$card['type']->value" class="h-4 w-4" /></span>
                                                                @endforeach
                                                                @if($row['on'])<span class="text-gagne">↑ {{ $row['on'] }}'</span>@endif
                                                                @if($row['off'])<span class="text-perdu">↓ {{ $row['off'] }}'</span>@endif
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ Chronologie ═══ --}}
    @if(count($timeline))
        <section id="chronologie" class="scroll-mt-16 border-t border-filet bg-white py-12 lg:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Chronologie</h2>
                @unless($sheet->isScoreComplete())
                    @php [$cHome, $cAway] = $homeAway($sheet->computedScore()); @endphp
                    <p class="mt-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
                        Chronologie partielle : les actions saisies totalisent {{ $cHome }}–{{ $cAway }} pour un score final de {{ $home['score'] }}–{{ $away['score'] }}.
                        Le score n'est donc pas affiché minute par minute.
                    </p>
                @endunless

                {{-- En-têtes d'équipes --}}
                <div class="mt-8 grid grid-cols-[minmax(0,1fr)_3rem_minmax(0,1fr)] items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-texte-2 sm:grid-cols-[minmax(0,1fr)_4rem_minmax(0,1fr)]">
                    <span class="text-right">{{ $home['flag'] }} {{ $home['name'] }}</span><span></span><span>{{ $away['name'] }} {{ $away['flag'] }}</span>
                </div>

                <ol class="relative mt-3">
                    <span class="absolute inset-y-0 left-1/2 w-px -translate-x-1/2 bg-filet" aria-hidden="true"></span>
                    @php $halfShown = false; @endphp
                    @foreach($timeline as $item)
                        @if(!$halfShown && $item['minute'] !== null && $item['minute'] > 40)
                            @php $halfShown = true; @endphp
                            <li class="relative my-4 flex justify-center" aria-label="Mi-temps">
                                <span class="relative rounded-full bg-encre px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-white">
                                    Mi-temps @if($halfTime)@php [$htHome, $htAway] = $homeAway($halfTime); @endphp<span class="ml-1 font-display tabular-nums">{{ $htHome }}–{{ $htAway }}</span>@endif
                                </span>
                            </li>
                        @endif
                        @php
                            $isHomeSide = ($item['side'] === TeamSide::FRANCE) === $m->is_home;
                            $person = $item['event']?->player;
                            $sideTeam = $isHomeSide ? $home : $away;
                        @endphp
                        <li class="relative grid grid-cols-[minmax(0,1fr)_3rem_minmax(0,1fr)] items-center gap-2 py-1.5 sm:grid-cols-[minmax(0,1fr)_4rem_minmax(0,1fr)]">
                            <div class="{{ $isHomeSide ? '' : 'invisible' }} flex min-w-0 justify-end">
                                @if($isHomeSide) @include('matches.partials.timeline-item', ['item' => $item, 'align' => 'right', 'team' => $sideTeam]) @endif
                            </div>
                            <div class="relative flex justify-center">
                                <span class="rounded-full border border-filet bg-papier px-2 py-0.5 font-display text-sm font-bold tabular-nums text-encre">{{ $item['minute'] !== null ? $item['minute'] . "'" : '?' }}</span>
                            </div>
                            <div class="{{ $isHomeSide ? 'invisible' : '' }} flex min-w-0">
                                @unless($isHomeSide) @include('matches.partials.timeline-item', ['item' => $item, 'align' => 'left', 'team' => $sideTeam]) @endunless
                            </div>
                        </li>
                    @endforeach
                    @if($sheet->isScoreComplete())
                        <li class="relative mt-4 flex justify-center">
                            <span class="relative rounded-full bg-encre px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-white">Score final <span class="ml-1 font-display tabular-nums">{{ $home['score'] }}–{{ $away['score'] }}</span></span>
                        </li>
                    @endif
                </ol>
            </div>
        </section>
    @endif

    {{-- ═══ Face-à-face ═══ --}}
    <section id="face-a-face" class="scroll-mt-16 py-12 lg:py-16 {{ $sheet->isDetailed() ? 'border-t border-filet' : '' }}">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:px-8">
            <div>
                <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Face-à-face</h2>
                <p class="mt-3 font-serif text-lg text-texte-2">
                    @if($meetingNumber === 1)Première @else{{ $meetingNumber }}<sup>e</sup> @endif confrontation entre la France et {{ $m->opponent->withArticle() }}.
                </p>
                @php $share = fn ($n) => $headToHead->total ? round($n / $headToHead->total * 100, 2) : 0; @endphp
                <div class="mt-6 rounded-2xl border border-filet bg-white p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Bilan à cette date</div>
                    <dl class="mt-4 grid grid-cols-3 text-center">
                        <div><dt class="text-xs text-texte-2">Victoires</dt><dd class="font-display text-4xl font-bold tabular-nums text-gagne">{{ $headToHead->wins }}</dd></div>
                        <div><dt class="text-xs text-texte-2">Nuls</dt><dd class="font-display text-4xl font-bold tabular-nums text-egal">{{ $headToHead->draws }}</dd></div>
                        <div><dt class="text-xs text-texte-2">Défaites</dt><dd class="font-display text-4xl font-bold tabular-nums text-perdu">{{ $headToHead->losses }}</dd></div>
                    </dl>
                    <div class="mt-4 flex h-2 overflow-hidden rounded-full bg-papier-2" role="img" aria-label="{{ $headToHead->wins }} victoires, {{ $headToHead->draws }} nuls, {{ $headToHead->losses }} défaites">
                        <span class="bg-gagne" style="width: {{ $share($headToHead->wins) }}%"></span>
                        <span class="bg-egal" style="width: {{ $share($headToHead->draws) }}%"></span>
                        <span class="bg-perdu" style="width: {{ $share($headToHead->losses) }}%"></span>
                    </div>
                    @if($headToHeadTotal->total > $headToHead->total)
                        <p class="mt-4 text-sm text-texte-2">Aujourd'hui : {{ $headToHeadTotal->total }} matches, {{ $headToHeadTotal->wins }} victoires, {{ $headToHeadTotal->draws }} nuls, {{ $headToHeadTotal->losses }} défaites.</p>
                    @endif
                    <a href="{{ route('opponents.show', $m->opponent->code) }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-bleu-france hover:underline {{ $focus }} focus-visible:ring-bleu-france">
                        Toute l'histoire France – {{ $m->opponent->name }} <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Confrontations précédentes</h3>
                @if($recentMeetings->isEmpty())
                    <p class="mt-3 font-serif text-texte-2">C'était la première rencontre entre les deux équipes.</p>
                @else
                    <ol class="mt-3 divide-y divide-filet overflow-hidden rounded-2xl border border-filet bg-white">
                        @foreach($recentMeetings as $meeting)
                            @php $tone = match ($meeting->result) { 'Victoire' => 'bg-gagne', 'Défaite' => 'bg-perdu', default => 'bg-egal' }; @endphp
                            <li>
                                <a href="{{ route('matches.show', $meeting) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-papier {{ $focus }} focus-visible:ring-inset focus-visible:ring-bleu-france">
                                    <span class="h-8 w-1 shrink-0 rounded-full {{ $tone }}" aria-hidden="true"></span>
                                    <span class="w-24 shrink-0 text-sm tabular-nums text-texte-2">{{ $meeting->match_date->format('d/m/Y') }}</span>
                                    <span class="min-w-0 flex-1 truncate font-semibold text-encre">
                                        {{ $meeting->home_team_name }} <span class="font-display tabular-nums">{{ $meeting->home_score }}–{{ $meeting->away_score }}</span> {{ $meeting->away_team_name }}
                                    </span>
                                    <span class="hidden truncate text-xs text-texte-2 sm:block">{{ $meeting->edition?->competition?->short_name }}</span>
                                    <span class="sr-only">{{ $meeting->result }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </section>

    {{-- ═══ Match précédent / suivant ═══ --}}
    <nav class="border-t border-filet bg-papier-2" aria-label="Matches du XV de France">
        <div class="mx-auto grid max-w-6xl gap-4 px-4 py-8 sm:grid-cols-2 sm:px-6 lg:px-8">
            @foreach(['previous' => $previous, 'next' => $next] as $dir => $other)
                @if($other)
                    <a href="{{ route('matches.show', $other) }}" class="group rounded-2xl border border-filet bg-white p-5 hover:border-encre/30 {{ $dir === 'next' ? 'sm:text-right' : '' }} {{ $focus }} focus-visible:ring-bleu-france">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $dir === 'previous' ? '← Match précédent' : 'Match suivant →' }}</span>
                        <span class="mt-1 block font-semibold text-encre group-hover:underline">
                            {{ $other->home_team_name }} <span class="font-display tabular-nums">{{ $other->home_score }}–{{ $other->away_score }}</span> {{ $other->away_team_name }}
                        </span>
                        <span class="block text-sm text-texte-2">{{ ucfirst($other->match_date->translatedFormat('j F Y')) }}</span>
                    </a>
                @else
                    <span class="hidden sm:block"></span>
                @endif
            @endforeach
        </div>
    </nav>
</div>
@endsection
