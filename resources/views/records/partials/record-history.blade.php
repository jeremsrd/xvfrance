{{-- Recordmen successifs : $history (RecordsService::capsRecordHistory()…), $unit = [singulier, pluriel],
     $reached = fn (int $n) => « avec sa 100<sup>e</sup> sélection » (HTML), $focus --}}
@php
    $count = fn (int $n) => $n . ' ' . $unit[$n > 1 ? 1 : 0];
    $on = fn ($match) => 'le ' . $match->match_date->format('d/m/Y') . ' contre ' . $match->opponent->withArticle();

    // Règnes à l'échelle : de 1906 à aujourd'hui, une barre par recordman seul, longueur = durée du règne
    $start = $history->first()['match']->match_date;
    $now = now();
    $span = $start->diffInDays($now);
    $at = fn ($date) => round($start->diffInDays($date) / $span * 100, 2);
    $reigns = app(\App\Services\RecordsService::class)->soleReigns($history)
        ->map(fn ($reign) => ['label' => $reign['player']->fullName(), 'to' => $reign['to'] ?? $now, 'current' => $reign['to'] === null] + $reign);
    if ($reigns->isNotEmpty() && $reigns->first()['from']->gt($start)) {
        $reigns->prepend(['label' => 'Record partagé', 'player' => null, 'from' => $start, 'to' => $reigns->first()['from'], 'current' => false]);
    }
    $duration = function ($reign) {
        $n = (int) floor($reign['from']->diffInYears($reign['to']));

        return $n < 1 ? "moins d'un an" : $n . ' an' . ($n > 1 ? 's' : '');
    };
    $ticks = [];
    for ($year = (int) ceil($start->year / 20) * 20; $year <= $now->year; $year += 20) {
        $ticks[] = $year;
    }
@endphp

<figure class="mt-10 max-w-4xl">
    <figcaption class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Durée de chaque règne, de {{ $start->year }} à aujourd'hui</figcaption>
    <ol class="mt-4 space-y-1.5">
        @foreach($reigns as $reign)
            @php $left = $at($reign['from']); $width = max($at($reign['to']) - $left, 0.6); @endphp
            <li class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-3 sm:grid-cols-[12rem_minmax(0,1fr)]">
                <span class="min-w-0 text-right text-xs leading-tight sm:text-sm">
                    @if($reign['player'])
                        <a href="{{ route('players.show', $reign['player']) }}" class="block truncate font-semibold text-encre hover:underline {{ $focus }}">{{ $reign['label'] }}</a>
                    @else
                        <span class="block truncate font-semibold text-texte-2">{{ $reign['label'] }}</span>
                    @endif
                    <span class="block text-[11px] tabular-nums text-texte-2">{{ $reign['from']->year }}–{{ $reign['current'] ? 'auj.' : $reign['to']->year }} · {{ $duration($reign) }}</span>
                </span>
                <span class="relative block h-5 rounded-sm bg-papier-2" title="{{ $reign['label'] }} : {{ $duration($reign) }}">
                    <span @class(['absolute inset-y-0 rounded-sm', 'bg-or' => $reign['current'], 'bg-bleu-france' => !$reign['current'] && $reign['player'], 'bg-texte-2/40' => !$reign['player']])
                          style="left: {{ $left }}%; width: {{ $width }}%"></span>
                </span>
            </li>
        @endforeach
    </ol>
    {{-- Axe des années --}}
    <div class="mt-1 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-3 sm:grid-cols-[12rem_minmax(0,1fr)]" aria-hidden="true">
        <span></span>
        <span class="relative block h-4 text-[10px] tabular-nums text-texte-2 sm:text-xs">
            @foreach($ticks as $year)
                <span class="absolute -translate-x-1/2" style="left: {{ $at(\Illuminate\Support\Carbon::create($year)) }}%">{{ $year }}</span>
            @endforeach
        </span>
    </div>
</figure>

