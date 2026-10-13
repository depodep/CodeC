@extends('layouts.app')

@section('title', 'Student Dashboard | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Student Dashboard</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Student Portal</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">
                    Welcome back, {{ $student->first_name }} {{ $student->last_name }}!
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black text-slate-700">
                <i class="fa-solid fa-id-badge text-[#8b1818] mr-1"></i>LRN: {{ $student->id_number ?? 'Not Set' }}
            </span>
            <a href="{{ route('student.schedules') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition">
                <i class="fa-solid fa-calendar-days"></i> My Schedule
            </a>
            <a href="{{ route('student.attendance') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-400 text-amber-950 text-xs font-black hover:bg-amber-300 transition">
                <i class="fa-solid fa-calendar-check"></i> Attendance Logs
            </a>
        </div>
    </header>

    <main class="px-6 lg:px-10 py-6 space-y-6 max-w-[1700px] mx-auto">

        <!-- ================= 1. EVALUATION ALERT BANNER ================= -->
        @if(!empty($isEvaluationOpen))
            @if(empty($allEvaluatedDone))
                <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-4 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-start sm:items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 border border-amber-300 flex items-center justify-center shrink-0 text-base">
                            <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                        </span>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-black text-amber-950">Faculty Performance Evaluation is Open</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-200 text-amber-900 border border-amber-300 uppercase">Action Required</span>
                            </div>
                            <p class="text-xs font-bold text-amber-800 mt-0.5">
                                {{ $activeCycle->name ?? 'Current Evaluation Cycle' }} is now ongoing. You have evaluated <strong>{{ $totalEvaluated ?? 0 }} of {{ $totalToEvaluate ?? 0 }}</strong> instructors.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('student.evaluations.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition shrink-0 shadow-xs">
                        <i class="fa-solid fa-clipboard-check"></i> Evaluate Instructors Now
                    </a>
                </div>
            @endif
        @endif

        <!-- ================= 2. STUDENT & SECTION INFO BANNER ================= -->
        <section class="bg-white rounded-3xl border-2 border-slate-200 p-6 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Left: Student Info -->
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#590d0d] text-amber-300 font-black text-xl flex items-center justify-center shrink-0 shadow-sm border-2 border-amber-300">
                        {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                {{ $student->first_name }} {{ $student->middle_name ? $student->middle_name . ' ' : '' }}{{ $student->last_name }}
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-mono font-black bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $student->id_number ?? 'LRN Not Set' }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-slate-500">
                            Username: <span class="text-slate-700 font-black">{{ $student->username }}</span> &bull; Southern Isabela Academy
                        </p>
                        <div class="flex items-center gap-2 pt-1 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-amber-50 text-amber-900 border border-amber-200">
                                <i class="fa-solid fa-layer-group text-amber-600 mr-1"></i>{{ $student->grade_level ?? 'Grade Level' }}
                            </span>
                            <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-blue-50 text-blue-900 border border-blue-200">
                                <i class="fa-solid fa-users text-blue-600 mr-1"></i>Section {{ $student->section ?? 'Unassigned' }}
                            </span>
                            @if(!empty($student->strand))
                                <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-rose-50 text-rose-900 border border-rose-200">
                                    <i class="fa-solid fa-book-bookmark text-rose-600 mr-1"></i>{{ $student->strand }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right: Section Adviser Card -->
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 lg:min-w-[280px] flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-[#8b1818] border border-amber-300 flex items-center justify-center font-black text-base shrink-0">
                        @if($advisor)
                            {{ strtoupper(substr($advisor->first_name ?? 'A', 0, 1)) }}{{ strtoupper(substr($advisor->last_name ?? 'D', 0, 1)) }}
                        @else
                            <i class="fa-solid fa-user-tie"></i>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Section Adviser</span>
                        @if($advisor)
                            <h4 class="text-sm font-black text-slate-900 truncate">{{ $advisor->first_name }} {{ $advisor->last_name }}</h4>
                            <p class="text-[11px] font-semibold text-slate-500 truncate">{{ $advisor->email ?? 'Faculty Adviser' }}</p>
                        @else
                            <h4 class="text-sm font-black text-slate-500 italic">No Adviser Assigned</h4>
                            <p class="text-[11px] font-semibold text-slate-400">Contact Administration</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 3. ATTENDANCE SUMMARY STATS ================= -->
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Overall Attendance Rate -->
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Attendance Rate</p>
                    <p class="text-2xl font-black {{ $attendanceRate >= 80 ? 'text-emerald-600' : ($attendanceRate >= 60 ? 'text-amber-600' : 'text-rose-600') }} mt-1">
                        {{ $attendanceRate }}%
                    </p>
                    <span class="text-[10px] font-bold text-slate-400">Overall class standing</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
            </div>

            <!-- Present Tally -->
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Present (On-Time)</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($attendancePresent) }}</p>
                    <span class="text-[10px] font-bold text-emerald-600">On schedule taps</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-check"></i>
                </span>
            </div>

            <!-- Late Tally -->
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Late Arrivals</p>
                    <p class="text-2xl font-black text-amber-700 mt-1">{{ number_format($attendanceLate ?? 0) }}</p>
                    <span class="text-[10px] font-bold text-amber-600">Tardy entries</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock"></i>
                </span>
            </div>

            <!-- Total Absences Count -->
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Total Absences</p>
                    <p class="text-2xl font-black text-rose-700 mt-1">{{ number_format($attendanceAbsent ?? 0) }}</p>
                    <span class="text-[10px] font-bold text-rose-600">Subject/Day absences</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-xmark"></i>
                </span>
            </div>
        </section>

        <!-- ================= 4. ATTENDANCE & ABSENTS BREAKDOWN PER SUBJECT ================= -->
        <section class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b-2 border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-base font-black text-slate-900">Attendance & Absents per Subject</h2>
                    <p class="text-[11px] font-bold text-slate-500">Detailed rate and absence tally for each enrolled class schedule</p>
                </div>
                <span class="px-3 py-1 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black text-slate-700">
                    {{ count($subjectAttendance) }} Subjects
                </span>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($subjectAttendance as $sub)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between hover:border-slate-300 transition">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-white border border-slate-200 text-slate-700">
                                    {{ $sub->subject_code ?? 'SUBJ' }}
                                </span>
                                <span class="text-xs font-black {{ $sub->rate === null ? 'text-slate-400' : ($sub->rate >= 75 ? 'text-emerald-700' : 'text-rose-700') }}">
                                    {{ $sub->rate === null ? 'No logs yet' : $sub->rate . '%' }}
                                </span>
                            </div>
                            <h4 class="text-sm font-black text-slate-900 leading-snug">{{ $sub->subject_name }}</h4>
                            <p class="text-[11px] font-semibold text-slate-500 mt-1">
                                <i class="fa-solid fa-user-tie text-[9px] mr-1 text-slate-400"></i>{{ $sub->teacher_name }}
                            </p>
                            <p class="text-[11px] font-mono font-bold text-slate-600 mt-0.5">
                                <i class="fa-solid fa-clock text-[9px] mr-1 text-slate-400"></i>{{ $sub->time_slot }} ({{ $sub->day }})
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs font-bold">
                            <span class="text-emerald-700 flex items-center gap-1">
                                <i class="fa-solid fa-check text-[10px]"></i> Present: <strong>{{ $sub->present }}</strong>
                            </span>
                            <span class="text-rose-700 flex items-center gap-1">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Absents: <strong>{{ $sub->absent }}</strong>
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-300 block"></i>
                        <p class="text-xs font-black text-slate-600">No enrolled subject schedules found for Section {{ $student->section }}.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- ================= 5. SCHEDULE TIMETABLE / LISTING VIEW ================= -->
        <section class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs overflow-hidden" x-data="{ viewMode: 'timetable' }">
            <div class="px-6 py-4 border-b-2 border-slate-100 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-base font-black text-slate-900">Class Schedule & Timetable</h2>
                    <p class="text-[11px] font-bold text-slate-500">Official section weekly timetable and class schedule list</p>
                </div>
                <!-- View Mode Switcher -->
                <div class="bg-slate-100 p-1 rounded-xl border border-slate-200 flex items-center shrink-0">
                    <button type="button" @click="viewMode = 'timetable'"
                            :class="viewMode === 'timetable' ? 'bg-[#8b1818] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black flex items-center gap-1.5 transition cursor-pointer">
                        <i class="fa-solid fa-calendar-days text-[11px]"></i>
                        <span>Timetable Grid</span>
                    </button>
                    <button type="button" @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'bg-[#8b1818] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black flex items-center gap-1.5 transition cursor-pointer">
                        <i class="fa-solid fa-list-ul text-[11px]"></i>
                        <span>Listing View</span>
                    </button>
                </div>
            </div>

            <!-- VIEW 1: TIMETABLE MATRIX GRID -->
            <div x-show="viewMode === 'timetable'" class="overflow-x-auto w-full">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-[#8b1818] text-white text-[11px] font-black uppercase tracking-wider text-center divide-x divide-red-900">
                            <th class="py-3 px-3 w-36 bg-[#731414] whitespace-nowrap">Time Slot</th>
                            @foreach($weekdays as $day)
                                <th class="py-3 px-3 min-w-[170px]">{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs font-semibold text-slate-800 bg-slate-50/20">
                        @foreach($timeSlots as $slot)
                            @php
                                list($slotStartStr, $slotEndStr) = explode(' - ', $slot);
                                $slotStartSec = strtotime($slotStartStr);
                                $slotEndSec = strtotime($slotEndStr);
                                $isLunchHour = (str_starts_with($slotStartStr, '12:00'));
                            @endphp
                            <tr class="divide-x divide-slate-200 hover:bg-slate-50/60 transition {{ $isLunchHour ? 'bg-amber-50/20' : '' }}">
                                <!-- TIME COLUMN -->
                                <td class="py-2.5 px-2 text-center font-mono text-[11px] font-bold text-slate-600 bg-slate-100/70 whitespace-nowrap align-middle">
                                    {{ $slot }}
                                </td>

                                <!-- DAY COLUMNS -->
                                @foreach($weekdays as $day)
                                    @php
                                        // Overlapping schedules for this day and slot
                                        $cellSchedules = $schedules->filter(function($sched) use ($day, $slotStartSec, $slotEndSec) {
                                            $schedDay = trim($sched->day ?? '');
                                            if (strcasecmp($schedDay, $day) !== 0) return false;
                                            if (empty($sched->start_time) || empty($sched->end_time)) return false;

                                            $sSec = strtotime($sched->start_time);
                                            $eSec = strtotime($sched->end_time);
                                            return ($sSec < $slotEndSec) && ($eSec > $slotStartSec);
                                        });
                                    @endphp

                                    <td class="p-1.5 align-top min-h-[70px] bg-white/40 relative">
                                        @if($cellSchedules->isNotEmpty())
                                            <div class="space-y-1.5">
                                                @foreach($cellSchedules as $schedItem)
                                                    @php
                                                        $sSec = strtotime($schedItem->start_time);
                                                        $startsHere = ($sSec >= $slotStartSec) && ($sSec < $slotEndSec);
                                                    @endphp

                                                    @if($startsHere)
                                                        <div class="relative bg-white p-2.5 rounded-xl border border-slate-200 border-l-4 border-l-[#8b1818] shadow-2xs hover:shadow-sm transition">
                                                            <div class="flex items-center justify-between gap-1 mb-1">
                                                                <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 text-[9px] font-black uppercase border border-slate-200">
                                                                    {{ $schedItem->subject_code ?? 'SUBJ' }}
                                                                </span>
                                                                <span class="font-mono text-[9px] font-bold text-slate-600 bg-slate-50 px-1 py-0.5 rounded">
                                                                    {{ date('g:i A', strtotime($schedItem->start_time)) }} - {{ date('g:i A', strtotime($schedItem->end_time)) }}
                                                                </span>
                                                            </div>

                                                            <h4 class="text-[11px] font-black text-slate-900 leading-tight">
                                                                {{ $schedItem->subject_name }}
                                                            </h4>

                                                            @if($schedItem->teacher)
                                                                <p class="text-[10px] text-slate-500 font-semibold mt-1 flex items-center gap-1 truncate">
                                                                    <i class="fa-solid fa-user-tie text-[8px] text-slate-400"></i>
                                                                    <span>{{ $schedItem->teacher->first_name }} {{ $schedItem->teacher->last_name }}</span>
                                                                </p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- VIEW 2: LISTING VIEW -->
            <div x-show="viewMode === 'list'" class="p-6">
                <div class="border-2 border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100">
                    <div class="bg-red-50/70 px-6 py-3.5 flex items-center justify-between text-xs font-black text-slate-700 uppercase tracking-wider">
                        <span>Subject & Schedule Information</span>
                        <span class="hidden sm:inline">Assigned Instructor</span>
                    </div>

                    @forelse($schedules as $index => $schedule)
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-red-50/20 transition">
                            <div class="flex items-start gap-4">
                                <span class="text-slate-400 font-black text-base pt-0.5">{{ $index + 1 }}.</span>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 text-[10px] font-black uppercase border border-slate-200">
                                            {{ $schedule->subject_code ?? 'SUBJ' }}
                                        </span>
                                        <h4 class="text-base font-black text-slate-900">{{ $schedule->subject_name }}</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 font-bold flex items-center gap-1.5">
                                        <i class="fa-solid fa-clock text-amber-600 text-[10px]"></i>
                                        <span>{{ $schedule->time_slot }}</span>
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-900 text-[10px] font-black">{{ $schedule->day }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 sm:text-right">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center font-black text-slate-700 text-xs shrink-0">
                                    @if($schedule->teacher)
                                        {{ strtoupper(substr($schedule->teacher->first_name, 0, 1)) }}{{ strtoupper(substr($schedule->teacher->last_name, 0, 1)) }}
                                    @else
                                        <i class="fa-solid fa-user-tie text-slate-400"></i>
                                    @endif
                                </div>
                                <div>
                                    <h5 class="text-xs font-black text-slate-900">
                                        {{ $schedule->teacher ? $schedule->teacher->last_name . ', ' . $schedule->teacher->first_name : 'To be assigned' }}
                                    </h5>
                                    <p class="text-[11px] text-slate-500 font-semibold">
                                        {{ $schedule->teacher?->email ?? 'Faculty Member' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400">
                            <i class="fa-regular fa-calendar-xmark text-3xl mb-2 text-slate-300 block"></i>
                            <p class="text-xs font-black text-slate-600">No schedule records available for Section {{ $student->section }}.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

    </main>
</div>
@endsection