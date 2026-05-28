<?php

namespace Database\Factories;

use App\Enums\ChampionshipStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChampionshipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => ChampionshipStatus::Pending,
        ];
    }
}
