<?php

namespace App\Support;

use App\Enums\TeamSide;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Support\Collection;

/**
 * Données d'affichage d'une fiche joueur, calculées à partir des feuilles de match saisies.
 *
 * Chaque match du parcours réutilise la ligne du joueur dans MatchSheet : les chiffres
 * (minutes, essais, points, cartons) sont donc identiques à ceux des feuilles de match.
 */
final class PlayerProfile
{
    /** @var Collection<int, array> */
    public readonly Collection $appearances;

    public function __construct(public readonly Player $player)
    {
        $matches = RugbyMatch::query()
            ->whereHas('lineups', fn ($q) => $q->where('player_id', $player->id))
            ->with(['opponent', 'venue', 'edition.competition', 'lineups.player', 'events.player', 'substitutions'])
            ->orderByDesc('match_date')
            ->get();

        $this->appearances = $matches->map(function (RugbyMatch $match) use ($player) {
            $lineup = $match->lineups->firstWhere('player_id', $player->id);
            $sheet = new MatchSheet($match);
            $lines = $sheet->lineup($lineup->team_side);
            $row = collect([...$lines['starters'], ...$lines['bench']])->first(fn ($r) => $r['player']->is($player));
            $forFrance = $lineup->team_side === TeamSide::FRANCE;

            return [
                'match' => $match,
                'side' => $lineup->team_side,
                'forFrance' => $forFrance,
                'result' => $forFrance ? $match->result : match ($match->result) {
                    'Victoire' => 'Défaite',
                    'Défaite' => 'Victoire',
                    default => 'Nul',
                },
                'row' => $row,
            ];
        });
    }

    public function hasAppearances(): bool
    {
        return $this->appearances->isNotEmpty();
    }

    /**
     * Totaux sur les matches recensés.
     *
     * @return array{matches: int, starts: int, captaincies: int, tries: int, points: int, minutes: ?int, minutesKnown: int, yellow: int, red: int}
     */
    public function totals(): array
    {
        $rows = $this->appearances->pluck('row');
        $withMinutes = $rows->filter(fn ($r) => $r['minutes'] !== null);
        $cards = $rows->flatMap(fn ($r) => $r['cards']);

        return [
            'matches' => $rows->count(),
            'starts' => $rows->where('starter', true)->count(),
            'captaincies' => $rows->where('captain', true)->count(),
            'tries' => $rows->sum('tries'),
            'points' => $rows->sum('points'),
            'minutes' => $withMinutes->isNotEmpty() ? $withMinutes->sum('minutes') : null,
            'minutesKnown' => $withMinutes->count(),
            'yellow' => $cards->where('type', \App\Enums\EventType::CARTON_JAUNE)->count(),
            'red' => $cards->where('type', \App\Enums\EventType::CARTON_ROUGE)->count(),
        ];
    }

    /**
     * Bilan du point de vue de l'équipe du joueur.
     */
    public function record(): RecordSummary
    {
        $results = $this->appearances->countBy('result');

        return new RecordSummary($results['Victoire'] ?? 0, $results['Défaite'] ?? 0, $results['Nul'] ?? 0);
    }

    public function firstAppearance(): ?array
    {
        return $this->appearances->last();
    }

    public function lastAppearance(): ?array
    {
        return $this->appearances->first();
    }

    /**
     * Numéro de maillot le plus porté (pour l'illustration du bandeau).
     */
    public function favouriteJersey(): ?int
    {
        // Les numéros portés comme titulaire priment, puis le plus fréquent
        $starts = $this->appearances->filter(fn ($a) => $a['row']['starter']);
        $pool = $starts->isNotEmpty() ? $starts : $this->appearances;

        return $pool->countBy(fn ($a) => $a['row']['jersey'])->sortDesc()->keys()->first();
    }

    /**
     * Parcours groupé par année, de la plus récente à la plus ancienne.
     *
     * @return Collection<int, Collection<int, array>>
     */
    public function byYear(): Collection
    {
        return $this->appearances->groupBy(fn ($a) => $a['match']->match_date->year);
    }

    /**
     * Postes occupés, du plus fréquent au moins fréquent.
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function positions(): Collection
    {
        return $this->appearances
            ->filter(fn ($a) => $a['row']['position'])
            ->groupBy(fn ($a) => $a['row']['position']->value)
            ->map(fn (Collection $group) => ['label' => $group->first()['row']['position']->label(), 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Adversaires affrontés : pays pour un Français, équipe de France pour un joueur adverse.
     *
     * @return Collection<int, array{name: string, flag: string, code: ?string, count: int, record: RecordSummary}>
     */
    public function opponents(): Collection
    {
        return $this->appearances
            ->groupBy(fn ($a) => $a['forFrance'] ? $a['match']->opponent_id : 'FRA')
            ->map(function (Collection $group) {
                $first = $group->first();
                $results = $group->countBy('result');

                return [
                    'name' => $first['forFrance'] ? $first['match']->opponent->name : 'France',
                    'flag' => $first['forFrance'] ? $first['match']->opponent->flag_emoji : '🇫🇷',
                    'code' => $first['forFrance'] ? $first['match']->opponent->code : null,
                    'count' => $group->count(),
                    'record' => new RecordSummary($results['Victoire'] ?? 0, $results['Défaite'] ?? 0, $results['Nul'] ?? 0),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Coéquipiers les plus souvent présents sur la même feuille de match.
     *
     * @return Collection<int, array{player: Player, count: int}>
     */
    public function teammates(int $limit = 6): Collection
    {
        return $this->appearances
            ->flatMap(fn ($a) => $a['match']->lineups
                ->filter(fn (MatchLineup $l) => $l->team_side === $a['side'] && $l->player_id !== $this->player->id)
                ->pluck('player'))
            ->groupBy('id')
            ->map(fn (Collection $group) => ['player' => $group->first(), 'count' => $group->count()])
            ->sortBy([['count', 'desc'], [fn ($a, $b) => strcmp($a['player']->last_name, $b['player']->last_name)]])
            ->take($limit)
            ->values();
    }
}
