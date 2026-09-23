<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Models\AcademicSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AdminScheduleController extends Controller
{
    public function index(Request $request)
    {
        $teachers = User::where(function ($q) {
            $cols = Schema::getColumnListing('users');
            if (in_array('role_id', $cols)) $q->where('role_id', 2);
            if (in_array('role', $cols)) {
                $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
            }
        })->orderBy('first_name')->get();

        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $sectionCol = in_array('section_id', $schedCols) ? 'section_id' : (in_array('section', $schedCols) ? 'section' : null);
        $subjectCol = in_array('subject_name', $schedCols) ? 'subject_name' : (in_array('subject_id', $schedCols) ? 'subject_id' : null);

        $query = ClassSchedule::with(['teacher', 'subjectRecord', 'academicSection'])->latest('id');

        if (Schema::hasColumn('class_schedules', 'academic_period_id')) {
            $activePeriodId = Schema::hasTable('academic_periods')
                ? DB::table('academic_periods')->where('is_active', 1)->value('id')
                : null;
            if ($activePeriodId) {
                $query->where('academic_period_id', $activePeriodId);
            }
        }

        if ($request->filled('grade_level') && in_array('grade_level', $schedCols)) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->filled('strand') && in_array('strand', $schedCols)) {
            $query->where('strand', $request->strand);
        }

        if ($request->filled('section') && $sectionCol) {
            $query->where($sectionCol, $request->section);
        }

        $dayFilterCol = in_array('day_of_week', $schedCols) ? 'day_of_week' : (in_array('day', $schedCols) ? 'day' : null);
        if ($request->filled('day') && $dayFilterCol) {
            $query->where($dayFilterCol, $request->day);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search, $schedCols, $sectionCol, $subjectCol) {
                if ($subjectCol) {
                    $q->where($subjectCol, 'like', "%{$search}%");
                }
                if (in_array('subject_code', $schedCols)) {
                    $q->orWhere('subject_code', 'like', "%{$search}%");
                }
                if ($sectionCol) {
                    $q->orWhere($sectionCol, 'like', "%{$search}%");
                }
                $q->orWhereHas('teacher', function ($t) use ($search) {
                    $t->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        }

        $allSchedules = (clone $query)->get();
        $schedules = $query->paginate(15);

        if (class_exists(AcademicSection::class) && Schema::hasTable('academic_sections')) {
            $totalSections = AcademicSection::count();
        } elseif ($sectionCol) {
            $totalSections = ClassSchedule::distinct($sectionCol)->count($sectionCol);
        } else {
            $totalSections = 0;
        }

        $totalSubjects = $subjectCol ? ClassSchedule::distinct($subjectCol)->count($subjectCol) : 0;
        $assignedFacultyCount = in_array('teacher_id', $schedCols) 
            ? ClassSchedule::whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id') 
            : 0;

        $viewMode = $request->get('view', 'grid');

        return view('admin.schedules.index', compact(
            'schedules',
            'allSchedules',
            'teachers',
            'totalSections',
            'totalSubjects',
            'assignedFacultyCount',
            'viewMode'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'subject_code' => ['nullable', 'string', 'max:50'],
            'teacher_id'   => ['required', 'exists:users,id'],
            'grade_level'  => ['required', 'string', 'max:50'],
            'strand'       => ['nullable', 'string', 'max:50'],
            'section'      => ['required', 'string', 'max:100'],
            'start_time'   => ['required'],
            'end_time'     => ['required'],
        ]);

        // Process Days array or string
        $days = [];
        if ($request->has('days') && is_array($request->days) && count($request->days) > 0) {
            $days = array_filter(array_map('trim', $request->days));
        } elseif ($request->filled('day')) {
            if (is_array($request->day)) {
                $days = $request->day;
            } elseif (str_contains($request->day, ',')) {
                $days = array_filter(array_map('trim', explode(',', $request->day)));
            } else {
                $days = [trim($request->day)];
            }
        }

        if (empty($days)) {
            return back()->withErrors(['days' => 'Please select at least one day for the class schedule.'])->withInput();
        }

        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $dayColName = in_array('day_of_week', $schedCols) ? 'day_of_week' : 'day';

        // --- ACTIVE SCHOOL YEAR / ACADEMIC PERIOD BINDING ---
        $activePeriodId = null;
        if (Schema::hasTable('academic_periods')) {
            $activePeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id') 
                        ?? DB::table('academic_periods')->value('id');
        }

        // --- SECTION BINDING ---
        $sectionRecord = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('section_name', $request->section)->first()
            : null;
        $sectionId = $sectionRecord ? $sectionRecord->id : null;

        // --- TEACHER & SECTION CONFLICT VERIFICATION ---
        foreach ($days as $dayItem) {
            // 1. Teacher Conflict Check
            $teacherConflictQuery = ClassSchedule::where('teacher_id', $request->teacher_id)
                ->where($dayColName, $dayItem)
                ->where(function ($query) use ($request) {
                    $query->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                });

            if (in_array('academic_period_id', $schedCols) && $activePeriodId) {
                $teacherConflictQuery->where('academic_period_id', $activePeriodId);
            }

            $teacherConflict = $teacherConflictQuery->first();
            if ($teacherConflict) {
                $teacher = User::find($request->teacher_id);
                $teacherName = $teacher ? ($teacher->first_name . ' ' . $teacher->last_name) : 'The assigned faculty';
                $sTime = date('g:i A', strtotime($teacherConflict->start_time));
                $eTime = date('g:i A', strtotime($teacherConflict->end_time));
                return back()->withErrors([
                    'teacher_id' => "Faculty Schedule Conflict: {$teacherName} already has a class ({$teacherConflict->subject_name}) scheduled on {$dayItem} from {$sTime} to {$eTime}."
                ])->withInput();
            }

            // 2. Section Conflict Check
            $sectionConflictQuery = ClassSchedule::where(function ($q) use ($request, $sectionId) {
                    $q->where('section', $request->section);
                    if ($sectionId) {
                        $q->orWhere('section_id', $sectionId);
                    }
                })
                ->where($dayColName, $dayItem)
                ->where(function ($query) use ($request) {
                    $query->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                });

            if (in_array('academic_period_id', $schedCols) && $activePeriodId) {
                $sectionConflictQuery->where('academic_period_id', $activePeriodId);
            }

            $sectionConflict = $sectionConflictQuery->first();
            if ($sectionConflict) {
                $sTime = date('g:i A', strtotime($sectionConflict->start_time));
                $eTime = date('g:i A', strtotime($sectionConflict->end_time));
                return back()->withErrors([
                    'section' => "Section Schedule Conflict: Section {$request->section} already has a class ({$sectionConflict->subject_name}) scheduled on {$dayItem} from {$sTime} to {$eTime}."
                ])->withInput();
            }
        }

        // --- SUBJECT BINDING ---
        $subjectId = null;
        if (in_array('subject_id', $schedCols) && Schema::hasTable('subjects')) {
            $subCols = Schema::getColumnListing('subjects');
            $nameCol = in_array('name', $subCols) ? 'name' : (in_array('subject_name', $subCols) ? 'subject_name' : null);
            if ($nameCol) {
                $existingSub = DB::table('subjects')->where($nameCol, $request->subject_name)->first();
                if ($existingSub) {
                    $subjectId = $existingSub->id;
                } else {
                    $insertData = [$nameCol => $request->subject_name];
                    $generatedCode = $request->subject_code ?? 'SUBJ-' . rand(100, 999);
                    if (in_array('code', $subCols)) $insertData['code'] = $generatedCode;
                    if (in_array('subject_code', $subCols)) $insertData['subject_code'] = $generatedCode;
                    if (in_array('created_at', $subCols)) $insertData['created_at'] = now();
                    if (in_array('updated_at', $subCols)) $insertData['updated_at'] = now();
                    $subjectId = DB::table('subjects')->insertGetId($insertData);
                }
            }
        }

        // --- CREATE SCHEDULE RECORDS FOR EACH SELECTED DAY ---
        $createdCount = 0;
        foreach ($days as $dayItem) {
            $data = [];
            if (in_array('academic_period_id', $schedCols)) $data['academic_period_id'] = $activePeriodId ?? 1;
            if (in_array('section_id', $schedCols)) $data['section_id'] = $sectionId ?? 1;
            if (in_array('subject_id', $schedCols) && $subjectId) $data['subject_id'] = $subjectId;

            if (in_array('subject_name', $schedCols)) $data['subject_name'] = $request->subject_name;
            elseif (in_array('subject', $schedCols)) $data['subject'] = $request->subject_name;

            if (in_array('day_of_week', $schedCols)) $data['day_of_week'] = $dayItem;
            elseif (in_array('day', $schedCols)) $data['day'] = $dayItem;

            $mappings = [
                'subject_code' => $request->subject_code,
                'teacher_id'   => $request->teacher_id,
                'grade_level'  => $request->grade_level,
                'strand'       => $request->strand,
                'section'      => $request->section,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
            ];

            foreach ($mappings as $col => $val) {
                if (in_array($col, $schedCols)) {
                    $data[$col] = $val;
                }
            }

            ClassSchedule::create($data);
            $createdCount++;
        }

        $msg = $createdCount > 1 
            ? "Class schedules created successfully for {$createdCount} selected days!" 
            : 'Class schedule created successfully!';

        return redirect()->route('admin.schedules.index')->with('success', $msg);
    }

    public function update(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'subject_code' => ['nullable', 'string', 'max:50'],
            'teacher_id'   => ['required', 'exists:users,id'],
            'grade_level'  => ['required', 'string', 'max:50'],
            'strand'       => ['nullable', 'string', 'max:50'],
            'section'      => ['required', 'string', 'max:100'],
            'start_time'   => ['required'],
            'end_time'     => ['required'],
        ]);

        $days = [];
        if ($request->has('days') && is_array($request->days) && count($request->days) > 0) {
            $days = array_filter(array_map('trim', $request->days));
        } elseif ($request->filled('day')) {
            $days = [trim($request->day)];
        } else {
            $days = [$schedule->day ?? $schedule->day_of_week ?? 'Monday'];
        }

        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $dayColName = in_array('day_of_week', $schedCols) ? 'day_of_week' : 'day';

        $activePeriodId = null;
        if (Schema::hasTable('academic_periods')) {
            $activePeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id') 
                        ?? DB::table('academic_periods')->value('id');
        }

        $sectionRecord = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('section_name', $request->section)->first()
            : null;
        $sectionId = $sectionRecord ? $sectionRecord->id : null;

        // Conflict check for primary selected day
        $targetDay = $days[0];

        // 1. Teacher Conflict
        $teacherConflictQuery = ClassSchedule::where('teacher_id', $request->teacher_id)
            ->where('id', '!=', $id)
            ->where($dayColName, $targetDay)
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
            });

        if (in_array('academic_period_id', $schedCols) && $activePeriodId) {
            $teacherConflictQuery->where('academic_period_id', $activePeriodId);
        }

        $teacherConflict = $teacherConflictQuery->first();
        if ($teacherConflict) {
            $teacher = User::find($request->teacher_id);
            $teacherName = $teacher ? ($teacher->first_name . ' ' . $teacher->last_name) : 'Faculty member';
            $sTime = date('g:i A', strtotime($teacherConflict->start_time));
            $eTime = date('g:i A', strtotime($teacherConflict->end_time));
            return back()->withErrors([
                'teacher_id' => "Faculty Schedule Conflict: {$teacherName} already has a class ({$teacherConflict->subject_name}) scheduled on {$targetDay} from {$sTime} to {$eTime}."
            ])->withInput();
        }

        // 2. Section Conflict
        $sectionConflictQuery = ClassSchedule::where(function ($q) use ($request, $sectionId) {
                $q->where('section', $request->section);
                if ($sectionId) {
                    $q->orWhere('section_id', $sectionId);
                }
            })
            ->where('id', '!=', $id)
            ->where($dayColName, $targetDay)
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
            });

        if (in_array('academic_period_id', $schedCols) && $activePeriodId) {
            $sectionConflictQuery->where('academic_period_id', $activePeriodId);
        }

        $sectionConflict = $sectionConflictQuery->first();
        if ($sectionConflict) {
            $sTime = date('g:i A', strtotime($sectionConflict->start_time));
            $eTime = date('g:i A', strtotime($sectionConflict->end_time));
            return back()->withErrors([
                'section' => "Section Schedule Conflict: Section {$request->section} already has a class ({$sectionConflict->subject_name}) scheduled on {$targetDay} from {$sTime} to {$eTime}."
            ])->withInput();
        }

        $data = [];

        if (in_array('subject_id', $schedCols) && Schema::hasTable('subjects')) {
            $subCols = Schema::getColumnListing('subjects');
            $nameCol = in_array('name', $subCols) ? 'name' : (in_array('subject_name', $subCols) ? 'subject_name' : null);
            if ($nameCol) {
                $existingSub = DB::table('subjects')->where($nameCol, $request->subject_name)->first();
                if ($existingSub) {
                    $data['subject_id'] = $existingSub->id;
                }
            }
        }

        if (in_array('subject_name', $schedCols)) $data['subject_name'] = $request->subject_name;
        elseif (in_array('subject', $schedCols)) $data['subject'] = $request->subject_name;

        if (in_array('day_of_week', $schedCols)) $data['day_of_week'] = $targetDay;
        elseif (in_array('day', $schedCols)) $data['day'] = $targetDay;

        if (in_array('section_id', $schedCols) && $sectionId) {
            $data['section_id'] = $sectionId;
        }

        $mappings = [
            'subject_code' => $request->subject_code,
            'teacher_id'   => $request->teacher_id,
            'grade_level'  => $request->grade_level,
            'strand'       => $request->strand,
            'section'      => $request->section,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
        ];

        foreach ($mappings as $col => $val) {
            if (in_array($col, $schedCols)) {
                $data[$col] = $val;
            }
        }

        $schedule->update($data);

        return redirect()->route('admin.schedules.index')->with('success', 'Class schedule updated successfully!');
    }

    /**
     * API for real-time schedule conflict validation in modal
     */
    public function checkConflict(Request $request)
    {
        $teacherId = $request->input('teacher_id');
        $sectionName = $request->input('section');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $ignoreId = $request->input('ignore_id');
        
        $days = $request->input('days', []);
        if (is_string($days)) {
            $days = array_filter(array_map('trim', explode(',', $days)));
        }

        if (!$startTime || !$endTime || empty($days)) {
            return response()->json(['has_conflict' => false]);
        }

        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $dayColName = in_array('day_of_week', $schedCols) ? 'day_of_week' : 'day';

        $activePeriodId = null;
        if (Schema::hasTable('academic_periods')) {
            $activePeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id') 
                        ?? DB::table('academic_periods')->value('id');
        }

        $sectionRecord = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('section_name', $sectionName)->first()
            : null;
        $sectionId = $sectionRecord ? $sectionRecord->id : null;

        foreach ($days as $dayItem) {
            // Teacher check
            if ($teacherId) {
                $tQuery = ClassSchedule::where('teacher_id', $teacherId)
                    ->where($dayColName, $dayItem)
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)->where('end_time', '>', $startTime);
                    });

                if ($ignoreId) $tQuery->where('id', '!=', $ignoreId);
                if (in_array('academic_period_id', $schedCols) && $activePeriodId) $tQuery->where('academic_period_id', $activePeriodId);

                $conflict = $tQuery->first();
                if ($conflict) {
                    $teacher = User::find($teacherId);
                    $teacherName = $teacher ? ($teacher->first_name . ' ' . $teacher->last_name) : 'Assigned faculty';
                    $st = date('g:i A', strtotime($conflict->start_time));
                    $et = date('g:i A', strtotime($conflict->end_time));
                    return response()->json([
                        'has_conflict' => true,
                        'type' => 'teacher',
                        'message' => "Faculty Schedule Conflict: {$teacherName} already has {$conflict->subject_name} on {$dayItem} ({$st} - {$et})."
                    ]);
                }
            }

            // Section check
            if ($sectionName) {
                $sQuery = ClassSchedule::where(function ($q) use ($sectionName, $sectionId) {
                        $q->where('section', $sectionName);
                        if ($sectionId) $q->orWhere('section_id', $sectionId);
                    })
                    ->where($dayColName, $dayItem)
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)->where('end_time', '>', $startTime);
                    });

                if ($ignoreId) $sQuery->where('id', '!=', $ignoreId);
                if (in_array('academic_period_id', $schedCols) && $activePeriodId) $sQuery->where('academic_period_id', $activePeriodId);

                $conflict = $sQuery->first();
                if ($conflict) {
                    $st = date('g:i A', strtotime($conflict->start_time));
                    $et = date('g:i A', strtotime($conflict->end_time));
                    return response()->json([
                        'has_conflict' => true,
                        'type' => 'section',
                        'message' => "Section Schedule Conflict: Section {$sectionName} already has {$conflict->subject_name} on {$dayItem} ({$st} - {$et})."
                    ]);
                }
            }
        }

        return response()->json(['has_conflict' => false]);
    }

   public function import(Request $request)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
    ]);

    $file = $request->file('file');
    $path = $file->getRealPath();

    $data = array_map('str_getcsv', file($path));
    $header = array_shift($data); // Alisin ang header row

    $importedCount = 0;

    foreach ($data as $row) {
        if (count($row) < 9) continue;

        [$subjectName, $subjectCode, $teacherId, $gradeLevel, $strand, $section, $day, $startTime, $endTime] = $row;

        ClassSchedule::create([
            'subject_name' => trim($subjectName),
            'subject_code' => trim($subjectCode),
            'teacher_id'   => trim($teacherId),
            'grade_level'  => trim($gradeLevel),
            'strand'       => trim($strand),
            'section'      => trim($section),
            'day'          => trim($day),
            'start_time'   => trim($startTime),
            'end_time'     => trim($endTime),
        ]);

        $importedCount++;
    }

    return redirect()->route('admin.schedules.index')->with('success', "Successfully imported {$importedCount} class schedules!");
}

public function matrix()
{
    $schedules = ClassSchedule::with(['teacher', 'academicSection'])->get();
    return view('admin.schedules.matrix', compact('schedules'));
}
    public function destroy($id)
    {
        ClassSchedule::findOrFail($id)->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Class schedule deleted successfully!');
    }

    public function clearAll()
    {
        ClassSchedule::truncate();
        return redirect()->route('admin.schedules.index')->with('success', 'All schedules have been cleared.');
    }
}