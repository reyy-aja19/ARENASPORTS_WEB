<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChatbotSetting extends Model {
 protected $fillable=['provider','model','system_prompt','is_enabled'];
 protected function casts(): array { return ['is_enabled'=>'boolean']; }
}
