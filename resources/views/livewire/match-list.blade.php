@section('title', 'Tous les matches du XV de France')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <span class="text-gray-700">Matches</span>
@endsection

<div>
    {{-- En-tête --}}
    <section class="bg-bleu-france text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight">Tous les matches</h1>
            <p class="mt-2 text-blue-200">{{ number_format($totalCount, 0, ',', ' ') }} matches depuis 1906</p>
        </div>
    </section>

    {{-- Filtres --}}
    <section class="bg-gray-50 border-b border-gray-200 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher un adversaire..."
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-bleu-france focus:ring-bleu-france">
                </div>
                <div>
                    <select wire:model.live="competition"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-bleu-france focus:ring-bleu-france">
                        <option value="">Toutes les compétitions</option>
                        @foreach($competitions as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->short_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select wire:model.live="result"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-bleu-france focus:ring-bleu-france">
                        <option value="">Tous les résultats</option>
                        <option value="victoire">Victoires</option>
                        <option value="defaite">Défaites</option>
                        <option value="nul">Nuls</option>
                    </select>
                </div>
                <div>
                    <select wire:model.live="decade"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-bleu-france focus:ring-bleu-france">
                        <option value="">Toutes les décennies</option>
                        @for($d = 2020; $d >= 1900; $d -= 10)
                            <option value="{{ $d }}">{{ $d }}s</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <select wire:model.live="location"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-bleu-france focus:ring-bleu-france">
                        <option value="">Domicile & Extérieur</option>
                        <option value="domicile">Domicile</option>
                        <option value="exterieur">Extérieur</option>
                    </select>
                </div>
                <div>
                    <button wire:click="$set('search', ''); $set('competition', ''); $set('result', ''); $set('decade', ''); $set('location', '');"
                            class="w-full rounded-lg bg-gray-200 text-gray-700 text-sm py-2 px-4 hover:bg-gray-300 transition">
                        Réinitialiser
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Tableau des matches --}}
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Desktop: tableau --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-200 text-left text-gray-500">
                            <th class="pb-3 cursor-pointer hover:text-bleu-france" wire:click="sort('match_date')">
                                Date
                                @if($sortField === 'match_date')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="pb-3">Match</th>
                            <th class="pb-3 text-center cursor-pointer hover:text-bleu-france" wire:click="sort('france_score')">
                                Score
                                @if($sortField === 'france_score')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="pb-3 text-center">Résultat</th>
                            <th class="pb-3">Compétition</th>
                            <th class="pb-3">Stade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($matches as $match)
                            <tr class="hover:bg-gray-50 cursor-pointer transition" onclick="window.location='{{ route('matches.show', $match) }}'">
                                <td class="py-3 text-gray-500 whitespace-nowrap">{{ $match->match_date->format('d/m/Y') }}</td>
                                <td class="py-3">
                                    @if($match->is_home)
                                        <span class="font-medium">France</span> - <x-country-flag :country="$match->opponent" />
                                    @else
                                        <x-country-flag :country="$match->opponent" /> - <span class="font-medium">France</span>
                                    @endif
                                </td>
                                <td class="py-3 text-center font-mono font-bold">
                                    {{ $match->home_score }} - {{ $match->away_score }}
                                </td>
                                <td class="py-3 text-center">
                                    <x-result-badge :result="$match->result" />
                                </td>
                                <td class="py-3 text-gray-500">
                                    {{ $match->edition?->competition?->short_name ?? '—' }}
                                </td>
                                <td class="py-3 text-gray-500">
                                    {{ $match->venue?->name ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">Aucun match trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile: cartes empilées --}}
            <div class="md:hidden space-y-3">
                @forelse($matches as $match)
                    <x-match-score-card :match="$match" />
                @empty
                    <p class="py-8 text-center text-gray-400">Aucun match trouvé.</p>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $matches->links() }}
            </div>
        </div>
    </section>
</div>
