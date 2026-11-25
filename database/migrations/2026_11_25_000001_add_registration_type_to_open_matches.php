<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('open_matches')) {
            return;
        }

        Schema::table('open_matches', function (Blueprint $table) {
            if (! Schema::hasColumn('open_matches', 'registration_type')) {
                $table->string('registration_type', 20)->default('Individu')->after('jenis');
            }
        });

        if (Schema::hasTable('open_match_participants')) {
            Schema::table('open_match_participants', function (Blueprint $table) {
                if (! Schema::hasColumn('open_match_participants', 'role')) {
                    $table->string('role', 20)->default('Lawan')->after('user_id');
                }

                if (! Schema::hasColumn('open_match_participants', 'team_name')) {
                    $table->string('team_name', 150)->nullable()->after('role');
                }

                if (! Schema::hasColumn('open_match_participants', 'member_count')) {
                    $table->unsignedInteger('member_count')->default(1)->after('team_name');
                }
            });

            // Tandai pembuat match (host booking) sebagai Host untuk data lama.
            if (Schema::hasTable('bookings')) {
                DB::table('open_match_participants')
                    ->join('open_matches', 'open_matches.id', '=', 'open_match_participants.open_match_id')
                    ->join('bookings', 'bookings.id', '=', 'open_matches.booking_id')
                    ->whereColumn('open_match_participants.user_id', 'bookings.user_id')
                    ->update(['open_match_participants.role' => 'Host']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('open_match_participants')) {
            Schema::table('open_match_participants', function (Blueprint $table) {
                foreach (['role', 'team_name', 'member_count'] as $column) {
                    if (Schema::hasColumn('open_match_participants', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('open_matches') && Schema::hasColumn('open_matches', 'registration_type')) {
            Schema::table('open_matches', function (Blueprint $table) {
                $table->dropColumn('registration_type');
            });
        }
    }
};
