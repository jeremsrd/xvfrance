@props(['match', 'featured' => false])

@php
    $bgClass = match($match->result) {
        'Victoire' => 'border-l-green-500',
        'Défaite' => 'border-l-red-500',
        'Nul' => 'border-l-yellow-500',
        default => 'border-l-gray-300',
    };
@endphp

<a href="{{ route('matches.show', $match) }}"
   class="block bg-white rounded-lg shadow-sm border border-gray-200 border-l-4 {{ $bgClass }} hover:shadow-md transition {{ $featured ? 'p-6' : 'p-4' }}">

    <div class="flex items-center justify-between">
        <div class="flex-1">
            {{-- Date et compétition --}}
            <div class="text-xs text-gray-500 mb-1">
                {{ $match->match_date->format('d/m/Y') }}
                @if($match->edition && $match->edition->competition)
                    <span class="text-gray-300 mx-1">|</span>
                    {{ $match->edition->competition->short_name }}
                @endif
            </div>

            {{-- Score --}}
            <div class="flex items-center gap-3 {{ $featured ? 'text-2xl' : 'text-lg' }}">
                <span class="font-bold">France</span>
                <span class="font-mono font-bold">{{ $match->france_score }}</span>
                <span class="text-gray-400">-</span>
                <span class="font-mono font-bold">{{ $match->opponent_score }}</span>
                <x-country-flag :country="$match->opponent" class="font-bold" />
            </div>

            {{-- Stade --}}
            @if($match->venue && $featured)
                <div class="text-sm text-gray-500 mt-2">
                    {{ $match->venue->name }}, {{ $match->venue->city }}
                </div>
            @endif
        </div>

        <div class="ml-4">
            <x-result-badge :result="$match->result" />
        </div>
    </div>
</a>
