<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Buat tabel hanya jika belum tersedia.
        if (!Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_admin_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->unsignedBigInteger('venue_application_id')
                    ->nullable()
                    ->unique();

                $table->string('name', 150);
                $table->string('slug')->unique();
                $table->text('address');
                $table->string('contact_phone', 30)->nullable();
                $table->text('description')->nullable();
                $table->string('photo')->nullable();
                $table->json('sports')->nullable();
                $table->string('status', 30)->default('Aktif');
                $table->timestamps();
            });
        } else {
            // Tambahkan slug tanpa menghapus data venue lama.
            if (!Schema::hasColumn('venues', 'slug')) {
                Schema::table('venues', function (Blueprint $table) {
                    $table->string('slug')->nullable()->unique();
                });
            }

            // Isi slug untuk dua venue lama.
            $venues = DB::table('venues')
                ->whereNull('slug')
                ->get(['id', 'name']);

            foreach ($venues as $venue) {
                $slug = Str::slug($venue->name) ?: 'venue';
                $slug .= '-' . $venue->id;

                DB::table('venues')
                    ->where('id', $venue->id)
                    ->update(['slug' => $slug]);
            }

            // Samakan status dengan sistem marketplace baru.
            DB::table('venues')
                ->where('status', 'active')
                ->update(['status' => 'Aktif']);
        }

        // Tambahkan relasi hanya jika belum tersedia.
        if (
            Schema::hasTable('lapangan') &&
            !Schema::hasColumn('lapangan', 'venue_id')
        ) {
            Schema::table('lapangan', function (Blueprint $table) {
                $table->foreignId('venue_id')
                    ->nullable()
                    ->after('owner_admin_id')
                    ->constrained('venues')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus tabel lama atau data venue.
    }
};