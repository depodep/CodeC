<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\IprogSmsService; 
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    public function classes(Request $request)
    {
        $teacher = Auth::user();
        $search = trim((string) $request->query('search'));
        $selectedSection = trim((string) $request->query('section'));
        $viewMode = $request->query('view', 'timetable');

        // 1. Get Active School Year / Period
        $activeSchoolYear = '2025-2026';
        $schoolYears = collect([]);
        if (Schema::hasTable('academic_periods')) {
            $schoolYears = DB::table('academic_periods')->orderBy('updated_at', 'desc')->get();
            $activePeriod = $schoolYears->firstWhere('is_active', 1);
            if ($activePeriod) {
                $activeSchoolYear = $activePeriod->name ?? $activePeriod->academic_year ?? $activePeriod->school_year ?? '2025-2026';
            }
        } elseif (Schema::hasTable('school_years')) {
            $schoolYears = DB::table('school_years')->orderBy('start_date', 'desc')->get();
            $activeYear = $schoolYears->firstWhere('status', 'Active') ?? $schoolYears->first();
            if ($activeYear) {
                $activeSchoolYear = $activeYear->name ?? $activeYear->school_year ?? $activeYear->academic_year ?? '2025-2026';
            }
        }

        // 2. Get Sections (Directory)
        $sections = collect([]);
        if (Schema::hasTable('academic_sections')) {
            $sortCol = Schema::hasColumn('academic_sections', 'section_name') ? 'section_name' : (Schema::hasColumn('academic_sections', 'name') ? 'name' : 'id');
            $sections = DB::table('academic_sections')->orderBy($sortCol, 'asc')->get();
        } elseif (Schema::hasTable('sections')) {
            $sortCol = Schema::hasColumn('sections', 'section_name') ? 'section_name' : (Schema::hasColumn('sections', 'name') ? 'name' : 'id');
            $sections = DB::table('sections')->orderBy($sortCol, 'asc')->get();
        }

        // 3. Get Class Schedules for the Authenticated Teacher
        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $teacherCol = in_array('teacher_id', $schedCols) ? 'teacher_id' : (in_array('user_id', $schedCols) ? 'user_id' : null);

        $mySchedules = collect([]);
        if (Schema::hasTable('class_schedules') && $teacherCol) {
            $mySchedules = ClassSchedule::with(['subjectRecord', 'academicSection'])
                ->where($teacherCol, $teacher->id)
                ->orderBy('start_time', 'asc')
                ->get();
        }

        $allSectionSchedules = collect();
        if (Schema::hasTable('class_schedules')) {
            $allSectionSchedules = ClassSchedule::with(['subjectRecord', 'teacher', 'academicSection'])
                ->orderBy('day')
                ->orderBy('start_time')
                ->get()
                ->map(function ($schedule) use ($teacher) {
                    $sectionName = optional($schedule->academicSection)->section_name ?: $schedule->section;
                    return [
                        'id' => $schedule->id,
                        'teacher_id' => (int) $schedule->teacher_id,
                        'section_id' => $schedule->section_id,
                        'section_name' => $sectionName,
                        'day' => trim($schedule->day ?? $schedule->day_of_week ?? 'Schedule'),
                        'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('g:i A') : 'TBA',
                        'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('g:i A') : 'TBA',
                        'start_time_value' => $schedule->start_time,
                        'end_time_value' => $schedule->end_time,
                        'subject' => $schedule->subject_name ?: optional($schedule->subjectRecord)->name ?: $schedule->subject ?: 'Class Schedule',
                        'subject_code' => $schedule->subject_code ?: optional($schedule->subjectRecord)->code,
                        'grade_level' => optional($schedule->academicSection)->grade_level ?: $schedule->grade_level,
                        'strand' => optional($schedule->academicSection)->strand ?: $schedule->strand,
                        'teacher' => optional($schedule->teacher)->first_name
                            ? trim(optional($schedule->teacher)->first_name . ' ' . optional($schedule->teacher)->last_name)
                            : 'Assigned Faculty',
                        'is_mine' => (int) $schedule->teacher_id === (int) $teacher->id,
                    ];
                });
        }

        $sections->each(function ($section) use ($allSectionSchedules) {
            $sectionName = trim((string) ($section->section_name ?? $section->name ?? ''));
            $section->schedule_list = $allSectionSchedules->filter(function ($schedule) use ($section, $sectionName) {
                return ($schedule['section_id'] && (int) $schedule['section_id'] === (int) $section->id)
                    || (!$schedule['section_id'] && $schedule['section_name'] === $sectionName);
            })->values();
            $section->has_my_schedule = $section->schedule_list->contains('is_mine', true);
        });

        // 4. Extract Teacher's unique assigned sections (for section filter)
        $teacherSectionNames = $mySchedules->map(function($sched) {
            return optional($sched->academicSection)->section_name ?: $sched->section;
        })->filter()->unique()->values();

        if (Schema::hasTable('academic_sections')) {
            $advisoryNames = DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->pluck('section_name')
                ->all();
            $teacherSectionNames = $teacherSectionNames->merge($advisoryNames)->unique()->values();
        }

        $teacherSectionNames = $teacherSectionNames->filter()->values();
        $sections = $sections->filter(function ($section) use ($teacherSectionNames) {
            $sectionName = trim((string) ($section->section_name ?? $section->name ?? ''));
            return $teacherSectionNames->contains(function ($assignedSection) use ($sectionName) {
                return strcasecmp(trim((string) $assignedSection), $sectionName) === 0;
            });
        })->values();

        // Calculate student counts per section
        $sectionStudentCounts = [];
        if (Schema::hasTable('users')) {
            $sectionStudentCounts = DB::table('users')
                ->where('role_id', 3)
                ->whereNotNull('section')
                ->groupBy('section')
                ->select('section', DB::raw('count(*) as count'))
                ->pluck('count', 'section')
                ->all();
        }

        $teacherTimetableSchedules = $allSectionSchedules
            ->filter(function ($schedule) use ($teacherSectionNames) {
                return $teacherSectionNames->contains(function ($sectionName) use ($schedule) {
                    return strcasecmp(trim((string) $sectionName), trim((string) $schedule['section_name'])) === 0;
                });
            })
            ->map(function ($schedule) use ($sectionStudentCounts) {
                $schedule['student_count'] = $sectionStudentCounts[$schedule['section_name']] ?? 0;
                return $schedule;
            })
            ->values();

        // Attach student counts and normalized section name to schedules
        $mySchedules->each(function($sched) use ($sectionStudentCounts) {
            $sec = optional($sched->academicSection)->section_name ?: $sched->section;
            $sched->normalized_section = $sec;
            $sched->student_count = $sec && isset($sectionStudentCounts[$sec]) ? $sectionStudentCounts[$sec] : 0;
        });

        // Metrics for count cards
        $totalLoads = $mySchedules->count();
        $sectionsCount = $teacherSectionNames->count();
        $subjectsCount = $mySchedules->pluck('subject_name')->filter()->unique()->count();
        if ($subjectsCount === 0 && $mySchedules->count() > 0) {
            $subjectsCount = $mySchedules->pluck('subject')->filter()->unique()->count() ?: $totalLoads;
        }

        return view('teacher.classes', compact(
            'teacher',
            'mySchedules',
            'teacherTimetableSchedules',
            'activeSchoolYear',
            'schoolYears',
            'sections',
            'teacherSectionNames',
            'totalLoads',
            'sectionsCount',
            'subjectsCount',
            'search',
            'selectedSection',
            'viewMode'
        ));
    }

    public function schedule(Request $request)
    {
        return $this->classes($request);
    }

    public function index()
    {
        $teacher = Auth::user();
        $activePeriod = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', 1)->first()
            : null;
        $schoolYearStart = $activePeriod?->start_date ? Carbon::parse($activePeriod->start_date)->startOfDay() : now()->startOfYear();
        $schoolYearEnd = $activePeriod?->end_date ? Carbon::parse($activePeriod->end_date)->endOfDay() : now()->endOfYear();
        $today = Carbon::today()->between($schoolYearStart, $schoolYearEnd) ? Carbon::today() : $schoolYearStart->copy()->startOfDay();
        $range = in_array(request('range', 'daily'), ['daily', 'weekly', 'monthly'], true) ? request('range', 'daily') : 'daily';
        $selectedDate = $this->dashboardDate(request('date'), $schoolYearStart, $schoolYearEnd, $today);
        [$rangeStart, $rangeEnd] = $this->dashboardRange($range, $selectedDate, $schoolYearStart, $schoolYearEnd);

        $advisorySections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('advisor_id', $teacher->id)->get()
            : collect();
        $schedules = Schema::hasTable('class_schedules')
            ? ClassSchedule::with(['academicSection', 'subjectRecord'])->where('teacher_id', $teacher->id)->orderBy('start_time')->get()
            : collect();
        $sectionIds = $advisorySections->pluck('id')->filter()->values();
        $scheduleSectionIds = $schedules->pluck('section_id')->filter()->values();
        $sectionNames = $schedules->map(fn ($schedule) => $schedule->academicSection?->section_name ?: $schedule->section)->filter()->unique()->values();
        $students = User::where('role_id', 3)->where(function ($query) use ($sectionNames, $sectionIds, $scheduleSectionIds) {
            if ($sectionNames->isNotEmpty()) {
                $query->whereIn('section', $sectionNames);
            }
            $pivotSectionIds = $sectionIds->merge($scheduleSectionIds)->unique()->values();
            if ($pivotSectionIds->isNotEmpty() && Schema::hasTable('section_student')) {
                $query->orWhereIn('id', DB::table('section_student')->whereIn('section_id', $pivotSectionIds)->pluck('student_id'));
            }
        })->orderBy('last_name')->orderBy('first_name')->get();

        $studentIds = $students->pluck('id');
        $advisoryCount = $advisorySections->count();
        $subjectCount = $schedules->count();
        $attendanceQuery = $this->teacherAttendanceQuery($teacher, $studentIds, $schedules, $sectionIds);
        $rangeLogs = (clone $attendanceQuery)->whereBetween('attendance_logs.attendance_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])->get();
        $todayLogs = $range === 'daily' ? $rangeLogs : (clone $attendanceQuery)->whereDate('attendance_logs.attendance_date', $selectedDate)->get();
        $presentStatuses = ['ON-TIME', 'PRESENT', 'on-time', 'present'];
        $lateStatuses = ['LATE', 'late'];
        $presentToday = $todayLogs->whereIn('status', array_merge($presentStatuses, $lateStatuses))->pluck('user_id')->unique()->count();
        $presentRecords = $rangeLogs->whereIn('status', array_merge($presentStatuses, $lateStatuses))->count();
        $lateRecords = $rangeLogs->whereIn('status', $lateStatuses)->count();
        $totalRecords = $rangeLogs->count();
        $totalStudents = $students->count();
        $attendanceRate = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0;
        $avgAttendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0;
        $chartDates = [];
        $chartPresents = [];
        $chartLates = [];
        $chartAbsents = [];
        $chartAdvisoryRates = [];
        $chartSubjectRates = [];

        // Precompute advisory and subject student groups for date loop
        $advisoryStudentIds = $students->filter(fn ($s) => $advisorySections->contains('section_name', $s->section))->pluck('id');
        $totalAdvisoryStudents = $advisoryStudentIds->count();

        // Subject class schedules student IDs
        $subjectStudentIds = $students->filter(fn ($s) => $schedules->contains(fn ($sched) => ($sched->academicSection?->section_name ?: $sched->section) === $s->section))->pluck('id');
        $totalSubjectStudents = $subjectStudentIds->count();

        for ($cursor = $rangeStart->copy(); $cursor->lte($rangeEnd); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $dayLogs = $rangeLogs->filter(fn ($log) => (string) $log->attendance_date === $key);
            $present = $dayLogs->whereIn('status', array_merge($presentStatuses, $lateStatuses))->pluck('user_id')->unique()->count();
            $chartDates[] = $range === 'monthly' ? $cursor->format('M d') : $cursor->format('D, M d');
            $chartPresents[] = $present;
            $chartLates[] = $dayLogs->whereIn('status', $lateStatuses)->pluck('user_id')->unique()->count();
            $chartAbsents[] = max(0, $totalStudents - $present);

            // Advisory attendance rate for this date
            if ($totalAdvisoryStudents > 0) {
                $dayAdvisoryPresent = $dayLogs->whereIn('user_id', $advisoryStudentIds)
                    ->whereIn('status', array_merge($presentStatuses, $lateStatuses))
                    ->pluck('user_id')->unique()->count();
                $chartAdvisoryRates[] = round(($dayAdvisoryPresent / $totalAdvisoryStudents) * 100, 1);
            } else {
                $chartAdvisoryRates[] = 0;
            }

            // Subject classes attendance rate for this date
            if ($totalSubjectStudents > 0) {
                $daySubjectPresent = $dayLogs->whereIn('user_id', $subjectStudentIds)
                    ->whereIn('status', array_merge($presentStatuses, $lateStatuses))
                    ->pluck('user_id')->unique()->count();
                $chartSubjectRates[] = round(($daySubjectPresent / $totalSubjectStudents) * 100, 1);
            } else {
                $chartSubjectRates[] = 0;
            }
        }

        $genderCounts = ['Male' => 0, 'Female' => 0];
        foreach ($students as $student) {
            $gender = match (strtolower((string) $student->gender)) {
                '1', 'm', 'male' => 'Male',
                '2', 'f', 'female' => 'Female',
                default => '',
            };
            if (isset($genderCounts[$gender])) $genderCounts[$gender]++;
        }
        $sectionStudentCounts = Schema::hasTable('section_student')
            ? DB::table('section_student')->select('section_id', DB::raw('COUNT(*) as total'))->groupBy('section_id')->pluck('total', 'section_id')
            : collect();
        // Gender breakdown per subject schedule
        $subjectSummary = $schedules->map(function ($schedule) use ($students, $rangeLogs, $sectionStudentCounts, $advisorySections, $sectionIds): array {
            $name = $schedule->subjectRecord?->name ?: ($schedule->subject_name ?: 'Subject');
            $section = $schedule->academicSection?->section_name ?: ($schedule->section ?: 'Unassigned');
            $sectionStudents = $students->filter(fn ($s) => $s->section === $section);
            $count = $schedule->section_id && isset($sectionStudentCounts[$schedule->section_id])
                ? (int) $sectionStudentCounts[$schedule->section_id]
                : $sectionStudents->count();
            
            // Gender counts among assigned section students
            $maleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['1', 'm', 'male']))->count();
            $femaleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['2', 'f', 'female']))->count();

            $advisorySectionNames = $advisorySections->pluck('section_name')->filter()->values();
            $isAdvisory = $advisorySectionNames->contains($section) || ($schedule->section_id && $sectionIds->contains($schedule->section_id));
            $displayLabel = $isAdvisory ? [$name, $section . ' (Advisory)'] : [$name, $section];

            $present = $rangeLogs->where('class_schedule_id', $schedule->id)->whereIn('status', ['ON-TIME', 'PRESENT', 'LATE', 'on-time', 'present', 'late'])->pluck('user_id')->unique()->count();
            return [
                'label' => $displayLabel,
                'full_label' => $name . ' - ' . $section . ($isAdvisory ? ' (Advisory)' : ''),
                'subject_name' => $name,
                'section_name' => $section,
                'is_advisory' => $isAdvisory,
                'students' => $count,
                'present' => $present,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
            ];
        })->values();

        // Gender breakdown per advisory section
        $advisorySummary = $advisorySections->map(function ($section) use ($students, $rangeLogs, $sectionStudentCounts): array {
            $sectionStudents = $students->filter(fn ($s) => $s->section === $section->section_name);
            $count = isset($sectionStudentCounts[$section->id])
                ? (int) $sectionStudentCounts[$section->id]
                : $sectionStudents->count();

            $maleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['1', 'm', 'male']))->count();
            $femaleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['2', 'f', 'female']))->count();

            $present = $rangeLogs->where('advisory_section_id', $section->id)->whereIn('status', ['ON-TIME', 'PRESENT', 'LATE', 'on-time', 'present', 'late'])->pluck('user_id')->unique()->count();
            return [
                'label' => $section->section_name,
                'section_id' => $section->id,
                'students' => $count,
                'present' => $present,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
            ];
        })->values();

        $classAttendanceRate = $subjectSummary->sum('students') > 0
            ? round(($subjectSummary->sum('present') / $subjectSummary->sum('students')) * 100, 1)
            : 0;
        $advisoryAttendanceRate = $advisorySummary->sum('students') > 0
            ? round(($advisorySummary->sum('present') / $advisorySummary->sum('students')) * 100, 1)
            : 0;

        // Check if an evaluation cycle is currently active
        $activeEvaluationCycle = null;
        if (Schema::hasTable('evaluation_cycles')) {
            $activeEvaluationCycle = DB::table('evaluation_cycles')
                ->where('is_active', 1)
                ->where('status', 'active')
                ->first();
        }

        $evaluatedStudentsCount = 0;
        $evaluationPercentage = 0;
        $isEvaluationActive = false;
        $evaluationGenderCounts = ['Male' => 0, 'Female' => 0];

        if ($activeEvaluationCycle && Schema::hasTable('evaluation_submissions')) {
            $isEvaluationActive = true;
            // Count unique students who evaluated this teacher in the active cycle
            $evaluatedStudentIds = DB::table('evaluation_submissions')
                ->where('teacher_id', $teacher->id)
                ->where('form_type', 'student')
                ->where('evaluation_cycle_id', $activeEvaluationCycle->id)
                ->distinct()
                ->pluck('student_id');

            $evaluatedStudentsCount = $evaluatedStudentIds->count();
            $evaluationPercentage = $totalStudents > 0 
                ? round(($evaluatedStudentsCount / $totalStudents) * 100, 1) 
                : 0;

            // Evaluated students by gender
            if ($evaluatedStudentIds->isNotEmpty()) {
                $evalStudents = User::whereIn('id', $evaluatedStudentIds)->get();
                foreach ($evalStudents as $st) {
                    $g = match (strtolower((string)$st->gender)) {
                        '1', 'm', 'male' => 'Male',
                        '2', 'f', 'female' => 'Female',
                        default => '',
                    };
                    if (isset($evaluationGenderCounts[$g])) {
                        $evaluationGenderCounts[$g]++;
                    }
                }
            }
        }

        // Section-by-section evaluation progress (Class Evaluation)
        $classEvaluationBreakdown = collect();
        $totalClassesHandled = 0;
        $completedClassesCount = 0;
        $allClassesCompleted = false;

        if ($isEvaluationActive) {
            // Group unique sections handled by this teacher
            $allTeacherSections = collect();

            // Advisory sections
            foreach ($advisorySections as $sec) {
                $secName = $sec->section_name;
                $key = 'sec_' . ($sec->id ?: strtolower(trim($secName)));
                $allTeacherSections->put($key, [
                    'type' => 'Advisory',
                    'name' => $secName,
                    'section_id' => $sec->id,
                ]);
            }

            // Subject sections (merge with advisory if same section)
            foreach ($schedules as $sched) {
                $secName = $sched->academicSection?->section_name ?: ($sched->section ?: 'Unassigned');
                $secId = $sched->section_id ?: ($sched->academicSection?->id ?: null);
                $key = 'sec_' . ($secId ?: strtolower(trim($secName)));
                if (!$allTeacherSections->has($key)) {
                    $allTeacherSections->put($key, [
                        'type' => 'Subject Class',
                        'name' => $secName,
                        'section_id' => $secId,
                        'subject_name' => $sched->subjectRecord?->name ?: ($sched->subject_name ?: 'Subject'),
                    ]);
                }
            }

            foreach ($allTeacherSections as $secInfo) {
                $secName = $secInfo['name'];
                $secStudents = $students->filter(fn ($s) => $s->section === $secName);
                $secTotal = $secStudents->count();
                $secEvaluated = $secStudents->whereIn('id', $evaluatedStudentIds ?? collect())->count();
                $secPercent = $secTotal > 0 ? round(($secEvaluated / $secTotal) * 100, 1) : 0;
                $isCompleted = ($secTotal > 0 && $secEvaluated >= $secTotal);

                if ($isCompleted) {
                    $completedClassesCount++;
                }
                $totalClassesHandled++;

                $classEvaluationBreakdown->push([
                    'type' => $secInfo['type'],
                    'section_name' => $secName,
                    'subject_name' => $secInfo['subject_name'] ?? null,
                    'total_students' => $secTotal,
                    'evaluated_students' => $secEvaluated,
                    'percentage' => $secPercent,
                    'is_completed' => $isCompleted,
                ]);
            }

            // Average class completion percentage across all handled sections
            $classEvaluationAvgPercentage = $totalClassesHandled > 0
                ? round($classEvaluationBreakdown->avg('percentage') ?? 0, 1)
                : 0;

            $allClassesCompleted = ($totalClassesHandled > 0 && $completedClassesCount >= $totalClassesHandled);

            // Advisory Class specific evaluation progress
            $advisoryEvalBreakdown = $classEvaluationBreakdown->firstWhere('type', 'Advisory');
            if ($advisoryEvalBreakdown) {
                $advisoryEvalTotal = (int) $advisoryEvalBreakdown['total_students'];
                $advisoryEvalCount = (int) $advisoryEvalBreakdown['evaluated_students'];
                $advisoryEvalPercentage = (float) $advisoryEvalBreakdown['percentage'];
                $advisorySectionTitle = $advisoryEvalBreakdown['section_name'];
            } else {
                $advisoryEvalTotal = 0;
                $advisoryEvalCount = 0;
                $advisoryEvalPercentage = 0;
                $advisorySectionTitle = 'No Advisory Section';
            }
        } else {
            $classEvaluationAvgPercentage = 0;
            $advisoryEvalTotal = 0;
            $advisoryEvalCount = 0;
            $advisoryEvalPercentage = 0;
            $advisorySectionTitle = 'No Advisory Section';
        }

        // Teacher's own evaluation status (self-evaluation and peer evaluations)
        $selfEvaluationDone = false;
        $peerEvaluationDone = false;
        $allTeacherEvaluationsDone = true;

        if ($isEvaluationActive && $activeEvaluationCycle) {
            if (Schema::hasTable('self_evaluations')) {
                $selfQuery = DB::table('self_evaluations')->where('teacher_id', $teacher->id);
                if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
                    $selfQuery->where('evaluation_cycle_id', $activeEvaluationCycle->id);
                }
                $selfEvaluationDone = $selfQuery->exists();
            }

            if (Schema::hasTable('peer_evaluations')) {
                $peerEvaluationDone = DB::table('peer_evaluations')
                    ->where('evaluator_id', $teacher->id)
                    ->where('evaluation_cycle_id', $activeEvaluationCycle->id)
                    ->exists();
            }

            $allTeacherEvaluationsDone = $selfEvaluationDone && $peerEvaluationDone;
        }

        // Attendance by Advisory: Male / Female stacked data
        $advisoryFemaleCounts = $advisorySummary->pluck('female_count')->values();
        $advisoryMaleCounts = $advisorySummary->pluck('male_count')->values();

        // Attendance by Subject: Male / Female stacked data
        $subjectFemaleCounts = $subjectSummary->pluck('female_count')->values();
        $subjectMaleCounts = $subjectSummary->pluck('male_count')->values();

        // Students by Grade: Grouping teacher's students by Grade Level with Male/Female counts
        $gradeGrouped = $students->groupBy(function ($s) {
            $g = trim((string)($s->grade_level ?? ''));
            return $g !== '' ? (str_starts_with(strtolower($g), 'grade') ? $g : 'Grade ' . $g) : 'Unassigned';
        })->sortKeys();

        $gradeLabels = $gradeGrouped->keys()->values();
        $gradeFemaleCounts = $gradeGrouped->map(fn ($group) => $group->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['2', 'f', 'female']))->count())->values();
        $gradeMaleCounts = $gradeGrouped->map(fn ($group) => $group->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['1', 'm', 'male']))->count())->values();

        // Return JSON if requested via AJAX
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'selectedDate' => $selectedDate->toDateString(),
                'rangeStartFormatted' => $rangeStart->format('M d, Y'),
                'rangeEndFormatted' => $rangeEnd->format('M d, Y'),
                'chartDates' => $chartDates,
                'chartPresents' => $chartPresents,
                'chartLates' => $chartLates,
                'chartAbsents' => $chartAbsents,
                'chartAdvisoryRates' => $chartAdvisoryRates,
                'chartSubjectRates' => $chartSubjectRates,
                'advisoryLabels' => $advisorySummary->pluck('label'),
                'advisoryPresent' => $advisorySummary->pluck('present'),
                'advisorySummary' => $advisorySummary,
                'advisoryFemaleCounts' => $advisoryFemaleCounts,
                'advisoryMaleCounts' => $advisoryMaleCounts,
                'subjectLabels' => $subjectSummary->pluck('label'),
                'subjectStudents' => $subjectSummary->pluck('students'),
                'subjectFemaleCounts' => $subjectFemaleCounts,
                'subjectMaleCounts' => $subjectMaleCounts,
                'subjectSummary' => $subjectSummary,
                'classAttendanceRate' => $classAttendanceRate,
                'advisoryAttendanceRate' => $advisoryAttendanceRate,
                'presentToday' => $presentToday,
                'attendanceRate' => $attendanceRate,
                'avgAttendanceRate' => $avgAttendanceRate,
                'classEvaluationAvgPercentage' => $classEvaluationAvgPercentage,
                'advisoryEvalTotal' => $advisoryEvalTotal,
                'advisoryEvalCount' => $advisoryEvalCount,
                'advisoryEvalPercentage' => $advisoryEvalPercentage,
                'advisorySectionTitle' => $advisorySectionTitle,
                'selfEvaluationDone' => $selfEvaluationDone,
                'peerEvaluationDone' => $peerEvaluationDone,
                'allTeacherEvaluationsDone' => $allTeacherEvaluationsDone,
            ]);
        }

        return view('teacher.dashboard', compact(
            'teacher', 'activePeriod', 'schoolYearStart', 'schoolYearEnd', 'range', 'selectedDate', 'rangeStart', 'rangeEnd',
            'totalStudents', 'advisoryCount', 'subjectCount', 'presentToday', 'attendanceRate', 'classAttendanceRate', 'advisoryAttendanceRate', 'totalRecords',
            'presentRecords', 'lateRecords', 'avgAttendanceRate', 'chartDates', 'chartPresents', 'chartLates', 'chartAbsents',
            'chartAdvisoryRates', 'chartSubjectRates',
            'genderCounts', 'subjectSummary', 'advisorySummary',
            'isEvaluationActive', 'activeEvaluationCycle', 'evaluatedStudentsCount', 'evaluationPercentage', 'evaluationGenderCounts',
            'classEvaluationBreakdown', 'totalClassesHandled', 'completedClassesCount', 'allClassesCompleted', 'classEvaluationAvgPercentage',
            'advisoryEvalTotal', 'advisoryEvalCount', 'advisoryEvalPercentage', 'advisorySectionTitle',
            'selfEvaluationDone', 'peerEvaluationDone', 'allTeacherEvaluationsDone',
            'advisoryFemaleCounts', 'advisoryMaleCounts', 'subjectFemaleCounts', 'subjectMaleCounts',
            'gradeLabels', 'gradeFemaleCounts', 'gradeMaleCounts'
        ));
    }

    private function dashboardDate(?string $value, Carbon $start, Carbon $end, Carbon $fallback): Carbon
    {
        try {
            $date = Carbon::parse($value ?: $fallback->toDateString())->startOfDay();
        } catch (\Throwable) {
            $date = $fallback->copy()->startOfDay();
        }
        return $date->between($start, $end) ? $date : $fallback->copy()->startOfDay();
    }

    private function dashboardRange(string $range, Carbon $date, Carbon $schoolYearStart, Carbon $schoolYearEnd): array
    {
        $start = match ($range) {
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
        $end = match ($range) {
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            default => $date->copy()->endOfDay(),
        };
        return [$start->max($schoolYearStart), $end->min($schoolYearEnd)];
    }

    private function teacherAttendanceQuery($teacher, $studentIds, $schedules, $sectionIds)
    {
        $query = DB::table('attendance_logs')->whereIn('attendance_logs.user_id', $studentIds->isNotEmpty() ? $studentIds : [0]);
        if (Schema::hasColumn('attendance_logs', 'attendance_date')) {
            $query->whereNotNull('attendance_logs.attendance_date');
        }
        $query->where(function ($scope) use ($teacher, $schedules, $sectionIds) {
            if ($schedules->isNotEmpty() && Schema::hasColumn('attendance_logs', 'class_schedule_id')) {
                $scope->whereIn('attendance_logs.class_schedule_id', $schedules->pluck('id'));
            }
            if ($sectionIds->isNotEmpty() && Schema::hasColumn('attendance_logs', 'advisory_section_id')) {
                $scope->orWhereIn('attendance_logs.advisory_section_id', $sectionIds);
            }
        });
        return $query->select('attendance_logs.*');
    }

    public function updateProfile(Request $request)
    {
        $teacher = Auth::user();

        $request->validate([
            'first_name'   => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'id_number'    => ['required', 'string', 'max:255'],
            'gender'       => ['required', 'in:1,2'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo'        => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $teacher->first_name   = $request->first_name;
        $teacher->last_name    = $request->last_name;
        $teacher->email        = $request->email;
        $teacher->id_number    = $request->id_number;
        $teacher->gender       = $request->gender;
        $teacher->phone_number = $request->phone_number;

        if ($request->filled('password')) {
            $teacher->password = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $path = $request->file('photo')->store('teacher-photos', 'public');
            $teacher->photo = $path;
        }

        $teacher->save();

        return redirect()->back()->with('success', 'Faculty profile updated successfully!');
    }

    public function schoolYears(Request $request)
    {
        return $this->classes($request);
    }

    public function students(Request $request)
    {
        $teacher = Auth::user();
        $search = trim((string) $request->query('search'));
        $sortOrder = $request->query('sort', 'asc');
        $tabs = collect();

        $advisorySectionIds = [];
        $advisorySectionNames = [];
        $advisorySections = collect();
        if (Schema::hasTable('academic_sections')) {
            $sections = DB::table('academic_sections')->where('advisor_id', $teacher->id)->get();
            $advisorySections = $sections;
            $advisorySectionIds = $sections->pluck('id')->all();
            $advisorySectionNames = $sections->pluck('section_name')->all();
            foreach ($sections as $section) {
                $tabs->prepend([
                    'id' => 'advisory-' . $section->id,
                    'type' => 'advisory',
                    'label' => 'Advisory - ' . ($section->grade_level ? 'Grade ' . $section->grade_level . ' ' : '') . $section->section_name,
                    'subject' => 'Advisory',
                    'grade' => $section->grade_level,
                    'section' => $section->section_name,
                    'section_id' => $section->id,
                    'students' => collect(),
                ]);
            }
        }

        if (Schema::hasTable('class_schedules')) {
            $teacherColumn = Schema::hasColumn('class_schedules', 'teacher_id') ? 'teacher_id' : (Schema::hasColumn('class_schedules', 'user_id') ? 'user_id' : null);
            if ($teacherColumn) {
                foreach (ClassSchedule::with(['subjectRecord', 'academicSection'])->where($teacherColumn, $teacher->id)->get() as $schedule) {
                    $section = optional($schedule->academicSection)->section_name ?: $schedule->section;
                    if (!$section) continue;
                    $grade = optional($schedule->academicSection)->grade_level ?: $schedule->grade_level;
                    $subject = $schedule->subject_name ?: optional($schedule->subjectRecord)->name ?: $schedule->subject ?: 'Subject';
                    $tabs->push([
                        'id' => 'subject-' . $schedule->id,
                        'type' => 'subject',
                        'label' => trim($subject . ' - ' . ($grade ? 'Grade ' . $grade . ' ' : '') . $section),
                        'subject' => $subject,
                        'grade' => $grade,
                        'section' => $section,
                        'section_id' => $schedule->section_id,
                        'schedule_id' => $schedule->id,
                        'students' => collect(),
                    ]);
                }
            }
        }

        $tabs = $tabs->unique(fn ($tab) => $tab['type'] . '|' . $tab['subject'] . '|' . $tab['grade'] . '|' . $tab['section'])->values();

        $activeEvaluationCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;
        $evaluatedStudentIds = ($activeEvaluationCycle && Schema::hasTable('peer_evaluations'))
            ? DB::table('peer_evaluations')
                ->where('evaluatee_id', $teacher->id)
                ->where('evaluation_cycle_id', $activeEvaluationCycle->id)
                ->pluck('evaluator_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $advisoryStudentIds = collect();
        if (Schema::hasTable('section_student') && !empty($advisorySectionIds)) {
            $advisoryStudentIds = DB::table('section_student')
                ->whereIn('section_id', $advisorySectionIds)
                ->pluck('student_id');
        }

        $baseQuery = User::with('nfcCard')->where('role_id', 3);
        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attendanceDate = $request->query('date', today()->toDateString());
        $tabs = $tabs->map(function ($tab) use ($baseQuery, $sortOrder, $advisorySectionNames, $advisoryStudentIds, $teacher, $evaluatedStudentIds, $activeEvaluationCycle, $attendanceDate) {
            $query = clone $baseQuery;
            $students = $query->where('section', $tab['section'])->orderBy('last_name', $sortOrder === 'desc' ? 'desc' : 'asc')->get();

            $attendanceQuery = collect();
            if (Schema::hasTable('attendance_logs') && $students->isNotEmpty()) {
                $attendanceStudentColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
                $attendanceDateColumn = Schema::hasColumn('attendance_logs', 'attendance_date')
                    ? 'attendance_date'
                    : (Schema::hasColumn('attendance_logs', 'scanned_at') ? 'scanned_at' : 'created_at');
                $attendanceBuilder = DB::table('attendance_logs');
                $attendanceBuilder->whereIn('attendance_logs.' . $attendanceStudentColumn, $students->pluck('id'));
                if (Schema::hasColumn('attendance_logs', 'attendance_type')) {
                    $attendanceBuilder->where('attendance_logs.attendance_type', $tab['type']);
                }
                if ($tab['type'] === 'subject' && !empty($tab['schedule_id'])) {
                    $attendanceBuilder->where('attendance_logs.class_schedule_id', $tab['schedule_id']);
                }
                if ($tab['type'] === 'advisory' && !empty($tab['section_id'])) {
                    $attendanceBuilder->where('attendance_logs.advisory_section_id', $tab['section_id']);
                }
                $attendanceQuery = $attendanceBuilder
                    ->whereDate('attendance_logs.' . $attendanceDateColumn, $attendanceDate)
                    ->select(
                        'attendance_logs.' . $attendanceStudentColumn . ' as student_id',
                        'attendance_logs.' . $attendanceDateColumn . ' as attendance_date',
                        'attendance_logs.time_in', 'attendance_logs.time_out',
                        'attendance_logs.status'
                    )
                    ->latest('attendance_logs.id')
                    ->get()
                    ->groupBy('student_id');
            }

            $students->each(function ($student) use ($advisorySectionNames, $advisoryStudentIds) {
                $isAdvisory = in_array($student->section, $advisorySectionNames)
                    || $advisoryStudentIds->contains($student->id);
                $student->can_edit = (bool) $isAdvisory;
            });

            $students->each(function ($student) use ($attendanceQuery, $evaluatedStudentIds, $activeEvaluationCycle) {
                $attendance = $attendanceQuery->get($student->id, collect())->first();
                $student->attendance_date = $attendance?->attendance_date;
                $student->attendance_time_in = $attendance?->time_in;
                $student->attendance_time_out = $attendance?->time_out;
                $student->attendance_status = $attendance
                    ? 'Present'
                    : 'Absent';
                $student->evaluation_status = $activeEvaluationCycle
                    ? (in_array((int) $student->id, $evaluatedStudentIds, true) ? 'Done' : 'Not Evaluated')
                    : 'Not Active';
            });

            $tab['students'] = $students;
            return $tab;
        });

        $students = $tabs->flatMap(fn ($tab) => $tab['students'])->unique('id')->values();
        $totalStudents = $students->count();

        $advisoryTabs = $tabs->where('type', 'advisory');
        $subjectTabs = $tabs->where('type', 'subject');
        $advisoryCount = $advisoryTabs->flatMap(fn ($tab) => $tab['students'])->unique('id')->count();
        $subjectCount = $subjectTabs->flatMap(fn ($tab) => $tab['students'])->unique('id')->count();
        $boundCount = $students->filter(fn ($student) => !empty(optional($student->nfcCard)->tag_id))->count();
        $unboundCount = max(0, $totalStudents - $boundCount);

        return view('teacher.students', compact(
            'teacher',
            'students',
            'tabs',
            'totalStudents',
            'advisoryCount',
            'subjectCount',
            'boundCount',
            'unboundCount',
            'search',
            'sortOrder'
            , 'advisorySections'
        ));
    }

    public function updateStudent(Request $request, $id)
    {
        $student = User::where('role_id', 3)->findOrFail($id);
        $teacher = Auth::user();

        $canUpdate = false;
        if (Schema::hasTable('academic_sections')) {
            $advisorSections = DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->get();
            $sectionNames = $advisorSections->pluck('section_name')->all();
            $sectionIds = $advisorSections->pluck('id')->all();

            $isEnrolledInAdvisorySection = false;
            if (Schema::hasTable('section_student') && !empty($sectionIds)) {
                $isEnrolledInAdvisorySection = DB::table('section_student')
                    ->where('student_id', $student->id)
                    ->whereIn('section_id', $sectionIds)
                    ->exists();
            }

            if (in_array($student->section, $sectionNames) || $isEnrolledInAdvisorySection) {
                $canUpdate = true;
            }
        }

        abort_unless($canUpdate, 403, 'Only the student’s advisory teacher can update this record.');
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users', 'id_number')->ignore($student->id)],
            'phone_number' => ['required', 'string', 'max:20'], 'gender' => ['required', 'string', 'max:20'], 'grade_level' => ['required', 'string', 'max:20'],
            'strand' => ['required', 'string', 'max:100'], 'section' => ['required', 'string', 'max:255'], 'parent_name' => ['required', 'string', 'max:255'],
            'parent_relationship' => ['required', 'string', 'max:50'], 'parent_phone_number' => ['required', 'string', 'max:20'],
        ]);
        foreach (['first_name', 'middle_name', 'last_name', 'id_number', 'phone_number', 'gender', 'grade_level', 'strand', 'section', 'parent_name', 'parent_relationship', 'parent_phone_number'] as $field) {
            if (Schema::hasColumn('users', $field)) $student->{$field} = $request->input($field);
        }
        $student->save();
        return back()->with('success', 'Student information updated successfully.');
    }

    public function storeStudent(Request $request)
    {
        $teacher = Auth::user();
        abort_unless(Schema::hasTable('academic_sections'), 403, 'Academic sections are not configured.');

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:255'],
            'id_number'  => ['required', 'string', 'max:255', 'unique:users,id_number'],
            'gender'     => ['required'],
            'grade_level' => ['required', 'string', 'max:20'],
            'strand'     => ['required', 'string', 'max:100'],
            'section'    => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:20'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_relationship' => ['required', 'string', 'max:50'],
            'parent_phone_number' => ['required', 'string', 'max:20'],
            'password'   => ['nullable', 'string', 'min:6'],
        ]);

        $section = DB::table('academic_sections')
            ->where('advisor_id', $teacher->id)
            ->where('section_name', $request->section)
            ->first();
        abort_unless($section, 403, 'You can only add students to your assigned advisory section.');

        $student = User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'  => $request->last_name,
            'id_number'  => $request->id_number,
            'gender'     => $request->gender,
            'grade_level' => $request->grade_level,
            'strand'     => $request->strand ?? 'STEM',
            'section'    => $request->section ?? 'Amber',
            'phone_number' => $request->phone_number,
            'parent_name' => $request->parent_name,
            'parent_relationship' => $request->parent_relationship,
            'parent_phone_number' => $request->parent_phone_number,
            'role_id'    => 3, 
            'academic_period_id' => Schema::hasTable('academic_periods')
                ? DB::table('academic_periods')->where('is_active', 1)->value('id')
                : null,
            'password'   => Hash::make($request->password ?: 'password123'), 
        ]);

        if (Schema::hasTable('section_student')) {
            DB::table('section_student')->insertOrIgnore([
                'section_id' => $section->id,
                'student_id' => $student->id,
            ]);
        }

        return redirect()->back()->with('success', 'Matagumpay na naidagdag ang bagong estudyante!');
    }

    public function messages(Request $request)
    {
        $teacher = Auth::user();
        $messages = collect([]);
        $sentCount = 0;
        $failedCount = 0;
        $students = collect([]);

        if (Schema::hasTable('sms_logs')) {
            $students = User::where('role_id', 3)->orderBy('last_name')->get();

            $query = DB::table('sms_logs')
                ->leftJoin('users', 'sms_logs.user_id', '=', 'users.id')
                ->select(
                    'sms_logs.*',
                    'users.first_name',
                    'users.last_name',
                    'users.id_number'
                );

            if ($request->filled('student_id')) {
                $query->where('sms_logs.user_id', $request->student_id);
            }
            if ($request->filled('status')) {
                $query->where('sms_logs.status', $request->status);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('sms_logs.created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('sms_logs.created_at', '<=', $request->to_date);
            }

            $messages = $query->orderBy('sms_logs.created_at', 'desc')->get();

            $sentCount = DB::table('sms_logs')->where('status', 'SENT')->count();
            $failedCount = DB::table('sms_logs')->where('status', 'FAILED')->count();
        }

        return view('teacher.messages', compact('teacher', 'messages', 'sentCount', 'failedCount', 'students'));
    }

    public function pingGateway()
    {
        $isOnline = IprogSmsService::ping();

        return response()->json([
            'success' => $isOnline,
            'message' => $isOnline ? 'Gateway active' : 'Gateway offline'
        ]);
    }
    
    // NOTIFY ABSENT STUDENTS VIA iProgSMS
    public function notifyAbsents(Request $request)
    {
        $today = Carbon::today()->toDateString();
        
        $attendanceColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';

        $presentIds = DB::table('attendance_logs')
            ->whereDate('created_at', $today)
            ->pluck($attendanceColumn) 
            ->toArray();

        $absentStudents = User::where('role_id', 3)
            ->whereNotIn('id', $presentIds)
            ->get();

        if ($absentStudents->isEmpty()) {
            return back()->with('info', 'Lahat ng estudyante ay pumasok ngayong araw. Walang kailangang ipadala na absent notice.');
        }

        $sentCount = 0;
        $dateStr = Carbon::now()->format('M d, Y');

        foreach ($absentStudents as $student) {
            $phone = $student->parent_phone_number 
                  ?? $student->parent_contact 
                  ?? $student->phone_number 
                  ?? $student->contact_number;

            if (empty($phone)) {
                continue;
            }

            $alreadySent = DB::table('sms_logs')
                ->where('user_id', $student->id)
                ->where('type', 'ABSENT_ALERT')
                ->where('status', 'SENT')
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $msg = "SIATRACK Advisory: Magandang araw. Nais naming ipaalam na ang inyong anak na si {$student->first_name} {$student->last_name} ay walang record ng pagpasok ngayong {$dateStr}.";

            $sent = IprogSmsService::send($phone, $msg);

            DB::table('sms_logs')->insert([
                'user_id'       => $student->id,
                'phone_number'  => $phone,
                'message'       => $msg,
                'type'          => 'ABSENT_ALERT',
                'status'        => $sent ? 'SENT' : 'FAILED',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            if ($sent) {
                $sentCount++;
            }
        }

        return back()->with('success', "Matagumpay na naipadala ang SMS absent notice sa {$sentCount} magulang.");
    }

    public function reports(Request $request)
    {
        $teacher = Auth::user();
        $myClasses = ClassSchedule::with(['subjectRecord', 'academicSection'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('section')
            ->orderBy('start_time')
            ->get();
        $advisorySections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->orderBy('grade_level')
                ->orderBy('section_name')
                ->get(['id', 'section_name', 'grade_level'])
            : collect();

        $period = $request->input('period', 'daily');
        validator(['period' => $period], [
            'period' => ['required', 'in:daily,weekly,monthly'],
        ])->validate();
        $fallbackDate = $request->input('report_date', now()->toDateString());
        $periodValue = $request->input('period_value');
        if (!$periodValue) {
            $fallback = Carbon::parse($fallbackDate);
            $periodValue = match ($period) {
                'weekly' => $fallback->format('o-\WW'),
                'monthly' => $fallback->format('Y-m'),
                default => $fallback->toDateString(),
            };
        }
        $request->merge([
            'report_by' => $request->input('report_by', 'section'),
            'period' => $period,
            'period_value' => $periodValue,
        ]);
        $filters = $request->validate([
            'report_by' => ['required', 'in:section,subject'],
            'period' => ['required', 'in:daily,weekly,monthly'],
            'period_value' => ['bail', 'required', 'string', function ($attribute, $value, $fail) use ($period): void {
                $valid = match ($period) {
                    'weekly' => preg_match('/^(\d{4})-W(\d{2})$/D', $value, $matches)
                        && (int) $matches[1] > 0
                        && Carbon::now()->setISODate((int) $matches[1], (int) $matches[2], Carbon::MONDAY)->format('o-W') === $matches[1] . '-' . $matches[2],
                    'monthly' => (bool) preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/D', $value),
                    default => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
                        && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)),
                };

                if (!$valid) {
                    $fail('Select a valid date, week, or month for the chosen report period.');
                }
            }],
            'section_id' => ['nullable', 'integer', 'exists:academic_sections,id'],
            'schedule_id' => ['nullable', 'integer', 'exists:class_schedules,id'],
            'status' => ['nullable', 'in:ON-TIME,LATE,ABSENT'],
            'per_page' => ['nullable', 'integer', 'in:10,20'],
        ]);

        $advisoryIds = $advisorySections->pluck('id');
        $scheduleIds = $myClasses->pluck('id');
        $selectionField = $filters['report_by'] === 'section' ? 'section_id' : 'schedule_id';
        $availableSelections = $filters['report_by'] === 'section' ? $advisorySections : $myClasses;
        $selectedId = $filters[$selectionField] ?? $availableSelections->first()?->id;
        $selectedItem = $availableSelections->firstWhere('id', (int) $selectedId);

        if ($selectedId && !$selectedItem) {
            abort(403, 'You may only view attendance for your assigned sections or class schedules.');
        }
        $filters['section_id'] = $filters['report_by'] === 'section' ? $selectedId : null;
        $filters['schedule_id'] = $filters['report_by'] === 'subject' ? $selectedId : null;
        $filters['status'] = $filters['status'] ?? null;

        $periodDate = match ($filters['period']) {
            'weekly' => (function () use ($periodValue): Carbon {
                preg_match('/^(\d{4})-W(\d{2})$/D', $periodValue, $matches);
                return Carbon::now()->setISODate((int) $matches[1], (int) $matches[2], Carbon::MONDAY)->startOfDay();
            })(),
            'monthly' => Carbon::createFromFormat('!Y-m', $periodValue)->startOfDay(),
            default => Carbon::parse($periodValue)->startOfDay(),
        };
        $filters['report_date'] = $periodDate->toDateString();
        $filters['period_value'] = $periodValue;
        if ($filters['period'] === 'weekly') {
            $periodStart = $periodDate->copy()->startOfWeek(Carbon::MONDAY);
            $periodEnd = $periodStart->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($filters['period'] === 'monthly') {
            $periodStart = $periodDate->copy()->startOfMonth();
            $periodEnd = $periodDate->copy()->endOfMonth();
        } else {
            $periodStart = $periodDate->copy();
            $periodEnd = $periodDate->copy();
        }

        $dates = collect();
        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $dates->push($date->copy());
        }
        $periodLabel = match ($filters['period']) {
            'weekly' => $periodStart->format('M j') . ' – ' . $periodEnd->format('M j, Y'),
            'monthly' => $periodStart->format('F Y'),
            default => $periodDate->format('F j, Y'),
        };

        $students = collect();
        if ($selectedItem) {
            $studentQuery = User::query()
                ->where('role_id', 3)
                ->select(['id', 'id_number', 'first_name', 'last_name', 'section']);

            $sectionId = $filters['report_by'] === 'section'
                ? $selectedItem->id
                : ($selectedItem->section_id ?? null);
            $legacySectionName = $filters['report_by'] === 'section'
                ? $selectedItem->section_name
                : ($selectedItem->academicSection?->section_name ?: $selectedItem->section);

            if ($sectionId && Schema::hasTable('section_student')) {
                $studentQuery->where(function ($scope) use ($sectionId, $legacySectionName, $selectedItem, $filters) {
                    if ($legacySectionName) {
                        $scope->where('section', $legacySectionName);
                    }
                    $scope->orWhereIn('users.id', function ($query) use ($sectionId) {
                        $query->select('student_id')->from('section_student')->where('section_id', $sectionId);
                    });
                    if (Schema::hasTable('attendance_logs')) {
                        $scope->orWhereIn('users.id', function ($query) use ($selectedItem, $filters) {
                            $query->select('user_id')
                                ->from('attendance_logs')
                                ->where('attendance_type', $filters['report_by'] === 'section' ? 'advisory' : 'subject')
                            ->where($filters['report_by'] === 'section' ? 'advisory_section_id' : 'class_schedule_id', $selectedItem->id);
                        });
                    }
                });
            } elseif ($legacySectionName) {
                $studentQuery->where(function ($scope) use ($legacySectionName, $selectedItem, $filters) {
                    $scope->where('section', $legacySectionName);
                    if ($filters['report_by'] === 'subject' && Schema::hasTable('attendance_logs')) {
                        $scope->orWhereIn('users.id', function ($query) use ($selectedItem) {
                            $query->select('user_id')
                                ->from('attendance_logs')
                                ->where('attendance_type', 'subject')
                                ->where('class_schedule_id', $selectedItem->id);
                        });
                    }
                });
            } else {
                $studentQuery->whereRaw('1 = 0');
            }

            $students = $studentQuery->orderBy('last_name')->orderBy('first_name')->get();
        }

        $logs = collect();
        if ($selectedItem) {
            $logsQuery = DB::table('attendance_logs')
                ->join('users', 'attendance_logs.user_id', '=', 'users.id')
                ->where('users.role_id', 3)
                ->where('attendance_logs.attendance_type', $filters['report_by'] === 'section' ? 'advisory' : 'subject')
                ->whereBetween('attendance_logs.attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);

            if ($filters['report_by'] === 'section') {
                $studentIds = $students->pluck('id');
                $logsQuery->where(function ($scope) use ($selectedItem, $studentIds) {
                    $scope->where('attendance_logs.advisory_section_id', $selectedItem->id)
                        ->orWhere(function ($legacy) use ($studentIds) {
                            $legacy->whereNull('attendance_logs.advisory_section_id')
                                ->whereIn('attendance_logs.user_id', $studentIds);
                        });
                });
            } else {
                $logsQuery->where('attendance_logs.class_schedule_id', $selectedItem->id);
            }
            if ($filters['status']) {
                $logsQuery->whereRaw('UPPER(attendance_logs.status) = ?', [$filters['status']]);
            }

            $logs = $logsQuery
                ->select([
                    'attendance_logs.user_id',
                    'attendance_logs.attendance_date',
                    'attendance_logs.time_in',
                    'attendance_logs.time_out',
                    'attendance_logs.status',
                ])
                ->orderBy('attendance_logs.id')
                ->get();
        }

        $logsByStudentDate = $logs->keyBy(
            fn ($log) => $log->user_id . '|' . Carbon::parse($log->attendance_date)->toDateString()
        );
        $reportRows = $students->map(function ($student) use ($dates, $logsByStudentDate) {
            $dailyLogs = [];
            foreach ($dates as $date) {
                $dailyLogs[$date->toDateString()] = $logsByStudentDate->get(
                    $student->id . '|' . $date->toDateString()
                );
            }

            return (object) [
                'student' => $student,
                'logs' => $dailyLogs,
            ];
        });
        if ($filters['status']) {
            $reportRows = $reportRows->filter(
                fn ($row) => collect($row->logs)->contains(
                    fn ($log) => $log && strtoupper((string) $log->status) === $filters['status']
                )
            )->values();
        }

        $summary = [
            'total' => $logs->count(),
            'present' => $logs->filter(fn ($log) => in_array(strtoupper((string) $log->status), ['ON-TIME', 'PRESENT'], true))->count(),
            'late' => $logs->filter(fn ($log) => strtoupper((string) $log->status) === 'LATE')->count(),
            'absent' => $logs->filter(fn ($log) => strtoupper((string) $log->status) === 'ABSENT')->count(),
        ];
        $perPage = (int) ($filters['per_page'] ?? 10);
        $page = max(1, (int) $request->input('page', 1));
        $attendanceRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $reportRows->forPage($page, $perPage)->values(),
            $reportRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $selectedLabel = $filters['report_by'] === 'section'
            ? ($selectedItem ? "Grade {$selectedItem->grade_level} · {$selectedItem->section_name}" : null)
            : ($selectedItem
                ? ($selectedItem->subject_name ?: $selectedItem->subjectRecord?->name ?: $selectedItem->subject_code ?: 'Subject')
                    . ' · ' . ($selectedItem->academicSection?->section_name ?: $selectedItem->section ?: 'Section')
                : null);

        if ($request->ajax()) {
            return view('teacher.partials.attendance-report-results', compact(
                'attendanceRows', 'filters', 'summary', 'dates', 'periodLabel', 'selectedLabel'
            ));
        }

        return view('teacher.reports', compact(
            'teacher', 'myClasses', 'advisorySections', 'attendanceRows', 'filters', 'summary',
            'dates', 'periodLabel', 'selectedLabel'
        ));
    }

    public function attendance(Request $request)
    {
        $teacher = Auth::user();
        $today = Carbon::today()->toDateString();
        $dateFrom = $request->query('date_from', $today);
        $dateTo = $request->query('date_to', $dateFrom);
        $advisoryIds = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('advisor_id', $teacher->id)->pluck('id')
            : collect();
        $scheduleIds = Schema::hasTable('class_schedules')
            ? ClassSchedule::where('teacher_id', $teacher->id)->pluck('id')
            : collect();
        $studentIds = User::where('role_id', 3)->where(function ($query) use ($teacher, $advisoryIds, $scheduleIds) {
            $names = ClassSchedule::whereIn('id', $scheduleIds)->pluck('section')->filter();
            if ($names->isNotEmpty()) $query->whereIn('section', $names);
            if ($advisoryIds->isNotEmpty() && Schema::hasTable('section_student')) {
                $query->orWhereIn('id', DB::table('section_student')->whereIn('section_id', $advisoryIds)->pluck('student_id'));
            }
        })->pluck('id');

        $query = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->whereIn('attendance_logs.user_id', $studentIds->isNotEmpty() ? $studentIds : [0])
            ->whereBetween('attendance_logs.attendance_date', [$dateFrom, $dateTo])
            ->where(function ($scope) use ($scheduleIds, $advisoryIds) {
                $scope->whereIn('attendance_logs.class_schedule_id', $scheduleIds->isNotEmpty() ? $scheduleIds : [0])
                    ->orWhereIn('attendance_logs.advisory_section_id', $advisoryIds->isNotEmpty() ? $advisoryIds : [0]);
            });
        if ($request->filled('status')) $query->where('attendance_logs.status', $request->status);
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($search) use ($term) {
                $search->where('users.first_name', 'like', "%{$term}%")
                    ->orWhere('users.last_name', 'like', "%{$term}%")
                    ->orWhere('users.id_number', 'like', "%{$term}%");
            });
        }
        $baseQuery = clone $query;
        $presentCount = (clone $baseQuery)->whereIn('attendance_logs.status', ['ON-TIME', 'PRESENT', 'Present'])->count();
        $lateCount = (clone $baseQuery)->whereIn('attendance_logs.status', ['LATE', 'Late'])->count();
        $absentCount = (clone $baseQuery)->whereIn('attendance_logs.status', ['ABSENT', 'Absent'])->count();
        $totalScanned = (clone $baseQuery)->count();
        $recentLogs = $query->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.strand', 'users.section')
            ->latest('attendance_logs.attendance_date')->latest('attendance_logs.id')
            ->paginate(15)->withQueryString();

        return view('teacher.attendance', compact('teacher', 'recentLogs', 'presentCount', 'lateCount', 'absentCount', 'totalScanned', 'dateFrom', 'dateTo', 'today'));
    }

    public function evaluationReport(Request $request)
    {
        $teacher = Auth::user();
        $selectedCycleId = $request->query('cycle_id');

        $publishedCycles = collect([]);
        if (Schema::hasTable('teacher_evaluation_publications') && Schema::hasTable('evaluation_cycles')) {
            $cycleIds = DB::table('teacher_evaluation_publications')
                ->where('teacher_id', $teacher->id)
                ->where('is_published', 1)
                ->pluck('evaluation_cycle_id')
                ->toArray();

            $publishedCycles = DB::table('evaluation_cycles')
                ->whereIn('id', $cycleIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $selectedCycle = null;
        if ($selectedCycleId) {
            $selectedCycle = $publishedCycles->firstWhere('id', (int)$selectedCycleId);
        }
        if (!$selectedCycle) {
            $selectedCycle = $publishedCycles->first();
        }

        $peerEvals = collect([]);
        $peerCount = 0;
        $peerAvg = null;
        $descriptor = 'Unpublished / Pending';
        $comments = collect([]);

        if ($selectedCycle && Schema::hasTable('peer_evaluations')) {
            $peerEvals = DB::table('peer_evaluations')
                ->where('evaluatee_id', $teacher->id)
                ->where('evaluation_cycle_id', $selectedCycle->id)
                ->get();

            $peerCount = $peerEvals->count();
            if ($peerCount > 0) {
                $peerAvg = round($peerEvals->avg('average_score'), 2);
                $comments = $peerEvals->whereNotNull('comments')->pluck('comments')->filter()->values();

                if ($peerAvg >= 4.50) {
                    $descriptor = 'Outstanding';
                } elseif ($peerAvg >= 3.50) {
                    $descriptor = 'Very Satisfactory';
                } elseif ($peerAvg >= 2.50) {
                    $descriptor = 'Satisfactory';
                } else {
                    $descriptor = 'Needs Improvement';
                }
            }
        }

        return view('teacher.evaluation-report', compact(
            'teacher', 'publishedCycles', 'selectedCycle', 'peerCount', 'peerAvg', 'descriptor', 'comments'
        ));
    }

    public function classList($scheduleId)
    {
        $teacher = Auth::user();

        $schedule = ClassSchedule::where('id', $scheduleId)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $students = User::where('role_id', 3) 
            ->when(Schema::hasColumn('users', 'section'), function($q) use ($schedule) {
                $q->where('section', $schedule->section);
            })
            ->get();

        return view('teacher.class-list', compact('teacher', 'schedule', 'students'));
    }

    public function evaluationsIndex()
    {
        $teacher = Auth::user();
        $peers = User::where('role_id', 2)->where('id', '!=', $teacher->id)->get();

        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        $evaluationActive = (bool) $activeCycle;
        $evaluationStatusCycle = $activeCycle ?: (Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->orderByDesc('id')->first()
            : null);

        $peerForm = Schema::hasTable('evaluation_forms')
            ? DB::table('evaluation_forms')->where('form_type', 'peer')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        $selfForm = Schema::hasTable('evaluation_forms')
            ? DB::table('evaluation_forms')->where('form_type', 'self')->where('is_active', 1)->orderByDesc('id')->first()
            : null;

        $peerQuestionsQuery = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')->where('form_type', 'peer')->where('is_active', 1)
            : null;
        if ($peerQuestionsQuery && $peerForm && Schema::hasColumn('evaluation_questions', 'form_id')) {
            $peerQuestionsQuery->where('form_id', $peerForm->id);
        }
        $peerQuestions = $peerQuestionsQuery ? $peerQuestionsQuery->orderBy('order_num')->get() : collect([]);
            
        $selfQuestionsQuery = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')->where('form_type', 'self')->where('is_active', 1)
            : null;
        if ($selfQuestionsQuery && $selfForm && Schema::hasColumn('evaluation_questions', 'form_id')) {
            $selfQuestionsQuery->where('form_id', $selfForm->id);
        }
        $selfQuestions = $selfQuestionsQuery ? $selfQuestionsQuery->orderBy('order_num')->get() : collect([]);

        $peerRatingScales = ($peerForm && Schema::hasTable('evaluation_rating_scales'))
            ? DB::table('evaluation_rating_scales')->where('form_id', $peerForm->id)->orderBy('order_num')->get()
            : collect([]);
        $selfRatingScales = ($selfForm && Schema::hasTable('evaluation_rating_scales'))
            ? DB::table('evaluation_rating_scales')->where('form_id', $selfForm->id)->orderBy('order_num')->get()
            : collect([]);

        $loadEvaluationSections = function ($form, $questions) {
            if (!$form || !Schema::hasTable('evaluation_sections')) {
                return $questions->groupBy('category')->map(function ($items, $title) {
                    return (object) ['title' => $title, 'subheadings' => collect(), 'direct_questions' => $items->values()];
                })->values();
            }

            return DB::table('evaluation_sections')
                ->where('form_id', $form->id)
                ->orderBy('order_num')
                ->get()
                ->map(function ($section) use ($questions) {
                    $section->subheadings = Schema::hasTable('evaluation_subheadings')
                        ? DB::table('evaluation_subheadings')->where('section_id', $section->id)->orderBy('order_num')->get()->map(function ($subheading) use ($questions) {
                            $subheading->questions = $questions->where('subheading_id', $subheading->id)->values();
                            return $subheading;
                        })
                        : collect();
                    $section->direct_questions = $questions->where('section_id', $section->id)->whereNull('subheading_id')->values();
                    return $section;
                });
        };

        $peerSections = $loadEvaluationSections($peerForm, $peerQuestions);
        $selfSections = $loadEvaluationSections($selfForm, $selfQuestions);
        $hasPeerLikertQuestions = $peerQuestions->contains(fn ($question) => ($question->type ?? 'likert') === 'likert');

        $evaluatedPeerIds = collect();
        if ($activeCycle && Schema::hasTable('peer_evaluations')) {
            $evaluatedPeerQuery = DB::table('peer_evaluations')->where('evaluator_id', $teacher->id);
            if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
                $evaluatedPeerQuery->where('evaluation_cycle_id', $activeCycle->id);
            }
            $evaluatedPeerIds = $evaluatedPeerQuery->pluck('evaluatee_id');
        }

        $selfEvaluationDone = false;
        if ($evaluationStatusCycle && Schema::hasTable('self_evaluations')) {
            $selfQuery = DB::table('self_evaluations')->where('teacher_id', $teacher->id);
            if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
                $selfQuery->where('evaluation_cycle_id', $evaluationStatusCycle->id);
            }
            $selfEvaluationDone = $selfQuery->exists();
        }

        $peerEvaluationDone = false;
        if ($evaluationStatusCycle && Schema::hasTable('peer_evaluations')) {
            $peerEvaluationDone = DB::table('peer_evaluations')
                ->where('evaluator_id', $teacher->id)
                ->where('evaluation_cycle_id', $evaluationStatusCycle->id)
                ->exists();
        }
        $evaluationCompleted = $peerEvaluationDone || $selfEvaluationDone;
        $allTeacherEvaluationsDone = $peerEvaluationDone && $selfEvaluationDone;
        $publishedEvaluationResult = null;
        if ($evaluationStatusCycle && Schema::hasTable('teacher_evaluation_publications')) {
            $publication = DB::table('teacher_evaluation_publications')
                ->where('evaluation_cycle_id', $evaluationStatusCycle->id)
                ->where('teacher_id', $teacher->id)
                ->where('is_published', 1)
                ->first();

            if ($publication) {
                $weights = [
                    'student' => (float) ($evaluationStatusCycle->student_weight ?? 40),
                    'principal' => (float) ($evaluationStatusCycle->principal_weight ?? 40),
                    'self' => (float) ($evaluationStatusCycle->self_weight ?? 10),
                    'peer' => (float) ($evaluationStatusCycle->peer_weight ?? 10),
                ];
                $averages = ['student' => null, 'principal' => null, 'self' => null, 'peer' => null];

                if (Schema::hasTable('peer_evaluations')) {
                    $averages['peer'] = DB::table('peer_evaluations')
                        ->where('evaluation_cycle_id', $evaluationStatusCycle->id)
                        ->where('evaluatee_id', $teacher->id)
                        ->avg('average_score');
                }
                if (Schema::hasTable('self_evaluations')) {
                    $selfTeacherColumn = Schema::hasColumn('self_evaluations', 'teacher_id') ? 'teacher_id' : 'user_id';
                    $averages['self'] = DB::table('self_evaluations')
                        ->where('evaluation_cycle_id', $evaluationStatusCycle->id)
                        ->where($selfTeacherColumn, $teacher->id)
                        ->avg('average_score');
                }
                if (Schema::hasTable('evaluation_submissions')) {
                    $submissionAverages = DB::table('evaluation_submissions')
                        ->where('evaluation_cycle_id', $evaluationStatusCycle->id)
                        ->where('teacher_id', $teacher->id)
                        ->whereIn('form_type', ['student', 'principal'])
                        ->select('form_type')
                        ->selectRaw('AVG(average_score) as average_score')
                        ->groupBy('form_type')
                        ->get()
                        ->keyBy('form_type');
                    $averages['student'] = optional($submissionAverages->get('student'))->average_score;
                    $averages['principal'] = optional($submissionAverages->get('principal'))->average_score;
                }

                $weightedScore = collect($averages)->reduce(function ($total, $average, $type) use ($weights) {
                    return $total + ($average === null ? 0 : ((float) $average / 5) * $weights[$type]);
                }, 0);

                $publishedEvaluationResult = (object) [
                    'weighted_score' => round($weightedScore, 2),
                    'averages' => $averages,
                    'weights' => $weights,
                    'published_at' => $publication->published_at,
                ];
            }
        }

        return view('teacher.evaluations.adaptive', compact(
            'teacher', 'peers', 'peerQuestions', 'selfQuestions', 'activeCycle',
            'evaluationActive', 'evaluatedPeerIds', 'selfEvaluationDone', 'peerEvaluationDone', 'evaluationCompleted', 'allTeacherEvaluationsDone', 'publishedEvaluationResult',
            'evaluationStatusCycle', 'peerForm', 'selfForm', 'peerRatingScales', 'selfRatingScales', 'peerSections',
            'selfSections', 'hasPeerLikertQuestions'
        ));
    }

    public function storePeerEvaluation(Request $request)
    {
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        if (!$activeCycle) {
            return back()->with('error', 'There is no active faculty evaluation at this time.');
        }

        $request->validate([
            'evaluatee_id' => 'required|exists:users,id',
            'ratings'      => 'required|array',
        ]);

        $likertQuestionIds = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')
                ->where('form_type', 'peer')
                ->when(Schema::hasColumn('evaluation_questions', 'type'), fn ($query) => $query->where('type', 'likert'))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all()
            : [];
        $scores = collect((array) $request->input('ratings', []))
            ->filter(fn ($score, $questionId) => (empty($likertQuestionIds) || in_array((string) $questionId, $likertQuestionIds, true)) && is_numeric($score))
            ->values()
            ->all();
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        $duplicateQuery = DB::table('peer_evaluations')
            ->where('evaluator_id', Auth::id())
            ->where('evaluatee_id', $request->evaluatee_id);
        if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
            $duplicateQuery->where('evaluation_cycle_id', $activeCycle->id);
        }
        if ($duplicateQuery->exists()) {
            return back()->with('error', 'You have already evaluated this faculty member for the active cycle.');
        }

        $evaluationData = [
            'evaluator_id'  => Auth::id(),
            'evaluatee_id'  => $request->evaluatee_id,
            'average_score' => $averageScore,
            'comments'      => $request->input('comments'),
            'answers'       => json_encode($request->input('ratings', [])),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
            $evaluationData['evaluation_cycle_id'] = $activeCycle->id;
        }
        DB::table('peer_evaluations')->insert($evaluationData);

        return back()->with('success', 'Peer evaluation submitted successfully!');
    }

    public function storeSelfEvaluation(Request $request)
    {
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        if (!$activeCycle) {
            return back()->with('error', 'There is no active faculty evaluation at this time.');
        }

        $request->validate([
            'ratings' => 'required|array',
        ]);

        $likertQuestionIds = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')
                ->where('form_type', 'self')
                ->when(Schema::hasColumn('evaluation_questions', 'type'), fn ($query) => $query->where('type', 'likert'))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all()
            : [];
        $scores = collect((array) $request->input('ratings', []))
            ->filter(fn ($score, $questionId) => (empty($likertQuestionIds) || in_array((string) $questionId, $likertQuestionIds, true)) && is_numeric($score))
            ->values()
            ->all();
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        $evaluationData = [
            'teacher_id'    => Auth::id(),
            'average_score' => $averageScore,
            'comments'      => $request->input('comments'),
            'answers'       => json_encode($request->input('ratings', [])),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
            $evaluationData['evaluation_cycle_id'] = $activeCycle->id;
        }
        DB::table('self_evaluations')->insert($evaluationData);

        return back()->with('success', 'Self-evaluation submitted successfully!');
    }

    // CLEAR DAILY SMS LOGS (Professional Version)
    public function clearDailyLogs(Request $request)
    {
        $today = Carbon::today()->toDateString();
        
        // Buburahin lang ang SMS logs ngayong araw para ma-reset ang system queue
        DB::table('sms_logs')->whereDate('created_at', $today)->delete();
        
        return back()->with('success', 'System Notice: Matagumpay na nabura ang mga SMS logs para sa araw na ito. Maaari nang magpadala muli ng mga notification.');
    }
}