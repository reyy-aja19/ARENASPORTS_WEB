<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Event extends Model {
 protected $fillable=['title','jenis','tanggal','start_time','end_time','lokasi','kuota','deskripsi','status','created_by'];
 protected function casts(): array { return ['tanggal'=>'date']; }
 public function creator(){ return $this->belongsTo(User::class,'created_by'); }
}
