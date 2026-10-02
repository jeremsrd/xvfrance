{{-- Classement de matches : $title, $matches, $value (fn RugbyMatch → string), $tone (emerald|red|slate) --}}
@php
    $valueClass = match ($tone ?? 'slate') {
        'emerald' => 'text-gagne',
        'red' => 'text-perdu',
        default => 'text-encre',
    };
@endphp

<div class="rounded-xl border border-filet bg-white shadow-xs">
    <h3 class="border-b border-filet px-5 py-4 font-display text-xl font-bold uppercase tracking-tight text-encre">{{ $title }}</h3>

    @if($matches->isEmpty())
        <p class="px-5 py-6 text-sm text-texte-2">Aucun match.</p>
    @else
        <ol class="divide-y divide-filet">
            @foreach($matches as $match)
                <li>
                    <a href="{{ route('matches.show', $match) }}"
                       class="grid grid-cols-[1.75rem_1fr_auto] items-center gap-3 px-5 py-3 motion-safe:transition-colors hover:bg-papier-2/60 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bleu-france">
                        <span class="font-display text-lg font-semibold tabular-nums text-texte-2/70">{{ $loop->iteration }}</span>
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-encre">
                                <span aria-hidden="true">{{ $match->opponent->flag_emoji }}</span>
                                France {{ $match->france_score }}–{{ $match->opponent_score }} {{ $match->opponent->name }}
                            </span>
                            <span class="block text-xs text-texte-2">{{ $match->match_date->format('d/m/Y') }}</span>
                        </span>
                        <span class="font-display text-2xl font-bold tabular-nums {{ $valueClass }}">{{ $value($match) }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    @endif
</div>
