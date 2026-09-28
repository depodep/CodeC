<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 100)->nullable()->unique()->after('email');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        DB::table('users')
            ->where('role_id', 3)
            ->orderBy('id')
            ->get(['id', 'id_number', 'first_name'])
            ->each(function ($student): void {
                $base = Str::lower(preg_replace('/[^a-z0-9]/i', '', (string) $student->id_number)
                    . preg_replace('/[^a-z0-9]/i', '', (string) $student->first_name));
                $base = $base !== '' ? $base : 'student' . $student->id;
                $username = $base;
                $suffix = 2;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base . $suffix++;
                }

                DB::table('users')->where('id', $student->id)->update([
                    'username' => $username,
                    'email' => null,
                ]);
            });

    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
