<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->foreignId('class_schedule_id')
                ->nullable()
                ->after('academic_period_id')
                ->constrained('class_schedules')
                ->nullOnDelete();
            $table->foreignId('nfc_card_id')
                ->nullable()
                ->after('class_schedule_id')
                ->constrained('nfc_cards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('nfc_card_id');
            $table->dropConstrainedForeignId('class_schedule_id');
        });
    }
};
