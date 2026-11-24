<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'jenis',
        'tanggal',
        'start_time',
        'end_time',
        'lokasi',
        'kuota',
        'registration_fee',
        'prize',
        'contact_phone',
        'registration_deadline',
        'venue_id',
        'deskripsi',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'registration_deadline' => 'date',
            'registration_fee' => 'integer',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Nomor WhatsApp penyelenggara dalam format internasional.
     */
    public function getWhatsappNumberAttribute(): ?string
    {
        $phone = trim((string) ($this->contact_phone ?: $this->venue?->contact_phone ?: $this->creator?->venue?->contact_phone ?: $this->creator?->phone));

        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    public function getVenueAddressAttribute(): ?string
    {
        return $this->venue?->address
            ?: $this->venue?->name
            ?: $this->lokasi;
    }

    public function getMapsUrlAttribute(): ?string
    {
        if ($this->venue?->google_maps_url) {
            return $this->venue->google_maps_url;
        }

        if ($this->venue && is_numeric($this->venue->latitude) && is_numeric($this->venue->longitude)) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .$this->venue->latitude.','.$this->venue->longitude;
        }

        if ($this->lokasi) {
            return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->lokasi);
        }

        return null;
    }

    public function getMapsEmbedUrlAttribute(): ?string
    {
        if ($this->venue && is_numeric($this->venue->latitude) && is_numeric($this->venue->longitude)) {
            return 'https://www.google.com/maps?q='
                .$this->venue->latitude.','.$this->venue->longitude.'&output=embed';
        }

        $address = $this->venue?->address ?: $this->lokasi;

        return $address
            ? 'https://www.google.com/maps?q='.urlencode($address).'&output=embed'
            : null;
    }
}
