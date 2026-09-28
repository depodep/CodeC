<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['ON-TIME', 'LATE'])->default('ON-TIME');
            $table->string('sms_status', 50)->default('PENDING');
            
            $table->string('academic_year')->nullable();
            $table->string('semester')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'attendance_date']);
            $table->index(['academic_year', 'semester']);
            $table->index(['user_id', 'academic_period_id', 'attendance_date'], 'attendance_user_period_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};