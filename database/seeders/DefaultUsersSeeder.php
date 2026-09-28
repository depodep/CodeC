<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Seed the default administrator and temporary demo accounts.
     */
    public function run(): void
    {
        $now = now();
        $columns = Schema::getColumnListing('users');
        $activePeriodId = null;
        if (Schema::hasTable('academic_periods')) {
            $activePeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id');
            if (!$activePeriodId) {
                $activePeriodId = DB::table('academic_periods')->insertGetId([
                    'school_year' => '2025-2026',
                    'semester'    => '1st Semester',
                    'is_active'   => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        $users = [
            [
                'id_number'    => 'ADM-2026-001',
                'role_id'      => 1,
                'role_name'    => 'admin',
                'first_name'   => 'System',
                'last_name'    => 'Administrator',
                'gender'       => 'Male',
                'email'        => 'admin@siatrack.edu.ph',
                'phone_number' => '09171234567',
                'section'      => null,
                'password'     => Hash::make('AdminPass2026!'),
            ],
            [
                'id_number'    => 'MNG-2026-001',
                'role_id'      => 4,
                'role_name'    => 'management',
                'first_name'   => 'Executive',
                'last_name'    => 'Director',
                'gender'       => 'Male',
                'email'        => 'management@siatrack.edu.ph',
                'phone_number' => '09179998877',
                'section'      => 'Executive Management',
                'password'     => Hash::make('siamanagement@123'),
            ],
        ];

        foreach ($users as $user) {
            $email = $user['email'];
            unset($user['email']);

            $userData = [
                'email' => $email,
                'password' => $user['password'],
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if (in_array('id_number', $columns, true)) {
                $userData['id_number'] = $user['id_number'];
            }
            if (in_array('role_id', $columns, true)) {
                $userData['role_id'] = $user['role_id'];
            }
            if (in_array('academic_period_id', $columns, true)) {
                $userData['academic_period_id'] = $activePeriodId;
            }
            if (in_array('first_name', $columns, true)) {
                $userData['first_name'] = $user['first_name'];
                $userData['last_name'] = $user['last_name'];
            }
            if (in_array('full_name', $columns, true)) {
                $userData['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            }
            if (in_array('username', $columns, true)) {
                $userData['username'] = $user['id_number'];
            }
            if (in_array('role', $columns, true)) {
                $userData['role'] = $user['role_name'] ?? 'admin';
            }
            if (in_array('gender', $columns, true)) {
                $userData['gender'] = $user['gender'];
            }
            if (in_array('phone_number', $columns, true)) {
                $userData['phone_number'] = $user['phone_number'] ?? null;
            }
            if (in_array('parent_name', $columns, true)) {
                $userData['parent_name'] = $user['parent_name'] ?? null;
            }
            if (in_array('parent_phone_number', $columns, true)) {
                $userData['parent_phone_number'] = $user['parent_phone_number'] ?? null;
            }
            if (in_array('grade_level', $columns, true)) {
                $userData['grade_level'] = $user['grade_level'] ?? null;
            }
            if (in_array('strand', $columns, true)) {
                $userData['strand'] = $user['strand'] ?? null;
            }
            if (in_array('section', $columns, true)) {
                $userData['section'] = $user['section'] ?? null;
            }
            if (in_array('is_active', $columns, true)) {
                $userData['is_active'] = true;
            }
            if (in_array('status', $columns, true)) {
                $userData['status'] = 'active';
            }

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                $userData
            );
        }
    }
}