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

    /**
     * Foto bawaan untuk venue yang fotonya tidak ada lagi di server
     * (misalnya karena container deploy dibuat ulang). Semua berkas ini
     * ikut tersimpan di repository sehingga selalu tersedia.
     */
    private const FALLBACK_PHOTOS = [
        'uploads/courts/20260425184205-6T9Ggss6.jpg',
        'uploads/courts/20260425184419-6s6ixWyv.jpg',
        'uploads/courts/20261112135504-RiuORs9p.png',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        $url = $this->resolvePhotoUrl($this->photo);

        return $url ?? $this->fallbackPhotoUrl();
    }

    /**
     * Daftar foto galeri venue yang benar-benar bisa dibuka.
     */
    public function getGalleryUrlsAttribute(): array
    {
        $images = is_array($this->gallery) ? $this->gallery : [];

        $urls = [];
        foreach ($images as $image) {
            $url = $this->resolvePhotoUrl(is_string($image) ? $image : null);
            if ($url !== null) {
                $urls[] = $url;
            }
        }

        if ($urls === []) {
            foreach (self::FALLBACK_PHOTOS as $candidate) {
                if (is_file(public_path($candidate))) {
                    $urls[] = asset($candidate);
                }
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
            $relative = substr($path, strlen('storage/'));

            return Storage::disk('public')->exists($relative)
                ? asset($path)
                : null;
        }

        if (str_starts_with($path, 'venues/')) {
            return Storage::disk('public')->exists($path)
                ? asset('storage/' . $path)
                : null;
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        return null;
    }

    private function fallbackPhotoUrl(): ?string
    {
        foreach (self::FALLBACK_PHOTOS as $candidate) {
            if (is_file(public_path($candidate))) {
                return asset($candidate);
            }
        }

        return null;
    }
}
