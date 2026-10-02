<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Support\PlayerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerProfileTest extends TestCase
{
    use RefreshDatabase;

    private Country $france;
    private Country $england;

    protected function setUp(): void
    {
        parent::setUp();

        $this->france = Country::factory()->france()->create();
        $this->england = Country::factory()->create(['name' => 'Angleterre', 'code' => 'ENG']);
    }

    private function play(Player $player, RugbyMatch $match, int $jersey, bool $starter = true, TeamSide $side = TeamSide::FRANCE, bool $captain = false): void
    {
        MatchLineup::factory()->create([
            'match_id' => $match->id, 'player_id' => $player->id, 'jersey_number' => $jersey,
            'is_starter' => $starter, 'team_side' => $side, 'is_captain' => $captain,
            'position_played' => $jersey === 9 ? PlayerPosition::DEMI_DE_MELEE : PlayerPosition::CENTRE,
        ]);
    }

    public function test_totals_and_record_for_a_french_player(): void
    {
        $player = Player::factory()->create(['country_id' => $this->france->id]);
        $mate = Player::factory()->create(['country_id' => $this->france->id, 'last_name' => 'Ntamack']);
        $win = RugbyMatch::factory()->score(30, 10)->create(['match_date' => '2024-02-01', 'opponent_id' => $this->england->id]);
        $loss = RugbyMatch::factory()->score(10, 30)->create(['match_date' => '2025-02-01', 'opponent_id' => $this->england->id]);
        $this->play($player, $win, 9, captain: true);
        $this->play($player, $loss, 21, starter: false);
        $this->play($mate, $win, 10);
        MatchEvent::factory()->create(['match_id' => $win->id, 'player_id' => $player->id, 'event_type' => EventType::ESSAI, 'team_side' => TeamSide::FRANCE]);
        MatchEvent::factory()->create(['match_id' => $win->id, 'player_id' => $player->id, 'event_type' => EventType::CARTON_JAUNE, 'team_side' => TeamSide::FRANCE]);

        $profile = new PlayerProfile($player);
        $totals = $profile->totals();

        $this->assertSame([2, 1, 1, 1, 5, 1], [$totals['matches'], $totals['starts'], $totals['captaincies'], $totals['tries'], $totals['points'], $totals['yellow']]);
        $this->assertSame([1, 1, 0], [$profile->record()->wins, $profile->record()->losses, $profile->record()->draws]);
        $this->assertTrue($profile->lastAppearance()['match']->is($loss));
        $this->assertSame([2025, 2024], $profile->byYear()->keys()->all());
        // Le n° 9 porté comme titulaire prime sur le 21 de remplaçant
        $this->assertSame(9, $profile->favouriteJersey());
        $this->assertSame('Ntamack', $profile->teammates()->first()['player']->last_name);
        $this->assertSame('Angleterre', $profile->opponents()->first()['name']);
    }

    public function test_opponent_player_sees_results_from_his_side(): void
    {
        $smith = Player::factory()->create(['country_id' => $this->england->id]);
        $franceWin = RugbyMatch::factory()->score(30, 10)->create(['opponent_id' => $this->england->id]);
        $this->play($smith, $franceWin, 10, side: TeamSide::ADVERSAIRE);

        $profile = new PlayerProfile($smith);

        $this->assertSame(1, $profile->record()->losses);
        $this->assertSame('France', $profile->opponents()->first()['name']);
        $this->get(route('players.show', $smith))->assertOk()->assertSee('Bilan avec l&#039;Angleterre', false);
    }

    public function test_player_without_appearances_has_a_clean_page(): void
    {
        $player = Player::factory()->create(['country_id' => $this->france->id]);

        $this->assertFalse((new PlayerProfile($player))->hasAppearances());
        $this->get(route('players.show', $player))->assertOk()->assertSee('Aucune feuille de match saisie');
    }
}
