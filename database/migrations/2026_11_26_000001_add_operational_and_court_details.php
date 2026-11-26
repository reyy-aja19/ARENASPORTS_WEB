<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venues')) {
            $columns = [
                'entity_type' => fn (Blueprint $t) => $t->string('entity_type', 30)->nullable(),
                'operational_status' => fn (Blueprint $t) => $t->string('operational_status', 40)->default('Beroperasi'),
                'opening_time' => fn (Blueprint $t) => $t->time('opening_time')->nullable(),
                'closing_time' => fn (Blueprint $t) => $t->time('closing_time')->nullable(),
                'operating_days' => fn (Blueprint $t) => $t->json('operating_days')->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('venues', $name)) {
                    Schema::table('venues', $definition);
                }
            }

            DB::table('venues')
                ->whereNull('operational_status')
                ->update(['operational_status' => 'Beroperasi']);

            DB::table('venues')
                ->whereNull('entity_type')
                ->update([
                    'entity_type' => DB::raw("CASE WHEN company_name IS NULL OR company_name = '' THEN 'Perorangan' ELSE 'Badan Usaha' END"),
                ]);
        }

        if (Schema::hasTable('venue_applications')) {
            $columns = [
                'entity_type' => fn (Blueprint $t) => $t->string('entity_type', 30)->nullable(),
                'opening_time' => fn (Blueprint $t) => $t->time('opening_time')->nullable(),
                'closing_time' => fn (Blueprint $t) => $t->time('closing_time')->nullable(),
                'operating_days' => fn (Blueprint $t) => $t->json('operating_days')->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('venue_applications', $name)) {
                    Schema::table('venue_applications', $definition);
                }
            }

            DB::table('venue_applications')
                ->whereNull('entity_type')
                ->update([
                    'entity_type' => DB::raw("CASE WHEN company_name IS NULL OR company_name = '' THEN 'Perorangan' ELSE 'Badan Usaha' END"),
                ]);
        }

        if (Schema::hasTable('lapangan')) {
            $columns = [
                'operational_status' => fn (Blueprint $t) => $t->string('operational_status', 40)->default('Beroperasi'),
                'use_venue_location' => fn (Blueprint $t) => $t->boolean('use_venue_location')->default(true),
                'area_type' => fn (Blueprint $t) => $t->string('area_type', 30)->nullable(),
                'floor_type' => fn (Blueprint $t) => $t->string('floor_type', 80)->nullable(),
                'court_facilities' => fn (Blueprint $t) => $t->json('court_facilities')->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('lapangan', $name)) {
                    Schema::table('lapangan', $definition);
                }
            }

            DB::table('lapangan')
                ->whereNull('operational_status')
                ->update([
                    'operational_status' => DB::raw("CASE status
                        WHEN 'Perawatan' THEN 'Perawatan'
                        WHEN 'Perbaikan' THEN 'Perbaikan'
                        WHEN 'Penuh' THEN 'Beroperasi'
                        WHEN 'Tidak Aktif' THEN 'Tidak Aktif'
                        ELSE 'Beroperasi'
                    END"),
                ]);

            DB::table('lapangan')
                ->whereNotNull('venue_id')
                ->update(['use_venue_location' => true]);
        }
    }

    public function down(): void
    {
        // Data operasional dan detail lapangan dipertahankan saat rollback.
    }
};
