<div>
    @section('title', 'Joueurs — XV de France')

    @section('breadcrumb')
        <span class="text-gray-300 mx-1">/</span>
        <span class="text-gray-700">Joueurs</span>
    @endsection

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- En-tête --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Joueurs</h1>
            <p class="mt-1 text-gray-500">{{ $totalCount }} joueur{{ $totalCount > 1 ? 's' : '' }} r&eacute;f&eacute;renc&eacute;{{ $totalCount > 1 ? 's' : '' }}</p>
        </div>

        {{-- Filtres --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                {{-- Recherche --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Recherche</label>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Nom du joueur..."
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-bleu-france focus:ring-bleu-france px-3 py-2 border">
                </div>

                {{-- Nationalit&eacute; --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nationalit&eacute;</label>
                    <select wire:model.live="country"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-bleu-france focus:ring-bleu-france px-3 py-2 border">
                        <option value="">Toutes</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->flag_emoji }} {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Poste --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Poste</label>
                    <select wire:model.live="position"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-bleu-france focus:ring-bleu-france px-3 py-2 border">
                        <option value="">Tous</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Statut --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
                    <select wire:model.live="status"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-bleu-france focus:ring-bleu-france px-3 py-2 border">
                        <option value="">Tous</option>
                        <option value="actif">En activit&eacute;</option>
                        <option value="retraite">Retrait&eacute;</option>
                    </select>
                </div>

                {{-- Tri --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Trier par</label>
                    <div class="flex gap-1">
                        <button wire:click="sort('last_name')"
                                class="flex-1 px-2 py-2 text-xs rounded border {{ $sortField === 'last_name' ? 'bg-bleu-france text-white border-bleu-france' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            Nom {{ $sortField === 'last_name' ? ($sortDirection === 'asc' ? '&uarr;' : '&darr;') : '' }}
                        </button>
                        <button wire:click="sort('selections')"
                                class="flex-1 px-2 py-2 text-xs rounded border {{ $sortField === 'selections' ? 'bg-bleu-france text-white border-bleu-france' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            S&eacute;l. {{ $sortField === 'selections' ? ($sortDirection === 'asc' ? '&uarr;' : '&darr;') : '' }}
                        </button>
                        <button wire:click="sort('cap_number')"
                                class="flex-1 px-2 py-2 text-xs rounded border {{ $sortField === 'cap_number' ? 'bg-bleu-france text-white border-bleu-france' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            Cap {{ $sortField === 'cap_number' ? ($sortDirection === 'asc' ? '&uarr;' : '&darr;') : '' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grille de joueurs --}}
        @if($players->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <div class="text-gray-400 text-5xl mb-4">&#127944;</div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucun joueur trouv&eacute;</h3>
                <p class="text-gray-500">
                    @if($search || $country || $position || $status)
                        Aucun joueur ne correspond &agrave; vos crit&egrave;res de recherche.
                    @else
                        Les donn&eacute;es arrivent bient&ocirc;t &mdash; les fiches joueurs sont en cours de saisie.
                    @endif
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($players as $player)
                    <a href="{{ route('players.show', $player) }}"
                       class="block bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                        <div class="flex items-center gap-4">
                            {{-- Photo --}}
                            <div class="flex-shrink-0">
                                @if($player->photo_path)
                                    <img src="{{ $player->photo_path }}" alt="{{ $player->fullName() }}"
                                         class="w-14 h-14 rounded-full object-cover">
                                @else
                                    <div class="w-14 h-14 rounded-full bg-bleu-france flex items-center justify-center text-white font-bold text-lg">
                                        {{ mb_substr($player->first_name, 0, 1) }}{{ mb_substr($player->last_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            {{-- Infos --}}
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-900 truncate">
                                    {{ $player->fullName() }}
                                    @if($player->isDeceased())
                                        <span class="text-gray-400 font-normal">&dagger;</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 flex items-center gap-2 mt-0.5">
                                    @if($player->country)
                                        <x-country-flag :country="$player->country" class="text-sm" />
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    @if($player->primary_position)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-bleu-france/10 text-bleu-france">
                                            {{ $player->primary_position->label() }}
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-500">{{ $player->lineups_count }} s&eacute;l.</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $players->links() }}
            </div>
        @endif
    </div>
</div>
