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
            'mostPointsScored' => $records->mostPointsScored(),
            'mostPointsConceded' => $records->mostPointsConceded(),
            'streaks' => $records->streaks(),
            'decades' => $records->recordByDecade(),
            'venueTypes' => $records->recordByVenueType(),
            'detailedMatchCount' => $records->detailedMatchCount(),
            'topTryScorers' => $records->topTryScorers(),
            'mostAppearances' => $records->mostAppearances(),
            'mostCaptaincies' => $records->mostCaptaincies(),
        ]);
    }
}
