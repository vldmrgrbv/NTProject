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
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('external_id')->nullable()->after('phone');
            $table->string('auth_code')->nullable()->after('external_id');
            $table->boolean('is_authorized')->default(false)->after('auth_code');
            $table->boolean('is_whitelisted')->default(false)->after('is_authorized');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->dropColumn(['phone', 'external_id', 'auth_code', 'is_authorized', 'is_whitelisted']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
