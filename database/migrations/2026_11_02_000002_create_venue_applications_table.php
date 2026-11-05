<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('venue_applications')) {
            Schema::create('venue_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('venue_name');
                $table->text('address');
                $table->string('contact_phone', 30);
                $table->text('description')->nullable();
                $table->json('sports')->nullable();
                $table->string('status', 20)->default('Menunggu');
                $table->string('supporting_photo')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('admin_note')->nullable();

                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        } else {
            // Database lama sudah memiliki tabel ini.
            // Tambahkan kolom yang diperlukan kode marketplace.
            if (!Schema::hasColumn('venue_applications', 'admin_note')) {
                Schema::table('venue_applications', function (Blueprint $table) {
                    $table->text('admin_note')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Pertahankan tabel pengajuan yang sudah ada.
    }
};