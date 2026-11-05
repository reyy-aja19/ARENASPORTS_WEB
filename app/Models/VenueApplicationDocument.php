<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueApplicationDocument extends Model
{
    protected $fillable = [
        'venue_application_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function application()
    {
        return $this->belongsTo(VenueApplication::class, 'venue_application_id');
    }
}
