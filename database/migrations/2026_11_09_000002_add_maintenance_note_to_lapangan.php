<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lapangan') && !Schema::hasColumn('lapangan', 'maintenance_note')) {
            Schema::table('lapangan', function (Blueprint $table) {
                $table->text('maintenance_note')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        // Data catatan perbaikan dipertahankan saat rollback.
    }
};
