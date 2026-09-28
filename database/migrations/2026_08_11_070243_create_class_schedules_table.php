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
    Schema::create('class_schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->onDelete('cascade');
        $table->foreignId('section_id')->nullable()->constrained('academic_sections')->onDelete('cascade');
        $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
        $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
        
        // Mga idinagdag na columns base sa iyong controller import code:
        $table->string('subject_name')->nullable();
        $table->string('subject_code')->nullable();
        $table->string('grade_level')->nullable();
        $table->string('strand')->nullable();
        $table->string('section')->nullable();
        $table->string('day', 20)->nullable(); // o day_of_week
        
        $table->time('start_time');
        $table->time('end_time');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
