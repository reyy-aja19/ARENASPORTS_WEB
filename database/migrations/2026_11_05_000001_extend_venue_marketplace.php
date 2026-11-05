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
                'owner_address' => fn (Blueprint $t) => $t->text('owner_address')->nullable(),
                'identity_number' => fn (Blueprint $t) => $t->string('identity_number', 80)->nullable(),
                'company_name' => fn (Blueprint $t) => $t->string('company_name', 180)->nullable(),
                'business_type' => fn (Blueprint $t) => $t->string('business_type', 80)->nullable(),
                'nib' => fn (Blueprint $t) => $t->string('nib', 100)->nullable(),
                'company_address' => fn (Blueprint $t) => $t->text('company_address')->nullable(),
                'company_phone' => fn (Blueprint $t) => $t->string('company_phone', 30)->nullable(),
                'company_description' => fn (Blueprint $t) => $t->text('company_description')->nullable(),
                'business_type' => fn (Blueprint $t) => $t->string('business_type', 80)->nullable(),
                'nib' => fn (Blueprint $t) => $t->string('nib', 100)->nullable(),
                'company_address' => fn (Blueprint $t) => $t->text('company_address')->nullable(),
                'city' => fn (Blueprint $t) => $t->string('city', 100)->nullable(),
                'province' => fn (Blueprint $t) => $t->string('province', 100)->nullable(),
                'company_phone' => fn (Blueprint $t) => $t->string('company_phone', 30)->nullable(),
                'company_description' => fn (Blueprint $t) => $t->text('company_description')->nullable(),
                'latitude' => fn (Blueprint $t) => $t->decimal('latitude', 10, 7)->nullable(),
                'longitude' => fn (Blueprint $t) => $t->decimal('longitude', 10, 7)->nullable(),
                'facilities' => fn (Blueprint $t) => $t->json('facilities')->nullable(),
            ];
            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('venue_applications', $name)) {
                    Schema::table('venue_applications', $definition);
                }
            }
        }

        if (Schema::hasTable('venues')) {
            $columns = [
                'city' => fn (Blueprint $t) => $t->string('city', 100)->nullable(),
                'province' => fn (Blueprint $t) => $t->string('province', 100)->nullable(),
                'latitude' => fn (Blueprint $t) => $t->decimal('latitude', 10, 7)->nullable(),
                'longitude' => fn (Blueprint $t) => $t->decimal('longitude', 10, 7)->nullable(),
                'facilities' => fn (Blueprint $t) => $t->json('facilities')->nullable(),
                'gallery' => fn (Blueprint $t) => $t->json('gallery')->nullable(),
                'company_name' => fn (Blueprint $t) => $t->string('company_name', 180)->nullable(),
                'business_type' => fn (Blueprint $t) => $t->string('business_type', 80)->nullable(),
                'nib' => fn (Blueprint $t) => $t->string('nib', 100)->nullable(),
                'company_address' => fn (Blueprint $t) => $t->text('company_address')->nullable(),
                'company_phone' => fn (Blueprint $t) => $t->string('company_phone', 30)->nullable(),
                'company_description' => fn (Blueprint $t) => $t->text('company_description')->nullable(),
            ];
            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('venues', $name)) {
                    Schema::table('venues', $definition);
                }
            }
        }

        if (!Schema::hasTable('venue_application_histories')) {
            Schema::create('venue_application_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_application_id')->constrained('venue_applications')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->text('note')->nullable();
                $table->timestamps();
                $table->index(['venue_application_id', 'created_at'], 'vah_app_created_idx');
            });
        }
    }

    public function down(): void
    {
        // Data pengajuan dan histori dipertahankan; rollback tidak menghapus data pengguna.
    }
};
