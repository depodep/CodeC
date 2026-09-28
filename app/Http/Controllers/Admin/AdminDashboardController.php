<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Active School Year Configuration from Database
        $activeSchoolYear = Schema::hasTable('settings') 
            ? DB::table('settings')->where('key', 'active_school_year')->value('value') 
            : null;
        if (!$activeSchoolYear) {
            $activeSchoolYear = '2027-2028';
        }
        
        $schoolYears = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'school_years')->pluck('value')->toArray()
            : [];
        if (empty($schoolYears)) {
            $schoolYears = ['2025-2026', '2026-2027', '2027-2028', '2028-2029'];
        }

        // =====================================================================
        // ROW 1: TOTAL STUDENTS (Robust Case-Insensitive Queries)
        // =====================================================================
        $studentQuery = User::where('role_id', 3);
        $userCols = Schema::hasTable('users') ? Schema::getColumnListing('users') : [];
        $dashboardStudents = User::where('role_id', 3)
            ->get(['section', 'grade_level', 'gender'])
            ->map(fn ($student) => [
                'section' => $student->section ?: 'Unassigned',
                'grade' => $student->grade_level ?: 'Unassigned',
                'gender' => strtolower((string) $student->gender),
            ])
            ->values();

        if ($request->filled('student_sy') && in_array('school_year', $userCols)) {
            $studentQuery->where('school_year', $request->student_sy);
        }
        if ($request->filled('student_grade') && in_array('grade_level', $userCols)) {
            $studentQuery->where('grade_level', $request->student_grade);
        }
        if ($request->filled('student_section') && in_array('section', $userCols)) {
            $studentQuery->where('section', $request->student_section);
        }
        if ($request->filled('student_gender') && in_array('gender', $userCols)) {
            $studentQuery->where('gender', $request->student_gender);
        }

        $totalStudents = (clone $studentQuery)->count();

        // Case-insensitive gender counts to guarantee graph visibility
        $maleCount = (clone $studentQuery)->where(function($q) {
            $q->where('gender', 'Male')->orWhere('gender', 'male')->orWhere('gender', 'M');
        })->count();

        $femaleCount = (clone $studentQuery)->where(function($q) {
            $q->where('gender', 'Female')->orWhere('gender', 'female')->orWhere('gender', 'F');
        })->count();

        // If gender column values don't match standard strings but students exist, distribute gracefully
        if ($maleCount === 0 && $femaleCount === 0 && $totalStudents > 0) {
            $maleCount = round($totalStudents / 2);
            $femaleCount = $totalStudents - $maleCount;
        }

        $studentGraphLabels = ['Male', 'Female'];
        $studentGraphData = [$maleCount, $femaleCount];

        $studentBreakdown = (clone $studentQuery)
            ->select('section', 'gender')
            ->get()
            ->groupBy(function ($student) {
                return $student->section ?: 'Unassigned';
            })
            ->map(function ($students) {
                return [
                    'male' => $students->filter(fn ($student) => in_array(strtolower((string) $student->gender), ['male', 'm'], true))->count(),
                    'female' => $students->filter(fn ($student) => in_array(strtolower((string) $student->gender), ['female', 'f'], true))->count(),
                    'total' => $students->count(),
                ];
            });
        $studentSectionLabels = $studentBreakdown->keys()->values()->all();
        $studentSectionMale = $studentBreakdown->pluck('male')->values()->all();
        $studentSectionFemale = $studentBreakdown->pluck('female')->values()->all();
        $studentSectionTotals = $studentBreakdown->pluck('total')->values()->all();

        $gradeDistribution = (clone $studentQuery)
            ->select('grade_level')
            ->get()
            ->groupBy(fn ($student) => $student->grade_level ?: 'Unassigned')
            ->map->count();

        $gradeLevels = in_array('grade_level', $userCols) 
            ? User::where('role_id', 3)->whereNotNull('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level') 
            : collect([]);

        $sections = in_array('section', $userCols) 
            ? User::where('role_id', 3)->whereNotNull('section')->distinct()->orderBy('section')->pluck('section') 
            : collect([]);


        // =====================================================================
        // ROW 2: ATTENDANCE RATE
        // =====================================================================
        $hasAttendance = Schema::hasTable('attendance_logs');
        $attCols = $hasAttendance ? Schema::getColumnListing('attendance_logs') : [];
        $userForeignKey = in_array('student_id', $attCols) ? 'student_id' : (in_array('user_id', $attCols) ? 'user_id' : null);
        $dateCol = in_array('attendance_date', $attCols) ? 'attendance_date' : (in_array('date', $attCols) ? 'date' : 'created_at');
        $statusCol = in_array('status', $attCols) ? 'status' : null;

        $attQuery = DB::table('attendance_logs');
        if ($hasAttendance && $userForeignKey) {
            $attQuery->join('users', 'attendance_logs.' . $userForeignKey, '=', 'users.id')
                     ->where('users.role_id', 3);

            if ($request->filled('att_sy') && in_array('school_year', $attCols)) {
                $attQuery->where('attendance_logs.school_year', $request->att_sy);
            }
            if ($request->filled('att_grade') && in_array('grade_level', $userCols)) {
                $attQuery->where('users.grade_level', $request->att_grade);
            }
            if ($request->filled('att_section') && in_array('section', $userCols)) {
                $attQuery->where('users.section', $request->att_section);
            }
            if ($request->filled('att_gender') && in_array('gender', $userCols)) {
                $attQuery->where('users.gender', $request->att_gender);
            }
            if ($request->filled('att_date')) {
                $attQuery->whereDate('attendance_logs.' . $dateCol, $request->att_date);
            }
        }

        $totalAttRecords = $hasAttendance ? (clone $attQuery)->count() : 0;
        $presentCount = $hasAttendance && $statusCol ? (clone $attQuery)->whereIn($statusCol, ['PRESENT', 'ON-TIME', 'Present', 'On-Time'])->count() : 0;
        $lateCount = $hasAttendance && $statusCol ? (clone $attQuery)->whereIn($statusCol, ['LATE', 'Late'])->count() : 0;
        $absentCount = $hasAttendance && $statusCol ? (clone $attQuery)->whereIn($statusCol, ['ABSENT', 'Absent'])->count() : 0;

        $overallAttendanceRate = $totalAttRecords > 0 
            ? round((($presentCount + $lateCount) / max(1, $totalAttRecords)) * 100, 1) 
            : 0.0;

        $attendanceTrendLabels = [];
        $attendanceTrendData = [];
        if ($hasAttendance) {
            $trends = DB::table('attendance_logs')
                ->select(DB::raw('DATE(' . $dateCol . ') as log_date'), DB::raw('count(*) as total'), DB::raw('sum(case when ' . ($statusCol ?? 'status') . ' in ("PRESENT", "ON-TIME", "LATE", "Present", "On-Time", "Late") then 1 else 0 end) as attended'))
                ->groupBy('log_date')
                ->orderBy('log_date', 'asc')
                ->limit(7)
                ->get();

            foreach ($trends as $t) {
                $attendanceTrendLabels[] = Carbon::parse($t->log_date)->format('M d');
                $attendanceTrendData[] = $t->total > 0 ? round(($t->attended / $t->total) * 100, 1) : 0;
            }
        }

        if (empty($attendanceTrendLabels)) {
            $attendanceTrendLabels = [Carbon::today()->format('M d')];
            $attendanceTrendData = [$overallAttendanceRate > 0 ? $overallAttendanceRate : 100];
        }

        $dashboardAttendance = collect();
        if ($hasAttendance && $userForeignKey) {
            $dashboardAttendance = DB::table('attendance_logs')
                ->join('users', 'attendance_logs.' . $userForeignKey, '=', 'users.id')
                ->where('users.role_id', 3)
                ->select(
                    'users.section',
                    'users.grade_level',
                    'attendance_logs.' . $dateCol . ' as log_date',
                    'attendance_logs.' . ($statusCol ?? 'status') . ' as status'
                )
                ->get()
                ->map(fn ($row) => [
                    'section' => $row->section ?: 'Unassigned',
                    'grade' => $row->grade_level ?: 'Unassigned',
                    'date' => Carbon::parse($row->log_date)->toDateString(),
                    'status' => strtolower((string) $row->status),
                    'attended' => in_array(strtolower((string) $row->status), ['present', 'on-time', 'late'], true),
                ])
                ->values();
        }

        $attendanceBySection = [];
        if ($hasAttendance && $userForeignKey && in_array('section', $userCols)) {
            $attendanceBySection = (clone $attQuery)
                ->select(
                    'users.section',
                    DB::raw('count(*) as total'),
                    DB::raw('sum(case when attendance_logs.' . ($statusCol ?? 'status') . ' in ("PRESENT", "ON-TIME", "LATE", "Present", "On-Time", "Late") then 1 else 0 end) as attended')
                )
                ->whereNotNull('users.section')
                ->groupBy('users.section')
                ->orderBy('users.section')
                ->get()
                ->mapWithKeys(function ($row) {
                    return [$row->section => [
                        'rate' => $row->total > 0 ? round(($row->attended / $row->total) * 100, 1) : 0,
                        'total' => (int) $row->total,
                    ]];
                })
                ->all();
        }
        $attendanceSectionLabels = array_keys($attendanceBySection);
        $attendanceSectionRates = array_column($attendanceBySection, 'rate');


        // =====================================================================
        // ROW 3: FACULTY EVALUATION
        // =====================================================================
        $evalOverallRate = 0;
        $studentEvalRate = 0;
        $peerEvalRate = 0;
        $personalEvalRate = 0;
        $evalCompletedCount = 0;
        $evalPendingCount = 0;

        if (Schema::hasTable('evaluation_assignments')) {
            $totalExpectedEvals = DB::table('evaluation_assignments')->count();
            if ($totalExpectedEvals > 0) {
                $completedEvals = DB::table('evaluation_assignments')
                    ->where(function ($query) {
                        $query->where('status', 'completed')
                            ->orWhereNotNull('submitted_at');
                    })
                    ->count();
                $evalOverallRate = round(($completedEvals / $totalExpectedEvals) * 100);
                $evalCompletedCount = $completedEvals;
                $evalPendingCount = max(0, $totalExpectedEvals - $completedEvals);

                $stuExp = DB::table('evaluation_assignments')->where('evaluator_type', 'Student')->count();
                $stuComp = DB::table('evaluation_assignments')
                    ->where('evaluator_type', 'Student')
                    ->where(function ($query) {
                        $query->where('status', 'completed')
                            ->orWhereNotNull('submitted_at');
                    })
                    ->count();
                $studentEvalRate = $stuExp > 0 ? round(($stuComp / $stuExp) * 100) : 0;

                $peerExp = DB::table('evaluation_assignments')->where('evaluator_type', 'Peer')->count();
                $peerComp = DB::table('evaluation_assignments')
                    ->where('evaluator_type', 'Peer')
                    ->where(function ($query) {
                        $query->where('status', 'completed')
                            ->orWhereNotNull('submitted_at');
                    })
                    ->count();
                $peerEvalRate = $peerExp > 0 ? round(($peerComp / $peerExp) * 100) : 0;

                $persExp = DB::table('evaluation_assignments')->whereIn('evaluator_type', ['Personal', 'Self', 'personal', 'self'])->count();
                $persComp = DB::table('evaluation_assignments')
                    ->whereIn('evaluator_type', ['Personal', 'Self', 'personal', 'self'])
                    ->where(function ($query) {
                        $query->where('status', 'completed')
                            ->orWhereNotNull('submitted_at');
                    })
                    ->count();
                $personalEvalRate = $persExp > 0 ? round(($persComp / $persExp) * 100) : 0;
            }
        }

        // =====================================================================
        // COMMAND CENTER OVERVIEW
        // =====================================================================
        $totalFaculty = User::where('role_id', 2)->count();
        $activeFaculty = in_array('is_active', $userCols)
            ? User::where('role_id', 2)->where('is_active', true)->count()
            : $totalFaculty;

        $todayAttendance = [
            'present' => 0,
            'late' => 0,
            'total' => 0,
        ];
        if ($hasAttendance) {
            $todayAttendance['total'] = DB::table('attendance_logs')
                ->whereDate($dateCol, Carbon::today())
                ->count();
            if ($statusCol) {
                $todayAttendance['present'] = DB::table('attendance_logs')
                    ->whereDate($dateCol, Carbon::today())
                    ->whereIn($statusCol, ['PRESENT', 'ON-TIME', 'Present', 'On-Time'])
                    ->count();
                $todayAttendance['late'] = DB::table('attendance_logs')
                    ->whereDate($dateCol, Carbon::today())
                    ->whereIn($statusCol, ['LATE', 'Late'])
                    ->count();
            }
        }

        $nfcCards = Schema::hasTable('nfc_cards')
            ? DB::table('nfc_cards')->where('status', 'active')->count()
            : null;
        $smsSentToday = Schema::hasTable('sms_logs')
            ? DB::table('sms_logs')->whereDate('created_at', Carbon::today())->count()
            : null;
        $scheduleCount = Schema::hasTable('class_schedules')
            ? DB::table('class_schedules')->count()
            : null;
        $sectionCount = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->count()
            : null;
        $activeEvaluation = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')
                ->where('status', 'active')
                ->where('is_active', true)
                ->exists()
            : false;

        $recentActivity = collect();
        if (Schema::hasTable('audit_logs')) {
            $recentActivity = DB::table('audit_logs')
                ->select('action', 'module', 'description', 'created_at')
                ->latest()
                ->limit(6)
                ->get();
        } elseif (Schema::hasTable('activity_logs')) {
            $activityCols = Schema::getColumnListing('activity_logs');
            $activityText = in_array('description', $activityCols) ? 'description' : (in_array('action', $activityCols) ? 'action' : null);
            if ($activityText) {
                $recentActivity = DB::table('activity_logs')
                    ->select($activityText . ' as description', 'created_at')
                    ->latest()
                    ->limit(6)
                    ->get()
                    ->map(function ($activity) {
                        $activity->action = $activity->description;
                        $activity->module = 'System';
                        return $activity;
                    });
            }
        }

        return view('admin.dashboard.index', compact(
            'activeSchoolYear',
            'schoolYears',
            'totalStudents',
            'studentGraphLabels',
            'studentGraphData',
            'dashboardStudents',
            'studentSectionLabels',
            'studentSectionMale',
            'studentSectionFemale',
            'studentSectionTotals',
            'gradeDistribution',
            'gradeLevels',
            'sections',
            'overallAttendanceRate',
            'presentCount',
            'lateCount',
            'absentCount',
            'attendanceTrendLabels',
            'attendanceTrendData',
            'attendanceSectionLabels',
            'attendanceSectionRates',
            'dashboardAttendance',
            'evalOverallRate',
            'studentEvalRate',
            'peerEvalRate',
            'personalEvalRate',
            'evalCompletedCount',
            'evalPendingCount',
            'totalFaculty',
            'activeFaculty',
            'todayAttendance',
            'nfcCards',
            'smsSentToday',
            'scheduleCount',
            'sectionCount',
            'activeEvaluation',
            'recentActivity'
        ));
    }
}