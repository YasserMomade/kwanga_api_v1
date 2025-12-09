<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    use HasUuid;


    protected $fillable = [
        'id',
        'user_id',
        'monthly_goal_id',
        'title',
        'purpose',
        'expected_result',
        'is_archived',
        'created_at',
        'updated_at',

    ];

    protected $casts = [
        'brainstorm_ideas' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monthly_goals()
    {
        return $this->belongsTo(MonthlyGoal::class, 'monthly_goal_id');
    }

    public function actions()
    {
        return $this->hasMany(ProjectAction::class, 'project_id');
    }
}
