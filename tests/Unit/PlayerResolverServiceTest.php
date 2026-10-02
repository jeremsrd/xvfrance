<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\Player;
use App\Services\PlayerResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private Country $nzl;
    private PlayerResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nzl = Country::factory()->create(['code' => 'NZL']);
        $this->resolver = new PlayerResolverService();
    }

    public function test_homonyms_with_different_first_names_are_distinct_players(): void
    {
        $scott = $this->resolver->resolve('Barrett', 'Scott', $this->nzl);
        $beauden = $this->resolver->resolve('Barrett', 'Beauden', $this->nzl);
        $jordie = $this->resolver->resolve('Barrett', 'Jordie', $this->nzl);

        $this->assertCount(3, collect([$scott->id, $beauden->id, $jordie->id])->unique());
        $this->assertSame(3, $this->resolver->getCreatedCount());
    }

    public function test_finds_existing_player_by_exact_name(): void
    {
        $existing = Player::factory()->create(['first_name' => 'Beauden', 'last_name' => 'Barrett', 'country_id' => $this->nzl->id]);

        $this->assertTrue($existing->is($this->resolver->resolve('barrett', 'BEAUDEN', $this->nzl)));
        $this->assertSame(0, $this->resolver->getCreatedCount());
    }

    public function test_falls_back_on_last_name_with_compatible_first_name(): void
    {
        $existing = Player::factory()->create(['first_name' => 'Grégory', 'last_name' => 'Alldritt', 'country_id' => $this->nzl->id]);

        $this->assertTrue($existing->is($this->resolver->resolve('Alldritt', 'Gregory', $this->nzl)));
        $this->assertTrue($existing->is((new PlayerResolverService())->resolve('Alldritt', 'G.', $this->nzl)));
        $this->assertTrue($existing->is((new PlayerResolverService())->resolve('Alldritt', null, $this->nzl)));
    }

    public function test_ambiguous_last_name_returns_null(): void
    {
        Player::factory()->count(2)->sequence(['first_name' => 'Scott'], ['first_name' => 'Beauden'])
            ->create(['last_name' => 'Barrett', 'country_id' => $this->nzl->id]);

        $this->assertNull($this->resolver->resolve('Barrett', null, $this->nzl));
    }

    public function test_players_from_another_country_are_ignored(): void
    {
        Player::factory()->create(['first_name' => 'Antoine', 'last_name' => 'Dupont']);

        $created = $this->resolver->resolve('Dupont', 'Antoine', $this->nzl);

        $this->assertSame($this->nzl->id, $created->country_id);
        $this->assertNotNull($created->slug);
    }
}
