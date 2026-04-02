<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\MatchSubstitution;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchImportService
{
    private PlayerResolverService $playerResolver;
    private MatchDataValidator $validator;

    private int $matchesProcessed = 0;
    private int $matchesImported = 0;
    private int $matchesSkipped = 0;
    private int $warningCount = 0;
    private int $errorCount = 0;
    private array $messages = [];

    private ?Country $franceCountry = null;

    public function __construct(PlayerResolverService $playerResolver, MatchDataValidator $validator)
    {
        $this->playerResolver = $playerResolver;
        $this->validator = $validator;
    }

    public function importFile(string $path, bool $dryRun, bool $force, bool $skipExisting): void
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Fichier JSON invalide : {$path} — " . json_last_error_msg());
            return;
        }

        // Multi-match (array of objects) or single match (object)?
        if (isset($data['match_date'])) {
            $matches = [$data];
        } elseif (is_array($data) && isset($data[0])) {
            $matches = $data;
        } else {
            $this->error("Format JSON non reconnu dans : {$path}");
            return;
        }

        foreach ($matches as $matchData) {
            $this->importSingleMatch($matchData, $dryRun, $force, $skipExisting);
        }
    }

    public function importSingleMatch(array $data, bool $dryRun, bool $force, bool $skipExisting): void
    {
        $this->matchesProcessed++;
        $label = ($data['match_date'] ?? '?') . ' vs ' . ($data['opponent_code'] ?? '?');

        $this->info("=== Import match {$label} ===");

        // Validation
        if (!$this->validator->validate($data)) {
            foreach ($this->validator->errors() as $err) {
                $this->error($err);
            }
            return;
        }
        foreach ($this->validator->warnings() as $warn) {
            $this->warn($warn);
        }

        // Étape 1 — Résolution du match
        $country = Country::where('code', $data['opponent_code'])->first();
        if (!$country) {
            $this->warn("Pays inconnu : {$data['opponent_code']} — match skippé");
            $this->matchesSkipped++;
            return;
        }

        $match = RugbyMatch::whereDate('match_date', $data['match_date'])
            ->where('opponent_id', $country->id)
            ->first();

        if (!$match) {
            $this->warn("Match non trouvé en base : {$label} — skippé");
            $this->matchesSkipped++;
            return;
        }

        $this->info("Match trouvé : France {$match->france_score}-{$match->opponent_score} {$country->name} (id: {$match->id})");

        // Vérification score
        if (isset($data['france_score']) && $data['france_score'] !== $match->france_score) {
            $this->warn("Score France diffère : JSON={$data['france_score']} vs BDD={$match->france_score}");
        }
        if (isset($data['opponent_score']) && $data['opponent_score'] !== $match->opponent_score) {
            $this->warn("Score adversaire diffère : JSON={$data['opponent_score']} vs BDD={$match->opponent_score}");
        }

        // Étape 2 — Vérification données existantes
        $hasLineups = $match->lineups()->count() > 0;
        $hasEvents = $match->events()->count() > 0;
        $hasSubs = $match->substitutions()->count() > 0;
        $hasExisting = $hasLineups || $hasEvents || $hasSubs;

        if ($hasExisting && $skipExisting) {
            $this->info("Match déjà rempli — skippé (--skip-existing)");
            $this->matchesSkipped++;
            return;
        }

        if ($hasExisting && !$force) {
            $this->info("Match déjà rempli — skippé (utiliser --force pour écraser)");
            $this->matchesSkipped++;
            return;
        }

        if ($dryRun) {
            $this->info("[DRY-RUN] Import simulé pour {$label}");
            $this->matchesImported++;
            return;
        }

        DB::transaction(function () use ($match, $data, $country, $force, $hasLineups, $hasEvents, $hasSubs) {
            // Suppression des données existantes si --force
            if ($force) {
                if ($hasLineups) {
                    $match->lineups()->delete();
                }
                if ($hasEvents) {
                    $match->events()->delete();
                }
                if ($hasSubs) {
                    $match->substitutions()->delete();
                }
                if ($hasLineups || $hasEvents || $hasSubs) {
                    $this->info("Données existantes supprimées (--force)");
                }
            }

            $france = $this->getFranceCountry();

            // Étape 3+4 — Lineups
            $this->importLineups($match, $data['lineups'] ?? [], $france, $country);

            // Étape 5 — Events
            $this->importEvents($match, $data['events'] ?? [], $france, $country);

            // Étape 6 — Substitutions
            $this->importSubstitutions($match, $data['substitutions'] ?? [], $france, $country);
        });

        // Collect player resolver logs
        foreach ($this->playerResolver->getLog() as [$level, $message]) {
            $this->{$level}($message);
        }
        $this->playerResolver->resetLog();

        $this->matchesImported++;
    }

    private function importLineups(RugbyMatch $match, array $lineups, Country $france, Country $opponent): void
    {
        foreach (['france', 'adversaire'] as $side) {
            $entries = $lineups[$side] ?? [];
            if (empty($entries)) {
                continue;
            }

            $teamSide = $side === 'france' ? TeamSide::FRANCE : TeamSide::ADVERSAIRE;
            $playerCountry = $side === 'france' ? $france : $opponent;
            $starterCount = 0;
            $subCount = 0;

            foreach ($entries as $entry) {
                $position = !empty($entry['position'])
                    ? PlayerPosition::tryFrom($entry['position'])
                    : null;

                $player = $this->playerResolver->resolve(
                    $entry['last_name'],
                    $entry['first_name'] ?? null,
                    $playerCountry,
                    $position,
                );

                if (!$player) {
                    continue;
                }

                MatchLineup::create([
                    'match_id' => $match->id,
                    'player_id' => $player->id,
                    'jersey_number' => $entry['jersey'],
                    'is_starter' => $entry['is_starter'],
                    'position_played' => $position,
                    'is_captain' => $entry['is_captain'] ?? false,
                    'team_side' => $teamSide,
                ]);

                if ($entry['is_starter']) {
                    $starterCount++;
                } else {
                    $subCount++;
                }
            }

            $sideLabel = $side === 'france' ? 'France' : $opponent->name;
            $this->info("{$starterCount} titulaires + {$subCount} remplaçants {$sideLabel} importés");
        }
    }

    private function importEvents(RugbyMatch $match, array $events, Country $france, Country $opponent): void
    {
        if (empty($events)) {
            return;
        }

        $count = 0;
        foreach ($events as $event) {
            $teamSide = TeamSide::from($event['team_side']);
            $eventType = EventType::from($event['type']);

            $player = null;
            if ($eventType !== EventType::ESSAI_PENALITE && !empty($event['player_last_name'])) {
                $playerCountry = $teamSide === TeamSide::FRANCE ? $france : $opponent;
                $player = $this->playerResolver->resolve(
                    $event['player_last_name'],
                    $event['player_first_name'] ?? null,
                    $playerCountry,
                );

                if (!$player) {
                    $this->warn("Event skippé : joueur non résolu {$event['player_last_name']}");
                    continue;
                }
            }

            MatchEvent::create([
                'match_id' => $match->id,
                'player_id' => $player?->id,
                'event_type' => $eventType,
                'minute' => $event['minute'] ?? null,
                'team_side' => $teamSide,
            ]);
            $count++;
        }

        $this->info("{$count} événements importés");
    }

    private function importSubstitutions(RugbyMatch $match, array $substitutions, Country $france, Country $opponent): void
    {
        if (empty($substitutions)) {
            return;
        }

        $count = 0;
        foreach ($substitutions as $sub) {
            $teamSide = TeamSide::from($sub['team_side']);
            $playerCountry = $teamSide === TeamSide::FRANCE ? $france : $opponent;

            $playerOff = $this->playerResolver->resolve(
                $sub['player_off_last_name'],
                $sub['player_off_first_name'] ?? null,
                $playerCountry,
            );
            $playerOn = $this->playerResolver->resolve(
                $sub['player_on_last_name'],
                $sub['player_on_first_name'] ?? null,
                $playerCountry,
            );

            if (!$playerOff || !$playerOn) {
                $this->warn("Substitution skippée : joueur non résolu");
                continue;
            }

            MatchSubstitution::create([
                'match_id' => $match->id,
                'player_off_id' => $playerOff->id,
                'player_on_id' => $playerOn->id,
                'minute' => $sub['minute'] ?? null,
                'is_tactical' => $sub['is_tactical'] ?? true,
                'team_side' => $teamSide,
            ]);
            $count++;
        }

        $this->info("{$count} remplacements importés");
    }

    private function getFranceCountry(): Country
    {
        if (!$this->franceCountry) {
            $this->franceCountry = Country::where('code', 'FRA')->firstOrFail();
        }
        return $this->franceCountry;
    }

    // --- Logging ---

    private function info(string $message): void
    {
        $this->messages[] = ['info', $message];
        Log::channel('import')->info($message);
    }

    private function warn(string $message): void
    {
        $this->warningCount++;
        $this->messages[] = ['warn', $message];
        Log::channel('import')->warning($message);
    }

    private function error(string $message): void
    {
        $this->errorCount++;
        $this->messages[] = ['error', $message];
        Log::channel('import')->error($message);
    }

    // --- Getters ---

    public function flushMessages(): array
    {
        $messages = $this->messages;
        $this->messages = [];
        return $messages;
    }

    public function getMatchesProcessed(): int
    {
        return $this->matchesProcessed;
    }

    public function getMatchesImported(): int
    {
        return $this->matchesImported;
    }

    public function getMatchesSkipped(): int
    {
        return $this->matchesSkipped;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getPlayersCreated(): int
    {
        return $this->playerResolver->getCreatedCount();
    }
}
