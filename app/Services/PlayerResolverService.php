<?php

namespace App\Services;

use App\Enums\PlayerPosition;
use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\Player;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlayerResolverService
{
    /**
     * Un joueur existant qui n'a pas joué à moins de 14 ans du match est un homonyme d'une autre
     * génération (Tom Voyce 1921 et 2006, Noel Murphy père et fils) : on ne le rattache pas.
     */
    private const HOMONYM_GAP_YEARS = 14;

    private array $log = [];
    private int $createdCount = 0;
    private array $playerCache = [];

    public function resolve(
        string $lastName,
        ?string $firstName,
        Country $country,
        ?PlayerPosition $position = null,
        ?CarbonInterface $matchDate = null,
    ): ?Player {
        $cacheKey = $this->cacheKey($lastName, $firstName, $country->id);
        if (isset($this->playerCache[$cacheKey]) && $this->sameEra($this->playerCache[$cacheKey], $matchDate)) {
            return $this->playerCache[$cacheKey];
        }

        $lastNorm = $this->normalize($lastName);
        $firstNorm = $firstName ? $this->normalize($firstName) : null;

        // 1. Recherche exacte (nom + prénom + pays)
        $query = Player::where('country_id', $country->id)
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($lastName))]);

        if ($firstNorm) {
            $query->whereRaw('LOWER(first_name) = ?', [mb_strtolower(trim($firstName))]);
        }

        $exact = $this->filterEra($query->get(), $matchDate);

        if ($exact->count() === 1) {
            return $this->cache($cacheKey, $exact->first());
        }

        if ($exact->count() > 1) {
            $this->log[] = ['warn', "Joueur ambigu : {$firstName} {$lastName} ({$country->code}) — {$exact->count()} résultats"];
            return null;
        }

        // 2. Recherche fuzzy par nom seul sans accents (même pays, « Jegou » = « Jégou »),
        //    en écartant les homonymes dont le prénom est incompatible (ex: Scott / Beauden / Jordie Barrett)
        $fuzzy = $this->filterEra(Player::where('country_id', $country->id)
            ->get()
            ->filter(fn (Player $p) => $this->normalize($p->last_name) === $lastNorm
                && $this->firstNamesCompatible($firstNorm, $p->first_name)), $matchDate);

        if ($fuzzy->count() === 1) {
            $this->log[] = ['info', "Joueur trouvé par nom seul : {$fuzzy->first()->fullName()} ({$country->code})"];
            return $this->cache($cacheKey, $fuzzy->first());
        }

        if ($fuzzy->count() > 1) {
            $this->log[] = ['warn', "Joueur ambigu (nom seul) : {$lastName} ({$country->code}) — {$fuzzy->count()} résultats"];
            return null;
        }

        // 3. Création
        // Poste inconnu (joueur cité seulement dans un événement) : défaut de la colonne
        // Le slug est généré par le model à la création
        $player = Player::create(array_filter([
            'first_name' => trim($firstName ?? ''),
            'last_name' => trim($lastName),
            'country_id' => $country->id,
            'primary_position' => $position,
            'is_active' => true,
        ], fn ($value) => $value !== null));

        $this->createdCount++;
        $this->log[] = ['info', "Joueur créé : {$player->fullName()} ({$country->code})"];

        return $this->cache($cacheKey, $player);
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function resetLog(): void
    {
        $this->log = [];
    }

    private function filterEra(Collection $players, ?CarbonInterface $matchDate): Collection
    {
        return $players->filter(fn (Player $p) => $this->sameEra($p, $matchDate))->values();
    }

    /** Sans date ou sans match connu, le joueur est retenu ; sinon il faut un match à moins de 14 ans */
    private function sameEra(Player $player, ?CarbonInterface $matchDate): bool
    {
        if (!$matchDate) {
            return true;
        }

        $dates = MatchLineup::where('player_id', $player->id)
            ->join('matches', 'matches.id', '=', 'match_lineups.match_id')
            ->pluck('matches.match_date');

        return $dates->isEmpty() || $dates->contains(
            fn ($date) => abs($matchDate->diffInYears($date)) < self::HOMONYM_GAP_YEARS
        );
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = Str::ascii($value);
        return mb_strtolower($value);
    }

    /**
     * Prénoms compatibles : l'un est absent, ils sont égaux sans accents,
     * ou l'un est l'initiale de l'autre (« B. » / « Beauden »).
     */
    private function firstNamesCompatible(?string $wanted, ?string $existing): bool
    {
        $existing = $existing ? $this->normalize($existing) : '';
        if (!$wanted || $existing === '') {
            return true;
        }

        $wanted = rtrim($wanted, '.');
        $existing = rtrim($existing, '.');
        if ($wanted === $existing) {
            return true;
        }

        $isInitial = fn (string $s) => mb_strlen($s) === 1;

        return ($isInitial($wanted) || $isInitial($existing))
            && mb_substr($wanted, 0, 1) === mb_substr($existing, 0, 1);
    }

    private function cacheKey(string $lastName, ?string $firstName, int $countryId): string
    {
        return mb_strtolower(trim($lastName)) . '|' . mb_strtolower(trim($firstName ?? '')) . '|' . $countryId;
    }

    private function cache(string $key, Player $player): Player
    {
        $this->playerCache[$key] = $player;
        return $player;
    }
}
