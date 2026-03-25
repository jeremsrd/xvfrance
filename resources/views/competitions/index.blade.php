@extends('layouts.app')

@section('title', 'Compétitions — XV de France')

@section('breadcrumb')
    <span class="text-gray-300 mx-1">/</span>
    <span class="text-gray-700">Comp&eacute;titions</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Comp&eacute;titions</h1>
        <p class="mt-1 text-gray-500">{{ $competitions->count() }} comp&eacute;tition{{ $competitions->count() > 1 ? 's' : '' }}</p>
    </div>

    @if($competitions->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <p class="text-gray-500">Aucune comp&eacute;tition enregistr&eacute;e.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($competitions as $competition)
                <a href="{{ route('competitions.show', $competition) }}"
                   class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-3">
                        <h2 class="text-lg font-bold text-gray-900">{{ $competition->name }}</h2>
                        @if($competition->type)
                            @php
                                $typeClass = match($competition->type->value) {
                                    'tournoi' => 'bg-blue-100 text-blue-800',
                                    'coupe_du_monde' => 'bg-or/20 text-or',
                                    'test_match' => 'bg-gray-100 text-gray-800',
                                    'tournee' => 'bg-green-100 text-green-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeClass }}">
                                {{ $competition->type->label() }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-6 text-sm text-gray-500">
                        <div>
                            <span class="text-2xl font-bold text-gray-900">{{ $competition->editions_count }}</span>
                            <span class="ml-1">&eacute;dition{{ $competition->editions_count > 1 ? 's' : '' }}</span>
                        </div>
                        <div>
                            <span class="text-2xl font-bold text-gray-900">{{ $competition->total_matches }}</span>
                            <span class="ml-1">match{{ $competition->total_matches > 1 ? 'es' : '' }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
