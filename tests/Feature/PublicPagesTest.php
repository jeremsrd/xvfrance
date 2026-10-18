<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Livewire\PlayerList;
use App\Models\Club;
use App\Models\Coach;
use App\Models\CoachTenure;
use App\Models\CompetitionEdition;
use App\Models\Country;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\MatchSubstitution;
use App\Models\Player;
use App\Models\PlayerClub;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private RugbyMatch $match;
    private Player $frenchPlayer;
    private CoachTenure $tenure;
    private Club $club;

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

        $this->club = Club::create(['name' => 'Stade toulousain', 'slug' => 'stade-toulousain', 'city' => 'Toulouse', 'country_id' => $france->id]);
        PlayerClub::create(['player_id' => $this->frenchPlayer->id, 'club_id' => $this->club->id, 'position' => 1, 'name' => 'Stade toulousain', 'source' => 'test']);

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
            'records des joueurs' => [fn (self $t) => route('records.players')],
            'stades' => [fn (self $t) => route('venues.index')],
            'clubs' => [fn (self $t) => route('clubs.index')],
            'club' => [fn (self $t) => route('clubs.show', $t->club)],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders(\Closure $url): void
    {
        $this->get($url($this))->assertOk();
    }

    #[DataProvider('publicPages')]
    public function test_public_page_has_title_description_and_canonical(\Closure $url): void
    {
        $html = $this->get($url($this))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<title>[^<]{10,}</title>#', $html);
        $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]{50,}">#', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . $url($this) . '">', $html);
    }

    public function test_canonical_drops_list_filters_but_keeps_pagination(): void
    {
        $this->get(route('players.index', ['team' => 'adversaires', 'page' => 2]))
            ->assertSee('<link rel="canonical" href="' . route('players.index') . '">', false);
        $this->get(route('matches.index', ['page' => 2]))
            ->assertSee('<link rel="canonical" href="' . route('matches.index') . '?page=2">', false);
    }

    public function test_sitemap_lists_every_detail_page(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>' . route('matches.show', $this->match) . '</loc>', false)
            ->assertSee('<loc>' . route('players.show', $this->frenchPlayer) . '</loc>', false)
            ->assertSee('<loc>' . route('opponents.show', 'NZL') . '</loc>', false)
            ->assertSee('<loc>' . route('clubs.show', $this->club) . '</loc>', false)
            ->assertSee('<loc>' . route('editions.show', $this->match->edition_id) . '</loc>', false)
            ->assertSee('<loc>' . route('coaches.show', $this->tenure->coach_id) . '</loc>', false)
            ->assertDontSee('/matches?', false);
    }

    #[DataProvider('publicPages')]
    public function test_public_page_has_open_graph_tags(\Closure $url): void
    {
        $html = $this->get($url($this))->getContent();

        $this->assertStringContainsString('<meta property="og:url" content="' . $url($this) . '">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:title" content="[^"]{10,}">#', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="https?://[^"]+">#', $html);
        $this->assertFileExists(public_path('images/partage.png'));
    }

    public function test_match_and_player_pages_carry_structured_data(): void
    {
        $structuredData = fn (string $url) => collect(
            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $this->get($url)->getContent(), $m) ? $m[1] : []
        )->map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR))->keyBy('@type');

        $match = $structuredData(route('matches.show', $this->match));
        $this->assertSame('2024-11-16', $match['SportsEvent']['startDate']);
        $this->assertSame('Nouvelle-Zélande', $match['SportsEvent']['awayTeam']['name']);
        $this->assertSame(route('matches.show', $this->match), last($match['BreadcrumbList']['itemListElement'])['item']);

        $player = $structuredData(route('players.show', $this->frenchPlayer));
        $this->assertSame($this->frenchPlayer->fullName(), $player['ProfilePage']['mainEntity']['name']);
        $this->assertSame('Équipe de France de rugby à XV', $player['ProfilePage']['mainEntity']['memberOf']['name']);

        $this->assertArrayHasKey('WebSite', $structuredData(route('home')));
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

    public function test_players_can_be_filtered_by_club(): void
    {
        Livewire::test(PlayerList::class, ['club' => 'stade-toulousain'])
            ->assertSee($this->frenchPlayer->last_name)
            ->assertViewHas('players', fn ($players) => $players->total() === 1);
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

    public function test_match_lineup_shows_portrait_next_to_name_when_known(): void
    {
        $this->frenchPlayer->update(['photo_path' => '/images/joueurs/fra-test.webp']);

        $this->get(route('matches.show', $this->match))
            ->assertSee('src="/images/joueurs/fra-test.webp"', false)
            ->assertSee('tabular-nums ring-1 h-7 w-7 text-sm', false);
    }
}
