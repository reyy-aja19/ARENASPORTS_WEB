<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'registration_fee')) {
                $table->unsignedInteger('registration_fee')->default(0)->after('kuota');
            }

            if (! Schema::hasColumn('events', 'prize')) {
                $table->string('prize', 255)->nullable()->after('registration_fee');
            }

            if (! Schema::hasColumn('events', 'contact_phone')) {
                $table->string('contact_phone', 30)->nullable()->after('prize');
            }

            if (! Schema::hasColumn('events', 'registration_deadline')) {
                $table->date('registration_deadline')->nullable()->after('contact_phone');
            }

            if (! Schema::hasColumn('events', 'venue_id')) {
                $table->foreignId('venue_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('venues')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'venue_id')) {
                $table->dropConstrainedForeignId('venue_id');
            }

            foreach (['registration_fee', 'prize', 'contact_phone', 'registration_deadline'] as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
