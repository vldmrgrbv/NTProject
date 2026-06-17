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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('marketing_agree')->default(false)->after('remember_token');
            $table->boolean('privacy_agree')->default(false)->after('marketing_agree');
            $table->date('birthday')->nullable()->after('phone');
            $table->string('gender')->nullable()->after('birthday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['marketing_agree', 'privacy_agree', 'birthday', 'gender']);
        });
    }
};
