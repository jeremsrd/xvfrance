<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\RugbyMatch;
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

        $nzl = Country::factory()->create(['code' => 'NZL']);
        RugbyMatch::factory()->create(['match_date' => '2024-11-16', 'opponent_id' => $nzl->id]);
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
        $bench = [['jersey' => 16, 'first_name' => 'Prénom', 'last_name' => 'Remplaçant', 'is_starter' => false, 'position' => 'talonneur']];

        return [
            'match_date' => '2024-11-16',
            'opponent_code' => 'NZL',
            'france_score' => 5,
            'opponent_score' => 7,
            'lineups' => ['france' => [...$starters, ...$bench]],
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

    public function test_accepts_match_info_fields(): void
    {
        $data = [
            'referee' => 'Luke Pearce',
            'referee_country_code' => 'NZL',
            'attendance' => 29152,
            'kickoff_time' => '19:05',
            'weather' => 'Pluie',
        ] + $this->validData();

        $this->assertTrue($this->validator->validate($data), implode("\n", $this->validator->errors()));
    }

    public function test_rejects_invalid_match_info_fields(): void
    {
        $data = [
            'referee_country_code' => 'XXX',
            'attendance' => '29 152',
            'kickoff_time' => '7pm',
            'weather' => '',
        ] + $this->validData();

        $this->assertFalse($this->validator->validate($data));
        $this->assertEqualsCanonicalizing([
            'weather doit être un texte non vide',
            'attendance doit être un entier positif',
            'kickoff_time invalide (format attendu : HH:MM, heure locale)',
            'referee_country_code sans referee',
            'referee_country_code inconnu en base : "XXX"',
        ], $this->validator->errors());
    }

    public function test_new_match_requires_scores_and_venue(): void
    {
        $data = ['match_date' => '2024-11-23'] + $this->validData();
        unset($data['france_score']);

        $this->assertFalse($this->validator->validate($data));
        $this->assertEqualsCanonicalizing([
            'france_score requis pour créer le match (absent de la base)',
            'venue requis pour créer le match (absent de la base)',
        ], $this->validator->errors());
    }

    public function test_new_venue_requires_city_and_country(): void
    {
        $data = ['venue' => 'Stade inconnu', 'competition' => 'Inconnue', 'stage' => 'barrage'] + $this->validData();

        $this->assertFalse($this->validator->validate($data));
        $this->assertContains('venue_city requis pour un stade absent de la base (Stade inconnu)', $this->validator->errors());
        $this->assertContains('venue_country_code requis et connu en base pour un stade absent de la base (Stade inconnu)', $this->validator->errors());
        $this->assertContains('stage invalide : "barrage"', $this->validator->errors());
        $this->assertCount(4, $this->validator->errors());
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
        unset($data['lineups']['france'][14]);
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

    public function test_rejects_events_that_do_not_add_up_to_the_score(): void
    {
        $this->assertFalse($this->validator->validate(['france_score' => 8] + $this->validData()));
        $this->assertSame(['france : les événements totalisent 5 points pour un score de 8'], $this->validator->errors());
    }

    public function test_penalty_try_is_worth_seven_points_since_2017_only(): void
    {
        // En 2010 : essai de pénalité = 5 points (transformation à part)
        RugbyMatch::factory()->create(['match_date' => '2010-11-13', 'opponent_id' => Country::where('code', 'NZL')->value('id')]);
        $this->assertFalse($this->validator->validate(['match_date' => '2010-11-13'] + $this->validData()));
        $this->assertSame(['adversaire : les événements totalisent 5 points pour un score de 7'], $this->validator->errors());
    }

    public function test_substitution_flow(): void
    {
        $data = $this->validData();
        $data['substitutions'][] = ['team_side' => 'france', 'player_off_last_name' => 'Joueur1', 'player_on_last_name' => 'Joueur2', 'minute' => 60];
        $data['substitutions'][] = ['team_side' => 'france', 'player_off_last_name' => 'Inconnu', 'player_on_last_name' => 'Remplaçant', 'minute' => 70];

        $this->assertFalse($this->validator->validate($data));
        $this->assertSame(['france : remplacement Inconnu → Remplaçant : joueur absent de la composition'], $this->validator->errors());

        array_pop($data['substitutions']);
        $this->assertTrue($this->validator->validate($data));
        $this->assertContains("france : remplacement Joueur1 → Joueur2 : le sortant n'est pas sur le terrain à ce moment-là", $this->validator->warnings());
    }

    public function test_warns_when_a_new_player_looks_like_a_known_one(): void
    {
        $france = \App\Models\Country::factory()->create(['code' => 'FRA']);
        \App\Models\Player::factory()->create(['country_id' => $france->id, 'first_name' => 'Tom', 'last_name' => 'Spring']);
        \App\Models\Player::factory()->create(['country_id' => $france->id, 'first_name' => 'Oscar', 'last_name' => 'Jégou']);
        $data = $this->validData();
        $data['lineups']['france'][0] = ['jersey' => 1, 'first_name' => 'Max', 'last_name' => 'Spring', 'is_starter' => true];
        $data['lineups']['france'][1] = ['jersey' => 2, 'first_name' => 'Oscar', 'last_name' => 'Jegou', 'is_starter' => true];
        $data['substitutions'] = [];

        $this->assertTrue($this->validator->validate($data));
        $this->assertSame([
            'france : Max Spring sera créé, mais Tom Spring existe déjà',
            'france : Oscar Jegou sera créé, mais Oscar Jégou existe déjà',
        ], $this->validator->warnings());
    }
}
