@extends('layouts.app')

@section('title', 'Les stades du XV de France — Carte interactive')

@section('meta_description', 'Carte interactive des ' . $venues->count() . ' stades où le XV de France a joué depuis 1906, avec le bilan de la France dans chacun.')

@section('breadcrumb')
    <span class="mx-2">/</span>
    <span class="text-gray-700">Stades</span>
@endsection

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
@endpush

@section('content')

    <section class="bg-bleu-france text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight">Les stades</h1>
            <p class="mt-3 text-blue-100">{{ $venues->count() }} stades dans {{ $venues->pluck('country_id')->unique()->count() }} pays ont accueilli le XV de France.</p>
        </div>
    </section>

    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="venues-map" class="h-[28rem] sm:h-[36rem] rounded-xl border border-slate-200 z-0"
                 role="region" aria-label="Carte des stades"></div>
            <p class="mt-2 text-xs text-slate-500">La taille des cercles est proportionnelle au nombre de matches. Fond de carte © contributeurs OpenStreetMap.</p>
        </div>
    </section>

    <section class="pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Bilan par stade</h2>
            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wider text-slate-500">
                            <th scope="col" class="px-5 py-3 font-medium">Stade</th>
                            <th scope="col" class="px-3 py-3 font-medium">Ville</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">Matches</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">V</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">N</th>
                            <th scope="col" class="px-3 py-3 text-right font-medium">D</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Victoires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($venues as $venue)
                            <tr>
                                <th scope="row" class="px-5 py-2.5 text-left font-medium text-slate-900">{{ $venue->name }}</th>
                                <td class="px-3 py-2.5 text-slate-600">
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
