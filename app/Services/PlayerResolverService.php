<?php

namespace App\Services;

use App\Enums\PlayerPosition;
use App\Models\Country;
use App\Models\Player;
use Illuminate\Support\Str;

class PlayerResolverService
{
    private array $log = [];
    private int $createdCount = 0;
    private array $playerCache = [];

    public function resolve(
        string $lastName,
        ?string $firstName,
        Country $country,
        ?PlayerPosition $position = null,
    ): ?Player {
        $cacheKey = $this->cacheKey($lastName, $firstName, $country->id);
        if (isset($this->playerCache[$cacheKey])) {
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

        $exact = $query->get();

        if ($exact->count() === 1) {
            return $this->cache($cacheKey, $exact->first());
        }

        if ($exact->count() > 1) {
            $this->log[] = ['warn', "Joueur ambigu : {$firstName} {$lastName} ({$country->code}) — {$exact->count()} résultats"];
            return null;
        }

        // 2. Recherche fuzzy par nom seul (même pays), en écartant les homonymes
        //    dont le prénom est incompatible (ex: Scott / Beauden / Jordie Barrett)
        $fuzzy = Player::where('country_id', $country->id)
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($lastName))])
            ->get()
            ->filter(fn (Player $p) => $this->firstNamesCompatible($firstNorm, $p->first_name));

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
