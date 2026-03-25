<?php

namespace App\Console\Commands;

use App\Enums\EventType;
use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;

class SeedMatch1906 extends Command
{
    protected $signature = 'seed:match-1906 {--fresh : Supprimer les données existantes du match avant de seeder}';
    protected $description = 'Seed le premier match de l\'histoire du XV de France : France - Nouvelle-Zélande, 1er janvier 1906';

    private ?RugbyMatch $match = null;
    private array $playerCache = [];

    public function handle(): int
    {
        $this->info('=== Seed du match France - Nouvelle-Zélande, 1er janvier 1906 ===');
        $this->newLine();

        // Retrouver le match
        $nz = Country::where('code', 'NZL')->firstOrFail();
        $this->match = RugbyMatch::whereDate('match_date', '1906-01-01')
            ->where('opponent_id', $nz->id)
            ->first();

        if (!$this->match) {
            $this->error('Match du 01/01/1906 vs NZL introuvable en base. Lancez d\'abord import:historical --year=1906');
            return self::FAILURE;
        }

        $this->info("Match trouvé : id={$this->match->id}, France {$this->match->france_score} - {$this->match->opponent_score} Nouvelle-Zélande");

        // Option --fresh : supprimer les données existantes
        if ($this->option('fresh')) {
            $this->match->lineups()->delete();
            $this->match->events()->delete();
            $this->warn('Données existantes (lineups + events) supprimées.');
        }

        // Vérifier qu'il n'y a pas déjà des lineups
        if ($this->match->lineups()->exists()) {
            $this->warn('Ce match a déjà des compositions. Utilisez --fresh pour réinitialiser.');
            return self::FAILURE;
        }

        $france = Country::where('code', 'FRA')->firstOrFail();
        $angleterre = Country::where('code', 'ENG')->firstOrFail();
        $usa = Country::where('code', 'USA')->firstOrFail();

        // 1. Créer les joueurs français
        $this->info('Création des joueurs français...');
        $frPlayers = $this->createFrenchPlayers($france, $angleterre, $usa);

        // 2. Créer les joueurs néo-zélandais
        $this->info('Création des joueurs néo-zélandais...');
        $nzPlayers = $this->createNZPlayers($nz);

        // 3. Créer les compositions
        $this->info('Création des compositions...');
        $this->createLineups($frPlayers, TeamSide::FRANCE);
        $this->createLineups($nzPlayers, TeamSide::ADVERSAIRE);

        // 4. Créer les faits de jeu
        $this->info('Création des faits de jeu...');
        $this->createEvents();

        // 5. Mettre à jour le match
        $this->info('Mise à jour du match...');
        $this->match->update([
            'referee' => 'Louis Dedet',
            'referee_country_id' => $france->id,
            'attendance' => 3000,
            'weather' => 'Temps humide, sol ferme',
            'notes' => "Premier match officiel de l'histoire du XV de France. Les Originals néo-zélandais achèvent leur tournée européenne (34 victoires en 35 matchs). Henri Amand est le premier capitaine et premier capé français. L'équipe comprend deux joueurs étrangers (Crichton, Anglais ; Muhr, Américain) et deux joueurs de couleur (Vergès, Jérome).",
            'match_number' => 1,
        ]);

        $this->newLine();
        $this->info('=== Résumé ===');
        $this->table(['Métrique', 'Valeur'], [
            ['Joueurs créés (France)', count($frPlayers)],
            ['Joueurs créés (NZ)', count($nzPlayers)],
            ['Lineups créés', $this->match->lineups()->count()],
            ['Events créés', $this->match->events()->count()],
        ]);

        return self::SUCCESS;
    }

