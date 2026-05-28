<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase' => $this->phase,
            'home_team' => new TeamResource($this->homeTeam),
            'away_team' => new TeamResource($this->awayTeam),
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
            'winner' => new TeamResource($this->whenLoaded('winner')),
        ];
    }
}
