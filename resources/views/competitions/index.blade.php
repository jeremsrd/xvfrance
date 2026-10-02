@extends('layouts.app')

@section('title', 'Les compétitions du XV de France — Tournoi, Coupe du monde, tournées')

@section('meta_description', 'Le bilan du XV de France dans chaque compétition : Tournoi des 5/6 Nations, Coupe du monde, tournées d\'été, tests d\'automne.')

@section('breadcrumb')
    <x-breadcrumb :items="['Compétitions' => null]" />
@endsection

@php
    $share = fn ($r, $n) => $r->total ? round($n / $r->total * 100, 2) : 0;
@endphp

@section('content')
    <x-page.hero kicker="Le calendrier" title="Les compétitions"
                 subtitle="Le Tournoi depuis 1910, la Coupe du monde depuis 1987, les tournées et les tests : le XV de France dans chaque compétition." />

    <section class="py-12 lg:py-16">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            @foreach($competitions as $competition)
                @php $r = $competition->record; @endphp
                <a href="{{ route('competitions.show', $competition) }}" class="group grid gap-6 rounded-2xl border border-filet bg-white p-6 hover:border-encre/30 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france sm:p-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">{{ $competition->type?->label() }}</p>
                        <h2 class="mt-1 font-display text-3xl font-bold uppercase leading-tight text-encre group-hover:underline">{{ $competition->name }}</h2>
                        <p class="mt-1 font-serif text-texte-2">
                            {{ $competition->editions_count }} édition{{ $competition->editions_count > 1 ? 's' : '' }}@if($competition->first_year) · {{ $competition->first_year }}@if($competition->last_year !== $competition->first_year)–{{ $competition->last_year }}@endif @endif
                        </p>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-display text-4xl font-bold tabular-nums text-encre">{{ $r->total }} <span class="text-base font-semibold text-texte-2">matches</span></span>
                            <span class="text-sm tabular-nums"><b class="text-gagne">{{ $r->wins }}</b> V · <b class="text-egal">{{ $r->draws }}</b> N · <b class="text-perdu">{{ $r->losses }}</b> D · <b class="text-encre">{{ $r->winPctLabel(0) }}</b></span>
                        </div>
                        <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                            <span class="bg-gagne" style="width: {{ $share($r, $r->wins) }}%"></span>
                            <span class="bg-egal" style="width: {{ $share($r, $r->draws) }}%"></span>
                            <span class="bg-perdu" style="width: {{ $share($r, $r->losses) }}%"></span>
                        </div>
                    </div>
                </a>
            @endforeach

            @if($withoutCompetition->total)
                @php $r = $withoutCompetition; @endphp
                <div class="grid gap-6 rounded-2xl border border-dashed border-filet p-6 sm:p-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-texte-2">Hors compétition</p>
                        <h2 class="mt-1 font-display text-3xl font-bold uppercase leading-tight text-encre">Tests et autres matches</h2>
                        <p class="mt-1 font-serif text-texte-2">Rencontres non rattachées à une compétition dans nos archives.</p>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-display text-4xl font-bold tabular-nums text-encre">{{ $r->total }} <span class="text-base font-semibold text-texte-2">matches</span></span>
                            <span class="text-sm tabular-nums"><b class="text-gagne">{{ $r->wins }}</b> V · <b class="text-egal">{{ $r->draws }}</b> N · <b class="text-perdu">{{ $r->losses }}</b> D · <b class="text-encre">{{ $r->winPctLabel(0) }}</b></span>
                        </div>
                        <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                            <span class="bg-gagne" style="width: {{ $share($r, $r->wins) }}%"></span>
                            <span class="bg-egal" style="width: {{ $share($r, $r->draws) }}%"></span>
                            <span class="bg-perdu" style="width: {{ $share($r, $r->losses) }}%"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