    private function createFrenchPlayers(Country $france, Country $angleterre, Country $usa): array
    {
        $players = [
            ['jersey' => 15, 'last' => 'Crichton',   'first' => 'William',  'pos' => PlayerPosition::ARRIERE,              'country' => $angleterre, 'cap' => 10, 'captain' => false],
            ['jersey' => 14, 'last' => 'Lane',        'first' => 'Gaston',   'pos' => PlayerPosition::AILIER,               'country' => $france,     'cap' => 14, 'captain' => false],
            ['jersey' => 13, 'last' => 'Levée',       'first' => 'Henri',    'pos' => PlayerPosition::CENTRE,               'country' => $france,     'cap' => 6,  'captain' => false],
            ['jersey' => 12, 'last' => 'Sagot',       'first' => 'Paul',     'pos' => PlayerPosition::CENTRE,               'country' => $france,     'cap' => 12, 'captain' => false],
            ['jersey' => 11, 'last' => 'Pujol',       'first' => 'Augustin', 'pos' => PlayerPosition::AILIER,               'country' => $france,     'cap' => 9,  'captain' => false],
            ['jersey' => 10, 'last' => 'Amand',       'first' => 'Henri',    'pos' => PlayerPosition::DEMI_OUVERTURE,       'country' => $france,     'cap' => 1,  'captain' => true, 'nickname' => 'le Barby'],
            ['jersey' => 9,  'last' => 'Lacassagne',  'first' => 'Henri',    'pos' => PlayerPosition::DEMI_DE_MELEE,        'country' => $france,     'cap' => 5,  'captain' => false],
            ['jersey' => 8,  'last' => 'Dufourcq',    'first' => 'Jacques',  'pos' => PlayerPosition::NUMERO_HUIT,          'country' => $france,     'cap' => 7,  'captain' => false],
            ['jersey' => 7,  'last' => 'Cessieux',    'first' => 'Noël',     'pos' => PlayerPosition::TROISIEME_LIGNE_AILE, 'country' => $france,     'cap' => 8,  'captain' => false],
            ['jersey' => 6,  'last' => 'Communeau',   'first' => 'Marcel',   'pos' => PlayerPosition::TROISIEME_LIGNE_AILE, 'country' => $france,     'cap' => 4,  'captain' => false],
            ['jersey' => 5,  'last' => 'Muhr',        'first' => 'Allan',    'pos' => PlayerPosition::DEUXIEME_LIGNE,       'country' => $usa,        'cap' => 2,  'captain' => false],
            ['jersey' => 4,  'last' => 'Jérome',      'first' => 'Georges',  'pos' => PlayerPosition::DEUXIEME_LIGNE,       'country' => $france,     'cap' => 3,  'captain' => false],
            ['jersey' => 3,  'last' => 'Branlat',     'first' => 'Albert',   'pos' => PlayerPosition::PILIER_DROIT,         'country' => $france,     'cap' => 13, 'captain' => false],
            ['jersey' => 2,  'last' => 'Dedeyn',      'first' => 'Paul',     'pos' => PlayerPosition::TALONNEUR,            'country' => $france,     'cap' => 11, 'captain' => false],
            ['jersey' => 1,  'last' => 'Vergès',      'first' => 'André',    'pos' => PlayerPosition::PILIER_GAUCHE,        'country' => $france,     'cap' => 15, 'captain' => false],
        ];

        $result = [];
        foreach ($players as $p) {
            $player = Player::firstOrCreate(
                ['first_name' => $p['first'], 'last_name' => $p['last'], 'country_id' => $p['country']->id],
                [
                    'primary_position' => $p['pos'],
                    'is_active' => false,
                    'cap_number' => $p['cap'],
                    'nickname' => $p['nickname'] ?? null,
                ]
            );
            $this->playerCache[$p['last']] = $player;
            $result[] = array_merge($p, ['player' => $player]);
        }

        return $result;
    }

