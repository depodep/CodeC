<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Carbon\Carbon;

class RandomAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('attendance_logs')) {
            $this->command?->warn('Attendance logs table is not available. Run migrations first.');
            return;
        }

        $columns = Schema::getColumnListing('attendance_logs');
        $students = User::where('role_id', 3)->get();
        if ($students->isEmpty()) {
            $this->command?->warn('No students found. Run DemoDataSeeder before RandomAttendanceSeeder.');
            return;
        }

        $periodId = Schema::hasColumn('attendance_logs', 'academic_period_id') && Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', 1)->value('id')
            : null;
        $sections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->get()->keyBy('section_name')
            : collect();
        $schedules = Schema::hasTable('class_schedules')
            ? DB::table('class_schedules')->whereNotNull('section')->get()->groupBy('section')
            : collect();

        mt_srand(20261008);
        $created = 0;
        $today = Carbon::today();

        for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
            $date = $today->copy()->subDays($daysAgo);
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($students as $student) {
                // Leave a realistic portion absent. Keep today populated for the dashboard.
                $attended = $daysAgo === 0
                    ? mt_rand(1, 100) <= 65
                    : mt_rand(1, 100) <= 82;
                if (!$attended) {
                    continue;
                }

                $section = $sections->get($student->section);
                $status = mt_rand(1, 100) <= 82 ? 'ON-TIME' : 'LATE';
                $timeIn = $status === 'LATE'
                    ? sprintf('07:%02d:00', mt_rand(16, 45))
                    : sprintf('06:%02d:00', mt_rand(35, 59));

                $advisoryData = [
                    'user_id' => $student->id,
                    'attendance_date' => $date->toDateString(),
                    'time_in' => $timeIn,
                    'time_out' => sprintf('16:%02d:00', mt_rand(0, 45)),
                    'status' => $status,
                ];
                $this->addIfColumn($advisoryData, 'academic_period_id', $periodId, $columns);
                $this->addIfColumn($advisoryData, 'attendance_type', 'advisory', $columns);
                $this->addIfColumn($advisoryData, 'advisory_section_id', $section?->id, $columns);
                $this->addIfColumn($advisoryData, 'recorded_by', $section?->advisor_id, $columns);
                $this->addIfColumn($advisoryData, 'sms_status', 'SENT', $columns);
                $this->addIfColumn($advisoryData, 'created_at', $date->copy()->setTime(7, 0), $columns);
                $this->addIfColumn($advisoryData, 'updated_at', $date->copy()->setTime(7, 0), $columns);

                $this->insertIfMissing($advisoryData, 'advisory', $columns);
                $created++;

                $subjectSchedule = $schedules->get($student->section, collect())->first();
                if (!$subjectSchedule || !Schema::hasColumn('attendance_logs', 'class_schedule_id')) {
                    continue;
                }

                $subjectData = [
                    'user_id' => $student->id,
                    'attendance_date' => $date->toDateString(),
                    'time_in' => $timeIn,
                    'time_out' => null,
                    'status' => $status,
                ];
                $this->addIfColumn($subjectData, 'academic_period_id', $periodId, $columns);
                $this->addIfColumn($subjectData, 'attendance_type', 'subject', $columns);
                $this->addIfColumn($subjectData, 'class_schedule_id', $subjectSchedule->id, $columns);
                $this->addIfColumn($subjectData, 'recorded_by', $subjectSchedule->teacher_id ?? null, $columns);
                $this->addIfColumn($subjectData, 'sms_status', 'SENT', $columns);
                $this->addIfColumn($subjectData, 'created_at', $date->copy()->setTime(7, 0), $columns);
                $this->addIfColumn($subjectData, 'updated_at', $date->copy()->setTime(7, 0), $columns);

                $this->insertIfMissing($subjectData, 'subject', $columns);
                $created++;
            }
        }

        $this->command?->info("Random attendance seeding complete: {$created} records created.");
    }

    private function insertIfMissing(array $data, string $type, array $columns): void
    {
        $query = DB::table('attendance_logs')
            ->where('user_id', $data['user_id'])
            ->whereDate('attendance_date', $data['attendance_date']);

        if (in_array('attendance_type', $columns, true)) {
            $query->where('attendance_type', $type);
        }
        if ($type === 'advisory' && isset($data['advisory_section_id'])) {
            $query->where('advisory_section_id', $data['advisory_section_id']);
        }
        if ($type === 'subject' && isset($data['class_schedule_id'])) {
            $query->where('class_schedule_id', $data['class_schedule_id']);
        }

        if (!$query->exists()) {
            DB::table('attendance_logs')->insert($data);
        }
    }

    private function addIfColumn(array &$data, string $column, mixed $value, array $columns): void
    {
        if (in_array($column, $columns, true) && $value !== null) {
            $data[$column] = $value;
        }
    }
}
