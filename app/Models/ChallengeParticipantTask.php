<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChallengeParticipantTask extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'id',
        'participant_id',
        'task_id',
        'completed',

    ];

    protected static function booted()
    {
        static::creating(function (ChallengeParticipantTask $ChallengeParticipantTask) {
            if (empty($ChallengeParticipantTask->id)) {
                $ChallengeParticipantTask->id = (string) Str::uuid();
            }
        });
    }

    public function participant()
    {
        return $this->belongsTo(ChallengeParticipant::class);
    }

    public function task()
    {
        return $this->belongsTo(ChallengeTask::class);
    }
}
