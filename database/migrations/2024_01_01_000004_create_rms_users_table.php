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
        Schema::create('rms_users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('user_name', 50)->unique(); // Likely the login username
            $table->string('email', 100)->nullable(); // Might not exist in legacy, but good for Laravel
            $table->string('password'); // Legacy password (likely md5), needs re-hashing strategy or custom provider
            $table->string('user_type', 50)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->integer('branch_id')->nullable();
            $table->string('branch_list')->nullable();
            $table->tinyInteger('is_system')->default(0);
            $table->string('schoolOption')->nullable();
            $table->string('degreeList')->nullable();
            $table->text('photo')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rms_users');
    }
};
