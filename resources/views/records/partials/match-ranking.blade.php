{{-- Classement de matches : $title, $matches, $value (fn RugbyMatch → string), $tone (emerald|red|slate) --}}
@php
    $valueClass = match ($tone ?? 'slate') {
        'emerald' => 'text-emerald-700',
        'red' => 'text-red-700',
        default => 'text-slate-900',
    };
@endphp

<div class="rounded-xl border border-slate-200 bg-white shadow-xs">
    <h3 class="border-b border-slate-100 px-5 py-4 font-display text-lg font-semibold uppercase tracking-tight text-slate-900">{{ $title }}</h3>

    @if($matches->isEmpty())
        <p class="px-5 py-6 text-sm text-slate-500">Aucun match.</p>
    @else
        <ol class="divide-y divide-slate-100">
            @foreach($matches as $match)
                <li>
                    <a href="{{ route('matches.show', $match) }}"
                       class="grid grid-cols-[1.75rem_1fr_auto] items-center gap-3 px-5 py-3 motion-safe:transition-colors hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bleu-france">
                        <span class="font-display text-lg font-semibold tabular-nums text-slate-400">{{ $loop->iteration }}</span>
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-slate-900">
                                <span aria-hidden="true">{{ $match->opponent->flag_emoji }}</span>
                                France {{ $match->france_score }}–{{ $match->opponent_score }} {{ $match->opponent->name }}
                            </span>
                            <span class="block text-xs text-slate-500">{{ $match->match_date->format('d/m/Y') }}</span>
                        </span>
                        <span class="font-display text-2xl font-bold tabular-nums {{ $valueClass }}">{{ $value($match) }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    @endif
</div>
