<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OpenMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'title',
        'jenis',
        'tanggal',
        'start_time',
        'end_time',
        'jumlah_pemain',
        'jumlah_bergabung',
        'deskripsi',
        'biaya_per_pemain',
        'level',
        'contact_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'biaya_per_pemain' => 'integer',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages()
    {
        return $this->hasMany(OpenMatchMessage::class);
    }

    public function getEndsAtAttribute(): ?Carbon
    {
        if (! $this->tanggal) {
            return null;
        }

        $endTime = substr((string) ($this->end_time ?: $this->start_time), 0, 5);

        if ($endTime === '') {
            return $this->tanggal->copy()->endOfDay();
        }

        return Carbon::parse($this->tanggal->toDateString().' '.$endTime);
    }

    public function getHasEndedAttribute(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function getIsFreeAttribute(): bool
    {
        return ! $this->biaya_per_pemain || (int) $this->biaya_per_pemain <= 0;
    }

    public function getSuggestedPricePerPlayerAttribute(): int
    {
        $total = (int) ($this->booking?->total_price ?? 0);
        $players = max(1, (int) ($this->jumlah_pemain ?? 1));

        return $total > 0 ? (int) ceil($total / $players) : 0;
    }

    public function getVenueAttribute()
    {
        return $this->booking?->lapangan?->venue;
    }

    public function getMapsUrlAttribute(): ?string
    {
        $venue = $this->venue;
        $address = $venue?->address ?: $this->booking?->lapangan?->lokasi;

        if ($venue?->google_maps_url) {
            return $venue->google_maps_url;
        }

        if ($venue && is_numeric($venue->latitude) && is_numeric($venue->longitude)) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .$venue->latitude.','.$venue->longitude;
        }

        return $address
            ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($address)
            : null;
    }

    public function getMapsEmbedUrlAttribute(): ?string
    {
        $venue = $this->venue;
        $address = $venue?->address ?: $this->booking?->lapangan?->lokasi;

        if ($venue && is_numeric($venue->latitude) && is_numeric($venue->longitude)) {
            return 'https://www.google.com/maps?q='
                .$venue->latitude.','.$venue->longitude.'&output=embed';
        }

        return $address
            ? 'https://www.google.com/maps?q='.urlencode($address).'&output=embed'
            : null;
    }

    public function getVenueAddressAttribute(): ?string
    {
        return $this->booking?->lapangan?->venue?->address
            ?: $this->booking?->lapangan?->lokasi;
    }

    public function getWhatsappNumberAttribute(): ?string
    {
        $phone = trim((string) $this->contact_phone);

        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
