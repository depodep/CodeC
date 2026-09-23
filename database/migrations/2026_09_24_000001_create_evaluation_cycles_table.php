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
        if (!Schema::hasTable('evaluation_cycles')) {
            Schema::create('evaluation_cycles', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g. Faculty Evaluation - Midterm
                $table->string('school_year', 50); // e.g. 2026-2027
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('active'); // active, completed
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('teacher_evaluation_publications')) {
            Schema::create('teacher_evaluation_publications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('evaluation_cycle_id');
                $table->unsignedBigInteger('teacher_id');
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['evaluation_cycle_id', 'teacher_id'], 'cycle_teacher_unique');
            });
        }

        if (Schema::hasTable('peer_evaluations') && !Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
            Schema::table('peer_evaluations', function (Blueprint $table) {
                $table->unsignedBigInteger('evaluation_cycle_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('evaluation_submissions') && !Schema::hasColumn('evaluation_submissions', 'evaluation_cycle_id')) {
            Schema::table('evaluation_submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('evaluation_cycle_id')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_evaluation_publications');
        Schema::dropIfExists('evaluation_cycles');
    }
};
