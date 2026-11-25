<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redemptions', function (Blueprint $table) {
            if (!Schema::hasColumn('redemptions', 'owner_admin_id')) {
                $table->foreignId('owner_admin_id')->nullable()->after('reward_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('redemptions', 'venue_id')) {
                $table->foreignId('venue_id')->nullable()->after('owner_admin_id')->constrained('venues')->nullOnDelete();
            }
            if (!Schema::hasColumn('redemptions', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('redemptions', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('redemptions', function (Blueprint $table) {
            foreach (['verified_by', 'venue_id', 'owner_admin_id'] as $col) {
                if (Schema::hasColumn('redemptions', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            if (Schema::hasColumn('redemptions', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
        });
    }
};
