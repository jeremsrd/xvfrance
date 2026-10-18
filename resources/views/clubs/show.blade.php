@extends('layouts.app')

@php
    $formerNames = $club->formerNames();
    $year = fn ($date) => $date ? substr($date, 0, 4) : null;
@endphp

@section('title', $club->name . ' — internationaux du XV de France')

@section('meta_description', $club->name . ($club->city ? ' (' . $club->city . ')' : '') . ' a fourni ' . \App\Support\FrenchText::count($players->count(), 'international', 'internationaux') . ' au XV de France' . ($players->isNotEmpty() ? ' depuis ' . $year($players->first()->first_cap) : '') . ' : la liste, leurs sélections et leurs postes.')

@section('breadcrumb')
    <x-breadcrumb :items="['Clubs' => route('clubs.index'), $club->name => null]" />
@endsection

@section('content')

    <x-page.hero :kicker="trim(($club->country?->flag_emoji ?? '') . ' ' . $club->city)" :title="$club->name"
                 :subtitle="$players->count() . ' international' . ($players->count() > 1 ? 'aux' : '') . ' du XV de France' . ($formerNames ? '. Aussi connu sous les noms ' . implode(', ', $formerNames) . '.' : '.')" />

    <section class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-x-auto rounded-xl border border-filet bg-white">
                <table class="w-full min-w-[36rem] text-sm">
                    <thead>
                        <tr class="border-b border-filet text-left text-xs uppercase tracking-wider text-texte-2">
                            <th scope="col" class="px-5 py-3 font-medium">Joueur</th>
                            <th scope="col" class="px-3 py-3 font-medium">Poste</th>
                            <th scope="col" class="px-3 py-3 font-medium">En équipe de France</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Sélections</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-filet">
                        @foreach($players as $player)
                            <tr>
                                <th scope="row" class="px-5 py-2.5 text-left font-medium text-encre">
                                    <a href="{{ route('players.show', $player) }}" class="hover:underline focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france">{{ $player->fullName() }}</a>
                                    @if($player->pivot->name !== $club->name)
                                        <span class="block text-xs font-normal text-texte-2">{{ $player->pivot->name }}</span>
                                    @endif
                                </th>
                                <td class="px-3 py-2.5 text-texte-2">{{ $player->primary_position?->label() }}</td>
                                <td class="px-3 py-2.5 tabular-nums text-texte-2">
                                    @if($player->first_cap)
                                        {{ $year($player->first_cap) }}@if($year($player->last_cap) !== $year($player->first_cap))–{{ $year($player->last_cap) }}@endif
                                    @endif
                                </td>
                                <td class="px-5 py-2.5 text-right font-semibold tabular-nums">{{ $player->caps }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
