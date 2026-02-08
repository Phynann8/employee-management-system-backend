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
        Schema::create('attendance_locks', function (Blueprint $table) {
            $table->id();
            $table->string('month')->unique(); // YYYY-MM
            $table->foreignId('locked_by')->constrained('users');
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_locks');
    }
};
