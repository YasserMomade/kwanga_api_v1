<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChallengeTask extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'id',
        'challenge_id',
        'title',
        'description',
        'order_index',
    ];

    protected static function booted()
    {
        static::creating(function (ChallengeTask $challengeTask) {
            if (empty($challengeTask->id)) {
                $challengeTask->id = (string) Str::uuid();
            }
        });
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
