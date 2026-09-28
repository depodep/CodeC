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
            $allSectionSchedules = ClassSchedule::with(['subjectRecord', 'teacher'])
                ->orderBy('day')
                ->orderBy('start_time')
                ->get()
                ->map(function ($schedule) use ($teacher) {
                    $sectionName = optional($schedule->academicSection)->section_name ?: $schedule->section;
                    return [
                        'section_id' => $schedule->section_id,
                        'section_name' => $sectionName,
                        'day' => trim($schedule->day ?? $schedule->day_of_week ?? 'Schedule'),
                        'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('g:i A') : 'TBA',
                        'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('g:i A') : 'TBA',
                        'subject' => $schedule->subject_name ?: optional($schedule->subjectRecord)->name ?: $schedule->subject ?: 'Class Schedule',
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
        $today = Carbon::today()->toDateString();

        $activePeriod = Schema::hasTable('academic_periods') 
            ? DB::table('academic_periods')->where('is_active', 1)->first() 
            : null;

        $activeEvaluationCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')
                ->where('status', 'active')
                ->where('is_active', 1)
                ->orderByDesc('id')
                ->first()
            : null;

        $totalStudents = Schema::hasTable('users') 
            ? User::where('role_id', 3)->count() 
            : 0;

        $mySectionsCount = 0;
        if (Schema::hasTable('class_schedules')) {
            $schedCols = Schema::getColumnListing('class_schedules');
            $teacherCol = in_array('teacher_id', $schedCols) ? 'teacher_id' : (in_array('user_id', $schedCols) ? 'user_id' : null);
            
            if ($teacherCol && in_array('section', $schedCols)) {
                $mySectionsCount = ClassSchedule::where($teacherCol, $teacher->id)->distinct('section')->count('section');
            } elseif ($teacherCol) {
                $mySectionsCount = ClassSchedule::where($teacherCol, $teacher->id)->count();
            }
        }

        $presentToday = 0;
        $totalRecords = 0;
        $presentRecords = 0;
        $chartDates = [];
        $chartPresents = [];

        if (Schema::hasTable('attendance_logs')) {
            $attendanceCol = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';

            $presentToday = DB::table('attendance_logs')
                ->whereDate('created_at', $today)
                ->whereIn('status', ['Present', 'PRESENT', 'Late', 'LATE'])
                ->distinct($attendanceCol)
                ->count($attendanceCol);

            $totalRecords = DB::table('attendance_logs')->count();
            $presentRecords = DB::table('attendance_logs')->whereIn('status', ['Present', 'PRESENT', 'Late', 'LATE'])->count();

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $chartDates[] = $date->format('M d'); 
                
                $count = DB::table('attendance_logs')
                    ->whereDate('created_at', $date->toDateString())
                    ->whereIn('status', ['Present', 'PRESENT', 'Late', 'LATE'])
                    ->distinct($attendanceCol)
                    ->count($attendanceCol);
                
                $chartPresents[] = $count;
            }
        }

        $attendanceRate = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0;
        $avgAttendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0;

        return view('teacher.dashboard', compact(
            'teacher', 'activePeriod', 'totalStudents', 'mySectionsCount', 
            'presentToday', 'attendanceRate', 'totalRecords', 
            'presentRecords', 'avgAttendanceRate', 'chartDates', 'chartPresents', 'activeEvaluationCycle'
        ));
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

        $tabs = $tabs->map(function ($tab) use ($baseQuery, $sortOrder, $advisorySectionNames, $advisoryStudentIds, $teacher, $evaluatedStudentIds, $activeEvaluationCycle) {
            $query = clone $baseQuery;
            $students = $query->where('section', $tab['section'])->orderBy('last_name', $sortOrder === 'desc' ? 'desc' : 'asc')->get();

            $attendanceQuery = collect();
            if (Schema::hasTable('attendance_logs') && $students->isNotEmpty()) {
                $attendanceStudentColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
                $attendanceDateColumn = Schema::hasColumn('attendance_logs', 'attendance_date')
                    ? 'attendance_date'
                    : (Schema::hasColumn('attendance_logs', 'scanned_at') ? 'scanned_at' : 'created_at');
                $attendanceBuilder = DB::table('attendance_logs');

                if (Schema::hasColumn('attendance_logs', 'class_schedule_id')) {
                    $attendanceBuilder
                        ->join('class_schedules', 'attendance_logs.class_schedule_id', '=', 'class_schedules.id')
                        ->where('class_schedules.teacher_id', $teacher->id)
                        ->where(function ($query) use ($tab) {
                            if (!empty($tab['schedule_id'])) {
                                $query->where('class_schedules.id', $tab['schedule_id']);
                                return;
                            }

                            $query->where('class_schedules.section', $tab['section']);
                            if (!empty($tab['section_id'])) {
                                $query->orWhere('class_schedules.section_id', $tab['section_id']);
                            }
                        });
                } else {
                    $attendanceBuilder->whereIn('attendance_logs.' . $attendanceStudentColumn, $students->pluck('id'));
                }

                $attendanceQuery = $attendanceBuilder
                    ->whereDate('attendance_logs.' . $attendanceDateColumn, today())
                    ->select('attendance_logs.' . $attendanceStudentColumn . ' as student_id', 'attendance_logs.status')
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
                $student->attendance_status = $attendance
                    ? (in_array(strtolower((string) $attendance->status), ['present', 'late', 'on-time', 'on time']) ? 'Present' : 'Absent')
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

    public function reports()
    {
        $teacher = Auth::user();
        $myClasses = collect([]);
        
        if (Schema::hasTable('class_schedules')) {
            $schedCols = Schema::getColumnListing('class_schedules');
            $teacherCol = in_array('teacher_id', $schedCols) ? 'teacher_id' : (in_array('user_id', $schedCols) ? 'user_id' : null);
            
            if ($teacherCol) {
                $myClasses = ClassSchedule::where($teacherCol, $teacher->id)->get();
            }
        }
        return view('teacher.reports', compact('teacher', 'myClasses'));
    }

    public function attendance(Request $request)
    {
        $teacher = Auth::user();
        $date = $request->query('date', now()->format('Y-m-d'));

        $attendanceLogs = collect([]);
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;

        if (Schema::hasTable('attendance_logs')) {
            
            $attendanceColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'attendance_logs.student_id' : 'attendance_logs.user_id';

            $attendanceLogs = DB::table('attendance_logs')
                ->join('users', $attendanceColumn, '=', 'users.id')
                ->whereDate('attendance_logs.created_at', $date)
                ->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.section')
                ->get();

            $presentCount = $attendanceLogs->where('status', 'Present')->count();
            $lateCount = $attendanceLogs->where('status', 'Late')->count();
            $absentCount = $attendanceLogs->where('status', 'Absent')->count();
        }

        return view('teacher.attendance', compact('teacher', 'attendanceLogs', 'presentCount', 'lateCount', 'absentCount', 'date'));
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