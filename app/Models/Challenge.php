<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Challenge extends Model
{
    use HasFactory;
    use HasUuid;


    protected $fillable = [
        'id',
        'community_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'status',
    ];


    protected static function booted()
    {
        static::creating(function (Challenge $challenge) {
            if (empty($challenge->id)) {
                $challenge->id = (string) Str::uuid();
            }
        });
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function participants()
    {
        return $this->hasMany(ChallengeParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'challenge_participants'
        );
    }
}
