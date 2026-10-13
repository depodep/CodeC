<?php

namespace App\Console\Commands;

use App\Models\AcademicSection;
use App\Models\User;
use App\Services\AndroidSmsGatewayService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * CheckAdvisoryAttendance
 *
 * Runs once daily (default: 8:30 AM) and notifies parents of students
 * who have NO advisory time-in scan recorded for the current school day.
 * Also notifies the section adviser with a summary of absent students.
 *
 * Duplicate prevention: uses `notification_key` on outbound_messages so
 * the same student will never receive two advisory-absence SMS on the same date.
 */
class CheckAdvisoryAttendance extends Command
{
    protected $signature = 'attendance:check-advisory
                            {--date= : Override date to check (Y-m-d). Defaults to today.}
                            {--dry-run : Show who would be notified without sending SMS.}';

    protected $description = 'Notify parents of students with no advisory time-in scan by the cutoff time.';

    public function __construct(private AndroidSmsGatewayService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->tablesExist()) {
            $this->warn('Advisory attendance check skipped: required tables/columns are missing.');
            return self::SUCCESS;
        }

        $date    = $this->option('date') ?? now()->toDateString();
        $dryRun  = $this->option('dry-run');
        $today   = Carbon::parse($date);

        // Skip weekends
        if ($today->isWeekend()) {
            $this->info("Skipped — {$date} is a weekend.");
            return self::SUCCESS;
        }

        $templates = $this->templates();
        $sent      = 0;
        $failed    = 0;
        $skipped   = 0;

        // Load all active sections (adviser loaded manually below via User::find)
        $sections = AcademicSection::all();

        if ($sections->isEmpty()) {
            $this->warn('No academic sections found. Nothing to check.');
            return self::SUCCESS;
        }

        foreach ($sections as $section) {
            // Fetch students in this section
            $students = $this->studentsForSection($section);
            if ($students->isEmpty()) {
                Log::debug('Advisory check: no students in section.', [
                    'section_id' => $section->id,
                    'section'    => $section->section_name,
                ]);
                continue;
            }

            // Students who HAVE scanned in today (any status except ABSENT)
            $presentIds = DB::table('attendance_logs')
                ->whereDate('attendance_date', $date)
                ->where('advisory_section_id', $section->id)
                ->where('attendance_type', 'advisory')
                ->whereNotNull('time_in')
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique();

            // Students who are ABSENT = no scan at all today
            $absentStudents = $students->reject(
                fn (User $s) => $presentIds->contains((int) $s->id)
            )->values();

            if ($absentStudents->isEmpty()) {
                Log::debug('Advisory check: all students present.', [
                    'section'    => $section->section_name,
                    'date'       => $date,
                ]);
                continue;
            }

            Log::info('Advisory check: absent students found.', [
                'section' => $section->section_name,
                'date'    => $date,
                'count'   => $absentStudents->count(),
            ]);

            // Notify each absent student's parent
            foreach ($absentStudents as $student) {
                $notifKey = "advisory_absence:{$date}:{$section->id}:{$student->id}";

                // Dedup: skip if SMS already sent today
                $alreadySent = DB::table('outbound_messages')
                    ->where('notification_key', $notifKey)
                    ->where('status', 'sent')
                    ->exists();

                if ($alreadySent) {
                    $skipped++;
                    continue;
                }

                $phone = $student->parent_phone_number;
                if (! $phone) {
                    Log::debug('Advisory absence: no parent phone.', ['student_id' => $student->id]);
                    $failed++;
                    continue;
                }

                $studentName  = $student->full_name ?: trim($student->first_name . ' ' . $student->last_name);
                $sectionName  = $section->section_name;
                $gradeLevel   = $section->grade_level;
                $formattedDate = $today->format('F d, Y');

                $message = $this->render($templates['parent'], [
                    '{student_name}'  => $studentName,
                    '{section_name}'  => $sectionName,
                    '{grade_level}'   => $gradeLevel,
                    '{date}'          => $formattedDate,
                ]);

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would send to {$phone} ({$student->parent_name}): {$message}");
                    continue;
                }

                $result = $this->smsService->sendSingleMessage($phone, $message, [
                    'student_id'       => $student->id,
                    'recipient_name'   => $student->parent_name ?: 'Parent/Guardian',
                    'dispatch_mode'    => 'advisory_absence',
                    'sender_id'        => 'SIATRACK',
                    'notification_key' => $notifKey,
                ]);

                $result['success'] ? $sent++ : $failed++;
            }

