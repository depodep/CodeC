<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    /**
     * Attendance Log Index — date-filtered list of all records.
     */
    public function index(Request $request)
    {
        $hasAttendance = Schema::hasTable('attendance_logs');
        $attCols       = $hasAttendance ? Schema::getColumnListing('attendance_logs') : [];

        $userForeignKey = in_array('student_id', $attCols) ? 'student_id'
            : (in_array('user_id', $attCols) ? 'user_id' : null);
        $dateCol = in_array('attendance_date', $attCols) ? 'attendance_date'
            : (in_array('date', $attCols) ? 'date' : 'created_at');

        $logs     = collect();
        $sections = collect();
        $total    = 0;
        $present  = 0;
        $late     = 0;
        $absent   = 0;

        if ($hasAttendance && $userForeignKey) {
            $query = DB::table('attendance_logs')
                ->join('users', "attendance_logs.{$userForeignKey}", '=', 'users.id')
                ->select(
                    'attendance_logs.*',
                    'users.first_name', 'users.last_name',
                    'users.id_number', 'users.section', 'users.strand', 'users.grade_level'
                );

            // Filters
            if ($request->filled('date')) {
                $query->whereDate($dateCol, $request->date);
            } else {
                $query->whereDate($dateCol, Carbon::today());
            }

            if ($request->filled('section')) {
                $query->where('users.section', $request->section);
            }

            if ($request->filled('status') && in_array('status', $attCols)) {
                $query->where('attendance_logs.status', strtoupper($request->status));
            }

            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('users.first_name', 'like', $search)
                      ->orWhere('users.last_name', 'like', $search)
                      ->orWhere('users.id_number', 'like', $search);
                });
            }

            $logs = $query->latest('attendance_logs.created_at')->paginate(30)->withQueryString();

            $sections = DB::table('users')
                ->where('role_id', 3)
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();

            // Summary counts for today
            $todayBase = DB::table('attendance_logs')
                ->join('users', "attendance_logs.{$userForeignKey}", '=', 'users.id')
                ->whereDate($dateCol, $request->filled('date') ? $request->date : Carbon::today());

            $total   = (clone $todayBase)->count();
            $present = in_array('status', $attCols)
                ? (clone $todayBase)->whereIn('attendance_logs.status', ['ON-TIME', 'on-time', 'PRESENT'])->count()
                : 0;
            $late    = in_array('status', $attCols)
                ? (clone $todayBase)->whereIn('attendance_logs.status', ['LATE', 'late'])->count()
                : 0;
            $absent  = max(0, $total - $present - $late);
        }

        return view('admin.attendance.index', compact(
            'logs', 'sections', 'total', 'present', 'late', 'absent'
        ));
    }

    /**
     * Live Tap Feed — auto-refreshes every few seconds.
     */
    public function live(Request $request)
    {
        $reportTitle   = 'Live Attendance Tap Feed';
        $hasAttendance = Schema::hasTable('attendance_logs');
        $recentLogs    = collect();

        if ($hasAttendance) {
            $attCols        = Schema::getColumnListing('attendance_logs');
            $userForeignKey = in_array('student_id', $attCols) ? 'student_id'
                : (in_array('user_id', $attCols) ? 'user_id' : null);

            $query = DB::table('attendance_logs');
            if ($userForeignKey) {
                $query->join('users', "attendance_logs.{$userForeignKey}", '=', 'users.id')
                      ->select('attendance_logs.*', 'users.first_name', 'users.last_name',
                               'users.id_number', 'users.section', 'users.strand');
            }

            $recentLogs = $query->latest('attendance_logs.created_at')->limit(50)->get();
        }

        return view('admin.attendance.live', compact('reportTitle', 'recentLogs'));
    }

    /**
     * Attendance Override — manually correct a student's time-in/out or status.
     */
    public function override(Request $request)
    {
        $hasAttendance = Schema::hasTable('attendance_logs');
        $attCols       = $hasAttendance ? Schema::getColumnListing('attendance_logs') : [];
        $userForeignKey = in_array('user_id', $attCols) ? 'user_id'
            : (in_array('student_id', $attCols) ? 'student_id' : null);
        $dateCol = in_array('attendance_date', $attCols) ? 'attendance_date'
            : (in_array('date', $attCols) ? 'date' : 'created_at');

        $logs     = collect();
        $sections = collect();
        $subjects = collect();
        $advisorySections = collect();
        $subjectSections = collect();
        $attendanceType = $request->input('attendance_type', 'advisory');
        $sort = $request->input('sort', 'student');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($hasAttendance && $userForeignKey && in_array($attendanceType, ['subject', 'advisory'], true)) {
            $date = $request->input('date', now()->toDateString());
            $hasSubjectRows = Schema::hasTable('subjects') && DB::table('subjects')->exists();
            $subjectScheduleIds = collect();
            if ($attendanceType === 'subject' && $request->filled('subject_id')) {
                $selectedSubjectId = $request->integer('subject_id');
                if ($hasSubjectRows) {
                    $subjectScheduleIds = DB::table('class_schedules')
                        ->where('subject_id', $selectedSubjectId)
                        ->pluck('id');
                } else {
                    $selectedSchedule = DB::table('class_schedules')
                        ->where('id', $selectedSubjectId)
                        ->first(['subject_name', 'subject_code']);
                    if ($selectedSchedule) {
                        $subjectScheduleIds = DB::table('class_schedules')
                            ->where('subject_name', $selectedSchedule->subject_name)
                            ->where('subject_code', $selectedSchedule->subject_code)
                            ->pluck('id');
                    }
                }
            }
            if ($attendanceType === 'subject' && $request->filled('subject_id')) {
                $subjectSections = DB::table('class_schedules')
                    ->leftJoin('academic_sections', 'class_schedules.section_id', '=', 'academic_sections.id')
                    ->when($hasSubjectRows,
                        fn ($query) => $query->where('class_schedules.subject_id', $request->integer('subject_id')),
                        fn ($query) => $query->whereIn('class_schedules.id', $subjectScheduleIds))
                    ->where(function ($query) {
                        $query->whereNotNull('academic_sections.section_name')
                            ->orWhereNotNull('class_schedules.section');
                    })
                    ->selectRaw('COALESCE(academic_sections.section_name, class_schedules.section) as section_name')
                    ->distinct()
                    ->orderBy('section_name')
                    ->pluck('section_name');
            }
            $advisorySectionId = $attendanceType === 'advisory' && $request->filled('advisory_section_id')
                ? $request->integer('advisory_section_id')
                : null;

            $query = DB::table('users')
                ->where('users.role_id', 3)
                ->leftJoin('attendance_logs', function ($join) use ($dateCol, $userForeignKey, $date, $attendanceType, $subjectScheduleIds, $advisorySectionId) {
                    $join->on("attendance_logs.{$userForeignKey}", '=', 'users.id')
                        ->whereDate("attendance_logs.{$dateCol}", $date)
                        ->where('attendance_logs.attendance_type', $attendanceType);
                    if ($attendanceType === 'subject' && $subjectScheduleIds->isNotEmpty()) {
                        $join->whereIn('attendance_logs.class_schedule_id', $subjectScheduleIds);
                    }
                    if ($attendanceType === 'advisory' && $advisorySectionId) {
                        $join->where('attendance_logs.advisory_section_id', $advisorySectionId);
                    }
                });

            if ($attendanceType === 'subject') {
                $query->leftJoin('class_schedules', 'attendance_logs.class_schedule_id', '=', 'class_schedules.id')
                    ->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id');
                if ($subjectSections->isNotEmpty()) {
                    $query->whereIn('users.section', $subjectSections);
                }
            } else {
                if ($advisorySectionId) {
                    $selectedAdvisorySection = DB::table('academic_sections')
                        ->where('id', $advisorySectionId)
                        ->first();
                    if ($selectedAdvisorySection) {
                        $query->where('users.section', $selectedAdvisorySection->section_name);
                    }
                }
            }

            if ($request->filled('section')) {
                $query->where('users.section', $request->section);
            }

            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('users.first_name', 'like', $search)
                      ->orWhere('users.last_name', 'like', $search)
                      ->orWhere('users.id_number', 'like', $search);
                });
            }

            $selectColumns = [
                'users.id as student_id',
                'users.first_name', 'users.last_name',
                'users.id_number', 'users.section', 'users.grade_level',
                'attendance_logs.id as attendance_log_id',
                'attendance_logs.attendance_date',
                'attendance_logs.time_in', 'attendance_logs.time_out',
                'attendance_logs.status', 'attendance_logs.attendance_type',
                'attendance_logs.class_schedule_id',
                'attendance_logs.advisory_section_id',
            ];
            if ($attendanceType === 'subject') {
                $selectColumns[] = 'class_schedules.subject_name';
                $selectColumns[] = 'class_schedules.subject_code';
            }

            $sortColumns = [
                'student' => ['users.last_name', 'users.first_name'],
                'section' => ['users.section', 'users.last_name'],
                'date' => ["attendance_logs.{$dateCol}", 'users.last_name'],
                'time_in' => ['attendance_logs.time_in', 'users.last_name'],
                'time_out' => ['attendance_logs.time_out', 'users.last_name'],
                'status' => ['attendance_logs.status', 'users.last_name'],
            ];
            $sortColumn = $sortColumns[$sort] ?? $sortColumns['student'];

            $query->select($selectColumns)
                ->orderBy($sortColumn[0], $direction)
                ->orderBy($sortColumn[1], 'asc');

            $perPage = min(100, max(1, $request->integer('per_page', 10)));
            $logs = $query->paginate($perPage)->withQueryString();

            $sections = $subjectSections->isNotEmpty()
                ? $subjectSections
                : DB::table('users')
                    ->where('role_id', 3)
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();

            $subjects = $hasSubjectRows
                ? DB::table('subjects')
                    ->select('subjects.id', 'subjects.code', 'subjects.name')
                    ->orderBy('subjects.name')
                    ->get()
                : DB::table('class_schedules')
                    ->whereNotNull('subject_name')
                    ->selectRaw('MIN(class_schedules.id) as id, class_schedules.subject_code as code, class_schedules.subject_name as name')
                    ->groupBy('class_schedules.subject_code', 'class_schedules.subject_name')
                    ->orderBy('name')
                    ->get();

            $advisorySections = DB::table('academic_sections')
                ->select('id', 'section_name', 'grade_level')
                ->orderBy('grade_level')
                ->orderBy('section_name')
                ->get();
        }

        return view('admin.attendance.override', compact(
            'logs', 'sections', 'subjects', 'advisorySections', 'attendanceType',
            'sort', 'direction'
        ));
    }

    /**
     * Apply a manual override to a specific attendance log.
     */
    public function overrideRecord(Request $request, int $id)
    {
        $request->validate([
            'time_in'         => 'nullable|date_format:H:i',
            'time_out'        => 'nullable|date_format:H:i',
            'status'          => 'required|in:ON-TIME,LATE,ABSENT',
            'override_reason' => 'required|string|max:500',
            'admin_password'  => 'required|string',
        ]);

        $admin = Auth::user();
        if (!$admin || !in_array((int) $admin->role_id, [1], true)) {
            abort(403, 'Only an administrator can override attendance records.');
        }

        $storedPassword = (string) $admin->password;
        $passwordInfo = password_get_info($storedPassword);
        $passwordValid = ($passwordInfo['algo'] ?? 0) !== 0
            ? Hash::check($request->admin_password, $storedPassword)
            : hash_equals($storedPassword, $request->admin_password);

        if (!$passwordValid) {
            return back()->withErrors([
                'admin_password' => 'The administrator password is incorrect.',
            ])->withInput();
        }

        $attCols = Schema::hasTable('attendance_logs') ? Schema::getColumnListing('attendance_logs') : [];
        $existingLog = DB::table('attendance_logs')->where('id', $id)->first();
        if (!$existingLog) {
            abort(404, 'Attendance record not found.');
        }

        $updateData = [];
        if (in_array('status', $attCols)) {
            $updateData['status'] = $request->status;
        }
        if (in_array('time_in', $attCols) && $request->filled('time_in')) {
            $updateData['time_in'] = $request->time_in . ':00';
        }
        if (in_array('time_out', $attCols) && $request->filled('time_out')) {
            $updateData['time_out'] = $request->time_out . ':00';
        }
        $updateData['updated_at'] = now();

        DB::table('attendance_logs')->where('id', $id)->update($updateData);

        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->insert([
                'user_id' => $admin->id,
                'user_name' => trim($admin->first_name . ' ' . $admin->last_name),
                'role' => 'Admin',
                'action' => 'Attendance Override',
                'module' => 'Attendance',
                'status' => 'Success',
                'description' => 'Attendance log #' . $id . ' overridden. Reason: ' . $request->override_reason,
                'ip_address' => $request->ip(),
                'device' => $request->userAgent(),
                'browser' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.attendance.record', $request->only([
            'date', 'section', 'search', 'attendance_type', 'subject_id', 'advisory_section_id',
            'sort', 'direction',
        ]))
            ->with('success', 'Attendance record has been overridden successfully.');
    }

    /**
     * CSV Export of attendance logs.
     */
    public function export(Request $request)
    {
        $hasAttendance = Schema::hasTable('attendance_logs');
        $attCols       = $hasAttendance ? Schema::getColumnListing('attendance_logs') : [];
        $userForeignKey = in_array('student_id', $attCols) ? 'student_id'
            : (in_array('user_id', $attCols) ? 'user_id' : null);
        $dateCol = in_array('attendance_date', $attCols) ? 'attendance_date'
            : (in_array('date', $attCols) ? 'date' : 'created_at');

        $rows = collect();

        if ($hasAttendance && $userForeignKey) {
            $query = DB::table('attendance_logs')
                ->join('users', "attendance_logs.{$userForeignKey}", '=', 'users.id')
                ->select(
                    'users.id_number', 'users.first_name', 'users.last_name',
                    'users.section', 'users.grade_level', 'users.strand',
                    "attendance_logs.{$dateCol} as att_date",
                    'attendance_logs.time_in', 'attendance_logs.time_out',
                    'attendance_logs.status'
                );

            if ($request->filled('date')) {
                $query->whereDate($dateCol, $request->date);
            }
            if ($request->filled('section')) {
                $query->where('users.section', $request->section);
            }

            $rows = $query->orderByDesc("attendance_logs.{$dateCol}")->get();
        }

        $filename = 'attendance_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID Number', 'First Name', 'Last Name', 'Section', 'Grade Level', 'Strand', 'Date', 'Time In', 'Time Out', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id_number   ?? '',
                    $row->first_name  ?? '',
                    $row->last_name   ?? '',
                    $row->section     ?? '',
                    $row->grade_level ?? '',
                    $row->strand      ?? '',
                    $row->att_date    ?? '',
                    $row->time_in     ?? '',
                    $row->time_out    ?? '',
                    $row->status      ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}