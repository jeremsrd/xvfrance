{{-- Liste d'une équipe avec colonnes de stats : $rows, $title, $team, $focus --}}
@php $cols = 'sm:grid-cols-[2rem_minmax(0,1fr)_3rem_3rem_3rem_4.5rem]'; @endphp

<div>
    <div class="mb-2 grid grid-cols-[2rem_minmax(0,1fr)] items-end gap-3 px-4 {{ $cols }}">
        <h3 class="col-span-2 text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $title }}</h3>
        @foreach(['Min.', 'Essais', 'Pts', 'Cartons'] as $label)
            <span class="hidden text-center text-[11px] font-semibold uppercase tracking-wider text-texte-2 sm:block">{{ $label }}</span>
        @endforeach
    </div>

    <ul class="divide-y divide-filet overflow-hidden rounded-xl border border-filet bg-white">
        @foreach($rows as $row)
            @php
                $minutes = $row['minutes'] !== null ? $row['minutes'] . "'" : null;
                $subNote = $row['on'] ? 'Entré à la ' . $row['on'] . 'e' : null;
                if ($row['off']) {
                    $subNote = ($subNote ? $subNote . ', sorti à la ' : 'Sorti à la ') . $row['off'] . 'e';
                }
                $didNotPlay = $row['minutes'] === 0;
            @endphp
            <li>
                <a href="{{ route('players.show', $row['player']) }}"
                   class="grid grid-cols-[2rem_minmax(0,1fr)] items-center gap-3 px-4 py-2.5 hover:bg-papier {{ $cols }} {{ $focus }} focus-visible:ring-inset focus-visible:ring-bleu-france {{ $didNotPlay ? 'opacity-60' : '' }}">
                    <x-match.jersey :number="$row['jersey']" :france="$team['isFrance']" size="sm" class="{{ $team['isFrance'] ? '' : 'ring-encre/25' }}" />

                    <span class="min-w-0">
                        <span class="block truncate font-semibold text-encre">
                            {{ $row['player']->first_name }} {{ $row['player']->last_name }}
                            @if($row['captain'])<span class="ml-1 rounded bg-or/20 px-1.5 py-0.5 align-middle text-[10px] font-bold uppercase tracking-wide text-or-2">Capitaine</span>@endif
                        </span>
                        <span class="block truncate text-xs text-texte-2">
                            {{ $row['position']?->label() }}@if($row['position'] && ($subNote || $didNotPlay)) · @endif{{ $didNotPlay ? 'N\'est pas entré en jeu' : $subNote }}
                        </span>
                        {{-- Mobile : stats en ligne --}}
                        @if($minutes || $row['tries'] || $row['points'] || $row['cards'])
                            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-encre tabular-nums sm:hidden">
                                @if($minutes)<span>{{ $row['minutes'] }} min jouées</span>@endif
                                @if($row['tries'])<span class="flex items-center gap-1"><x-match.event-icon type="essai" class="h-3.5 w-3.5" />{{ $row['tries'] }} essai{{ $row['tries'] > 1 ? 's' : '' }}</span>@endif
                                @if($row['points'])<span class="font-semibold">{{ $row['points'] }} pts</span>@endif
                                @foreach($row['cards'] as $card)
                                    <span class="flex items-center gap-0.5"><x-match.event-icon :type="$card['type']->value" class="h-3.5 w-3.5" />@if($card['minute']){{ $card['minute'] }}'@endif</span>
                                @endforeach
                            </span>
                        @endif
                    </span>

                    {{-- Ordinateur : colonnes alignées --}}
                    <span class="hidden text-center text-sm tabular-nums text-texte-2 sm:block">{{ $minutes }}</span>
                    <span class="hidden items-center justify-center gap-0.5 text-sm font-semibold tabular-nums text-encre sm:flex">
                        @if($row['tries'])<x-match.event-icon type="essai" class="h-4 w-4" />{{ $row['tries'] }}@endif
                    </span>
                    <span class="hidden text-center font-display text-base font-bold tabular-nums text-encre sm:block">{{ $row['points'] ?: '' }}</span>
                    <span class="hidden flex-wrap items-center justify-center gap-1 text-xs tabular-nums text-texte-2 sm:flex">
                        @foreach($row['cards'] as $card)
                            <span class="flex items-center gap-0.5" title="{{ $card['type']->label() }}"><x-match.event-icon :type="$card['type']->value" class="h-4 w-4" />@if($card['minute']){{ $card['minute'] }}'@endif</span>
                        @endforeach
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
