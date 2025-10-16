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
        'monthly_Goal_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function monthlyGoal()
    {
        return $this->belongsTo(MonthlyGoal::class, 'monthly_Goal_id');
    }
}
