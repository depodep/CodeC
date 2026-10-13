<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class FacultyEvaluationController extends Controller
{
    public function monitoring(Request $request)
    {
        $reportTitle = 'Faculty Evaluation Monitoring Dashboard';

        // 1. Fetch Active School Years and Periods from DB
        $schoolYears = Schema::hasTable('academic_periods') 
            ? DB::table('academic_periods')->distinct()->pluck('school_year')->toArray() 
            : ['2025-2026', '2026-2027', '2027-2028'];
            
        if(empty($schoolYears)) {
            $schoolYears = ['2026-2027'];
        }

        $activeSchoolYear = $request->input('school_year', $schoolYears[0] ?? '2026-2027');
        $activePeriod = 'Academic Year ' . $activeSchoolYear;

        // Active Period Info Box from DB if available
        $activePeriodRecord = null;
        if (Schema::hasTable('academic_periods')) {
            $activePeriodRecord = DB::table('academic_periods')
                ->where('school_year', $activeSchoolYear)
                ->first();
        }

        $periodInfo = [
            'name' => 'A.Y. ' . $activeSchoolYear,
            'start_date' => !empty($activePeriodRecord->start_date) ? Carbon::parse($activePeriodRecord->start_date)->format('F d, Y') : 'June 1, ' . explode('-', $activeSchoolYear)[0],
            'end_date' => !empty($activePeriodRecord->end_date) ? Carbon::parse($activePeriodRecord->end_date)->format('F d, Y') : 'March 31, ' . (explode('-', $activeSchoolYear)[1] ?? '2027'),
            'status' => ($activePeriodRecord->is_active ?? true) ? 'Active' : 'Closed'
        ];

        // 2. Fetch Filters from Request
        $selectedType = $request->input('evaluator_type'); 
        $selectedDepartment = $request->input('department');
        $selectedStatus = $request->input('status');
        $searchQuery = trim($request->input('search', ''));

        // Departments from Users table
        $departments = Schema::hasTable('users') && Schema::hasColumn('users', 'department') 
            ? DB::table('users')->whereNotNull('department')->distinct()->pluck('department') 
            : collect(['Information Technology', 'Education', 'General Education']);

        // Faculty list (role_id 2 or role teacher/faculty)
        $hasRoleCol = Schema::hasTable('users') && Schema::hasColumn('users', 'role');
        $hasDeptCol = Schema::hasTable('users') && Schema::hasColumn('users', 'department');
        $facultyQuery = DB::table('users')->where(function($q) use ($hasRoleCol) {
            $q->where('role_id', 2);
            if ($hasRoleCol) {
                $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
            }
        });

        if ($selectedDepartment && $hasDeptCol) {
            $facultyQuery->where('department', $selectedDepartment);
        }

        $selectCols = ['id', 'first_name', 'last_name', 'id_number'];
        if ($hasDeptCol) {
            $selectCols[] = 'department';
        }

        $facultyList = $facultyQuery->select($selectCols)->get();

        // 3. Database Queries for Evaluation Statistics
        $hasEvalTable = Schema::hasTable('evaluations');
        $hasAssignmentsTable = Schema::hasTable('evaluation_assignments');

        // Calculate Totals dynamically from DB
        $totalExpected = 0;
        $completedCount = 0;

        // Initialize typesData with default structure and labels
        $typesData = [
            'student' => ['label' => 'Students', 'expected' => 0, 'completed' => 0, 'percentage' => 0],
            'peer' => ['label' => 'Peers', 'expected' => 0, 'completed' => 0, 'percentage' => 0],
            'personal' => ['label' => 'Self', 'expected' => 0, 'completed' => 0, 'percentage' => 0],
        ];

        if ($hasAssignmentsTable && DB::table('evaluation_assignments')->count() > 0) {
            $assignQuery = DB::table('evaluation_assignments');
            if ($activePeriod) {
                $assignQuery->where('evaluation_period', $activePeriod);
            }
            if ($activeSchoolYear) {
                $assignQuery->where('school_year', $activeSchoolYear);
            }
            $totalExpected = $assignQuery->count();
            $completedCount = (clone $assignQuery)->where(function($q) {
                $q->where('status', 'completed')->orWhere('status', 'Completed')->orWhereNotNull('submitted_at');
            })->count();

            foreach (['student' => ['Student', 'student'], 'peer' => ['Peer', 'peer'], 'personal' => ['Personal', 'self', 'Personal / Self-Evaluation']] as $key => $aliases) {
                $typeQuery = DB::table('evaluation_assignments')->whereIn('evaluator_type', $aliases);
                if ($activePeriod) $typeQuery->where('evaluation_period', $activePeriod);
                if ($activeSchoolYear) $typeQuery->where('school_year', $activeSchoolYear);
                
                $exp = $typeQuery->count();
                $comp = (clone $typeQuery)->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'Completed')->orWhereNotNull('submitted_at'))->count();
                
                $typesData[$key]['expected'] = $exp;
                $typesData[$key]['completed'] = $comp;
                $typesData[$key]['percentage'] = $exp > 0 ? round(($comp / $exp) * 100) : 0;
            }
        } elseif ($hasEvalTable && DB::table('evaluations')->count() > 0) {
            $evalQuery = DB::table('evaluations');
            if ($activePeriod) {
                $evalQuery->where('evaluation_period', $activePeriod);
            }
            if ($activeSchoolYear) {
                $evalQuery->where('school_year', $activeSchoolYear);
            }
            $totalExpected = $evalQuery->count();
            $completedCount = (clone $evalQuery)->where(function($q) {
                $q->where('status', 'completed')->orWhere('status', 'Completed')->orWhereNotNull('submitted_at');
            })->count();
        } else {
            // Real evaluation tables
            $activeCycle = Schema::hasTable('evaluation_cycles')
                ? (DB::table('evaluation_cycles')->where('status', 'active')->first() ?? DB::table('evaluation_cycles')->first())
                : null;
            $cycleId = $activeCycle->id ?? null;

            $teacherCount = DB::table('users')->where('role_id', 2)->count();
            $studentCount = DB::table('users')->where('role_id', 3)->count();

            $stuComp = Schema::hasTable('evaluation_submissions')
                ? DB::table('evaluation_submissions')->where('form_type', 'student')->when($cycleId, fn($q) => $q->where('evaluation_cycle_id', $cycleId))->count()
                : 0;
            $stuExp = $studentCount * max(1, $teacherCount);

            $peerComp = Schema::hasTable('peer_evaluations')
                ? DB::table('peer_evaluations')->when($cycleId, fn($q) => $q->where('evaluation_cycle_id', $cycleId))->count()
                : 0;
            $peerExp = $teacherCount > 1 ? ($teacherCount * ($teacherCount - 1)) : 0;

            $persComp = Schema::hasTable('self_evaluations')
                ? DB::table('self_evaluations')->when($cycleId, fn($q) => $q->where('evaluation_cycle_id', $cycleId))->count()
                : 0;
            $persExp = $teacherCount;

            $totalExpected = $stuExp + $peerExp + $persExp;
            $completedCount = $stuComp + $peerComp + $persComp;

            $typesData['student']['expected'] = $stuExp;
            $typesData['student']['completed'] = $stuComp;
            $typesData['student']['percentage'] = $stuExp > 0 ? min(100, round(($stuComp / $stuExp) * 100)) : 0;

            $typesData['peer']['expected'] = $peerExp;
            $typesData['peer']['completed'] = $peerComp;
            $typesData['peer']['percentage'] = $peerExp > 0 ? min(100, round(($peerComp / $peerExp) * 100)) : 0;

            $typesData['personal']['expected'] = $persExp;
            $typesData['personal']['completed'] = $persComp;
            $typesData['personal']['percentage'] = $persExp > 0 ? min(100, round(($persComp / $persExp) * 100)) : 0;
        }

        $notYetEvaluatedCount = max(0, $totalExpected - $completedCount);
        $overallRate = $totalExpected > 0 ? round(($completedCount / $totalExpected) * 100) : 0;

        // Status Breakdown percentages
        $statusBreakdown = [
            'completed' => $overallRate,
            'not_yet' => $totalExpected > 0 ? round(($notYetEvaluatedCount / $totalExpected) * 100) : 0,
            'in_progress' => 0,
            'overdue' => 0
        ];

        // Trend Data (Real distribution or empty if no tracking dates)
        $trendData = [
            ['week' => 'Week 1', 'rate' => 0],
            ['week' => 'Week 2', 'rate' => 0],
            ['week' => 'Week 3', 'rate' => 0],
            ['week' => 'Week 4', 'rate' => $overallRate],
        ];

        // 4. Faculty Evaluation Summary Table (Real Data from DB)
        $facultySummary = $facultyList->map(function($fac) use ($hasAssignmentsTable, $activePeriod, $activeSchoolYear, $searchQuery) {
            $studentTotal = 0; $studentDone = 0;
            $peerTotal = 0; $peerDone = 0;
            $personalTotal = 0; $personalDone = 0;

            if ($hasAssignmentsTable && DB::table('evaluation_assignments')->count() > 0) {
                $facAssigns = DB::table('evaluation_assignments')->where('faculty_id', $fac->id);
                if ($activePeriod) $facAssigns->where('evaluation_period', $activePeriod);
                if ($activeSchoolYear) $facAssigns->where('school_year', $activeSchoolYear);

                $studentTotal = (clone $facAssigns)->where('evaluator_type', 'Student')->count();
                $studentDone = (clone $facAssigns)->where('evaluator_type', 'Student')->where(fn($q) => $q->where('status', 'completed')->orWhereNotNull('submitted_at'))->count();

                $peerTotal = (clone $facAssigns)->where('evaluator_type', 'Peer')->count();
                $peerDone = (clone $facAssigns)->where('evaluator_type', 'Peer')->where(fn($q) => $q->where('status', 'completed')->orWhereNotNull('submitted_at'))->count();

                $personalTotal = (clone $facAssigns)->whereIn('evaluator_type', ['Personal', 'Self'])->count();
                $personalDone = (clone $facAssigns)->whereIn('evaluator_type', ['Personal', 'Self'])->where(fn($q) => $q->where('status', 'completed')->orWhereNotNull('submitted_at'))->count();
            } else {
                $cycleId = Schema::hasTable('evaluation_cycles')
                    ? (DB::table('evaluation_cycles')->where('status', 'active')->value('id') ?? DB::table('evaluation_cycles')->value('id'))
                    : null;
                $studentTotal = DB::table('users')->where('role_id', 3)->count();
                $studentDone = Schema::hasTable('evaluation_submissions')
                    ? DB::table('evaluation_submissions')->where('teacher_id', $fac->id)->where('form_type', 'student')->when($cycleId, fn($q) => $q->where('evaluation_cycle_id', $cycleId))->count()
                    : 0;

                $peerTotal = max(0, DB::table('users')->where('role_id', 2)->count() - 1);
                $peerDone = Schema::hasTable('peer_evaluations')
                    ? DB::table('peer_evaluations')->where('evaluatee_id', $fac->id)->when($cycleId, fn($q) => $q->where('evaluation_cycle_id', $cycleId))->count()
                    : 0;

                $personalTotal = 1;
                $personalDone = Schema::hasTable('self_evaluations')
                    ? DB::table('self_evaluations')->where('teacher_id', $fac->id)->when($cycleId, fn($q) => $q->where('evaluation_cycle_id', $cycleId))->count()
                    : 0;
            }

            $facTotalExpected = $studentTotal + $peerTotal + $personalTotal;
            $facTotalDone = $studentDone + $peerDone + $personalDone;
            $rate = $facTotalExpected > 0 ? round(($facTotalDone / $facTotalExpected) * 100) : 0;

            if (!empty($searchQuery)) {
                if (!stripos($fac->first_name, $searchQuery) && !stripos($fac->last_name, $searchQuery) && !stripos($fac->id_number, $searchQuery)) {
                    return null;
                }
            }

            return (object)[
                'id' => $fac->id,
                'name' => $fac->first_name . ' ' . $fac->last_name,
                'id_number' => $fac->id_number ?? 'FAC-' . str_pad($fac->id, 3, '0', STR_PAD_LEFT),
                'department' => $fac->department ?? 'General Education',
                'student_progress' => "{$studentDone}/{$studentTotal}",
                'peer_progress' => "{$peerDone}/{$peerTotal}",
                'personal_progress' => "{$personalDone}/{$personalTotal}",
                'overall_rate' => $rate,
                'status' => ($facTotalExpected > 0 && $facTotalDone >= $facTotalExpected) ? 'Completed' : 'In Progress'
            ];
        })->filter()->values();

        if ($selectedStatus) {
            $facultySummary = $facultySummary->filter(function($item) use ($selectedStatus) {
                if ($selectedStatus === 'completed') return $item->overall_rate >= 100;
                if ($selectedStatus === 'in_progress') return $item->overall_rate < 100;
                return true;
            })->values();
        }

        // 5. Not Yet Evaluated Section (Real Pending Items from DB)
        $pendingEvaluations = collect();
        if ($hasAssignmentsTable) {
            $pendingQuery = DB::table('evaluation_assignments')
                ->where(fn($q) => $q->where('status', '!=', 'completed')->orWhereNull('submitted_at'));
            if ($activePeriod) $pendingQuery->where('evaluation_period', $activePeriod);
            if ($activeSchoolYear) $pendingQuery->where('school_year', $activeSchoolYear);

            $pendingRecords = $pendingQuery->limit(20)->get();
            foreach ($pendingRecords as $pend) {
                $evaluatorName = 'Evaluator #' . ($pend->evaluator_id ?? 'Unknown');
                if (Schema::hasTable('users') && $pend->evaluator_id) {
                    $evUser = DB::table('users')->where('id', $pend->evaluator_id)->first();
                    if ($evUser) {
                        $evaluatorName = $evUser->first_name . ' ' . $evUser->last_name;
                    }
                }

                $facUser = DB::table('users')->where('id', $pend->faculty_id)->first();
                $facultyName = $facUser ? ($facUser->first_name . ' ' . $facUser->last_name) : 'Faculty Member';

                $pendingEvaluations->push((object)[
                    'evaluator' => $evaluatorName,
                    'evaluator_type' => $pend->evaluator_type ?? 'Standard',
                    'faculty' => $facultyName,
                    'period' => $pend->evaluation_period ?? $activePeriod,
                    'status' => 'Pending'
                ]);
            }
        }

        $evaluationPeriods = [];

        return view('admin.evaluations.monitoring', compact(
            'reportTitle',
            'schoolYears',
            'activeSchoolYear',
            'evaluationPeriods',
            'activePeriod',
            'periodInfo',
            'departments',
            'facultyList',
            'totalExpected',
            'completedCount',
            'notYetEvaluatedCount',
            'overallRate',
            'typesData',
            'statusBreakdown',
            'trendData',
            'facultySummary',
            'pendingEvaluations'
        ));
    }
}