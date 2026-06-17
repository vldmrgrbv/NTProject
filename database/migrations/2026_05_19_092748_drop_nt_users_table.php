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
        Schema::dropIfExists('nt_users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('nt_users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->string('external_id')->nullable();
            $table->string('auth_code')->nullable();
            $table->boolean('is_authorized')->default(false);
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_whitelisted')->default(false);
            $table->timestamps();
        });
    }
};