<ol class="relative mt-12 max-w-3xl border-l-2 border-filet pl-6 sm:pl-8">
    @foreach($history as $entry)
        @php
            $player = $entry['players']->first();
            $alone = $entry['players']->count() === 1;
            $current = $alone && !$entry['equalled'] && $entry['until'] === null;
        @endphp
        <li class="relative pb-8 last:pb-0">
            <span @class([
                'absolute top-1.5 -left-[calc(1.5rem+7px)] h-3 w-3 rounded-full ring-4 ring-papier sm:-left-[calc(2rem+7px)]',
                'bg-or' => $current,
                'bg-bleu-france' => !$current && !$entry['equalled'],
                'bg-white border-2 border-bleu-france' => $entry['equalled'],
            ]) aria-hidden="true"></span>

            <p class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-texte-2 tabular-nums">
                {{ $entry['match']->match_date->year }}
                @if($alone && !$entry['equalled'])
                    · <span class="text-encre">{{ $count($entry['value']) }}</span>
                @endif
            </p>

            @if($alone)
                <div class="mt-2 flex items-start gap-4">
                    @if($player->photo_path)
                        <img src="{{ $player->photo_path }}" alt="" loading="lazy" class="h-14 w-14 shrink-0 rounded-full object-cover object-top ring-1 ring-filet">
                    @endif
                    <div class="min-w-0">
                        <a href="{{ route('players.show', $player) }}" class="font-display text-2xl font-bold uppercase tracking-tight text-encre hover:underline {{ $focus }}">{{ $player->fullName() }}</a>
                        <p class="mt-1 font-serif text-texte-2">
                            @if($entry['equalled'])
                                Égale le record ({{ $count($entry['value']) }}) {{ $on($entry['match']) }} et le partage avec
                                {{ $entry['with']->map->fullName()->join(', ', ' et ') }}, sans le battre.
                            @else
                                Prend seul le record {!! $reached($entry['value']) !!}, <a href="{{ route('matches.show', $entry['match']) }}" class="underline decoration-filet underline-offset-2 hover:decoration-encre {{ $focus }}">{{ $on($entry['match']) }}</a>@if($entry['equalledMatch']), après l'avoir égalé {{ $on($entry['equalledMatch']) }}@endif.
                            @endif
                        </p>
                        @php
                            $reign = match (true) {
                                $current => 'Recordman depuis ' . $entry['match']->match_date->year
                                    . ' (' . $duration(['from' => $entry['match']->match_date, 'to' => $now]) . ')',
                                !$entry['equalled'] && $entry['until'] !== null => "Recordman jusqu'en " . $entry['until']->match_date->year
                                    . ' (' . $duration(['from' => $entry['match']->match_date, 'to' => $entry['until']->match_date]) . ')',
                                default => null,
                            };
                        @endphp
                        <p class="mt-1 text-sm text-texte-2">
                            @if($reign)<b @class(['font-semibold text-encre' => $current, 'font-normal' => !$current])>{{ $reign }}</b> · @endif{{ $count($entry['totals'][$player->id]) }} en carrière
                        </p>
                    </div>
                </div>
            @else
                {{-- Record partagé : premières années, ou deux joueurs à égalité --}}
                <p class="mt-2 font-serif text-texte-2">
                    @if($entry['players']->count() === 2)
                        <span class="font-semibold text-encre">
                            @foreach($entry['players'] as $p)
                                <a href="{{ route('players.show', $p) }}" class="hover:underline {{ $focus }}">{{ $p->fullName() }}</a>@if(!$loop->last) et @endif
                            @endforeach
                        </span>
                        partagent le record ({{ $count($entry['value']) }}) {{ $on($entry['match']) }}.
                    @else
                        Record partagé par {{ $entry['players']->count() }} joueurs ({{ $count($entry['value']) }}) {{ $on($entry['match']) }} :
                        @foreach($entry['players'] as $p)
                            <a href="{{ route('players.show', $p) }}" class="hover:underline {{ $focus }}">{{ $p->fullName() }}</a>{{ $loop->last ? '.' : ',' }}
                        @endforeach
                    @endif
                </p>
            @endif
        </li>
    @endforeach
</ol>
