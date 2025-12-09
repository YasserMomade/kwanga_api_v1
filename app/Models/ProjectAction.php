<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAction extends Model
{
    use HasFactory;
    use HasUuid;


    protected $fillable = [
        'id',
        'project_id',
        'description',
        'order_index',
        'is_done',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'is_done' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function Order($query)
    {
        return $query->orderBy('order_index');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'linked_action_id');
    }
}
