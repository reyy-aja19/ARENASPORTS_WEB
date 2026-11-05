<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venue_applications')) {
            $columns = [
                'applicant_phone' => fn (Blueprint $table) => $table->string('applicant_phone', 30)->nullable(),
                'ownership_type' => fn (Blueprint $table) => $table->string('ownership_type', 30)->nullable(),
                'google_maps_url' => fn (Blueprint $table) => $table->string('google_maps_url', 500)->nullable(),
                'operating_hours' => fn (Blueprint $table) => $table->string('operating_hours', 150)->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (!Schema::hasColumn('venue_applications', $column)) {
                    Schema::table('venue_applications', $definition);
                }
            }
        }

        if (Schema::hasTable('venues')) {
            if (!Schema::hasColumn('venues', 'google_maps_url')) {
                Schema::table('venues', function (Blueprint $table) {
                    $table->string('google_maps_url', 500)->nullable();
                });
            }

            if (!Schema::hasColumn('venues', 'operating_hours')) {
                Schema::table('venues', function (Blueprint $table) {
                    $table->string('operating_hours', 150)->nullable();
                });
            }
        }

        if (!Schema::hasTable('venue_application_documents')) {
            Schema::create('venue_application_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_application_id')
                    ->constrained('venue_applications')
                    ->cascadeOnDelete();
                $table->string('document_type', 50);
                $table->string('file_path', 500);
                $table->string('original_name', 255)->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->timestamps();

                $table->index(['venue_application_id', 'document_type']);
            });
        }
    }

    public function down(): void
    {
        // Data pengajuan dan dokumen dipertahankan untuk mencegah kehilangan berkas.
    }
};
