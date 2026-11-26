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
        'entity_type',
        'operational_status',
        'opening_time',
        'closing_time',
        'operating_days',
    ];

    protected function casts(): array
    {
        return [
            'sports' => 'array',
            'facilities' => 'array',
            'gallery' => 'array',
            'operating_days' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function getIsOperatingAttribute(): bool
    {
        return ($this->operational_status ?: 'Beroperasi') === 'Beroperasi';
    }

    /**
     * Nama pengelola yang ditampilkan di halaman publik. Perusahaan hanya
     * dipakai jika memang tersedia; jika tidak, gunakan nama pemilik venue.
     */
    public function getManagedByAttribute(): ?string
    {
        $company = trim((string) $this->company_name);

        if ($company !== '' && $this->entity_type !== 'Perorangan') {
            return $company;
        }

        return $this->owner?->name ?: $company ?: null;
    }

    public function getCompanyInformationAvailableAttribute(): bool
    {
        return collect([
            $this->company_name,
            $this->company_address,
            $this->company_phone,
            $this->company_description,
            $this->nib,
        ])->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty();
    }

    public function getOperatingDaysListAttribute(): array
    {
        $days = $this->operating_days;

        if (!is_array($days) || $days === []) {
            return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        }

        return array_values(array_intersect(
            ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            $days
        ));
    }

    public function getOperatingHoursTextAttribute(): string
    {
        if (!$this->opening_time && !$this->closing_time) {
            return trim((string) $this->operating_hours) ?: 'Belum diinformasikan';
        }

        $open = $this->formatTime($this->opening_time);
        $close = $this->formatTime($this->closing_time);

        if ($open && $close) {
            return $open . ' – ' . $close;
        }

        return $open ?: ($close ?: 'Belum diinformasikan');
    }

    public function getOperatingDaysTextAttribute(): string
    {
        $days = $this->operating_days_list;

        if (count($days) === 7) {
            return 'Setiap hari';
        }

        return implode(', ', $days);
    }

    /**
     * Foto utama + galeri, dipakai oleh slider di halaman detail venue.
     */
    public function getPhotoGalleryUrlsAttribute(): array
    {
        $urls = [];

        if ($cover = $this->photo_url) {
            $urls[] = $cover;
        }

        foreach ($this->gallery_urls as $url) {
            if (!in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function formatTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
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
