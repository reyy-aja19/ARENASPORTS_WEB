<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lapangan extends Model
{
    use HasFactory;

    protected $table = 'lapangan';

    protected $fillable = [
        'nama',
        'owner_admin_id',
        'venue_id',
        'jenis',
        'lokasi',
        'latitude',
        'longitude',
        'harga',
        'rating',
        'status',
        'deskripsi',
        'foto',
        'foto_gallery',
        'fasilitas',
        'operational_status',
        'use_venue_location',
        'area_type',
        'floor_type',
        'court_facilities',
    ];

    protected function casts(): array
    {
        return [
            'foto_gallery' => 'array',
            'fasilitas' => 'array',
            'court_facilities' => 'array',
            'use_venue_location' => 'boolean',
        ];
    }

    public function priceRules()
    {
        return $this->hasMany(CourtPriceRule::class, 'lapangan_id')
            ->orderByDesc('priority')
            ->orderBy('start_time');
    }

    public function scopeOperational($query)
    {
        return $query->where(function ($q) {
            $q->where('operational_status', 'Beroperasi')
                ->orWhereNull('operational_status');
        });
    }

    public function scopeBookable($query)
    {
        return $query->operational()->where(function ($q) {
            $q->whereNull('venue_id')
                ->orWhereHas('venue', function ($venue) {
                    $venue->where('status', 'Aktif')
                        ->where(function ($inner) {
                            $inner->where('operational_status', 'Beroperasi')
                                ->orWhereNull('operational_status');
                        });
                });
        });
    }

    public function getIsBookableAttribute(): bool
    {
        if (($this->operational_status ?: 'Beroperasi') !== 'Beroperasi') {
            return false;
        }

        if (!$this->venue_id) {
            return true;
        }

        $venue = $this->relationLoaded('venue') ? $this->venue : $this->venue;

        return $venue
            && $venue->status === 'Aktif'
            && ($venue->operational_status ?: 'Beroperasi') === 'Beroperasi';
    }

    public function getIsUnderMaintenanceAttribute(): bool
    {
        return in_array($this->operational_status, ['Perawatan', 'Perbaikan'], true);
    }

    public function getDisplayLocationAttribute(): string
    {
        if ($this->use_venue_location && $this->venue) {
            return $this->venue->address ?: ($this->venue->name ?? '');
        }

        return $this->lokasi ?: ($this->venue->address ?? '');
    }

    public function getEffectiveLatitudeAttribute()
    {
        if ($this->use_venue_location && $this->venue) {
            return $this->venue->latitude ?? $this->latitude;
        }

        return $this->latitude ?? $this->venue?->latitude;
    }

    public function getEffectiveLongitudeAttribute()
    {
        if ($this->use_venue_location && $this->venue) {
            return $this->venue->longitude ?? $this->longitude;
        }

        return $this->longitude ?? $this->venue?->longitude;
    }

    public function getMinPriceAttribute(): int
    {
        $prices = $this->priceRules->pluck('price')->filter();
        $prices->push((int) $this->harga);

        return (int) $prices->min();
    }

    public function getCourtFacilitiesListAttribute(): array
    {
        return is_array($this->court_facilities) ? array_values($this->court_facilities) : [];
    }

    public function getGalleryUrlsAttribute(): array
    {
        $photos = array_merge(
            [$this->foto],
            is_array($this->foto_gallery) ? $this->foto_gallery : []
        );

        $urls = [];
        foreach ($photos as $photo) {
            $url = $this->resolvePhotoUrl(is_string($photo) ? $photo : null);
            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    private function resolvePhotoUrl(?string $photo): ?string
    {
        $photo = trim((string) $photo);
        if ($photo === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }

        $path = ltrim($photo, '/');

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        return null;
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'lapangan_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'lapangan_id');
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto) {
            return null;
        }

        $photo = trim($this->foto);
        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }

        $path = ltrim($photo, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return asset($path);
    }
}
