@extends('layouts.app')

@section('title', 'Class Schedules Matrix - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60">

    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-6 flex flex-col md:flex-row md:items-center justify-between gap-6 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#8b1818] text-white flex items-center justify-center text-lg shadow-sm shrink-0">
                <i class="fa-solid fa-calendar-days text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Master Timetable Matrix</h1>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wide shadow-2xs">A.Y. {{ $activeSchoolYear ?? '2026-2027' }}</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-1">Grid allocation & list view for class schedules</p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-black text-xs uppercase tracking-wider transition border-2 border-slate-200 shrink-0 cursor-pointer shadow-2xs">
                <i class="fa-solid fa-print text-sm text-[#8b1818]"></i>
                <span>Print</span>
            </button>

            <button type="button" onclick="openImportModal()"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-black text-xs uppercase tracking-wider transition border-2 border-slate-200 shrink-0 cursor-pointer shadow-2xs">
                <i class="fa-solid fa-file-arrow-up text-sm text-blue-600"></i>
                <span>Import CSV</span>
            </button>

            <button type="button" 
                    onclick="openScheduleModal()"
                    class="inline-flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider shadow-md shadow-red-950/20 transition-all duration-150 shrink-0 cursor-pointer">
                <i class="fa-solid fa-plus text-sm text-amber-300"></i>
                <span>Add Class Schedule</span>
            </button>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="py-8 px-6 sm:px-10 lg:px-12 w-full space-y-6 flex-1 max-w-[1700px] mx-auto">

        @php
            $dbSections = \Illuminate\Support\Facades\Schema::hasTable('academic_sections') 
                ? \Illuminate\Support\Facades\DB::table('academic_sections')->orderBy('section_name')->get() 
                : collect();

            $dbStrands = \Illuminate\Support\Facades\Schema::hasTable('academic_sections') 
                ? \Illuminate\Support\Facades\DB::table('academic_sections')->whereNotNull('strand')->where('strand', '!=', '')->distinct()->pluck('strand') 
                : collect();

            $dbGrades = \Illuminate\Support\Facades\Schema::hasTable('academic_sections') 
                ? \Illuminate\Support\Facades\DB::table('academic_sections')->whereNotNull('grade_level')->where('grade_level', '!=', '')->distinct()->orderBy('grade_level')->pluck('grade_level') 
                : collect();

            // Expanded time slots up to 10:00 PM to include evening schedules
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
                '05:00 PM - 06:00 PM',
                '06:00 PM - 07:00 PM',
                '07:00 PM - 08:00 PM',
                '08:00 PM - 09:00 PM',
                '09:00 PM - 10:00 PM',
            ];

            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $currentView = request('view', $viewMode ?? 'grid');
        @endphp

        <!-- KPI STATS CARDS BAR -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Subjects</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $totalSubjects ?? 0 }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl border border-amber-200">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Sections</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $totalSections ?? 0 }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl border border-blue-200">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Assigned Faculty</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $assignedFacultyCount ?? 0 }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl border border-emerald-200">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Active Schedules</span>
                    <h3 class="text-2xl font-black text-[#8b1818] mt-1">{{ isset($allSchedules) ? $allSchedules->count() : $schedules->total() }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-50 text-[#8b1818] flex items-center justify-center text-xl border border-red-200">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- FILTERS & DISPLAY MODE SWITCHER BAR -->
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs w-full flex flex-col xl:flex-row gap-4 items-stretch xl:items-center justify-between">
            <form id="filterForm" method="GET" action="{{ route('admin.schedules.index') }}" class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                <input type="hidden" name="view" id="viewInput" value="{{ $currentView }}">

                <select name="grade_level" onchange="this.form.submit()" class="py-2.5 px-4 text-xs font-bold rounded-2xl border-2 border-slate-200 bg-slate-50/50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Grade Levels</option>
                    @foreach($dbGrades as $gradeItem)
                        <option value="{{ $gradeItem }}" {{ request('grade_level') == $gradeItem ? 'selected' : '' }}>{{ $gradeItem }}</option>
                    @endforeach
                </select>

                <select name="strand" onchange="this.form.submit()" class="py-2.5 px-4 text-xs font-bold rounded-2xl border-2 border-slate-200 bg-slate-50/50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Strands</option>
                    @foreach($dbStrands as $strandItem)
                        <option value="{{ $strandItem }}" {{ request('strand') == $strandItem ? 'selected' : '' }}>{{ $strandItem }}</option>
                    @endforeach
                </select>

                <select name="section" onchange="this.form.submit()" class="py-2.5 px-4 text-xs font-bold rounded-2xl border-2 border-slate-200 bg-slate-50/50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Sections</option>
                    @foreach($dbSections as $sec)
                        <option value="{{ $sec->section_name }}" {{ request('section') == $sec->section_name ? 'selected' : '' }}>{{ $sec->section_name }}</option>
                    @endforeach
                </select>

                <select name="day" onchange="this.form.submit()" class="py-2.5 px-4 text-xs font-bold rounded-2xl border-2 border-slate-200 bg-slate-50/50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Days</option>
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $d)
                        <option value="{{ $d }}" {{ request('day') == $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>

                @if(request()->hasAny(['grade_level', 'strand', 'section', 'day', 'search']))
                    <a href="{{ route('admin.schedules.index', ['view' => $currentView]) }}" class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-2xl text-xs font-black transition shrink-0 shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-3 w-full xl:w-auto justify-between xl:justify-end">
                <!-- Search Bar -->
                <form method="GET" action="{{ route('admin.schedules.index') }}" class="relative w-full xl:w-72">
                    <input type="hidden" name="view" value="{{ $currentView }}">
                    @if(request('grade_level')) <input type="hidden" name="grade_level" value="{{ request('grade_level') }}"> @endif
                    @if(request('strand')) <input type="hidden" name="strand" value="{{ request('strand') }}"> @endif
                    @if(request('section')) <input type="hidden" name="section" value="{{ request('section') }}"> @endif
                    @if(request('day')) <input type="hidden" name="day" value="{{ request('day') }}"> @endif
                    
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subject or teacher..." 
                           class="w-full pl-10 pr-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-2xl focus:border-[#8b1818] outline-none bg-slate-50/50 transition shadow-2xs">
                </form>

                <!-- VIEW MODE SWITCHER TOGGLE -->
                <div class="bg-slate-100 p-1 rounded-2xl border border-slate-200 flex items-center shrink-0">
                    <button type="button" onclick="switchViewMode('grid')" 
                            class="px-4 py-2 rounded-xl text-xs font-black flex items-center gap-2 transition cursor-pointer {{ $currentView === 'grid' ? 'bg-[#8b1818] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span class="hidden sm:inline">Timetable Grid</span>
                    </button>
                    <button type="button" onclick="switchViewMode('list')" 
                            class="px-4 py-2 rounded-xl text-xs font-black flex items-center gap-2 transition cursor-pointer {{ $currentView === 'list' ? 'bg-[#8b1818] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-list-ul"></i>
                        <span class="hidden sm:inline">List View</span>
                    </button>
                </div>
            </div>
        </div>

        @if($currentView === 'grid')
            <!-- ================= VIEW MODE 1: TIMETABLE MATRIX GRID VIEW ================= -->
            <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-sm overflow-hidden w-full">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide">Weekly Time Slot Allocation Grid</h3>
                    </div>
                    <span class="text-[11px] text-slate-500 font-bold">Showing schedules from 07:00 AM to 10:00 PM</span>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#8b1818] text-white text-xs font-black uppercase tracking-wider text-center divide-x divide-red-950/40">
                                <th class="py-4 px-4 w-36 bg-[#731414] whitespace-nowrap">Time Slot</th>
                                @foreach($weekdays as $day)
                                    <th class="py-4 px-4 min-w-[200px]">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs font-semibold text-slate-800 bg-slate-50/30">
                            @foreach($timeSlots as $slot)
                                @php
                                    list($slotStartStr, $slotEndStr) = explode(' - ', $slot);
                                    $slotStartSec = strtotime($slotStartStr);
                                    $slotEndSec = strtotime($slotEndStr);
                                @endphp
                                <tr class="divide-x divide-slate-200">
                                    <!-- TIME COLUMN -->
                                    <td class="py-4 px-3 text-center font-mono font-black text-slate-600 bg-slate-100/80 whitespace-nowrap align-middle">
                                        {{ $slot }}
                                    </td>

                                    <!-- DAY COLUMNS -->
                                    @foreach($weekdays as $day)
                                        @php
                                            $sourceCollection = isset($allSchedules) ? $allSchedules : $schedules;
                                            
                                            // Find all schedules overlapping this slot interval
                                            $cellSchedules = $sourceCollection->filter(function($sched) use ($day, $slotStartSec, $slotEndSec) {
                                                $schedDay = trim($sched->day ?? $sched->day_of_week ?? '');
                                                if (strcasecmp($schedDay, $day) !== 0) return false;

                                                if (empty($sched->start_time) || empty($sched->end_time)) return false;

                                                $sSec = strtotime($sched->start_time);
                                                $eSec = strtotime($sched->end_time);

                                                // Overlap condition: start < slotEnd AND end > slotStart
                                                return ($sSec < $slotEndSec) && ($eSec > $slotStartSec);
                                            });
                                        @endphp

                                        <td class="p-2.5 align-top min-h-[110px] w-1/6 bg-white/50 relative">
                                            @if($cellSchedules->isNotEmpty())
                                                <div class="space-y-2.5">
                                                    @foreach($cellSchedules as $schedItem)
                                                        @php
                                                            $sSec = strtotime($schedItem->start_time);
                                                            $eSec = strtotime($schedItem->end_time);
                                                            $startsHere = ($sSec >= $slotStartSec) && ($sSec < $slotEndSec);
                                                            $durationHours = round(($eSec - $sSec) / 3600, 1);
                                                        @endphp

                                                        @if($startsHere)
                                                            <!-- Main Primary Class Card -->
                                                            <div class="relative bg-white p-3.5 rounded-2xl border-2 border-slate-200 shadow-xs hover:shadow-md hover:border-[#8b1818] transition-all group overflow-hidden flex flex-col justify-between">
                                                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-[#8b1818]"></div>

                                                                <div class="space-y-1.5 pl-2">
                                                                    <div class="flex items-center justify-between gap-1 flex-wrap">
                                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl bg-slate-100 text-slate-800 text-[10px] font-black uppercase border border-slate-300 whitespace-nowrap shrink-0">
                                                                            {{ $schedItem->grade_level ?? 'Gr' }} - {{ $schedItem->section ?? 'Sec' }}
                                                                        </span>
                                                                        <span class="px-2 py-0.5 rounded-xl bg-red-50 text-[#8b1818] text-[9px] font-black uppercase border border-red-200 whitespace-nowrap shrink-0">
                                                                            {{ $schedItem->strand ?? 'GEN' }}
                                                                        </span>
                                                                    </div>

                                                                    <h4 class="text-xs font-black text-slate-900 group-hover:text-[#8b1818] transition leading-snug">
                                                                        {{ $schedItem->subject_name ?? $schedItem->subject ?? optional($schedItem->subjectRecord)->name ?? 'Subject' }}
                                                                    </h4>

                                                                    @if($schedItem->teacher)
                                                                        <p class="text-[10px] text-slate-500 font-bold flex items-center gap-1">
                                                                            <i class="fa-solid fa-user-tie text-[9px] text-slate-400"></i>
                                                                            <span>{{ $schedItem->teacher->first_name }} {{ $schedItem->teacher->last_name }}</span>
                                                                        </p>
                                                                    @endif
                                                                </div>

                                                                <div class="pt-2 border-t border-slate-100 pl-2 flex items-center justify-between mt-3">
                                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                                        <span class="font-mono text-[10px] font-bold text-amber-900 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-300 whitespace-nowrap">
                                                                            {{ date('g:i A', strtotime($schedItem->start_time)) }} - {{ date('g:i A', strtotime($schedItem->end_time)) }}
                                                                        </span>
                                                                        @if($durationHours > 1)
                                                                            <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-lg bg-amber-100 text-amber-950 border border-amber-300 whitespace-nowrap">
                                                                                ⏱️ {{ $durationHours }} HRS
                                                                            </span>
                                                                        @endif
                                                                    </div>

                                                                    <div class="flex items-center gap-1 shrink-0">
                                                                        <button type="button" onclick="openEditScheduleModal({{ $schedItem }})" title="Edit Schedule"
                                                                                class="w-6 h-6 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 transition border border-amber-200 cursor-pointer inline-flex items-center justify-center text-[10px]">
                                                                            <i class="fa-solid fa-pen"></i>
                                                                        </button>
                                                                        <form action="{{ route('admin.schedules.destroy', $schedItem->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete schedule entry?');">
                                                                            @csrf @method('DELETE')
                                                                            <button type="submit" title="Delete Schedule"
                                                                                    class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-100 text-[#8b1818] transition border border-red-200 cursor-pointer inline-flex items-center justify-center text-[10px]">
                                                                                <i class="fa-solid fa-trash-can"></i>
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <!-- Ongoing Extension Block (Multi-hour class continuation strip style) -->
                                                            <div class="p-2.5 rounded-xl bg-amber-50/70 border border-dashed border-amber-300 text-amber-950 flex items-center justify-between shadow-2xs group hover:border-amber-400 transition">
                                                                <div class="space-y-0.5">
                                                                    <span class="text-[9px] font-black uppercase tracking-wider text-amber-700 block">
                                                                        ↳ ONGOING BLOCK
                                                                    </span>
                                                                    <h5 class="text-[11px] font-black text-slate-800 leading-tight">
                                                                        {{ $schedItem->subject_name ?? $schedItem->subject }}
                                                                    </h5>
                                                                    <p class="text-[9px] font-bold text-amber-800 font-mono">
                                                                        ({{ date('g:i A', strtotime($schedItem->start_time)) }} - {{ date('g:i A', strtotime($schedItem->end_time)) }})
                                                                    </p>
                                                                </div>
                                                                <span class="px-1.5 py-0.5 rounded bg-amber-200/80 text-amber-900 text-[8px] font-black uppercase">
                                                                    {{ $schedItem->section }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <!-- Empty Slot State -->
                                                <div class="h-full w-full min-h-[90px] flex items-center justify-center text-slate-300 text-[10px] font-semibold italic select-none">
                                                    -
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            <!-- ================= VIEW MODE 2: FACULTY-GROUPED COMPACT LIST VIEW ================= -->
            <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-sm overflow-hidden w-full">
                @php
                    // Group schedules by Faculty (teacher_id)
                    $facultyGroups = isset($allSchedules) ? $allSchedules->groupBy(function($item) {
                        return $item->teacher_id ?? 0;
                    }) : collect();
                @endphp

                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide">Faculty Class Matrix Table</h3>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-black uppercase border border-amber-300">
                            1 Faculty = 1 Row
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-bold">
                        Total {{ $facultyGroups->count() }} faculty {{ Str::plural('entry', $facultyGroups->count()) }} found ({{ isset($allSchedules) ? $allSchedules->count() : 0 }} active schedules)
                    </span>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-900 text-white text-xs font-black uppercase tracking-wider border-b border-slate-800">
                                <th class="py-4 px-4 w-12 text-center">#</th>
                                <th class="py-4 px-6 w-1/4">Assigned Faculty</th>
                                <th class="py-4 px-6">Assigned Classes & Schedules (Sections & Subjects)</th>
                                <th class="py-4 px-6 text-right w-40">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs font-semibold text-slate-800">
                            @forelse($facultyGroups as $teacherId => $facultySchedules)
                                @php
                                    $firstItem = $facultySchedules->first();
                                    $teacherObj = $firstItem ? $firstItem->teacher : null;
                                    
                                    // Group schedules for this faculty by Grade, Section, Strand, and Subject Name
                                    $classGroups = $facultySchedules->groupBy(function($s) {
                                        $subj = trim($s->subject_name ?? $s->subject ?? 'Subject');
                                        $sec = trim($s->section ?? 'Section');
                                        $grade = trim($s->grade_level ?? 'Grade');
                                        $strand = trim($s->strand ?? '');
                                        return "{$grade}|{$sec}|{$strand}|{$subj}";
                                    });
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition align-top">
                                    <!-- NUMBERING COLUMN -->
                                    <td class="py-5 px-4 text-center font-bold text-slate-400 text-xs whitespace-nowrap align-top">
                                        {{ $loop->iteration }}
                                    </td>

                                    <!-- FACULTY NAME & AVATAR (APPEARS EXACTLY ONCE) -->
                                    <td class="py-5 px-6 align-top">
                                        @if($teacherObj)
                                            <div class="flex items-start gap-3">
                                                <div class="w-10 h-10 rounded-2xl bg-amber-100 border border-amber-300 text-amber-950 font-black flex items-center justify-center text-sm shrink-0 shadow-2xs">
                                                    {{ strtoupper(substr($teacherObj->first_name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <h4 class="font-black text-slate-900 text-sm leading-tight">
                                                        {{ $teacherObj->first_name }} {{ $teacherObj->last_name }}
                                                    </h4>
                                                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                                        {{ $teacherObj->email }}
                                                    </p>
                                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-black border border-slate-200 uppercase">
                                                            {{ $classGroups->count() }} {{ Str::plural('Class', $classGroups->count()) }}
                                                        </span>
                                                        <span class="px-2.5 py-0.5 rounded-lg bg-red-50 text-[#8b1818] text-[10px] font-black border border-red-200 uppercase">
                                                            {{ $facultySchedules->count() }} {{ Str::plural('Schedule', $facultySchedules->count()) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-start gap-3 text-slate-400">
                                                <div class="w-10 h-10 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 font-bold flex items-center justify-center text-sm shrink-0">
                                                    ?
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-slate-600 text-xs">Unassigned Faculty</h4>
                                                    <p class="text-[10px] text-slate-400 font-normal">Pending teacher assignment</p>
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- ASSIGNED CLASSES & SCHEDULES SUB-TREE -->
                                    <td class="py-5 px-6 align-top">
                                        <div class="space-y-3.5">
                                            @foreach($classGroups as $classKey => $schedulesInGroup)
                                                @php
                                                    $classPrimary = $schedulesInGroup->first();
                                                @endphp
                                                <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200 shadow-2xs hover:bg-white hover:border-slate-300 transition space-y-2">
                                                    <!-- Grade & Section — Strand + Subject -->
                                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-2">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-xl bg-slate-200/80 text-slate-900 text-[11px] font-black border border-slate-300 uppercase tracking-wide">
                                                                <i class="fa-solid fa-graduation-cap text-[9px] text-slate-500"></i>
                                                                <span>{{ $classPrimary->grade_level ?? 'Grade' }} - {{ $classPrimary->section }}</span>
                                                            </span>
                                                            @if($classPrimary->strand)
                                                                <span class="px-2 py-0.5 rounded-xl bg-red-50 text-[#8b1818] text-[10px] font-black border border-red-200 uppercase">
                                                                    {{ $classPrimary->strand }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <h5 class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                                            <i class="fa-solid fa-book-bookmark text-amber-600 text-[10px]"></i>
                                                            <span>{{ $classPrimary->subject_name ?? $classPrimary->subject }}</span>
                                                        </h5>
                                                    </div>

                                                    <!-- Day & Time Schedules -->
                                                    <div class="pl-2 space-y-1.5">
                                                        @foreach($schedulesInGroup as $subSched)
                                                            <div class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700 hover:text-slate-900 group/item py-0.5">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="text-amber-600 font-bold text-[10px]">•</span>
                                                                    <span class="font-bold text-slate-800">{{ $subSched->day ?? $subSched->day_of_week }}:</span>
                                                                    <span class="font-mono text-[11px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200 shadow-2xs">
                                                                        {{ date('g:i A', strtotime($subSched->start_time)) }} – {{ date('g:i A', strtotime($subSched->end_time)) }}
                                                                    </span>
                                                                </div>

                                                                <!-- Action Buttons for this specific schedule -->
                                                                <div class="flex items-center gap-1.5 opacity-80 group-hover/item:opacity-100 transition">
                                                                    <button type="button" onclick="openEditScheduleModal({{ $subSched }})" title="Edit Schedule"
                                                                            class="px-2.5 py-0.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-bold transition cursor-pointer inline-flex items-center gap-1">
                                                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                                                        <span>Edit</span>
                                                                    </button>
                                                                    <form action="{{ route('admin.schedules.destroy', $subSched->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this schedule slot?');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" title="Delete Schedule"
                                                                                class="px-2.5 py-0.5 rounded-lg bg-red-50 hover:bg-red-100 text-[#8b1818] border border-red-200 text-[10px] font-bold transition cursor-pointer inline-flex items-center gap-1">
                                                                            <i class="fa-solid fa-trash-can text-[9px]"></i>
                                                                            <span>Delete</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>

                                    <!-- ACTIONS COLUMN (FACULTY-LEVEL) -->
                                    <td class="py-5 px-6 align-top text-right">
                                        <button type="button" onclick="openScheduleModalForTeacher({{ $teacherObj->id ?? 'null' }})"
                                                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-[#8b1818] hover:text-white text-slate-800 border border-slate-300 text-xs font-black transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                            <span>Add Class</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-400 font-bold italic">
                                        No faculty class schedules found matching current filter criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </main>
</div>

<!-- INCLUDE MODALS -->
@include('admin.schedules.create')
@include('admin.schedules.import')

<!-- ================= Modal: Edit Class Schedule ================= -->
<div id="editScheduleModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 sm:p-6 transition-all duration-300 opacity-0 scale-95">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col max-h-[92vh] transform transition-all duration-300">
        <div class="px-8 py-5 border-b-2 border-slate-100 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#8b1818] text-amber-300 flex items-center justify-center shadow-xs">
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900">Edit Class Schedule</h3>
                    <p class="text-[11px] font-bold text-slate-500">Update day, teacher, section or time allocation</p>
                </div>
            </div>
            <button type="button" onclick="closeEditScheduleModal()" class="w-9 h-9 rounded-xl hover:bg-slate-200 text-slate-500 font-bold cursor-pointer transition">✕</button>
        </div>

        <!-- Real-time Conflict Alert Box (Edit) -->
        <div id="editConflictAlert" class="hidden mx-8 mt-6 p-4 rounded-2xl bg-red-50 border-2 border-red-200 text-red-900 text-xs font-bold space-y-1">
            <div class="flex items-center gap-2 text-[#8b1818] font-black text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span id="editConflictTitle">Schedule Conflict Detected</span>
            </div>
            <p id="editConflictText" class="text-slate-700 leading-relaxed font-semibold mt-1"></p>
        </div>

        <form id="editScheduleForm" method="POST" class="p-8 overflow-y-auto space-y-6">
            @csrf @method('PUT')
            <input type="hidden" id="edit_schedule_id" name="schedule_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Subject Name *</label>
                <input type="text" id="edit_subject_name" name="subject_name" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Assigned Faculty *</label>
                <select id="edit_teacher_id" name="teacher_id" required onchange="triggerEditConflictCheck()" class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                    @foreach($teachers as $t) 
                        <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->email }})</option> 
                    @endforeach
                </select>
            </div>

            <!-- Grade, Section, Strand -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- GRADE -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Grade *</label>
                    <select id="edit_grade_level" name="grade_level" required onchange="handleEditGradeChange(); triggerEditConflictCheck();" class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Grade</option>
                        @foreach($dbGrades as $g) <option value="{{ $g }}">{{ $g }}</option> @endforeach
                    </select>
                </div>

                <!-- SECTION (Filtered by selected Grade level) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Section *</label>
                    <select id="edit_section" name="section" required onchange="triggerEditConflictCheck()" class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Section</option>
                        @foreach($dbSections as $sec) <option value="{{ $sec->section_name }}" data-grade="{{ $sec->grade_level ?? '' }}">{{ $sec->section_name }}</option> @endforeach
                    </select>
                </div>

                <!-- STRAND (Hidden when Grade 7-10 is selected) -->
                <div id="edit_strand_container">
                    <label id="edit_strand_label" class="block text-xs font-bold text-slate-700 uppercase mb-2">Strand *</label>
                    <select id="edit_strand" name="strand" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Strand</option>
                        @foreach($dbStrands as $s) <option value="{{ $s }}">{{ $s }}</option> @endforeach
                    </select>
                </div>
            </div>

            <!-- DAY SELECTION CHECKLIST IN EDIT MODAL (FULLY HORIZONTAL SINGLE ROW) -->
            <div class="space-y-3 bg-slate-50/80 p-4 rounded-2xl border-2 border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wide">
                            Day Selection *
                        </label>
                        <p class="text-[10px] text-slate-500 font-bold">Select target day for this schedule entry</p>
                    </div>
                </div>

                <!-- Fully Horizontal 7-column layout -->
                <div class="grid grid-cols-7 gap-1.5 pt-1 w-full overflow-x-auto">
                    @php
                        $weekDaysList = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    @endphp
                    @foreach($weekDaysList as $dayName)
                        <label class="flex items-center justify-center gap-1 sm:gap-1.5 px-1.5 sm:px-2.5 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-400 cursor-pointer transition select-none shadow-2xs group text-center">
                            <input type="checkbox" name="days[]" value="{{ $dayName }}" 
                                   class="edit-day-checkbox w-3.5 h-3.5 text-[#8b1818] rounded border-slate-300 focus:ring-[#8b1818] cursor-pointer shrink-0"
                                   onchange="updateEditDayBadges(); triggerEditConflictCheck();">
                            <span class="text-[11px] sm:text-xs font-bold text-slate-700 group-hover:text-slate-900 whitespace-nowrap">
                                {{ substr($dayName, 0, 3) }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="pt-2 border-t border-slate-200">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Selected Days:</span>
                    <div id="editSelectedDaysBadges" class="flex flex-wrap gap-2 min-h-[30px] items-center">
                        <span class="text-xs text-slate-400 italic">No days selected.</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Start Time *</label>
                    <input type="time" id="edit_start_time" name="start_time" required onchange="triggerEditConflictCheck()" class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">End Time *</label>
                    <input type="time" id="edit_end_time" name="end_time" required onchange="triggerEditConflictCheck()" class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
                </div>
            </div>

            <div class="pt-6 border-t-2 border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeEditScheduleModal()" class="px-6 py-3 rounded-2xl border-2 border-slate-300 text-xs font-bold hover:bg-slate-50 cursor-pointer transition">Cancel</button>
                <button type="submit" id="editSubmitBtn" class="px-6 py-3 rounded-2xl bg-[#8b1818] text-white text-xs font-black hover:bg-[#731414] cursor-pointer shadow-md transition">Update Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= Modal: Success Alert ================= -->
<div id="successModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm {{ session('success') ? 'flex' : 'hidden' }} items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-sm rounded-3xl p-8 text-center space-y-6 shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mx-auto border border-emerald-200">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <h3 class="text-xl font-black text-slate-900">Success!</h3>
            <p class="text-xs font-bold text-slate-500 mt-1">{{ session('success') }}</p>
        </div>
        <button type="button" onclick="closeSuccessModal()" class="w-full py-3 rounded-2xl bg-[#8b1818] text-white text-xs font-black uppercase cursor-pointer">Okay, Continue</button>
    </div>
</div>

<!-- ================= Modal: Error / Conflict Alert ================= -->
<div id="errorModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm {{ isset($errors) && $errors->any() ? 'flex' : 'hidden' }} items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-sm rounded-3xl p-8 text-center space-y-6 shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-red-50 text-[#8b1818] flex items-center justify-center text-2xl mx-auto border border-red-200">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <h3 class="text-xl font-black text-slate-900">
                {{ isset($errors) && str_contains($errors->first(), 'Conflict') ? 'Scheduling Conflict' : 'Validation Error' }}
            </h3>
            <p class="text-xs font-bold text-slate-500 mt-1.5 leading-relaxed">{{ isset($errors) && $errors->any() ? $errors->first() : '' }}</p>
        </div>
        <button type="button" onclick="closeErrorModal()" class="w-full py-3 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black uppercase tracking-wider cursor-pointer transition">Got it, Thanks</button>
    </div>
</div>

@push('scripts')
<script>
    function switchViewMode(mode) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', mode);
        window.location.href = url.toString();
    }

    // --- CREATE MODAL DAY BADGES & CHECKLIST LOGIC ---
    function updateCreateDayBadges() {
        const checkboxes = document.querySelectorAll('.create-day-checkbox:checked');
        const container = document.getElementById('createSelectedDaysBadges');
        if (!container) return;

        if (checkboxes.length === 0) {
            container.innerHTML = `<span class="text-xs text-slate-400 italic">No days selected yet. Please check boxes above.</span>`;
            return;
        }

        let html = '';
        checkboxes.forEach(cb => {
            const dayName = cb.value;
            html += `
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-100/90 text-amber-950 border border-amber-300 font-black text-xs shadow-2xs animate-fadeIn">
                    <span>${dayName}</span>
                    <button type="button" onclick="uncheckCreateDay('${dayName}')" title="Remove ${dayName}"
                            class="w-4 h-4 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-900 inline-flex items-center justify-center text-[10px] cursor-pointer transition font-bold">
                        ✕
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function uncheckCreateDay(dayName) {
        const cb = document.querySelector(`.create-day-checkbox[value="${dayName}"]`);
        if (cb) {
            cb.checked = false;
            updateCreateDayBadges();
            triggerCreateConflictCheck();
        }
    }

    function selectPresetDays(daysArray) {
        document.querySelectorAll('.create-day-checkbox').forEach(cb => {
            cb.checked = daysArray.includes(cb.value);
        });
        updateCreateDayBadges();
        triggerCreateConflictCheck();
    }

    function clearAllDays() {
        document.querySelectorAll('.create-day-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateCreateDayBadges();
        triggerCreateConflictCheck();
    }

    // Real-time conflict checking API call for Create form
    let createConflictTimeout = null;
    function triggerCreateConflictCheck() {
        clearTimeout(createConflictTimeout);
        createConflictTimeout = setTimeout(() => {
            const teacherId = document.getElementById('create_teacher_id')?.value;
            const section = document.getElementById('create_section')?.value;
            const startTime = document.getElementById('create_start_time')?.value;
            const endTime = document.getElementById('create_end_time')?.value;
            
            const selectedDays = Array.from(document.querySelectorAll('.create-day-checkbox:checked')).map(c => c.value);
            const alertBox = document.getElementById('createConflictAlert');
            const alertText = document.getElementById('createConflictText');
            const submitBtn = document.getElementById('createSubmitBtn');

            if (!teacherId || !section || !startTime || !endTime || selectedDays.length === 0) {
                if (alertBox) alertBox.classList.add('hidden');
                if (submitBtn) submitBtn.disabled = false;
                return;
            }

            fetch('{{ route("admin.schedules.check-conflict") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    section: section,
                    start_time: startTime,
                    end_time: endTime,
                    days: selectedDays
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.has_conflict) {
                    if (alertBox) {
                        alertText.innerText = data.message;
                        alertBox.classList.remove('hidden');
                    }
                } else {
                    if (alertBox) alertBox.classList.add('hidden');
                }
            })
            .catch(err => console.error('Conflict check error:', err));
        }, 200);
    }

    // --- EDIT MODAL DAY BADGES & CHECKLIST LOGIC ---
    function updateEditDayBadges() {
        const checkboxes = document.querySelectorAll('.edit-day-checkbox:checked');
        const container = document.getElementById('editSelectedDaysBadges');
        if (!container) return;

        if (checkboxes.length === 0) {
            container.innerHTML = `<span class="text-xs text-slate-400 italic">No days selected.</span>`;
            return;
        }

        let html = '';
        checkboxes.forEach(cb => {
            const dayName = cb.value;
            html += `
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-100/90 text-amber-950 border border-amber-300 font-black text-xs shadow-2xs">
                    <span>${dayName}</span>
                    <button type="button" onclick="uncheckEditDay('${dayName}')" title="Remove ${dayName}"
                            class="w-4 h-4 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-900 inline-flex items-center justify-center text-[10px] cursor-pointer transition font-bold">
                        ✕
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function uncheckEditDay(dayName) {
        const cb = document.querySelector(`.edit-day-checkbox[value="${dayName}"]`);
        if (cb) {
            cb.checked = false;
            updateEditDayBadges();
            triggerEditConflictCheck();
        }
    }

    let editConflictTimeout = null;
    function triggerEditConflictCheck() {
        clearTimeout(editConflictTimeout);
        editConflictTimeout = setTimeout(() => {
            const scheduleId = document.getElementById('edit_schedule_id')?.value;
            const teacherId = document.getElementById('edit_teacher_id')?.value;
            const section = document.getElementById('edit_section')?.value;
            const startTime = document.getElementById('edit_start_time')?.value;
            const endTime = document.getElementById('edit_end_time')?.value;
            
            const selectedDays = Array.from(document.querySelectorAll('.edit-day-checkbox:checked')).map(c => c.value);
            const alertBox = document.getElementById('editConflictAlert');
            const alertText = document.getElementById('editConflictText');

            if (!teacherId || !section || !startTime || !endTime || selectedDays.length === 0) {
                if (alertBox) alertBox.classList.add('hidden');
                return;
            }

            fetch('{{ route("admin.schedules.check-conflict") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    ignore_id: scheduleId,
                    teacher_id: teacherId,
                    section: section,
                    start_time: startTime,
                    end_time: endTime,
                    days: selectedDays
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.has_conflict) {
                    if (alertBox) {
                        alertText.innerText = data.message;
                        alertBox.classList.remove('hidden');
                    }
                } else {
                    if (alertBox) alertBox.classList.add('hidden');
                }
            })
            .catch(err => console.error('Edit Conflict check error:', err));
        }, 200);
    }

    // Modal Show/Hide Helpers
    function openScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        if (modal) { 
            modal.classList.remove('hidden'); 
            modal.classList.add('flex'); 
            setTimeout(() => { modal.classList.remove('opacity-0', 'scale-95'); modal.classList.add('opacity-100', 'scale-100'); }, 10);
            document.body.classList.add('overflow-hidden'); 
            updateCreateDayBadges();
        }
    }
    function openScheduleModalForTeacher(teacherId) {
        openScheduleModal();
        if (teacherId && teacherId !== 'null') {
            const select = document.getElementById('create_teacher_id');
            if (select) {
                select.value = teacherId;
                if (typeof triggerCreateConflictCheck === 'function') {
                    triggerCreateConflictCheck();
                }
            }
        }
    }
    function closeScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        if (modal) { 
            modal.classList.remove('opacity-100', 'scale-100'); modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); }, 200);
        }
    }
    function handleCreateGradeChange() {
        const gradeVal = document.getElementById('create_grade_level')?.value || '';
        const strandContainer = document.getElementById('create_strand_container');
        const strandSelect = document.getElementById('create_strand_select');
        const sectionSelect = document.getElementById('create_section');

        const numGrade = parseInt(gradeVal.replace(/\D/g, ''));
        const isJHS = (numGrade >= 7 && numGrade <= 10);

        // 1. Hide Strand container if Grade 7-10 selected
        if (isJHS) {
            if (strandContainer) strandContainer.classList.add('hidden');
            if (strandSelect) {
                strandSelect.value = '';
                strandSelect.removeAttribute('required');
            }
        } else {
            if (strandContainer) strandContainer.classList.remove('hidden');
            if (strandSelect) {
                if (gradeVal) {
                    strandSelect.setAttribute('required', 'required');
                } else {
                    strandSelect.removeAttribute('required');
                }
            }
        }

        // 2. Filter Section options based on selected grade
        if (sectionSelect) {
            const cleanGradeNum = gradeVal ? gradeVal.replace(/\D/g, '') : '';
            let isCurrentSectionValid = false;

            Array.from(sectionSelect.options).forEach(opt => {
                if (!opt.value) return;
                
                const optGrade = opt.getAttribute('data-grade') || '';
                const cleanOptGradeNum = optGrade.replace(/\D/g, '');

                if (!cleanGradeNum || cleanOptGradeNum === cleanGradeNum) {
                    opt.hidden = false;
                    opt.disabled = false;
                    opt.style.display = '';
                    if (opt.value === sectionSelect.value) {
                        isCurrentSectionValid = true;
                    }
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                    opt.style.display = 'none';
                }
            });

            if (!isCurrentSectionValid) {
                sectionSelect.value = '';
            }
        }
    }

    function handleEditGradeChange() {
        const gradeVal = document.getElementById('edit_grade_level')?.value || '';
        const strandContainer = document.getElementById('edit_strand_container');
        const strandSelect = document.getElementById('edit_strand');
        const sectionSelect = document.getElementById('edit_section');

        const numGrade = parseInt(gradeVal.replace(/\D/g, ''));
        const isJHS = (numGrade >= 7 && numGrade <= 10);

        // 1. Hide Strand container if Grade 7-10 selected
        if (isJHS) {
            if (strandContainer) strandContainer.classList.add('hidden');
            if (strandSelect) {
                strandSelect.value = '';
                strandSelect.removeAttribute('required');
            }
        } else {
            if (strandContainer) strandContainer.classList.remove('hidden');
            if (strandSelect) {
                if (gradeVal) {
                    strandSelect.setAttribute('required', 'required');
                } else {
                    strandSelect.removeAttribute('required');
                }
            }
        }

        // 2. Filter Section options based on selected grade
        if (sectionSelect) {
            const cleanGradeNum = gradeVal ? gradeVal.replace(/\D/g, '') : '';
            let isCurrentSectionValid = false;

            Array.from(sectionSelect.options).forEach(opt => {
                if (!opt.value) return;
                
                const optGrade = opt.getAttribute('data-grade') || '';
                const cleanOptGradeNum = optGrade.replace(/\D/g, '');

                if (!cleanGradeNum || cleanOptGradeNum === cleanGradeNum) {
                    opt.hidden = false;
                    opt.disabled = false;
                    opt.style.display = '';
                    if (opt.value === sectionSelect.value) {
                        isCurrentSectionValid = true;
                    }
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                    opt.style.display = 'none';
                }
            });

            if (!isCurrentSectionValid) {
                sectionSelect.value = '';
            }
        }
    }

    function openEditScheduleModal(schedule) {
        const modal = document.getElementById('editScheduleModal');
        const form = document.getElementById('editScheduleForm');
        form.action = `/admin/schedules/${schedule.id}`;
        document.getElementById('edit_schedule_id').value = schedule.id || '';
        document.getElementById('edit_subject_name').value = schedule.subject_name || schedule.subject || '';
        document.getElementById('edit_teacher_id').value = schedule.teacher_id || '';

        const gradeVal = schedule.grade_level || schedule.academic_section?.grade_level || '';
        const sectionVal = schedule.section || schedule.academic_section?.section_name || '';
        const strandVal = schedule.strand || schedule.academic_section?.strand || '';

        document.getElementById('edit_grade_level').value = gradeVal;
        
        handleEditGradeChange();

        document.getElementById('edit_section').value = sectionVal;
        if (document.getElementById('edit_strand')) {
            document.getElementById('edit_strand').value = strandVal;
        }

        const schedDay = schedule.day || schedule.day_of_week || 'Monday';
        document.querySelectorAll('.edit-day-checkbox').forEach(cb => {
            cb.checked = (cb.value === schedDay);
        });
        updateEditDayBadges();

        document.getElementById('edit_start_time').value = schedule.start_time || '';
        document.getElementById('edit_end_time').value = schedule.end_time || '';
        
        if (modal) { 
            modal.classList.add('flex'); 
            setTimeout(() => { modal.classList.remove('opacity-0', 'scale-95'); modal.classList.add('opacity-100', 'scale-100'); }, 10);
            document.body.classList.add('overflow-hidden'); 
        }
    }
    function closeEditScheduleModal() {
        const modal = document.getElementById('editScheduleModal');
        if (modal) { 
            modal.classList.remove('opacity-100', 'scale-100'); modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); }, 200);
        }
    }
    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        if (modal) { modal.classList.remove('flex'); modal.classList.add('hidden'); }
    }
    function closeErrorModal() {
        const modal = document.getElementById('errorModal');
        if (modal) { modal.classList.remove('flex'); modal.classList.add('hidden'); }
    }
    function handleOutsideClick(event) {
        if (event.target === document.getElementById('scheduleModal')) closeScheduleModal();
        if (event.target === document.getElementById('editScheduleModal')) closeEditScheduleModal();
        if (event.target === document.getElementById('successModal')) closeSuccessModal();
        if (event.target === document.getElementById('errorModal')) closeErrorModal();
        if (event.target === document.getElementById('importScheduleModal')) closeImportModal();
    }
    window.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeScheduleModal(); closeEditScheduleModal(); closeSuccessModal(); closeErrorModal(); closeImportModal(); }
    });
</script>
@endpush
@endsection