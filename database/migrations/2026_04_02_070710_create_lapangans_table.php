<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lapangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jenis');
            $table->string('lokasi');
            $table->unsignedInteger('harga');
            $table->decimal('rating', 2, 1)->default(4.5);
            $table->string('status')->default('Tersedia');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->text('foto_gallery')->nullable();
            $table->text('fasilitas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lapangan');
    }
};
