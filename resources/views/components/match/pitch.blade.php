@props(['starters', 'france' => true, 'sheet', 'side'])

@php
    // Placement des n° 1 à 15 sur le terrain (en %), avants en haut, arrière en bas
    $spots = [
        1 => [26, 9], 2 => [50, 9], 3 => [74, 9],
        4 => [38, 23], 5 => [62, 23],
        6 => [16, 36], 8 => [50, 39], 7 => [84, 36],
        9 => [40, 53], 10 => [24, 63],
        12 => [42, 72], 13 => [64, 76],
        11 => [13, 81], 14 => [87, 81],
        15 => [50, 91],
    ];
@endphp

<div class="relative mx-auto aspect-[4/5] w-full max-w-md overflow-hidden rounded-2xl bg-encre ring-1 ring-encre/10 sm:aspect-[5/6]">
    {{-- Lignes du terrain --}}
    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 120" preserveAspectRatio="none" aria-hidden="true">
        <defs>
            <pattern id="stripes-{{ $side->value }}" width="100" height="20" patternUnits="userSpaceOnUse">
                <rect width="100" height="10" fill="rgba(255,255,255,.025)"/>
            </pattern>
        </defs>
        <rect width="100" height="120" fill="url(#stripes-{{ $side->value }})"/>
        <g stroke="rgba(255,255,255,.14)" stroke-width=".35" fill="none">
            <rect x="3" y="3" width="94" height="114"/>
            <line x1="3" y1="60" x2="97" y2="60" stroke-width=".5"/>
            <line x1="3" y1="38" x2="97" y2="38" stroke-dasharray="1.6 1.6"/>
            <line x1="3" y1="82" x2="97" y2="82" stroke-dasharray="1.6 1.6"/>
            <line x1="3" y1="25" x2="97" y2="25"/>
            <line x1="3" y1="95" x2="97" y2="95"/>
        </g>
    </svg>

    @foreach($starters as $row)
        @continue(!isset($spots[$row['jersey']]))
        @php [$x, $y] = $spots[$row['jersey']]; @endphp
        <a href="{{ route('players.show', $row['player']) }}"
           class="group absolute flex w-[22%] -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-1 text-center focus-visible:outline-hidden"
           style="left: {{ $x }}%; top: {{ $y }}%">
            <span class="relative">
                <x-match.jersey :number="$row['jersey']" :france="$france"
                    class="shadow-lg shadow-black/30 motion-safe:transition-transform group-hover:scale-110 group-focus-visible:ring-2 group-focus-visible:ring-or" />
                @if($row['captain'])
                    <span class="absolute -right-1.5 -top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-or font-display text-[10px] font-bold text-encre" title="Capitaine">C</span>
                @endif
                @if($row['tries'] > 0)
                    <span class="absolute -bottom-1 -right-2 flex items-center rounded-full bg-white px-1 text-[10px] font-bold text-encre" title="{{ $row['tries'] }} essai(s)">
                        <x-match.event-icon type="essai" class="h-3 w-3 text-encre" />@if($row['tries'] > 1)<span>{{ $row['tries'] }}</span>@endif
                    </span>
                @endif
            </span>
            <span class="w-full truncate text-[11px] font-semibold leading-tight text-white sm:text-xs">{{ $sheet->shortName($row['player'], $side) }}</span>
            @if($row['off'])
                <span class="-mt-0.5 text-[10px] leading-none text-blue-200/80 tabular-nums">↓ {{ $row['off'] }}'</span>
            @endif
        </a>
    @endforeach
</div>
