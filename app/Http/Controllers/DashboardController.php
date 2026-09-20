<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirect based on user role
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'teacher') {
            return redirect()->route('teacher.schedules');
        } elseif ($user->role === 'student') {
            return redirect()->route('student.dashboard');
        }

        // Fallback default
        return view('dashboard');
    }

    // =========================================================================
    // Student Analytics & Filtering Screen Action
    // =========================================================================
    public function studentAnalytics(Request $request)
    {
        $query = User::where('role_id', 3);

        // Filter by Strand / Track
        if ($request->filled('strand')) {
            $query->where('strand', $request->strand);
        }

        // Filter by Section
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        $students = $query->latest('id')->paginate(15)->withQueryString();

        // Bulletproof case-insensitive gender count matching text or numeric codes
        $maleCount = (clone $query)->where(function($q) {
            $q->where('gender', '1')
              ->orWhere('gender', 'LIKE', 'Male%')
              ->orWhere('gender', 'LIKE', 'male%')
              ->orWhere('gender', 'M')
              ->orWhere('gender', 'm');
        })->count();

        $femaleCount = (clone $query)->where(function($q) {
            $q->where('gender', '2')
              ->orWhere('gender', 'LIKE', 'Female%')
              ->orWhere('gender', 'LIKE', 'female%')
              ->orWhere('gender', 'F')
              ->orWhere('gender', 'f');
        })->count();

        // Live Database Section Populations (Respecting filters)
        $sectionPopulations = (clone $query)
            ->whereNotNull('section')
            ->select('section', DB::raw('count(*) as total'))
            ->groupBy('section')
            ->pluck('total', 'section')
            ->toArray();

        // Live Database Distinct Sections for Dropdown Options
        $sections = User::where('role_id', 3)->whereNotNull('section')->distinct()->pluck('section')->toArray();

        return view('admin.dashboard.analytics.total_students', compact(
            'students', 
            'maleCount', 
            'femaleCount', 
            'sectionPopulations',
            'sections'
        ));
    }

    // =========================================================================
    // Attendance Rate Analytics & Monitoring Screen Action
    // =========================================================================
    public function attendanceRate(Request $request)
    {
        $reportTitle = 'Attendance Rate Analytics & Monitoring';
        
        $activeSchoolYear = Schema::hasTable('settings') 
            ? DB::table('settings')->where('key', 'active_school_year')->value('value') 
            : '2027-2028';
        if (!$activeSchoolYear) $activeSchoolYear = '2027-2028';

        $schoolYears = ['2025-2026', '2026-2027', '2027-2028', '2028-2029'];

        $hasAttendance = Schema::hasTable('attendance_logs');
        $userCols = Schema::hasTable('users') ? Schema::getColumnListing('users') : [];
        $attCols = $hasAttendance ? Schema::getColumnListing('attendance_logs') : [];

        $userForeignKey = in_array('student_id', $attCols) ? 'student_id' : (in_array('user_id', $attCols) ? 'user_id' : null);
        $dateCol = in_array('attendance_date', $attCols) ? 'attendance_date' : (in_array('date', $attCols) ? 'date' : 'created_at');
        $statusCol = in_array('status', $attCols) ? 'status' : null;

        $query = DB::table('attendance_logs');
        if ($hasAttendance && $userForeignKey) {
            $query->join('users', 'attendance_logs.' . $userForeignKey, '=', 'users.id')
                  ->where('users.role_id', 3);

            if ($request->filled('school_year') && in_array('school_year', $attCols)) {
                $query->where('attendance_logs.school_year', $request->school_year);
            }
            if ($request->filled('grade_level') && in_array('grade_level', $userCols)) {
                $query->where('users.grade_level', $request->grade_level);
            }
            if ($request->filled('section') && in_array('section', $userCols)) {
                $query->where('users.section', $request->section);
            }
            if ($request->filled('gender') && in_array('gender', $userCols)) {
                $query->where('users.gender', $request->gender);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('attendance_logs.' . $dateCol, '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('attendance_logs.' . $dateCol, '<=', $request->date_to);
            }
        }

        $totalRecords = $hasAttendance ? (clone $query)->count() : 0;
        $presentCount = $hasAttendance && $statusCol ? (clone $query)->whereIn($statusCol, ['PRESENT', 'ON-TIME'])->count() : $totalRecords;
        $lateCount = $hasAttendance && $statusCol ? (clone $query)->where($statusCol, 'LATE')->count() : 0;
        $absentCount = $hasAttendance && $statusCol ? (clone $query)->where($statusCol, 'ABSENT')->count() : 0;
        $excusedCount = $hasAttendance && $statusCol ? (clone $query)->where($statusCol, 'EXCUSED')->count() : 0;

        $overallRate = $totalRecords > 0 ? round((($presentCount + $lateCount) / $totalRecords) * 100, 1) : 0;

        $trendData = collect();
        if ($hasAttendance) {
            $trendData = (clone $query)
                ->select(DB::raw('DATE(attendance_logs.' . $dateCol . ') as log_date'), DB::raw('count(*) as total'), DB::raw('sum(case when attendance_logs.' . ($statusCol ?? 'status') . ' in ("PRESENT", "ON-TIME", "LATE") then 1 else 0 end) as attended'))
                ->groupBy('log_date')
                ->orderBy('log_date', 'asc')
                ->get();
        }

        $sectionAttendance = [];
        if ($hasAttendance && $userForeignKey && in_array('section', $userCols)) {
            $sectionsData = (clone $query)
                ->select('users.section', DB::raw('count(*) as total'), DB::raw('sum(case when attendance_logs.' . ($statusCol ?? 'status') . ' in ("PRESENT", "ON-TIME", "LATE") then 1 else 0 end) as present'))
                ->whereNotNull('users.section')
                ->groupBy('users.section')
                ->get();
            foreach ($sectionsData as $sec) {
                $rate = $sec->total > 0 ? round(($sec->present / $sec->total) * 100, 1) : 0;
                $sectionAttendance[$sec->section] = $rate;
            }
        }

        $concernsStudents = collect();
        if ($hasAttendance && $userForeignKey) {
            $concernsStudents = DB::table('users')
                ->where('role_id', 3)
                ->when($request->filled('section'), fn($q) => $q->where('section', $request->section))
                ->when($request->filled('grade_level'), fn($q) => $q->where('grade_level', $request->grade_level))
                ->leftJoin('attendance_logs', 'users.id', '=', 'attendance_logs.' . $userForeignKey)
                ->select(
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.id_number',
                    'users.section',
                    DB::raw('count(attendance_logs.id) as total_logs'),
                    DB::raw('sum(case when attendance_logs.' . ($statusCol ?? 'status') . ' in ("PRESENT", "ON-TIME") then 1 else 0 end) as present_count'),
                    DB::raw('sum(case when attendance_logs.' . ($statusCol ?? 'status') . ' = "LATE" then 1 else 0 end) as late_count'),
                    DB::raw('sum(case when attendance_logs.' . ($statusCol ?? 'status') . ' = "ABSENT" then 1 else 0 end) as absent_count')
                )
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.id_number', 'users.section')
                ->having('total_logs', '>', 0)
                ->get()
                ->map(function($student) {
                    $rate = $student->total_logs > 0 ? round((($student->present_count + $student->late_count) / $student->total_logs) * 100, 1) : 100;
                    $student->attendance_rate = $rate;
                    if ($rate < 75 || $student->absent_count >= 3) {
                        $student->status_badge = 'At Risk';
                    } elseif ($rate < 88 || $student->late_count >= 3) {
                        $student->status_badge = 'Monitor';
                    } else {
                        $student->status_badge = 'Good';
                    }
                    return $student;
                })
                ->filter(fn($s) => $s->status_badge !== 'Good')
                ->take(10);
        }

        $detailedQuery = DB::table('attendance_logs');
        if ($hasAttendance && $userForeignKey) {
            $detailedQuery->join('users', 'attendance_logs.' . $userForeignKey, '=', 'users.id')
                          ->select(
                              'attendance_logs.*',
                              'users.first_name',
                              'users.last_name',
                              'users.id_number',
                              'users.section',
                              'users.grade_level'
                          );
            if ($request->filled('school_year') && in_array('school_year', $attCols)) {
                $detailedQuery->where('attendance_logs.school_year', $request->school_year);
            }
            if ($request->filled('grade_level') && in_array('grade_level', $userCols)) {
                $detailedQuery->where('users.grade_level', $request->grade_level);
            }
            if ($request->filled('section') && in_array('section', $userCols)) {
                $detailedQuery->where('users.section', $request->section);
            }
            if ($request->filled('gender') && in_array('gender', $userCols)) {
                $detailedQuery->where('users.gender', $request->gender);
            }
            if ($request->filled('date_from')) {
                $detailedQuery->whereDate('attendance_logs.' . $dateCol, '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $detailedQuery->whereDate('attendance_logs.' . $dateCol, '<=', $request->date_to);
            }
        }
        $detailedRecords = $detailedQuery->latest('attendance_logs.created_at')->paginate(15)->withQueryString();

        $gradeLevels = User::where('role_id', 3)->whereNotNull('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level');
        $sections = User::where('role_id', 3)->whereNotNull('section')->distinct()->orderBy('section')->pluck('section');

        return view('admin.dashboard.analytics.attendance-rate', compact(
            'reportTitle',
            'activeSchoolYear',
            'schoolYears',
            'totalRecords',
            'presentCount',
            'lateCount',
            'absentCount',
            'excusedCount',
            'overallRate',
            'trendData',
            'sectionAttendance',
            'concernsStudents',
            'detailedRecords',
            'gradeLevels',
            'sections'
        ));
    }
}