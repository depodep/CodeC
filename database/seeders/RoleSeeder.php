<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id'          => 1,
                'name'        => 'ADMIN',
                'description' => 'SYSTEM ADMINISTRATOR',
            ],
            [
                'id'          => 2,
                'name'        => 'FACULTY',
                'description' => 'ACADEMIC INSTRUCTOR',
            ],
            [
                'id'          => 3,
                'name'        => 'STUDENT',
                'description' => 'ENROLLED STUDENT',
            ],
            [
                'id'          => 4,
                'name'        => 'MANAGEMENT',
                'description' => 'INSTITUTIONAL MANAGEMENT & OPERATIONS',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name'        => $role['name'],
                    'description' => $role['description'],
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }
}