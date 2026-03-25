@extends('layouts.app')

@section('title', $coach->fullName() . ' — XV de France')

@section('breadcrumb')
    <span class="text-gray-300 mx-1">/</span>
    <a href="{{ route('coaches.index') }}" class="hover:text-bleu-france">S&eacute;lectionneurs</a>
    <span class="text-gray-300 mx-1">/</span>
    <span class="text-gray-700">{{ $coach->fullName() }}</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- En-tête --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 md:p-8 mb-8">
        <div class="flex flex-col md:flex-row items-start gap-6">
            {{-- Photo --}}
            <div class="flex-shrink-0">
                @if($coach->photo_url)
                    <img src="{{ $coach->photo_url }}" alt="{{ $coach->fullName() }}"
                         class="w-28 h-28 rounded-full object-cover border-4 border-bleu-france/20">
                @else
                    <div class="w-28 h-28 rounded-full bg-bleu-france flex items-center justify-center text-white font-bold text-3xl border-4 border-bleu-france/20">
                        {{ mb_substr($coach->first_name, 0, 1) }}{{ mb_substr($coach->last_name, 0, 1) }}
                    </div>
                @endif
            </div>

            {{-- Infos --}}
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900">{{ $coach->fullName() }}</h1>

                <div class="flex flex-wrap items-center gap-3 mt-3">
                    @if($coach->country)
                        <x-country-flag :country="$coach->country" />
                    @endif
                </div>

                <div class="mt-4 text-sm text-gray-600 space-y-1">
                    @if($coach->birth_date)
                        <div><span class="text-gray-400">N&eacute; le</span> {{ $coach->birth_date->format('d/m/Y') }}</div>
                    @endif
                    @if($coach->birth_city)
                        <div><span class="text-gray-400">&Agrave;</span> {{ $coach->birth_city }}</div>
                    @endif
                </div>

                {{-- R&ocirc;les --}}
                <div class="mt-4 space-y-1">
                    @foreach($coach->tenures as $tenure)
                        <div class="flex items-center gap-2 text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tenure->role === \App\Enums\CoachRole::SELECTIONNEUR ? 'bg-bleu-france text-white' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $tenure->role->value)) }}
                            </span>
                            <span class="text-gray-500">
                                {{ $tenure->start_date->format('d/m/Y') }}
                                &rarr;
                                {{ $tenure->end_date ? $tenure->end_date->format('d/m/Y') : 'en cours' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if($selectorTenure)
        {{-- Bilan --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <x-stat-card :value="$matches->count()" label="Matches" />
            <x-stat-card :value="$wins" label="Victoires" color="green" />
            <x-stat-card :value="$losses" label="D&eacute;faites" color="red" />
            <x-stat-card :value="$draws" label="Nuls" color="yellow" />
        </div>

        {{-- % de victoires --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-8">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Taux de victoires</span>
                <span class="text-sm font-bold {{ $winPct >= 50 ? 'text-victoire' : 'text-gray-700' }}">{{ $winPct }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full {{ $winPct >= 50 ? 'bg-victoire' : 'bg-defaite' }}" style="width: {{ $winPct }}%"></div>
            </div>
        </div>

        {{-- Liste des matches --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Matches sous son mandat</h2>
            </div>

            @if($matches->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    Aucun match enregistr&eacute; pour cette p&eacute;riode.
                </div>
            @else
                {{-- Desktop --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Match</th>
                                <th class="px-4 py-3 text-center">Score</th>
                                <th class="px-4 py-3 text-center">R&eacute;sultat</th>
                                <th class="px-4 py-3 text-left">Comp&eacute;tition</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($matches as $match)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('matches.show', $match) }}" class="text-bleu-france hover:underline">
                                            {{ $match->match_date->format('d/m/Y') }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($match->is_home)
                                            <span class="font-medium">France</span> - <x-country-flag :country="$match->opponent" class="text-sm" />
                                        @else
                                            <x-country-flag :country="$match->opponent" class="text-sm" /> - <span class="font-medium">France</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-bold">
                                        {{ $match->home_score }} - {{ $match->away_score }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <x-result-badge :result="$match->result" />
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $match->edition?->competition?->short_name ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile --}}
                <div class="md:hidden divide-y divide-gray-100">
                    @foreach($matches as $match)
                        <a href="{{ route('matches.show', $match) }}" class="block p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-xs text-gray-500">{{ $match->match_date->format('d/m/Y') }}</div>
                                    <div class="font-bold mt-0.5">
                                        {{ $match->home_team_name }} {{ $match->home_score }} - {{ $match->away_score }} {{ $match->away_team_name }}
                                    </div>
                                </div>
                                <x-result-badge :result="$match->result" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500">
            Aucun mandat de s&eacute;lectionneur enregistr&eacute;.
        </div>
    @endif
</div>
@endsection
