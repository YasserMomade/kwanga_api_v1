<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListModel extends Model
{

    use HasUuid;
    use HasFactory;

    protected $table = 'lists';

    protected $fillable =
    [
        'id',
        'user_id',
        'designation',
        'type'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
