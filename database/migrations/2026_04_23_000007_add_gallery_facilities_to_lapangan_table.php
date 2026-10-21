<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lapangan', function (Blueprint $table) {
            if (! Schema::hasColumn('lapangan', 'foto_gallery')) {
                $table->text('foto_gallery')->nullable()->after('foto');
            }
            if (! Schema::hasColumn('lapangan', 'fasilitas')) {
                $table->text('fasilitas')->nullable()->after('foto_gallery');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lapangan', function (Blueprint $table) {
            if (Schema::hasColumn('lapangan', 'fasilitas')) {
                $table->dropColumn('fasilitas');
            }
            if (Schema::hasColumn('lapangan', 'foto_gallery')) {
                $table->dropColumn('foto_gallery');
            }
        });
    }
};
