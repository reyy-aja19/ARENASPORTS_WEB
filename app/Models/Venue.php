<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Venue extends Model
{
    protected $fillable = [
        'owner_admin_id',
        'venue_application_id',
        'name',
        'slug',
        'address',
        'contact_phone',
        'description',
        'photo',
        'sports',
        'status',
        'google_maps_url',
        'operating_hours',
        'city',
        'province',
        'latitude',
        'longitude',
        'facilities',
        'gallery',
        'company_name',
        'business_type',
        'nib',
        'company_address',
        'company_phone',
        'company_description',
    ];

    protected function casts(): array
    {
        return [
            'sports' => 'array',
            'facilities' => 'array',
            'gallery' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_admin_id');
    }

    public function lapangans()
    {
        return $this->hasMany(Lapangan::class, 'venue_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(VenueWithdrawal::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        $photo = trim($this->photo);
        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }

        $path = ltrim($photo, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'venues/')) {
            return asset('storage/' . $path);
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return asset($path);
    }
}
