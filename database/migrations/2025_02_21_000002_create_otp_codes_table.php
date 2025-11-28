<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();
        $usePgsqlEnums = $connection === 'pgsql';

        if ($usePgsqlEnums) {
            DB::statement("CREATE TYPE otp_channel_enum AS ENUM ('sms','email');");
            DB::statement("CREATE TYPE otp_purpose_enum AS ENUM ('login','signup','verify','forgot');");
        }

        Schema::create('otp_codes', function (Blueprint $table) use ($usePgsqlEnums) {
            $idDefault = $usePgsqlEnums ? DB::raw('gen_random_uuid()') : null;

            $table->uuid('id')->primary()->default($idDefault);
            $table->uuid('user_id')->nullable();
            $table->string('identifier');
            $table->string('code', 128);
            $table->string('channel');
            $table->string('purpose');
            $table->timestampTz('expires_at');
            $table->integer('attempts')->default(0);
            $table->boolean('used')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('identifier', 'idx_otp_identifier');
            $table->index('user_id', 'idx_otp_user_id');
            $table->index('expires_at', 'idx_otp_expires');
        });

        if ($usePgsqlEnums) {
            DB::statement("ALTER TABLE otp_codes ALTER COLUMN channel TYPE otp_channel_enum USING channel::otp_channel_enum;");
            DB::statement("ALTER TABLE otp_codes ALTER COLUMN purpose TYPE otp_purpose_enum USING purpose::otp_purpose_enum;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');

        $connection = Schema::getConnection()->getDriverName();
        if ($connection === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS otp_channel_enum;');
            DB::statement('DROP TYPE IF EXISTS otp_purpose_enum;');
        }
    }
};
