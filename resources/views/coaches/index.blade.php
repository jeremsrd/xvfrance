@extends('layouts.app')

@section('title', 'Sélectionneurs — XV de France')

@section('breadcrumb')
    <span class="text-gray-300 mx-1">/</span>
    <span class="text-gray-700">S&eacute;lectionneurs</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">S&eacute;lectionneurs</h1>
        <p class="mt-1 text-gray-500">Les hommes &agrave; la t&ecirc;te du XV de France</p>
    </div>

    @if($coaches->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <div class="text-gray-400 text-5xl mb-4">&#127944;</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Donn&eacute;es &agrave; venir</h3>
            <p class="text-gray-500">Les fiches des s&eacute;lectionneurs sont en cours de saisie.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($coaches as $coach)
                <a href="{{ route('coaches.show', $coach) }}"
                   class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        {{-- Photo --}}
                        <div class="flex-shrink-0">
                            @if($coach->photo_url)
                                <img src="{{ $coach->photo_url }}" alt="{{ $coach->fullName() }}"
                                     class="w-16 h-16 rounded-full object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-bleu-france flex items-center justify-center text-white font-bold text-lg">
                                    {{ mb_substr($coach->first_name, 0, 1) }}{{ mb_substr($coach->last_name, 0, 1) }}
                                </div>
                            @endif
                        </div>

                        {{-- Infos --}}
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-gray-900">{{ $coach->fullName() }}</h2>
                            <div class="text-sm text-gray-500 mt-1">
                                @if($coach->tenure->end_date)
                                    De {{ $coach->tenure->start_date->format('Y') }} &agrave; {{ $coach->tenure->end_date->format('Y') }}
                                @else
                                    Depuis {{ $coach->tenure->start_date->format('Y') }}
                                @endif
                            </div>
                        </div>

                        {{-- Bilan --}}
                        <div class="flex items-center gap-6 text-sm">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $coach->total_matches }}</div>
                                <div class="text-xs text-gray-500">Matches</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold">
                                    <span class="text-victoire">{{ $coach->wins }}V</span>
                                    <span class="text-gray-400">-</span>
                                    <span class="text-defaite">{{ $coach->losses }}D</span>
                                    <span class="text-gray-400">-</span>
                                    <span class="text-nul">{{ $coach->draws }}N</span>
                                </div>
                                <div class="text-xs text-gray-500">Bilan</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold {{ $coach->win_pct >= 50 ? 'text-victoire' : 'text-gray-900' }}">{{ $coach->win_pct }}%</div>
                                <div class="text-xs text-gray-500">Victoires</div>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
