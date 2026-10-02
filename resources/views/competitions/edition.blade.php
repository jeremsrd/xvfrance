@extends('layouts.app')

@php
    $e = $competitionEdition;
    $c = $e->competition;
    $share = fn ($n) => $record->total ? round($n / $record->total * 100, 2) : 0;
@endphp

@section('title', $c->name . ' ' . $e->year . ' — Les matches du XV de France')

@section('meta_description', 'Le XV de France dans la compétition ' . $c->name . ' ' . $e->year . ' : ' . $record->total . ' matches, ' . $record->wins . ' victoires, ' . $record->losses . ' défaites.')

@section('breadcrumb')
    <x-breadcrumb :items="['Compétitions' => route('competitions.index'), $c->name => route('competitions.show', $c), (string) $e->year => null]" />
@endsection

@section('content')
    <x-page.hero :kicker="$c->name" :title="(string) $e->year">
        @if($grandSlam)
            <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-or/20 px-4 py-1.5 font-display text-lg font-bold uppercase tracking-wide text-amber-200 ring-1 ring-inset ring-or/40">Grand Chelem</p>
        @elseif($e->france_ranking)
            <p class="mt-4 font-serif text-lg text-blue-100">La France termine {{ $e->france_ranking === 1 ? '1ʳᵉ' : $e->france_ranking . 'ᵉ' }}.</p>
        @endif
        @if($record->total)
            <div class="mt-8 flex flex-wrap items-end gap-x-10 gap-y-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-300">Bilan</div>
                    <div class="mt-1 font-display text-4xl font-bold tabular-nums">{{ $record->total }} <span class="text-xl font-semibold text-blue-200">match{{ $record->total > 1 ? 'es' : '' }}</span></div>
                </div>
                <div class="min-w-64 flex-1 sm:max-w-md">
                    <div class="flex justify-between text-sm tabular-nums">
                        <span><b class="text-emerald-300">{{ $record->wins }}</b> V · <b class="text-amber-200">{{ $record->draws }}</b> N · <b class="text-red-300">{{ $record->losses }}</b> D</span>
                        <span class="text-blue-100">{{ $matches->sum('france_score') }} points marqués · {{ $matches->sum('opponent_score') }} encaissés</span>
                    </div>
                    <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                        <span class="bg-emerald-400" style="width: {{ $share($record->wins) }}%"></span>
                        <span class="bg-amber-300" style="width: {{ $share($record->draws) }}%"></span>
                        <span class="bg-rouge-france" style="width: {{ $share($record->losses) }}%"></span>
                    </div>
                </div>
            </div>
        @endif
    </x-page.hero>

    <section class="py-12 lg:py-16" aria-labelledby="matches">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="matches" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Les matches</h2>
            @if($matches->isEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-filet p-6 font-serif text-texte-2">Aucun match saisi pour cette édition.</p>
            @else
                <ol class="mt-6 divide-y divide-filet overflow-hidden rounded-2xl border border-filet bg-white">
                    @foreach($matches as $match)
                        <li><x-match.row :match="$match" :competition="false" :venue="true" /></li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>

    <nav class="border-t border-filet bg-papier-2" aria-label="Éditions">
        <div class="mx-auto grid max-w-6xl gap-4 px-4 py-8 sm:grid-cols-2 sm:px-6 lg:px-8">
            @foreach(['previous' => $previous, 'next' => $next] as $dir => $other)
                @if($other)
                    <a href="{{ route('editions.show', $other) }}" class="group rounded-2xl border border-filet bg-white p-5 hover:border-encre/30 {{ $dir === 'next' ? 'sm:text-right' : '' }}">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $dir === 'previous' ? '← Édition précédente' : 'Édition suivante →' }}</span>
                        <span class="mt-1 block font-display text-2xl font-bold tabular-nums text-encre group-hover:underline">{{ $other->year }}</span>
                    </a>
                @else
                    <span class="hidden sm:block"></span>
                @endif
            @endforeach
        </div>
    </nav>
@endsection
