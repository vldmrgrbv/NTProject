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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nt_user_id')->constrained('nt_users')->cascadeOnDelete();
            $table->integer('status')->default(-100);
            $table->string('external_id')->nullable()->index();
            $table->string('fn')->nullable();
            $table->string('fd')->nullable();
            $table->string('fp')->nullable();
            $table->string('dt')->nullable();
            $table->string('summ')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->integer('scores')->default(0);
            $table->text('reason_failed')->nullable();
            $table->string('number')->nullable();
            $table->text('skus')->nullable();
            $table->string('source')->nullable();
            $table->json('raw_response_sender')->nullable();
            $table->json('raw_response_monitor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
