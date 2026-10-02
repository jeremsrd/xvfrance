@extends('layouts.app')

@php
    use App\Support\FrenchDate;
    $t = $selectorTenure;
    $share = fn ($n) => $record->total ? round($n / $record->total * 100, 2) : 0;
@endphp

@section('title', $coach->fullName() . ' — Sélectionneur du XV de France')

@section('meta_description', $coach->fullName() . ', sélectionneur du XV de France' . ($t ? ' depuis ' . $t->start_date->year : '') . ' : ' . $record->total . ' matches, ' . $record->wins . ' victoires.')

@section('breadcrumb')
    <x-breadcrumb :items="['Sélectionneurs' => route('coaches.index'), $coach->fullName() => null]" />
@endsection

@section('content')
    <section class="bg-encre text-white">
        <div class="mx-auto max-w-6xl px-4 pb-12 pt-6 sm:px-6 lg:px-8 lg:pb-16 lg:pt-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Sélectionneur du XV de France</p>
            <h1 class="mt-3 leading-none">
                <span class="block font-serif text-2xl font-normal normal-case italic tracking-normal text-blue-100 sm:text-3xl">{{ $coach->first_name }}</span>
                <span class="mt-1 block font-display text-5xl font-bold uppercase tracking-tight sm:text-7xl">{{ $coach->last_name }}</span>
            </h1>
            @if($t)
                <p class="mt-4 font-serif text-lg text-blue-100">
                    Du {{ FrenchDate::long($t->start_date) }} {{ $t->end_date ? 'au ' . FrenchDate::long($t->end_date) : 'à aujourd\'hui' }}
                    @if($coach->country && $coach->country->code !== 'FRA') · {{ $coach->country->name }}@endif
                </p>
            @endif

            @if($record->total)
                <div class="mt-8 flex flex-wrap items-end gap-x-10 gap-y-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-300">Bilan</div>
                        <div class="mt-1 font-display text-4xl font-bold tabular-nums">{{ $record->total }} <span class="text-xl font-semibold text-blue-200">matches</span></div>
                    </div>
                    <div class="min-w-64 flex-1 sm:max-w-md">
                        <div class="flex justify-between text-sm tabular-nums">
                            <span><b class="text-emerald-300">{{ $record->wins }}</b> V · <b class="text-amber-200">{{ $record->draws }}</b> N · <b class="text-red-300">{{ $record->losses }}</b> D</span>
                            <span class="text-blue-100">{{ $record->winPctLabel() }}</span>
                        </div>
                        <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                            <span class="bg-emerald-400" style="width: {{ $share($record->wins) }}%"></span>
                            <span class="bg-amber-300" style="width: {{ $share($record->draws) }}%"></span>
                            <span class="bg-rouge-france" style="width: {{ $share($record->losses) }}%"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,8fr)_minmax(0,4fr)] lg:px-8 lg:py-16">
        <section aria-labelledby="matches">
            <h2 id="matches" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Ses matches</h2>
            @if($matches->isEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-filet p-6 font-serif text-texte-2">Aucun match sur cette période.</p>
            @else
                <ol class="mt-6 divide-y divide-filet overflow-hidden rounded-2xl border border-filet bg-white">
                    @foreach($matches as $match)
                        <li><x-match.row :match="$match" /></li>
                    @endforeach
                </ol>
            @endif
        </section>

        @if($coach->tenures->count() > 0)
            <aside>
                <div class="rounded-2xl border border-filet bg-white p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Fonctions</h2>
                    <ul class="mt-3 divide-y divide-filet text-sm">
                        @foreach($coach->tenures as $tenure)
                            <li class="py-2">
                                <span class="block font-medium text-encre">{{ $tenure->role->label() }}</span>
                                <span class="text-texte-2 tabular-nums">{{ $tenure->start_date->year }} – {{ $tenure->end_date?->year ?? 'aujourd\'hui' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif
    </div>
@endsection
