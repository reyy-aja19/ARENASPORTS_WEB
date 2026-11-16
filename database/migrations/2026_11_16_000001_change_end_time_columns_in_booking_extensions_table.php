<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            $table->string('previous_end_time', 19)->change();
            $table->string('new_end_time', 19)->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_extensions', function (Blueprint $table) {
            $table->dateTime('previous_end_time')->change();
            $table->dateTime('new_end_time')->change();
        });
    }
};
