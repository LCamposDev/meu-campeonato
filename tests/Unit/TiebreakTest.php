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

class TiebreakTest extends TestCase
{
    use RefreshDatabase;

    private TiebreakResolver $tiebreakResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $scoreGenerator = $this->createMock(ScoreGeneratorService::class);
        $scoreGenerator->method('generatePenalties')->willReturn(['home' => 5, 'away' => 3]);

        $this->tiebreakResolver = new TiebreakResolver($scoreGenerator);
    }

    public function test_team_with_more_points_wins_on_draw(): void
    {
        $championship = Championship::factory()->create();
        $home = Team::factory()->create(['name' => 'Time A']);
        $away = Team::factory()->create(['name' => 'Time B']);

        $championship->teams()->attach([$home->id, $away->id]);

        GameMatch::factory()->create([
            'championship_id' => $championship->id,
            'phase'           => MatchPhase::Quarterfinal,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'home_score'      => 3,
            'away_score'      => 0,
            'winner_id'       => $home->id,
        ]);

        $match = GameMatch::factory()->create([
            'championship_id' => $championship->id,
            'phase'           => MatchPhase::Semifinal,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'home_score'      => 1,
            'away_score'      => 1,
        ]);

        $winnerId = $this->tiebreakResolver->resolve($match, $championship);

        $this->assertEquals($home->id, $winnerId);
    }

    public function test_first_registered_team_wins_when_points_are_equal(): void
    {
        $championship = Championship::factory()->create();
        $home = Team::factory()->create(['name' => 'Time A']);
        $away = Team::factory()->create(['name' => 'Time B']);

        $championship->teams()->attach($home->id);
        $championship->teams()->attach($away->id);

        $match = GameMatch::factory()->create([
            'championship_id' => $championship->id,
            'phase'           => MatchPhase::Semifinal,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'home_score'      => 1,
            'away_score'      => 1,
        ]);

        $winnerId = $this->tiebreakResolver->resolve($match, $championship);

        $this->assertEquals($home->id, $winnerId);
    }

    public function test_penalties_are_used_on_draw(): void
    {
        $championship = Championship::factory()->create();
        $home = Team::factory()->create(['name' => 'Time A']);
        $away = Team::factory()->create(['name' => 'Time B']);

        $championship->teams()->attach([$home->id, $away->id]);

        $match = GameMatch::factory()->create([
            'championship_id' => $championship->id,
            'phase'           => MatchPhase::Semifinal,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'home_score'      => 1,
            'away_score'      => 1,
        ]);

        $winnerId = $this->tiebreakResolver->resolve($match, $championship);

        $this->assertEquals($home->id, $winnerId);
        $this->assertEquals(5, $match->home_penalties);
        $this->assertEquals(3, $match->away_penalties);
    }
}