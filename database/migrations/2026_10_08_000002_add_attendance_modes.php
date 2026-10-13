<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->string('attendance_type', 20)->default('subject')->after('nfc_card_id');
            $table->foreignId('advisory_section_id')->nullable()->after('attendance_type')
                ->constrained('academic_sections')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->after('advisory_section_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['attendance_type', 'attendance_date', 'user_id'], 'attendance_type_date_user_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->dropIndex('attendance_type_date_user_index');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropConstrainedForeignId('advisory_section_id');
            $table->dropColumn('attendance_type');
        });
    }
};
