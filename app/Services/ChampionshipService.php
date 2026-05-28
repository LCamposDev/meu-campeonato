<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

class ChampionshipService
{
    public function __construct(
        private ScoreGeneratorService $scoreGenerator
    ) {}

    public function generateBracket(Championship $championship): void
    {
        $teams = $championship->teams()->orderBy('championship_team.created_at')->get()->shuffle();

        $pairs = $teams->chunk(2);

        foreach ($pairs as $pair) {
            GameMatch::create([
                'championship_id' => $championship->id,
                'phase' => 'quarterfinal',
                'home_team_id' => $pair->first()->id,
                'away_team_id' => $pair->last()->id,
            ]);
        }
    }

    public function simulate(Championship $championship): Championship
    {
        $championship->update(['status' => 'in_progress']);

        $this->simulatePhase($championship, 'quarterfinal');

        $winners = $this->getWinners($championship, 'quarterfinal');
        $this->createMatches($championship, $winners, 'semifinal');
        $this->simulatePhase($championship, 'semifinal');

        $losers = $this->getLosers($championship, 'semifinal');
        $this->createMatches($championship, $losers, 'third_place');
        $this->simulatePhase($championship, 'third_place');

        $winners = $this->getWinners($championship, 'semifinal');
        $this->createMatches($championship, $winners, 'final');
        $this->simulatePhase($championship, 'final');

        $championship->update(['status' => 'finished']);

        return $championship->load('matches.homeTeam', 'matches.awayTeam', 'matches.winner');
    }

    private function simulatePhase(Championship $championship, string $phase): void
    {
        $matches = $championship->matches()->where('phase', $phase)->get();

        foreach ($matches as $match) {
            $score = $this->scoreGenerator->generate();

            $match->home_score = $score['home'];
            $match->away_score = $score['away'];
            $match->winner_id = $this->determineWinner($match, $championship);
            $match->save();
        }
    }

    private function determineWinner(GameMatch $match, Championship $championship): int
    {
        if ($match->home_score > $match->away_score) {
            return $match->home_team_id;
        }

        if ($match->away_score > $match->home_score) {
            return $match->away_team_id;
        }

        $homePoints = $this->getAccumulatedScore($match->home_team_id, $championship);
        $awayPoints = $this->getAccumulatedScore($match->away_team_id, $championship);

        if ($homePoints !== $awayPoints) {
            return $homePoints > $awayPoints ? $match->home_team_id : $match->away_team_id;
        }

        $homeJoinedAt = $championship->teams()
            ->where('team_id', $match->home_team_id)
            ->first()->pivot->created_at;

        $awayJoinedAt = $championship->teams()
            ->where('team_id', $match->away_team_id)
            ->first()->pivot->created_at;

        return $homeJoinedAt <= $awayJoinedAt ? $match->home_team_id : $match->away_team_id;
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

        foreach ($matches as $match) {
            if ($match->home_team_id === $teamId) {
                $points += $match->home_score - $match->away_score;
            } else {
                $points += $match->away_score - $match->home_score;
            }
        }

        return $points;
    }

    private function getWinners(Championship $championship, string $phase): Collection
    {
        return $championship->matches()
            ->where('phase', $phase)
            ->with('winner')
            ->get()
            ->pluck('winner');
    }

    private function getLosers(Championship $championship, string $phase): Collection
    {
        return $championship->matches()
            ->where('phase', $phase)
            ->get()
            ->map(function ($match) {
                return $match->winner_id === $match->home_team_id
                    ? $match->awayTeam
                    : $match->homeTeam;
            });
    }

    private function createMatches(Championship $championship, Collection $teams, string $phase): void
    {
        $pairs = $teams->chunk(2);

        foreach ($pairs as $pair) {
            GameMatch::create([
                'championship_id' => $championship->id,
                'phase' => $phase,
                'home_team_id' => $pair->first()->id,
                'away_team_id' => $pair->last()->id,
            ]);
        }
    }

}