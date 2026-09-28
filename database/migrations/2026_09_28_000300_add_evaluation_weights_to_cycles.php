<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evaluation_cycles')) {
            return;
        }

        Schema::table('evaluation_cycles', function (Blueprint $table): void {
            if (!Schema::hasColumn('evaluation_cycles', 'student_weight')) {
                $table->decimal('student_weight', 5, 2)->default(40)->after('is_active');
            }
            if (!Schema::hasColumn('evaluation_cycles', 'principal_weight')) {
                $table->decimal('principal_weight', 5, 2)->default(40)->after('student_weight');
            }
            if (!Schema::hasColumn('evaluation_cycles', 'self_weight')) {
                $table->decimal('self_weight', 5, 2)->default(10)->after('principal_weight');
            }
            if (!Schema::hasColumn('evaluation_cycles', 'peer_weight')) {
                $table->decimal('peer_weight', 5, 2)->default(10)->after('self_weight');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('evaluation_cycles')) {
            return;
        }

        Schema::table('evaluation_cycles', function (Blueprint $table): void {
            foreach (['student_weight', 'principal_weight', 'self_weight', 'peer_weight'] as $column) {
                if (Schema::hasColumn('evaluation_cycles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};