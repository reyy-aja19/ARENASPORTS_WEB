<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'points_required',
        'badge',
        'description',
        'image',
        'status',
    ];

    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }
}
