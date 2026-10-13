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
        $gradeLevel = $user->grade_level ?? $user->grade ?? $user->year_level ?? null;
        $strand    = $user->strand ?? $user->track ?? $user->course ?? $user->program ?? null;
        $section    = $user->section ?? $user->section_name ?? null;
        $sectionId  = $user->section_id ?? null;

        // Check pivot section_student first if user->section_id is empty
        if (empty($sectionId) && Schema::hasTable('section_student')) {
            $pivotSecId = DB::table('section_student')
                ->where('student_id', $user->id)
                ->orderBy('id', 'desc')
                ->value('section_id');
            if ($pivotSecId) {
                $sectionId = $pivotSecId;
            }
        }

        // Check separate students / student_profiles table if it exists
        if ((empty($gradeLevel) || empty($section) || empty($sectionId)) && Schema::hasTable('students')) {
            $studentProfile = DB::table('students')
                ->where('user_id', $user->id)
                ->orWhere('id', $user->id)
                ->first();

            if ($studentProfile) {
                $gradeLevel = $gradeLevel ?? $studentProfile->grade_level ?? $studentProfile->grade ?? $studentProfile->year_level ?? null;
                $strand    = $strand ?? $studentProfile->strand ?? $studentProfile->track ?? $studentProfile->course ?? null;
                $section    = $section ?? $studentProfile->section ?? $studentProfile->section_name ?? null;
                $sectionId  = $sectionId ?? $studentProfile->section_id ?? null;
            }
        }

        // Check linked academic_sections table via section_id or name
        $advisor = null;
        $academicSection = null;
        if (Schema::hasTable('academic_sections')) {
            if (!empty($sectionId)) {
                $academicSection = DB::table('academic_sections')->where('id', $sectionId)->first();
            } elseif (!empty($section)) {
                $academicSection = DB::table('academic_sections')->where('section_name', $section)->first();
            }

            if ($academicSection) {
                $sectionId  = $academicSection->id;
                $section    = $academicSection->section_name ?? $section;
                $gradeLevel = $gradeLevel ?? $academicSection->grade_level ?? null;
                $strand    = $strand ?? $academicSection->strand ?? null;

                if (!empty($academicSection->advisor_id)) {
                    $advisor = DB::table('users')->where('id', $academicSection->advisor_id)->first();
                }
            }
        }

        // Attach resolved registration data back to $student object
        $student = clone $user;
        $student->grade_level = $gradeLevel ?? 'Grade 11';
        $student->strand      = $strand ?? 'Information & Communications Technology';
        $student->section     = $section ?? 'Diamond';
        $student->section_id  = $sectionId;

        // 2. Fetch Class Schedules matching the student's section
        $schedules = $this->fetchStudentSchedules($student);

        // 3. Attendance Statistics (Overall + Per Subject)
        $attendanceTotal = 0;
        $attendancePresent = 0;
        $attendanceLate = 0;
        $attendanceAbsent = 0;
        $attendanceRate = 0;
        $subjectAttendance = collect();

        if (Schema::hasTable('attendance_logs')) {
            $attendanceStudentColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
            $studentLogs = DB::table('attendance_logs')->where($attendanceStudentColumn, $student->id)->get();
            $attendanceTotal = $studentLogs->count();
            
            $attendancePresent = $studentLogs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['present', 'on-time', 'on time']))->count();
            $attendanceLate = $studentLogs->filter(fn ($log) => strtolower((string) $log->status) === 'late')->count();
            $attendanceAbsent = $studentLogs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['absent', 'unexcused', 'excused']))->count();

            // Overall attendance rate: (Present + Late) / Total
            $effectiveAttended = $attendancePresent + $attendanceLate;
            $attendanceRate = $attendanceTotal > 0 ? round(($effectiveAttended / $attendanceTotal) * 100, 1) : 0;

            // Attendance per Subject & Number of Absents per Subject
            if (Schema::hasColumn('attendance_logs', 'class_schedule_id') && $schedules->isNotEmpty()) {
                $scheduleMap = $schedules->keyBy('id');
                $subjectAttendance = $schedules->map(function ($sched) use ($studentLogs) {
                    $schedLogs = $studentLogs->where('class_schedule_id', $sched->id);
                    $subTotal = $schedLogs->count();
                    $subPresent = $schedLogs->filter(fn ($l) => in_array(strtolower((string) $l->status), ['present', 'on-time', 'on time', 'late']))->count();
                    $subAbsent = $schedLogs->filter(fn ($l) => in_array(strtolower((string) $l->status), ['absent', 'unexcused', 'excused']))->count();
                    $subRate = $subTotal > 0 ? round(($subPresent / $subTotal) * 100, 1) : null;

                    return (object) [
                        'schedule_id'  => $sched->id,
                        'subject_name' => $sched->subject_name,
                        'subject_code' => $sched->subject_code,
                        'teacher_name' => $sched->teacher ? $sched->teacher->first_name . ' ' . $sched->teacher->last_name : 'TBA',
                        'time_slot'    => $sched->time_slot,
                        'day'          => $sched->day,
                        'present'      => $subPresent,
                        'absent'       => $subAbsent,
                        'total'        => $subTotal,
                        'rate'         => $subRate,
                    ];
                });
            } else {
                $subjectAttendance = $schedules->map(fn ($sched) => (object) [
                    'schedule_id'  => $sched->id,
                    'subject_name' => $sched->subject_name,
                    'subject_code' => $sched->subject_code,
                    'teacher_name' => $sched->teacher ? $sched->teacher->first_name . ' ' . $sched->teacher->last_name : 'TBA',
                    'time_slot'    => $sched->time_slot,
                    'day'          => $sched->day,
                    'present'      => 0,
                    'absent'       => 0,
                    'total'        => 0,
                    'rate'         => null,
                ]);
            }
        }

        // 4. Faculty Evaluation Status Check
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;

        $isEvaluationOpen = (bool) $activeCycle || Cache::get('evaluations_open', false);

        // Check if student has pending evaluations
        $assignedTeachers = $schedules->pluck('teacher')->filter()->unique('id')->values();
        $assignedTeacherIds = $assignedTeachers->pluck('id')->toArray();

        // If no direct subject teachers mapped, fallback to all faculty
        if (empty($assignedTeacherIds)) {
            $assignedTeacherIds = User::where('role_id', 2)->pluck('id')->toArray();
        }

        $evaluatedTeacherIds = [];
        if ($activeCycle && Schema::hasTable('evaluation_submissions')) {
            $evaluatedTeacherIds = DB::table('evaluation_submissions')
                ->where('form_type', 'student')
                ->where('student_id', $user->id)
                ->where('evaluation_cycle_id', $activeCycle->id)
                ->pluck('teacher_id')
                ->toArray();
        }

        $pendingTeacherIds = array_diff($assignedTeacherIds, $evaluatedTeacherIds);
        $totalToEvaluate = count($assignedTeacherIds);
        $totalEvaluated = count(array_intersect($assignedTeacherIds, $evaluatedTeacherIds));
        $allEvaluatedDone = ($totalToEvaluate > 0 && empty($pendingTeacherIds));

        // Time slots for timetable grid
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $timeSlots = [
            '07:00 AM - 08:00 AM',
            '08:00 AM - 09:00 AM',
            '09:00 AM - 10:00 AM',
            '10:00 AM - 11:00 AM',
            '11:00 AM - 12:00 PM',
            '12:00 PM - 01:00 PM',
            '01:00 PM - 02:00 PM',
            '02:00 PM - 03:00 PM',
            '03:00 PM - 04:00 PM',
            '04:00 PM - 05:00 PM',
        ];

        return view('student.dashboard', compact(
            'student', 'advisor', 'academicSection', 'schedules',
            'attendanceTotal', 'attendancePresent', 'attendanceLate', 'attendanceAbsent', 'attendanceRate', 'subjectAttendance',
            'isEvaluationOpen', 'activeCycle', 'totalToEvaluate', 'totalEvaluated', 'allEvaluatedDone',
            'weekdays', 'timeSlots'
        ));
    }

    public function schedules()
    {
        $user = auth()->user();
        $student = clone $user;
        $student->grade_level = $user->grade_level ?? 'Grade 11';
        $student->strand      = $user->strand ?? 'Information & Communications Technology';
        $student->section     = $user->section ?? 'Diamond';
        $student->section_id  = $user->section_id ?? null;

        if (empty($student->section_id) && Schema::hasTable('section_student')) {
            $student->section_id = DB::table('section_student')
                ->where('student_id', $user->id)
                ->orderBy('id', 'desc')
                ->value('section_id');
        }

        $advisor = null;
        if (Schema::hasTable('academic_sections')) {
            $academicSection = !empty($student->section_id)
                ? DB::table('academic_sections')->where('id', $student->section_id)->first()
                : DB::table('academic_sections')->where('section_name', $student->section)->first();

            if ($academicSection) {
                $student->section = $academicSection->section_name;
                $student->section_id = $academicSection->id;
                if (!empty($academicSection->advisor_id)) {
                    $advisor = DB::table('users')->where('id', $academicSection->advisor_id)->first();
                }
            }
        }

        $schedules = $this->fetchStudentSchedules($student);

        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $timeSlots = [
            '07:00 AM - 08:00 AM',
            '08:00 AM - 09:00 AM',
            '09:00 AM - 10:00 AM',
            '10:00 AM - 11:00 AM',
            '11:00 AM - 12:00 PM',
            '12:00 PM - 01:00 PM',
            '01:00 PM - 02:00 PM',
            '02:00 PM - 03:00 PM',
            '03:00 PM - 04:00 PM',
            '04:00 PM - 05:00 PM',
        ];

        return view('student.dashboard', compact(
            'student', 'advisor', 'schedules', 'weekdays', 'timeSlots'
        ));
    }

    public function attendance()
    {
        $user = auth()->user();
        $student = clone $user;
        $student->grade_level = $user->grade_level ?? 'Grade 11';
        $student->section     = $user->section ?? 'Diamond';

        $studentLogs = collect();
        $attendanceTotal = 0;
        $attendancePresent = 0;
        $attendanceLate = 0;
        $attendanceAbsent = 0;
        $attendanceRate = 0;

        if (Schema::hasTable('attendance_logs')) {
            $attendanceStudentColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
            $studentLogs = DB::table('attendance_logs')
                ->where($attendanceStudentColumn, $student->id)
                ->orderBy('attendance_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $attendanceTotal = $studentLogs->count();
            $attendancePresent = $studentLogs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['present', 'on-time', 'on time']))->count();
            $attendanceLate = $studentLogs->filter(fn ($log) => strtolower((string) $log->status) === 'late')->count();
            $attendanceAbsent = $studentLogs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['absent', 'unexcused', 'excused']))->count();
            $attendanceRate = $attendanceTotal > 0 ? round((($attendancePresent + $attendanceLate) / $attendanceTotal) * 100, 1) : 0;
        }

        return view('student.attendance', compact(
            'student', 'studentLogs', 'attendanceTotal', 'attendancePresent', 'attendanceLate', 'attendanceAbsent', 'attendanceRate'
        ));
    }

    private function fetchStudentSchedules($student)
    {
        $schedules = collect();
        if (!Schema::hasTable('class_schedules')) {
            return $schedules;
        }

        $query = DB::table('class_schedules');

        // Subjects join
        if (Schema::hasTable('subjects')) {
            $query->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id');
        }

        // Academic sections join
        if (Schema::hasTable('academic_sections')) {
            $query->leftJoin('academic_sections', 'class_schedules.section_id', '=', 'academic_sections.id');
        }

        // Teachers join
        if (Schema::hasTable('users') && Schema::hasColumn('class_schedules', 'teacher_id')) {
            $query->leftJoin('users as teachers', 'class_schedules.teacher_id', '=', 'teachers.id');
        }

        $query->select([
            'class_schedules.id',
            'class_schedules.section_id',
            'class_schedules.subject_id',
            'class_schedules.teacher_id',
            'class_schedules.day',
            'class_schedules.start_time',
            'class_schedules.end_time',
            DB::raw("COALESCE(subjects.name, class_schedules.subject_name, 'Unassigned Subject') as subject_name"),
            DB::raw("COALESCE(subjects.code, class_schedules.subject_code, 'NO-CODE') as subject_code"),
            DB::raw("COALESCE(academic_sections.section_name, class_schedules.section, 'Section') as section_name"),
            'teachers.first_name as teacher_first_name',
            'teachers.last_name as teacher_last_name',
            'teachers.email as teacher_email',
        ]);

        // Filter: By Section ID first, fallback to section name
        if (!empty($student->section_id)) {
            $query->where(function ($q) use ($student) {
                $q->where('class_schedules.section_id', $student->section_id)
                  ->orWhere('class_schedules.section', $student->section);
            });
        } elseif (!empty($student->section)) {
            $query->where(function ($q) use ($student) {
                $q->where('class_schedules.section', $student->section);
                if (Schema::hasTable('academic_sections')) {
                    $q->orWhere('academic_sections.section_name', $student->section);
                }
            });
        }

        // Sort by day of week then time
        $query->orderByRaw("
            CASE LOWER(TRIM(class_schedules.day))
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
                WHEN 'sunday' THEN 7
                ELSE 8
            END ASC
        ")->orderBy('class_schedules.start_time', 'asc');

        return $query->get()->map(function ($s) {
            $s->teacher = !empty($s->teacher_first_name) ? (object)[
                'id'         => $s->teacher_id,
                'first_name' => $s->teacher_first_name,
                'last_name'  => $s->teacher_last_name,
                'email'      => $s->teacher_email,
            ] : null;

            $s->time_slot = (!empty($s->start_time) && !empty($s->end_time))
                ? date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time))
                : 'Schedule TBA';

            return $s;
        });
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
            'answers'             => json_encode($scores),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('student.evaluations.index')
            ->with('success', 'Faculty evaluation successfully submitted!');
    }
}