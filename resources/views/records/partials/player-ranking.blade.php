{{-- Classement de joueurs : $title, $rows (avec ->player et ->total), $unit (singulier, pluriel) --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-xs">
    <h3 class="border-b border-slate-100 px-5 py-4 font-display text-lg font-semibold uppercase tracking-tight text-slate-900">{{ $title }}</h3>

    @if($rows->isEmpty())
        <p class="px-5 py-6 text-sm text-slate-500">Pas encore de données.</p>
    @else
        <ol class="divide-y divide-slate-100">
            @foreach($rows as $row)
                <li>
                    <a href="{{ route('players.show', $row->player) }}"
                       class="grid grid-cols-[1.75rem_1fr_auto] items-center gap-3 px-5 py-3 motion-safe:transition-colors hover:bg-slate-50 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-bleu-france">
                        <span class="font-display text-lg font-semibold tabular-nums text-slate-400">{{ $loop->iteration }}</span>
                        <span class="truncate font-medium text-slate-900">{{ $row->player->fullName() }}</span>
                        <span class="text-sm text-slate-500">
                            <span class="font-display text-2xl font-bold tabular-nums text-slate-900">{{ $row->total }}</span>
                            {{ $row->total > 1 ? $unit[1] : $unit[0] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    @endif
</div>
