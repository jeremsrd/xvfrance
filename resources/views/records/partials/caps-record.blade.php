{{-- Recordmen des sélections successifs : $history (RecordsService::capsRecordHistory()), $focus --}}
@php
    $on = fn ($match) => 'le ' . $match->match_date->format('d/m/Y') . ' contre ' . $match->opponent->withArticle();
    $years = fn ($from, $to) => $to->match_date->year - $from->match_date->year;
@endphp

<ol class="relative mt-10 max-w-3xl border-l-2 border-filet pl-6 sm:pl-8">
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
                    · <span class="text-encre">{{ $entry['caps'] }} sélections</span>
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
                                Égale le record ({{ $entry['caps'] }} sélections) {{ $on($entry['match']) }} et le partage avec
                                {{ $entry['with']->map->fullName()->join(', ', ' et ') }}, sans le battre.
                            @else
                                Prend seul le record avec sa {{ $entry['caps'] }}<sup>e</sup> sélection, <a href="{{ route('matches.show', $entry['match']) }}" class="underline decoration-filet underline-offset-2 hover:decoration-encre {{ $focus }}">{{ $on($entry['match']) }}</a>@if($entry['equalledMatch']), après l'avoir égalé {{ $on($entry['equalledMatch']) }}@endif.
                            @endif
                        </p>
                        @php
                            $reign = match (true) {
                                $current => 'Recordman depuis ' . $entry['match']->match_date->year,
                                !$entry['equalled'] && $entry['until'] !== null => "Recordman jusqu'en " . $entry['until']->match_date->year
                                    . (($n = $years($entry['match'], $entry['until'])) > 0 ? " ({$n} an" . ($n > 1 ? 's' : '') . ')' : ''),
                                default => null,
                            };
                        @endphp
                        <p class="mt-1 text-sm text-texte-2">
                            @if($reign)<b @class(['font-semibold text-encre' => $current, 'font-normal' => !$current])>{{ $reign }}</b> · @endif{{ $entry['totals'][$player->id] }} sélections en carrière
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
                        partagent le record ({{ $entry['caps'] }} sélections) {{ $on($entry['match']) }}.
                    @else
                        Record partagé par {{ $entry['players']->count() }} joueurs ({{ $entry['caps'] }} sélection{{ $entry['caps'] > 1 ? 's' : '' }}) {{ $on($entry['match']) }} :
                        @foreach($entry['players'] as $p)
                            <a href="{{ route('players.show', $p) }}" class="hover:underline {{ $focus }}">{{ $p->fullName() }}</a>{{ $loop->last ? '.' : ',' }}
                        @endforeach
                    @endif
                </p>
            @endif
        </li>
    @endforeach
</ol>
