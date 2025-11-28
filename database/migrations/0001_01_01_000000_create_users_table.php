<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');
        }

        Schema::create('users', function (Blueprint $table) use ($connection) {
            $idDefault = $connection === 'pgsql' ? DB::raw('gen_random_uuid()') : null;

            $table->uuid('id')->primary()->default($idDefault);
            $table->string('username', 64)->unique()->nullable();
            $table->string('email')->unique()->nullable();
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
            $table->foreign('introduced_by')->references('id')->on('users')->onDelete('set null');
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
