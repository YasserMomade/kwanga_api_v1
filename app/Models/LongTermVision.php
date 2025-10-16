<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LongTermVision extends Model
{
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
