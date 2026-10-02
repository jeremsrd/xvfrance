@extends('layouts.app')

@section('title', 'Les adversaires du XV de France — Bilan face à chaque nation')

@section('meta_description', 'Le bilan du XV de France face aux ' . $opponents->count() . ' nations affrontées depuis 1906 : victoires, défaites, nuls et premières rencontres.')

@section('breadcrumb')
    <x-breadcrumb :items="['Adversaires' => null]" />
@endsection

@php
    $rivals = $opponents->take(6);
    $share = fn ($r, $n) => $r->total ? round($n / $r->total * 100, 2) : 0;
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france';
@endphp

@section('content')
    <x-page.hero kicker="Les rivalités" title="Les adversaires"
                 :subtitle="$opponents->count() . ' nations affrontées depuis 1906, des voisins du Tournoi aux îles du Pacifique.'" />

    {{-- ═══ Grands rivaux ═══ --}}
    <section class="py-12 lg:py-16" aria-labelledby="rivaux">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="rivaux" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Les grands rivaux</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($rivals as $opponent)
                    @php $r = $opponent->record; @endphp
                    <li>
                        <a href="{{ route('opponents.show', $opponent) }}" class="group block h-full rounded-2xl border border-filet bg-white p-6 hover:border-encre/30 {{ $focus }}">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="text-4xl" aria-hidden="true">{{ $opponent->flag_emoji }}</span>
                                    <h3 class="mt-2 font-display text-2xl font-bold uppercase leading-tight text-encre group-hover:underline">{{ $opponent->name }}</h3>
                                    <p class="text-sm text-texte-2">depuis {{ $opponent->first_met->year }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="block font-display text-4xl font-bold leading-none tabular-nums text-encre">{{ $r->total }}</span>
                                    <span class="text-xs text-texte-2">matches</span>
                                </div>
                            </div>
                            <div class="mt-5 flex h-2 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                                <span class="bg-gagne" style="width: {{ $share($r, $r->wins) }}%"></span>
                                <span class="bg-egal" style="width: {{ $share($r, $r->draws) }}%"></span>
                                <span class="bg-perdu" style="width: {{ $share($r, $r->losses) }}%"></span>
                            </div>
                            <div class="mt-2 flex justify-between text-sm tabular-nums">
                                <span><b class="text-gagne">{{ $r->wins }}</b> V · <b class="text-egal">{{ $r->draws }}</b> N · <b class="text-perdu">{{ $r->losses }}</b> D</span>
                                <span class="font-semibold text-encre">{{ $r->winPctLabel(0) }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ═══ Toutes les nations ═══ --}}
    <section class="border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="toutes">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="toutes" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Toutes les nations</h2>
            <div class="mt-6 overflow-x-auto rounded-2xl border border-filet">
                <table class="w-full min-w-[40rem] text-sm">
                    <caption class="sr-only">Bilan du XV de France face à chaque nation, par nombre de matches</caption>
                    <thead>
                        <tr class="border-b border-filet bg-papier text-left text-xs uppercase tracking-wider text-texte-2">
                            <th scope="col" class="px-5 py-3 font-semibold">Nation</th>
                            <th scope="col" class="px-3 py-3 text-right font-semibold">Matches</th>
                            <th scope="col" class="px-3 py-3 text-right font-semibold">V</th>
                            <th scope="col" class="px-3 py-3 text-right font-semibold">N</th>
                            <th scope="col" class="px-3 py-3 text-right font-semibold">D</th>
                            <th scope="col" class="w-1/4 px-5 py-3 font-semibold">Victoires</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Période</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-filet">
                        @foreach($opponents as $opponent)
                            @php $r = $opponent->record; @endphp
                            <tr class="hover:bg-papier">
                                <th scope="row" class="px-5 py-3 text-left">
                                    <a href="{{ route('opponents.show', $opponent) }}" class="font-semibold text-encre hover:underline {{ $focus }}">
                                        <span aria-hidden="true">{{ $opponent->flag_emoji }}</span> {{ $opponent->name }}
                                    </a>
                                </th>
                                <td class="px-3 py-3 text-right font-display text-lg font-bold tabular-nums text-encre">{{ $r->total }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gagne">{{ $r->wins }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-egal">{{ $r->draws }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-perdu">{{ $r->losses }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-1.5 flex-1 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                                            <span class="bg-gagne" style="width: {{ $share($r, $r->wins) }}%"></span>
                                            <span class="bg-egal" style="width: {{ $share($r, $r->draws) }}%"></span>
                                            <span class="bg-perdu" style="width: {{ $share($r, $r->losses) }}%"></span>
                                        </span>
                                        <span class="w-12 text-right font-semibold tabular-nums text-encre">{{ $r->winPctLabel(0) }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-texte-2">
                                    {{ $opponent->first_met->year }}@if($opponent->last_met->year !== $opponent->first_met->year)–{{ $opponent->last_met->year }}@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
