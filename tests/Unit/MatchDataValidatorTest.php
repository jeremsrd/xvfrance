<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Services\MatchDataValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchDataValidatorTest extends TestCase
{
    use RefreshDatabase;

    private MatchDataValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        Country::factory()->create(['code' => 'NZL']);
        $this->validator = new MatchDataValidator();
    }

    private function validData(): array
    {
        $starters = array_map(fn (int $jersey) => [
            'jersey' => $jersey,
            'first_name' => 'Prénom',
            'last_name' => "Joueur{$jersey}",
            'is_starter' => true,
            'position' => 'centre',
        ], range(1, 15));

        return [
            'match_date' => '2024-11-16',
            'opponent_code' => 'NZL',
            'france_score' => 30,
            'opponent_score' => 29,
            'lineups' => ['france' => $starters],
            'events' => [
                ['team_side' => 'france', 'type' => 'essai', 'player_last_name' => 'Joueur9', 'minute' => 12],
                ['team_side' => 'adversaire', 'type' => 'essai_penalite', 'minute' => 70],
            ],
            'substitutions' => [
                ['team_side' => 'france', 'player_off_last_name' => 'Joueur1', 'player_on_last_name' => 'Remplaçant', 'minute' => 50],
            ],
        ];
    }

    public function test_valid_data_passes_without_warnings(): void
    {
        $this->assertTrue($this->validator->validate($this->validData()), implode("\n", $this->validator->errors()));
        $this->assertSame([], $this->validator->warnings());
    }

    public function test_required_fields(): void
    {
        $this->assertFalse($this->validator->validate([]));
        $this->assertContains('match_date est requis', $this->validator->errors());
        $this->assertContains('opponent_code est requis', $this->validator->errors());
    }

    public function test_rejects_bad_date_and_unknown_opponent(): void
    {
        $data = ['match_date' => '16/11/2024', 'opponent_code' => 'XXX'] + $this->validData();

        $this->assertFalse($this->validator->validate($data));
        $this->assertCount(2, $this->validator->errors());
    }

    public function test_rejects_negative_or_non_integer_scores(): void
    {
        $this->assertFalse($this->validator->validate(['france_score' => -1, 'opponent_score' => '12'] + $this->validData()));
        $this->assertCount(2, $this->validator->errors());
    }

    public function test_rejects_duplicate_and_out_of_range_jerseys(): void
    {
        $data = $this->validData();
        $data['lineups']['france'][1]['jersey'] = 1;
        $data['lineups']['france'][2]['jersey'] = 24;

        $this->assertFalse($this->validator->validate($data));
        $this->assertContains('lineups.france[1].jersey doublon : 1', $this->validator->errors());
        $this->assertContains('lineups.france[2].jersey invalide', $this->validator->errors());
    }

    public function test_rejects_unknown_position_and_event_type(): void
    {
        $data = $this->validData();
        $data['lineups']['france'][0]['position'] = 'pivot';
        $data['events'][0]['type'] = 'but';

        $this->assertFalse($this->validator->validate($data));
        $this->assertCount(2, $this->validator->errors());
    }

    public function test_event_requires_player_except_penalty_try(): void
    {
        $data = $this->validData();
        unset($data['events'][0]['player_last_name']);

        $this->assertFalse($this->validator->validate($data));
        $this->assertSame(['events[0].player_last_name requis (sauf essai_penalite)'], $this->validator->errors());
    }

    public function test_rejects_minutes_out_of_range(): void
    {
        $data = $this->validData();
        $data['events'][0]['minute'] = 121;
        $data['substitutions'][0]['minute'] = -5;

        $this->assertFalse($this->validator->validate($data));
        $this->assertCount(2, $this->validator->errors());
    }

    public function test_warns_on_incomplete_lineup_and_unknown_scorer(): void
    {
        $data = $this->validData();
        array_pop($data['lineups']['france']);
        $data['events'][0]['player_last_name'] = 'Inconnu';

        $this->assertTrue($this->validator->validate($data));
        $this->assertSame([
            'france : 14 titulaires au lieu de 15',
            'events[0] : joueur Inconnu absent des lineups france',
        ], $this->validator->warnings());
    }

    public function test_state_is_reset_between_validations(): void
    {
        $this->validator->validate([]);
        $this->validator->validate($this->validData());

        $this->assertSame([], $this->validator->errors());
    }
}
