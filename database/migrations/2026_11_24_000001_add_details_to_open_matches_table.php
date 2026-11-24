<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('open_matches')) {
            return;
        }

        Schema::table('open_matches', function (Blueprint $table) {
            if (! Schema::hasColumn('open_matches', 'biaya_per_pemain')) {
                $table->unsignedInteger('biaya_per_pemain')->nullable()->after('deskripsi');
            }

            if (! Schema::hasColumn('open_matches', 'level')) {
                $table->string('level', 50)->nullable()->after('biaya_per_pemain');
            }

            if (! Schema::hasColumn('open_matches', 'contact_phone')) {
                $table->string('contact_phone', 30)->nullable()->after('level');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('open_matches')) {
            return;
        }

        Schema::table('open_matches', function (Blueprint $table) {
            foreach (['biaya_per_pemain', 'level', 'contact_phone'] as $column) {
                if (Schema::hasColumn('open_matches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
