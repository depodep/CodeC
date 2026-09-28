<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_principal')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_principal')->default(false)->after('role_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_principal')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_principal');
            });
        }
    }
};