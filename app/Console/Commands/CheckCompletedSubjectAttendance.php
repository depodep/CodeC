<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use App\Models\OutboundMessage;
use App\Models\User;
use App\Services\AndroidSmsGatewayService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckCompletedSubjectAttendance extends Command
{
    protected $signature = 'attendance:check-completed-subjects';

    protected $description = 'Notify emergency contacts and class advisers about missing subject attendance.';

    public function __construct(private AndroidSmsGatewayService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->attendanceDataIsUsable()) {
            $this->warn('Attendance monitoring skipped because required attendance fields are unavailable.');
            return self::SUCCESS;
        }

        $now = now();
        $date = $now->toDateString();
        $day = strtolower($now->format('D'));
        $templates = $this->templates();
        $processed = 0;
        $sent = 0;
        $failed = 0;

        $schedules = ClassSchedule::with(['academicSection', 'subjectRecord'])
            ->whereNotNull('end_time')
            ->get()
            ->filter(function (ClassSchedule $schedule) use ($now, $day): bool {
                $scheduledDay = strtolower(trim((string) ($schedule->day ?? $schedule->day_of_week ?? '')));
                if ($scheduledDay === '' || substr($scheduledDay, 0, 3) !== $day) {
                    return false;
                }

                try {
                    return Carbon::parse($now->toDateString() . ' ' . $schedule->end_time)->lte($now);
                } catch (\Throwable) {
                    return false;
                }
            });

        foreach ($schedules as $schedule) {
            $students = $this->studentsForSchedule($schedule);
            if ($students->isEmpty()) {
                continue;
            }

            $presentIds = DB::table('attendance_logs')
                ->whereDate('attendance_date', $date)
                ->where('class_schedule_id', $schedule->id)
                ->whereIn('attendance_type', ['subject', 'SUBJECT'])
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique();

            // A completely empty scan set is treated as incomplete data, not an all-absent class.
            if ($presentIds->isEmpty()) {
                Log::debug('Subject absence check skipped: no valid attendance records.', [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                ]);
                continue;
            }

            $absentStudents = $students->reject(fn (User $student) => $presentIds->contains((int) $student->id))->values();
            if ($absentStudents->isEmpty()) {
                continue;
            }

            $processed++;
            $sectionName = $schedule->academicSection?->section_name ?: ($schedule->section ?: 'Unassigned');
            $subjectName = $schedule->subjectRecord?->name ?: ($schedule->subject_name ?: 'Subject');
            $startTime = $this->formatTime($schedule->start_time);
            $endTime = $this->formatTime($schedule->end_time);

            foreach ($absentStudents as $student) {
                $phone = $student->parent_phone_number;
                if (!$phone) {
                    $failed++;
                    continue;
                }

                $message = $this->render($templates['subject'], [
                    '{student_name}' => $student->full_name ?: trim($student->first_name . ' ' . $student->last_name),
                    '{subject_name}' => $subjectName,
                    '{date}' => $now->format('F d, Y'),
                    '{time}' => $endTime,
                    '{start_time}' => $startTime,
                    '{end_time}' => $endTime,
                    '{section_name}' => $sectionName,
                    '{section}' => $sectionName,
                ]);

                $result = $this->smsService->sendSingleMessage($phone, $message, [
                    'student_id' => $student->id,
                    'recipient_name' => $student->parent_name ?: 'Parent/Guardian',
                    'dispatch_mode' => 'subject_schedule_absence',
                    'sender_id' => 'SIATRACK',
                    'notification_key' => "subject_absence:{$date}:{$schedule->id}:{$student->id}",
                ]);
                $result['success'] ? $sent++ : $failed++;
            }

            $this->notifyAdviser($schedule, $absentStudents, $sectionName, $subjectName, $date, $templates['adviser'], $sent, $failed);
        }

        $this->info("Checked {$processed} completed subject(s): {$sent} sent, {$failed} failed or skipped.");
        return self::SUCCESS;
    }

    private function notifyAdviser(ClassSchedule $schedule, $students, string $sectionName, string $subjectName, string $date, string $template, int &$sent, int &$failed): void
    {
        $adviser = $schedule->academicSection?->advisor_id
            ? User::find($schedule->academicSection->advisor_id)
            : null;
        $phone = $adviser?->phone_number;
        if (!$adviser || !$phone) {
            $failed++;
            return;
        }

        $names = $students->map(fn (User $student) => $student->full_name ?: trim($student->first_name . ' ' . $student->last_name))->implode(', ');
        $message = $this->render($template, [
            '{section_name}' => $sectionName,
            '{section}' => $sectionName,
            '{subject_name}' => $subjectName,
            '{date}' => Carbon::parse($date)->format('F d, Y'),
            '{absent_students}' => $names,
        ]);

        $result = $this->smsService->sendSingleMessage($phone, $message, [
            'recipient_name' => $adviser->full_name ?: trim($adviser->first_name . ' ' . $adviser->last_name),
            'dispatch_mode' => 'classroom_section_absence',
            'sender_id' => 'SIATRACK',
            'notification_key' => "section_absence:{$date}:{$schedule->id}:{$adviser->id}",
        ]);
        $result['success'] ? $sent++ : $failed++;
    }

    private function studentsForSchedule(ClassSchedule $schedule)
    {
        if ($schedule->section_id && Schema::hasTable('section_student')) {
            return User::where('role_id', 3)
                ->whereIn('id', DB::table('section_student')->where('section_id', $schedule->section_id)->pluck('student_id'))
                ->get();
        }

        $sectionName = trim((string) ($schedule->section ?: $schedule->academicSection?->section_name));
        return $sectionName === ''
            ? collect()
            : User::where('role_id', 3)->where('section', $sectionName)->get();
    }

    private function attendanceDataIsUsable(): bool
    {
        return Schema::hasTable('attendance_logs')
            && Schema::hasColumn('attendance_logs', 'user_id')
            && Schema::hasColumn('attendance_logs', 'attendance_date')
            && Schema::hasColumn('attendance_logs', 'class_schedule_id')
            && Schema::hasColumn('attendance_logs', 'attendance_type')
            && Schema::hasColumn('attendance_logs', 'status');
    }

    private function templates(): array
    {
        $defaults = [
            'subject' => 'Your child, {student_name}, was recorded absent from {subject_name} on {date}, scheduled from {start_time} to {end_time}. Please contact the school for confirmation.',
            'adviser' => 'Attendance Advisory: The following students from {section_name} were recorded absent on {date}: {absent_students}. Please verify their attendance.',
        ];

        if (!Schema::hasTable('settings')) {
            return $defaults;
        }

        $saved = DB::table('settings')->whereIn('key', ['sms_subject_absence_template', 'sms_classroom_absence_template'])->pluck('value', 'key');
        return [
            'subject' => $saved['sms_subject_absence_template'] ?? $defaults['subject'],
            'adviser' => $saved['sms_classroom_absence_template'] ?? $defaults['adviser'],
        ];
    }

    private function render(string $template, array $values): string
    {
        return strtr($template, $values);
    }

    private function formatTime(?string $time): string
    {
        return $time ? Carbon::parse($time)->format('g:i A') : 'the scheduled time';
    }
}
