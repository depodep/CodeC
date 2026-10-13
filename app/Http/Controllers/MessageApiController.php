<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\OutboundMessage;
use App\Models\User;
use App\Services\AndroidSmsGatewayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MessageApiController extends Controller
{
    public function __construct(
        protected AndroidSmsGatewayService $smsService
    ) {}

    /**
     * Teacher Messaging Dashboard View.
     */
    public function teacherIndex(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $teacher = Auth::user();

        // 1. Resolve Teacher's Associated Students
        $teacherSections = collect();
        if (!empty($teacher->section)) {
            $teacherSections->push($teacher->section);
        }
        if (Schema::hasTable('academic_sections')) {
            $advised = DB::table('academic_sections')->where('advisor_id', $teacher->id)->pluck('section_name');
            $teacherSections = $teacherSections->merge($advised);
        }
        if (Schema::hasTable('class_schedules')) {
            $schedSections = DB::table('class_schedules')->where('teacher_id', $teacher->id)->pluck('section');
            $teacherSections = $teacherSections->merge($schedSections);
        }
        $teacherSections = $teacherSections->filter()->unique()->values();

        $teacherAdvisorySections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('advisor_id', $teacher->id)->get()
            : collect();

        $absenceDate = $request->input('absence_date', today()->toDateString());
        try {
            $absenceDate = Carbon::parse($absenceDate)->toDateString();
        } catch (\Throwable) {
            $absenceDate = today()->toDateString();
        }

        $teacherSchedules = Schema::hasTable('class_schedules')
            ? ClassSchedule::with(['academicSection', 'subjectRecord'])->where('teacher_id', $teacher->id)->orderBy('start_time')->get()
            : collect();

        $studentsQuery = User::where('role_id', 3);
        if ($teacherSections->isNotEmpty()) {
            $studentsQuery->whereIn('section', $teacherSections);
        }
        $students = $studentsQuery->orderBy('last_name')->orderBy('first_name')->get();

        // 2. Query Messages (Teacher scope)
        $query = OutboundMessage::with(['sender', 'student'])
            ->where(function ($q) use ($teacher, $students) {
                $q->where('sender_user_id', $teacher->id);
                if ($students->isNotEmpty()) {
                    $q->orWhereIn('student_id', $students->pluck('id'));
                }
            });

        $this->applyFilters($query, $request);

        // Stats (computed before pagination)
        $totalSent = (clone $query)->where('status', 'delivered')->count();
        $totalFailed = (clone $query)->where('status', 'failed')->count();

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;
        $messages = $query->latest('created_at')->paginate($perPage)->withQueryString();

        $messageTabs = collect([
            ['id' => 'advisory', 'label' => 'Advisory', 'count' => $teacherAdvisorySections->count()],
            ['id' => 'subjects', 'label' => 'My Subjects', 'count' => $teacherSchedules->count()],
        ]);
        $absenceContexts = $teacherSchedules->map(function (ClassSchedule $schedule) use ($absenceDate): array {
            $section = $schedule->academicSection?->section_name ?: $schedule->section ?: 'Unassigned';
            $studentIds = $schedule->section_id && Schema::hasTable('section_student')
                ? DB::table('section_student')->where('section_id', $schedule->section_id)->pluck('student_id')
                : User::where('role_id', 3)->where('section', $section)->pluck('id');
            $presentIds = DB::table('attendance_logs')
                ->whereDate('attendance_date', $absenceDate)
                ->where('class_schedule_id', $schedule->id)
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->whereIn('user_id', $studentIds)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id);
            $absentIds = $studentIds->map(fn ($id) => (int) $id)->diff($presentIds);
            $absentCount = $absentIds->count();

            $contextKey = (string) $schedule->id;
            $sentCount = OutboundMessage::whereDate('created_at', $absenceDate)
                ->whereIn('student_id', $absentIds)
                ->whereIn('status', ['sent', 'delivered', 'pending'])
                ->where(function ($q) use ($contextKey) {
                    $q->where('notification_key', 'like', "%:{$contextKey}:%")
                      ->orWhere('notification_key', 'like', "%subject-{$contextKey}:%")
                      ->orWhere('dispatch_mode', 'like', '%absence%');
                })
                ->distinct('student_id')
                ->count('student_id');

            return [
                'id' => 'subject-' . $schedule->id,
                'type' => 'subject',
                'schedule_id' => $schedule->id,
                'section_id' => $schedule->section_id,
                'label' => ($schedule->subjectRecord?->name ?: $schedule->subject_name ?: 'Subject') . ' - ' . $section,
                'section' => $section,
                'student_count' => $studentIds->count(),
                'absent_count' => $absentCount,
                'sent_count' => $sentCount,
                'remaining_count' => max(0, $absentCount - $sentCount),
            ];
        })->merge($teacherAdvisorySections->map(function ($section) use ($absenceDate): array {
            $studentIds = Schema::hasTable('section_student')
                ? DB::table('section_student')->where('section_id', $section->id)->pluck('student_id')
                : User::where('role_id', 3)->where('section', $section->section_name)->pluck('id');
            $presentIds = DB::table('attendance_logs')
                ->whereDate('attendance_date', $absenceDate)
                ->where('advisory_section_id', $section->id)
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->whereIn('user_id', $studentIds)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id);
            $absentIds = $studentIds->map(fn ($id) => (int) $id)->diff($presentIds);
            $absentCount = $absentIds->count();

            $contextKey = 'advisory-' . $section->id;
            $sentCount = OutboundMessage::whereDate('created_at', $absenceDate)
                ->whereIn('student_id', $absentIds)
                ->whereIn('status', ['sent', 'delivered', 'pending'])
                ->where(function ($q) use ($contextKey, $section) {
                    $q->where('notification_key', 'like', "%{$contextKey}%")
                      ->orWhere('notification_key', 'like', "%{$section->section_name}%")
                      ->orWhere('dispatch_mode', 'like', '%absence%');
                })
                ->distinct('student_id')
                ->count('student_id');

            return [
                'id' => 'advisory-' . $section->id,
                'type' => 'advisory',
                'schedule_id' => null,
                'section_id' => $section->id,
                'label' => 'Advisory - ' . $section->section_name,
                'section' => $section->section_name,
                'student_count' => $studentIds->count(),
                'absent_count' => $absentCount,
                'sent_count' => $sentCount,
                'remaining_count' => max(0, $absentCount - $sentCount),
            ];
        }))->values();

        if ($request->wantsJson() || $request->boolean('partial')) {
            return response()->json([
                'messages' => $messages->getCollection()->map(function (OutboundMessage $message): array {
                    return [
                        'id' => $message->id,
                        'created_at' => $message->created_at?->format('M d, Y'),
                        'time' => $message->created_at?->format('h:i A'),
                        'recipient_name' => $message->recipient_name ?: ($message->student?->full_name ?: 'Parent/Contact'),
                        'contact_number' => $message->contact_number,
                        'message' => \Illuminate\Support\Str::limit($message->message, 70, '...'),
                        'sender' => $message->sender_display_name,
                        'status' => strtolower((string) $message->status),
                    ];
                })->values(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'from' => $messages->firstItem(),
                    'to' => $messages->lastItem(),
                    'total' => $messages->total(),
                    'per_page' => $messages->perPage(),
                    'previous_url' => $messages->previousPageUrl(),
                    'next_url' => $messages->nextPageUrl(),
                ],
            ]);
        }

        return view('teacher.message', compact(
            'teacher',
            'students',
            'messages',
            'totalSent',
            'totalFailed',
            'messageTabs',
            'absenceContexts',
            'absenceDate'
        ));
    }

    /**
     * Send teacher-triggered absence notifications for one owned subject or advisory section.
     */
    public function sendTeacherAbsenceNotifications(Request $request): JsonResponse
    {
        $teacher = Auth::user();
        $request->validate([
            'context_type' => ['required', 'in:subject,advisory'],
            'schedule_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'absence_date' => ['nullable', 'date'],
            'retry' => ['nullable', 'boolean'],
        ]);

        $date = Carbon::parse($request->input('absence_date', today()->toDateString()));
        $type = $request->input('context_type');
        $schedule = null;
        $section = null;

        if ($type === 'subject') {
            $schedule = ClassSchedule::with(['academicSection', 'subjectRecord'])
                ->where('id', $request->integer('schedule_id'))
                ->where('teacher_id', $teacher->id)
                ->first();
            if (!$schedule) {
                return response()->json(['success' => false, 'message' => 'You can only trigger alerts for your assigned subjects.'], 403);
            }
            $scheduledDay = strtolower(trim((string) ($schedule->day ?? $schedule->day_of_week ?? '')));
            if ($scheduledDay !== '' && substr($scheduledDay, 0, 3) !== strtolower($date->format('D'))) {
                return response()->json(['success' => false, 'message' => 'This subject is not scheduled on the selected date.'], 422);
            }
            if (!$schedule->end_time || Carbon::parse($date->toDateString() . ' ' . $schedule->end_time)->isFuture()) {
                return response()->json(['success' => false, 'message' => 'This subject has not ended yet.'], 422);
            }
            $section = $schedule->academicSection;
        } else {
            $section = DB::table('academic_sections')
                ->where('id', $request->integer('section_id'))
                ->where('advisor_id', $teacher->id)
                ->first();
            if (!$section) {
                return response()->json(['success' => false, 'message' => 'You can only trigger alerts for your advisory sections.'], 403);
            }
        }

        if (!Schema::hasTable('attendance_logs')
            || !Schema::hasColumn('attendance_logs', 'user_id')
            || !Schema::hasColumn('attendance_logs', 'attendance_date')) {
            return response()->json(['success' => false, 'message' => 'Attendance data is not available. No alerts were sent.'], 422);
        }

        $sectionId = $schedule?->section_id ?: $section?->id;
        $sectionName = $schedule?->academicSection?->section_name ?: ($schedule?->section ?: $section?->section_name);
        $studentIds = Schema::hasTable('section_student') && $sectionId
            ? DB::table('section_student')->where('section_id', $sectionId)->pluck('student_id')
            : User::where('role_id', 3)->where('section', $sectionName)->pluck('id');
        $students = User::where('role_id', 3)->whereIn('id', $studentIds)->get();
        if ($students->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No students are assigned to this context.'], 422);
        }

        $attendanceQuery = DB::table('attendance_logs')
            ->whereDate('attendance_date', $date->toDateString())
            ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present']);
        if (Schema::hasColumn('attendance_logs', 'attendance_type')) {
            $attendanceQuery->where('attendance_type', $type);
        }
        if ($type === 'subject') {
            $attendanceQuery->where('class_schedule_id', $schedule->id);
        } elseif (Schema::hasColumn('attendance_logs', 'advisory_section_id')) {
            $attendanceQuery->where('advisory_section_id', $sectionId);
        }
        $presentIds = $attendanceQuery->pluck('user_id')->map(fn ($id) => (int) $id)->unique();
        if ($presentIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Attendance data is incomplete for this context. No students were classified as absent.'], 422);
        }

        $absentStudents = $students->reject(fn (User $student) => $presentIds->contains((int) $student->id))->values();
        if ($absentStudents->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'No absent students were found.', 'summary' => ['total' => 0, 'sent' => 0, 'failed' => 0]]);
        }

        $gateway = $this->smsService->ping();
        if (!$gateway['online']) {
            return response()->json(['success' => false, 'message' => 'SMS gateway is offline. No messages were sent.', 'gateway' => $gateway], 503);
        }

        $subjectName = $schedule?->subjectRecord?->name ?: ($schedule?->subject_name ?: 'Advisory');
        $subjectTemplate = $this->smsTemplate('sms_subject_absence_template', 'Your child, {student_name}, was recorded absent from {subject_name} on {date}, scheduled from {start_time} to {end_time}. Please contact the school for confirmation.');
        $adviserTemplate = $this->smsTemplate('sms_classroom_absence_template', 'Attendance Advisory: The following students from {section_name} were recorded absent on {date}: {absent_students}. Please verify their attendance.');
        $summary = ['total' => $absentStudents->count(), 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'skipped_reasons' => []];
        $startTime = $schedule?->start_time ? Carbon::parse($schedule->start_time)->format('g:i A') : 'N/A';
        $endTime = $schedule?->end_time ? Carbon::parse($schedule->end_time)->format('g:i A') : 'N/A';

        foreach ($absentStudents as $student) {
            if (!$student->parent_phone_number) {
                $summary['skipped']++;
                $summary['skipped_reasons'][] = ['phone' => 'N/A', 'reason' => "No contact number for {$student->full_name}"];
                continue;
            }
            $studentName = $student->full_name ?: trim($student->first_name . ' ' . $student->last_name);
            $message = strtr($subjectTemplate, [
                '{student_name}' => $studentName,
                '{subject_name}' => $subjectName,
                '{date}' => $date->format('F d, Y'),
                '{start_time}' => $startTime,
                '{end_time}' => $endTime,
                '{time}' => $endTime,
                '{section_name}' => $sectionName ?: 'Unassigned',
                '{section}' => $sectionName ?: 'Unassigned',
            ]);
            $result = $this->smsService->sendSingleMessage($student->parent_phone_number, $message, [
                'sender_user_id' => $teacher->id,
                'student_id' => $student->id,
                'recipient_name' => $student->parent_name ?: 'Parent/Guardian',
                'dispatch_mode' => 'teacher_' . $type . '_absence',
                'sender_id' => 'SIATRACK',
                'notification_key' => "teacher_subject_absence:{$date->toDateString()}:" . ($schedule?->id ?: 'advisory-' . $sectionId) . ":{$student->id}",
                'retry_failed' => $request->boolean('retry'),
            ]);
            if (!empty($result['skipped'])) {
                $summary['skipped']++;
                $summary['skipped_reasons'][] = ['phone' => $student->parent_phone_number, 'reason' => $result['reason'] ?? 'Skipped by gateway'];
            } elseif ($result['success']) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
            }
        }

        $adviser = $section?->advisor_id ? User::find($section->advisor_id) : null;
        if ($adviser?->phone_number) {
            $names = $absentStudents->map(fn (User $student) => $student->full_name ?: trim($student->first_name . ' ' . $student->last_name))->implode(', ');
            $adviserMessage = strtr($adviserTemplate, [
                '{section_name}' => $sectionName ?: 'Unassigned',
                '{section}' => $sectionName ?: 'Unassigned',
                '{subject_name}' => $subjectName,
                '{date}' => $date->format('F d, Y'),
                '{absent_students}' => $names,
            ]);
            $result = $this->smsService->sendSingleMessage($adviser->phone_number, $adviserMessage, [
                'sender_user_id' => $teacher->id,
                'recipient_name' => $adviser->full_name ?: trim($adviser->first_name . ' ' . $adviser->last_name),
                'dispatch_mode' => 'teacher_adviser_absence',
                'sender_id' => 'SIATRACK',
                'notification_key' => "teacher_adviser_absence:{$date->toDateString()}:" . ($schedule?->id ?: 'advisory-' . $sectionId) . ":{$adviser->id}",
                'retry_failed' => $request->boolean('retry'),
            ]);
            if (!empty($result['skipped'])) {
                $summary['skipped']++;
                $summary['skipped_reasons'][] = ['phone' => $adviser->phone_number, 'reason' => $result['reason'] ?? 'Skipped by gateway'];
            } elseif (!$result['success']) {
                $summary['failed']++;
            }
        }

        $parts = ["{$summary['sent']} sent", "{$summary['failed']} failed"];
        if ($summary['skipped'] > 0) $parts[] = "{$summary['skipped']} skipped";

        $isSuccessful = $summary['sent'] > 0 || ($summary['skipped'] > 0 && $summary['failed'] === 0);

        return response()->json([
            'success' => $isSuccessful,
            'message' => "Absence notification complete: " . implode(', ', $parts) . ".",
            'summary' => $summary,
        ], $isSuccessful ? 200 : 422);
    }

    private function smsTemplate(string $key, string $fallback): string
    {
        return Schema::hasTable('settings')
            ? (DB::table('settings')->where('key', $key)->value('value') ?: $fallback)
            : $fallback;
    }

    /**
     * Admin Messaging Dashboard View.
     */
    public function adminIndex(Request $request): View
    {
        $admin = Auth::user();
        $today = $request->input('absence_date', Carbon::today()->toDateString());
        try {
            $today = Carbon::parse($today)->toDateString();
        } catch (\Throwable) {
            $today = Carbon::today()->toDateString();
        }

        $students = User::where('role_id', 3)->orderBy('last_name')->orderBy('first_name')->get();
        $teachers = User::where('role_id', 2)->orderBy('last_name')->orderBy('first_name')->get();

        // 1. Custom Absent SMS Template Setting
        $defaultTemplate = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'absence_sms_template')->value('value')
            : null;
        if (!$defaultTemplate) {
            $defaultTemplate = "Good morning. This is to inform you that {student_name} was marked absent today, {date}. Please contact {school_name} if you have any concerns.";
        }

        // 2. Summary Statistics (Today & All-Time)
        $totalSentToday = OutboundMessage::whereDate('created_at', $today)->count();
        $successSentToday = OutboundMessage::whereDate('created_at', $today)->where('status', 'delivered')->count();
        $failedSentToday = OutboundMessage::whereDate('created_at', $today)->where('status', 'failed')->count();
        $pendingSentToday = OutboundMessage::whereDate('created_at', $today)->where('status', 'pending')->count();

        // 3. Build Detailed Absence Contexts (Advisory Sections & Subject Classes)
        $absenceContexts = collect();

        // 3a. Advisory Sections
        if (Schema::hasTable('academic_sections')) {
            $sections = DB::table('academic_sections')->orderBy('section_name')->get();
            foreach ($sections as $section) {
                $studentIds = Schema::hasTable('section_student')
                    ? DB::table('section_student')->where('section_id', $section->id)->pluck('student_id')
                    : User::where('role_id', 3)->where('section', $section->section_name)->pluck('id');

                $presentIds = Schema::hasTable('attendance_logs')
                    ? DB::table('attendance_logs')
                        ->whereDate('attendance_date', $today)
                        ->where('advisory_section_id', $section->id)
                        ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                        ->whereIn('user_id', $studentIds)
                        ->pluck('user_id')
                        ->map(fn ($id) => (int) $id)
                    : collect();

                $absentIds = $studentIds->map(fn ($id) => (int) $id)->diff($presentIds);
                $absentCount = $absentIds->count();

                $contextKey = 'advisory-' . $section->id;
                $sentCount = OutboundMessage::whereDate('created_at', $today)
                    ->whereIn('student_id', $absentIds)
                    ->whereIn('status', ['sent', 'delivered', 'pending'])
                    ->where(function ($q) use ($contextKey, $section) {
                        $q->where('notification_key', 'like', "%{$contextKey}%")
                          ->orWhere('notification_key', 'like', "%{$section->section_name}%")
                          ->orWhere('dispatch_mode', 'like', '%absence%');
                    })
                    ->distinct('student_id')
                    ->count('student_id');

                $absenceContexts->push([
                    'id' => 'advisory-' . $section->id,
                    'type' => 'advisory',
                    'schedule_id' => null,
                    'section_id' => $section->id,
                    'label' => 'Advisory - ' . $section->section_name,
                    'name' => $section->section_name,
                    'section' => $section->section_name,
                    'teacher' => null,
                    'student_count' => $studentIds->count(),
                    'absent_count' => $absentCount,
                    'sent_count' => $sentCount,
                    'remaining_count' => max(0, $absentCount - $sentCount),
                ]);
            }
        }

        // 3b. Subject Classes
        if (Schema::hasTable('class_schedules')) {
            $schedules = ClassSchedule::with(['academicSection', 'subjectRecord', 'teacher'])
                ->orderBy('day')
                ->orderBy('start_time')
                ->get();
            foreach ($schedules as $sched) {
                $secName = $sched->academicSection?->section_name ?: ($sched->section ?: 'Unassigned');
                $studentIds = $sched->section_id && Schema::hasTable('section_student')
                    ? DB::table('section_student')->where('section_id', $sched->section_id)->pluck('student_id')
                    : User::where('role_id', 3)->where('section', $secName)->pluck('id');

                $presentIds = Schema::hasTable('attendance_logs')
                    ? DB::table('attendance_logs')
                        ->whereDate('attendance_date', $today)
                        ->where('class_schedule_id', $sched->id)
                        ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                        ->whereIn('user_id', $studentIds)
                        ->pluck('user_id')
                        ->map(fn ($id) => (int) $id)
                    : collect();

                $absentIds = $studentIds->map(fn ($id) => (int) $id)->diff($presentIds);
                $absentCount = $absentIds->count();

                $contextKey = (string) $sched->id;
                $sentCount = OutboundMessage::whereDate('created_at', $today)
                    ->whereIn('student_id', $absentIds)
                    ->whereIn('status', ['sent', 'delivered', 'pending'])
                    ->where(function ($q) use ($contextKey) {
                        $q->where('notification_key', 'like', "%:{$contextKey}:%")
                          ->orWhere('notification_key', 'like', "%subject-{$contextKey}:%")
                          ->orWhere('dispatch_mode', 'like', '%absence%');
                    })
                    ->distinct('student_id')
                    ->count('student_id');

                $subjName = $sched->subjectRecord?->name ?: ($sched->subject_name ?: 'Subject');
                $absenceContexts->push([
                    'id' => 'subject-' . $sched->id,
                    'type' => 'subject',
                    'schedule_id' => $sched->id,
                    'section_id' => $sched->section_id,
                    'label' => $subjName . ' - ' . $secName,
                    'name' => $subjName,
                    'section' => $secName,
                    'teacher' => $sched->teacher?->full_name ?: 'Teacher',
                    'student_count' => $studentIds->count(),
                    'absent_count' => $absentCount,
                    'sent_count' => $sentCount,
                    'remaining_count' => max(0, $absentCount - $sentCount),
                ]);
            }
        }

        $absentStudentsCount = $absenceContexts->where('type', 'advisory')->sum('absent_count');
        if ($absentStudentsCount === 0 && $absenceContexts->where('type', 'subject')->isNotEmpty()) {
            $absentStudentsCount = $absenceContexts->where('type', 'subject')->sum('absent_count');
        }

        $absentBySection = $absenceContexts->where('type', 'advisory')->map(fn ($ctx) => (object)[
            'section' => $ctx['name'],
            'total' => $ctx['absent_count'],
            'sent' => $ctx['sent_count'],
            'remaining' => $ctx['remaining_count'],
        ])->values();

        // 4. Filtered Outbound Message History Table
        $query = OutboundMessage::with(['sender', 'student']);
        $this->applyFilters($query, $request);

        $totalSent = (clone $query)->where('status', 'delivered')->count();
        $totalFailed = (clone $query)->where('status', 'failed')->count();

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;
        $messages = $query->latest('created_at')->paginate($perPage)->withQueryString();

        // 5. Active School Year
        $activeSchoolYear = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'active_school_year')->value('value')
            : null;
        if (!$activeSchoolYear) {
            $activeSchoolYear = date('Y') . '-' . (date('Y') + 1);
        }

        return view('admin.message', compact(
            'admin',
            'students',
            'teachers',
            'messages',
            'totalSent',
            'totalFailed',
            'totalSentToday',
            'successSentToday',
            'failedSentToday',
            'pendingSentToday',
            'absentStudentsCount',
            'defaultTemplate',
            'today',
            'activeSchoolYear',
            'absentBySection',
            'absenceContexts'
        ));
    }

    /**
     * Update the Absent Custom SMS Template setting.
     * Route: POST /admin/sms/update-template
     */
    public function updateAbsenceTemplate(Request $request)
    {
        $request->validate([
            'absence_sms_template' => 'required|string|max:500'
        ]);

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'absence_sms_template'],
                ['value' => $request->absence_sms_template, 'updated_at' => now()]
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Absence SMS template updated successfully.',
                'template' => $request->absence_sms_template,
            ]);
        }

        return redirect()->route('admin.message')->with('success', 'Absence SMS template updated successfully.');
    }

    /**
     * Send today's absence notifications for selected advisory sections and/or subject classes.
     */
    public function sendAbsenceNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'absence_date' => 'required|date',
            'context_ids' => 'nullable|array',
            'context_ids.*' => 'string|max:100',
            'sections' => 'nullable|array',
            'sections.*' => 'string|max:100',
            'retry' => 'nullable|boolean',
        ]);

        $gateway = $this->smsService->ping();
        if (!$gateway['online']) {
            return response()->json([
                'success' => false,
                'message' => 'SMS gateway is offline. No absence notifications were sent.',
                'summary' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'skipped_reasons' => []],
                'gateway' => $gateway,
            ], 503);
        }

        $today = Carbon::parse($request->input('absence_date'));
        $todayString = $today->toDateString();
        $retry = $request->boolean('retry');

        $template = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'absence_sms_template')->value('value')
            : null;
        $template = $template ?: 'Good morning. This is to inform you that {student_name} was marked absent today, {date}. Please contact {school_name} if you have any concerns.';

        $selectedContextIds = collect($request->input('context_ids', []))->filter()->unique()->values();
        $selectedSections = collect($request->input('sections', []))->filter()->unique()->values();

        // If no context_ids provided but sections provided, resolve sections into advisory context IDs
        if ($selectedContextIds->isEmpty() && $selectedSections->isNotEmpty()) {
            foreach ($selectedSections as $secName) {
                $sec = DB::table('academic_sections')->where('section_name', $secName)->first();
                if ($sec) {
                    $selectedContextIds->push('advisory-' . $sec->id);
                } else {
                    $selectedContextIds->push('section-' . $secName);
                }
            }
        }

        if ($selectedContextIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No sections or subjects selected for absence notification.',
                'summary' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'skipped_reasons' => []],
            ], 422);
        }

        $summary = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'skipped_reasons' => [],
        ];

        $processedNotificationKeys = [];

        foreach ($selectedContextIds as $ctxId) {
            $isSubject = str_starts_with($ctxId, 'subject-');

            if ($isSubject) {
                $schedId = (int) str_replace('subject-', '', $ctxId);
                $schedule = ClassSchedule::with(['academicSection', 'subjectRecord'])->find($schedId);
                if (!$schedule) {
                    continue;
                }

                $section = $schedule->academicSection?->section_name ?: ($schedule->section ?: 'Unassigned');
                $studentIds = $schedule->section_id && Schema::hasTable('section_student')
                    ? DB::table('section_student')->where('section_id', $schedule->section_id)->pluck('student_id')
                    : User::where('role_id', 3)->where('section', $section)->pluck('id');

                $presentIds = DB::table('attendance_logs')
                    ->whereDate('attendance_date', $todayString)
                    ->where('class_schedule_id', $schedId)
                    ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                    ->whereIn('user_id', $studentIds)
                    ->pluck('user_id')
                    ->map(fn ($id) => (int) $id);

                $absentStudents = User::where('role_id', 3)->whereIn('id', $studentIds->map(fn ($id) => (int) $id)->diff($presentIds))->get();
                $contextLabel = ($schedule->subjectRecord?->name ?: ($schedule->subject_name ?: 'Subject')) . ' - ' . $section;

                foreach ($absentStudents as $student) {
                    $notificationKey = "admin_absence:{$todayString}:subject-{$schedId}:{$student->id}";
                    $this->dispatchStudentAbsence($student, $notificationKey, $contextLabel, $today, $template, $summary, $retry, $processedNotificationKeys);
                }
            } else {
                $cleanId = str_replace(['advisory-', 'section-'], '', $ctxId);
                $section = is_numeric($cleanId) && (int) $cleanId > 0
                    ? DB::table('academic_sections')->where('id', (int) $cleanId)->first()
                    : DB::table('academic_sections')->where('section_name', $cleanId)->first();

                $sectionName = $section?->section_name ?: $cleanId;
                $studentIds = $section && Schema::hasTable('section_student')
                    ? DB::table('section_student')->where('section_id', $section->id)->pluck('student_id')
                    : User::where('role_id', 3)->where('section', $sectionName)->pluck('id');

                $presentQuery = DB::table('attendance_logs')
                    ->whereDate('attendance_date', $todayString)
                    ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                    ->whereIn('user_id', $studentIds);
                if ($section && Schema::hasColumn('attendance_logs', 'advisory_section_id')) {
                    $presentQuery->where('advisory_section_id', $section->id);
                }
                $presentIds = $presentQuery->pluck('user_id')->map(fn ($id) => (int) $id);

                $absentStudents = User::where('role_id', 3)->whereIn('id', $studentIds->map(fn ($id) => (int) $id)->diff($presentIds))->get();
                $contextLabel = 'Advisory - ' . $sectionName;

                foreach ($absentStudents as $student) {
                    $notificationKey = "admin_absence:{$todayString}:advisory-" . ($section?->id ?: $sectionName) . ":{$student->id}";
                    $this->dispatchStudentAbsence($student, $notificationKey, $contextLabel, $today, $template, $summary, $retry, $processedNotificationKeys);
                }
            }
        }

        if ($summary['total'] === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No absent students found for the selected targets.',
                'summary' => $summary,
            ]);
        }

        $parts = ["{$summary['sent']} sent", "{$summary['failed']} failed"];
        if ($summary['skipped'] > 0) {
            $parts[] = "{$summary['skipped']} skipped";
        }

        $isSuccessful = $summary['sent'] > 0 || ($summary['skipped'] > 0 && $summary['failed'] === 0);

        return response()->json([
            'success' => $isSuccessful,
            'message' => "Absence notifications: " . implode(', ', $parts) . ".",
            'summary' => $summary,
        ], $isSuccessful ? 200 : 422);
    }

    /**
     * Helper to dispatch individual absent student notifications with duplicate prevention.
     */
    private function dispatchStudentAbsence(User $student, string $notificationKey, string $contextLabel, Carbon $today, string $template, array &$summary, bool $retry, array &$processedNotificationKeys): void
    {
        if (in_array($notificationKey, $processedNotificationKeys, true)) {
            return;
        }
        $processedNotificationKeys[] = $notificationKey;
        $summary['total']++;

        $studentName = $student->full_name ?: trim("{$student->first_name} {$student->last_name}");
        $contact = $student->parent_phone_number ?: $student->phone_number;

        if (!$contact) {
            $summary['skipped']++;
            $summary['skipped_reasons'][] = [
                'recipient' => $studentName,
                'phone' => 'N/A',
                'reason' => "No contact number for {$studentName} ({$contextLabel})",
            ];
            return;
        }

        // Duplicate check to avoid redundant messaging
        $existing = OutboundMessage::where('notification_key', $notificationKey)->latest('id')->first();
        if ($existing && $existing->status !== 'failed' && !$retry) {
            $summary['skipped']++;
            $summary['skipped_reasons'][] = [
                'recipient' => $studentName,
                'phone' => $contact,
                'reason' => "Notification already sent for {$studentName} ({$contextLabel})",
            ];
            return;
        }

        $message = strtr($template, [
            '{student_name}' => $studentName,
            '{date}' => $today->format('F d, Y'),
            '{time}' => now()->format('g:i A'),
            '{section}' => $student->section ?: 'General',
            '{school_name}' => 'Southern Isabela Academy',
        ]);

        $result = $this->smsService->sendSingleMessage($contact, $message, [
            'sender_user_id' => Auth::id(),
            'student_id' => $student->id,
            'recipient_name' => $student->parent_name ?: "Parent/Guardian of {$studentName}",
            'dispatch_mode' => 'admin_absence',
            'sender_id' => 'Scan-to-notify',
            'notification_key' => $notificationKey,
            'retry_failed' => $retry,
        ]);

        if (!empty($result['skipped'])) {
            $summary['skipped']++;
            $summary['skipped_reasons'][] = [
                'recipient' => $studentName,
                'phone' => $contact,
                'reason' => $result['reason'] ?? 'Skipped by gateway',
            ];
        } elseif ($result['success']) {
            $summary['sent']++;
        } else {
            $summary['failed']++;
        }
    }

    /**
     * Gateway Liveness & Latency Ping.
     * Route: GET /system/status/sms
     */
    public function pingGateway(): JsonResponse
    {
        $result = $this->smsService->ping();

        return response()->json([
            'success'     => $result['online'],
            'online'      => $result['online'],
            'latency'     => $result['latency'],
            'status_code' => $result['status_code'],
            'message'     => $result['message'],
        ]);
    }

    /**
     * Send SMS Endpoint (All / Selected / Specific student, Teacher, or Custom Number).
     * Route: POST /send-sms
     */
    public function sendSms(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_mode' => 'required|string|in:all_parents,all_students,selected_students,specific_student,all_teachers,specific_teacher,custom',
            'message'        => 'required|string|max:1000',
            'student_id'     => 'nullable|integer',
            'student_ids'    => 'nullable|array',
            'student_ids.*'  => 'integer',
            'teacher_id'     => 'nullable|integer',
            'custom_number'  => 'nullable|string',
            'custom_name'    => 'nullable|string|max:100',
        ]);

        $currentUser = Auth::user();
        $recipientMode = $request->input('recipient_mode');
        $rawMessage = trim($request->input('message'));

        $gateway = $this->smsService->ping();
        if (!$gateway['online']) {
            return response()->json([
                'success' => false,
                'message' => 'SMS gateway is offline. No messages were sent.',
                'gateway' => $gateway,
            ], 503);
        }

        // Handle Teacher / Signature footer toggle
        $finalMessage = $rawMessage;
        if ($request->boolean('include_signature')) {
            $sigTeacher = $request->input('signature_teacher') ?: ($currentUser->full_name ?: 'Faculty');
            $sigSection = $request->input('signature_section') ?: ($currentUser->section ?: '');
            $sigSchool  = $request->input('signature_school') ?: 'Southern Isabela Academy';

            $footer = "\n- {$sigTeacher}";
            if (!empty($sigSection)) {
                $footer .= " ({$sigSection})";
            }
            $footer .= "\n{$sigSchool}";

            if (!str_ends_with($finalMessage, $footer)) {
                $finalMessage .= $footer;
            }
        }

        $recipients = [];

        // 1. DISPATCH MODE: ALL PARENTS / ALL STUDENTS
        if (in_array($recipientMode, ['all_parents', 'all_students'], true)) {
            $studentsQuery = User::where('role_id', 3)->where('is_active', true);

            // If current user is a teacher, restrict to their sections
            if ((int)$currentUser->role_id === 2 && !empty($currentUser->section)) {
                $studentsQuery->where('section', $currentUser->section);
            }

            $activeStudents = $studentsQuery->get();

            foreach ($activeStudents as $student) {
                $contact = $student->parent_phone_number 
                    ?: $student->phone_number 
                    ?: $student->contact_person_contact;

                if (!empty($contact)) {
                    $recipients[] = [
                        'number'     => $contact,
                        'name'       => $student->full_name ?: "{$student->first_name} {$student->last_name}",
                        'student_id' => $student->id,
                    ];
                }
            }

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active students with contact numbers found.',
                ], 422);
            }
        }

        // 2. DISPATCH MODE: SELECTED STUDENTS
        elseif ($recipientMode === 'selected_students') {
            $studentIds = $request->input('student_ids', []);
            if (empty($studentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one student recipient.',
                ], 422);
            }

            $selectedStudents = User::where('role_id', 3)->whereIn('id', $studentIds)->get();

            foreach ($selectedStudents as $student) {
                $contact = $student->parent_phone_number 
                    ?: $student->phone_number 
                    ?: $student->contact_person_contact;

                if (!empty($contact)) {
                    $recipients[] = [
                        'number'     => $contact,
                        'name'       => $student->full_name ?: "{$student->first_name} {$student->last_name}",
                        'student_id' => $student->id,
                    ];
                }
            }

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'None of the selected students have a valid contact number registered.',
                ], 422);
            }
        }

        // 3. DISPATCH MODE: SPECIFIC STUDENT
        elseif ($recipientMode === 'specific_student') {
            $studentId = $request->input('student_id');
            $student = User::where('role_id', 3)->find($studentId);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected student could not be found.',
                ], 404);
            }

            $contact = $student->parent_phone_number 
                ?: $student->phone_number 
                ?: $student->contact_person_contact;

            if (empty($contact)) {
                return response()->json([
                    'success' => false,
                    'message' => "Student {$student->first_name} {$student->last_name} has no contact number registered.",
                ], 422);
            }

            $recipients[] = [
                'number'     => $contact,
                'name'       => $student->full_name ?: "{$student->first_name} {$student->last_name}",
                'student_id' => $student->id,
            ];
        }

        // 4. DISPATCH MODE: ALL TEACHERS (Admin only)
        elseif ($recipientMode === 'all_teachers') {
            if ((int)$currentUser->role_id !== 1) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }

            $teachers = User::where('role_id', 2)->where('is_active', true)->get();
            foreach ($teachers as $teacher) {
                if (!empty($teacher->phone_number)) {
                    $recipients[] = [
                        'number'     => $teacher->phone_number,
                        'name'       => $teacher->full_name ?: "{$teacher->first_name} {$teacher->last_name}",
                        'student_id' => null,
                    ];
                }
            }

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active teachers with contact numbers found.',
                ], 422);
            }
        }

        // 5. DISPATCH MODE: SPECIFIC TEACHER (Admin only)
        elseif ($recipientMode === 'specific_teacher') {
            if ((int)$currentUser->role_id !== 1) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }

            $teacher = User::where('role_id', 2)->find($request->input('teacher_id'));
            if (!$teacher || empty($teacher->phone_number)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found or missing contact number.',
                ], 422);
            }

            $recipients[] = [
                'number'     => $teacher->phone_number,
                'name'       => $teacher->full_name ?: "{$teacher->first_name} {$teacher->last_name}",
                'student_id' => null,
            ];
        }

        // 6. DISPATCH MODE: CUSTOM NUMBER (Admin only)
        elseif ($recipientMode === 'custom') {
            if ((int)$currentUser->role_id !== 1) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }

            $customNumber = $request->input('custom_number');
            if (empty($customNumber)) {
                return response()->json(['success' => false, 'message' => 'Please provide a valid custom contact number.'], 422);
            }

            $recipients[] = [
                'number'     => $customNumber,
                'name'       => $request->input('custom_name') ?: 'Custom Recipient',
                'student_id' => null,
            ];
        }

        // Dispatch Batch or Single
        $meta = [
            'sender_user_id' => $currentUser->id,
            'dispatch_mode'  => $recipientMode,
            'sender_id'      => 'Scan-to-notify',
        ];

        $summary = $this->smsService->sendBatchMessages($recipients, $finalMessage, $meta);

        $hasSent = $summary['sent'] > 0;
        $pendingCount = collect($summary['results'])
            ->filter(fn (array $result): bool => ($result['record']->status ?? null) === 'pending')
            ->count();
        $statusMsg = "Accepted: " . ($summary['sent'] - $pendingCount) . " sent, {$pendingCount} pending, {$summary['failed']} failed";
        if ($summary['skipped'] > 0) {
            $statusMsg .= " ({$summary['skipped']} rate-limited/skipped)";
        }
        if (!empty($summary['skipped_reasons'])) {
            $reasonText = collect($summary['skipped_reasons'])
                ->map(fn (array $item): string => $item['recipient'] . ': ' . $item['reason'])
                ->implode('; ');
            $statusMsg .= " Skipped details: {$reasonText}";
        }

        return response()->json([
            'success' => $hasSent,
            'message' => $statusMsg,
            'summary' => $summary,
        ], $hasSent ? 200 : 422);
    }

    /**
     * Resend all messages queued by the gateway today.
     */
    public function resendPendingToday(Request $request)
    {
        $pendingMessages = OutboundMessage::query()
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'pending')
            ->get();

        if ($pendingMessages->isEmpty()) {
            return back()->with('success', 'There are no pending SMS messages from today to resend.');
        }

        $summary = [
            'total' => $pendingMessages->count(),
            'sent' => 0,
            'pending' => 0,
            'failed' => 0,
        ];

        foreach ($pendingMessages as $pendingMessage) {
            $result = $this->smsService->sendSingleMessage(
                $pendingMessage->contact_number,
                $pendingMessage->message,
                [
                    'sender_user_id' => auth()->id(),
                    'student_id' => $pendingMessage->student_id,
                    'recipient_name' => $pendingMessage->recipient_name,
                    'sender_id' => $pendingMessage->sender_id,
                    'dispatch_mode' => 'resend_pending_today',
                    'bypass_rate_limit' => true,
                ]
            );

            $replacement = $result['record'];
            $pendingMessage->update([
                'gateway_message_id' => $replacement->gateway_message_id,
                'status' => $replacement->status,
                'api_response' => $replacement->api_response,
                'error_message' => $replacement->error_message,
                'last_sent_at' => $replacement->last_sent_at,
            ]);
            $replacement->delete();

            $status = $pendingMessage->status ?? 'failed';
            if ($status === 'pending') {
                $summary['pending']++;
            } elseif ($status === 'delivered' || $status === 'sent') {
                $summary['sent']++;
            } else {
                $summary['failed']++;
            }
        }

        Log::info('Pending SMS resend batch completed.', $summary);

        return back()->with(
            'success',
            "Pending SMS resend completed: {$summary['sent']} accepted, {$summary['pending']} still pending, {$summary['failed']} failed."
        );
    }

    /**
     * History Logs Endpoint (JSON API).
     * Route: GET /outbound-messages
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = OutboundMessage::with(['sender', 'student']);

        // Teacher scoping
        if ((int)($user->role_id ?? 0) === 2) {
            $query->where('sender_user_id', $user->id);
        }

        $this->applyFilters($query, $request);

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;
        $messages = $query->latest('created_at')->paginate($perPage);

        return response()->json($messages);
    }

    /**
     * View Details Modal Data.
     * Route: GET /outbound-messages/{id}
     */
    public function show($id): JsonResponse
    {
        $message = OutboundMessage::with(['sender', 'student'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => $message,
            'date_formatted' => $message->created_at->format('M d, Y h:i:s A'),
            'last_sent_formatted' => $message->last_sent_at ? $message->last_sent_at->format('M d, Y h:i:s A') : 'N/A',
            'sender_name' => $message->sender_display_name,
        ]);
    }

    /**
     * Check Rate Limit status for a phone number.
     * Route: GET /sms/check-rate
     */
    public function checkRateLimit(Request $request): JsonResponse
    {
        $phone = $request->query('phone');
        $normalized = $this->smsService->normalizePhoneNumber($phone);

        if (!$normalized) {
            return response()->json([
                'valid'        => false,
                'rate_limited' => false,
                'message'      => 'Invalid Philippine phone number',
            ]);
        }

        $isLimited = $this->smsService->isRateLimited($normalized);

        return response()->json([
            'valid'        => true,
            'normalized'   => $normalized,
            'rate_limited' => $isLimited,
            'message'      => $isLimited ? 'Recipient is on cooldown (60s).' : 'Ready to send.',
        ]);
    }

    /**
     * Public hook to trigger attendance notifications.
     */
    public function sendAttendanceNotification(User $student, string $status, ?string $time = null): ?OutboundMessage
    {
        return $this->smsService->sendAttendanceNotification($student, $status, $time);
    }

    /**
     * Helper to apply common search and date filters.
     */
    protected function applyFilters($query, Request $request): void
    {
        // 1. Recipient / Student filter
        if ($request->filled('recipient')) {
            $term = $request->input('recipient');
            $query->where(function ($q) use ($term) {
                $q->where('recipient_name', 'like', "%{$term}%")
                  ->orWhere('contact_number', 'like', "%{$term}%")
                  ->orWhereHas('student', function ($sq) use ($term) {
                      $sq->where('first_name', 'like', "%{$term}%")
                         ->orWhere('last_name', 'like', "%{$term}%")
                         ->orWhere('id_number', 'like', "%{$term}%");
                  });
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        // 2. Status filter
        if ($request->filled('status')) {
            $statusVal = strtolower(trim($request->input('status')));
            $query->where('status', $statusVal);
        }

        // 3. Date filters
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }
    }
}