    private function createNZPlayers(Country $nz): array
    {
        $players = [
            ['jersey' => 15, 'last' => 'Booth',       'first' => 'Eric',    'pos' => PlayerPosition::ARRIERE,              'captain' => false],
            ['jersey' => 14, 'last' => 'Harper',       'first' => 'George',  'pos' => PlayerPosition::AILIER,               'captain' => false],
            ['jersey' => 13, 'last' => 'Wallace',      'first' => 'Billy',   'pos' => PlayerPosition::CENTRE,               'captain' => false],
            ['jersey' => 12, 'last' => 'Abbott',       'first' => 'John',    'pos' => PlayerPosition::CENTRE,               'captain' => false],
            ['jersey' => 11, 'last' => 'Hunter',       'first' => 'Ernest',  'pos' => PlayerPosition::AILIER,               'captain' => false],
            ['jersey' => 10, 'last' => 'Mynott',       'first' => 'Harold',  'pos' => PlayerPosition::DEMI_OUVERTURE,       'captain' => false],
            ['jersey' => 9,  'last' => 'Stead',        'first' => 'Billy',   'pos' => PlayerPosition::DEMI_DE_MELEE,        'captain' => false],
            ['jersey' => 8,  'last' => 'Machrell',     'first' => 'Steven',  'pos' => PlayerPosition::NUMERO_HUIT,          'captain' => false],
            ['jersey' => 7,  'last' => 'Tyler',        'first' => 'George',  'pos' => PlayerPosition::TROISIEME_LIGNE_AILE, 'captain' => false],
            ['jersey' => 6,  'last' => 'Glasgow',      'first' => 'Frank',   'pos' => PlayerPosition::TROISIEME_LIGNE_AILE, 'captain' => false],
            ['jersey' => 5,  'last' => 'Newton',       'first' => 'James',   'pos' => PlayerPosition::DEUXIEME_LIGNE,       'captain' => false],
            ['jersey' => 4,  'last' => 'Cunningham',   'first' => 'William', 'pos' => PlayerPosition::DEUXIEME_LIGNE,       'captain' => false],
            ['jersey' => 3,  'last' => 'Glenn',        'first' => 'George',  'pos' => PlayerPosition::PILIER_DROIT,         'captain' => false],
            ['jersey' => 2,  'last' => 'Seeling',      'first' => 'Charlie', 'pos' => PlayerPosition::TALONNEUR,            'captain' => false],
            ['jersey' => 1,  'last' => 'Gallaher',     'first' => 'Dave',    'pos' => PlayerPosition::PILIER_GAUCHE,        'captain' => true],
        ];

        $result = [];
        foreach ($players as $p) {
            $player = Player::firstOrCreate(
                ['first_name' => $p['first'], 'last_name' => $p['last'], 'country_id' => $nz->id],
                [
                    'primary_position' => $p['pos'],
                    'is_active' => false,
                ]
            );
            $this->playerCache[$p['last']] = $player;
            $result[] = array_merge($p, ['player' => $player]);
        }

        return $result;
    }

    private function createLineups(array $players, TeamSide $side): void
    {
        foreach ($players as $p) {
            MatchLineup::create([
                'match_id' => $this->match->id,
                'player_id' => $p['player']->id,
                'jersey_number' => $p['jersey'],
                'is_starter' => true,
                'position_played' => $p['pos'],
                'is_captain' => $p['captain'],
                'team_side' => $side,
            ]);
        }
    }

    private function createEvents(): void
    {
        // Essais France
        $this->event('Cessieux', EventType::ESSAI, TeamSide::FRANCE, null, 'Premier essai français de l\'histoire');
        $this->event('Dufourcq', EventType::ESSAI, TeamSide::FRANCE);

        // Transformations France
        $this->event('Pujol', EventType::TRANSFORMATION, TeamSide::FRANCE);

        // Essais Nouvelle-Zélande
        $this->event('Abbott', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Abbott', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Abbott', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Glasgow', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Harper', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Harper', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Hunter', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Hunter', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Wallace', EventType::ESSAI, TeamSide::ADVERSAIRE);
        $this->event('Wallace', EventType::ESSAI, TeamSide::ADVERSAIRE);

        // Transformations Nouvelle-Zélande
        $this->event('Abbott', EventType::TRANSFORMATION, TeamSide::ADVERSAIRE);
        $this->event('Tyler', EventType::TRANSFORMATION, TeamSide::ADVERSAIRE);
        $this->event('Wallace', EventType::TRANSFORMATION, TeamSide::ADVERSAIRE);
        $this->event('Wallace', EventType::TRANSFORMATION, TeamSide::ADVERSAIRE);
    }

    private function event(string $lastName, EventType $type, TeamSide $side, ?int $minute = null, ?string $detail = null): void
    {
        $player = $this->playerCache[$lastName] ?? null;

        MatchEvent::create([
            'match_id' => $this->match->id,
            'player_id' => $player?->id,
            'event_type' => $type,
            'minute' => $minute,
            'team_side' => $side,
            'detail' => $detail,
        ]);
    }
}
