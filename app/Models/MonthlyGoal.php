<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Traits\HasUuid;

class MonthlyGoal extends Model
{

    use HasUuid;
    use HasFactory;

    protected $fillable = [

        'id',
        'description',
        'annual_goals_id',
        'user_id',
        'month',
        'created_at',
        'updated_at',
    ];

    public function annualGoal()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goals_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
