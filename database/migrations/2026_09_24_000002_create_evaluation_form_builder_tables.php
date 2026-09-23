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
        if (!Schema::hasTable('evaluation_forms')) {
            Schema::create('evaluation_forms', function (Blueprint $table) {
                $table->id();
                $table->string('form_type', 50); // principal, peer, student, self
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->integer('version')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('evaluation_rating_scales')) {
            Schema::create('evaluation_rating_scales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id');
                $table->string('value', 20); // 5, 4, 3, 2, 1, N/A
                $table->string('label'); // Always Manifested, etc.
                $table->integer('order_num')->default(1);
                $table->timestamps();

                $table->foreign('form_id')->references('id')->on('evaluation_forms')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('evaluation_sections')) {
            Schema::create('evaluation_sections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('order_num')->default(1);
                $table->timestamps();

                $table->foreign('form_id')->references('id')->on('evaluation_forms')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('evaluation_subheadings')) {
            Schema::create('evaluation_subheadings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('section_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('order_num')->default(1);
                $table->timestamps();

                $table->foreign('section_id')->references('id')->on('evaluation_sections')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('evaluation_questions')) {
            Schema::table('evaluation_questions', function (Blueprint $table) {
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
                    $table->string('type', 50)->default('likert')->after('question'); // likert, multiple_choice, yes_no, open_ended
                }
                if (!Schema::hasColumn('evaluation_questions', 'options')) {
                    $table->json('options')->nullable()->after('type');
                }
                if (!Schema::hasColumn('evaluation_questions', 'is_required')) {
                    $table->boolean('is_required')->default(true)->after('options');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_subheadings');
        Schema::dropIfExists('evaluation_sections');
        Schema::dropIfExists('evaluation_rating_scales');
        Schema::dropIfExists('evaluation_forms');
    }
};
