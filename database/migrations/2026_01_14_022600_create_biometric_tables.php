<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Biometric Raw Logs (Staging Table)
        Schema::create('biometric_raw_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50)->nullable();
            $table->string('enroll_number', 50); // Maps to Employee Code
            $table->integer('verify_mode')->default(0);
            $table->integer('in_out_mode')->default(0);
            $table->dateTime('log_time');
            $table->boolean('processed')->default(false); // Flag for processing job
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        // 2. Biometric Devices Registry
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name', 100);
            $table->string('ip_address', 50);
            $table->integer('port')->default(4370);
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_raw_logs');
        Schema::dropIfExists('biometric_devices');
    }
};
