@extends('layouts.app')

@section('title', 'France ' . $rugbyMatch->france_score . ' - ' . $rugbyMatch->opponent_score . ' ' . $rugbyMatch->opponent->name . ' — ' . $rugbyMatch->match_date->format('d/m/Y'))

@section('meta_description', 'Fiche du match France ' . $rugbyMatch->france_score . ' - ' . $rugbyMatch->opponent_score . ' ' . $rugbyMatch->opponent->name . ' du ' . $rugbyMatch->match_date->format('d/m/Y'))

@section('breadcrumb')
    <span class="mx-2">/</span>
    <a href="{{ route('matches.index') }}" class="hover:text-bleu-france">Matches</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700">France vs {{ $rugbyMatch->opponent->name }}</span>
@endsection

@section('content')

    {{-- Bannière du match --}}
    <section class="bg-bleu-france text-white py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            {{-- Résultat badge --}}
            @php
                $badgeClass = match($rugbyMatch->result) {
                    'Victoire' => 'bg-green-500',
                    'Défaite' => 'bg-red-500',
                    'Nul' => 'bg-yellow-500',
                    default => 'bg-gray-500',
                };
            @endphp
            <span class="inline-block px-4 py-1 rounded-full text-sm font-bold {{ $badgeClass }} mb-6">
                {{ strtoupper($rugbyMatch->result) }}
            </span>

            {{-- Score --}}
            <div class="flex items-center justify-center gap-6 sm:gap-10">
                <div class="text-center">
                    <div class="text-4xl mb-2">🇫🇷</div>
                    <div class="text-lg font-semibold">France</div>
                </div>
                <div class="text-5xl sm:text-7xl font-bold font-mono tracking-tight">
                    {{ $rugbyMatch->france_score }} <span class="text-blue-300">—</span> {{ $rugbyMatch->opponent_score }}
                </div>
                <div class="text-center">
                    <div class="text-4xl mb-2">{{ $rugbyMatch->opponent->flag_emoji }}</div>
                    <div class="text-lg font-semibold">{{ $rugbyMatch->opponent->name }}</div>
                </div>
            </div>

            {{-- Infos --}}
            <div class="mt-6 space-y-1 text-blue-200 text-sm">
                <p class="text-base text-white">
                    @if($rugbyMatch->edition && $rugbyMatch->edition->competition)
                        {{ $rugbyMatch->edition->competition->name }}
                        @if($rugbyMatch->stage)
                            — {{ $rugbyMatch->stage->value }}
                        @endif
                    @endif
                </p>
                <p>{{ $rugbyMatch->match_date->translatedFormat('l j F Y') }}</p>
                @if($rugbyMatch->venue)
                    <p>{{ $rugbyMatch->venue->name }}, {{ $rugbyMatch->venue->city }}</p>
                @endif
                @if($rugbyMatch->attendance)
                    <p>{{ number_format($rugbyMatch->attendance, 0, ',', ' ') }} spectateurs</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Détails du match --}}
    <section class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Infos complémentaires --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Informations</h3>
                    <dl class="space-y-3 text-sm">
                        @if($rugbyMatch->referee)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Arbitre</dt>
                                <dd class="font-medium">
                                    {{ $rugbyMatch->referee }}
                                    @if($rugbyMatch->refereeCountry)
                                        <span class="text-gray-400">({{ $rugbyMatch->refereeCountry->flag_emoji }} {{ $rugbyMatch->refereeCountry->name }})</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if($rugbyMatch->match_number)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Test n°</dt>
                                <dd class="font-medium">{{ $rugbyMatch->match_number }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Lieu</dt>
                            <dd class="font-medium">
                                @if($rugbyMatch->is_home) Domicile
                                @elseif($rugbyMatch->is_neutral) Terrain neutre
                                @else Extérieur
                                @endif
                            </dd>
                        </div>
                        @if($rugbyMatch->weather)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Météo</dt>
                                <dd class="font-medium">{{ $rugbyMatch->weather }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Notes --}}
                @if($rugbyMatch->notes)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Notes</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $rugbyMatch->notes }}</p>
                </div>
                @endif
            </div>

            {{-- Compositions (si disponibles) --}}
            @php
                $franceLineups = $rugbyMatch->lineups->where('team_side', \App\Enums\TeamSide::FRANCE)->sortBy('jersey_number');
                $opponentLineups = $rugbyMatch->lineups->where('team_side', \App\Enums\TeamSide::ADVERSAIRE)->sortBy('jersey_number');
            @endphp

            @if($franceLineups->isNotEmpty() || $opponentLineups->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                {{-- France --}}
                @if($franceLineups->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">🇫🇷 XV de France</h3>
                    <div class="space-y-1 text-sm">
                        <p class="text-xs text-gray-400 font-semibold uppercase mb-2">Titulaires</p>
                        @foreach($franceLineups->where('is_starter', true) as $lineup)
                            <div class="flex items-center gap-2 py-1">
                                <span class="w-6 text-center font-mono text-gray-400">{{ $lineup->jersey_number }}</span>
                                <span class="font-medium">
                                    {{ $lineup->player->first_name }} {{ $lineup->player->last_name }}
                                    @if($lineup->is_captain) <span class="text-bleu-france font-bold">(C)</span> @endif
                                </span>
                            </div>
                        @endforeach
                        @if($franceLineups->where('is_starter', false)->isNotEmpty())
                            <p class="text-xs text-gray-400 font-semibold uppercase mt-4 mb-2">Remplaçants</p>
                            @foreach($franceLineups->where('is_starter', false) as $lineup)
                                <div class="flex items-center gap-2 py-1">
                                    <span class="w-6 text-center font-mono text-gray-400">{{ $lineup->jersey_number }}</span>
                                    <span>{{ $lineup->player->first_name }} {{ $lineup->player->last_name }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                @endif

                {{-- Adversaire --}}
                @if($opponentLineups->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ $rugbyMatch->opponent->flag_emoji }} {{ $rugbyMatch->opponent->name }}</h3>
                    <div class="space-y-1 text-sm">
                        <p class="text-xs text-gray-400 font-semibold uppercase mb-2">Titulaires</p>
                        @foreach($opponentLineups->where('is_starter', true) as $lineup)
                            <div class="flex items-center gap-2 py-1">
                                <span class="w-6 text-center font-mono text-gray-400">{{ $lineup->jersey_number }}</span>
                                <span class="font-medium">
                                    {{ $lineup->player->first_name }} {{ $lineup->player->last_name }}
                                    @if($lineup->is_captain) <span class="text-bleu-france font-bold">(C)</span> @endif
                                </span>
                            </div>
                        @endforeach
                        @if($opponentLineups->where('is_starter', false)->isNotEmpty())
                            <p class="text-xs text-gray-400 font-semibold uppercase mt-4 mb-2">Remplaçants</p>
                            @foreach($opponentLineups->where('is_starter', false) as $lineup)
                                <div class="flex items-center gap-2 py-1">
                                    <span class="w-6 text-center font-mono text-gray-400">{{ $lineup->jersey_number }}</span>
                                    <span>{{ $lineup->player->first_name }} {{ $lineup->player->last_name }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="mt-6 bg-gray-50 rounded-lg border border-gray-200 p-6 text-center text-gray-400 text-sm">
                Compositions à venir
            </div>
            @endif

            {{-- Événements (si disponibles) --}}
            @if($rugbyMatch->events->isNotEmpty())
            <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Faits de jeu</h3>
                <div class="space-y-2 text-sm">
                    @foreach($rugbyMatch->events->sortBy('minute') as $event)
                        @php
                            $icon = match($event->event_type->value) {
                                'essai' => '🏉',
                                'essai_penalite' => '🏉',
                                'transformation' => '🔄',
                                'penalite' => '🥾',
                                'drop' => '🦶',
                                'carton_jaune' => '🟨',
                                'carton_rouge' => '🟥',
                                default => '•',
                            };
                        @endphp
                        <div class="flex items-center gap-3 py-1 {{ $event->team_side->value === 'france' ? '' : 'text-gray-500' }}">
                            <span class="w-10 text-right font-mono text-gray-400">{{ $event->minute ? $event->minute . "'" : '' }}</span>
                            <span>{{ $icon }}</span>
                            <span class="font-medium">
                                {{ $event->player ? $event->player->first_name . ' ' . $event->player->last_name : 'Essai de pénalité' }}
                            </span>
                            <span class="text-gray-400">({{ ucfirst(str_replace('_', ' ', $event->event_type->value)) }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </section>

@endsection