            // Notify the section adviser with a summary
            $this->notifyAdviser($section, $absentStudents, $date, $today, $templates['adviser'], $sent, $failed, $dryRun);
        }

        $this->info("Advisory absence check done for {$date}: {$sent} sent, {$failed} failed, {$skipped} already sent (skipped).");
        return self::SUCCESS;
    }

    /**
     * Send a summary SMS to the section adviser listing all absent students.
     */
    private function notifyAdviser(
        AcademicSection $section,
        \Illuminate\Support\Collection $absentStudents,
        string $date,
        Carbon $today,
        string $template,
        int &$sent,
        int &$failed,
        bool $dryRun
    ): void {
        $adviser = $section->advisor_id ? User::find($section->advisor_id) : null;
        $phone   = $adviser?->phone_number;

        if (! $adviser || ! $phone) {
            Log::debug('Advisory adviser notify skipped: no adviser or phone.', ['section_id' => $section->id]);
            $failed++;
            return;
        }

        $notifKey = "advisory_adviser:{$date}:{$section->id}:{$adviser->id}";

        $alreadySent = DB::table('outbound_messages')
            ->where('notification_key', $notifKey)
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            return;
        }

        $names = $absentStudents
            ->map(fn (User $s) => $s->full_name ?: trim($s->first_name . ' ' . $s->last_name))
            ->implode(', ');

        $message = $this->render($template, [
            '{section_name}'     => $section->section_name,
            '{grade_level}'      => $section->grade_level,
            '{date}'             => $today->format('F d, Y'),
            '{absent_students}'  => $names,
            '{absent_count}'     => $absentStudents->count(),
        ]);

        if ($dryRun) {
            $this->line("[DRY-RUN] Adviser notify to {$phone} ({$adviser->first_name}): {$message}");
            return;
        }

        $result = $this->smsService->sendSingleMessage($phone, $message, [
            'recipient_name'   => $adviser->full_name ?: trim($adviser->first_name . ' ' . $adviser->last_name),
            'dispatch_mode'    => 'advisory_absence_adviser',
            'sender_id'        => 'SIATRACK',
            'notification_key' => $notifKey,
        ]);

        $result['success'] ? $sent++ : $failed++;
    }

    /**
     * Get all students belonging to the given section via section_student pivot.
     * Falls back to matching the section_name on the users.section column.
     */
    private function studentsForSection(AcademicSection $section): \Illuminate\Support\Collection
    {
        if (Schema::hasTable('section_student')) {
            $studentIds = DB::table('section_student')
                ->where('section_id', $section->id)
                ->pluck('student_id');

            if ($studentIds->isNotEmpty()) {
                return User::where('role_id', 3)
                    ->whereIn('id', $studentIds)
                    ->where('is_active', 1)
                    ->get();
            }
        }

        // Fallback: match by section name string on users table
        return User::where('role_id', 3)
            ->where('section', $section->section_name)
            ->where('is_active', 1)
            ->get();
    }

    /**
     * Check that required tables and columns exist before running.
     */
    private function tablesExist(): bool
    {
        return Schema::hasTable('attendance_logs')
            && Schema::hasColumn('attendance_logs', 'advisory_section_id')
            && Schema::hasColumn('attendance_logs', 'attendance_type')
            && Schema::hasColumn('attendance_logs', 'time_in')
            && Schema::hasTable('academic_sections');
    }

    /**
     * Load SMS templates from the settings table (if available), or use defaults.
     */
    private function templates(): array
    {
        $defaults = [
            'parent'  => 'Advisory Notice: Your child, {student_name} (Grade {grade_level} - {section_name}), has not yet checked in at school as of today, {date}. Please contact the school if this is unexpected.',
            'adviser' => 'Advisory Alert ({section_name} - Grade {grade_level}): {absent_count} student(s) have not checked in as of {date}. Absent: {absent_students}.',
        ];

        if (! Schema::hasTable('settings')) {
            return $defaults;
        }

        $saved = DB::table('settings')
            ->whereIn('key', ['sms_advisory_absence_template', 'sms_advisory_adviser_template'])
            ->pluck('value', 'key');

        return [
            'parent'  => $saved['sms_advisory_absence_template']  ?? $defaults['parent'],
            'adviser' => $saved['sms_advisory_adviser_template']   ?? $defaults['adviser'],
        ];
    }

    private function render(string $template, array $values): string
    {
        return strtr($template, $values);
    }
}
