<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'championship_id',
        'phase',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'winner_id',
    ];

    public function championship()
    {
        return $this->belongsTo(championship::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }
}
