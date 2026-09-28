<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evaluation_questions')) {
            return;
        }

        Schema::table('evaluation_questions', function (Blueprint $table): void {
            if (!Schema::hasColumn('evaluation_questions', 'form_id')) {
                $table->unsignedBigInteger('form_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('evaluation_questions', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('form_id');
            }
            if (!Schema::hasColumn('evaluation_questions', 'subheading_id')) {
                $table->unsignedBigInteger('subheading_id')->nullable()->after('section_id');
            }
            if (!Schema::hasColumn('evaluation_questions', 'type')) {
                $table->string('type', 50)->default('likert')->after('question');
            }
            if (!Schema::hasColumn('evaluation_questions', 'options')) {
                $table->json('options')->nullable()->after('type');
            }
            if (!Schema::hasColumn('evaluation_questions', 'is_required')) {
                $table->boolean('is_required')->default(true)->after('options');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('evaluation_questions')) {
            return;
        }

        Schema::table('evaluation_questions', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('evaluation_questions', 'form_id') ? 'form_id' : null,
                Schema::hasColumn('evaluation_questions', 'section_id') ? 'section_id' : null,
                Schema::hasColumn('evaluation_questions', 'subheading_id') ? 'subheading_id' : null,
                Schema::hasColumn('evaluation_questions', 'type') ? 'type' : null,
                Schema::hasColumn('evaluation_questions', 'options') ? 'options' : null,
                Schema::hasColumn('evaluation_questions', 'is_required') ? 'is_required' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
