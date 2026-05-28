<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChampionshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $matches = $this->matches->groupBy('phase');

        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'created_at'  => $this->created_at,
            'teams'       => TeamResource::collection($this->whenLoaded('teams')),
            'phases'      => [
                'quarterfinal' => GameMatchResource::collection($matches->get('quarterfinal', collect())),
                'semifinal'    => GameMatchResource::collection($matches->get('semifinal', collect())),
                'third_place'  => GameMatchResource::collection($matches->get('third_place', collect())),
                'final'        => GameMatchResource::collection($matches->get('final', collect())),
            ],
            'result'      => $this->when($this->status === 'finished', function () use ($matches) {
                $final      = $matches->get('final')->first();
                $thirdPlace = $matches->get('third_place')->first();

                return [
                    '1st' => new TeamResource($final->winner),
                    '2nd' => new TeamResource(
                        $final->winner_id === $final->home_team_id ? $final->awayTeam : $final->homeTeam
                    ),
                    '3rd' => new TeamResource($thirdPlace->winner),
                    '4th' => new TeamResource(
                        $thirdPlace->winner_id === $thirdPlace->home_team_id ? $thirdPlace->awayTeam : $thirdPlace->homeTeam
                    ),
                ];
            }),
        ];
    }
}