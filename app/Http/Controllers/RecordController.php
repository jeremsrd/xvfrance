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
            'detailedMatchCount' => $records->detailedMatchCount(),
            'topTryScorers' => $records->topTryScorers(),
            'mostAppearances' => $records->mostAppearances(),
            'mostCaptaincies' => $records->mostCaptaincies(),
        ]);
    }
}
