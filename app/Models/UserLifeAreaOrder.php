<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLifeAreaOrder extends Model
{
    protected $table = 'user_life_area_orders';

    protected $fillable = [
        'user_id',
        'life_area_id',
        'order_index',
    ];
}
