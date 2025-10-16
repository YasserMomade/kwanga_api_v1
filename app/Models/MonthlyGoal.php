<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyGoal extends Model
{
    protected $fillable = [

        'description',
        'annual_goals_id',
        'user_id',
        'month',
        'status'
    ];

    public function AnnualGoals()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goals_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
