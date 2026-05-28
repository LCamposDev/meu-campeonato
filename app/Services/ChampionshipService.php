<?php

namespace App\Services;

use App\Enums\ChampionshipStatus;
use App\Enums\MatchPhase;
use App\Exceptions\ChampionshipAlreadySimulatedException;
use App\Models\Championship;
use App\Models\GameMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChampionshipService
{
    public function __construct(
        private ScoreGeneratorService $scoreGenerator,
        private TiebreakResolver $tiebreakResolver,
    ) {}

    public function create(array $teamIds): Championship
    {
        return DB::transaction(function () use ($teamIds) {
            $championship = Championship::create(['status' => ChampionshipStatus::Pending]);
            $championship->teams()->attach($teamIds);
            $this->generateBracket($championship);

            return $championship->load(['teams', 'matches.homeTeam', 'matches.awayTeam']);
        });
    }

    public function generateBracket(Championship $championship): void
    {
        $teams = $championship->teams()->orderBy('championship_team.created_at')->get()->shuffle();

        foreach ($teams->chunk(2) as $pair) {
            GameMatch::create([
                'championship_id' => $championship->id,
                'phase' => MatchPhase::Quarterfinal,
                'home_team_id' => $pair->first()->id,
                'away_team_id' => $pair->last()->id,
            ]);
        }
    }

    public function simulate(Championship $championship): Championship
    {
        return DB::transaction(function () use ($championship) {
            $championship = Championship::query()
                ->whereKey($championship->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($championship->status !== ChampionshipStatus::Pending) {
                throw new ChampionshipAlreadySimulatedException;
            }

            $championship->update(['status' => ChampionshipStatus::InProgress]);

            $this->simulatePhase($championship, MatchPhase::Quarterfinal);

            $winners = $this->getWinners($championship, MatchPhase::Quarterfinal);
            $this->createMatches($championship, $winners, MatchPhase::Semifinal);
            $this->simulatePhase($championship, MatchPhase::Semifinal);

            $losers = $this->getLosers($championship, MatchPhase::Semifinal);
            $this->createMatches($championship, $losers, MatchPhase::ThirdPlace);
            $this->simulatePhase($championship, MatchPhase::ThirdPlace);

            $winners = $this->getWinners($championship, MatchPhase::Semifinal);
            $this->createMatches($championship, $winners, MatchPhase::Final);
            $this->simulatePhase($championship, MatchPhase::Final);

            $championship->update(['status' => ChampionshipStatus::Finished]);

            return $championship->load('matches.homeTeam', 'matches.awayTeam', 'matches.winner');
        });
    }

    private function simulatePhase(Championship $championship, MatchPhase $phase): void
    {
        $matches = $championship->matches()->where('phase', $phase)->get();

        foreach ($matches as $match) {
            $score = $this->scoreGenerator->generate();

            $match->home_score = $score['home'];
            $match->away_score = $score['away'];
            $match->winner_id = $this->tiebreakResolver->resolve($match, $championship);
            $match->save();
        }
    }

    private function getWinners(Championship $championship, MatchPhase $phase): Collection
    {
        return $championship->matches()
            ->where('phase', $phase)
            ->with('winner')
            ->get()
            ->pluck('winner');
    }

    private function getLosers(Championship $championship, MatchPhase $phase): Collection
    {
        return $championship->matches()
            ->where('phase', $phase)
            ->with(['homeTeam', 'awayTeam'])
            ->get()
            ->map(fn (GameMatch $match) => $match->winner_id === $match->home_team_id
                ? $match->awayTeam
                : $match->homeTeam);
    }

    private function createMatches(Championship $championship, Collection $teams, MatchPhase $phase): void
    {
        foreach ($teams->chunk(2) as $pair) {
            GameMatch::create([
                'championship_id' => $championship->id,
                'phase' => $phase,
                'home_team_id' => $pair->first()->id,
                'away_team_id' => $pair->last()->id,
            ]);
        }
    }
}
