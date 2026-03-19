@extends('layouts.app')

@section('title', 'XV de France — L\'histoire complète depuis 1906')

@section('content')

    {{-- Hero --}}
    <section class="bg-bleu-france text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-5xl sm:text-6xl font-bold tracking-tight">XV de France</h1>
            <p class="mt-4 text-xl text-blue-200">L'histoire complète depuis 1906</p>
            <div class="mt-8 flex flex-wrap justify-center gap-6 text-sm sm:text-base">
                <div>
                    <span class="text-3xl font-bold block">{{ number_format($stats['total'], 0, ',', ' ') }}</span>
                    <span class="text-blue-200">matches</span>
                </div>
                <div class="w-px bg-blue-400 hidden sm:block"></div>
                <div>
                    <span class="text-3xl font-bold block">{{ $stats['victories'] }}</span>
                    <span class="text-blue-200">victoires</span>
                </div>
                <div class="w-px bg-blue-400 hidden sm:block"></div>
                <div>
                    <span class="text-3xl font-bold block">{{ $stats['win_pct'] }}%</span>
                    <span class="text-blue-200">de réussite</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Dernier match --}}
    @if($latestMatch)
    <section class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Dernier match</h2>
            <x-match-score-card :match="$latestMatch" :featured="true" />
        </div>
    </section>
    @endif

    {{-- Derniers résultats --}}
    @if($recentMatches->isNotEmpty())
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Derniers résultats</h2>
                <a href="{{ route('matches.index') }}" class="text-bleu-france hover:text-bleu-france-light text-sm font-medium transition">
                    Voir tous les matches &rarr;
                </a>
            </div>

            <div class="space-y-3">
                @foreach($recentMatches as $match)
                    <x-match-score-card :match="$match" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Statistiques globales --}}
    <section class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Statistiques</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card
                    :value="number_format($stats['total'], 0, ',', ' ')"
                    label="Matches joués"
                    subtitle="Depuis le 1er janvier 1906"
                />
                <x-stat-card
                    :value="$stats['victories'] . ' (' . $stats['win_pct'] . '%)'"
                    label="Victoires"
                    color="green"
                />
                @if($biggestWin)
                <x-stat-card
                    :value="$biggestWin->france_score . ' - ' . $biggestWin->opponent_score"
                    label="Plus gros score"
                    :subtitle="'vs ' . $biggestWin->opponent->name . ' (' . $biggestWin->match_date->format('Y') . ')'"
                />
                @endif
                @if($mostFaced)
                <x-stat-card
                    :value="$mostFaced->flag_emoji . ' ' . $mostFaced->name"
                    label="Adversaire le plus affronté"
                    :subtitle="$mostFaced->matches_as_opponent_count . ' matches'"
                />
                @endif
            </div>
        </div>
    </section>

@endsection
