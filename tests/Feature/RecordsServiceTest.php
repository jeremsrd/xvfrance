<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Models\Country;
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

    public function test_home_win_streak_ignores_away_matches(): void
    {
        // domicile V, extérieur D, domicile V V, domicile D : 3 victoires de rang à domicile
        foreach ([[20, 10, true], [5, 10, false], [20, 10, true], [20, 10, true], [5, 10, true]] as $i => [$fr, $opp, $home]) {
            RugbyMatch::factory()->score($fr, $opp)->create(['match_date' => "2000-01-0" . ($i + 1), 'is_home' => $home]);
        }

        $this->assertSame(3, (new RecordsService())->streaks()['homeWins']['length']);
    }

    public function test_opponent_records(): void
    {
        $nzl = Country::factory()->create();
        $first = RugbyMatch::factory()->score(10, 5)->for($nzl, 'opponent')->create(['match_date' => '1954-01-01']);
        $big = RugbyMatch::factory()->score(40, 5)->for($nzl, 'opponent')->create(['match_date' => '1960-01-01']);
        $loss = RugbyMatch::factory()->score(10, 61)->for($nzl, 'opponent')->create(['match_date' => '2007-01-01']);
        RugbyMatch::factory()->score(30, 0)->create(['match_date' => '2001-01-01']);

        $records = (new RecordsService())->opponentRecords();
        $nz = $records->first();

        $this->assertCount(2, $records);
        $this->assertTrue($nz->opponent->is($nzl));
        $this->assertSame(3, $nz->total);
        $this->assertTrue($first->is($nz->firstWin));
        $this->assertTrue($big->is($nz->biggestWin));
        $this->assertTrue($loss->is($nz->heaviestDefeat));
        $this->assertNull($records->last()->heaviestDefeat);
    }

    public function test_score_records_and_best_year(): void
    {
        [$a, $b, $c, $d] = $this->playMatches([[96, 0], [35, 55], [20, 20], [30, 27]], '2023-01-01');
        $this->playMatches([[3, 10], [3, 10], [3, 10], [3, 10], [20, 10], [20, 10]], '2010-01-01');

        $service = new RecordsService();
        $records = $service->scoreRecords();

        $this->assertTrue($a->is($records['mostScored']));
        $this->assertTrue($b->is($records['mostConceded']));
        $this->assertTrue($a->is($records['highestAggregate']));
        $this->assertTrue($b->is($records['mostScoredInDefeat']));
        $this->assertTrue($d->is($records['mostConcededInWin']));
        $this->assertTrue($c->is($records['highestDraw']));
        // 2023 n'a que 4 matches : seule 2010 atteint le minimum de 6
        $this->assertSame(2010, $service->bestYear()['year']);
        $this->assertSame(2023, $service->bestYear(3)['year']);
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
