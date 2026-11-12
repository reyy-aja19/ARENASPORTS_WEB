
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_applications', function (Blueprint $table) {
            $table->string('applicant_relationship', 30)
                ->nullable()
                ->after('applicant_phone');

            $table->timestamp('responsibility_agreed_at')
                ->nullable()
                ->after('applicant_relationship');
        });
    }

    public function down(): void
    {
        Schema::table('venue_applications', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_relationship',
                'responsibility_agreed_at',
            ]);
        });
    }
};
