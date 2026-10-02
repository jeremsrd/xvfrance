<?php

namespace Tests\Feature;

use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Services\MatchImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Monolog\Handler\NullHandler;
use Tests\TestCase;

class MatchImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = 'database/data/matches/2024/2024-11-16-NZL.json';

    private Country $france;
    private RugbyMatch $match;
    private MatchImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Pas d'écriture dans storage/logs/import.log pendant les tests
        config(['logging.channels.import' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);

        $this->france = Country::factory()->france()->create();
        $nzl = Country::factory()->create(['name' => 'Nouvelle-Zélande', 'code' => 'NZL']);
        $this->match = RugbyMatch::factory()->score(30, 29)->create([
            'match_date' => '2024-11-16',
            'opponent_id' => $nzl->id,
        ]);

        $this->service = app(MatchImportService::class);
    }

    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path(self::FIXTURE)), true);
    }

    private function import(array $data, bool $dryRun = false, bool $force = false, bool $skipExisting = false): void
    {
        $this->service->importSingleMatch($data, $dryRun, $force, $skipExisting);
    }

    public function test_imports_real_match_file(): void
    {
        $data = $this->fixture();

        $this->service->importFile(base_path(self::FIXTURE), dryRun: false, force: false, skipExisting: false);

        $this->assertSame(1, $this->service->getMatchesImported());
        $this->assertSame(0, $this->service->getErrorCount());
        $this->assertSame(count($data['lineups']['france']), $this->match->lineups()->france()->count());
        $this->assertSame(count($data['lineups']['adversaire'] ?? []), $this->match->lineups()->adversaire()->count());
        $this->assertSame(count($data['events'] ?? []), $this->match->events()->count());
        $this->assertSame(count($data['substitutions'] ?? []), $this->match->substitutions()->count());

        $captain = $this->match->lineups()->france()->where('is_captain', true)->with('player')->sole();
        $this->assertSame('Dupont', $captain->player->last_name);
        $this->assertSame($this->france->id, $captain->player->country_id);
    }

    public function test_created_players_get_a_slug(): void
    {
        $this->import($this->fixture());

        $this->assertGreaterThan(0, $this->service->getPlayersCreated());
        $this->assertSame(0, Player::whereNull('slug')->count());
    }

    public function test_reuses_existing_players(): void
    {
        $dupont = Player::factory()->create(['first_name' => 'Antoine', 'last_name' => 'Dupont', 'country_id' => $this->france->id]);

        $this->import($this->fixture());

        $this->assertSame(1, Player::where('last_name', 'Dupont')->count());
        $this->assertTrue(MatchLineup::where('player_id', $dupont->id)->where('jersey_number', 9)->exists());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->import($this->fixture(), dryRun: true);

        $this->assertSame(1, $this->service->getMatchesImported());
        $this->assertSame(0, MatchLineup::count());
        $this->assertSame(0, Player::count());
    }

    public function test_already_filled_match_is_skipped_unless_forced(): void
    {
        $this->import($this->fixture());
        $lineupCount = MatchLineup::count();

        $this->import($this->fixture());
        $this->assertSame(1, $this->service->getMatchesSkipped());
        $this->assertSame($lineupCount, MatchLineup::count());

        $this->import($this->fixture(), force: true);
        $this->assertSame(2, $this->service->getMatchesImported());
        $this->assertSame($lineupCount, MatchLineup::count());
    }

    public function test_unknown_match_is_skipped(): void
    {
        $this->import(['match_date' => '1999-01-01'] + $this->fixture());

        $this->assertSame(1, $this->service->getMatchesSkipped());
        $this->assertSame(0, MatchLineup::count());
    }

    public function test_invalid_data_is_rejected(): void
    {
        $this->import(['opponent_code' => 'XXX'] + $this->fixture());

        $this->assertSame(0, $this->service->getMatchesImported());
        $this->assertGreaterThan(0, $this->service->getErrorCount());
        $this->assertSame(0, MatchLineup::count());
    }

    public function test_score_mismatch_is_reported_as_warning(): void
    {
        $this->import(['france_score' => 31] + $this->fixture());

        $this->assertContains(
            ['warn', 'Score France diffère : JSON=31 vs BDD=30'],
            $this->service->flushMessages(),
        );
    }

    public function test_penalty_try_has_no_player(): void
    {
        $data = $this->fixture();
        $data['events'][] = ['team_side' => 'adversaire', 'type' => 'essai_penalite', 'minute' => 79];

        $this->import($data);

        $this->assertTrue($this->match->events()->whereNull('player_id')->where('team_side', TeamSide::ADVERSAIRE)->exists());
    }
}
