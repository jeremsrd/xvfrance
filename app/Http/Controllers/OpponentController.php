<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;

class OpponentController extends Controller
{
    public function index()
    {
        $records = RecordSummary::groupedBy(RugbyMatch::query(), 'opponent_id');

        $opponents = Country::whereIn('id', $records->keys())
            ->get()
            ->each(fn ($country) => $country->record = $records[$country->id])
            ->sortByDesc(fn ($country) => $country->record->total)
            ->values();

        return view('opponents.index', compact('opponents'));
    }

    public function show(Country $country)
    {
        $matches = RugbyMatch::with(['venue', 'edition.competition'])
            ->where('opponent_id', $country->id)
            ->orderBy('match_date', 'desc')
            ->get()
            ->each->setRelation('opponent', $country);

        $record = RecordSummary::fromMatches($matches);

        $biggestWin = $matches->where('is_victory', true)->sortByDesc('point_diff')->first();
        $biggestLoss = $matches->where('is_defeat', true)->sortBy('point_diff')->first();

        return view('opponents.show', compact('country', 'matches', 'record', 'biggestWin', 'biggestLoss'));
    }
}
