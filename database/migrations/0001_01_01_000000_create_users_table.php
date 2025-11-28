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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 64)->unique()->nullable();
            $table->string('email', 255)->unique()->nullable();
            $table->string('phone', 32)->unique()->nullable();
            $table->boolean('is_phone_verified')->default(false);
            $table->boolean('is_email_verified')->default(false);
            $table->string('role')->default('visitor');
            $table->string('status')->default('visitor');
            $table->uuid('default_circle_id')->nullable();
            $table->uuid('introduced_by')->nullable();
            $table->bigInteger('coins_balance')->default(0);
            $table->integer('influencer_stars')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
