<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\MatchStage;
use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use App\Models\Competition;
use App\Models\CompetitionEdition;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\MatchSubstitution;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Models\Venue;
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

    public function importFile(string $path, bool $dryRun, bool $force, bool $skipExisting, bool $changedOnly = false): void
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
            $this->importSingleMatch($matchData, $dryRun, $force, $skipExisting, $changedOnly);
        }
    }

    /**
     * @param  bool  $changedOnly  réimporte le match seulement si son JSON a changé depuis le dernier import
     */
    public function importSingleMatch(array $data, bool $dryRun, bool $force, bool $skipExisting, bool $changedOnly = false): void
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

        if ($match) {
            $this->info("Match trouvé : France {$match->france_score}-{$match->opponent_score} {$country->name} (id: {$match->id})");

            // Vérification score
            if (isset($data['france_score']) && $data['france_score'] !== $match->france_score) {
                $this->warn("Score France diffère : JSON={$data['france_score']} vs BDD={$match->france_score}");
            }
            if (isset($data['opponent_score']) && $data['opponent_score'] !== $match->opponent_score) {
                $this->warn("Score adversaire diffère : JSON={$data['opponent_score']} vs BDD={$match->opponent_score}");
            }
        } else {
            $this->info("Match absent de la base : il sera créé (France {$data['france_score']}-{$data['opponent_score']} {$country->name})");
        }

        // Étape 2 — Vérification données existantes
        $hasLineups = $match?->lineups()->exists() ?? false;
        $hasEvents = $match?->events()->exists() ?? false;
        $hasSubs = $match?->substitutions()->exists() ?? false;
        $hasExisting = $hasLineups || $hasEvents || $hasSubs;

        // Empreinte indépendante de la mise en forme du fichier
        $checksum = hash('sha256', json_encode($data));

        if ($changedOnly) {
            if ($hasExisting && $match?->source_checksum === $checksum) {
                $this->info("Feuille de match inchangée — skippée (--changed)");
                $this->matchesSkipped++;
                return;
            }
            $force = true;
        }

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

        DB::transaction(function () use ($match, $data, $country, $force, $hasLineups, $hasEvents, $hasSubs, $checksum) {
            if (!$match) {
                $match = $this->createMatch($data, $country);
            } else {
                $this->applyMatchContext($match, $data, $country);
            }

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

            $this->importMatchInfo($match, $data);

            $match->forceFill(['source_checksum' => $checksum])->saveQuietly();
        });

        // Collect player resolver logs
        foreach ($this->playerResolver->getLog() as [$level, $message]) {
            $this->{$level}($message);
        }
        $this->playerResolver->resetLog();

        $this->matchesImported++;
    }

    private function createMatch(array $data, Country $opponent): RugbyMatch
    {
        $match = new RugbyMatch([
            'match_date' => $data['match_date'],
            'opponent_id' => $opponent->id,
            'france_score' => $data['france_score'],
            'opponent_score' => $data['opponent_score'],
        ]);
        $this->applyMatchContext($match, $data, $opponent);
        $match->save();

        $this->info("Match créé : {$match->slug} (id: {$match->id})");

        return $match;
    }

    /**
     * Stade, compétition, phase : seuls les champs présents dans le JSON sont écrits.
     * Domicile et terrain neutre se déduisent du pays du stade.
     */
    private function applyMatchContext(RugbyMatch $match, array $data, Country $opponent): void
    {
        if (isset($data['venue'])) {
            $venue = Venue::where('name', $data['venue'])->first();
            if (!$venue) {
                $venue = Venue::create([
                    'name' => $data['venue'],
                    'city' => $data['venue_city'],
                    'country_id' => Country::where('code', $data['venue_country_code'])->value('id'),
                ]);
                $this->warn("Stade créé : {$venue->name}, {$venue->city} (coordonnées à ajouter dans venue_coordinates.csv)");
            }

            $france = $this->getFranceCountry();
            $match->venue_id = $venue->id;
            $match->is_home = $venue->country_id === $france->id;
            $match->is_neutral = $venue->country_id !== null
                && !in_array($venue->country_id, [$france->id, $opponent->id], true);
        }

        if (isset($data['competition'])) {
            $competition = Competition::where('short_name', $data['competition'])->firstOrFail();
            $year = (int) substr($data['match_date'], 0, 4);
            $match->edition_id = CompetitionEdition::firstOrCreate(
                ['competition_id' => $competition->id, 'year' => $year],
                ['label' => "{$competition->name} {$year}"],
            )->id;
        }

        if (isset($data['stage'])) {
            $match->stage = MatchStage::from($data['stage']);
        }

        if ($match->exists && $match->isDirty()) {
            $this->info('Contexte du match mis à jour : ' . implode(', ', array_keys($match->getDirty())));
        }
    }

    /**
     * Arbitre, affluence, coup d'envoi, météo, résumé vidéo : seuls les champs présents dans le JSON
     * sont écrits, pour ne pas effacer ce qui vient d'une autre source (CSV, saisie admin).
     */
    private function importMatchInfo(RugbyMatch $match, array $data): void
    {
        $info = [];

        foreach (['referee', 'attendance', 'weather', 'video_url', 'video_embed_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $info[$field] = $data[$field];
            }
        }

        if (array_key_exists('kickoff_time', $data)) {
            $info['kickoff_time'] = $data['kickoff_time'] !== null ? $data['kickoff_time'] . ':00' : null;
        }

        if (array_key_exists('referee_country_code', $data)) {
            $info['referee_country_id'] = $data['referee_country_code'] !== null
                ? Country::where('code', $data['referee_country_code'])->value('id')
                : null;
        }

        if ($info === []) {
            return;
        }

        $match->forceFill($info);
        if ($match->isDirty()) {
            $this->info('Champs de match mis à jour : ' . implode(', ', array_keys($match->getDirty())));
        }
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
