<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purpose extends Model
{

    use HasUuid;
    use HasFactory;

    protected $fillable = [
        'id',
        'description',
        'life_area_id',
        'user_id',
    ];

    public function lifeArea()
    {
        return $this->belongsTo(LifeArea::class, 'life_area_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
