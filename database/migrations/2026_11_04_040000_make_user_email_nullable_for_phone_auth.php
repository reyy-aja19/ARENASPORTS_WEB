<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        // Beri alamat placeholder pada akun yang mendaftar hanya dengan WhatsApp
        // agar rollback tidak menghapus akun atau gagal karena kolom email wajib.
        foreach (DB::table('users')->whereNull('email')->orderBy('id')->get(['id']) as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'email' => 'phone-'.$user->id.'@noemail.arenasports.invalid',
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
