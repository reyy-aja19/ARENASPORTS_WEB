<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lapangan_id',
        'payment_method',
        'booking_date',
        'start_time',
        'end_time',
        'hours',
        'total_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lapangan()
    {
        return $this->belongsTo(Lapangan::class, 'lapangan_id');
    }

    public function openMatch()
    {
        return $this->hasOne(OpenMatch::class);
    }

    // Pembayaran booking awal saja
    public function payment()
    {
        return $this->hasOne(Payment::class)
            ->whereNull('booking_extension_id');
    }

    // Semua pembayaran, termasuk perpanjangan
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Riwayat perpanjangan booking
    public function extensions()
    {
        return $this->hasMany(BookingExtension::class);
    }
}