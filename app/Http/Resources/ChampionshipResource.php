<?php

namespace App\Http\Resources;

use App\Enums\ChampionshipStatus;
use App\Enums\MatchPhase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChampionshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $matches = $this->matches->groupBy(fn ($match) => $match->phase->value);

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
            'phases' => [
                'quarterfinal' => GameMatchResource::collection($matches->get(MatchPhase::Quarterfinal->value, collect())),
                'semifinal' => GameMatchResource::collection($matches->get(MatchPhase::Semifinal->value, collect())),
                'third_place' => GameMatchResource::collection($matches->get(MatchPhase::ThirdPlace->value, collect())),
                'final' => GameMatchResource::collection($matches->get(MatchPhase::Final->value, collect())),
            ],
            'result' => $this->when($this->status === ChampionshipStatus::Finished, function () use ($matches) {
                $final = $matches->get(MatchPhase::Final->value)?->first();
                $thirdPlace = $matches->get(MatchPhase::ThirdPlace->value)?->first();

                if (! $final?->winner || ! $thirdPlace?->winner) {
                    return null;
                }

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
