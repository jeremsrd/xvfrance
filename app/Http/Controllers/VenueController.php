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

        // Par pays : la France d'abord, puis les pays où le XV a le plus joué
        $countries = $venues->groupBy('country_id')
            ->map(fn ($group) => (object) [
                'country' => $group->first()->country,
                'venues' => $group,
                'record' => new RecordSummary(
                    wins: $group->sum(fn (Venue $venue) => $venue->record->wins),
                    losses: $group->sum(fn (Venue $venue) => $venue->record->losses),
                    draws: $group->sum(fn (Venue $venue) => $venue->record->draws),
                ),
            ])
            ->sortBy([
                fn ($a, $b) => ($b->country?->code === 'FRA') <=> ($a->country?->code === 'FRA'),
                fn ($a, $b) => $b->record->total <=> $a->record->total,
            ])
            ->values();

        return view('venues.index', compact('venues', 'countries'));
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
