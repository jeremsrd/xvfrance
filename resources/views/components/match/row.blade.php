@props(['match', 'competition' => true, 'venue' => false, 'date' => 'd/m/Y'])

{{-- Ligne de match des listes : barre de résultat, date, score (domicile d'abord), compétition --}}
@php
    $tone = match ($match->result) { 'Victoire' => 'bg-gagne', 'Défaite' => 'bg-perdu', default => 'bg-egal' };
    $meta = array_filter([
        $competition && $match->edition?->competition ? $match->edition->competition->short_name . ' ' . $match->edition->year : null,
        $venue && $match->venue ? $match->venue->city : null,
    ]);
@endphp
<a href="{{ route('matches.show', $match) }}"
   {{ $attributes->merge(['class' => 'group grid grid-cols-[0.25rem_minmax(0,1fr)] items-center gap-x-4 gap-y-0.5 px-4 py-3 hover:bg-papier focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bleu-france sm:grid-cols-[0.25rem_5.5rem_minmax(0,1fr)_auto] sm:px-5']) }}>
    <span class="row-span-2 h-9 w-1 rounded-full sm:row-span-1 {{ $tone }}" aria-hidden="true"></span>
    <span class="min-w-0 truncate font-semibold text-encre group-hover:underline sm:order-2">
        <span aria-hidden="true">{{ $match->home_team_flag }}</span>
        {{ $match->home_team_name }}
        <span class="mx-0.5 font-display text-lg tabular-nums">{{ $match->home_score }}–{{ $match->away_score }}</span>
        {{ $match->away_team_name }}
        <span aria-hidden="true">{{ $match->away_team_flag }}</span>
        <span class="sr-only">, {{ $match->result === 'Nul' ? 'match nul' : mb_strtolower($match->result) . ' de la France' }}</span>
    </span>
    <span class="flex min-w-0 gap-2 text-xs text-texte-2 sm:contents sm:text-sm">
        <span class="tabular-nums sm:order-1">{{ $match->match_date->format($date) }}</span>
        @if($meta)<span class="truncate sm:order-3 sm:text-right sm:text-xs"><span class="sm:hidden" aria-hidden="true">· </span>{{ implode(' · ', $meta) }}</span>@endif
    </span>
</a>
