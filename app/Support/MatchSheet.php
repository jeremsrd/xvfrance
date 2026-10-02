<?php

namespace App\Support;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\MatchSubstitution;
use App\Models\RugbyMatch;
use Illuminate\Support\Collection;

/**
 * Données d'affichage d'une feuille de match : marqueurs, compositions,
 * chronologie et score minute par minute.
 *
 * Le match doit avoir ses relations lineups.player, events.player et
 * substitutions.playerOff / playerOn chargées.
 */
final class MatchSheet
{
    /** Actions qui rapportent des points, dans l'ordre d'affichage des résumés */
    public const SCORING_TYPES = [
        EventType::ESSAI,
        EventType::ESSAI_PENALITE,
        EventType::TRANSFORMATION,
        EventType::PENALITE,
        EventType::DROP,
    ];

    /** Durée réglementaire d'un match (hors prolongations) */
    public const MATCH_LENGTH = 80;

    private ?array $timeline = null;

    public function __construct(public readonly RugbyMatch $match)
    {
    }

    public function hasLineups(): bool
    {
        return $this->match->lineups->isNotEmpty();
    }

    public function hasEvents(): bool
    {
        return $this->match->events->isNotEmpty();
    }

    public function isDetailed(): bool
    {
        return $this->hasLineups() || $this->hasEvents();
    }

    /**
     * Les deux équipes, domicile à gauche.
     *
     * @return list<array{side: TeamSide, name: string, flag: string, score: int, isFrance: bool}>
     */
    public function teams(): array
    {
        $france = ['side' => TeamSide::FRANCE, 'name' => 'France', 'flag' => '🇫🇷', 'score' => $this->match->france_score, 'isFrance' => true];
        $opponent = [
            'side' => TeamSide::ADVERSAIRE,
            'name' => $this->match->opponent->name,
            'flag' => $this->match->opponent->flag_emoji,
            'score' => $this->match->opponent_score,
            'isFrance' => false,
        ];

        return $this->match->is_home ? [$france, $opponent] : [$opponent, $france];
    }

    /**
     * Marqueurs d'une équipe, groupés par type d'action.
     *
     * @return list<array{type: EventType, label: string, scorers: list<array{name: string, player: ?\App\Models\Player, minutes: list<int>}>}>
     */
    public function scorers(TeamSide $side): array
    {
        $events = $this->match->events->filter(fn (MatchEvent $e) => $e->team_side === $side);
        $groups = [];

        foreach (self::SCORING_TYPES as $type) {
            $ofType = $events->filter(fn (MatchEvent $e) => $e->event_type === $type)->sortBy(fn ($e) => $e->minute ?? 999);
            if ($ofType->isEmpty()) {
                continue;
            }

            $scorers = $ofType->groupBy(fn (MatchEvent $e) => $e->player_id ?? 0)
                ->map(fn (Collection $group) => [
                    'name' => $group->first()->player ? $this->shortName($group->first()->player, $side) : 'Essai de pénalité',
                    'player' => $group->first()->player,
                    'minutes' => $group->pluck('minute')->filter(fn ($m) => $m !== null)->values()->all(),
                    'count' => $group->count(),
                ])
                ->values()
                ->all();

            $groups[] = [
                'type' => $type,
                'label' => $ofType->count() > 1 ? $this->pluralLabel($type) : $type->label(),
                'scorers' => $scorers,
            ];
        }

        return $groups;
    }

    /**
     * Composition d'une équipe : titulaires et remplaçants, avec les faits de jeu de chaque joueur.
     *
     * @return array{starters: list<array>, bench: list<array>}
     */
    public function lineup(TeamSide $side): array
    {
        $subs = $this->match->substitutions->filter(fn (MatchSubstitution $s) => $s->team_side === $side);
        $events = $this->match->events->filter(fn (MatchEvent $e) => $e->team_side === $side && $e->player_id);

        $players = $this->match->lineups->filter(fn (MatchLineup $l) => $l->team_side === $side);
        // Remplacements complets si chaque remplaçant de la feuille a une entrée saisie
        $subsComplete = $players->where('is_starter', false)->isNotEmpty()
            && $players->where('is_starter', false)->every(fn (MatchLineup $l) => $subs->contains('player_on_id', $l->player_id));

        $rows = $players
            ->sortBy('jersey_number')
            ->map(function (MatchLineup $l) use ($subs, $events, $subsComplete) {
                $own = $events->where('player_id', $l->player_id);
                $on = $l->is_starter ? 0 : $subs->firstWhere('player_on_id', $l->player_id)?->minute;
                $off = $subs->firstWhere('player_off_id', $l->player_id)?->minute
                    ?? $own->firstWhere('event_type', EventType::CARTON_ROUGE)?->minute;

                return [
                    'jersey' => $l->jersey_number,
                    'player' => $l->player,
                    'position' => $l->position_played,
                    'captain' => $l->is_captain,
                    'starter' => $l->is_starter,
                    'on' => $subs->firstWhere('player_on_id', $l->player_id)?->minute,
                    'off' => $subs->firstWhere('player_off_id', $l->player_id)?->minute,
                    'minutes' => $this->minutesPlayed($l->is_starter, $on, $off, $subsComplete),
                    'tries' => $own->where('event_type', EventType::ESSAI)->count(),
                    'points' => $own->sum(fn (MatchEvent $e) => $e->event_type->points($this->match->match_date)),
                    'cards' => $own->filter(fn ($e) => in_array($e->event_type, [EventType::CARTON_JAUNE, EventType::CARTON_ROUGE]))
                        ->map(fn ($e) => ['type' => $e->event_type, 'minute' => $e->minute])->values()->all(),
                ];
            });

        return [
            'starters' => $rows->where('starter', true)->values()->all(),
            'bench' => $rows->where('starter', false)->values()->all(),
        ];
    }

