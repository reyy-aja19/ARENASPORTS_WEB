<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'registration_type',
        'team_name',
        'captain_name',
        'captain_phone',
        'member_count',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'member_count' => 'integer',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsIndividualAttribute(): bool
    {
        return $this->registration_type === Event::REGISTRATION_INDIVIDU;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->is_individual
            ? (string) $this->captain_name
            : (string) $this->team_name;
    }

    public function getWhatsappNumberAttribute(): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->captain_phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
