<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Models\Coach;
use App\Models\CoachTenure;
use App\Models\CompetitionEdition;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\MatchSubstitution;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private RugbyMatch $match;
    private Player $frenchPlayer;
    private CoachTenure $tenure;

    protected function setUp(): void
    {
        parent::setUp();

        $france = Country::factory()->france()->create();
        $opponent = Country::factory()->create(['name' => 'Nouvelle-Zélande', 'code' => 'NZL']);
        $edition = CompetitionEdition::factory()->create(['year' => 2024]);

        $this->match = RugbyMatch::factory()->score(30, 29)->create([
            'match_date' => '2024-11-16',
            'opponent_id' => $opponent->id,
            'edition_id' => $edition->id,
            'is_home' => true,
        ]);

        $this->frenchPlayer = Player::factory()->create(['country_id' => $france->id, 'cap_number' => 1000]);
        $frenchSub = Player::factory()->create(['country_id' => $france->id]);
        $opponentPlayer = Player::factory()->create(['country_id' => $opponent->id]);

        MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $this->frenchPlayer->id, 'is_captain' => true]);
        MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $frenchSub->id, 'jersey_number' => 16, 'is_starter' => false]);
        MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $opponentPlayer->id, 'team_side' => TeamSide::ADVERSAIRE]);

        MatchEvent::factory()->create(['match_id' => $this->match->id, 'player_id' => $this->frenchPlayer->id]);
        MatchEvent::factory()->create(['match_id' => $this->match->id, 'player_id' => null, 'event_type' => EventType::ESSAI_PENALITE, 'team_side' => TeamSide::ADVERSAIRE]);
        MatchSubstitution::factory()->create(['match_id' => $this->match->id, 'player_off_id' => $this->frenchPlayer->id, 'player_on_id' => $frenchSub->id]);

        $this->tenure = CoachTenure::factory()->create([
            'start_date' => '2020-01-01',
            'coach_id' => Coach::factory()->create(['country_id' => $france->id])->id,
        ]);
    }

    public static function publicPages(): array
    {
        return [
            'accueil' => [fn (self $t) => route('home')],
            'matches' => [fn (self $t) => route('matches.index')],
            'feuille de match' => [fn (self $t) => route('matches.show', $t->match)],
            'joueurs' => [fn (self $t) => route('players.index')],
            'fiche joueur' => [fn (self $t) => route('players.show', $t->frenchPlayer)],
            'adversaires' => [fn (self $t) => route('opponents.index')],
            'bilan adversaire' => [fn (self $t) => route('opponents.show', 'NZL')],
            'compétitions' => [fn (self $t) => route('competitions.index')],
            'compétition' => [fn (self $t) => route('competitions.show', $t->match->edition->competition_id)],
            'édition' => [fn (self $t) => route('editions.show', $t->match->edition_id)],
            'sélectionneurs' => [fn (self $t) => route('coaches.index')],
            'fiche sélectionneur' => [fn (self $t) => route('coaches.show', $t->tenure->coach_id)],
            'records' => [fn (self $t) => route('records.index')],
            'stades' => [fn (self $t) => route('venues.index')],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders(\Closure $url): void
    {
        $this->get($url($this))->assertOk();
    }

    public function test_pages_render_with_empty_database(): void
    {
        $this->match->lineups()->delete();
        $this->match->events()->delete();
        $this->match->substitutions()->delete();
        RugbyMatch::query()->delete();

        foreach (['home', 'matches.index', 'players.index', 'opponents.index', 'competitions.index', 'coaches.index', 'records.index', 'venues.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_unknown_slugs_return_404(): void
    {
        $this->get('/matches/1900-01-01-inconnu')->assertNotFound();
        $this->get('/joueurs/inconnu-0')->assertNotFound();
        $this->get('/adversaires/XXX')->assertNotFound();
    }

    public function test_match_page_shows_score_and_players(): void
    {
        $this->get(route('matches.show', $this->match))
            ->assertSee('30')
            ->assertSee('29')
            ->assertSee($this->frenchPlayer->last_name);
    }
}
