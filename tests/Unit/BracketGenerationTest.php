<?php

namespace Tests\Unit;

use App\Enums\MatchPhase;
use App\Models\Championship;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\ChampionshipService;
use App\Services\ScoreGeneratorService;
use App\Services\TiebreakResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BracketGenerationTest extends TestCase
{
    use RefreshDatabase;

    private ChampionshipService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $scoreGenerator = $this->createMock(ScoreGeneratorService::class);
        $this->service = new ChampionshipService($scoreGenerator, new TiebreakResolver);
    }

    public function test_bracket_generates_four_matches_for_eight_teams(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);

        $matches = GameMatch::where('championship_id', $championship->id)
            ->where('phase', MatchPhase::Quarterfinal)
            ->get();

        $this->assertCount(4, $matches);
    }

    public function test_bracket_has_no_repeated_teams(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);

        $matches = GameMatch::where('championship_id', $championship->id)
            ->where('phase', MatchPhase::Quarterfinal)
            ->get();

        $teamIds = $matches->flatMap(fn ($m) => [$m->home_team_id, $m->away_team_id]);

        $this->assertEquals($teamIds->count(), $teamIds->unique()->count());
    }

    public function test_bracket_uses_all_eight_teams(): void
    {
        $championship = Championship::factory()->create();
        $teams = Team::factory()->count(8)->create();
        $championship->teams()->attach($teams->pluck('id'));

        $this->service->generateBracket($championship);

        $matches = GameMatch::where('championship_id', $championship->id)
            ->where('phase', MatchPhase::Quarterfinal)
            ->get();

        $usedTeamIds = $matches->flatMap(fn ($m) => [$m->home_team_id, $m->away_team_id])->unique()->sort()->values();
        $expectedIds = $teams->pluck('id')->sort()->values();

        $this->assertEquals($expectedIds, $usedTeamIds);
    }
}
