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
        if (Schema::hasTable('evaluation_submissions') && !Schema::hasColumn('evaluation_submissions', 'answers')) {
            Schema::table('evaluation_submissions', function (Blueprint $table) {
                $table->longText('answers')->nullable()->after('comments');
            });
        }

        if (Schema::hasTable('peer_evaluations') && !Schema::hasColumn('peer_evaluations', 'answers')) {
            Schema::table('peer_evaluations', function (Blueprint $table) {
                $table->longText('answers')->nullable()->after('comments');
            });
        }

        if (Schema::hasTable('self_evaluations') && !Schema::hasColumn('self_evaluations', 'answers')) {
            Schema::table('self_evaluations', function (Blueprint $table) {
                $table->longText('answers')->nullable()->after('comments');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('evaluation_submissions') && Schema::hasColumn('evaluation_submissions', 'answers')) {
            Schema::table('evaluation_submissions', function (Blueprint $table) {
                $table->dropColumn('answers');
            });
        }

        if (Schema::hasTable('peer_evaluations') && Schema::hasColumn('peer_evaluations', 'answers')) {
            Schema::table('peer_evaluations', function (Blueprint $table) {
                $table->dropColumn('answers');
            });
        }

        if (Schema::hasTable('self_evaluations') && Schema::hasColumn('self_evaluations', 'answers')) {
            Schema::table('self_evaluations', function (Blueprint $table) {
                $table->dropColumn('answers');
            });
        }
    }
};
