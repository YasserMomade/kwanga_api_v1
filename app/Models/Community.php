<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Community extends Model
{
    use HasFactory;
    use HasUuid;



    protected $fillable = [
        'id',
        'owner_id',
        'designation',
        'description',
        'objective',
        'whatsapp_link',
        'status',
        'visibility',
        'life_area_id'
    ];

    protected static function booted()
    {
        static::creating(function (Community $community) {
            if (empty($community->id)) {
                $community->id = (string) Str::uuid();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isPublic(): bool
    {
        return $this->visibility  === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->visibility  === 'private';
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class, 'community_id');
    }

    public function lifeArea()
    {
        return $this->belongsTo(LifeArea::class, 'life_area_id');
    }
}
