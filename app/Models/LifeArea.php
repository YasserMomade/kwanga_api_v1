<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifeArea extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [

        'id',
        'designation',
        'icon_path',
        'user_id',
        'is_default',
        'created_at',
        'updated_at',

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

    public function communities()
    {
        return $this->hasMany(Community::class, 'life_area_id');
    }
}
