<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->json('responses')->nullable()->after('is_network');
            $table->dropColumn('kpp');
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
            $table->renameColumn('number', 'nt_number');
            $table->dropColumn(['raw_response_sender', 'raw_response_monitor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('responses');
            $table->string('kpp')->nullable()->after('inn');
            $table->string('external_id')->nullable()->index()->after('user_id');
            $table->renameColumn('nt_number', 'number');
            $table->json('raw_response_sender')->nullable()->after('is_network');
            $table->json('raw_response_monitor')->nullable()->after('raw_response_sender');
        });
    }
};
