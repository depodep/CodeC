<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSectionController extends Controller
{
    public function index()
    {
        $activeSchoolYear = Schema::hasTable('settings') 
            ? (DB::table('settings')->where('key', 'active_school_year')->value('value') ?? '2025-2026') 
            : '2025-2026';

        $activeSemester = Schema::hasTable('settings') 
            ? (DB::table('settings')->where('key', 'active_semester')->value('value') ?? '1st Semester') 
            : '1st Semester';

        $sections = Schema::hasTable('academic_sections') 
            ? DB::table('academic_sections')->orderBy('id', 'desc')->get() 
            : collect([]);

        // Faculty / Teacher users for adviser assignment
        $teachers = \App\Models\User::where(function($q) {
            $cols = Schema::getColumnListing('users');
            if (in_array('role_id', $cols)) $q->where('role_id', 2);
            if (in_array('role', $cols)) {
                $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
            }
        })->orderBy('first_name')->get();

        // Student counts per section
        $studentCounts = \App\Models\User::where(function($q) {
            $cols = Schema::getColumnListing('users');
            if (in_array('role_id', $cols)) $q->where('role_id', 3);
            if (in_array('role', $cols)) $q->orWhere('role', 'student');
        })
        ->whereNotNull('section')
        ->where('section', '!=', '')
        ->select('section', DB::raw('count(*) as count'))
        ->groupBy('section')
        ->pluck('count', 'section');

        // Map teachers by section name to find assigned advisers
        $advisersBySection = $teachers->filter(fn($t) => !empty($t->section))
            ->keyBy(fn($t) => strtoupper(trim($t->section)));

        // Enrich sections with adviser and student count
        $sections = $sections->map(function($sec) use ($advisersBySection, $studentCounts) {
            $secKey = strtoupper(trim($sec->section_name));
            $sec->adviser = $advisersBySection->get($secKey);
            $sec->student_count = $studentCounts->get($sec->section_name) ?? 0;
            return $sec;
        });

        // Mini dashboard stats calculation
        $totalSectionsCount = $sections->count();
        $jhsSectionsCount = $sections->filter(function($s) {
            return preg_match('/7|8|9|10/i', $s->grade_level);
        })->count();

        $shsSectionsCount = $sections->filter(function($s) {
            return preg_match('/11|12/i', $s->grade_level);
        })->count();

        $assignedAdvisersCount = $sections->filter(fn($s) => !empty($s->adviser))->count();
        $unassignedSectionsCount = $totalSectionsCount - $assignedAdvisersCount;

        // Breakdown per grade level
        $gradeBreakdown = $sections->groupBy(function($s) {
            $cleanNum = preg_replace('/[^0-9]/', '', $s->grade_level);
            return $cleanNum ? "Grade {$cleanNum}" : ucwords(strtolower(trim($s->grade_level)));
        })->map->count();

        return view('admin.sections.index', compact(
            'sections', 
            'teachers',
            'activeSchoolYear', 
            'activeSemester',
            'totalSectionsCount',
            'jhsSectionsCount',
            'shsSectionsCount',
            'assignedAdvisersCount',
            'unassignedSectionsCount',
            'gradeBreakdown'
        ));
    }

    public function storeSection(Request $request)
    {
        $request->validate([
            'grade_level'  => 'required|string|max:50',
            'section_name' => 'required|string|max:100',
            'strand'       => 'nullable|string|max:50'
        ]);

        if (Schema::hasTable('academic_sections')) {
            $activePeriodId = Schema::hasTable('academic_periods')
                ? DB::table('academic_periods')->where('is_active', 1)->value('id')
                : null;

            DB::table('academic_sections')->insert([
                'academic_period_id' => $activePeriodId,
                'grade_level'  => ucwords(strtolower(trim($request->grade_level))),
                'section_name' => trim($request->section_name),
                'strand'       => $request->filled('strand') ? strtoupper(trim($request->strand)) : null,
                'created_at'   => now(),
                'updated_at'   => now()
            ]);
        }

        return back()->with('success', 'Class section successfully added.');
    }

    public function updateSection(Request $request, $id)
    {
        $request->validate([
            'grade_level'  => 'required|string|max:50',
            'section_name' => 'required|string|max:100',
            'strand'       => 'nullable|string|max:50',
            'teacher_id'   => 'nullable'
        ]);

        if (Schema::hasTable('academic_sections')) {
            // 1. Kunin muna ang lumang detalye ng section bago i-update
            $oldSection = DB::table('academic_sections')->where('id', $id)->first();

            $newGradeLevel = ucwords(strtolower(trim($request->grade_level)));
            $newSectionName = trim($request->section_name);
            $newStrand = $request->filled('strand') ? strtoupper(trim($request->strand)) : null;

            // 2. I-update ang section sa academic_sections table
            DB::table('academic_sections')->where('id', $id)->update([
                'grade_level'  => $newGradeLevel,
                'section_name' => $newSectionName,
                'strand'       => $newStrand,
                'updated_at'   => now()
            ]);

            // 3. PROPAGATION SA USER MANAGEMENT
            if ($oldSection) {
                \App\Models\User::where('section', $oldSection->section_name)
                    ->where('grade_level', $oldSection->grade_level)
                    ->update([
                        'grade_level' => $newGradeLevel,
                        'section'     => $newSectionName,
                        'strand'      => $newStrand
                    ]);
            }

            // 4. UPDATE TEACHER / ADVISER ASSIGNMENT IF TEACHER_ID PASSED IN EDIT FORM
            if ($request->has('teacher_id')) {
                // Clear any existing teacher assigned to old/new section
                \App\Models\User::where('section', $newSectionName)
                    ->where(function($q) {
                        $cols = Schema::getColumnListing('users');
                        if (in_array('role_id', $cols)) $q->where('role_id', 2);
                        if (in_array('role', $cols)) $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
                    })
                    ->update([
                        'section' => null,
                        'grade_level' => null
                    ]);

                if ($request->filled('teacher_id')) {
                    $teacher = \App\Models\User::find($request->teacher_id);
                    if ($teacher) {
                        $cleanGrade = preg_replace('/[^0-9]/', '', $newGradeLevel);
                        $teacher->grade_level = $cleanGrade ?: $newGradeLevel;
                        $teacher->section = $newSectionName;
                        $teacher->save();
                    }
                }
            }
        }

        return back()->with('success', 'Class section details and faculty adviser placement successfully updated.');
    }

    public function destroySection($id)
    {
        if (Schema::hasTable('academic_sections')) {
            $section = DB::table('academic_sections')->where('id', $id)->first();

            if ($section) {
                // Opsyonal: I-clear o i-set as null ang section ng mga users na nakatali dito para walang "orphan placement"
                \App\Models\User::where('section', $section->section_name)
                    ->where('grade_level', $section->grade_level)
                    ->update([
                        'section' => null,
                        'grade_level' => null,
                        'strand' => null
                    ]);

                // Burahin na ang section
                DB::table('academic_sections')->where('id', $id)->delete();
            }
        }
        return back()->with('success', 'Class section successfully removed and user placements cleared.');
    }

    public function assignAdviser(Request $request, $id)
    {
        $request->validate([
            'teacher_id' => 'nullable'
        ]);

        if (Schema::hasTable('academic_sections')) {
            $section = DB::table('academic_sections')->where('id', $id)->first();

            if ($section) {
                // Clear any existing teacher assigned to this section
                \App\Models\User::where('section', $section->section_name)
                    ->where(function($q) {
                        $cols = Schema::getColumnListing('users');
                        if (in_array('role_id', $cols)) $q->where('role_id', 2);
                        if (in_array('role', $cols)) $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
                    })
                    ->update([
                        'section' => null,
                        'grade_level' => null
                    ]);

                // Assign new teacher if provided
                if ($request->filled('teacher_id')) {
                    $teacher = \App\Models\User::findOrFail($request->teacher_id);
                    $cleanGrade = preg_replace('/[^0-9]/', '', $section->grade_level);
                    
                    $teacher->grade_level = $cleanGrade ?: $section->grade_level;
                    $teacher->section = $section->section_name;
                    $teacher->save();

                    return back()->with('success', "Faculty teacher '{$teacher->first_name} {$teacher->last_name}' assigned as Adviser for Section '{$section->section_name}'.");
                }

                return back()->with('success', "Adviser placement cleared for Section '{$section->section_name}'.");
            }
        }

        return back()->with('error', 'Section not found.');
    }
}