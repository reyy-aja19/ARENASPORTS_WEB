<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VenueWithdrawal extends Model {protected $fillable=['venue_id','user_id','amount','bank_name','account_number','account_holder','status','admin_note','processed_by','processed_at'];protected function casts():array{return ['processed_at'=>'datetime'];}public function venue(){return $this->belongsTo(Venue::class);}public function user(){return $this->belongsTo(User::class);} }
