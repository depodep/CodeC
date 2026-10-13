<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NfcCard;
use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class NfcAttendanceController extends Controller
{
    public function attendanceStart()
    {
        if (session('attendance_session.expires_at', 0) >= now()->timestamp) {
            return redirect()->route('attendance.live');
        }

        return view('attendance.start');
    }

    public function startAttendance(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = Auth::user();
        $stored = (string) $user->password;
        $info = password_get_info($stored);
        $valid = ($info['algo'] ?? 0) !== 0
            ? Hash::check($request->password, $stored)
            : hash_equals($stored, $request->password);

        if (!$valid) {
            return back()->withErrors(['password' => 'The attendance password is incorrect.']);
        }

        $request->session()->put('attendance_session', [
            'started_at' => now()->timestamp,
            'expires_at' => now()->addHours(8)->timestamp,
            'operator_id' => $user->id,
        ]);

        return redirect()->route('attendance.live');
    }

    public function endAttendance(Request $request)
    {
        $request->session()->forget('attendance_session');
        return redirect()->route('attendance.start');
    }

    public function scanPage()
    {
        if (session('attendance_session.expires_at', 0) < now()->timestamp) {
            return redirect()->route('attendance.start');
        }
        $user = Auth::user();
        $isAdmin = $this->isAdmin($user);
        $allSchedules = ClassSchedule::with(['academicSection', 'subjectRecord'])
            ->when(!$isAdmin, fn ($query) => $query->where('teacher_id', $user->id))
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();
        $currentSchedules = $allSchedules
            ->filter(fn (ClassSchedule $schedule) => $this->scheduleIsOpen($schedule, false))
            ->values();
        $schedules = $isAdmin ? $allSchedules : $currentSchedules;
        $currentSchedule = $currentSchedules->first();
        $advisorySections = \App\Models\AcademicSection::with('students')
            ->when(!$isAdmin, fn ($query) => $query->where('advisor_id', $user->id))
            ->orderBy('section_name')
            ->get();
        $defaultAttendanceType = $isAdmin
            ? ($currentSchedule ? 'subject' : ($advisorySections->isNotEmpty() ? 'advisory' : 'subject'))
            : ($advisorySections->isNotEmpty() ? 'advisory' : 'subject');
        $defaultSection = $advisorySections->first();

        return view('teacher.kiosk', compact(
            'schedules',
            'currentSchedule',
            'advisorySections',
            'defaultAttendanceType',
            'defaultSection',
            'isAdmin'
        ));
    }

    /**
     * Display the Teacher Attendance Monitor Dashboard with Real Data
     */
public function attendanceIndex(Request $request)
    {
        $teacher = Auth::user(); // Uses the default authenticated user
        $today = Carbon::today();

        // Base Query for Attendance Logs joined with Users
        $query = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.strand', 'users.photo');

        // 1. Date Range Filter (Defaults to today if not specified)
        $dateFrom = $request->input('date_from', $today->toDateString());
        $dateTo   = $request->input('date_to', $today->toDateString());
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('attendance_logs.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }

        // 2. Status Filter (ON-TIME / LATE)
        if ($request->filled('status')) {
            $query->where('attendance_logs.status', $request->status);
        }

        // 3. Search Filter (Student Name or ID Number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('users.first_name', 'like', "%{$search}%")
                  ->orWhere('users.last_name', 'like', "%{$search}%")
                  ->orWhere('users.id_number', 'like', "%{$search}%");
            });
        }

        // Calculate Metrics for the Filtered Period
        $totalScanned = (clone $query)->count();
        $presentCount = (clone $query)->where('attendance_logs.status', 'ON-TIME')->count();
        $lateCount    = (clone $query)->where('attendance_logs.status', 'LATE')->count();
        
        // Paginated Results for the Table Feed
        $recentLogs = $query->orderBy('attendance_logs.created_at', 'desc')->paginate(15)->withQueryString();

        return view('teacher.attendance', compact('teacher', 'totalScanned', 'presentCount', 'lateCount', 'recentLogs', 'dateFrom', 'dateTo'));
    }

    /**
     * Export Filtered Attendance Records to CSV
     */
    public function exportCsv(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->input('date_to', Carbon::today()->toDateString());

        $logs = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->whereBetween('attendance_logs.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('users.id_number', 'users.first_name', 'users.last_name', 'users.strand', 'attendance_logs.status', 'attendance_logs.created_at', 'attendance_logs.time_out')
            ->get();

        $filename = "SIATRACK_Attendance_{$dateFrom}_to_{$dateTo}.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID Number', 'First Name', 'Last Name', 'Strand', 'Status', 'Time In', 'Time Out']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id_number,
                    $log->first_name,
                    $log->last_name,
                    $log->strand,
                    $log->status,
                    $log->created_at,
                    $log->time_out ?? 'N/A'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display the Live NFC Kiosk View
     */
    public function kioskView()
    {
        return view('teacher.kiosk');
    }

    /**
     * Handles NFC Tap from hardware reader or Python bridge in Real-Time
     */
    public function handleTap(Request $request) {
        $request->validate([
            'tag_id' => ['required', 'string', 'max:100'],
            'attendance_type' => ['required', 'in:advisory,subject'],
            'schedule_id' => ['nullable', 'integer', 'exists:class_schedules,id'],
            'advisory_section_id' => ['nullable', 'integer', 'exists:academic_sections,id'],
        ]);

        $operator = Auth::user();
        if (session('attendance_session.operator_id') !== $operator->id ||
            session('attendance_session.expires_at', 0) < now()->timestamp) {
            return response()->json(['success' => false, 'message' => 'Attendance session expired. Please re-authenticate.'], 401);
        }
        $attendanceType = $request->string('attendance_type')->toString();
        if ($attendanceType === 'subject' && !$request->filled('schedule_id')) {
            return response()->json(['success' => false, 'message' => 'Select a subject schedule first.'], 422);
        }
        if ($attendanceType === 'advisory' && !$request->filled('advisory_section_id')) {
            return response()->json(['success' => false, 'message' => 'Select an advisory section first.'], 422);
        }
        $schedule = $attendanceType === 'subject'
            ? ClassSchedule::with(['academicSection', 'subjectRecord'])->findOrFail($request->integer('schedule_id'))
            : null;
        $section = $attendanceType === 'advisory'
            ? \App\Models\AcademicSection::findOrFail($request->integer('advisory_section_id'))
            : null;
        if ($attendanceType === 'subject' && !$this->isAdmin($operator) && (int) $schedule->teacher_id !== (int) $operator->id) {
            return response()->json(['success' => false, 'message' => 'You may only record attendance for your assigned subject.'], 403);
        }
        if ($attendanceType === 'advisory' && !$this->isAdmin($operator) && (int) $section->advisor_id !== (int) $operator->id) {
            return response()->json(['success' => false, 'message' => 'You may only record attendance for your assigned advisory section.'], 403);
        }

        if($request->has('uid')) {
            Cache::put('latest_nfc_uid', $request->uid, now()->addSeconds(10));
        }

        $tagId = strtoupper(trim((string) ($request->input('tag_id') ?? $request->query('tag_id'))));

        if (empty($tagId)) {
            return response()->json([
                'success' => false,
                'message' => 'No NFC Tag UID detected.',
            ], 400);
        }

        $card = NfcCard::where('tag_id', $tagId)->where('status', 'active')->first();
        $user = $card?->user;

        if (!$user) {
            return response()->json([
                'success'       => false,
                'is_registered' => false,
                'tag_id'        => $tagId,
                'message'       => "Unregistered card (UID: {$tagId}). Please assign it to a student.",
            ], 404);
        }

        $strandValue = $user->strand ?? $user->track ?? null;
        $trackName   = match (strtoupper((string) $strandValue)) {
            'STEM', 'ABM', 'HUMSS', 'GAS', 'TVL', 'SPORTS', 'ARTS'
                => 'Academic Track',
            default => $strandValue ?: 'General Track',
        };
        $sectionName = !empty($user->section) ? $user->section : 'Unassigned Section';
        $gradeLevelText = !empty($user->grade_level) ? 'Grade ' . $user->grade_level : 'Grade 11';
        $studentPayload = [
            'id' => $user->id,
            'name' => "{$user->first_name} {$user->last_name}",
            'id_number' => $user->id_number ?? 'N/A',
            'grade_level' => $gradeLevelText,
            'track' => $trackName,
            'strand' => $user->strand ?? 'STEM',
            'section' => $sectionName,
            'avatar' => !empty($user->photo) ? asset('storage/' . $user->photo) : null,
        ];

        $studentInSection = $attendanceType === 'advisory'
            ? $user->sections()->whereKey($section->id)->exists()
            : ($schedule->section_id
                ? $user->sections()->whereKey($schedule->section_id)->exists()
                : strcasecmp(trim((string) $user->section), trim((string) ($schedule->section ?: $schedule->academicSection?->section_name))) === 0);
        if (!$studentInSection) {
            $selectedSectionName = $attendanceType === 'advisory'
                ? $section->section_name
                : ($schedule->academicSection?->section_name ?? $schedule->section ?? 'Selected class');

            return response()->json([
                'success' => false,
                'error_code' => 'STUDENT_NOT_IN_CLASS',
                'is_registered' => true,
                'student' => $studentPayload,
                'selected_section' => $selectedSectionName,
                'message' => "{$studentPayload['name']} is not assigned to {$selectedSectionName}. Attendance was not recorded.",
            ], 422);
        }

        $today   = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');
        $existingLog = DB::table('attendance_logs')
            ->where('user_id', $user->id)
            ->where('attendance_type', $attendanceType)
            ->when($attendanceType === 'subject', fn ($query) => $query->where('class_schedule_id', $schedule->id))
            ->when($attendanceType === 'advisory', fn ($query) => $query->where('advisory_section_id', $section->id))
            ->whereDate('attendance_date', $today)
            ->first();
        if ($attendanceType === 'subject' && !$this->isAdmin($operator) && !$this->scheduleIsOpen($schedule, false)) {
            $selectedSectionName = $schedule->academicSection?->section_name ?? $schedule->section ?? 'Selected class';
            return response()->json([
                'success' => false,
                'error_code' => 'NOT_IN_SCHEDULE',
                'is_registered' => true,
                'student' => $studentPayload,
                'selected_section' => $selectedSectionName,
                'message' => "{$studentPayload['name']} is not in the active schedule window. Attendance was not recorded.",
            ], 422);
        }
        if ($attendanceType === 'subject' && !$this->scheduleIsOpen($schedule, (bool) $existingLog)) {
            $window = $existingLog ? 'the scheduled end time' : 'the scheduled start time';
            return response()->json([
                'success' => false,
                'error_code' => 'NOT_IN_SCHEDULE',
                'is_registered' => true,
                'student' => $studentPayload,
                'selected_section' => $schedule->academicSection?->section_name ?? $schedule->section ?? 'Selected class',
                'message' => "{$studentPayload['name']} is not in the active schedule window. Scan within 30 minutes before or after {$window}.",
            ], 422);
        }
        $status = $attendanceType === 'advisory'
            ? 'ON-TIME'
            : (Carbon::now()->gt(Carbon::parse($today . ' ' . $schedule->start_time)) ? 'LATE' : 'ON-TIME');

        if (!$existingLog) {
            $insertData = [
                'user_id' => $user->id,
                'class_schedule_id' => $attendanceType === 'subject' ? $schedule->id : null,
                'nfc_card_id' => $card->id,
                'attendance_type' => $attendanceType,
                'advisory_section_id' => $attendanceType === 'advisory' ? $section->id : null,
                'recorded_by' => $operator->id,
                'attendance_date' => $today,
                'time_in' => $nowTime,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasTable('academic_periods')) {
                $insertData['academic_period_id'] = DB::table('academic_periods')->where('is_active', 1)->value('id');
            }

            DB::table('attendance_logs')->insert($insertData);

            $actionType    = 'TIME IN';
            $displayStatus = $status;
            $message       = "TIME IN recorded for {$user->first_name} {$user->last_name} ({$displayStatus})";

            // Hook Attendance Auto-Trigger SMS
            try {
                app(\App\Services\AndroidSmsGatewayService::class)->sendAttendanceNotification($user, 'TIME IN', Carbon::now()->format('g:i A'));
            } catch (\Throwable $smsEx) {
                \Illuminate\Support\Facades\Log::warning("Attendance SMS Notification failed: " . $smsEx->getMessage());
            }
        } else {
            if ($attendanceType === 'subject') {
                return response()->json([
                    'success' => true,
                    'is_registered' => true,
                    'action' => 'ALREADY_LOGGED',
                    'status' => $existingLog->status ?? 'ON-TIME',
                    'time' => Carbon::parse($existingLog->time_in)->format('g:i A'),
                    'time_in' => $existingLog->time_in
                        ? Carbon::parse($existingLog->time_in)->format('g:i A')
                        : null,
                    'time_out' => $existingLog->time_out
                        ? Carbon::parse($existingLog->time_out)->format('g:i A')
                        : null,
                    'student' => [
                        'id' => $user->id,
                        'name' => "{$user->first_name} {$user->last_name}",
                        'id_number' => $user->id_number ?? 'N/A',
                        'grade_level' => $gradeLevelText,
                        'track' => $trackName,
                        'strand' => $user->strand ?? 'STEM',
                        'section' => $sectionName,
                        'avatar' => !empty($user->photo) ? asset('storage/' . $user->photo) : null,
                    ],
                    'message' => 'Subject attendance already recorded.',
                ]);
            }
            if (!empty($existingLog->time_out)) {
                return response()->json([
                    'success'       => true,
                    'is_registered' => true,
                    'action'        => 'ALREADY_COMPLETED',
                    'status'        => $existingLog->status ?? 'ON-TIME',
                    'time'          => Carbon::parse($existingLog->time_out)->format('g:i A'),
                    'time_in'       => $existingLog->time_in
                        ? Carbon::parse($existingLog->time_in)->format('g:i A')
                        : null,
                    'time_out'      => Carbon::parse($existingLog->time_out)->format('g:i A'),
                    'student'       => $studentPayload,
                    'message'       => "{$studentPayload['name']} already completed attendance. Time out was recorded at "
                        . Carbon::parse($existingLog->time_out)->format('g:i A') . '.',
                ]);
            }
            $lastActionTime = Carbon::parse($existingLog->updated_at ?? $existingLog->created_at);

            // 60-Second Debounce: ignore accidental double taps while holding card on reader
            if ($lastActionTime->diffInSeconds(now()) < 60 && empty($existingLog->time_out)) {
                return response()->json([
                    'success'       => true,
                    'is_registered' => true,
                    'action'        => 'ALREADY_LOGGED',
                    'status'        => $existingLog->status ?? 'ON-TIME',
                    'time'          => Carbon::parse($existingLog->time_in ?? now())->format('g:i A'),
                    'student'       => [
                        'id'          => $user->id,
                        'name'        => "{$user->first_name} {$user->last_name}",
                        'id_number'   => $user->id_number ?? 'N/A',
                        'grade_level' => $gradeLevelText,
                        'track'       => $trackName,
                        'strand'      => $user->strand ?? 'STEM',
                        'section'     => $sectionName,
                        'avatar'      => !empty($user->photo) ? asset('storage/' . $user->photo) : null,
                    ],
                    'message'       => "{$user->first_name} has already timed in at " . Carbon::parse($existingLog->time_in ?? now())->format('g:i A'),
                ]);
            }

            $updateData = ['updated_at' => now(), 'time_out' => $nowTime];

            DB::table('attendance_logs')->where('id', $existingLog->id)->update($updateData);

            $actionType    = 'TIME OUT';
            $displayStatus = $existingLog->status ?? 'ON-TIME';
            $message       = "TIME OUT recorded for {$user->first_name} {$user->last_name}";

            // Hook Attendance Auto-Trigger SMS
            try {
                app(\App\Services\AndroidSmsGatewayService::class)->sendAttendanceNotification($user, 'TIME OUT', Carbon::now()->format('g:i A'));
            } catch (\Throwable $smsEx) {
                \Illuminate\Support\Facades\Log::warning("Attendance SMS Notification failed: " . $smsEx->getMessage());
            }
        }

        return response()->json([
            'success'       => true,
            'is_registered' => true,
            'action'        => $actionType,
            'status'        => $displayStatus,
            'time'          => Carbon::now()->format('g:i A'),
            'student'       => [
                'id'          => $user->id,
                'name'        => "{$user->first_name} {$user->last_name}",
                'id_number'   => $user->id_number ?? 'N/A',
                'grade_level' => $gradeLevelText,
                'track'       => $trackName,
                'strand'      => $user->strand ?? 'STEM',
                'section'     => $sectionName,
                'avatar'      => !empty($user->photo) ? asset('storage/' . $user->photo) : null,
            ],
            'message'       => $message,
        ]);
    }

    /**
     * Alias for storeTap endpoint
     */
    public function storeTap(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Authentication is required to record attendance.'], 401);
        }
        return $this->handleTap($request);
    }

    private function isAdmin(User $user): bool
    {
        return (int) $user->role_id === 1 || strtolower((string) (is_object($user->role) ? $user->role->name : $user->role)) === 'admin';
    }

    private function scheduleIsOpen(ClassSchedule $schedule, bool $timeOut): bool
    {
        $now = Carbon::now();
        if (!$this->scheduleMatchesToday($schedule, $now)) {
            return false;
        }

        $boundary = $timeOut ? $schedule->end_time : $schedule->start_time;
        $start = Carbon::parse($now->toDateString() . ' ' . $boundary)->subMinutes(30);
        $end = Carbon::parse($now->toDateString() . ' ' . $boundary)->addMinutes(30);
        return $now->betweenIncluded($start, $end);
    }

    private function scheduleMatchesToday(ClassSchedule $schedule, Carbon $now): bool
    {
        $day = strtolower(trim((string) $schedule->day));
        return $day === '' || in_array($day, [
            strtolower($now->format('l')),
            strtolower($now->format('D')),
            (string) $now->dayOfWeekIso,
        ], true);
    }
}