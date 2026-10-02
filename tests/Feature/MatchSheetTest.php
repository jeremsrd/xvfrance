<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\MatchSubstitution;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Support\MatchSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchSheetTest extends TestCase
{
    use RefreshDatabase;

    private RugbyMatch $match;
    private Player $fr;
    private Player $eng;

    protected function setUp(): void
    {
        parent::setUp();

        $france = Country::factory()->france()->create();
        $england = Country::factory()->create(['name' => 'Angleterre', 'code' => 'ENG']);
        // 2024 : essai à 5 points
        $this->match = RugbyMatch::factory()->score(12, 3)->create(['match_date' => '2024-02-10', 'opponent_id' => $england->id, 'is_home' => false]);
        $this->fr = Player::factory()->create(['country_id' => $france->id, 'first_name' => 'Antoine', 'last_name' => 'Dupont']);
        $this->eng = Player::factory()->create(['country_id' => $england->id, 'first_name' => 'Marcus', 'last_name' => 'Smith']);
    }

    private function event(EventType $type, ?int $minute, TeamSide $side = TeamSide::FRANCE, ?Player $player = null): void
    {
        MatchEvent::factory()->create([
            'match_id' => $this->match->id,
            'player_id' => $type === EventType::ESSAI_PENALITE ? null : ($player ?? ($side === TeamSide::FRANCE ? $this->fr : $this->eng))->id,
            'event_type' => $type,
            'minute' => $minute,
            'team_side' => $side,
        ]);
    }

    private function sheet(): MatchSheet
    {
        return new MatchSheet($this->match->fresh(['opponent', 'lineups.player', 'events.player', 'substitutions.playerOff', 'substitutions.playerOn']));
    }

    private function completeMatch(): void
    {
        $this->event(EventType::ESSAI, 10);
        $this->event(EventType::TRANSFORMATION, 11);
        $this->event(EventType::PENALITE, 30, TeamSide::ADVERSAIRE);
        $this->event(EventType::ESSAI_PENALITE, 60);
    }

    public function test_teams_are_ordered_home_first(): void
    {
        [$home, $away] = $this->sheet()->teams();

        $this->assertSame('Angleterre', $home['name']);
        $this->assertSame(3, $home['score']);
        $this->assertTrue($away['isFrance']);
    }

    public function test_complete_timeline_has_running_score_and_half_time(): void
    {
        $this->completeMatch();
        $sheet = $this->sheet();

        $this->assertTrue($sheet->isScoreComplete());
        $this->assertSame([7, 3], $sheet->halfTimeScore());
        $this->assertSame([[5, 0], [7, 0], [7, 3], [12, 3]], array_column($sheet->timeline(), 'score'));
    }

    public function test_incomplete_timeline_hides_running_score(): void
    {
        $this->event(EventType::ESSAI, 10);
        $sheet = $this->sheet();

        $this->assertFalse($sheet->isScoreComplete());
        $this->assertSame([5, 0], $sheet->computedScore());
        $this->assertNull($sheet->halfTimeScore());
        $this->assertNull($sheet->timeline()[0]['score']);
    }

    public function test_events_without_minute_make_timeline_incomplete(): void
    {
        $this->completeMatch();
        MatchEvent::where('minute', 60)->update(['minute' => null]);

        $this->assertFalse($this->sheet()->isScoreComplete());
        $this->assertSame([null], array_unique(array_column($this->sheet()->timeline(), 'score'), SORT_REGULAR));
    }

    public function test_scoring_uses_historical_values(): void
    {
        $this->match->update(['match_date' => '1960-03-01', 'france_score' => 3, 'opponent_score' => 0]);
        $this->event(EventType::ESSAI, 20);

        $this->assertTrue($this->sheet()->isScoreComplete());
    }

    public function test_scorers_are_grouped_by_type_and_player(): void
    {
        $this->completeMatch();
        $this->event(EventType::ESSAI, 70);

        $groups = $this->sheet()->scorers(TeamSide::FRANCE);

        $this->assertSame(['Essais', 'Essai de pénalité', 'Transformation'], array_column($groups, 'label'));
        $this->assertSame([10, 70], $groups[0]['scorers'][0]['minutes']);
        $this->assertSame('Essai de pénalité', $groups[1]['scorers'][0]['name']);
    }

    public function test_timeline_merges_substitutions_after_events_of_same_minute(): void
    {
        $this->completeMatch();
        $sub = Player::factory()->create(['country_id' => $this->fr->country_id]);
        MatchSubstitution::factory()->create(['match_id' => $this->match->id, 'player_off_id' => $this->fr->id, 'player_on_id' => $sub->id, 'minute' => 10]);

        $types = array_column($this->sheet()->timeline(), 'type');

        $this->assertSame(['essai', 'remplacement', 'transformation'], array_slice($types, 0, 3));
    }

    public function test_lineup_rows_carry_substitutions_tries_and_cards(): void
    {
        $sub = Player::factory()->create(['country_id' => $this->fr->country_id, 'last_name' => 'Dupont', 'first_name' => 'Gabin']);
        MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $this->fr->id, 'jersey_number' => 9, 'is_captain' => true]);
        MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $sub->id, 'jersey_number' => 21, 'is_starter' => false]);
        MatchSubstitution::factory()->create(['match_id' => $this->match->id, 'player_off_id' => $this->fr->id, 'player_on_id' => $sub->id, 'minute' => 65]);
        $this->event(EventType::ESSAI, 10);
        $this->event(EventType::CARTON_JAUNE, 50);

        $sheet = $this->sheet();
        $lineup = $sheet->lineup(TeamSide::FRANCE);

        $this->assertSame(65, $lineup['starters'][0]['off']);
        $this->assertSame(1, $lineup['starters'][0]['tries']);
        $this->assertSame(EventType::CARTON_JAUNE, $lineup['starters'][0]['cards'][0]['type']);
        $this->assertSame(65, $lineup['bench'][0]['on']);
        // Deux Dupont dans l'équipe : initiale ajoutée
        $this->assertSame('A. Dupont', $sheet->shortName($this->fr, TeamSide::FRANCE));
        $this->assertSame('Smith', $sheet->shortName($this->eng, TeamSide::ADVERSAIRE));
    }

    public function test_match_page_renders_detailed_and_empty_sheets(): void
    {
        $this->get(route('matches.show', $this->match))->assertOk()->assertSee('Feuille de match à compléter');

        $this->completeMatch();
        MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $this->fr->id, 'jersey_number' => 9]);

        $this->get(route('matches.show', $this->match))
            ->assertOk()
            ->assertDontSee('Feuille de match à compléter')
            ->assertSee('Compositions')
            ->assertSee('Mi-temps')
            ->assertSee('confrontation entre la France et l&#039;Angleterre', false);
    }
}
