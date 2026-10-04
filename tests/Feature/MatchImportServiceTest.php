<?php

namespace Tests\Feature;

use App\Enums\MatchStage;
use App\Enums\TeamSide;
use App\Models\Competition;
use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Models\Venue;
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
        Country::factory()->create(['name' => 'Angleterre', 'code' => 'ENG']);
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

    public function test_unknown_match_without_venue_is_rejected(): void
    {
        $this->import(['match_date' => '1999-01-01'] + $this->fixture());

        $this->assertSame(0, $this->service->getMatchesImported());
        $this->assertContains(['error', 'venue requis pour créer le match (absent de la base)'], $this->service->flushMessages());
        $this->assertSame(1, RugbyMatch::count());
    }

    public function test_unknown_match_is_created_with_new_venue_and_edition(): void
    {
        $competition = Competition::create(['name' => 'Championnat des Nations', 'short_name' => 'Championnat des Nations', 'type' => 'autre']);

        $this->import([
            'match_date' => '2026-11-28',
            'venue' => 'Allianz Stadium',
            'venue_city' => 'Londres',
            'venue_country_code' => 'ENG',
            'competition' => 'Championnat des Nations',
            'stage' => 'finale',
        ] + $this->fixture());

        $this->assertSame(0, $this->service->getErrorCount(), json_encode($this->service->flushMessages()));
        $match = RugbyMatch::whereDate('match_date', '2026-11-28')->sole();
        $this->assertSame('2026-11-28-nouvelle-zelande', $match->slug);
        $this->assertSame([30, 29], [$match->france_score, $match->opponent_score]);
        $this->assertSame('Allianz Stadium', $match->venue->name);
        $this->assertFalse($match->is_home);
        $this->assertTrue($match->is_neutral, 'stade anglais, ni France ni Nouvelle-Zélande');
        $this->assertSame($competition->id, $match->edition->competition_id);
        $this->assertSame(2026, $match->edition->year);
        $this->assertSame(MatchStage::FINALE, $match->stage);
        $this->assertGreaterThan(0, $match->lineups()->count());
    }

    public function test_existing_match_gets_venue_and_home_flag(): void
    {
        $venue = Venue::factory()->create(['name' => 'Stade de France', 'country_id' => $this->france->id]);
        $this->match->update(['is_home' => false]);

        $this->import(['venue' => 'Stade de France'] + $this->fixture());

        $this->match->refresh();
        $this->assertSame($venue->id, $this->match->venue_id);
        $this->assertTrue($this->match->is_home);
        $this->assertFalse($this->match->is_neutral);
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
        $this->match->update(['france_score' => 31]);
        $this->import($this->fixture());

        $this->assertContains(
            ['warn', 'Score France diffère : JSON=30 vs BDD=31'],
            $this->service->flushMessages(),
        );
    }

    public function test_penalty_try_has_no_player(): void
    {
        $data = $this->fixture();
        $data['events'][] = ['team_side' => 'adversaire', 'type' => 'essai_penalite', 'minute' => 79];
        $data['opponent_score'] += 7;

        $this->import($data);

        $this->assertTrue($this->match->events()->whereNull('player_id')->where('team_side', TeamSide::ADVERSAIRE)->exists());
    }

    public function test_changed_mode_reimports_only_modified_sheets(): void
    {
        $data = $this->fixture();
        $this->import($data);

        $this->service->importSingleMatch($data, false, false, false, changedOnly: true);
        $this->assertSame(1, $this->service->getMatchesImported(), 'feuille inchangée : rien à réimporter');

        $data['events'][0]['minute'] = ($data['events'][0]['minute'] ?? 0) + 1;
        $this->service->importSingleMatch($data, false, false, false, changedOnly: true);
        $this->assertSame(2, $this->service->getMatchesImported(), 'feuille modifiée : réimportée');
        $this->assertSame(count($data['lineups']['france']), $this->match->lineups()->france()->count());
    }

    public function test_imports_match_info_fields(): void
    {
        $eng = Country::where('code', 'ENG')->sole();

        $this->import([
            'referee' => 'Luke Pearce',
            'referee_country_code' => 'ENG',
            'attendance' => 29152,
            'kickoff_time' => '19:05',
        ] + $this->fixture());

        $this->match->refresh();
        $this->assertSame('Luke Pearce', $this->match->referee);
        $this->assertSame($eng->id, $this->match->referee_country_id);
        $this->assertSame(29152, $this->match->attendance);
        $this->assertSame('19:05:00', $this->match->kickoff_time);
    }

    public function test_absent_match_info_fields_are_left_untouched(): void
    {
        $this->match->update(['referee' => 'Louis Dedet', 'weather' => 'Temps humide']);

        $this->import(['attendance' => 3000] + $this->fixture());

        $this->match->refresh();
        $this->assertSame('Louis Dedet', $this->match->referee);
        $this->assertSame('Temps humide', $this->match->weather);
        $this->assertSame(3000, $this->match->attendance);
    }
}
