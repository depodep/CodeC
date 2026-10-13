<?php

namespace Database\Seeders;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DemoScheduleCompletionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = AcademicSection::whereIn('section_name', ['Sapphire', 'Emerald', 'STEM A'])
            ->get()
            ->keyBy('section_name');

        foreach (['Sapphire', 'Emerald', 'STEM A'] as $sectionName) {
            if (!$sections->has($sectionName)) {
                throw new RuntimeException("Cannot complete the demo timetable: section {$sectionName} does not exist.");
            }
        }

        $teacherProfiles = [
            'juan' => ['email' => 'juan.delacruz@siatrack.edu.ph', 'id_number' => 'TCH-2026-001'],
            'maria' => ['email' => 'maria.santos@siatrack.edu.ph', 'id_number' => 'TCH-2026-002'],
            'ramon' => ['email' => 'ramon.magsaysay@siatrack.edu.ph', 'id_number' => 'TCH-2026-003'],
            'filipino' => [
                'first_name' => 'Liza', 'middle_name' => 'Mendoza', 'last_name' => 'Reyes',
                'id_number' => 'TCH-2026-004', 'email' => 'liza.reyes@siatrack.edu.ph',
                'phone_number' => '09174445566', 'gender' => 'Female',
            ],
            'araling-panlipunan' => [
                'first_name' => 'Carlo', 'middle_name' => 'Santos', 'last_name' => 'Mendoza',
                'id_number' => 'TCH-2026-005', 'email' => 'carlo.mendoza@siatrack.edu.ph',
                'phone_number' => '09175556677', 'gender' => 'Male',
            ],
            'mapeh' => [
                'first_name' => 'Ana', 'middle_name' => 'Garcia', 'last_name' => 'Villanueva',
                'id_number' => 'TCH-2026-006', 'email' => 'ana.villanueva@siatrack.edu.ph',
                'phone_number' => '09176667788', 'gender' => 'Female',
            ],
            'tle' => [
                'first_name' => 'Roberto', 'middle_name' => 'Torres', 'last_name' => 'Garcia',
                'id_number' => 'TCH-2026-007', 'email' => 'roberto.garcia@siatrack.edu.ph',
                'phone_number' => '09177778899', 'gender' => 'Male',
            ],
            'values' => [
                'first_name' => 'Teresa', 'middle_name' => 'Navarro', 'last_name' => 'Flores',
                'id_number' => 'TCH-2026-008', 'email' => 'teresa.flores@siatrack.edu.ph',
                'phone_number' => '09178889900', 'gender' => 'Female',
            ],
        ];

        $courseCatalog = [
            'math' => [
                'teacher_key' => 'juan',
                'subject_code' => ['7' => 'MATH-7', '8' => 'MATH-8', '11' => 'GEN-MATH'],
                'subject_name' => ['7' => 'Mathematics 7', '8' => 'Mathematics 8', '11' => 'General Mathematics'],
            ],
            'science' => [
                'teacher_key' => 'maria',
                'subject_code' => ['7' => 'SCI-7', '8' => 'SCI-8', '11' => 'CHEM-1'],
                'subject_name' => ['7' => 'Science 7', '8' => 'Science 8', '11' => 'General Chemistry 1'],
            ],
            'english' => [
                'teacher_key' => 'ramon',
                'subject_code' => ['7' => 'ENG-7', '8' => 'ENG-8', '11' => 'ORAL-COMM'],
                'subject_name' => ['7' => 'English 7', '8' => 'English 8', '11' => 'Oral Communication'],
            ],
            'filipino' => [
                'teacher_key' => 'filipino',
                'subject_code' => ['7' => 'FIL-7', '8' => 'FIL-8', '11' => 'FIL-PILING-LARANG'],
                'subject_name' => ['7' => 'Filipino 7', '8' => 'Filipino 8', '11' => 'Filipino sa Piling Larang'],
            ],
            'araling-panlipunan' => [
                'teacher_key' => 'araling-panlipunan',
                'subject_code' => ['7' => 'AP-7', '8' => 'AP-8', '11' => 'PH-POL-GOV'],
                'subject_name' => ['7' => 'Araling Panlipunan 7', '8' => 'Araling Panlipunan 8', '11' => 'Philippine Politics and Governance'],
            ],
            'mapeh' => [
                'teacher_key' => 'mapeh',
                'subject_code' => ['7' => 'MAPEH-7', '8' => 'MAPEH-8', '11' => 'PEH-11'],
                'subject_name' => ['7' => 'MAPEH 7', '8' => 'MAPEH 8', '11' => 'Physical Education and Health'],
            ],
            'tle' => [
                'teacher_key' => 'tle',
                'subject_code' => ['7' => 'TLE-7', '8' => 'TLE-8', '11' => 'EMP-TECH'],
                'subject_name' => ['7' => 'Technology and Livelihood Education 7', '8' => 'Technology and Livelihood Education 8', '11' => 'Empowerment Technologies'],
            ],
            'values' => [
                'teacher_key' => 'values',
                'subject_code' => ['7' => 'ESP-7', '8' => 'ESP-8', '11' => 'PERSONAL-DEV'],
                'subject_name' => ['7' => 'Edukasyon sa Pagpapakatao 7', '8' => 'Edukasyon sa Pagpapakatao 8', '11' => 'Personal Development'],
            ],
        ];

        $sectionTimetables = [
            'Sapphire' => [
                ['course' => 'math', 'start_time' => '07:00:00', 'end_time' => '08:30:00'],
                ['course' => 'values', 'start_time' => '08:45:00', 'end_time' => '09:15:00'],
                ['course' => 'english', 'start_time' => '09:15:00', 'end_time' => '10:15:00'],
                ['course' => 'filipino', 'start_time' => '10:15:00', 'end_time' => '11:15:00'],
                ['course' => 'araling-panlipunan', 'start_time' => '11:15:00', 'end_time' => '12:00:00'],
                ['course' => 'mapeh', 'start_time' => '13:00:00', 'end_time' => '13:30:00'],
                ['course' => 'science', 'start_time' => '13:45:00', 'end_time' => '15:15:00'],
                ['course' => 'tle', 'start_time' => '15:15:00', 'end_time' => '16:30:00'],
            ],
            'Emerald' => [
                ['course' => 'tle', 'start_time' => '07:00:00', 'end_time' => '08:30:00'],
                ['course' => 'filipino', 'start_time' => '08:45:00', 'end_time' => '09:15:00'],
                ['course' => 'araling-panlipunan', 'start_time' => '09:15:00', 'end_time' => '10:15:00'],
                ['course' => 'mapeh', 'start_time' => '10:15:00', 'end_time' => '11:15:00'],
                ['course' => 'values', 'start_time' => '11:15:00', 'end_time' => '12:00:00'],
                ['course' => 'math', 'start_time' => '13:00:00', 'end_time' => '13:30:00'],
                ['course' => 'english', 'start_time' => '13:45:00', 'end_time' => '15:15:00'],
                ['course' => 'science', 'start_time' => '15:15:00', 'end_time' => '16:30:00'],
            ],
            'STEM A' => [
                ['course' => 'english', 'start_time' => '07:00:00', 'end_time' => '07:30:00'],
                ['course' => 'araling-panlipunan', 'start_time' => '07:30:00', 'end_time' => '08:00:00'],
                ['course' => 'filipino', 'start_time' => '08:00:00', 'end_time' => '08:15:00'],
                ['course' => 'math', 'start_time' => '09:00:00', 'end_time' => '11:00:00'],
                ['course' => 'science', 'start_time' => '09:00:00', 'end_time' => '11:00:00'],
                ['course' => 'tle', 'start_time' => '11:00:00', 'end_time' => '11:30:00'],
                ['course' => 'values', 'start_time' => '13:30:00', 'end_time' => '13:45:00'],
                ['course' => 'mapeh', 'start_time' => '15:00:00', 'end_time' => '17:00:00'],
            ],
        ];
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $previousClassPeriods = [
            ['start_time' => '07:00:00', 'end_time' => '08:00:00'],
            ['start_time' => '08:00:00', 'end_time' => '09:00:00'],
            ['start_time' => '09:00:00', 'end_time' => '10:00:00'],
            ['start_time' => '10:00:00', 'end_time' => '11:00:00'],
            ['start_time' => '11:00:00', 'end_time' => '12:00:00'],
            ['start_time' => '13:00:00', 'end_time' => '14:00:00'],
            ['start_time' => '14:00:00', 'end_time' => '15:00:00'],
            ['start_time' => '15:00:00', 'end_time' => '16:00:00'],
        ];
        $previousSectionCourseOffsets = ['Sapphire' => 0, 'Emerald' => 3, 'STEM A' => 6];
        $columns = Schema::getColumnListing('users');
        $activePeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id');

        DB::transaction(function () use (
            $sections,
            $teacherProfiles,
            $courseCatalog,
            $sectionTimetables,
            $weekdays,
            $previousClassPeriods,
            $previousSectionCourseOffsets,
            $columns,
            $activePeriodId
        ) {
            $teachers = [];
            foreach ($teacherProfiles as $key => $profile) {
                $user = User::where('email', $profile['email'])->first();

                if ($user) {
                    if ((int) $user->role_id !== 2) {
                        throw new RuntimeException("The demo faculty email {$profile['email']} belongs to a non-faculty account.");
                    }
                    $teachers[$key] = $user;
                    continue;
                }

                if ($key === 'juan' || $key === 'maria' || $key === 'ramon') {
                    throw new RuntimeException("Cannot complete the demo timetable: original faculty account {$profile['email']} is missing.");
                }

                $firstName = $profile['first_name'];
                $middleName = $profile['middle_name'];
                $lastName = $profile['last_name'];
                $userData = [
                    'email' => $profile['email'],
                    'password' => Hash::make('siafaculty@123'),
                ];

                $optionalUserFields = [
                    'id_number' => $profile['id_number'],
                    'role_id' => 2,
                    'role' => 'teacher',
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'name' => "{$firstName} {$middleName} {$lastName}",
                    'full_name' => "{$firstName} {$middleName} {$lastName}",
                    'username' => $profile['id_number'],
                    'gender' => $profile['gender'],
                    'phone_number' => $profile['phone_number'],
                    'section' => null,
                    'grade_level' => null,
                    'strand' => null,
                    'academic_period_id' => $activePeriodId,
                    'is_active' => true,
                    'status' => 'active',
                ];

                foreach ($optionalUserFields as $column => $value) {
                    if (in_array($column, $columns, true)) {
                        $userData[$column] = $value;
                    }
                }

                $teachers[$key] = User::create($userData);
            }

            $expectedSchedules = [];
            foreach ($sectionTimetables as $sectionName => $dailyTimetable) {
                $section = $sections[$sectionName];
                $gradeLevel = (string) $section->grade_level;

                foreach ($weekdays as $day) {
                    foreach ($dailyTimetable as $period) {
                        $course = $courseCatalog[$period['course']];
                        $expectedSchedules[] = [
                            'academic_period_id' => $activePeriodId,
                            'section_id' => $section->id,
                            'teacher_id' => $teachers[$course['teacher_key']]->id,
                            'subject_code' => $course['subject_code'][$gradeLevel],
                            'subject_name' => $course['subject_name'][$gradeLevel],
                            'grade_level' => $gradeLevel,
                            'strand' => $section->strand,
                            'section' => $sectionName,
                            'day' => $day,
                            'start_time' => $period['start_time'],
                            'end_time' => $period['end_time'],
                        ];
                    }
                }
            }

            $sectionIds = $sections->pluck('id');
            $currentSchedules = ClassSchedule::whereIn('section_id', $sectionIds)->get();
            $scheduleSignature = static fn ($schedule) => implode('|', [
                data_get($schedule, 'academic_period_id'),
                data_get($schedule, 'section_id'),
                data_get($schedule, 'teacher_id'),
                data_get($schedule, 'section'),
                data_get($schedule, 'subject_code'),
                data_get($schedule, 'subject_name'),
                data_get($schedule, 'day'),
                data_get($schedule, 'start_time'),
                data_get($schedule, 'end_time'),
            ]);

            $expectedSignatures = collect($expectedSchedules)->map($scheduleSignature)->sort()->values();
            $currentSignatures = $currentSchedules->map($scheduleSignature)->sort()->values();
            if ($currentSchedules->count() === count($expectedSchedules) && $currentSignatures->all() === $expectedSignatures->all()) {
                return;
            }

            if ($currentSchedules->isEmpty()) {
                foreach ($expectedSchedules as $schedule) {
                    ClassSchedule::create($schedule);
                }
                return;
            }

            $previousCourseOrder = array_keys($courseCatalog);
            $previousSchedules = collect();
            foreach ($previousSectionCourseOffsets as $sectionName => $courseOffset) {
                $section = $sections[$sectionName];
                $gradeLevel = (string) $section->grade_level;

                foreach ($weekdays as $day) {
                    foreach ($previousClassPeriods as $periodIndex => $period) {
                        $courseKey = $previousCourseOrder[($periodIndex + $courseOffset) % count($previousCourseOrder)];
                        $course = $courseCatalog[$courseKey];
                        $previousSchedules->push([
                            'academic_period_id' => $activePeriodId,
                            'section_id' => $section->id,
                            'teacher_id' => $teachers[$course['teacher_key']]->id,
                            'subject_code' => $course['subject_code'][$gradeLevel],
                            'subject_name' => $course['subject_name'][$gradeLevel],
                            'grade_level' => $gradeLevel,
                            'strand' => $section->strand,
                            'section' => $sectionName,
                            'day' => $day,
                            'start_time' => $period['start_time'],
                            'end_time' => $period['end_time'],
                        ]);
                    }
                }
            }
            $previousSignatures = $previousSchedules->map($scheduleSignature)->sort()->values();

            $legacySchedules = [
                ['Sapphire', 'juan', null, 'Mathematics 7', 'Monday', '07:00:00', '09:30:00'],
                ['Sapphire', 'maria', 'SCI-7', 'Science 7', 'Monday', '09:45:00', '11:15:00'],
                ['Sapphire', 'ramon', 'ENG-7', 'English 7', 'Monday', '13:00:00', '14:30:00'],
                ['Emerald', 'maria', 'SCI-8', 'Science 8', 'Tuesday', '08:00:00', '09:30:00'],
                ['Emerald', 'juan', 'MATH-8', 'Mathematics 8', 'Tuesday', '09:45:00', '11:15:00'],
                ['Emerald', 'ramon', 'ENG-8', 'English 8', 'Tuesday', '13:00:00', '14:30:00'],
                ['STEM A', 'ramon', 'EMP-TECH', 'Empowerment Technologies', 'Wednesday', '08:00:00', '09:30:00'],
                ['STEM A', 'juan', 'GEN-MATH', 'General Mathematics', 'Wednesday', '09:45:00', '11:15:00'],
                ['STEM A', 'maria', 'CHEM-1', 'General Chemistry 1', 'Wednesday', '13:00:00', '14:30:00'],
            ];
            $legacySignatures = collect($legacySchedules)->map(function ($legacy) use (
                $sections,
                $teachers,
                $activePeriodId,
                $scheduleSignature
            ) {
                [$sectionName, $teacherKey, $subjectCode, $subjectName, $day, $startTime, $endTime] = $legacy;
                return $scheduleSignature([
                    'academic_period_id' => $activePeriodId,
                    'section_id' => $sections[$sectionName]->id,
                    'teacher_id' => $teachers[$teacherKey]->id,
                    'section' => $sectionName,
                    'subject_code' => $subjectCode,
                    'subject_name' => $subjectName,
                    'day' => $day,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            })->sort()->values();

            $matchesPreviousDemoTimetable = $currentSchedules->count() === $previousSignatures->count()
                && $currentSignatures->all() === $previousSignatures->all();
            $matchesOriginalDemoTimetable = $currentSchedules->count() === $legacySignatures->count()
                && $currentSignatures->all() === $legacySignatures->all();

            if (!$matchesPreviousDemoTimetable && !$matchesOriginalDemoTimetable) {
                throw new RuntimeException('The demo sections contain schedules outside the known sample timetable; no schedules were changed.');
            }

            ClassSchedule::whereIn('id', $currentSchedules->pluck('id'))->delete();
            foreach ($expectedSchedules as $schedule) {
                ClassSchedule::create($schedule);
            }
        });

        $this->command?->info('Demo timetable completed: 8 faculty and 120 weekday class schedules.');
    }
}
