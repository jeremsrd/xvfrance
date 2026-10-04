{{-- Classement de joueurs : $title, $rows (avec ->player et ->total), $unit (singulier, pluriel) --}}
<div class="rounded-2xl border border-filet bg-white">
    <h3 class="border-b border-filet px-5 py-4 font-display text-xl font-bold uppercase tracking-tight text-encre">{{ $title }}</h3>

    @if($rows->isEmpty())
        <p class="px-5 py-6 text-sm text-texte-2">Pas encore de données.</p>
    @else
        <ol class="divide-y divide-filet">
            @foreach($rows as $row)
                <li>
                    <a href="{{ route('players.show', $row->player) }}"
                       class="grid grid-cols-[1.75rem_1fr_auto] items-center gap-3 px-5 py-3 hover:bg-papier focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bleu-france">
                        <span class="font-display text-lg font-semibold tabular-nums text-texte-2">{{ $loop->iteration }}</span>
                        <span class="truncate font-medium text-encre">{{ $row->player->fullName() }}</span>
                        <span class="text-sm text-texte-2">
                            <span class="font-display text-2xl font-bold tabular-nums text-encre">{{ $row->total }}</span>
                            {{ $row->total > 1 ? $unit[1] : $unit[0] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    @endif
</div>
