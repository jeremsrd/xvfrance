<?php

namespace App\Http\Controllers;

use App\Models\RugbyMatch;
use App\Models\Venue;
use App\Support\RecordSummary;

class VenueController extends Controller
{
    public function index()
    {
        $records = RecordSummary::groupedBy(RugbyMatch::whereNotNull('venue_id'), 'venue_id');

        $venues = Venue::with(['country', 'names'])
            ->whereIn('id', $records->keys())
            ->get()
            ->each(fn (Venue $venue) => $venue->record = $records[$venue->id])
            ->sortByDesc(fn (Venue $venue) => $venue->record->total)
            ->values();

        $markers = $venues
            ->filter(fn (Venue $venue) => $venue->latitude !== null && $venue->longitude !== null)
            ->map(fn (Venue $venue) => [
                'name' => $venue->name,
                'url' => route('venues.show', $venue),
                'city' => $venue->city,
                'country' => $venue->country?->name,
                'lat' => (float) $venue->latitude,
                'lng' => (float) $venue->longitude,
                'total' => $venue->record->total,
                'wins' => $venue->record->wins,
                'draws' => $venue->record->draws,
                'losses' => $venue->record->losses,
            ])
            ->values();

        return view('venues.index', compact('venues', 'markers'));
    }

    public function show(Venue $venue)
    {
        $venue->load(['country', 'names']);
        $matches = $venue->matches()
            ->with(['opponent', 'edition.competition', 'venue'])
            ->orderByDesc('match_date')
            ->get();

        abort_if($matches->isEmpty(), 404);

        $record = RecordSummary::fromMatches($matches);

        return view('venues.show', compact('venue', 'matches', 'record'));
    }
}
