<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('chatbot_settings',function(Blueprint $table){$table->id();$table->string('provider')->default('openai');$table->string('model')->default('gpt-4o-mini');$table->text('system_prompt')->nullable();$table->boolean('is_enabled')->default(false);$table->timestamps();});} public function down(): void {Schema::dropIfExists('chatbot_settings');} };
