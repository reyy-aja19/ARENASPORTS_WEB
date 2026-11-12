<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueApplication extends Model
{
    protected $fillable = [
        'user_id',
        'venue_name',
        'address',
        'contact_phone',
        'description',
        'sports',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'applicant_phone',
        'ownership_type',
        'google_maps_url',
        'operating_hours',
        'owner_address',
        'identity_number',
        'company_name',
        'business_type',
        'nib',
        'company_address',
        'city',
        'province',
        'company_phone',
        'company_description',
        'latitude',
        'longitude',
        'facilities',
        'applicant_relationship',
        'responsibility_agreed_at',
    ];

    protected function casts(): array
    {
        return [
            'sports' => 'array',
            'reviewed_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'facilities' => 'array',
            'responsibility_agreed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function histories()
    {
        return $this->hasMany(VenueApplicationHistory::class, 'venue_application_id')->latest();
    }

    public function documents()
    {
        return $this->hasMany(
            VenueApplicationDocument::class,
            'venue_application_id'
        );
    }
}
