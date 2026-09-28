<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evaluation_submissions')) {
            return;
        }

        Schema::table('evaluation_submissions', function (Blueprint $table): void {
            if (!Schema::hasColumn('evaluation_submissions', 'form_type')) {
                $table->string('form_type', 30)->default('student')->after('status');
            }
            if (!Schema::hasColumn('evaluation_submissions', 'evaluator_id')) {
                $table->foreignId('evaluator_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            }
            if (Schema::hasColumn('evaluation_submissions', 'student_id')) {
                $table->foreignId('student_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('evaluation_submissions')) {
            return;
        }

        Schema::table('evaluation_submissions', function (Blueprint $table): void {
            if (Schema::hasColumn('evaluation_submissions', 'form_type')) {
                $table->dropColumn('form_type');
            }
            if (Schema::hasColumn('evaluation_submissions', 'evaluator_id')) {
                $table->dropForeign(['evaluator_id']);
                $table->dropColumn('evaluator_id');
            }
        });
    }
};