@extends('layouts.app')

@section('title', 'France vs ' . $country->name . ' — Bilan complet')

@section('meta_description', 'Bilan complet France vs ' . $country->name . ' : ' . $stats['total'] . ' matches, ' . $stats['victories'] . ' victoires, ' . $stats['defeats'] . ' défaites.')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <a href="{{ route('opponents.index') }}" class="hover:text-bleu-france">Adversaires</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700">{{ $country->name }}</span>
@endsection

@section('content')

    {{-- En-tête --}}
    <section class="bg-bleu-france text-white py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-5xl mb-4">{{ $country->flag_emoji }}</div>
            <h1 class="text-3xl font-bold tracking-tight">France vs {{ $country->name }}</h1>
            <p class="mt-3 text-blue-200">
                {{ $stats['total'] }} matches — {{ $stats['victories'] }} victoires — {{ $stats['defeats'] }} défaites — {{ $stats['draws'] }} nuls
            </p>
        </div>
    </section>

    {{-- Stats --}}
    <section class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card :value="$stats['victories']" label="Victoires" color="green" />
                <x-stat-card :value="$stats['defeats']" label="Défaites" color="red" />
                <x-stat-card :value="$stats['draws']" label="Nuls" color="yellow" />
                <x-stat-card :value="$stats['win_pct'] . '%'" label="Victoires" />
            </div>

            {{-- Barre de progression --}}
            @if($stats['total'] > 0)
            <div class="mt-6 h-4 rounded-full bg-gray-100 overflow-hidden flex">
                <div class="bg-green-500 h-full" style="width: {{ ($stats['victories'] / $stats['total']) * 100 }}%"></div>
                <div class="bg-yellow-400 h-full" style="width: {{ ($stats['draws'] / $stats['total']) * 100 }}%"></div>
                <div class="bg-red-500 h-full" style="width: {{ ($stats['defeats'] / $stats['total']) * 100 }}%"></div>
            </div>
            @endif

            {{-- Records --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                @if($biggestWin)
                <div class="bg-green-50 rounded-lg border border-green-200 p-5">
                    <div class="text-xs font-semibold text-green-600 uppercase mb-1">Plus grosse victoire</div>
                    <div class="text-xl font-bold">France {{ $biggestWin->france_score }} - {{ $biggestWin->opponent_score }} {{ $country->name }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ $biggestWin->match_date->format('d/m/Y') }}</div>
                </div>
                @endif
                @if($biggestLoss)
                <div class="bg-red-50 rounded-lg border border-red-200 p-5">
                    <div class="text-xs font-semibold text-red-600 uppercase mb-1">Plus grosse défaite</div>
                    <div class="text-xl font-bold">France {{ $biggestLoss->france_score }} - {{ $biggestLoss->opponent_score }} {{ $country->name }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ $biggestLoss->match_date->format('d/m/Y') }}</div>
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Liste des matches --}}
    <section class="bg-gray-50 py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Tous les matches ({{ $matches->count() }})</h2>
            <div class="space-y-3">
                @foreach($matches as $match)
                    <x-match-score-card :match="$match" />
                @endforeach
            </div>
        </div>
    </section>

@endsection
