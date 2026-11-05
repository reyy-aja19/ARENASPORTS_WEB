<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueApplicationHistory extends Model
{
    protected $fillable = [
        'venue_application_id', 'actor_id', 'from_status', 'to_status', 'note',
    ];

    public function application()
    {
        return $this->belongsTo(VenueApplication::class, 'venue_application_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
