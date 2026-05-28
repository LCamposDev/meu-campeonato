<?php

namespace Tests\Unit;

use App\Enums\ChampionshipStatus;
use App\Enums\MatchPhase;
use App\Models\Championship;
use App\Models\Team;
use App\Services\ChampionshipService;
use App\Services\ScoreGeneratorService;
use App\Services\TiebreakResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseAdvancementTest extends TestCase
{
    use RefreshDatabase;

    private ChampionshipService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $scoreGenerator = $this->createMock(ScoreGeneratorService::class);
        $scoreGenerator->method('generate')->willReturn(['home' => 2, 'away' => 1]);
        $scoreGenerator->method('generatePenalties')->willReturn(['home' => 5, 'away' => 3]);

        $tiebreakResolver = new TiebreakResolver($scoreGenerator);
        $this->service = new ChampionshipService($scoreGenerator, $tiebreakResolver);
    }

    public function test_championship_is_finished_after_simulation(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);
        $this->service->simulate($championship);

        $this->assertEquals(ChampionshipStatus::Finished, $championship->fresh()->status);
    }

    public function test_simulation_generates_all_phases(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);
        $this->service->simulate($championship);

        $phases = $championship->matches()
            ->pluck('phase')
            ->map(fn(MatchPhase $phase) => $phase->value)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $this->assertContains(MatchPhase::Quarterfinal->value, $phases);
        $this->assertContains(MatchPhase::Semifinal->value, $phases);
        $this->assertContains(MatchPhase::ThirdPlace->value, $phases);
        $this->assertContains(MatchPhase::Final->value, $phases);
    }

    public function test_simulation_generates_correct_number_of_matches(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);
        $this->service->simulate($championship);

        $this->assertCount(8, $championship->matches);
    }

    public function test_all_matches_have_a_winner(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);
        $this->service->simulate($championship);

        $matchesWithoutWinner = $championship->matches()
            ->whereNull('winner_id')
            ->count();

        $this->assertEquals(0, $matchesWithoutWinner);
    }
}
