<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->unique(
                ['user_id', 'attendance_date', 'attendance_type', 'class_schedule_id', 'advisory_section_id'],
                'attendance_logs_one_record_per_context'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->dropUnique('attendance_logs_one_record_per_context');
        });
    }
};
