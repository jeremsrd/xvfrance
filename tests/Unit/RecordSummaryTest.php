<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_computes_total_and_win_percentage(): void
    {
        $record = new RecordSummary(wins: 3, losses: 1, draws: 1);

        $this->assertSame(5, $record->total);
        $this->assertSame(60.0, $record->winPct);
    }

    public function test_empty_record_has_zero_percentage(): void
    {
        $record = new RecordSummary();

        $this->assertSame(0, $record->total);
        $this->assertSame(0.0, $record->winPct);
    }

    public function test_win_percentage_label_uses_french_format(): void
    {
        $record = new RecordSummary(wins: 1, losses: 2);

        $this->assertSame("33,3\u{00A0}%", $record->winPctLabel());
        $this->assertSame("33\u{00A0}%", $record->winPctLabel(0));
    }

    public function test_from_matches_counts_results_in_memory(): void
    {
        $matches = [
            new RugbyMatch(['france_score' => 20, 'opponent_score' => 10]),
            new RugbyMatch(['france_score' => 10, 'opponent_score' => 20]),
            new RugbyMatch(['france_score' => 15, 'opponent_score' => 15]),
            new RugbyMatch(['france_score' => 30, 'opponent_score' => 3]),
        ];

        $record = RecordSummary::fromMatches($matches);

        $this->assertSame([2, 1, 1], [$record->wins, $record->losses, $record->draws]);
    }

    public function test_from_query_aggregates_in_sql(): void
    {
        RugbyMatch::factory()->score(20, 10)->count(3)->create();
        RugbyMatch::factory()->score(10, 20)->create();
        RugbyMatch::factory()->score(9, 9)->create();

        $record = RecordSummary::fromQuery(RugbyMatch::query()->orderBy('match_date'));

        $this->assertSame([3, 1, 1, 5], [$record->wins, $record->losses, $record->draws, $record->total]);
    }

    public function test_from_query_respects_filters_and_handles_empty_result(): void
    {
        RugbyMatch::factory()->score(20, 10)->create();

        $this->assertSame(0, RecordSummary::fromQuery(RugbyMatch::where('france_score', '>', 100))->total);
    }

    public function test_grouped_by_returns_one_record_per_key(): void
    {
        [$nzl, $eng] = Country::factory()->count(2)->create();
        RugbyMatch::factory()->score(30, 29)->create(['opponent_id' => $nzl->id]);
        RugbyMatch::factory()->score(10, 20)->create(['opponent_id' => $nzl->id]);
        RugbyMatch::factory()->score(15, 15)->create(['opponent_id' => $eng->id]);

        $records = RecordSummary::groupedBy(RugbyMatch::query(), 'opponent_id');

        $this->assertCount(2, $records);
        $this->assertSame([1, 1, 0], [$records[$nzl->id]->wins, $records[$nzl->id]->losses, $records[$nzl->id]->draws]);
        $this->assertSame(1, $records[$eng->id]->draws);
    }
}
