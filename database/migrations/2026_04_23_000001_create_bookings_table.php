<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lapangan_id')->constrained('lapangan')->cascadeOnDelete();
            $table->string('payment_method')->default('Transfer');
            $table->date('booking_date');
            $table->string('start_time');
            $table->string('end_time');
            $table->unsignedTinyInteger('hours')->default(1);
            $table->unsignedInteger('total_price');
            $table->string('status')->default('Menunggu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
