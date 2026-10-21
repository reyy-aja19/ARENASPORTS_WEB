<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('payments',function(Blueprint $table){$table->string('transaction_id')->nullable()->unique();$table->string('snap_token')->nullable();}); } public function down(): void { Schema::table('payments',function(Blueprint $table){$table->dropUnique(['transaction_id']);$table->dropColumn(['transaction_id','snap_token']);}); } };
