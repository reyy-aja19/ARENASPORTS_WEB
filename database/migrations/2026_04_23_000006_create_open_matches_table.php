<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('jenis')->default('Futsal');
            $table->date('tanggal');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->unsignedTinyInteger('jumlah_pemain')->default(10);
            $table->unsignedTinyInteger('jumlah_bergabung')->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_matches');
    }
};
