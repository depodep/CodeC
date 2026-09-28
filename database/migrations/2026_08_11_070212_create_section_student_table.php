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
    Schema::create('section_student', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('section_id')->constrained('academic_sections')->onDelete('cascade');
        $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
        $table->timestamps();

        $table->index(['student_id', 'academic_period_id'], 'enrollment_student_period_index');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_student');
    }
};
