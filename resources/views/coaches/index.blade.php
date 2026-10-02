@extends('layouts.app')

@section('title', 'Les sélectionneurs du XV de France')

@section('meta_description', 'Les sélectionneurs du XV de France et leur bilan à la tête de l\'équipe.')

@section('breadcrumb')
    <x-breadcrumb :items="['Sélectionneurs' => null]" />
@endsection

@php
    use App\Support\FrenchDate;
    $share = fn ($r, $n) => $r->total ? round($n / $r->total * 100, 2) : 0;
@endphp

@section('content')
    <x-page.hero kicker="Le banc" title="Les sélectionneurs"
                 subtitle="Ceux qui ont composé le XV de France, et leur bilan à la tête de l'équipe." />

    <section class="py-12 lg:py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if($coaches->isEmpty())
                <div class="rounded-2xl border border-dashed border-filet bg-white/60 p-8 text-center">
                    <p class="font-display text-2xl font-bold uppercase text-encre">Frise en préparation</p>
                    <p class="mx-auto mt-2 max-w-xl font-serif text-texte-2">
                        Les sélectionneurs du XV de France et leurs périodes n'ont pas encore été saisis dans nos archives.
                        Leur bilan sera calculé automatiquement à partir des matches.
                    </p>
                </div>
            @else
                <ol class="relative border-l-2 border-filet pl-8">
                    @foreach($coaches as $coach)
                        @php $r = $coach->record; $t = $coach->tenure; @endphp
                        <li class="relative pb-8 last:pb-0">
                            <span class="absolute -left-[2.6rem] top-1.5 h-4 w-4 rounded-full border-4 border-papier {{ $t->end_date ? 'bg-encre-3' : 'bg-bleu-france' }}" aria-hidden="true"></span>
                            <a href="{{ route('coaches.show', $coach) }}" class="group block rounded-2xl border border-filet bg-white p-6 hover:border-encre/30 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france">
                                <p class="font-display text-lg font-bold tabular-nums text-texte-2">
                                    {{ $t->start_date->year }} – {{ $t->end_date ? $t->end_date->year : 'aujourd\'hui' }}
                                    @unless($t->end_date)<span class="ml-2 rounded-full bg-bleu-france/10 px-2 py-0.5 align-middle text-xs font-semibold uppercase tracking-wide text-bleu-france">En poste</span>@endunless
                                </p>
                                <h2 class="mt-1 leading-tight">
                                    <span class="font-serif text-lg italic text-texte-2">{{ $coach->first_name }}</span>
                                    <span class="block font-display text-3xl font-bold uppercase text-encre group-hover:underline">{{ $coach->last_name }}</span>
                                </h2>
                                @if($r->total)
                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm tabular-nums">
                                        <span>{{ $r->total }} matches · <b class="text-gagne">{{ $r->wins }}</b> V · <b class="text-egal">{{ $r->draws }}</b> N · <b class="text-perdu">{{ $r->losses }}</b> D</span>
                                        <span class="font-display text-xl font-bold text-encre">{{ $r->winPctLabel(0) }}</span>
                                    </div>
                                    <div class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-papier-2" aria-hidden="true">
                                        <span class="bg-gagne" style="width: {{ $share($r, $r->wins) }}%"></span>
                                        <span class="bg-egal" style="width: {{ $share($r, $r->draws) }}%"></span>
                                        <span class="bg-perdu" style="width: {{ $share($r, $r->losses) }}%"></span>
                                    </div>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>
@endsection
