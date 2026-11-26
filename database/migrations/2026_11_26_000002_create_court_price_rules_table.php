<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('court_price_rules')) {
            Schema::create('court_price_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lapangan_id')->constrained('lapangan')->cascadeOnDelete();
                $table->string('label', 80)->nullable();
                $table->string('day_type', 20)->default('weekday');
                $table->json('days')->nullable();
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedInteger('price');
                $table->unsignedInteger('priority')->default(0);
                $table->timestamps();

                $table->index(['lapangan_id', 'day_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('court_price_rules');
    }
};
