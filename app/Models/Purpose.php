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

    public function user()
    {
        return $this->belongsTo(LifeArea::class);
    }
}
