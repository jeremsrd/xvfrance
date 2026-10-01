<?php

namespace App\Support;

use App\Models\RugbyMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Bilan victoires / défaites / nuls du XV de France sur un ensemble de matches.
 */
final class RecordSummary
{
    private const AGGREGATES = 'COUNT(*) AS total,'
        . ' SUM(CASE WHEN france_score > opponent_score THEN 1 ELSE 0 END) AS wins,'
        . ' SUM(CASE WHEN france_score < opponent_score THEN 1 ELSE 0 END) AS losses,'
        . ' SUM(CASE WHEN france_score = opponent_score THEN 1 ELSE 0 END) AS draws';

    public readonly int $total;
    public readonly float $winPct;

    public function __construct(
        public readonly int $wins = 0,
        public readonly int $losses = 0,
        public readonly int $draws = 0,
    ) {
        $this->total = $wins + $losses + $draws;
        $this->winPct = $this->total > 0 ? round($wins / $this->total * 100, 1) : 0.0;
    }

    /**
     * Pourcentage de victoires au format français : « 20,9 % ».
     */
    public function winPctLabel(int $decimals = 1): string
    {
        return number_format($this->winPct, $decimals, ',', "\u{202F}") . "\u{00A0}%";
    }

    /**
     * Bilan calculé sur des matches déjà chargés en mémoire.
     *
     * @param  iterable<RugbyMatch>  $matches
     */
    public static function fromMatches(iterable $matches): self
    {
        $wins = $losses = $draws = 0;

        foreach ($matches as $match) {
            match (true) {
                $match->france_score > $match->opponent_score => $wins++,
                $match->france_score < $match->opponent_score => $losses++,
                default => $draws++,
            };
        }

        return new self($wins, $losses, $draws);
    }

    /**
     * Bilan calculé en une seule requête SQL agrégée.
     *
     * @param  Builder<RugbyMatch>  $query
     */
    public static function fromQuery(Builder $query): self
    {
        $row = (clone $query)->toBase()->reorder()->selectRaw(self::AGGREGATES)->first();

        return self::fromRow($row);
    }

    /**
     * Bilans groupés par colonne (ex: opponent_id), en une seule requête.
     *
     * @param  Builder<RugbyMatch>  $query
     * @return Collection<int|string, self>
     */
    public static function groupedBy(Builder $query, string $column): Collection
    {
        return (clone $query)->toBase()
            ->reorder()
            ->select($column)
            ->selectRaw(self::AGGREGATES)
            ->groupBy($column)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->{$column} => self::fromRow($row)]);
    }

    private static function fromRow(?object $row): self
    {
        return new self(
            (int) ($row->wins ?? 0),
            (int) ($row->losses ?? 0),
            (int) ($row->draws ?? 0),
        );
    }
}
