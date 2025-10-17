<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Project;


class MonthlyGoal extends Model
{

    // protected $casts = [
    //     'status' => 'boolean'
    // ];

    protected $fillable = [

        'description',
        'annual_goals_id',
        'user_id',
        'month',
        'status'
    ];

    public function annualGoal()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goals_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function UpdateStatusFromProjects()
    {

        $total = $this->projects()->count();
        $completed = $this->projects()->where('status', 'completed')->count();

        $this->status = ($total > 0 && $total == $completed);
        $this->save();
    }
}
