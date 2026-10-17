<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Records et statistiques du XV de France.
 *
 * Les records d'équipe reposent sur les scores (complets depuis 1906), les records
 * individuels sur les feuilles de match détaillées (partielles, voir detailedMatchCount()).
 */
class RecordsService
{
    /** @var Collection<int, RugbyMatch>|null */
    private ?Collection $chronology = null;

    // --- Records d'équipe ---

    /** @return Collection<int, RugbyMatch> */
    public function biggestWins(int $limit = 10): Collection
    {
        return $this->matchQuery()->wins()
            ->orderByRaw('(france_score - opponent_score) DESC')
            ->orderBy('match_date')
            ->limit($limit)->get();
    }

    /** @return Collection<int, RugbyMatch> */
    public function heaviestDefeats(int $limit = 10): Collection
    {
        return $this->matchQuery()->losses()
            ->orderByRaw('(opponent_score - france_score) DESC')
            ->orderBy('match_date')
            ->limit($limit)->get();
    }

    /**
     * Plus longues séries, dans l'ordre chronologique.
     *
     * @return array{wins: ?array, unbeaten: ?array, losses: ?array, homeWins: ?array}
     */
    public function streaks(): array
    {
        return [
            'wins' => $this->longestStreak(fn (RugbyMatch $m) => $m->france_score > $m->opponent_score),
            'unbeaten' => $this->longestStreak(fn (RugbyMatch $m) => $m->france_score >= $m->opponent_score),
            'losses' => $this->longestStreak(fn (RugbyMatch $m) => $m->france_score < $m->opponent_score),
            'homeWins' => $this->longestStreak(
                fn (RugbyMatch $m) => $m->france_score > $m->opponent_score,
                $this->chronology()->where('is_home', true),
            ),
        ];
    }

    /**
     * Bilan par décennie, de la plus ancienne à la plus récente.
     *
     * @return Collection<int, RecordSummary>  clé = première année de la décennie
     */
    public function recordByDecade(): Collection
    {
        return $this->chronology()
            ->groupBy(fn (RugbyMatch $m) => intdiv($m->match_date->year, 10) * 10)
            ->map(fn (Collection $matches) => RecordSummary::fromMatches($matches));
    }

    /**
     * Bilan à domicile, à l'extérieur et sur terrain neutre.
     *
     * @return array{home: RecordSummary, away: RecordSummary, neutral: RecordSummary}
     */
    public function recordByVenueType(): array
    {
        $groups = $this->chronology()->groupBy(
            fn (RugbyMatch $m) => $m->is_neutral ? 'neutral' : ($m->is_home ? 'home' : 'away')
        );

        return [
            'home' => RecordSummary::fromMatches($groups['home'] ?? []),
            'away' => RecordSummary::fromMatches($groups['away'] ?? []),
            'neutral' => RecordSummary::fromMatches($groups['neutral'] ?? []),
        ];
    }

