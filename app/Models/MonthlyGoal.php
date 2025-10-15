<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyGoal extends Model
{
    protected $fillable = [

        'description',
        'annualGoal_id',
        'user_id',
        'month',
        'status'
    ];

    public function longTermVision()
    {
        return $this->belongsTo(AnnualGoal::class, 'annualGoals_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
