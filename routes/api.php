<?php

use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::apiResource('teams', TeamController::class)->only(['index', 'store']);

Route::apiResource('championships', ChampionshipController::class)->only(['index', 'store', 'show']);
Route::post('championships/{championship}/simulate', [ChampionshipController::class, 'simulate']);