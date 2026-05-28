<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChampionshipRequest;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use App\Services\ChampionshipService;

class ChampionshipController extends Controller
{
    public function __construct(
        private ChampionshipService $championshipService
    ) {}

    public function index()
    {
        $championships = Championship::with(['teams', 'matches.homeTeam', 'matches.awayTeam', 'matches.winner'])
            ->orderByDesc('created_at')
            ->get();

        return ChampionshipResource::collection($championships);
    }

    public function store(StoreChampionshipRequest $request)
    {
        $championship = Championship::create(['status' => 'pending']);
        $championship->teams()->attach($request->team_ids);

        $this->championshipService->generateBracket($championship);

        $championship->load(['teams', 'matches.homeTeam', 'matches.awayTeam']);

        return (new ChampionshipResource($championship))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Championship $championship)
    {
        $championship->load(['teams', 'matches.homeTeam', 'matches.awayTeam', 'matches.winner']);

        return new ChampionshipResource($championship);
    }

    public function simulate(Championship $championship)
    {
        if ($championship->status !== 'pending') {
            return response()->json([
                'message' => 'Este campeonato já foi simulado.',
            ], 422);
        }

        $championship = $this->championshipService->simulate($championship);

        $championship->load(['teams', 'matches.homeTeam', 'matches.awayTeam', 'matches.winner']);

        return new ChampionshipResource($championship);
    }
}