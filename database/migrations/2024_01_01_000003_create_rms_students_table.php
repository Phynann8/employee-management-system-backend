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
        Schema::create('rms_student', function (Blueprint $table) {
            $table->id('stu_id'); // Primary Key
            $table->integer('branch_id')->index();
            $table->string('stu_code', 50)->nullable()->index();
            $table->integer('user_id')->nullable()->comment('Created By');
            
            // Name
            $table->string('stu_khname', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('stu_enname', 100)->nullable();
            
            // Personal Info
            $table->tinyInteger('sex')->nullable()->default(1);
            $table->integer('nationality')->nullable();
            $table->integer('nation')->nullable();
            $table->date('dob')->nullable();
            $table->string('tel', 50)->nullable();
            $table->tinyInteger('primary_phone')->nullable()->default(1);
            
            // Address
            $table->string('pob', 255)->nullable()->comment('Place of Birth');
            $table->string('home_num', 50)->nullable();
            $table->string('street_num', 50)->nullable();
            $table->string('village_name', 100)->nullable();
            $table->string('commune_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();
            $table->integer('province_id')->nullable();
            
            // Background
            $table->integer('lang_level')->nullable();
            $table->string('from_school', 255)->nullable();
            $table->integer('know_by')->nullable();
            $table->string('sponser', 255)->nullable();
            $table->string('sponser_phone', 50)->nullable();
            
            // Enrollment
            $table->tinyInteger('status')->nullable()->default(1);
            $table->text('remark')->nullable();
            $table->date('enrollDate')->nullable();
            $table->string('photo', 255)->nullable();
            $table->integer('customer_type')->nullable()->default(1); // 1 = Student
            
            // Family & Meta
            $table->integer('familyId')->nullable();
            $table->string('studentToken', 100)->nullable();
            
            $table->dateTime('create_date')->useCurrent();
            $table->dateTime('modify_date')->nullable();
            
            $table->timestamps(); // create_at, updated_at (Laravel standard, optional)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rms_student');
    }
};
