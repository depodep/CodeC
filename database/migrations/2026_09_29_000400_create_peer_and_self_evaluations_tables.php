<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('peer_evaluations')) {
            Schema::create('peer_evaluations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('evaluation_cycle_id')->nullable()->constrained('evaluation_cycles')->nullOnDelete();
                $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('evaluatee_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('average_score', 4, 2)->default(0);
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->index(['evaluation_cycle_id', 'evaluatee_id']);
                $table->index(['evaluation_cycle_id', 'evaluator_id']);
            });
        }

        if (!Schema::hasTable('self_evaluations')) {
            Schema::create('self_evaluations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('evaluation_cycle_id')->nullable()->constrained('evaluation_cycles')->nullOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('average_score', 4, 2)->default(0);
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->index(['evaluation_cycle_id', 'teacher_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('self_evaluations');
        Schema::dropIfExists('peer_evaluations');
    }
};