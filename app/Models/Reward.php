<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_admin_id',
        'venue_id',
        'title',
        'points_required',
        'badge',
        'description',
        'image',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_admin_id');
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }

    public function getProviderLabelAttribute(): string
    {
        if ($this->venue?->name) {
            return $this->venue->name;
        }

        if ($this->owner?->name && $this->owner_admin_id) {
            return $this->owner->name;
        }

        return 'ArenaSports';
    }
}
