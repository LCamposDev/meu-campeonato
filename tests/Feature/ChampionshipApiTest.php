<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Services\ScoreGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ScoreGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generate')->andReturn(['home' => 2, 'away' => 1]);
        });
    }

    public function test_can_list_championships(): void
    {
        $response = $this->getJson('/api/championships');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_can_create_championship_with_eight_teams(): void
    {
        $teams = Team::factory()->count(8)->create();

        $response = $this->postJson('/api/championships', [
            'team_ids' => $teams->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['status' => 'pending'])
                 ->assertJsonCount(4, 'data.phases.quarterfinal');
    }

    public function test_cannot_create_championship_with_less_than_eight_teams(): void
    {
        $teams = Team::factory()->count(5)->create();

        $response = $this->postJson('/api/championships', [
            'team_ids' => $teams->pluck('id')->toArray(),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['team_ids']);
    }

    public function test_cannot_create_championship_with_more_than_eight_teams(): void
    {
        $teams = Team::factory()->count(10)->create();

        $response = $this->postJson('/api/championships', [
            'team_ids' => $teams->pluck('id')->toArray(),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['team_ids']);
    }

    public function test_cannot_create_championship_with_duplicate_teams(): void
    {
        $teams = Team::factory()->count(7)->create();
        $teamIds = $teams->pluck('id')->toArray();
        $teamIds[] = $teamIds[0];

        $response = $this->postJson('/api/championships', [
            'team_ids' => $teamIds,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['team_ids.0']);
    }

    public function test_can_show_championship(): void
    {
        $teams = Team::factory()->count(8)->create();

        $championship = $this->postJson('/api/championships', [
            'team_ids' => $teams->pluck('id')->toArray(),
        ])->json('data');

        $response = $this->getJson("/api/championships/{$championship['id']}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $championship['id']]);
    }

    public function test_can_simulate_championship(): void
    {
        $teams = Team::factory()->count(8)->create();

        $championship = $this->postJson('/api/championships', [
            'team_ids' => $teams->pluck('id')->toArray(),
        ])->json('data');

        $response = $this->postJson("/api/championships/{$championship['id']}/simulate");

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'finished'])
                 ->assertJsonStructure(['data' => ['result' => ['1st', '2nd', '3rd', '4th']]]);
    }

    public function test_cannot_simulate_championship_twice(): void
    {
        $teams = Team::factory()->count(8)->create();

        $championship = $this->postJson('/api/championships', [
            'team_ids' => $teams->pluck('id')->toArray(),
        ])->json('data');

        $this->postJson("/api/championships/{$championship['id']}/simulate");
        $response = $this->postJson("/api/championships/{$championship['id']}/simulate");

        $response->assertStatus(422);
    }
}