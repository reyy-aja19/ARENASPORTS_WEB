<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public const REGISTRATION_INDIVIDU = 'Individu';

    public const REGISTRATION_TIM = 'Tim';

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_PUBLIKASI = 'Publikasi';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $fillable = [
        'title',
        'jenis',
        'registration_type',
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

    public static function registrationTypes(): array
    {
        return [self::REGISTRATION_INDIVIDU, self::REGISTRATION_TIM];
    }

    public function getIsIndividualAttribute(): bool
    {
        return $this->registration_type === self::REGISTRATION_INDIVIDU;
    }

    public function getRegistrationTypeLabelAttribute(): string
    {
        return $this->registration_type ?: self::REGISTRATION_TIM;
    }

    public function getQuotaUnitAttribute(): string
    {
        return $this->is_individual ? 'peserta' : 'tim';
    }

    public function getRegistrationCountAttribute(): int
    {
        if ($this->relationLoaded('registrations')) {
            return $this->registrations->count();
        }

        return $this->registrations()->count();
    }

    public function getRemainingQuotaAttribute(): int
    {
        return max(0, (int) $this->kuota - $this->registration_count);
    }

    public function getIsFullAttribute(): bool
    {
        return (int) $this->kuota > 0 && $this->registration_count >= (int) $this->kuota;
    }

    public function getIsFinishedAttribute(): bool
    {
        return $this->tanggal !== null && $this->tanggal->lt(today());
    }

    public function getIsRegistrationClosedAttribute(): bool
    {
        return $this->is_full
            || $this->is_finished
            || ($this->registration_deadline !== null && $this->registration_deadline->lt(today()));
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === self::STATUS_DIBATALKAN) {
            return 'Dibatalkan';
        }

        if ($this->status === self::STATUS_DRAFT) {
            return 'Segera Dibuka';
        }

        if ($this->is_finished) {
            return 'Selesai';
        }

        if ($this->is_registration_closed) {
            return 'Penuh';
        }

        return 'Dibuka';
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->display_status) {
            'Segera Dibuka' => 'draft',
            'Penuh' => 'full',
            'Selesai' => 'done',
            'Dibatalkan' => 'cancelled',
            default => 'open',
        };
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLIKASI && ! $this->is_registration_closed;
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
