<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (! Schema::hasColumn('events', 'registration_type')) {
                    $table->string('registration_type', 20)->default('Tim')->after('jenis');
                }
            });
        }

        if (Schema::hasTable('event_registrations')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                if (! Schema::hasColumn('event_registrations', 'registration_type')) {
                    $table->string('registration_type', 20)->default('Tim')->after('user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('event_registrations') && Schema::hasColumn('event_registrations', 'registration_type')) {
            Schema::table('event_registrations', function (Blueprint $table) {
                $table->dropColumn('registration_type');
            });
        }

        if (Schema::hasTable('events') && Schema::hasColumn('events', 'registration_type')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('registration_type');
            });
        }
    }
};
