<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\AcademicSection;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CLEAR CURRENT TEACHERS (role_id=2) AND STUDENTS (role_id=3)
        $hasRoleCol = Schema::hasColumn('users', 'role');
        $targetUserIds = User::whereIn('role_id', [2, 3])
            ->when($hasRoleCol, function ($q) {
                $q->orWhereIn('role', ['teacher', 'faculty', 'student']);
            })
            ->pluck('id');

        if (Schema::hasTable('section_student')) {
            DB::table('section_student')->delete();
        }
        if (Schema::hasTable('nfc_cards') && $targetUserIds->count() > 0) {
            DB::table('nfc_cards')->whereIn('user_id', $targetUserIds)->delete();
        }

        // Delete schedules & sections
        if (Schema::hasTable('class_schedules')) {
            DB::table('class_schedules')->delete();
        }
        if (Schema::hasTable('academic_sections')) {
            DB::table('academic_sections')->delete();
        }

        // Delete users with role 2 or 3
        User::whereIn('id', $targetUserIds)->delete();

        $activePeriodId = null;
        if (Schema::hasTable('academic_periods')) {
            $activePeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id');
            if (!$activePeriodId) {
                $activePeriodId = DB::table('academic_periods')->insertGetId([
                    'school_year' => '2025-2026',
                    'semester'    => '1st Semester',
                    'is_active'   => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $cols = Schema::getColumnListing('users');

        // 2. SEED THE ORIGINAL 3 DEMO TEACHERS
        $teachersData = [
                [
                    'teacher_key'  => 'juan',
                    'first_name'   => 'Juan',
                    'last_name'    => 'Dela Cruz',
                    'middle_name'  => 'Reyes',
                    'id_number'    => 'TCH-2026-001',
                    'email'        => 'juan.delacruz@siatrack.edu.ph',
                    'phone_number' => '09171112233',
                    'gender'       => 'Male',
                    'section_name' => 'Sapphire',
                    'grade_level'  => '7',
                    'strand'       => null,
                ],
                [
                    'teacher_key'  => 'maria',
                    'first_name'   => 'Maria',
                    'last_name'    => 'Santos',
                    'middle_name'  => 'Garcia',
                    'id_number'    => 'TCH-2026-002',
                    'email'        => 'maria.santos@siatrack.edu.ph',
                    'phone_number' => '09172223344',
                    'gender'       => 'Female',
                    'section_name' => 'Emerald',
                    'grade_level'  => '8',
                    'strand'       => null,
                ],
                [
                    'teacher_key'  => 'ramon',
                    'first_name'   => 'Ramon',
                    'last_name'    => 'Magsaysay',
                    'middle_name'  => 'Del Rosario',
                    'id_number'    => 'TCH-2026-003',
                    'email'        => 'ramon.magsaysay@siatrack.edu.ph',
                    'phone_number' => '09173334455',
                    'gender'       => 'Male',
                    'section_name' => 'STEM A',
                    'grade_level'  => '11',
                    'strand'       => 'STEM',
                ],
            ];

            $createdTeachers = [];

            foreach ($teachersData as $t) {
                $uData = [
                    'email'    => $t['email'],
                    'password' => Hash::make('siafaculty@123'),
                ];
                if (in_array('id_number', $cols)) $uData['id_number'] = $t['id_number'];
                if (in_array('role_id', $cols)) $uData['role_id'] = 2;
                if (in_array('role', $cols)) $uData['role'] = 'teacher';
                if (in_array('first_name', $cols)) $uData['first_name'] = $t['first_name'];
                if (in_array('last_name', $cols)) $uData['last_name'] = $t['last_name'];
                if (in_array('middle_name', $cols)) $uData['middle_name'] = $t['middle_name'];
                if (in_array('name', $cols)) $uData['name'] = "{$t['first_name']} {$t['middle_name']} {$t['last_name']}";
                if (in_array('gender', $cols)) $uData['gender'] = $t['gender'];
                if (in_array('phone_number', $cols)) $uData['phone_number'] = $t['phone_number'];
                if (in_array('section', $cols)) $uData['section'] = $t['section_name'];
                if (in_array('grade_level', $cols)) $uData['grade_level'] = $t['grade_level'];
                if (in_array('strand', $cols)) $uData['strand'] = $t['strand'];
                if (in_array('academic_period_id', $cols)) $uData['academic_period_id'] = $activePeriodId;

                $user = User::create($uData);
                $createdTeachers[$t['teacher_key']] = $user;
            }

            // 3. SEED 3 ACADEMIC SECTIONS
            $sectionsData = [
                [
                    'grade_level'  => '7',
                    'section_name' => 'Sapphire',
                    'strand'       => null,
                    'advisor_id'   => $createdTeachers['juan']->id,
                ],
                [
                    'grade_level'  => '8',
                    'section_name' => 'Emerald',
                    'strand'       => null,
                    'advisor_id'   => $createdTeachers['maria']->id,
                ],
                [
                    'grade_level'  => '11',
                    'section_name' => 'STEM A',
                    'strand'       => 'STEM',
                    'advisor_id'   => $createdTeachers['ramon']->id,
                ],
            ];

            $createdSections = [];
            foreach ($sectionsData as $s) {
                $sec = AcademicSection::create([
                    'academic_period_id' => $activePeriodId,
                    'grade_level'        => $s['grade_level'],
                    'section_name'       => $s['section_name'],
                    'strand'             => $s['strand'],
                    'advisor_id'         => $s['advisor_id'],
                ]);
                $createdSections[$s['section_name']] = $sec;
            }

            // 4. SEED 10 STUDENTS FOR EACH OF THE 3 SECTIONS (30 STUDENTS TOTAL)
            $studentNames = [
                // SECTION 1: Sapphire (Grade 7)
                'Sapphire' => [
                    ['fn' => 'Sophia',   'mn' => 'Ramos',     'ln' => 'Bautista', 'gender' => 'Female', 'p_name' => 'Antonio Bautista', 'p_rel' => 'Father'],
                    ['fn' => 'Gabriel',  'mn' => 'Cruz',      'ln' => 'Fernandez', 'gender' => 'Male',   'p_name' => 'Elena Fernandez', 'p_rel' => 'Mother'],
                    ['fn' => 'Angela',   'mn' => 'Villanueva', 'ln' => 'Aquino',   'gender' => 'Female', 'p_name' => 'Carlos Aquino',  'p_rel' => 'Father'],
                    ['fn' => 'Ethan',    'mn' => 'Dizon',     'ln' => 'Reyes',    'gender' => 'Male',   'p_name' => 'Maria Reyes',    'p_rel' => 'Mother'],
                    ['fn' => 'Chloe',    'mn' => 'Valdez',    'ln' => 'Gonzales', 'gender' => 'Female', 'p_name' => 'Jose Gonzales',  'p_rel' => 'Father'],
                    ['fn' => 'Joshua',   'mn' => 'Navarro',   'ln' => 'Torres',   'gender' => 'Male',   'p_name' => 'Anna Torres',    'p_rel' => 'Mother'],
                    ['fn' => 'Samantha', 'mn' => 'Castillo',  'ln' => 'Flores',   'gender' => 'Female', 'p_name' => 'Manuel Flores',  'p_rel' => 'Father'],
                    ['fn' => 'Liam',     'mn' => 'Pineda',    'ln' => 'Mercado',  'gender' => 'Male',   'p_name' => 'Rosa Mercado',   'p_rel' => 'Mother'],
                    ['fn' => 'Hannah',   'mn' => 'Soriano',   'ln' => 'Ocampo',   'gender' => 'Female', 'p_name' => 'Pedro Ocampo',   'p_rel' => 'Father'],
                    ['fn' => 'Nathan',   'mn' => 'Castro',    'ln' => 'Delos Santos', 'gender' => 'Male', 'p_name' => 'Luz Delos Santos', 'p_rel' => 'Mother'],
                ],
                // SECTION 2: Emerald (Grade 8)
                'Emerald' => [
                    ['fn' => 'Alexander', 'mn' => 'Miranda',  'ln' => 'Perez',    'gender' => 'Male',   'p_name' => 'Roberto Perez',  'p_rel' => 'Father'],
                    ['fn' => 'Beatrice',  'mn' => 'Corpuz',   'ln' => 'Santiago', 'gender' => 'Female', 'p_name' => 'Carmen Santiago','p_rel' => 'Mother'],
                    ['fn' => 'Daniel',    'mn' => 'Tolentino', 'ln' => 'Ramos',   'gender' => 'Male',   'p_name' => 'Francisco Ramos','p_rel' => 'Father'],
                    ['fn' => 'Ella',      'mn' => 'Gomez',    'ln' => 'Mendoza',  'gender' => 'Female', 'p_name' => 'Teresa Mendoza', 'p_rel' => 'Mother'],
                    ['fn' => 'Lucas',     'mn' => 'Alvarez',  'ln' => 'Castillo', 'gender' => 'Male',   'p_name' => 'Jaime Castillo', 'p_rel' => 'Father'],
                    ['fn' => 'Mia',       'mn' => 'Salvador', 'ln' => 'Villanueva','gender' => 'Female', 'p_name' => 'Alicia Villanueva','p_rel' => 'Mother'],
                    ['fn' => 'Noah',      'mn' => 'Agustin',  'ln' => 'Gutierrez','gender' => 'Male',   'p_name' => 'Ramon Gutierrez','p_rel' => 'Father'],
                    ['fn' => 'Olivia',    'mn' => 'Bautista', 'ln' => 'Rivera',   'gender' => 'Female', 'p_name' => 'Grace Rivera',   'p_rel' => 'Mother'],
                    ['fn' => 'Patrick',   'mn' => 'Enriquez', 'ln' => 'Dela Cruz','gender' => 'Male',   'p_name' => 'Victor Dela Cruz','p_rel' => 'Father'],
                    ['fn' => 'Rachel',    'mn' => 'Pascual',  'ln' => 'San Jose', 'gender' => 'Female', 'p_name' => 'Nenita San Jose','p_rel' => 'Mother'],
                ],
                // SECTION 3: STEM A (Grade 11)
                'STEM A' => [
                    ['fn' => 'Adrian',   'mn' => 'David',     'ln' => 'Cruz',     'gender' => 'Male',   'p_name' => 'Eduardo Cruz',   'p_rel' => 'Father'],
                    ['fn' => 'Alyssa',   'mn' => 'Fajardo',   'ln' => 'Reyes',    'gender' => 'Female', 'p_name' => 'Consuelo Reyes', 'p_rel' => 'Mother'],
                    ['fn' => 'Benjamin', 'mn' => 'Velasco',   'ln' => 'Santos',   'gender' => 'Male',   'p_name' => 'Danilo Santos',  'p_rel' => 'Father'],
                    ['fn' => 'Danica',   'mn' => 'Manalo',    'ln' => 'Bautista', 'gender' => 'Female', 'p_name' => 'Sonia Bautista', 'p_rel' => 'Mother'],
                    ['fn' => 'Elijah',   'mn' => 'Nieves',    'ln' => 'Garcia',   'gender' => 'Male',   'p_name' => 'Benito Garcia',  'p_rel' => 'Father'],
                    ['fn' => 'Fiona',    'mn' => 'Quinto',    'ln' => 'Marquez',  'gender' => 'Female', 'p_name' => 'Lourdes Marquez','p_rel' => 'Mother'],
                    ['fn' => 'Jacob',    'mn' => 'Roman',     'ln' => 'Evangelista', 'gender' => 'Male', 'p_name' => 'Oscar Evangelista', 'p_rel' => 'Father'],
                    ['fn' => 'Kaitlyn',  'mn' => 'Serrano',   'ln' => 'Beltran',  'gender' => 'Female', 'p_name' => 'Imelda Beltran', 'p_rel' => 'Mother'],
                    ['fn' => 'Marcus',   'mn' => 'Samson',    'ln' => 'Ventura',  'gender' => 'Male',   'p_name' => 'Dominador Ventura', 'p_rel' => 'Father'],
                    ['fn' => 'Nicole',   'mn' => 'Tamayo',    'ln' => 'Pineda',   'gender' => 'Female', 'p_name' => 'Wilma Pineda',   'p_rel' => 'Mother'],
                ],
            ];

            $studentCounter = 1;
            $testEmergencyContact = '09261074274';
            foreach ($studentNames as $secName => $list) {
                $targetSectionObj = $createdSections[$secName];

                foreach ($list as $st) {
                    $lrn = '103063' . str_pad($studentCounter, 6, '0', STR_PAD_LEFT);
                    $studentIdNum = 'STU-2026-' . str_pad($studentCounter, 3, '0', STR_PAD_LEFT);
                    $email = strtolower($st['fn'] . '.' . str_replace(' ', '', $st['ln'])) . '@siatrack.edu.ph';
                    $phone = '092610' . str_pad($studentCounter, 5, '0', STR_PAD_LEFT);
                    $pPhone = $testEmergencyContact;

                    $uData = [
                        'email'    => $email,
                        'password' => Hash::make('onesia@123'),
                    ];

                    if (in_array('id_number', $cols)) $uData['id_number'] = $lrn;
                    if (in_array('student_id', $cols)) $uData['student_id'] = $studentIdNum;
                    if (in_array('role_id', $cols)) $uData['role_id'] = 3;
                    if (in_array('role', $cols)) $uData['role'] = 'student';
                    if (in_array('first_name', $cols)) $uData['first_name'] = $st['fn'];
                    if (in_array('last_name', $cols)) $uData['last_name'] = $st['ln'];
                    if (in_array('middle_name', $cols)) $uData['middle_name'] = $st['mn'];
                    if (in_array('name', $cols)) $uData['name'] = "{$st['fn']} {$st['mn']} {$st['ln']}";
                    if (in_array('gender', $cols)) $uData['gender'] = $st['gender'];
                    if (in_array('phone_number', $cols)) $uData['phone_number'] = $phone;
                    if (in_array('parent_name', $cols)) $uData['parent_name'] = $st['p_name'];
                    if (in_array('parent_relationship', $cols)) $uData['parent_relationship'] = $st['p_rel'];
                    if (in_array('parent_phone_number', $cols)) $uData['parent_phone_number'] = $pPhone;
                    if (in_array('grade_level', $cols)) $uData['grade_level'] = $targetSectionObj->grade_level;
                    if (in_array('strand', $cols)) $uData['strand'] = $targetSectionObj->strand;
                    if (in_array('section', $cols)) $uData['section'] = $secName;
                    if (in_array('academic_period_id', $cols)) $uData['academic_period_id'] = $activePeriodId;

                    $studentUser = User::create($uData);

                    // Insert into pivot table section_student if present
                    if (Schema::hasTable('section_student')) {
                        DB::table('section_student')->insert([
                            'section_id' => $targetSectionObj->id,
                            'student_id' => $studentUser->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $studentCounter++;
                }
            }

            $this->call(DemoScheduleCompletionSeeder::class);
    }
}
