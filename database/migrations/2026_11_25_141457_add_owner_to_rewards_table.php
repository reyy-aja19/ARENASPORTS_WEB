<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('rewards', 'owner_admin_id')) {
                $table->foreignId('owner_admin_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('rewards', 'venue_id')) {
                $table->foreignId('venue_id')->nullable()->after('owner_admin_id')->constrained('venues')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            if (Schema::hasColumn('rewards', 'venue_id')) {
                $table->dropConstrainedForeignId('venue_id');
            }
            if (Schema::hasColumn('rewards', 'owner_admin_id')) {
                $table->dropConstrainedForeignId('owner_admin_id');
            }
        });
    }
};
