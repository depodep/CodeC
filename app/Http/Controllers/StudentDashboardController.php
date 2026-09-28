<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1. Retrieve Registration Details from users / students / academic_sections
        $gradeLevel = null;
        $strand    = null;
        $section    = null;

        // Check columns directly in the users record
        $gradeLevel = $user->grade_level ?? $user->grade ?? $user->year_level ?? null;
        $strand    = $user->strand ?? $user->track ?? $user->course ?? $user->program ?? null;
        $section    = $user->section ?? $user->section_name ?? null;

        // Check separate students / student_profiles table if it exists
        if ((empty($gradeLevel) || empty($section)) && Schema::hasTable('students')) {
            $studentProfile = DB::table('students')
                ->where('user_id', $user->id)
                ->orWhere('id', $user->id)
                ->first();

            if ($studentProfile) {
                $gradeLevel = $gradeLevel ?? $studentProfile->grade_level ?? $studentProfile->grade ?? $studentProfile->year_level ?? null;
                $strand    = $strand ?? $studentProfile->strand ?? $studentProfile->track ?? $studentProfile->course ?? null;
                $section    = $section ?? $studentProfile->section ?? $studentProfile->section_name ?? null;
                if (!empty($studentProfile->section_id) && empty($user->section_id)) {
                    $user->section_id = $studentProfile->section_id;
                }
            }
        }

        // Check linked academic_sections table via section_id
        if ((empty($section) || empty($gradeLevel)) && !empty($user->section_id) && Schema::hasTable('academic_sections')) {
            $sectionRow = DB::table('academic_sections')->where('id', $user->section_id)->first();
            if ($sectionRow) {
                $section    = $section ?? $sectionRow->name ?? $sectionRow->section_name ?? $sectionRow->section ?? null;
                $gradeLevel = $gradeLevel ?? $sectionRow->grade_level ?? $sectionRow->grade ?? $sectionRow->year_level ?? null;
                $strand    = $strand ?? $sectionRow->strand ?? $sectionRow->track ?? null;
            }
        }

        // Attach resolved registration data back to $student object
        $student = clone $user;
        $student->grade_level = $gradeLevel ?? 'Grade 11';
        $student->strand      = $strand ?? 'Information & Communications Technology';
        $student->section     = $section ?? 'Diamond';

        // 2. Fetch Class Schedules matching the student's section
        $schedules = collect();

        if (Schema::hasTable('class_schedules')) {
            $query = DB::table('class_schedules');

            // Resolve Subjects Table Column Names
            $subjectNameCol = null;
            $subjectCodeCol = null;
            if (Schema::hasTable('subjects')) {
                foreach (['name', 'subject_name', 'title'] as $col) {
                    if (Schema::hasColumn('subjects', $col)) {
                        $subjectNameCol = $col;
                        break;
                    }
                }
                foreach (['code', 'subject_code'] as $col) {
                    if (Schema::hasColumn('subjects', $col)) {
                        $subjectCodeCol = $col;
                        break;
                    }
                }
                $query->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id');
            }

            // Resolve Academic Sections Table Column Names
            $secNameCol = null;
            $secGradeCol = null;
            $secStrandCol = null;
            if (Schema::hasTable('academic_sections')) {
                foreach (['section_name', 'name', 'section', 'title'] as $col) {
                    if (Schema::hasColumn('academic_sections', $col)) {
                        $secNameCol = $col;
                        break;
                    }
                }
                foreach (['grade_level', 'grade', 'year_level'] as $col) {
                    if (Schema::hasColumn('academic_sections', $col)) {
                        $secGradeCol = $col;
                        break;
                    }
                }
                foreach (['strand', 'track_strand', 'track'] as $col) {
                    if (Schema::hasColumn('academic_sections', $col)) {
                        $secStrandCol = $col;
                        break;
                    }
                }
                $query->leftJoin('academic_sections', 'class_schedules.section_id', '=', 'academic_sections.id');
            }

            // Teacher Join
            if (Schema::hasTable('users') && Schema::hasColumn('class_schedules', 'teacher_id')) {
                $query->leftJoin('users as teachers', 'class_schedules.teacher_id', '=', 'teachers.id');
            }

            // Select Columns
            $selects = [
                'class_schedules.id',
                'class_schedules.day as day',
                'class_schedules.start_time',
                'class_schedules.end_time',
            ];

            $selects[] = $subjectNameCol ? "subjects.{$subjectNameCol} as subject_name" : DB::raw("'Unassigned Subject' as subject_name");
            $selects[] = $subjectCodeCol ? "subjects.{$subjectCodeCol} as subject_code" : DB::raw("'NO-CODE' as subject_code");
            $selects[] = $secNameCol ? "academic_sections.{$secNameCol} as section_name" : DB::raw("'Assigned Section' as section_name");

            if (Schema::hasTable('users')) {
                $selects[] = 'teachers.first_name as teacher_first_name';
                $selects[] = 'teachers.last_name as teacher_last_name';
                $selects[] = 'teachers.email as teacher_email';
            }

            $query->select($selects);

            // Filter Schedules by Student Section ID or Name
            if (!empty($student->section_id) && Schema::hasColumn('class_schedules', 'section_id')) {
                $query->where('class_schedules.section_id', $student->section_id);
            } else {
                if (!empty($student->section) && $secNameCol) {
                    $query->where("academic_sections.{$secNameCol}", 'like', '%' . $student->section . '%');
                }
                if (!empty($student->grade_level) && $secGradeCol) {
                    $query->where("academic_sections.{$secGradeCol}", $student->grade_level);
                }
            }

            // Sort by day and time
            if (Schema::hasColumn('class_schedules', 'day_of_week')) {
                $query->orderByRaw("
                    CASE class_schedules.day_of_week 
                        WHEN 'Monday' THEN 1 
                        WHEN 'Tuesday' THEN 2 
                        WHEN 'Wednesday' THEN 3 
                        WHEN 'Thursday' THEN 4 
                        WHEN 'Friday' THEN 5 
                        WHEN 'Saturday' THEN 6 
                        WHEN 'Sunday' THEN 7 
                        WHEN 'Mon / Wed' THEN 8 
                        WHEN 'Tue / Thu' THEN 9 
                        ELSE 10 
                    END ASC
                ");
            }
            if (Schema::hasColumn('class_schedules', 'start_time')) {
                $query->orderBy('class_schedules.start_time', 'asc');
            }

            $schedules = $query->get()->map(function ($schedule) {
                $schedule->teacher = !empty($schedule->teacher_first_name) ? (object)[
                    'first_name' => $schedule->teacher_first_name,
                    'last_name'  => $schedule->teacher_last_name,
                    'email'      => $schedule->teacher_email,
                ] : null;

                $schedule->time_slot = (!empty($schedule->start_time) && !empty($schedule->end_time))
                    ? date('g:i A', strtotime($schedule->start_time)) . ' - ' . date('g:i A', strtotime($schedule->end_time))
                    : 'Schedule TBA';

                return $schedule;
            });
        }

        // Attendance summary for the dashboard.
        $attendanceTotal = 0;
        $attendancePresent = 0;
        $attendanceRate = 0;
        $subjectAttendance = collect();
        if (Schema::hasTable('attendance_logs')) {
            $attendanceStudentColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
            $attendanceDateColumn = Schema::hasColumn('attendance_logs', 'attendance_date')
                ? 'attendance_date'
                : (Schema::hasColumn('attendance_logs', 'scanned_at') ? 'scanned_at' : 'created_at');
            $studentLogs = DB::table('attendance_logs')->where($attendanceStudentColumn, $student->id)->get();
            $attendanceTotal = $studentLogs->count();
            $attendancePresent = $studentLogs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['present', 'on-time', 'on time', 'late']))->count();
            $attendanceRate = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100, 1) : 0;

            if (Schema::hasColumn('attendance_logs', 'class_schedule_id') && $schedules->isNotEmpty()) {
                $scheduleNames = $schedules->keyBy('id')->map(fn ($schedule) => $schedule->subject_name ?: 'Unassigned Subject');
                $subjectAttendance = $studentLogs
                    ->filter(fn ($log) => !empty($log->class_schedule_id) && $scheduleNames->has($log->class_schedule_id))
                    ->groupBy('class_schedule_id')
                    ->map(function ($logs, $scheduleId) use ($scheduleNames) {
                        $total = $logs->count();
                        $present = $logs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['present', 'on-time', 'on time', 'late']))->count();
                        return (object) [
                            'subject_name' => $scheduleNames->get($scheduleId),
                            'present' => $present,
                            'total' => $total,
                            'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                        ];
                    })->values();
            }

            if ($subjectAttendance->isEmpty() && $schedules->isNotEmpty()) {
                $subjectAttendance = $schedules->unique('subject_name')->map(fn ($schedule) => (object) [
                    'subject_name' => $schedule->subject_name ?: 'Unassigned Subject',
                    'present' => 0,
                    'total' => 0,
                    'rate' => 0,
                ])->values();
            }
        }

        // 3. Faculty Evaluation Status Check (Safe & Guarded)
        $isEvaluationOpen = Cache::get('evaluations_open', false);
        $activeCycle = null;
        
        if (Schema::hasTable('evaluation_cycles')) {
            $activeCycle = DB::table('evaluation_cycles')
                ->where('status', 'active')
                ->where('is_active', 1)
                ->first();
            
            if ($activeCycle) {
                $isEvaluationOpen = true;
            }
        }

        if (!$isEvaluationOpen && Schema::hasTable('academic_periods') && Schema::hasColumn('academic_periods', 'status')) {
            $periodStatus = DB::table('academic_periods')->where('is_active', 1)->value('status');
            $isEvaluationOpen = in_array(strtolower((string)$periodStatus), ['open', 'active', '1', 'true']);
        }

        return view('student.dashboard', compact(
            'student', 'schedules', 'isEvaluationOpen', 'activeCycle',
            'attendanceTotal', 'attendancePresent', 'attendanceRate', 'subjectAttendance'
        ));
    }

    // ==========================================
    // STUDENT EVALUATION METHODS (WITH DUPLICATE CHECK)
    // ==========================================

    public function evaluationsIndex()
    {
        $facultyMembers = User::where('role_id', 2)->orderBy('last_name', 'asc')->get();
        
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;

        $cycleId = $activeCycle->id ?? null;

        // Kunin ang mga IDs ng guro na nasagutan na ng kasalukuyang estudyante para sa active cycle
        $evaluatedQuery = DB::table('evaluation_submissions')
            ->where('form_type', 'student')
            ->where('student_id', auth()->id());
        if ($cycleId) {
            $evaluatedQuery->where('evaluation_cycle_id', $cycleId);
        }
        $evaluatedTeacherIds = $evaluatedQuery->pluck('teacher_id')->toArray();

        return view('student.evaluations.index', compact('facultyMembers', 'evaluatedTeacherIds', 'activeCycle'));
    }

    public function takeEvaluation($teacherId)
    {
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;

        $cycleId = $activeCycle->id ?? null;

        $alreadyQuery = DB::table('evaluation_submissions')
            ->where('form_type', 'student')
            ->where('student_id', auth()->id())
            ->where('teacher_id', $teacherId);

        if ($cycleId) {
            $alreadyQuery->where('evaluation_cycle_id', $cycleId);
        }

        if ($alreadyQuery->exists()) {
            return redirect()->route('student.evaluations.index')
                ->with('error', 'You have already evaluated this instructor for this evaluation cycle.');
        }

        $teacher = User::where('id', $teacherId)->where('role_id', 2)->firstOrFail();
        
        $questions = DB::table('evaluation_questions')
            ->where('form_type', 'student')
            ->where('is_active', 1)
            ->orderBy('order_num', 'asc')
            ->get();

        $groupedQuestions = $questions->groupBy('category');

        return view('student.evaluations.take', compact('teacher', 'groupedQuestions', 'activeCycle'));
    }

    public function storeEvaluation(Request $request)
    {
        $request->validate([
            'evaluatee_id'   => 'required|exists:users,id',
            'scores'         => 'nullable|array',
            'scores.*'       => 'nullable|integer',
            'text_responses' => 'nullable|array',
            'comments'       => 'nullable|string|max:1000',
        ]);

        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;

        $cycleId = $activeCycle->id ?? null;

        $alreadyQuery = DB::table('evaluation_submissions')
            ->where('form_type', 'student')
            ->where('student_id', auth()->id())
            ->where('teacher_id', $request->evaluatee_id);

        if ($cycleId) {
            $alreadyQuery->where('evaluation_cycle_id', $cycleId);
        }

        if ($alreadyQuery->exists()) {
            return redirect()->route('student.evaluations.index')
                ->with('error', 'You have already submitted an evaluation for this instructor for this cycle.');
        }

        $scores = array_filter((array) $request->input('scores', []), 'is_numeric');
        $averageScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0;

        DB::table('evaluation_submissions')->insert([
            'evaluation_cycle_id' => $cycleId,
            'academic_period_id'  => Schema::hasTable('academic_periods')
                ? DB::table('academic_periods')->where('is_active', 1)->value('id')
                : null,
            'form_type'           => 'student',
            'student_id'          => auth()->id(),
            'evaluator_id'        => auth()->id(),
            'teacher_id'          => $request->evaluatee_id,
            'average_score'       => $averageScore,
            'status'              => 'SUBMITTED',
            'comments'            => $request->input('comments'),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('student.evaluations.index')
            ->with('success', 'Faculty evaluation successfully submitted!');
    }
}