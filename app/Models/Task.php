<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'designation',
        'status',
        'deadline',
        'reminder_time',
        'recurring'
    ];

    protected $casts = [
        'recurring' => 'boolean',
    ];




    public function project()
    {
        return $this->belongsTo(project::class);
    }
}
