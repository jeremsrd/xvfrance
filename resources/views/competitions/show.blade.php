@extends('layouts.app')

@section('title', $competition->name . ' — XV de France')

@section('breadcrumb')
    <span class="text-gray-300 mx-1">/</span>
    <a href="{{ route('competitions.index') }}" class="hover:text-bleu-france">Comp&eacute;titions</a>
    <span class="text-gray-300 mx-1">/</span>
    <span class="text-gray-700">{{ $competition->name }}</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-gray-900">{{ $competition->name }}</h1>
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
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium {{ $typeClass }}">
                    {{ $competition->type->label() }}
                </span>
            @endif
        </div>
        <p class="mt-1 text-gray-500">{{ $editions->count() }} &eacute;dition{{ $editions->count() > 1 ? 's' : '' }}</p>
    </div>

    @if($editions->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <p class="text-gray-500">Aucune &eacute;dition enregistr&eacute;e.</p>
        </div>
    @else
        {{-- Desktop --}}
        <div class="hidden md:block bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">&Eacute;dition</th>
                        <th class="px-4 py-3 text-center">Ann&eacute;e</th>
                        <th class="px-4 py-3 text-center">Classement</th>
                        <th class="px-4 py-3 text-center">Matches</th>
                        <th class="px-4 py-3 text-center">Bilan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($editions as $edition)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('editions.show', $edition) }}" class="text-bleu-france font-medium hover:underline">
                                    {{ $edition->label }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">{{ $edition->year }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($edition->france_ranking)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $edition->france_ranking === 1 ? 'bg-or/20 text-or' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $edition->france_ranking }}{{ $edition->france_ranking === 1 ? 'er' : 'e' }}
                                    </span>
                                @else
                                    <span class="text-gray-300">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $edition->matches_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-victoire font-medium">{{ $edition->wins }}V</span>
                                <span class="text-gray-400 mx-0.5">-</span>
                                <span class="text-defaite font-medium">{{ $edition->losses }}D</span>
                                <span class="text-gray-400 mx-0.5">-</span>
                                <span class="text-nul font-medium">{{ $edition->draws }}N</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden space-y-3">
            @foreach($editions as $edition)
                <a href="{{ route('editions.show', $edition) }}"
                   class="block bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-bold text-gray-900">{{ $edition->label }}</div>
                            <div class="text-sm text-gray-500 mt-1">{{ $edition->matches_count }} match{{ $edition->matches_count > 1 ? 'es' : '' }}</div>
                        </div>
                        @if($edition->france_ranking)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $edition->france_ranking === 1 ? 'bg-or/20 text-or' : 'bg-gray-100 text-gray-700' }}">
                                {{ $edition->france_ranking }}{{ $edition->france_ranking === 1 ? 'er' : 'e' }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 text-sm">
                        <span class="text-victoire font-medium">{{ $edition->wins }}V</span>
                        <span class="text-gray-400 mx-0.5">-</span>
                        <span class="text-defaite font-medium">{{ $edition->losses }}D</span>
                        <span class="text-gray-400 mx-0.5">-</span>
                        <span class="text-nul font-medium">{{ $edition->draws }}N</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
