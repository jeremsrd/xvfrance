@extends('layouts.app')

@section('title', 'Adversaires du XV de France')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <span class="text-gray-700">Adversaires</span>
@endsection

@section('content')

    <section class="bg-bleu-france text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight">Adversaires</h1>
            <p class="mt-2 text-blue-200">{{ $opponents->count() }} nations affrontées depuis 1906</p>
        </div>
    </section>

    <section class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($opponents as $opponent)
                    @php
                        $winPct = $opponent->matches_as_opponent_count > 0
                            ? round(($opponent->victories / $opponent->matches_as_opponent_count) * 100)
                            : 0;
                    @endphp
                    <a href="{{ route('opponents.show', $opponent) }}"
                       class="block bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <x-country-flag :country="$opponent" class="text-lg font-semibold" />
                            <span class="text-sm text-gray-400">{{ $opponent->matches_as_opponent_count }} matches</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-green-600 font-semibold">{{ $opponent->victories }}V</span>
                            <span class="text-red-600 font-semibold">{{ $opponent->defeats }}D</span>
                            <span class="text-yellow-600 font-semibold">{{ $opponent->draws }}N</span>
                            <span class="ml-auto text-gray-500">{{ $winPct }}%</span>
                        </div>
                        {{-- Barre de progression --}}
                        <div class="mt-3 h-2 rounded-full bg-gray-100 overflow-hidden flex">
                            @if($opponent->matches_as_opponent_count > 0)
                                <div class="bg-green-500 h-full" style="width: {{ ($opponent->victories / $opponent->matches_as_opponent_count) * 100 }}%"></div>
                                <div class="bg-yellow-400 h-full" style="width: {{ ($opponent->draws / $opponent->matches_as_opponent_count) * 100 }}%"></div>
                                <div class="bg-red-500 h-full" style="width: {{ ($opponent->defeats / $opponent->matches_as_opponent_count) * 100 }}%"></div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

@endsection