    /**
     * Minutes jouées, uniquement quand elles sont certaines (null sinon).
     * Un remplaçant sans entrée saisie n'a pas joué si les remplacements sont complets.
     */
    private function minutesPlayed(bool $starter, ?int $on, ?int $off, bool $subsComplete): ?int
    {
        $end = self::MATCH_LENGTH;

        if (!$starter && $on === null) {
            return $subsComplete ? 0 : null;
        }
        if ($off !== null) {
            return max(0, min($off, $end) - ($on ?? 0));
        }
        if (!$starter) {
            return max(0, $end - $on);
        }

        return $subsComplete ? $end : null;
    }

    /**
     * Chronologie : actions et remplacements fusionnés, dans l'ordre des minutes.
     * Le score courant n'est fourni que si la chronologie est complète.
     *
     * @return list<array{minute: ?int, side: TeamSide, type: string, label: string, event: ?MatchEvent, sub: ?MatchSubstitution, score: ?array{0: int, 1: int}}>
     */
    public function timeline(): array
    {
        if ($this->timeline !== null) {
            return $this->timeline;
        }

        $items = collect();

        foreach ($this->match->events as $event) {
            $items->push([
                'minute' => $event->minute,
                'side' => $event->team_side,
                'type' => $event->event_type->value,
                'label' => $event->event_type->label(),
                'event' => $event,
                'sub' => null,
                'order' => 1,
            ]);
        }

        foreach ($this->match->substitutions as $sub) {
            $items->push([
                'minute' => $sub->minute,
                'side' => $sub->team_side,
                'type' => 'remplacement',
                'label' => 'Remplacement',
                'event' => null,
                'sub' => $sub,
                'order' => 2,
            ]);
        }

        // Minutes inconnues en fin de liste ; à minute égale, l'action avant le remplacement
        $items = $items->sortBy([fn ($a, $b) => ($a['minute'] ?? 999) <=> ($b['minute'] ?? 999), ['order', 'asc']])->values();

        $complete = $this->isScoreComplete();
        $france = $opponent = 0;

        return $this->timeline = $items->map(function (array $item) use ($complete, &$france, &$opponent) {
            if ($item['event']) {
                $points = $item['event']->event_type->points($this->match->match_date);
                $item['side'] === TeamSide::FRANCE ? $france += $points : $opponent += $points;
            }
            $item['score'] = $complete && $item['event'] && $item['event']->event_type->points($this->match->match_date) > 0
                ? [$france, $opponent]
                : null;
            unset($item['order']);

            return $item;
        })->all();
    }

    /**
     * Score reconstitué à partir des actions saisies : [france, adversaire].
     *
     * @return array{0: int, 1: int}
     */
    public function computedScore(): array
    {
        $score = [0, 0];
        foreach ($this->match->events as $event) {
            $score[$event->team_side === TeamSide::FRANCE ? 0 : 1] += $event->event_type->points($this->match->match_date);
        }

        return $score;
    }

    /**
     * Vrai si les actions saisies redonnent exactement le score final (et ont toutes une minute).
     */
    public function isScoreComplete(): bool
    {
        return $this->hasEvents()
            && $this->computedScore() === [$this->match->france_score, $this->match->opponent_score]
            && $this->match->events->every(fn (MatchEvent $e) => $e->minute !== null);
    }

    /**
     * Score à la mi-temps, uniquement si la chronologie est complète.
     *
     * @return array{0: int, 1: int}|null
     */
    public function halfTimeScore(): ?array
    {
        if (!$this->isScoreComplete()) {
            return null;
        }

        $score = [0, 0];
        foreach ($this->match->events as $event) {
            if ($event->minute <= 40) {
                $score[$event->team_side === TeamSide::FRANCE ? 0 : 1] += $event->event_type->points($this->match->match_date);
            }
        }

        return $score;
    }

    /**
     * Nom court : nom de famille, précédé de l'initiale si deux joueurs de l'équipe le partagent.
     */
    public function shortName(\App\Models\Player $player, TeamSide $side): string
    {
        $homonyms = $this->match->lineups
            ->filter(fn (MatchLineup $l) => $l->team_side === $side && $l->player && $l->player->last_name === $player->last_name)
            ->count();

        return $homonyms > 1 && $player->first_name !== ''
            ? mb_substr($player->first_name, 0, 1) . '. ' . $player->last_name
            : $player->last_name;
    }

    private function pluralLabel(EventType $type): string
    {
        return match ($type) {
            EventType::ESSAI => 'Essais',
            EventType::ESSAI_PENALITE => 'Essais de pénalité',
            EventType::TRANSFORMATION => 'Transformations',
            EventType::PENALITE => 'Pénalités',
            EventType::DROP => 'Drops',
            default => $type->label(),
        };
    }
}
