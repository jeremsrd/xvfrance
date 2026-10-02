{{-- Une action de la chronologie : $item (voir MatchSheet::timeline), $align (left|right), $team --}}
@php
    $event = $item['event'];
    $sub = $item['sub'];
    $isScore = $event && $event->event_type->points($rugbyMatch->match_date) > 0;
    $isTry = in_array($item['type'], ['essai', 'essai_penalite']);
    $iconTone = $isTry ? ($team['isFrance'] ? 'text-bleu-france' : 'text-encre') : 'text-texte-2';
    if ($item['score']) {
        [$sHome, $sAway] = $rugbyMatch->is_home ? $item['score'] : [$item['score'][1], $item['score'][0]];
    }
@endphp

<div class="flex min-w-0 max-w-full items-center gap-2 rounded-xl px-2 py-2 sm:gap-2.5 sm:px-3 {{ $align === 'right' ? 'flex-row-reverse text-right' : '' }} {{ $isTry ? 'bg-papier ring-1 ring-filet' : '' }}">
    <span class="flex h-6 w-6 shrink-0 sm:h-7 sm:w-7 items-center justify-center {{ $iconTone }}">
        <x-match.event-icon :type="$item['type']" class="{{ $isTry ? 'h-6 w-6' : 'h-5 w-5' }}" />
    </span>
    <span class="min-w-0">
        <span class="block text-[11px] font-semibold uppercase tracking-wider text-texte-2">{{ $item['label'] }}</span>
        @if($sub)
            <span class="block truncate text-sm text-encre">
                <span class="text-gagne">↑</span> <a href="{{ route('players.show', $sub->playerOn) }}" class="font-semibold hover:underline">{{ $sub->playerOn->last_name }}</a>
                <span class="text-perdu">↓</span> <a href="{{ route('players.show', $sub->playerOff) }}" class="hover:underline">{{ $sub->playerOff->last_name }}</a>
            </span>
        @elseif($event?->player)
            <a href="{{ route('players.show', $event->player) }}" class="block break-words font-semibold leading-snug text-encre hover:underline {{ $isTry ? 'text-base' : 'text-sm' }}">{{ $event->player->first_name }} {{ $event->player->last_name }}</a>
        @endif
    </span>
    @if($isScore && $item['score'])
        <span class="shrink-0 rounded-md bg-encre px-1.5 py-0.5 font-display text-sm font-bold tabular-nums text-white">{{ $sHome }}–{{ $sAway }}</span>
    @endif
</div>
