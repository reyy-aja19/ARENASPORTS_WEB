<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lapangan extends Model
{
    use HasFactory;

    protected $table = 'lapangan';

    protected $fillable = [
        'nama',
        'owner_admin_id',
        'venue_id',
        'jenis',
        'lokasi',
        'latitude',
        'longitude',
        'harga',
        'rating',
        'status',
        'deskripsi',
        'foto',
        'foto_gallery',
        'fasilitas',
    ];

    protected function casts(): array
    {
        return [
            'foto_gallery' => 'array',
            'fasilitas' => 'array',
        ];
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'lapangan_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'lapangan_id');
    }
}
