<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'id',
        'user_id',
        'list_id',
        'project_id',
        'description',
        'order_index',
        'deadline',
        'time',
        'frequency',
        'completed',
        'linked_action_id',
    ];

    protected $casts = [
        'deadline'    => 'datetime',
        'time'        => 'datetime',
        'frequency'   => 'array',
        'completed'   => 'boolean',
        'order_index' => 'integer',
    ];



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function list()
    {
        return $this->belongsTo(ListModel::class, 'list_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
