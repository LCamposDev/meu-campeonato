<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;

class TeamController extends Controller
{
    public function store(StoreTeamRequest $request)
    {
        $team = Team::create($request->validated());

        return (new TeamResource($team))
            ->response()
            ->setStatusCode(201);
    }
}