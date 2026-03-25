@extends('layouts.app')

@section('title', $player->fullName() . ' — XV de France')

@section('breadcrumb')
    <span class="text-gray-300 mx-1">/</span>
    <a href="{{ route('players.index') }}" class="hover:text-bleu-france">Joueurs</a>
    <span class="text-gray-300 mx-1">/</span>
    <span class="text-gray-700">{{ $player->fullName() }}</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- En-tête joueur --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 md:p-8 mb-8">
        <div class="flex flex-col md:flex-row items-start gap-6">
            {{-- Photo --}}
            <div class="flex-shrink-0">
                @if($player->photo_path)
                    <img src="{{ $player->photo_path }}" alt="{{ $player->fullName() }}"
                         class="w-28 h-28 rounded-full object-cover border-4 border-bleu-france/20">
                @else
                    <div class="w-28 h-28 rounded-full bg-bleu-france flex items-center justify-center text-white font-bold text-3xl border-4 border-bleu-france/20">
                        {{ mb_substr($player->first_name, 0, 1) }}{{ mb_substr($player->last_name, 0, 1) }}
                    </div>
                @endif
            </div>

            {{-- Infos --}}
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $player->fullName() }}
                    @if($player->isDeceased())
                        <span class="text-gray-400 text-xl">&dagger;</span>
                    @endif
                </h1>

                @if($player->nickname)
                    <p class="text-lg text-gray-500 italic">&laquo; {{ $player->nickname }} &raquo;</p>
                @endif

                <div class="flex flex-wrap items-center gap-3 mt-3">
                    @if($player->country)
                        <x-country-flag :country="$player->country" />
                    @endif

                    @if($player->primary_position)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-bleu-france text-white">
                            {{ $player->primary_position->label() }}
                        </span>
                    @endif

                    @if($player->cap_number)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-or/20 text-or border border-or/30">
                            {{ $player->cap_number }}e international
                        </span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-2 text-sm text-gray-600">
                    @if($player->birth_date)
                        <div>
                            <span class="text-gray-400">N&eacute; le</span>
                            {{ $player->birth_date->format('d/m/Y') }}
                            @if(!$player->isDeceased())
                                <span class="text-gray-400">({{ $player->birth_date->age }} ans)</span>
                            @endif
                        </div>
                    @endif
                    @if($player->isDeceased())
                        <div>
                            <span class="text-gray-400">D&eacute;c&eacute;d&eacute; le</span>
                            {{ $player->death_date->format('d/m/Y') }}
                        </div>
                    @endif
                    @if($player->birth_city)
                        <div><span class="text-gray-400">&Agrave;</span> {{ $player->birth_city }}</div>
                    @endif
                    @if($player->height_cm)
                        <div><span class="text-gray-400">Taille</span> {{ number_format($player->height_cm / 100, 2, ',', '') }} m</div>
                    @endif
                    @if($player->weight_kg)
                        <div><span class="text-gray-400">Poids</span> {{ $player->weight_kg }} kg</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <x-stat-card :value="$totalCaps" label="S&eacute;lections" />
        <x-stat-card :value="$starts" label="Titularisations" color="green" />
        <x-stat-card :value="$tries" label="Essais" color="green" />
        <x-stat-card :value="$captaincies" label="Capitanats" color="yellow" />
        <x-stat-card :value="$points" label="Points" />
    </div>

    {{-- Matches jou&eacute;s --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Matches jou&eacute;s</h2>
        </div>

        @if($lineups->isEmpty())
            <div class="p-8 text-center text-gray-500">
                Aucune s&eacute;lection enregistr&eacute;e pour l'instant.
            </div>
        @else
            {{-- Desktop --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Adversaire</th>
                            <th class="px-4 py-3 text-center">Score</th>
                            <th class="px-4 py-3 text-center">R&eacute;sultat</th>
                            <th class="px-4 py-3 text-center">Poste</th>
                            <th class="px-4 py-3 text-center">Statut</th>
                            <th class="px-4 py-3 text-center">Cap.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($lineups as $lineup)
                            @php $match = $lineup->match; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('matches.show', $match) }}" class="text-bleu-france hover:underline">
                                        {{ $match->match_date->format('d/m/Y') }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <x-country-flag :country="$match->opponent" class="text-sm" />
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold">
                                    {{ $match->france_score }} - {{ $match->opponent_score }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <x-result-badge :result="$match->result" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($lineup->position_played)
                                        <span class="text-xs">{{ $lineup->position_played->shortLabel() }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $lineup->is_starter ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $lineup->is_starter ? 'Tit.' : 'Rempl.' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($lineup->is_captain)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-or/20 text-or">C</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="md:hidden divide-y divide-gray-100">
                @foreach($lineups as $lineup)
                    @php $match = $lineup->match; @endphp
                    <a href="{{ route('matches.show', $match) }}" class="block p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-xs text-gray-500">{{ $match->match_date->format('d/m/Y') }}</div>
                                <div class="font-bold mt-0.5">
                                    France {{ $match->france_score }} - {{ $match->opponent_score }}
                                    {{ $match->opponent->name }}
                                </div>
                            </div>
                            <x-result-badge :result="$match->result" />
                        </div>
                        <div class="flex gap-2 mt-1">
                            <span class="text-xs text-gray-500">{{ $lineup->is_starter ? 'Titulaire' : 'Remplaçant' }}</span>
                            @if($lineup->is_captain)
                                <span class="text-xs text-or font-medium">Capitaine</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Faits de jeu --}}
    @if($events->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Faits de jeu</h2>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Adversaire</th>
                            <th class="px-4 py-3 text-left">&Eacute;v&eacute;nement</th>
                            <th class="px-4 py-3 text-center">Minute</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($events as $event)
                            @php $match = $event->match; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('matches.show', $match) }}" class="text-bleu-france hover:underline">
                                        {{ $match->match_date->format('d/m/Y') }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <x-country-flag :country="$match->opponent" class="text-sm" />
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $eventClass = match(true) {
                                            in_array($event->event_type, [\App\Enums\EventType::ESSAI, \App\Enums\EventType::ESSAI_PENALITE]) => 'bg-green-100 text-green-800',
                                            $event->event_type === \App\Enums\EventType::CARTON_JAUNE => 'bg-yellow-100 text-yellow-800',
                                            $event->event_type === \App\Enums\EventType::CARTON_ROUGE => 'bg-red-100 text-red-800',
                                            default => 'bg-blue-100 text-blue-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $eventClass }}">
                                        {{ $event->event_type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $event->minute ? $event->minute . "'" : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden divide-y divide-gray-100">
                @foreach($events as $event)
                    @php $match = $event->match; @endphp
                    <div class="p-4">
                        <div class="flex justify-between items-center">
                            <div class="text-xs text-gray-500">{{ $match->match_date->format('d/m/Y') }} vs {{ $match->opponent->name }}</div>
                            <span class="text-xs text-gray-400">{{ $event->minute ? $event->minute . "'" : '' }}</span>
                        </div>
                        <div class="mt-1 font-medium">{{ $event->event_type->label() }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
