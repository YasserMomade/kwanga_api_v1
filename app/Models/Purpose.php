<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purpose extends Model
{
    protected $fillable = [

        'designation',
        'lifeArea_id',
        'user_id',
    ];

    public function lifeArea()
    {
        return $this->belongsTo(LifeArea::class,'lifeArea_id');
    }

     public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
