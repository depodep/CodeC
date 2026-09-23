<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'academic_period_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('academic_period_id')->nullable()->after('role_id')->constrained('academic_periods')->nullOnDelete();
                $table->index(['role_id', 'academic_period_id'], 'users_role_period_index');
            });
        }

        if (Schema::hasTable('academic_sections') && !Schema::hasColumn('academic_sections', 'academic_period_id')) {
            Schema::table('academic_sections', function (Blueprint $table) {
                $table->foreignId('academic_period_id')->nullable()->after('id')->constrained('academic_periods')->nullOnDelete();
                $table->index(['academic_period_id', 'section_name'], 'sections_period_name_index');
            });
        }

        if (Schema::hasTable('section_student') && !Schema::hasColumn('section_student', 'academic_period_id')) {
            Schema::table('section_student', function (Blueprint $table) {
                $table->foreignId('academic_period_id')->nullable()->after('section_id')->constrained('academic_periods')->nullOnDelete();
                $table->index(['student_id', 'academic_period_id'], 'enrollment_student_period_index');
            });
        }

        if (Schema::hasTable('attendance_logs') && !Schema::hasColumn('attendance_logs', 'academic_period_id')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->foreignId('academic_period_id')->nullable()->after('user_id')->constrained('academic_periods')->nullOnDelete();
                $table->index(['user_id', 'academic_period_id', 'attendance_date'], 'attendance_user_period_date_index');
            });
        }
    }

    public function down(): void
    {
        $indexes = [
            'attendance_logs' => 'attendance_user_period_date_index',
            'section_student' => 'enrollment_student_period_index',
            'academic_sections' => 'sections_period_name_index',
            'users' => 'users_role_period_index',
        ];

        foreach ($indexes as $tableName => $indexName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'academic_period_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropForeign(['academic_period_id']);
                    $table->dropIndex($indexName);
                    $table->dropColumn('academic_period_id');
                });
            }
        }
    }
};