<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMatchFactory extends Factory
{

    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'phase' => 'quarterfinal',
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'home_score' => null,
            'away_score' => null,
            'winner_id' => null,
        ];
    }
}
