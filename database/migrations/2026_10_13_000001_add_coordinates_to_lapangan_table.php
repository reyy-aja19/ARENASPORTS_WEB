<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lapangan', function (Blueprint $table) {
            if (!Schema::hasColumn('lapangan', 'latitude')) $table->decimal('latitude', 10, 7)->nullable();
            if (!Schema::hasColumn('lapangan', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lapangan', function (Blueprint $table) {
            if (Schema::hasColumn('lapangan', 'longitude')) $table->dropColumn('longitude');
            if (Schema::hasColumn('lapangan', 'latitude')) $table->dropColumn('latitude');
        });
    }
};
