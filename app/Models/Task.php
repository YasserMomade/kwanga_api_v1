<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'list_id',
        'designation',
        'completed',
        'has_due_date',
        'due_date',
        'has_reminder',
        'reminder_datetime',
        'has_frequency',
        'frequency_days'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'has_due_date' => 'boolean',
        'has_reminder' => 'boolean',
        'has_frequency' => 'boolean',
        'frequency_days' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function list()
    {
        return $this->belongsTo(ListModel::class, 'list_id');
    }
}
