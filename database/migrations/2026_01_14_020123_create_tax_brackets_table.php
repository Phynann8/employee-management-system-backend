<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2);
            $table->decimal('rate', 5, 2); // Percentage
            $table->decimal('deduction', 15, 2); // Quick deduction amount
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_brackets');
    }
};
