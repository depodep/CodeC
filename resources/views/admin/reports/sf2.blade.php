@extends('layouts.app')

@section('title', 'SF2 Learner Attendance Sheet - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70">

    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs print:hidden">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reports') }}"
               class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">SF2 — Learner Attendance Sheet</h1>
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-[10px] font-black uppercase">DepEd Form</span>
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Generate daily attendance summary per section for DepEd compliance</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()"
                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs cursor-pointer">
                <i class="fa-solid fa-print"></i> Print SF2
            </button>
        </div>
    </header>

    <main class="p-6 lg:p-10 max-w-[1400px] mx-auto space-y-6 print:p-0 print:max-w-none">

        {{-- Filter --}}
        <form method="GET" action="{{ route('admin.reports.sf2') }}"
              class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs print:hidden">
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-4">Generate SF2</p>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1 min-w-[140px]">
                    <label class="text-[10px] font-black uppercase text-slate-400">Section</label>
                    @php
                        $sf2Sections = \Illuminate\Support\Facades\DB::table('academic_sections')
                            ->select('id','section_name','grade_level','strand')
                            ->orderBy('grade_level')->orderBy('section_name')
                            ->get();
                    @endphp
                    <select name="section_id" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                        <option value="">Select Section</option>
                        @foreach($sf2Sections as $sec)
                            <option value="{{ $sec->id }}" @selected(request('section_id') == $sec->id)>
                                Grade {{ $sec->grade_level }} – {{ $sec->section_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[120px]">
                    <label class="text-[10px] font-black uppercase text-slate-400">Month</label>
                    <select name="month" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" @selected(request('month', now()->month) == $m)>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[100px]">
                    <label class="text-[10px] font-black uppercase text-slate-400">Year</label>
                    <select name="year" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" @selected(request('year', now()->year) == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="px-5 py-2 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition shadow-xs cursor-pointer">
                    <i class="fa-solid fa-table-list mr-1"></i> Generate
                </button>
            </div>
        </form>

        @php
            $sectionId  = request('section_id');
            $month      = (int) request('month', now()->month);
            $year       = (int) request('year',  now()->year);
            $section    = $sectionId ? \Illuminate\Support\Facades\DB::table('academic_sections')->find($sectionId) : null;
            $daysInMonth = \Carbon\Carbon::createFromDate($year, $month)->daysInMonth;

            $students = collect();
            $sf2Logs  = collect();

            if ($section) {
                // Get students in this section
                $students = \Illuminate\Support\Facades\DB::table('users')
                    ->where('role_id', 3)
                    ->where('section', $section->section_name)
                    ->orderBy('last_name')->orderBy('first_name')
                    ->get(['id','first_name','last_name','id_number','gender']);

                // Get all attendance logs for this section in the month
                $attCols = \Illuminate\Support\Facades\Schema::hasTable('attendance_logs')
                    ? \Illuminate\Support\Facades\Schema::getColumnListing('attendance_logs') : [];
                $dateCol = in_array('attendance_date', $attCols) ? 'attendance_date'
                    : (in_array('date', $attCols) ? 'date' : 'created_at');
                $userFk  = in_array('user_id', $attCols) ? 'user_id'
                    : (in_array('student_id', $attCols) ? 'student_id' : null);

                if ($userFk && !empty($attCols)) {
                    $sf2Logs = \Illuminate\Support\Facades\DB::table('attendance_logs')
                        ->whereYear($dateCol, $year)
                        ->whereMonth($dateCol, $month)
                        ->whereIn($userFk, $students->pluck('id'))
                        ->get([$userFk, $dateCol, 'status'])
                        ->groupBy(fn($l) => $l->$userFk);
                }
            }
        @endphp

        @if($section && $students->isNotEmpty())
        {{-- SF2 Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden print:rounded-none print:border-0 print:shadow-none">
            {{-- School Header --}}
            <div class="p-6 border-b border-slate-200 print:border-b-2 print:border-slate-800">
                <div class="text-center">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 print:text-black">Republic of the Philippines</p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 print:text-black">Department of Education</p>
                    <p class="text-sm font-black text-slate-800 mt-1 print:text-base">School Form 2 (SF2) — Learner Attendance Sheet</p>
                    <div class="mt-2 flex items-center justify-center gap-6 text-xs font-bold text-slate-600">
                        <span>Grade: <strong>{{ $section->grade_level }}</strong></span>
                        <span>Section: <strong>{{ $section->section_name }}</strong></span>
                        <span>Month: <strong>{{ \Carbon\Carbon::create()->month($month)->format('F') . ' ' . $year }}</strong></span>
                        <span>Total Learners: <strong>{{ $students->count() }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" style="font-size: 10px;">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="border border-slate-300 px-2 py-2 font-black text-slate-700 sticky left-0 bg-slate-50 min-w-[160px]">Learner Name</th>
                            <th class="border border-slate-300 px-2 py-2 font-black text-slate-700 text-center">Sex</th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $dayOfWeek = \Carbon\Carbon::createFromDate($year, $month, $d)->dayOfWeek; @endphp
                                <th class="border px-1 py-2 font-black text-center min-w-[24px] whitespace-nowrap
                                    {{ in_array($dayOfWeek, [0,6]) ? 'bg-slate-200 text-slate-400 border-slate-300' : 'bg-slate-50 text-slate-700 border-slate-300' }}">
                                    {{ $d }}
                                </th>
                            @endfor
                            <th class="border border-slate-300 px-2 py-2 font-black text-center text-slate-700">Total Days Present</th>
                            <th class="border border-slate-300 px-2 py-2 font-black text-center text-slate-700">Total Days Absent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        @php
                            $studentLogs = $sf2Logs->get($student->id, collect());
                            $logsByDay   = $studentLogs->keyBy(fn($l) => (int)\Carbon\Carbon::parse($l->{array_key_exists('attendance_date', (array)$l) ? 'attendance_date' : 'date'})->format('j'));
                            $presentCount = 0;
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="border border-slate-200 px-2 py-1.5 font-bold sticky left-0 bg-white">
                                {{ $student->last_name }}, {{ $student->first_name }}
                            </td>
                            <td class="border border-slate-200 px-1 py-1.5 text-center font-bold">
                                {{ strtoupper(substr($student->gender ?? 'U', 0, 1)) }}
                            </td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $dayOfWeek = \Carbon\Carbon::createFromDate($year, $month, $d)->dayOfWeek;
                                    $isWeekend = in_array($dayOfWeek, [0, 6]);
                                    $log = $logsByDay->get($d);
                                    $isPresent = $log && in_array(strtoupper($log->status ?? ''), ['ON-TIME', 'PRESENT', 'LATE']);
                                    if ($isPresent && !$isWeekend) $presentCount++;
                                @endphp
                                <td class="border px-1 py-1.5 text-center
                                    {{ $isWeekend ? 'bg-slate-100 border-slate-300' : 'border-slate-200' }}">
                                    @if(!$isWeekend)
                                        @if($isPresent)
                                            <span class="font-black text-emerald-700">✓</span>
                                        @elseif($log)
                                            <span class="font-black text-rose-500">A</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    @endif
                                </td>
                            @endfor
                            @php
                                $schoolDays = collect(range(1, $daysInMonth))
                                    ->filter(fn($d) => !in_array(\Carbon\Carbon::createFromDate($year, $month, $d)->dayOfWeek, [0,6]))
                                    ->count();
                                $absentCount = max(0, $schoolDays - $presentCount);
                            @endphp
                            <td class="border border-slate-200 px-2 py-1.5 text-center font-black text-emerald-700">{{ $presentCount }}</td>
                            <td class="border border-slate-200 px-2 py-1.5 text-center font-black text-rose-600">{{ $absentCount }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 text-[10px] text-slate-400 font-bold print:text-black">
                ✓ = Present &nbsp;&nbsp; A = Absent &nbsp;&nbsp; Shaded = Weekend/Holiday
            </div>
        </div>
        @elseif($section && $students->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs py-16 text-center">
                <p class="font-black text-slate-500">No students found in {{ $section->section_name }}</p>
            </div>
        @elseif($sectionId)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs py-16 text-center">
                <p class="font-black text-slate-500">Section not found. Please select a valid section.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-3xl mx-auto mb-4">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <p class="font-black text-slate-500">Select a section and month above to generate the SF2 sheet</p>
                <p class="text-xs text-slate-400 font-semibold mt-1">The sheet will be ready to print once generated</p>
            </div>
        @endif

    </main>
</div>
@endsection
