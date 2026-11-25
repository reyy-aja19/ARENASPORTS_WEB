<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenMatchParticipant extends Model
{
    protected $fillable = [
        'open_match_id',
        'user_id',
        'role',
        'team_name',
        'member_count',
    ];

    protected function casts(): array
    {
        return [
            'member_count' => 'integer',
        ];
    }

    public function openMatch()
    {
        return $this->belongsTo(OpenMatch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsHostAttribute(): bool
    {
        return strcasecmp((string) $this->role, 'Host') === 0;
    }
}
