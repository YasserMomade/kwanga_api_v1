<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualGoal extends Model
{
    use HasFactory;

    protected $fillable = [

        'description',
        'longTermVision_id',
        'user_id',
        'year',
        'status'
    ];

    public function longTermVision()
    {
        return $this->belongsTo(LongTermVision::class, 'longTermVision_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
