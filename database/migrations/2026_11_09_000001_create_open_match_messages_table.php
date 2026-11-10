<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('open_match_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_match_id')->constrained('open_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
            $table->index(['open_match_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('open_match_messages'); }
};
