<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OpenMatch extends Model
{
    use HasFactory;

    public const REGISTRATION_INDIVIDU = 'Individu';

    public const REGISTRATION_KELOMPOK = 'Kelompok';

    public const STATUS_OPEN = 'Open';

    public const STATUS_PENUH = 'Penuh';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $fillable = [
        'booking_id',
        'title',
        'jenis',
        'registration_type',
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

    public function participantRecords()
    {
        return $this->hasMany(OpenMatchParticipant::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'open_match_participants')
            ->withPivot(['role', 'team_name', 'member_count'])
            ->withTimestamps();
    }

    public static function registrationTypes(): array
    {
        return [self::REGISTRATION_INDIVIDU, self::REGISTRATION_KELOMPOK];
    }

    public static function statuses(): array
    {
        return [self::STATUS_OPEN, self::STATUS_PENUH, self::STATUS_SELESAI, self::STATUS_DIBATALKAN];
    }

    public function getIsGroupAttribute(): bool
    {
        return $this->registration_type === self::REGISTRATION_KELOMPOK;
    }

    public function getRegistrationTypeLabelAttribute(): string
    {
        return $this->registration_type ?: self::REGISTRATION_INDIVIDU;
    }

    public function getCapacityLabelAttribute(): string
    {
        return $this->is_group ? 'kelompok' : 'pemain';
    }

    public function getRemainingSlotsAttribute(): int
    {
        return max(0, (int) $this->jumlah_pemain - (int) $this->jumlah_bergabung);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->jumlah_pemain > 0 && $this->jumlah_bergabung >= $this->jumlah_pemain;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === self::STATUS_DIBATALKAN) {
            return 'Dibatalkan';
        }

        if ($this->status === self::STATUS_SELESAI || $this->has_ended) {
            return 'Selesai';
        }

        if ($this->status === self::STATUS_PENUH || $this->is_full) {
            return 'Penuh';
        }

        return 'Mencari Pemain';
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->display_status) {
            'Penuh' => 'full',
            'Selesai' => 'done',
            'Dibatalkan' => 'cancelled',
            default => 'open',
        };
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
