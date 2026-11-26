<?php

namespace App\Services;

use App\Models\CourtPriceRule;
use App\Models\Lapangan;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CourtPricingService
{
    /**
     * Hitung rincian harga per jam berdasarkan aturan harga lapangan.
     *
     * @return array{items: array<int, array{start: string, end: string, price: int, label: ?string}>, total: int, hours: int}
     */
    public function quote(Lapangan $lapangan, string $date, string $startTime, int $hours): array
    {
        $hours = max(1, (int) $hours);
        $date = Carbon::parse($date)->startOfDay();
        $startMinutes = $this->toMinutes($startTime);
        $rules = $this->rulesFor($lapangan);

        $items = [];
        $total = 0;

        for ($i = 0; $i < $hours; $i++) {
            $slotStart = $startMinutes + ($i * 60);
            $slotEnd = $slotStart + 60;

            $rule = $this->matchingRule($rules, $date, $slotStart);
            $price = $rule?->price ?? (int) $lapangan->harga;

            $items[] = [
                'start' => $this->fromMinutes($slotStart),
                'end' => $this->fromMinutes($slotEnd),
                'price' => (int) $price,
                'label' => $rule?->label,
            ];

            $total += (int) $price;
        }

        return [
            'items' => $items,
            'total' => $total,
            'hours' => $hours,
        ];
    }

    public function minimumPrice(Lapangan $lapangan): int
    {
        $prices = $this->rulesFor($lapangan)->pluck('price')->filter();

        if ($prices->isEmpty()) {
            return (int) $lapangan->harga;
        }

        return (int) min($prices->min(), (int) $lapangan->harga);
    }

    /**
     * @return Collection<int, CourtPriceRule>
     */
    private function rulesFor(Lapangan $lapangan): Collection
    {
        if ($lapangan->relationLoaded('priceRules')) {
            return $lapangan->priceRules->sortByDesc('priority')->values();
        }

        return $lapangan->priceRules()
            ->orderByDesc('priority')
            ->orderBy('start_time')
            ->get();
    }

    private function matchingRule(Collection $rules, Carbon $date, int $slotStart): ?CourtPriceRule
    {
        foreach ($rules as $rule) {
            if (!$rule->appliesOn($date)) {
                continue;
            }

            $start = $this->toMinutes($rule->start_time);
            $end = $this->toMinutes($rule->end_time);

            if ($end <= $start) {
                $end += 24 * 60;
            }

            if ($slotStart >= $start && $slotStart < $end) {
                return $rule;
            }
        }

        return null;
    }

    public function withinOperatingHours(?Venue $venue, string $startTime, int $hours): bool
    {
        if (!$venue) {
            return true;
        }

        if (!$venue->opening_time || !$venue->closing_time) {
            return true;
        }

        $start = $this->toMinutes($startTime);
        $end = $start + (max(1, $hours) * 60);
        $open = $this->toMinutes($venue->opening_time);
        $close = $this->toMinutes($venue->closing_time);

        if ($close <= $open) {
            $close += 24 * 60;
        }

        return $start >= $open && $end <= $close;
    }

    public function isOperatingDay(?Venue $venue, string $date): bool
    {
        if (!$venue) {
            return true;
        }

        $days = $venue->operating_days_list;
        $dayName = $this->dayName(Carbon::parse($date));

        return in_array($dayName, $days, true);
    }

    private function dayName(Carbon $date): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$date->dayOfWeekIso] ?? 'Senin';
    }

    private function toMinutes($value): int
    {
        if ($value instanceof \DateTimeInterface) {
            return ((int) $value->format('H')) * 60 + (int) $value->format('i');
        }

        $parts = explode(':', (string) $value);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }

    private function fromMinutes(int $minutes): string
    {
        $minutes = $minutes % (24 * 60);

        return str_pad((string) intdiv($minutes, 60), 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }
}
