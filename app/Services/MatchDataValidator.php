<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\PlayerPosition;
use App\Models\Country;

class MatchDataValidator
{
    private array $errors = [];
    private array $warnings = [];

    public function validate(array $data): bool
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateRequiredFields($data);
        $this->validateOpponentCode($data);
        $this->validateScores($data);
        $this->validateLineups($data);
        $this->validateEvents($data);
        $this->validateSubstitutions($data);

        if (empty($this->errors)) {
            $this->validateCoherence($data);
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function warnings(): array
    {
        return $this->warnings;
    }

    private function validateRequiredFields(array $data): void
    {
        if (empty($data['match_date'])) {
            $this->errors[] = 'match_date est requis';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $data['match_date'])) {
            $this->errors[] = "match_date invalide : {$data['match_date']} (format attendu : Y-m-d)";
        }

        if (empty($data['opponent_code'])) {
            $this->errors[] = 'opponent_code est requis';
        } elseif (strlen($data['opponent_code']) !== 3) {
            $this->errors[] = "opponent_code doit faire 3 caractères : {$data['opponent_code']}";
        }
    }

    private function validateOpponentCode(array $data): void
    {
        if (empty($data['opponent_code'])) {
            return;
        }

        if (!Country::where('code', $data['opponent_code'])->exists()) {
            $this->errors[] = "opponent_code inconnu en base : {$data['opponent_code']}";
        }
    }

    private function validateScores(array $data): void
    {
        if (isset($data['france_score']) && (!is_int($data['france_score']) || $data['france_score'] < 0)) {
            $this->errors[] = "france_score invalide : {$data['france_score']}";
        }
        if (isset($data['opponent_score']) && (!is_int($data['opponent_score']) || $data['opponent_score'] < 0)) {
            $this->errors[] = "opponent_score invalide : {$data['opponent_score']}";
        }
    }

    private function validateLineups(array $data): void
    {
        if (!isset($data['lineups'])) {
            return;
        }

        foreach (['france', 'adversaire'] as $side) {
            if (!isset($data['lineups'][$side]) || !is_array($data['lineups'][$side])) {
                continue;
            }

            $jerseys = [];
            foreach ($data['lineups'][$side] as $i => $entry) {
                $prefix = "lineups.{$side}[{$i}]";

                if (!isset($entry['jersey']) || !is_int($entry['jersey']) || $entry['jersey'] < 1 || $entry['jersey'] > 23) {
                    $this->errors[] = "{$prefix}.jersey invalide";
                } elseif (in_array($entry['jersey'], $jerseys)) {
                    $this->errors[] = "{$prefix}.jersey doublon : {$entry['jersey']}";
                } else {
                    $jerseys[] = $entry['jersey'];
                }

                if (empty($entry['last_name'])) {
                    $this->errors[] = "{$prefix}.last_name requis";
                }
                if (!array_key_exists('first_name', $entry)) {
                    $this->errors[] = "{$prefix}.first_name requis";
                }
                if (!isset($entry['is_starter']) || !is_bool($entry['is_starter'])) {
                    $this->errors[] = "{$prefix}.is_starter requis (boolean)";
                }

                if (!empty($entry['position'])) {
                    $valid = array_column(PlayerPosition::cases(), 'value');
                    if (!in_array($entry['position'], $valid)) {
                        $this->errors[] = "{$prefix}.position invalide : {$entry['position']}";
                    }
                }
            }
        }
    }

    private function validateEvents(array $data): void
    {
        if (!isset($data['events']) || !is_array($data['events'])) {
            return;
        }

        $validSides = ['france', 'adversaire'];
        $validTypes = array_column(EventType::cases(), 'value');

        foreach ($data['events'] as $i => $event) {
            $prefix = "events[{$i}]";

            if (empty($event['team_side']) || !in_array($event['team_side'], $validSides)) {
                $this->errors[] = "{$prefix}.team_side invalide";
            }

            if (empty($event['type']) || !in_array($event['type'], $validTypes)) {
                $this->errors[] = "{$prefix}.type invalide : " . ($event['type'] ?? 'null');
            }

            if (($event['type'] ?? '') !== 'essai_penalite') {
                if (empty($event['player_last_name'])) {
                    $this->errors[] = "{$prefix}.player_last_name requis (sauf essai_penalite)";
                }
            }

            if (isset($event['minute']) && $event['minute'] !== null) {
                if (!is_int($event['minute']) || $event['minute'] < 0 || $event['minute'] > 120) {
                    $this->errors[] = "{$prefix}.minute invalide : {$event['minute']}";
                }
            }
        }
    }

    private function validateSubstitutions(array $data): void
    {
        if (!isset($data['substitutions']) || !is_array($data['substitutions'])) {
            return;
        }

        $validSides = ['france', 'adversaire'];

        foreach ($data['substitutions'] as $i => $sub) {
            $prefix = "substitutions[{$i}]";

            if (empty($sub['team_side']) || !in_array($sub['team_side'], $validSides)) {
                $this->errors[] = "{$prefix}.team_side invalide";
            }
            if (empty($sub['player_off_last_name'])) {
                $this->errors[] = "{$prefix}.player_off_last_name requis";
            }
            if (empty($sub['player_on_last_name'])) {
                $this->errors[] = "{$prefix}.player_on_last_name requis";
            }

            if (isset($sub['minute']) && $sub['minute'] !== null) {
                if (!is_int($sub['minute']) || $sub['minute'] < 0 || $sub['minute'] > 120) {
                    $this->errors[] = "{$prefix}.minute invalide : {$sub['minute']}";
                }
            }
        }
    }

    private function validateCoherence(array $data): void
    {
        foreach (['france', 'adversaire'] as $side) {
            $lineup = $data['lineups'][$side] ?? [];
            if (empty($lineup)) {
                continue;
            }

            $starters = array_filter($lineup, fn ($e) => ($e['is_starter'] ?? false) === true);
            $starterCount = count($starters);
            if ($starterCount > 0 && $starterCount !== 15) {
                $this->warnings[] = "{$side} : {$starterCount} titulaires au lieu de 15";
            }
        }

        // Vérifier que les joueurs d'events sont dans les lineups
        $lineupNames = [];
        foreach (['france', 'adversaire'] as $side) {
            foreach ($data['lineups'][$side] ?? [] as $entry) {
                $key = $side . ':' . mb_strtolower(trim($entry['last_name'] ?? ''));
                $lineupNames[$key] = true;
            }
        }

        foreach ($data['events'] ?? [] as $i => $event) {
            if (($event['type'] ?? '') === 'essai_penalite') {
                continue;
            }
            $key = ($event['team_side'] ?? '') . ':' . mb_strtolower(trim($event['player_last_name'] ?? ''));
            if (!empty($event['player_last_name']) && !isset($lineupNames[$key])) {
                $this->warnings[] = "events[{$i}] : joueur {$event['player_last_name']} absent des lineups {$event['team_side']}";
            }
        }
    }
}
