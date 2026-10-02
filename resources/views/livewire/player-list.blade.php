@section('title', 'Les joueurs du XV de France et leurs adversaires')

@section('meta_description', 'Les joueurs du XV de France et leurs adversaires : recherche par nom, poste et nationalité, matches recensés.')

@section('breadcrumb')
    <x-breadcrumb :items="['Joueurs' => null]" />
@endsection

@php
    $fr = fn ($n) => number_format($n, 0, ',', "\u{202F}");
    $field = 'h-11 w-full rounded-full border border-filet bg-white px-4 text-sm text-encre shadow-xs focus:border-bleu-france focus:outline-hidden focus:ring-2 focus:ring-bleu-france/30';
    $tabs = ['bleus' => ['Les Bleus', $counts['bleus']], 'adversaires' => ['Adversaires', $counts['adversaires']], 'tous' => ['Tous', $counts['bleus'] + $counts['adversaires']]];
    $byLetter = $order === 'nom';
    $currentLetter = null;
@endphp

<div>
    <x-page.hero kicker="L'effectif" title="Les joueurs"
                 subtitle="Les Bleus et leurs adversaires, tels qu'ils apparaissent sur les feuilles de match saisies dans nos archives." />

    {{-- ═══ Onglets et filtres ═══ --}}
    <section class="border-b border-filet bg-papier-2/60" aria-label="Filtres">
        <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="Équipe">
                @foreach($tabs as $key => [$label, $count])
                    <button type="button" role="tab" wire:click="$set('team', '{{ $key }}')" aria-selected="{{ $team === $key ? 'true' : 'false' }}"
                            class="inline-flex min-h-[40px] items-center gap-2 rounded-full px-4 text-sm font-semibold focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france {{ $team === $key ? 'bg-encre text-white' : 'bg-white text-texte-2 ring-1 ring-inset ring-filet hover:text-encre' }}">
                        {{ $label }} <span class="tabular-nums {{ $team === $key ? 'text-blue-200' : 'text-texte-2/70' }}">{{ $fr($count) }}</span>
                    </button>
                @endforeach
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))]">
                <label class="relative col-span-2 block lg:col-span-1">
                    <span class="sr-only">Rechercher un joueur</span>
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-texte-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Nom ou prénom (ex. Dupont)" class="{{ $field }} pl-10">
                </label>
                <label>
                    <span class="sr-only">Poste</span>
                    <select wire:model.live="position" class="{{ $field }}">
                        <option value="">Tous les postes</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                        @endforeach
                    </select>
                </label>
                @if($team !== 'bleus')
                    <label>
                        <span class="sr-only">Nationalité</span>
                        <select wire:model.live="country" class="{{ $field }}">
                            <option value="">Tous les pays</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="{{ $team === 'bleus' ? 'col-span-1 lg:col-span-2' : '' }}">
                    <span class="sr-only">Trier</span>
                    <select wire:model.live="order" class="{{ $field }}">
                        <option value="nom">Par nom</option>
                        <option value="matches">Par matches recensés</option>
                        <option value="selection">Par numéro de sélection</option>
                    </select>
                </label>
            </div>
            @if($filtered)
                <div class="mt-3 text-right">
                    <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-bleu-france hover:underline focus-visible:outline-hidden focus-visible:underline">Effacer les filtres</button>
                </div>
            @endif
        </div>
    </section>

    {{-- ═══ Annuaire ═══ --}}
    <section id="liste" class="scroll-mt-4 py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8" wire:loading.class="opacity-60" wire:target="search,team,country,position,order,gotoPage,nextPage,previousPage">
            @if($players->isEmpty())
                <div class="rounded-2xl border border-dashed border-filet p-10 text-center">
                    <p class="font-display text-2xl font-bold uppercase text-encre">Aucun joueur</p>
                    <p class="mt-1 font-serif text-texte-2">Aucun joueur ne correspond à ces critères.</p>
                </div>
            @else
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($players as $player)
                        @php
                            $letter = mb_strtoupper(\Illuminate\Support\Str::ascii(mb_substr($player->last_name, 0, 1)));
                            $isFrench = $player->country?->code === 'FRA';
                        @endphp
                        @if($byLetter && $letter !== $currentLetter)
                            @php $currentLetter = $letter; @endphp
                            <li class="col-span-full {{ $loop->first ? '' : 'mt-4' }} flex items-center gap-4" aria-hidden="true">
                                <span class="font-display text-3xl font-bold text-encre">{{ $letter }}</span>
                                <span class="h-px flex-1 bg-filet"></span>
                            </li>
                        @endif
                        <li wire:key="p-{{ $player->id }}">
                            <a href="{{ route('players.show', $player) }}" class="group flex h-full items-center gap-4 rounded-2xl border border-filet bg-white p-4 hover:border-encre/30 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold {{ $isFrench ? 'bg-bleu-france text-white' : 'bg-papier-2 text-encre ring-1 ring-inset ring-filet' }}">
                                    {{ $player->primary_position?->shortLabel() ?? mb_substr($player->last_name, 0, 1) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm text-texte-2">{{ $player->first_name }}</span>
                                    <span class="block truncate font-display text-xl font-bold uppercase leading-tight text-encre group-hover:underline">{{ $player->last_name }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-texte-2">
                                        @unless($isFrench)<span aria-hidden="true">{{ $player->country?->flag_emoji }}</span> {{ $player->country?->name }} · @endunless{{ $player->primary_position?->label() }}
                                    </span>
                                </span>
                                <span class="shrink-0 text-right">
                                    @if($player->lineups_count)
                                        <span class="block font-display text-2xl font-bold leading-none tabular-nums text-encre">{{ $player->lineups_count }}</span>
                                        <span class="text-[11px] text-texte-2">match{{ $player->lineups_count > 1 ? 'es' : '' }}</span>
                                    @endif
                                    @if($player->cap_number)
                                        <span class="mt-1 block text-[11px] text-or-2">n° {{ $player->cap_number }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-8">{{ $players->links() }}</div>
            @endif
        </div>
    </section>
</div>
