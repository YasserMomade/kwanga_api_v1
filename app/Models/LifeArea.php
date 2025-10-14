<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifeArea extends Model
{
    use HasFactory;

    protected $fillable = [

        'designation',
        'icon_path',
        'user_id',
        'is_default'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    //Pegar areas da vida que vem ordefaulr
    public function getDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
