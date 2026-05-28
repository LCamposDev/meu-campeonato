<?php

namespace App\Http\Controllers;

use App\Enums\ChampionshipStatus;
use App\Exceptions\ChampionshipAlreadySimulatedException;
use App\Http\Requests\StoreChampionshipRequest;
use App\Http\Resources\ChampionshipResource;
use App\Models\Championship;
use App\Services\ChampionshipService;
use Illuminate\Http\JsonResponse;

class ChampionshipController extends Controller
{
    public function __construct(
        private ChampionshipService $championshipService
    ) {}

    public function index()
    {
        $championships = Championship::with(['teams', 'matches.homeTeam', 'matches.awayTeam', 'matches.winner'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return ChampionshipResource::collection($championships);
    }

    public function store(StoreChampionshipRequest $request)
    {
        $championship = $this->championshipService->create($request->team_ids);

        return (new ChampionshipResource($championship))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Championship $championship)
    {
        $championship->load(['teams', 'matches.homeTeam', 'matches.awayTeam', 'matches.winner']);

        return new ChampionshipResource($championship);
    }

    public function simulate(Championship $championship): ChampionshipResource|JsonResponse
    {
        try {
            $championship = $this->championshipService->simulate($championship);
        } catch (ChampionshipAlreadySimulatedException) {
            return response()->json([
                'message' => 'Este campeonato já foi simulado.',
            ], 422);
        }

        $championship->load(['teams', 'matches.homeTeam', 'matches.awayTeam', 'matches.winner']);

        return new ChampionshipResource($championship);
    }
}
