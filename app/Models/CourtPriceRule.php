<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourtPriceRule extends Model
{
    protected $fillable = [
        'lapangan_id',
        'label',
        'day_type',
        'days',
        'start_time',
        'end_time',
        'price',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'price' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function lapangan()
    {
        return $this->belongsTo(Lapangan::class, 'lapangan_id');
    }

    public function appliesOn(\Illuminate\Support\Carbon $date): bool
    {
        $dayName = $date->locale('id')->dayName;
        $isWeekend = $date->isWeekend();

        return match ($this->day_type) {
            'weekend' => $isWeekend,
            'custom' => in_array($dayName, $this->days ?? [], true),
            default => !$isWeekend,
        };
    }
}
