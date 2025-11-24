<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualGoal extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable =
    [
        'id',
        'description',
        'long_term_vision_id',
        'user_id',
        'year',
        'created_at',
        'updated_at',
    ];

    public function longTermVision()
    {
        return $this->belongsTo(LongTermVision::class, 'long_term_vision_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
