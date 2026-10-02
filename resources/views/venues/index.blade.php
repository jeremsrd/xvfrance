@extends('layouts.app')

@section('title', 'Les stades du XV de France — Carte interactive')

@section('meta_description', 'Carte interactive des ' . $venues->count() . ' stades où le XV de France a joué depuis 1906, avec le bilan de la France dans chacun.')

@section('breadcrumb')
    <x-breadcrumb :items="['Stades' => null]" />
@endsection

@push('head')
    <style>
        /* Fond de carte désaturé, accordé à la palette papier / encre */
        #venues-map .leaflet-tile-pane { filter: grayscale(0.9) sepia(0.15) contrast(0.92) brightness(1.04); }
        #venues-map { background: #EFEBE2; }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
@endpush

@section('content')

    <x-page.hero kicker="Les terrains" title="Les stades"
                 :subtitle="$venues->count() . ' stades dans ' . $venues->pluck('country_id')->unique()->count() . ' pays ont accueilli le XV de France.'" />

    <section class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="venues-map" class="h-[28rem] sm:h-[36rem] rounded-xl border border-filet z-0"
                 role="region" aria-label="Carte des stades"></div>
            <p class="mt-2 text-xs text-texte-2">La taille des cercles est proportionnelle au nombre de matches. Fond de carte © contributeurs OpenStreetMap.</p>
        </div>
    </section>

    <section class="pb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-encre">Bilan par stade</h2>
            <div class="mt-4 overflow-x-auto rounded-xl border border-filet bg-white">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead>
                        <tr class="border-b border-filet text-left text-xs uppercase tracking-wider text-texte-2">
                            <th scope="col" class="px-5 py-3 font-medium">Stade</th>
                            <th scope="col" class="px-3 py-3 font-medium">Ville</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Matches</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">V</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">N</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">D</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Victoires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-filet">
                        @foreach($venues as $venue)
                            <tr>
                                <th scope="row" class="px-5 py-2.5 text-left font-medium text-encre">{{ $venue->name }}</th>
                                <td class="px-3 py-2.5 text-texte-2">
                                    @if($venue->country)<span aria-hidden="true">{{ $venue->country->flag_emoji }}</span>@endif
                                    {{ $venue->city }}
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->total }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->wins }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->draws }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->losses }}</td>
                                <td class="px-5 py-2.5 text-right font-semibold tabular-nums">{{ $venue->record->winPctLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const markers = @json($markers);
            const map = L.map('venues-map', { scrollWheelZoom: false, worldCopyJump: true });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
            const plural = (n, one, many) => `${n} ${n > 1 ? many : one}`;

            markers.forEach((venue) => {
                L.circleMarker([venue.lat, venue.lng], {
                    radius: 5 + Math.sqrt(venue.total) * 2.5,
                    color: '#002395',
                    weight: 1.5,
                    fillColor: '#002395',
                    fillOpacity: 0.45,
                })
                    .bindPopup(
                        `<strong>${escape(venue.name)}</strong><br>${escape(venue.city)}, ${escape(venue.country)}<br>`
                        + `${plural(venue.total, 'match', 'matches')} · ${venue.wins} V · ${venue.draws} N · ${venue.losses} D`
                    )
                    .addTo(map);
            });

            // Vue initiale centrée sur l'Europe, où se joue l'essentiel des matches
            map.setView([47, 2], 4);
        });
    </script>

@endsection
