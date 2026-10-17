<?php

namespace App\Http\Controllers;

use App\Services\RecordsService;

class RecordController extends Controller
{
    public function index(RecordsService $records)
    {
        return view('records.index', [
            'biggestWins' => $records->biggestWins(),
            'heaviestDefeats' => $records->heaviestDefeats(),
            'scoreRecords' => $records->scoreRecords(),
            'streaks' => $records->streaks(),
            'bestYear' => $records->bestYear(),
            'opponentRecords' => $records->opponentRecords(),
            'decades' => $records->recordByDecade(),
            'venueTypes' => $records->recordByVenueType(),
            'firstMatch' => $records->firstMatch(),
        ]);
    }

    public function players(RecordsService $records)
    {
        return view('records.players', [
            'matchCount' => \App\Models\RugbyMatch::count(),
            'detailedMatchCount' => $records->detailedMatchCount(),
            'mostAppearances' => $records->mostAppearances(),
            'topTryScorers' => $records->topTryScorers(),
            'topPointScorers' => $records->topPointScorers(),
            'mostCaptaincies' => $records->mostCaptaincies(),
            'recordHistories' => [
                'selections' => $records->capsRecordHistory(),
                'essais' => $records->triesRecordHistory(),
                'points' => $records->pointsRecordHistory(),
            ],
        ]);
    }
}
