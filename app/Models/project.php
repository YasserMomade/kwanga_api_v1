<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class project extends Model
{

    protected $fillable = [
        'designation',
        'purpose',
        'expected_result',
        'status',
        'user_id',
        'monthly_goals_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function monthlyGoals()
    {
        return $this->belongsTo(MonthlyGoal::class, 'monthly_goals_id');
    }
}
