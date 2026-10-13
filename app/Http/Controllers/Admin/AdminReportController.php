<?php

namespace App\Http\Controllers\Admin; // <-- Must include \Admin

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminReportController extends Controller
{
    public function index()
    {
        $totalStudents = Schema::hasTable('users') ? User::where('role_id', 3)->count() : 0;
        $totalFaculty  = Schema::hasTable('users') ? User::where('role_id', 2)->count() : 0;
        $totalLogs     = Schema::hasTable('attendance_logs') ? DB::table('attendance_logs')->count() : 0;
        $totalEvals    = Schema::hasTable('evaluation_submissions') ? DB::table('evaluation_submissions')->count() : 0;

        return view('admin.reports.index', compact('totalStudents', 'totalFaculty', 'totalLogs', 'totalEvals'));
    }

    public function attendance(Request $request)
    {
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
            'section' => ['nullable', 'string', 'max:150'],
            'schedule_id' => ['nullable', 'integer', 'exists:class_schedules,id'],
            'status' => ['nullable', 'in:ON-TIME,LATE,ABSENT'],
            'per_page' => ['nullable', 'integer', 'in:10,20'],
        ]);

        $sections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')
                ->orderBy('grade_level')
                ->orderBy('section_name')
                ->get(['id', 'section_name', 'grade_level'])
                ->map(fn ($section) => (object) [
                    'key' => 'section:' . $section->id,
                    'id' => $section->id,
                    'section_name' => $section->section_name,
                    'grade_level' => $section->grade_level,
                ])
            : collect();
        $legacySections = Schema::hasTable('users')
            ? DB::table('users')
                ->where('role_id', 3)
                ->whereNotNull('section')
                ->distinct()
                ->orderBy('section')
                ->pluck('section')
                ->reject(fn ($name) => $sections->contains('section_name', $name))
                ->map(fn ($name) => (object) [
                    'key' => 'legacy:' . $name,
                    'id' => null,
                    'section_name' => $name,
                    'grade_level' => null,
                ])
            : collect();
        $sections = $sections->concat($legacySections)->sortBy('section_name')->values();
        $schedules = DB::table('class_schedules')
            ->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('academic_sections as schedule_sections', 'class_schedules.section_id', '=', 'schedule_sections.id')
            ->orderBy('schedule_sections.section_name')
            ->orderBy('class_schedules.start_time')
            ->get([
                'class_schedules.id',
                'class_schedules.section_id',
                'class_schedules.subject_name',
                'class_schedules.subject_code',
                'class_schedules.section',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'subjects.name as catalog_subject_name',
                'schedule_sections.section_name',
            ]);

            $selectionField = $filters['report_by'] === 'section' ? 'section' : 'schedule_id';
            $selectedId = $filters[$selectionField] ?? (
            $filters['report_by'] === 'section'
                ? $sections->first()?->key
                : $schedules->first()?->id
        );
        $selectedItem = $filters['report_by'] === 'section'
            ? $sections->firstWhere('key', $selectedId)
            : $schedules->firstWhere('id', (int) $selectedId);
        if ($selectedId && !$selectedItem) {
            abort(404, 'The selected section or subject was not found.');
        }
        $filters['section'] = $filters['report_by'] === 'section' ? $selectedId : null;
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
            if ($filters['report_by'] === 'section') {
                $sectionName = $selectedItem->section_name;
                $sectionId = $selectedItem->id;
                $studentQuery->where(function ($scope) use ($sectionId, $sectionName) {
                    $scope->where('section', $sectionName);
                    if ($sectionId && Schema::hasTable('section_student')) {
                        $scope->orWhereIn('users.id', function ($query) use ($sectionId) {
                            $query->select('student_id')
                                ->from('section_student')
                                ->where('section_id', $sectionId);
                        });
                    }
                    if ($sectionId && Schema::hasTable('attendance_logs')) {
                        $scope->orWhereIn('users.id', function ($query) use ($sectionId) {
                            $query->select('user_id')
                                ->from('attendance_logs')
                                ->where('attendance_type', 'advisory')
                                ->where('advisory_section_id', $sectionId);
                        });
                    }
                });
            } else {
                $sectionId = $selectedItem->section_id;
                $sectionName = $selectedItem->section_name ?: $selectedItem->section;
                $studentQuery->where(function ($scope) use ($sectionId, $sectionName, $selectedItem) {
                    if ($sectionName) {
                        $scope->where('section', $sectionName);
                    }
                    if ($sectionId && Schema::hasTable('section_student')) {
                        $scope->orWhereIn('users.id', function ($query) use ($sectionId) {
                            $query->select('student_id')->from('section_student')->where('section_id', $sectionId);
                        });
                    }
                    if (Schema::hasTable('attendance_logs')) {
                        $scope->orWhereIn('users.id', function ($query) use ($selectedItem) {
                            $query->select('user_id')
                                ->from('attendance_logs')
                                ->where('attendance_type', 'subject')
                                ->where('class_schedule_id', $selectedItem->id);
                        });
                    }
                    if (!$sectionName && !$sectionId && !Schema::hasTable('attendance_logs')) {
                        $scope->whereRaw('1 = 0');
                    }
                });
            }
            $students = $studentQuery->orderBy('last_name')->orderBy('first_name')->get();
        }

        $logs = collect();
        if ($selectedItem && Schema::hasTable('attendance_logs')) {
            $logsQuery = DB::table('attendance_logs')
                ->join('users', 'attendance_logs.user_id', '=', 'users.id')
                ->where('users.role_id', 3)
                ->where('attendance_logs.attendance_type', $filters['report_by'] === 'section' ? 'advisory' : 'subject')
                ->whereBetween('attendance_logs.attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            if ($filters['report_by'] === 'section') {
                if ($selectedItem->id) {
                    $studentIds = $students->pluck('id');
                    $logsQuery->where(function ($scope) use ($selectedItem, $studentIds) {
                        $scope->where('attendance_logs.advisory_section_id', $selectedItem->id)
                            ->orWhere(function ($legacy) use ($studentIds) {
                                $legacy->whereNull('attendance_logs.advisory_section_id')
                                    ->whereIn('attendance_logs.user_id', $studentIds);
                            });
                    });
                } else {
                    $logsQuery->whereIn('attendance_logs.user_id', $students->pluck('id'));
                }
            } else {
                $logsQuery->where('attendance_logs.class_schedule_id', $selectedItem->id);
            }
            if ($filters['status']) {
                $logsQuery->whereRaw('UPPER(attendance_logs.status) = ?', [$filters['status']]);
            }
            $logs = $logsQuery->select([
                'attendance_logs.user_id',
                'attendance_logs.attendance_date',
                'attendance_logs.time_in',
                'attendance_logs.time_out',
                'attendance_logs.status',
            ])->orderBy('attendance_logs.id')->get();
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

            return (object) ['student' => $student, 'logs' => $dailyLogs];
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
            ? ($selectedItem ? ($selectedItem->grade_level ? "Grade {$selectedItem->grade_level} · " : '') . $selectedItem->section_name : null)
            : ($selectedItem
                ? ($selectedItem->subject_name ?: $selectedItem->catalog_subject_name ?: $selectedItem->subject_code ?: 'Subject')
                    . ' · ' . ($selectedItem->section_name ?: $selectedItem->section ?: 'Section')
                : null);

        if ($request->ajax()) {
            return view('admin.reports.partials.attendance-results', compact(
                'attendanceRows', 'filters', 'summary', 'dates', 'periodLabel', 'selectedLabel'
            ));
        }

        return view('admin.reports.attendance', compact(
            'attendanceRows', 'filters', 'sections', 'schedules', 'summary', 'dates', 'periodLabel', 'selectedLabel'
        ));
    }

    public function sf2() { return view('admin.reports.sf2'); }

    public function evaluation() { return view('admin.reports.evaluation'); }

    public function users()
    {
        $users = Schema::hasTable('users') ? User::with('role')->orderBy('last_name')->get() : collect();
        return view('admin.reports.users', compact('users'));
    }
}