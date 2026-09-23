<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_periods')) {
            return;
        }

        $schoolYear = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'active_school_year')->value('value')
            : null;
        $semester = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'active_semester')->value('value')
            : null;

        $schoolYear = $schoolYear ?: '2026-2027';
        $semester = strtoupper($semester ?: '1ST SEMESTER');

        DB::table('academic_periods')->update(['is_active' => false]);
        $period = DB::table('academic_periods')
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->first();

        $periodId = $period?->id;
        if (!$periodId) {
            $periodId = DB::table('academic_periods')->insertGetId([
                'school_year' => $schoolYear,
                'semester' => $semester,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('academic_periods')->where('id', $periodId)->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        foreach (['users', 'academic_sections', 'section_student', 'attendance_logs', 'class_schedules'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'academic_period_id')) {
                DB::table($table)->whereNull('academic_period_id')->update(['academic_period_id' => $periodId]);
            }
        }
    }

    public function down(): void
    {
        // The relationship migration owns the columns; historical backfills are retained.
    }
};