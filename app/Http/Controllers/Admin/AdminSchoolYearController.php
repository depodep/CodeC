<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class AdminSchoolYearController extends Controller
{
    public function index()
    {
        // 1. Siguraduhing laging may tamang default na values kung sakaling blanko o may maling data sa settings
        $sySetting = DB::table('settings')->where('key', 'active_school_year')->first();
        if (!$sySetting || strlen($sySetting->value) > 9 || !str_contains($sySetting->value, '-')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'active_school_year'],
                ['value' => '2026-2027', 'updated_at' => now()]
            );
        }

        $activeSchoolYear = DB::table('settings')->where('key', 'active_school_year')->value('value');

        if (Schema::hasTable('academic_periods')) {
            $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

            if (!$activePeriod) {
                $periodId = DB::table('academic_periods')->insertGetId([
                    'school_year' => $activeSchoolYear,
                    'semester' => 'ANNUAL',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $activePeriod = DB::table('academic_periods')->where('id', $periodId)->first();
            }

            $activeSchoolYear = $activePeriod->school_year;
        }

        $periods = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->orderByDesc('school_year')->orderBy('semester')->get()
            : collect();
        $schoolYears = $periods->pluck('school_year')->unique()->values();
        if ($schoolYears->isEmpty()) {
            $schoolYears = collect([$activeSchoolYear]);
        }
        $schoolYearGroups = $periods->groupBy('school_year');
        $totalLogs = Schema::hasTable('attendance_logs') ? DB::table('attendance_logs')->count() : 0;

        return view('admin.school-year.index', compact(
            'activeSchoolYear', 'periods', 'schoolYears', 'schoolYearGroups', 'totalLogs'
        ));
    }

    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startYear = (int) date('Y', strtotime($validated['start_date']));
        $endYear = (int) date('Y', strtotime($validated['end_date']));
        $academicYear = $startYear . '-' . $endYear;

        if (!Schema::hasTable('academic_periods')) {
            return back()->with('error', 'Academic periods table is not available. Run the migrations first.');
        }

        $exists = DB::table('academic_periods')
            ->where('school_year', $academicYear)
            ->exists();

        if ($exists) {
            return back()->with('error', 'That school year already exists. Select it from the active school-year list.');
        }

        DB::table('academic_periods')->insert([
            'school_year' => $academicYear,
            'semester' => 'ANNUAL',
            'is_active' => false,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'New school year added. Select it from the active school-year list when ready.');
    }

    public function update(Request $request)
    {
        // 1. I-validate ang pormat ng inputs at password field
        $request->validate([
            'academic_year'  => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'admin_password' => ['required', 'string']
        ], [
            'academic_year.regex' => 'The school year must strictly follow the YYYY-YYYY format (e.g., 2026-2027).'
        ]);

        $user = auth()->user();

        // 2. Ligtas na Password Check (iniiwasan ang Bcrypt exception kung plain text ang nasa DB)
        $isPasswordValid = false;
        
        if ($user && $user->password) {
            // Kung sakaling plain text ang nakaimbak sa database
            if ($request->admin_password === $user->password) {
                $isPasswordValid = true;
            } else {
                // Subukan ang Hash::check kung naka-encrypt ito nang tama
                try {
                    if (\Illuminate\Support\Facades\Hash::check($request->admin_password, $user->password)) {
                        $isPasswordValid = true;
                    }
                } catch (\Exception $e) {
                    $isPasswordValid = false;
                }
            }
        }

        if (!$isPasswordValid) {
            return back()->with('error', 'Incorrect admin password. Changes not saved.');
        }

        // 3. I-save sa database kapag tama ang password
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'active_school_year'],
                ['value' => $request->academic_year, 'updated_at' => now()]
            );

        }

        if (Schema::hasTable('academic_periods')) {
            DB::table('academic_periods')->update(['is_active' => false]);

            $period = DB::table('academic_periods')
                ->where('school_year', $request->academic_year)
                ->first();

            if ($period) {
                DB::table('academic_periods')->where('id', $period->id)->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('academic_periods')->insert([
                    'school_year' => $request->academic_year,
                    'semester' => 'ANNUAL',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $activePeriodId = DB::table('academic_periods')
                ->where('school_year', $request->academic_year)
                ->value('id');

            DB::table('academic_periods')->where('id', $activePeriodId)->update(['is_active' => true]);
        }

        return back()->with('success', 'Active school year successfully updated to A.Y. ' . $request->academic_year . '.');
    }

    public function reset()
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'active_school_year'],
                ['value' => '2026-2027', 'updated_at' => now()]
            );
        }

        return back()->with('success', 'School year configuration reset to default (2026-2027).');
    }
}