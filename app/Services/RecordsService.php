<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;
use Illuminate\Database\Eloquent\Builder;
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

    /** @return Collection<int, RugbyMatch> */
    public function mostPointsScored(int $limit = 5): Collection
    {
        return $this->matchQuery()
            ->orderByDesc('france_score')->orderBy('match_date')
            ->limit($limit)->get();
    }

    /** @return Collection<int, RugbyMatch> */
    public function mostPointsConceded(int $limit = 5): Collection
    {
        return $this->matchQuery()
            ->orderByDesc('opponent_score')->orderBy('match_date')
            ->limit($limit)->get();
    }

    /**
     * Plus longues séries, dans l'ordre chronologique.
     *
     * @return array{wins: ?array, unbeaten: ?array, losses: ?array}
     */
    public function streaks(): array
    {
        return [
            'wins' => $this->longestStreak(fn (RugbyMatch $m) => $m->france_score > $m->opponent_score),
            'unbeaten' => $this->longestStreak(fn (RugbyMatch $m) => $m->france_score >= $m->opponent_score),
            'losses' => $this->longestStreak(fn (RugbyMatch $m) => $m->france_score < $m->opponent_score),
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

    /** @return Collection<int, MatchLineup>  avec l'attribut total */
    public function mostAppearances(int $limit = 10): Collection
    {
        return $this->leaderboard(MatchLineup::where('team_side', TeamSide::FRANCE), $limit);
    }

    /** @return Collection<int, MatchLineup>  avec l'attribut total */
    public function mostCaptaincies(int $limit = 10): Collection
    {
        return $this->leaderboard(
            MatchLineup::where('team_side', TeamSide::FRANCE)->where('is_captain', true),
            $limit,
        );
    }

    // --- Interne ---

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
     * @return array{length: int, from: RugbyMatch, to: RugbyMatch, ongoing: bool}|null
     */
    private function longestStreak(callable $continues): ?array
    {
        $matches = $this->chronology()->values();
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
