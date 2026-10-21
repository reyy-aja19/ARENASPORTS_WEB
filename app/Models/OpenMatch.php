<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
