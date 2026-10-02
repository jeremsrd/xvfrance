<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Models\MatchEvent;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Services\RecordsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordsServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crée des matches consécutifs à partir de scores [france, adversaire].
     *
     * @return list<RugbyMatch>
     */
    private function playMatches(array $scores, string $from = '2000-01-01'): array
    {
        $date = \Illuminate\Support\Carbon::parse($from);

        return array_map(function (array $score) use ($date) {
            $match = RugbyMatch::factory()->score(...$score)->create(['match_date' => $date->format('Y-m-d')]);
            $date->addWeek();

            return $match;
        }, $scores);
    }

    public function test_biggest_wins_and_heaviest_defeats_are_ranked_by_margin(): void
    {
        [$small, $big, $loss, $bigLoss] = $this->playMatches([[20, 10], [50, 3], [10, 15], [3, 60]]);
        $service = new RecordsService();

        $this->assertSame([$big->id, $small->id], $service->biggestWins()->pluck('id')->all());
        $this->assertSame([$bigLoss->id, $loss->id], $service->heaviestDefeats()->pluck('id')->all());
        $this->assertSame($big->id, $service->mostPointsScored(1)->first()->id);
        $this->assertSame($bigLoss->id, $service->mostPointsConceded(1)->first()->id);
    }

    public function test_streaks_track_longest_runs_in_chronological_order(): void
    {
        // V V N V V V D D | la série d'invincibilité (6) inclut le nul
        $matches = $this->playMatches([[20, 10], [20, 10], [9, 9], [20, 10], [20, 10], [20, 10], [5, 10], [5, 10]]);

        $streaks = (new RecordsService())->streaks();

        $this->assertSame(3, $streaks['wins']['length']);
        $this->assertTrue($matches[3]->is($streaks['wins']['from']));
        $this->assertTrue($matches[5]->is($streaks['wins']['to']));
        $this->assertSame(6, $streaks['unbeaten']['length']);
        $this->assertSame(2, $streaks['losses']['length']);
        $this->assertTrue($streaks['losses']['ongoing']);
        $this->assertFalse($streaks['wins']['ongoing']);
    }

    public function test_streaks_are_null_without_matching_results(): void
    {
        $this->playMatches([[20, 10]]);

        $this->assertNull((new RecordsService())->streaks()['losses']);
    }

    public function test_record_by_decade_and_venue_type(): void
    {
        RugbyMatch::factory()->score(10, 20)->create(['match_date' => '1911-01-01', 'is_home' => true]);
        RugbyMatch::factory()->score(20, 10)->create(['match_date' => '1919-12-31', 'is_home' => false]);
        RugbyMatch::factory()->score(20, 10)->create(['match_date' => '1920-01-01', 'is_home' => false, 'is_neutral' => true]);

        $service = new RecordsService();
        $decades = $service->recordByDecade();
        $venues = $service->recordByVenueType();

        $this->assertSame([1910, 1920], $decades->keys()->all());
        $this->assertSame([1, 1], [$decades[1910]->wins, $decades[1910]->losses]);
        $this->assertSame([1, 1, 1], [$venues['home']->total, $venues['away']->total, $venues['neutral']->total]);
    }

    public function test_individual_leaderboards_only_count_france(): void
    {
        $match = RugbyMatch::factory()->create();
        [$star, $other] = Player::factory()->count(2)->create();
        $opponent = Player::factory()->create();

        MatchEvent::factory()->count(3)->create(['match_id' => $match->id, 'player_id' => $star->id]);
        MatchEvent::factory()->create(['match_id' => $match->id, 'player_id' => $other->id]);
        MatchEvent::factory()->create(['match_id' => $match->id, 'player_id' => $other->id, 'event_type' => EventType::PENALITE]);
        MatchEvent::factory()->count(5)->create(['match_id' => $match->id, 'player_id' => $opponent->id, 'team_side' => 'adversaire']);
        MatchLineup::factory()->create(['match_id' => $match->id, 'player_id' => $star->id, 'is_captain' => true]);

        $service = new RecordsService();
        $tries = $service->topTryScorers();

        $this->assertSame([$star->id, $other->id], $tries->pluck('player_id')->all());
        $this->assertSame([3, 1], $tries->pluck('total')->map(fn ($t) => (int) $t)->all());
        $this->assertSame($star->id, $service->mostCaptaincies()->sole()->player_id);
        $this->assertSame(1, $service->detailedMatchCount());
    }
}