    /**
     * Records face à chaque adversaire : première victoire, plus large victoire,
     * plus lourde défaite. Triés par nombre de rencontres.
     *
     * @return Collection<int, object{opponent: \App\Models\Country, total: int, firstWin: ?RugbyMatch, biggestWin: ?RugbyMatch, heaviestDefeat: ?RugbyMatch}>
     */
    public function opponentRecords(): Collection
    {
        return $this->chronology()
            ->groupBy('opponent_id')
            ->map(function (Collection $matches) {
                $wins = $matches->filter(fn (RugbyMatch $m) => $m->france_score > $m->opponent_score);
                $defeats = $matches->filter(fn (RugbyMatch $m) => $m->france_score < $m->opponent_score);

                return (object) [
                    'opponent' => $matches->first()->opponent,
                    'total' => $matches->count(),
                    'firstWin' => $wins->first(),
                    'biggestWin' => $wins->sortByDesc(fn (RugbyMatch $m) => $m->france_score - $m->opponent_score)->first(),
                    'heaviestDefeat' => $defeats->sortByDesc(fn (RugbyMatch $m) => $m->opponent_score - $m->france_score)->first(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Records de score isolés, un match chacun (null si aucun match ne convient).
     *
     * @return array<string, ?RugbyMatch>
     */
    public function scoreRecords(): array
    {
        $matches = $this->chronology();
        $highest = fn (Collection $c, callable $value) => $c->sortByDesc($value)->first();

        return [
            'mostScored' => $highest($matches, fn (RugbyMatch $m) => $m->france_score),
            'mostConceded' => $highest($matches, fn (RugbyMatch $m) => $m->opponent_score),
            'highestAggregate' => $highest($matches, fn (RugbyMatch $m) => $m->france_score + $m->opponent_score),
            'mostScoredInDefeat' => $highest($matches->filter(fn (RugbyMatch $m) => $m->france_score < $m->opponent_score), fn (RugbyMatch $m) => $m->france_score),
            'mostConcededInWin' => $highest($matches->filter(fn (RugbyMatch $m) => $m->france_score > $m->opponent_score), fn (RugbyMatch $m) => $m->opponent_score),
            'highestDraw' => $highest($matches->filter(fn (RugbyMatch $m) => $m->france_score === $m->opponent_score), fn (RugbyMatch $m) => $m->france_score),
        ];
    }

    /**
     * Meilleure année civile : plus fort taux de victoires (au moins $minMatches matches),
     * puis le plus de victoires.
     *
     * @return array{year: int, record: RecordSummary}|null
     */
    public function bestYear(int $minMatches = 6): ?array
    {
        $year = $this->chronology()
            ->groupBy(fn (RugbyMatch $m) => $m->match_date->year)
            ->map(fn (Collection $matches) => RecordSummary::fromMatches($matches))
            ->filter(fn (RecordSummary $r) => $r->total >= $minMatches)
            ->sortBy([fn ($a, $b) => $b->wins / $b->total <=> $a->wins / $a->total, fn ($a, $b) => $b->wins <=> $a->wins])
            ->keys()
            ->first();

        return $year === null ? null : ['year' => $year, 'record' => $this->recordByYear($year)];
    }

    public function firstMatch(): ?RugbyMatch
    {
        return $this->chronology()->first();
    }

    // --- Records individuels (feuilles de match détaillées) ---

    public function detailedMatchCount(): int
    {
        return RugbyMatch::has('lineups')->count();
    }

    /** @return Collection<int, MatchEvent>  avec l'attribut total */
    public function topTryScorers(int $limit = 10): Collection
    {
        return $this->leaderboard(
            MatchEvent::where('team_side', TeamSide::FRANCE)
                ->where('event_type', EventType::ESSAI)
                ->whereNotNull('player_id'),
            $limit,
        );
    }

    /**
     * Meilleurs marqueurs de points, selon le barème de chaque époque.
     *
     * @return Collection<int, object{player: Player, total: int}>
     */
    public function topPointScorers(int $limit = 10): Collection
    {
        $totals = $this->scoringByMatch()->flatten(1)
            ->groupBy('player_id')
            ->map(fn (Collection $events) => $events->sum(fn ($e) => $e->event_type->points(Carbon::parse($e->match_date))))
            ->sortDesc()
            ->take($limit);
        $players = Player::whereIn('id', $totals->keys())->get()->keyBy('id');

        return $totals->map(fn (int $total, int $id) => (object) ['player' => $players[$id], 'total' => $total])->values();
    }

    /** @return Collection<int, MatchLineup>  avec l'attribut total (remplaçants restés sur le banc exclus) */
    public function mostAppearances(int $limit = 10): Collection
    {
        return $this->leaderboard(MatchLineup::query()->played()->where('team_side', TeamSide::FRANCE), $limit);
    }

    /** @return Collection<int, MatchLineup>  avec l'attribut total */
    public function mostCaptaincies(int $limit = 10): Collection
    {
        return $this->leaderboard(
            MatchLineup::where('team_side', TeamSide::FRANCE)->where('is_captain', true),
            $limit,
        );
    }

    /**
     * Les recordmen des sélections successifs : chaque fois qu'un joueur prend seul le record du nombre de matches
     * joués avec le XV de France (remplaçants restés sur le banc exclus), ou l'égale et le partage.
     *
     * @return Collection<int, array{players: Collection<int, Player>, match: RugbyMatch, value: int, equalled: bool, with: Collection<int, Player>, equalledMatch: RugbyMatch|null, until: RugbyMatch|null, totals: array<int, int>}>
     */
    public function capsRecordHistory(): Collection
    {
        $lineups = MatchLineup::query()->played()
            ->where('team_side', TeamSide::FRANCE)
            ->join('matches', 'matches.id', '=', 'match_lineups.match_id')
            ->orderBy('matches.match_date')->orderBy('matches.id')
            ->get(['match_lineups.match_id', 'match_lineups.player_id']);

        return $this->recordHistory(
            $lineups->groupBy('match_id')->map(fn (Collection $rows) => $rows->mapWithKeys(fn ($row) => [$row->player_id => 1])->all())
        );
    }

    /**
     * Les recordmen des essais successifs (essais de pénalité exclus), même format que capsRecordHistory().
     */
    public function triesRecordHistory(): Collection
    {
        return $this->recordHistory($this->scoringByMatch()->map(
            fn (Collection $events) => $events->where('event_type', EventType::ESSAI)->countBy('player_id')->all()
        ));
    }

    /**
     * Les recordmen des points successifs, selon le barème de chaque époque (EventType::points()),
     * même format que capsRecordHistory().
     */
    public function pointsRecordHistory(): Collection
    {
        return $this->recordHistory($this->scoringByMatch()->map(
            fn (Collection $events) => $events->groupBy('player_id')
                ->map(fn (Collection $own) => $own->sum(fn ($e) => $e->event_type->points(Carbon::parse($e->match_date))))
                ->all()
        ));
    }

    // --- Interne ---

    /**
     * Événements qui rapportent des points à un joueur français, par match dans l'ordre chronologique.
     *
     * @return Collection<int, Collection<int, MatchEvent>>
     */
    private function scoringByMatch(): Collection
    {
        return MatchEvent::query()
            ->where('team_side', TeamSide::FRANCE)
            ->whereNotNull('player_id')
            ->whereIn('event_type', [EventType::ESSAI, EventType::TRANSFORMATION, EventType::PENALITE, EventType::DROP])
            ->join('matches', 'matches.id', '=', 'match_events.match_id')
            ->orderBy('matches.match_date')->orderBy('matches.id')
            ->get(['match_events.match_id', 'match_events.player_id', 'match_events.event_type', 'matches.match_date'])
            ->groupBy('match_id');
    }

    /**
     * Recordmen successifs d'un total cumulé : chaque fois qu'un joueur prend seul le record, ou l'égale et le partage.
     *
     * @param  Collection<int, array<int, int>>  $gains  par match (clé = match_id, ordre chronologique) : player_id => gain
     */
    private function recordHistory(Collection $gains): Collection
    {
        $totals = [];
        $record = 0;
        $holders = [];
        $entries = [];
        foreach ($gains as $matchId => $byPlayer) {
            $ids = array_keys(array_filter($byPlayer));
            if (!$ids) {
                continue;
            }
            foreach ($byPlayer as $id => $gain) {
                $totals[$id] = ($totals[$id] ?? 0) + $gain;
            }
            $best = max(array_map(fn ($id) => $totals[$id], $ids));
            $atBest = array_values(array_filter($ids, fn ($id) => $totals[$id] === $best));

            if ($best > $record) {
                // Les mêmes recordmen qui allongent ensemble leur record ne font pas une nouvelle entrée
                $changed = array_diff($atBest, $holders) || array_diff($holders, $atBest);
                $holders = $atBest;
                $record = $best;
                $last = end($entries);
                if ($changed && $last && $last['equalled'] && $last['players'] === $atBest) {
                    // Record égalé puis battu par le même joueur : une seule entrée, qui garde la date de l'égalisation
                    $entries[array_key_last($entries)] = ['equalledMatch' => $last['match'], 'match' => $matchId, 'value' => $best, 'equalled' => false] + $last;
                } elseif ($changed) {
                    $entries[] = ['players' => $atBest, 'match' => $matchId, 'value' => $best, 'equalled' => false];
                }
            } elseif ($best === $record && ($joining = array_values(array_diff($atBest, $holders)))) {
                $entries[] = ['players' => $joining, 'with' => $holders, 'match' => $matchId, 'value' => $best, 'equalled' => true];
                $holders = array_merge($holders, $joining);
            }
        }

        $players = Player::whereIn('id', collect($entries)->pluck('players')->merge(collect($entries)->pluck('with'))->flatten()->filter()->unique())
            ->get()->keyBy('id');
        $matches = RugbyMatch::with('opponent')
            ->whereIn('id', collect($entries)->pluck('match')->merge(collect($entries)->pluck('equalledMatch')->filter()))
            ->get()->keyBy('id');

        return collect($entries)->map(function (array $entry, int $i) use ($entries, $players, $matches, $totals) {
            // Fin du règne : le premier joueur qui prend seul le record sans faire partie de ces recordmen
            $next = collect($entries)->slice($i + 1)
                ->first(fn ($later) => !$later['equalled'] && !array_intersect($later['players'], $entry['players']));

            return [
                'players' => collect($entry['players'])->map(fn ($id) => $players[$id]),
                'match' => $matches[$entry['match']],
                'value' => $entry['value'],
                'equalled' => $entry['equalled'],
                // Recordmen avec qui le record égalé est partagé
                'with' => collect($entry['with'] ?? [])->map(fn ($id) => $players[$id]),
                'equalledMatch' => isset($entry['equalledMatch']) ? $matches[$entry['equalledMatch']] : null,
                'until' => $next ? $matches[$next['match']] : null,
                'totals' => array_intersect_key($totals, array_flip($entry['players'])),
            ];
        });
    }

    private function recordByYear(int $year): RecordSummary
    {
        return RecordSummary::fromMatches($this->chronology()->filter(fn (RugbyMatch $m) => $m->match_date->year === $year));
    }

    /** @return Builder<RugbyMatch> */
    private function matchQuery(): Builder
    {
        return RugbyMatch::with('opponent');
    }

    /**
     * Classement des joueurs par nombre de lignes (essais, feuilles de match…).
     */
    private function leaderboard(Builder $query, int $limit): Collection
    {
        return $query->with('player')
            ->select('player_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('player_id')
            ->orderByDesc('total')
            ->orderBy('player_id')
            ->limit($limit)
            ->get();
    }

    /**
     * Tous les matches, du plus ancien au plus récent, avec les seules colonnes utiles.
     *
     * @return Collection<int, RugbyMatch>
     */
    private function chronology(): Collection
    {
        return $this->chronology ??= RugbyMatch::with('opponent')
            ->select(['id', 'slug', 'match_date', 'opponent_id', 'france_score', 'opponent_score', 'is_home', 'is_neutral'])
            ->orderBy('match_date')->orderBy('id')
            ->get();
    }

    /**
     * @param  callable(RugbyMatch): bool  $continues
     * @param  Collection<int, RugbyMatch>|null  $matches  sous-ensemble chronologique (tous les matches par défaut)
     * @return array{length: int, from: RugbyMatch, to: RugbyMatch, ongoing: bool}|null
     */
    private function longestStreak(callable $continues, ?Collection $matches = null): ?array
    {
        $matches = ($matches ?? $this->chronology())->values();
        $best = null;
        $start = null;

        foreach ($matches as $i => $match) {
            if (!$continues($match)) {
                $start = null;
                continue;
            }

            $start ??= $i;
            $length = $i - $start + 1;

            if ($best === null || $length > $best['length']) {
                $best = [
                    'length' => $length,
                    'from' => $matches[$start],
                    'to' => $match,
                    'ongoing' => false,
                ];
            }
        }

        if ($best !== null) {
            $best['ongoing'] = $best['to']->is($matches->last());
        }

        return $best;
    }
}
