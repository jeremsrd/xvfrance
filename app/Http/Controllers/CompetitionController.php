<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;

class CompetitionController extends Controller
{
    public function index()
    {
        $competitions = Competition::withCount(['editions', 'matches'])
            ->orderBy('name')
            ->get();

        return view('competitions.index', compact('competitions'));
    }

    public function show(Competition $competition)
    {
        $editions = $competition->editions()
            ->orderByDesc('year')
            ->get();

        $records = RecordSummary::groupedBy(
            RugbyMatch::whereIn('edition_id', $editions->modelKeys()),
            'edition_id'
        );

        $editions->each(fn ($edition) => $edition->record = $records[$edition->id] ?? new RecordSummary());

        return view('competitions.show', compact('competition', 'editions'));
    }
}
