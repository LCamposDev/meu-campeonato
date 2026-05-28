<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\GameMatch;
use Illuminate\Support\Collection;

class TiebreakResolver
{
    public function __construct(
        private ScoreGeneratorService $scoreGenerator
    ) {}

    public function resolve(GameMatch $match, Championship $championship): int
    {
        if ($match->home_score > $match->away_score) {
            return $match->home_team_id;
        }

        if ($match->away_score > $match->home_score) {
            return $match->away_team_id;
        }

        $penalties = $this->scoreGenerator->generatePenalties();
        $match->home_penalties = $penalties['home'];
        $match->away_penalties = $penalties['away'];

        if ($match->home_penalties !== $match->away_penalties) {
            return $match->home_penalties > $match->away_penalties
                ? $match->home_team_id
                : $match->away_team_id;
        }

        $homePoints = $this->getAccumulatedScore($match->home_team_id, $championship);
        $awayPoints = $this->getAccumulatedScore($match->away_team_id, $championship);

        if ($homePoints !== $awayPoints) {
            return $homePoints > $awayPoints ? $match->home_team_id : $match->away_team_id;
        }

        $registrationDates = $this->getRegistrationDates($championship, $match);

        return $registrationDates[$match->home_team_id] <= $registrationDates[$match->away_team_id]
            ? $match->home_team_id
            : $match->away_team_id;
    }

    private function getAccumulatedScore(int $teamId, Championship $championship): int
    {
        $matches = $championship->matches()
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->whereNotNull('home_score')
            ->get();

        $points = 0;

        foreach ($matches as $playedMatch) {
            if ($playedMatch->home_team_id === $teamId) {
                $points += $playedMatch->home_score - $playedMatch->away_score;
            } else {
                $points += $playedMatch->away_score - $playedMatch->home_score;
            }
        }

        return $points;
    }

    private function getRegistrationDates(Championship $championship, GameMatch $match): Collection
    {
        return $championship->teams()
            ->whereIn('team_id', [$match->home_team_id, $match->away_team_id])
            ->get()
            ->mapWithKeys(fn ($team) => [$team->id => $team->pivot->created_at]);
    }
}