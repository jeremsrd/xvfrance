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
        $periods = RugbyMatch::toBase()
            ->selectRaw('opponent_id, MIN(match_date) AS first_met, MAX(match_date) AS last_met')
            ->groupBy('opponent_id')
            ->get()
            ->keyBy('opponent_id');

        $opponents = Country::whereIn('id', $records->keys())
            ->get()
            ->each(function ($country) use ($records, $periods) {
                $country->record = $records[$country->id];
                $country->first_met = \Illuminate\Support\Carbon::parse($periods[$country->id]->first_met);
                $country->last_met = \Illuminate\Support\Carbon::parse($periods[$country->id]->last_met);
            })
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
