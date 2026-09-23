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
    public function schedule(Request $request)
    {
        $teacher = Auth::user();
        $search = trim((string) $request->query('search'));

        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $teacherCol = in_array('teacher_id', $schedCols) ? 'teacher_id' : (in_array('user_id', $schedCols) ? 'user_id' : null);

        if (!Schema::hasTable('class_schedules') || !$teacherCol) {
            $emptyCollection = collect();
            return view('teacher.schedules', [
                'teacher' => $teacher,
                'mySchedules' => $emptyCollection,
                'schedules' => $emptyCollection,
                'search' => $search
            ]);
        }

        $query = ClassSchedule::with(['subjectRecord', 'academicSection'])
            ->where($teacherCol, $teacher->id);

        if ($search) {
            $query->where(function($q) use ($search, $schedCols) {
                if (in_array('subject_name', $schedCols)) {
                    $q->where('subject_name', 'like', "%{$search}%");
                } elseif (in_array('subject', $schedCols)) {
                    $q->orWhere('subject', 'like', "%{$search}%");
                }
                
                if (in_array('section', $schedCols)) {
                    $q->orWhere('section', 'like', "%{$search}%");
                }

                $q->orWhereHas('subjectRecord', function($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                });
            });
        }

        $mySchedules = $query->get();
        $schedules = $mySchedules; 
        return view('teacher.schedules', compact('teacher', 'mySchedules', 'schedules', 'search'));
    }

    public function index()
    {
        $teacher = Auth::user();
        $today = Carbon::today()->toDateString();

        $activePeriod = Schema::hasTable('academic_periods') 
            ? DB::table('academic_periods')->where('is_active', 1)->first() 
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
            'presentRecords', 'avgAttendanceRate', 'chartDates', 'chartPresents'
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

    public function schoolYears()
    {
        $teacher = Auth::user();
        $schoolYears = collect([]);
        if (Schema::hasTable('academic_periods')) {
            $schoolYears = DB::table('academic_periods')->orderBy('updated_at', 'desc')->get();
        } elseif (Schema::hasTable('school_years')) {
            $schoolYears = DB::table('school_years')->orderBy('start_date', 'desc')->get();
        }

        $sections = collect([]);
        if (Schema::hasTable('academic_sections')) {
             $sortCol = Schema::hasColumn('academic_sections', 'section_name') ? 'section_name' : (Schema::hasColumn('academic_sections', 'name') ? 'name' : 'id');
             $sections = DB::table('academic_sections')->orderBy($sortCol, 'asc')->get();
        } elseif (Schema::hasTable('sections')) {
             $sortCol = Schema::hasColumn('sections', 'section_name') ? 'section_name' : (Schema::hasColumn('sections', 'name') ? 'name' : 'id');
             $sections = DB::table('sections')->orderBy($sortCol, 'asc')->get();
        }

        return view('teacher.school-years', compact('teacher', 'schoolYears', 'sections'));
    }

    public function students(Request $request)
    {
        $teacher = Auth::user();
        $search = $request->query('search');
        $sectionFilter = $request->query('section');
        $sortOrder = $request->query('sort', 'asc'); 

        $sectionsList = collect([]);
        if (Schema::hasTable('academic_sections')) {
            $sectionsList = DB::table('academic_sections')->pluck(Schema::hasColumn('academic_sections', 'section_name') ? 'section_name' : 'name');
        } elseif (Schema::hasTable('sections')) {
            $sectionsList = DB::table('sections')->pluck(Schema::hasColumn('sections', 'section_name') ? 'section_name' : 'name');
        }

        $query = User::where('role_id', 3);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        if ($sectionFilter) {
            if (Schema::hasColumn('users', 'section')) {
                $query->where('section', $sectionFilter);
            }
        }

        $students = $query->orderBy('last_name', $sortOrder)->get();
        $totalStudents = $students->count();

        return view('teacher.students', compact('teacher', 'students', 'sectionsList', 'totalStudents', 'search', 'sectionFilter', 'sortOrder'));
    }

    public function storeStudent(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'id_number'  => ['required', 'string', 'max:255', 'unique:users,id_number'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'gender'     => ['required'],
            'strand'     => ['nullable', 'string', 'max:100'],
            'section'    => ['nullable', 'string', 'max:100'],
            'password'   => ['nullable', 'string', 'min:6'],
        ]);

        User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'id_number'  => $request->id_number,
            'email'      => $request->email,
            'gender'     => $request->gender,
            'strand'     => $request->strand ?? 'STEM',
            'section'    => $request->section ?? 'Amber',
            'role_id'    => 3, 
            'academic_period_id' => Schema::hasTable('academic_periods')
                ? DB::table('academic_periods')->where('is_active', 1)->value('id')
                : null,
            'password'   => Hash::make($request->password ?: 'password123'), 
        ]);

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

        $peerQuestions = Schema::hasTable('evaluation_questions') 
            ? DB::table('evaluation_questions')->where('form_type', 'peer')->get() 
            : collect([]);
            
        $selfQuestions = Schema::hasTable('evaluation_questions') 
            ? DB::table('evaluation_questions')->where('form_type', 'self')->get() 
            : collect([]);

        return view('teacher.evaluations.index', compact('teacher', 'peers', 'peerQuestions', 'selfQuestions'));
    }

    public function storePeerEvaluation(Request $request)
    {
        $request->validate([
            'evaluatee_id' => 'required|exists:users,id',
            'ratings'      => 'required|array',
        ]);

        $scores = array_values($request->ratings);
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        DB::table('peer_evaluations')->insert([
            'evaluator_id'  => Auth::id(),
            'evaluatee_id'  => $request->evaluatee_id,
            'average_score' => $averageScore,
            'comments'      => $request->input('comments'),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return back()->with('success', 'Peer evaluation submitted successfully!');
    }

    public function storeSelfEvaluation(Request $request)
    {
        $request->validate([
            'ratings' => 'required|array',
        ]);

        $scores = array_values($request->ratings);
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        DB::table('self_evaluations')->insert([
            'teacher_id'    => Auth::id(),
            'average_score' => $averageScore,
            'comments'      => $request->input('comments'),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

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