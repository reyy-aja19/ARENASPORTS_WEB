<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('venue_applications') || !Schema::hasColumn('venue_applications', 'status')) {
            return;
        }
        DB::table('venue_applications')->whereIn('status', ['Menunggu', 'menunggu'])->update(['status' => 'pending']);
        DB::table('venue_applications')->whereIn('status', ['Disetujui', 'disetujui'])->update(['status' => 'approved']);
        DB::table('venue_applications')->whereIn('status', ['Ditolak', 'ditolak'])->update(['status' => 'rejected']);
        DB::table('venue_applications')->whereIn('status', ['Perlu Revisi', 'Perlu revisi'])->update(['status' => 'revision']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('venue_applications') || !Schema::hasColumn('venue_applications', 'status')) {
            return;
        }
        DB::table('venue_applications')->where('status', 'pending')->update(['status' => 'Menunggu']);
        DB::table('venue_applications')->where('status', 'approved')->update(['status' => 'Disetujui']);
        DB::table('venue_applications')->where('status', 'rejected')->update(['status' => 'Ditolak']);
        DB::table('venue_applications')->where('status', 'revision')->update(['status' => 'Perlu Revisi']);
    }
};
