{{-- Classement par écart : $title, $matches, $tone (gagne|perdu). Barre proportionnelle au plus grand écart. --}}
@php
    $max = max(1, $matches->max(fn ($m) => abs($m->point_diff)));
    [$text, $bar, $sign] = $tone === 'gagne' ? ['text-gagne', 'bg-gagne', '+'] : ['text-perdu', 'bg-perdu', '−'];
@endphp

<div class="rounded-2xl border border-filet bg-white">
    <h3 class="border-b border-filet px-5 py-4 font-display text-xl font-bold uppercase tracking-tight text-encre">{{ $title }}</h3>
    <ol class="divide-y divide-filet">
        @foreach($matches as $match)
            <li>
                <a href="{{ route('matches.show', $match) }}"
                   class="grid grid-cols-[1.5rem_minmax(0,1fr)_3.5rem] items-center gap-x-3 px-5 py-3 hover:bg-papier focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bleu-france">
                    <span class="font-display text-lg font-semibold tabular-nums text-texte-2">{{ $loop->iteration }}</span>
                    <span class="min-w-0">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="truncate font-medium text-encre"><span aria-hidden="true">{{ $match->opponent->flag_emoji }}</span> {{ $match->opponent->name }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-texte-2">{{ $match->france_score }}–{{ $match->opponent_score }} · {{ $match->match_date->year }}</span>
                        </span>
                        <span class="mt-1.5 block h-1.5 rounded-full bg-papier-2" aria-hidden="true">
                            <span class="block h-full rounded-full {{ $bar }}" style="width: {{ round(abs($match->point_diff) / $max * 100, 1) }}%"></span>
                        </span>
                    </span>
                    <span class="text-right font-display text-2xl font-bold tabular-nums {{ $text }}">{{ $sign }}{{ abs($match->point_diff) }}</span>
                </a>
            </li>
        @endforeach
    </ol>
</div>
