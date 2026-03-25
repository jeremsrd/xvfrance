@extends('layouts.app')

@section('title', $competitionEdition->label . ' — XV de France')

@section('breadcrumb')
    <span class="text-gray-300 mx-1">/</span>
    <a href="{{ route('competitions.index') }}" class="hover:text-bleu-france">Compétitions</a>
    <span class="text-gray-300 mx-1">/</span>
    <a href="{{ route('competitions.show', $competitionEdition->competition) }}" class="hover:text-bleu-france">{{ $competitionEdition->competition->name }}</a>
    <span class="text-gray-300 mx-1">/</span>
    <span class="text-gray-700">{{ $competitionEdition->year }}</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ $competitionEdition->label }}</h1>
        @if($competitionEdition->france_ranking)
            <p class="mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $competitionEdition->france_ranking === 1 ? 'bg-or/20 text-or' : 'bg-gray-100 text-gray-700' }}">
                    France : {{ $competitionEdition->france_ranking }}{{ $competitionEdition->france_ranking === 1 ? 'er' : 'e' }}
                </span>
            </p>
        @endif
    </div>

    {{-- Bilan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <x-stat-card :value="$matches->count()" label="Matches" />
        <x-stat-card :value="$wins" label="Victoires" color="green" />
        <x-stat-card :value="$losses" label="Défaites" color="red" />
        <x-stat-card :value="$draws" label="Nuls" color="yellow" />
    </div>

    {{-- Liste des matches --}}
    <div class="space-y-3">
        @forelse($matches as $match)
            <x-match-score-card :match="$match" />
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500">
                Aucun match enregistré pour cette édition.
            </div>
        @endforelse
    </div>
</div>
@endsection
