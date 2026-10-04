<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\PlayerPosition;
use App\Models\Country;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        $this->validateMatchInfo($data);
        $this->validateLineups($data);
        $this->validateEvents($data);
        $this->validateSubstitutions($data);

        if (empty($this->errors)) {
            $this->validateCoherence($data);
            $this->validateScoreMatchesEvents($data);
            $this->validateSubstitutionFlow($data);
            $this->warnAboutNearNames($data);
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

    /**
     * Champs de match optionnels : arbitre, affluence, coup d'envoi (heure locale), météo.
     */
    private function validateMatchInfo(array $data): void
    {
        foreach (['referee', 'weather'] as $field) {
            if (isset($data[$field]) && (!is_string($data[$field]) || trim($data[$field]) === '')) {
                $this->errors[] = "{$field} doit être un texte non vide";
            }
        }

        if (isset($data['weather']) && is_string($data['weather']) && mb_strlen($data['weather']) > 100) {
            $this->errors[] = 'weather ne doit pas dépasser 100 caractères';
        }

        if (isset($data['attendance']) && (!is_int($data['attendance']) || $data['attendance'] <= 0)) {
            $this->errors[] = 'attendance doit être un entier positif';
        }

        if (isset($data['kickoff_time'])
            && (!is_string($data['kickoff_time']) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $data['kickoff_time']))) {
            $this->errors[] = "kickoff_time invalide (format attendu : HH:MM, heure locale)";
        }

        if (isset($data['referee_country_code'])) {
            if (!isset($data['referee'])) {
                $this->errors[] = 'referee_country_code sans referee';
            }
            if (!is_string($data['referee_country_code']) || !Country::where('code', $data['referee_country_code'])->exists()) {
                $this->errors[] = 'referee_country_code inconnu en base : ' . json_encode($data['referee_country_code']);
            }
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
    /**
     * La somme des points des événements doit donner le score final, selon le barème
     * en vigueur à la date du match. Ne s'applique que si des événements de score sont saisis.
     */
    private function validateScoreMatchesEvents(array $data): void
    {
        $scoring = array_filter($data['events'] ?? [], fn ($e) => EventType::from($e['type'])->points() > 0);
        if (empty($scoring)) {
            return;
        }

        $date = Carbon::parse($data['match_date']);
        foreach (['france' => 'france_score', 'adversaire' => 'opponent_score'] as $side => $scoreKey) {
            $points = array_sum(array_map(
                fn ($e) => EventType::from($e['type'])->points($date),
                array_filter($scoring, fn ($e) => $e['team_side'] === $side),
            ));

            if ($points !== (int) $data[$scoreKey]) {
                $this->errors[] = "{$side} : les événements totalisent {$points} points pour un score de {$data[$scoreKey]}";
            }
        }
    }

    /**
     * Remplacements : les deux joueurs figurent sur la feuille, l'entrant est un remplaçant,
     * le sortant est sur le terrain à ce moment-là (sinon simple avertissement : un remplacement
     * temporaire peut faire revenir un joueur).
     */
    private function validateSubstitutionFlow(array $data): void
    {
        foreach (['france', 'adversaire'] as $side) {
            $lineup = $data['lineups'][$side] ?? [];
            $byName = [];
            foreach ($lineup as $entry) {
                $byName[$this->nameKey($entry['first_name'] ?? '', $entry['last_name'])] = $entry;
            }

            $onField = array_keys(array_filter($byName, fn ($e) => $e['is_starter']));
            $subs = array_values(array_filter($data['substitutions'] ?? [], fn ($s) => $s['team_side'] === $side));
            usort($subs, fn ($a, $b) => ($a['minute'] ?? 999) <=> ($b['minute'] ?? 999));

            // Prénom facultatif dans les remplacements : on retombe alors sur le nom seul
            $find = function (?string $first, string $last) use ($byName): string {
                $key = $this->nameKey($first ?? '', $last);
                if (isset($byName[$key]) || $first) {
                    return $key;
                }
                $matches = array_keys(array_filter($byName, fn ($e) => Str::lower(Str::ascii(trim($e['last_name']))) === Str::lower(Str::ascii(trim($last)))));

                return count($matches) === 1 ? $matches[0] : $key;
            };

            foreach ($subs as $sub) {
                $off = $find($sub['player_off_first_name'] ?? null, $sub['player_off_last_name']);
                $on = $find($sub['player_on_first_name'] ?? null, $sub['player_on_last_name']);
                $label = "{$side} : remplacement {$sub['player_off_last_name']} → {$sub['player_on_last_name']}";

                if (empty($lineup)) {
                    continue;
                }
                if (!isset($byName[$off]) || !isset($byName[$on])) {
                    $this->errors[] = "{$label} : joueur absent de la composition";
                    continue;
                }
                if ($byName[$on]['is_starter'] && !in_array($on, $onField, true) && $byName[$on]['jersey'] <= 15) {
                    $this->warnings[] = "{$label} : l'entrant est un titulaire qui revient";
                } elseif (!$byName[$on]['is_starter'] && $byName[$on]['jersey'] < 16) {
                    $this->errors[] = "{$label} : l'entrant n'est pas un remplaçant";
                }
                if (!in_array($off, $onField, true)) {
                    $this->warnings[] = "{$label} : le sortant n'est pas sur le terrain à ce moment-là";
                }

                $onField = array_values(array_diff($onField, [$off]));
                $onField[] = $on;
            }
        }
    }

    /**
     * Joueur inconnu en base dont le nom ressemble à celui d'un joueur existant du même pays
     * (accents, homonyme au prénom différent, faute de frappe) : risque de doublon.
     */
    private function warnAboutNearNames(array $data): void
    {
        $countries = [
            'france' => Country::where('code', 'FRA')->value('id'),
            'adversaire' => Country::where('code', strtoupper($data['opponent_code']))->value('id'),
        ];

        foreach ($countries as $side => $countryId) {
            if (!$countryId) {
                continue;
            }
            $known = Player::where('country_id', $countryId)->get(['first_name', 'last_name']);
            $exact = $known->mapWithKeys(fn ($p) => [mb_strtolower($p->first_name . '|' . $p->last_name) => true]);

            foreach ($data['lineups'][$side] ?? [] as $entry) {
                $first = $entry['first_name'] ?? '';
                if (isset($exact[mb_strtolower($first . '|' . $entry['last_name'])])) {
                    continue;
                }

                $wanted = $this->nameKey('', $entry['last_name']);
                $near = $known->filter(fn ($p) => levenshtein($this->nameKey('', $p->last_name), $wanted) <= 1);
                foreach ($near as $p) {
                    $this->warnings[] = "{$side} : {$first} {$entry['last_name']} sera créé, mais {$p->first_name} {$p->last_name} existe déjà";
                }
            }
        }
    }

    private function nameKey(string $first, string $last): string
    {
        return Str::lower(Str::ascii(trim($first) . '|' . trim($last)));
    }
}
