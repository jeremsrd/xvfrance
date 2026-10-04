@extends('layouts.app')

@section('title', $competition->name . ' — Le XV de France, édition par édition')

@section('meta_description', 'Le XV de France dans la compétition ' . $competition->name . ' : ' . $editions->count() . ' éditions, ' . $record->total . ' matches, ' . $record->wins . ' victoires.')

@section('breadcrumb')
    <x-breadcrumb :items="['Compétitions' => route('competitions.index'), $competition->name => null]" />
@endsection

@php
    $slams = $editions->where('grand_slam', true)->sortBy('year');
    $decades = $editions->sortBy('year')->groupBy(fn ($e) => intdiv($e->year, 10) * 10)->reverse();
    $share = fn ($r, $n) => $r->total ? round($n / $r->total * 100, 2) : 0;
@endphp

@section('content')
    <x-page.hero :kicker="$competition->type?->label()" :title="$competition->name" :logo="$competition->logo_path"
                 :subtitle="$editions->count() . ' édition' . ($editions->count() > 1 ? 's' : '') . ($editions->isNotEmpty() ? ', de ' . $editions->min('year') . ' à ' . $editions->max('year') : '') . '.'">
        <div class="mt-8 grid gap-6 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end sm:gap-10">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-300">Bilan</div>
                <div class="mt-1 font-display text-4xl font-bold tabular-nums">{{ $record->total }} <span class="text-xl font-semibold text-blue-200">matches</span></div>
            </div>
            <div class="max-w-md">
                <div class="flex justify-between text-sm tabular-nums">
                    <span><b class="text-emerald-300">{{ $record->wins }}</b> V · <b class="text-amber-200">{{ $record->draws }}</b> N · <b class="text-red-300">{{ $record->losses }}</b> D</span>
                    <span class="text-blue-100">{{ $record->winPctLabel() }}</span>
                </div>
                <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                    <span class="bg-emerald-400" style="width: {{ $share($record, $record->wins) }}%"></span>
                    <span class="bg-amber-300" style="width: {{ $share($record, $record->draws) }}%"></span>
                    <span class="bg-rouge-france" style="width: {{ $share($record, $record->losses) }}%"></span>
                </div>
            </div>
        </div>
    </x-page.hero>

    @if($slams->isNotEmpty())
        <section class="border-b border-filet bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-6 sm:px-6 lg:px-8">
                <div>
                    <span class="font-display text-3xl font-bold text-or-2">{{ $slams->count() }}</span>
                    <span class="ml-1 font-display text-xl font-bold uppercase text-encre">Grand{{ $slams->count() > 1 ? 's' : '' }} Chelem{{ $slams->count() > 1 ? 's' : '' }}</span>
                </div>
                <ul class="flex flex-wrap gap-2">
                    @foreach($slams as $slam)
                        <li><a href="{{ route('editions.show', $slam) }}" class="inline-flex rounded-full bg-or/15 px-3 py-1 font-display text-lg font-bold tabular-nums text-or-2 ring-1 ring-inset ring-or/40 hover:bg-or/25">{{ $slam->year }}</a></li>
                    @endforeach
                </ul>
                <p class="w-full text-xs text-texte-2">Grands Chelems établis d'après les matches saisis : une édition incomplète dans nos archives n'apparaît pas.</p>
            </div>
        </section>
    @endif

    <section class="py-12 lg:py-16" aria-labelledby="editions">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="editions" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Édition par édition</h2>
            @if($editions->isEmpty())
                <p class="mt-4 font-serif text-texte-2">Aucune édition enregistrée.</p>
            @else
                <div class="mt-8 space-y-8">
                    @foreach($decades as $decade => $group)
                        <div>
                            <h3 class="border-b border-filet pb-2 font-display text-xl font-bold tabular-nums text-encre">Années {{ $decade }}</h3>
                            <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach($group as $edition)
                                    @php $r = $edition->record; @endphp
                                    <li>
                                        <a href="{{ route('editions.show', $edition) }}" class="group block h-full rounded-xl border p-4 hover:border-encre/30 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france {{ $edition->grand_slam ? 'border-or/50 bg-or/10' : 'border-filet bg-white' }}">
                                            <span class="flex items-baseline justify-between gap-2">
                                                <span class="font-display text-2xl font-bold tabular-nums text-encre group-hover:underline">{{ $edition->year }}</span>
                                                @if($edition->france_ranking)<span class="text-xs font-semibold text-texte-2">{{ $edition->france_ranking === 1 ? '1ᵉʳ' : $edition->france_ranking . 'ᵉ' }}</span>@endif
                                            </span>
                                            @if($edition->grand_slam)<span class="mt-0.5 block text-[11px] font-bold uppercase tracking-wider text-or-2">Grand Chelem</span>@endif
                                            @if($r->total)
                                                <span class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                                                    <span class="bg-gagne" style="width: {{ $share($r, $r->wins) }}%"></span>
                                                    <span class="bg-egal" style="width: {{ $share($r, $r->draws) }}%"></span>
                                                    <span class="bg-perdu" style="width: {{ $share($r, $r->losses) }}%"></span>
                                                </span>
                                                <span class="mt-1.5 block text-xs tabular-nums text-texte-2">{{ $r->wins }} V · {{ $r->draws }} N · {{ $r->losses }} D</span>
                                            @else
                                                <span class="mt-2 block text-xs text-texte-2">Aucun match saisi</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
