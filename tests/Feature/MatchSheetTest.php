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
        $this->match = RugbyMatch::factory()->score(14, 3)->create(['match_date' => '2024-02-10', 'opponent_id' => $england->id, 'is_home' => false]);
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
        $this->assertSame([[5, 0], [7, 0], [7, 3], [14, 3]], array_column($sheet->timeline(), 'score'));
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

    public function test_minutes_played_only_when_certain(): void
    {
        [$starter, $other, $bench1, $bench2] = Player::factory()->count(4)->create(['country_id' => $this->fr->country_id]);
        foreach ([[$starter, 1, true], [$other, 2, true], [$bench1, 16, false], [$bench2, 17, false]] as [$p, $n, $start]) {
            MatchLineup::factory()->create(['match_id' => $this->match->id, 'player_id' => $p->id, 'jersey_number' => $n, 'is_starter' => $start]);
        }
        MatchSubstitution::factory()->create(['match_id' => $this->match->id, 'player_off_id' => $starter->id, 'player_on_id' => $bench1->id, 'minute' => 50]);

        // Un seul remplacement sur deux remplaçants : seuls les joueurs concernés sont certains
        $minutes = fn () => array_column(array_merge(...array_values($this->sheet()->lineup(TeamSide::FRANCE))), 'minutes', 'jersey');
        $this->assertSame([1 => 50, 2 => null, 16 => 30, 17 => null], $minutes());

        // Tous les remplaçants entrés : les titulaires non remplacés ont joué 80 minutes
        MatchSubstitution::factory()->create(['match_id' => $this->match->id, 'player_off_id' => $bench1->id, 'player_on_id' => $bench2->id, 'minute' => 70]);
        $this->assertSame([1 => 50, 2 => 80, 16 => 20, 17 => 10], $minutes());

        // Un carton rouge arrête le compteur
        $this->event(EventType::CARTON_ROUGE, 30, TeamSide::FRANCE, $other);
        $this->assertSame(30, $minutes()[2]);
    }

    public function test_other_video_sources_get_a_link_card(): void
    {
        $this->match->update(['video_url' => 'https://www.tf1.fr/tf1/nations-championship/videos/australie-france-voir-le-resume-de-10-minutes-54462699.html']);

        $this->get(route('matches.show', $this->match))
            ->assertOk()
            ->assertSee('Résumé vidéo')
            ->assertSee('Voir le résumé sur TF1+')
            ->assertDontSee('youtube-nocookie.com', false);
    }

    public function test_broadcaster_player_is_embedded_when_given(): void
    {
        $this->match->update([
            'video_url' => 'https://www.tf1.fr/tf1/nations-championship/videos/australie-france-voir-le-resume-de-10-minutes-54462699.html',
            'video_embed_url' => 'https://www.tf1.fr/player/1aac044f-d0b3-4296-b97b-04f828daed7e',
        ]);

        $this->get(route('matches.show', $this->match))
            ->assertOk()
            ->assertSee('src="https://www.tf1.fr/player/1aac044f-d0b3-4296-b97b-04f828daed7e"', false)
            ->assertSee('Voir sur TF1+')
            ->assertDontSee('Voir le résumé sur TF1+');
    }

    public function test_youtube_video_summary_is_embedded(): void
    {
        $this->get(route('matches.show', $this->match))->assertOk()->assertDontSee('Résumé vidéo');

        $this->match->update(['video_url' => 'https://youtu.be/fBxLPuhXs7s?t=5']);

        $this->get(route('matches.show', $this->match))
            ->assertOk()
            ->assertSee('Résumé vidéo')
            ->assertSee('https://www.youtube-nocookie.com/embed/fBxLPuhXs7s', false)
            ->assertSee('href="https://youtu.be/fBxLPuhXs7s?t=5"', false);
    }

    public function test_youtube_id_extraction(): void
    {
        $this->assertSame('fBxLPuhXs7s', RugbyMatch::youtubeId('https://www.youtube.com/watch?v=fBxLPuhXs7s'));
        $this->assertSame('fBxLPuhXs7s', RugbyMatch::youtubeId('https://m.youtube.com/watch?si=abc&v=fBxLPuhXs7s&t=3'));
        $this->assertSame('fBxLPuhXs7s', RugbyMatch::youtubeId('https://www.youtube.com/shorts/fBxLPuhXs7s'));
        $this->assertNull(RugbyMatch::youtubeId('https://vimeo.com/123456'));
        $this->assertNull(RugbyMatch::youtubeId('https://www.youtube.com/watch?v=trop-court'));
        $this->assertNull(RugbyMatch::youtubeId(null));
    }
}
