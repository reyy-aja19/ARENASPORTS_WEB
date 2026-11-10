<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenMatchMessage extends Model
{
    protected $fillable = ['open_match_id', 'user_id', 'message', 'attachment_path', 'attachment_name'];

    public function openMatch() { return $this->belongsTo(OpenMatch::class); }
    public function user() { return $this->belongsTo(User::class); }
}
