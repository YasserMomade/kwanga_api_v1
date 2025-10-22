<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LongTermVision extends Model
{

    use HasUuid;
    use HasFactory;

    protected $fillable = [

        'user_id',
        'life_area_id',
        'description',
        'deadline',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lifeArea()
    {
        return $this->belongsTo(LifeArea::class, 'life_area_id');
    }
}
