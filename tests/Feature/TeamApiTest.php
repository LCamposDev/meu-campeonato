<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_team(): void
    {
        $response = $this->postJson('/api/teams', ['name' => 'Flamengo']);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Flamengo']);

        $this->assertDatabaseHas('teams', ['name' => 'Flamengo']);
    }

    public function test_cannot_create_team_without_name(): void
    {
        $response = $this->postJson('/api/teams', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_duplicate_team(): void
    {
        Team::factory()->create(['name' => 'Flamengo']);

        $response = $this->postJson('/api/teams', ['name' => 'Flamengo']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }
}