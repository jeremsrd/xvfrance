<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionType;
use App\Models\Competition;
use App\Models\CompetitionEdition;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;

class CompetitionController extends Controller
{
    public function index()
    {
        $matches = RugbyMatch::with('edition')->get(['id', 'edition_id', 'france_score', 'opponent_score']);
        $byCompetition = $matches->groupBy(fn ($m) => $m->edition?->competition_id ?? 0);

        $competitions = Competition::withCount('editions')
            ->with('editions:id,competition_id,year')
            ->get()
            ->each(function (Competition $c) use ($byCompetition) {
                $c->record = RecordSummary::fromMatches($byCompetition[$c->id] ?? []);
                $c->first_year = $c->editions->min('year');
                $c->last_year = $c->editions->max('year');
            })
            ->sortByDesc(fn ($c) => $c->record->total)
            ->values();

        $withoutCompetition = RecordSummary::fromMatches($byCompetition[0] ?? []);

        return view('competitions.index', compact('competitions', 'withoutCompetition'));
    }

    public function show(Competition $competition)
    {
        $editions = $competition->editions()
            ->with('matches:id,edition_id,france_score,opponent_score')
            ->orderByDesc('year')
            ->get()
            ->each(function (CompetitionEdition $edition) use ($competition) {
                $edition->record = RecordSummary::fromMatches($edition->matches);
                $edition->grand_slam = $competition->type === CompetitionType::TOURNOI && self::isGrandSlam($edition);
            });

        $record = RecordSummary::fromMatches($editions->flatMap->matches);

        return view('competitions.show', compact('competition', 'editions', 'record'));
    }

    /**
     * Grand Chelem certain : toutes les rencontres gagnées, et le nombre de matches attendu
     * (4 adversaires dans le Tournoi des Cinq Nations, 5 depuis 2000).
     */
    public static function isGrandSlam(CompetitionEdition $edition): bool
    {
        $expected = $edition->year >= 2000 ? 5 : 4;

        return $edition->record->total === $expected && $edition->record->wins === $expected;
    }
}
