@extends('layouts.app')

@section('title', 'Class Schedules Matrix - SIATRACK')

@push('styles')
<style>
    .master-timetable-grid-scroll {
        max-height: min(72vh, 780px);
        overflow: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .master-timetable-grid-table {
        width: max-content;
        min-width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .master-timetable-grid-table thead th {
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .master-timetable-grid-table .time-axis {
        position: sticky;
        left: 0;
        z-index: 25;
        width: 132px;
        min-width: 132px;
        box-shadow: 1px 0 0 #cbd5e1;
    }

    .master-timetable-grid-table thead .time-axis {
        z-index: 30;
    }

    .schedule-grid-card:hover,
    .schedule-grid-card:focus-within {
        z-index: 40;
    }

    .schedule-grid-card:focus-visible {
        outline: 2px solid #8b1818;
        outline-offset: 1px;
    }

    .schedule-grid-card-details {
        display: none;
        position: absolute;
        z-index: 50;
        left: 0;
        width: max-content;
        min-width: min(200px, calc(100vw - 24px));
        max-width: min(280px, calc(100vw - 24px));
    }

    .schedule-grid-card:hover .schedule-grid-card-details,
    .schedule-grid-card:focus-within .schedule-grid-card-details {
        display: block;
    }

    .schedule-grid-card-details.below {
        top: calc(100% + 4px);
    }

    .schedule-grid-card-details.above {
        bottom: calc(100% + 4px);
    }

    .schedule-grid-card-details.right {
        right: 0;
        left: auto;
    }

    /* Print specific optimization */
    @media print {
        body {
            background: white !important;
            color: black !important;
            font-size: 11px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Hide all main layout elements during print */
        #app-main > *:not(#printScheduleModal),
        aside,
        header,
        nav,
        .no-print,
        #scheduleModal,
        #editScheduleModal,
        #successModal,
        #errorModal,
        #importScheduleModal {
            display: none !important;
        }

        #printScheduleModal {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            min-height: 100% !important;
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            z-index: 999999 !important;
            display: block !important;
            opacity: 1 !important;
        }

        #printScheduleModal .modal-chrome {
            display: none !important;
        }

        #printableScheduleCard {
            width: 100% !important;
            max-width: 100% !important;
            box-shadow: none !important;
            border: 1px solid #94a3b8 !important;
            border-radius: 0 !important;
            padding: 12px !important;
            margin: 0 !important;
            background: white !important;
            page-break-inside: avoid !important;
        }

        #printableScheduleCard table {
            border-collapse: collapse !important;
            width: 100% !important;
        }

        #printableScheduleCard th, 
        #printableScheduleCard td {
            border: 1px solid #cbd5e1 !important;
            padding: 4px 6px !important;
        }

        #printableScheduleCard .print-header {
            border-bottom: 2px solid #0f172a !important;
            padding-bottom: 8px !important;
            margin-bottom: 10px !important;
        }

        @page {
            size: landscape;
            margin: 8mm;
        }
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60">

    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-[#8b1818] text-white flex items-center justify-center text-lg shadow-sm shrink-0">
                <i class="fa-solid fa-calendar-days text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Class Schedules & Master Timetable</h1>
                    <span class="px-3 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wide">
                        A.Y. {{ $activeSchoolYear ?? '2025-2026' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">7:00 AM – 5:00 PM Timetable Matrix with Classroom & Faculty Print Preview</p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <!-- Open Print Preview Modal Button -->
            <button type="button" onclick="openPrintScheduleModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 font-black text-xs uppercase tracking-wider transition border-2 border-slate-200 shrink-0 cursor-pointer shadow-2xs hover:border-[#8b1818]">
                <i class="fa-solid fa-print text-sm text-[#8b1818]"></i>
                <span>Print Schedule / PDF</span>
            </button>

            <button type="button" onclick="openScheduleModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider shadow-md shadow-red-950/20 transition shrink-0 cursor-pointer">
                <i class="fa-solid fa-plus text-xs text-amber-300"></i>
                <span>Add Schedule</span>
            </button>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="py-6 px-6 sm:px-10 lg:px-12 w-full space-y-6 flex-1 max-w-[1700px] mx-auto">

        @php
            $dbSections = isset($academicSections) && $academicSections->isNotEmpty()
                ? $academicSections
                : (\Illuminate\Support\Facades\Schema::hasTable('academic_sections') 
                    ? \Illuminate\Support\Facades\DB::table('academic_sections')->orderBy('section_name')->get() 
                    : collect());

            $dbStrands = \Illuminate\Support\Facades\Schema::hasTable('academic_sections') 
                ? \Illuminate\Support\Facades\DB::table('academic_sections')->whereNotNull('strand')->where('strand', '!=', '')->distinct()->pluck('strand') 
                : collect();

            $dbGrades = \Illuminate\Support\Facades\Schema::hasTable('academic_sections') 
                ? \Illuminate\Support\Facades\DB::table('academic_sections')->whereNotNull('grade_level')->where('grade_level', '!=', '')->distinct()->orderBy('grade_level')->pluck('grade_level') 
                : collect();

            // 7:00 AM – 5:00 PM (10 one-hour slots)
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

            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $currentView = request('view', $viewMode ?? 'grid');
        @endphp

        <!-- FILTERS & DISPLAY MODE SWITCHER BAR -->
        <div class="bg-white p-4 rounded-3xl border-2 border-slate-200 shadow-xs w-full flex flex-col xl:flex-row gap-4 items-stretch xl:items-center justify-between">
            <form id="filterForm" method="GET" action="{{ route('admin.schedules.index') }}" class="flex flex-wrap items-center gap-2.5 w-full xl:w-auto">
                <input type="hidden" name="view" id="viewInput" value="{{ $currentView }}">

                <select name="grade_level" onchange="this.form.submit()" class="py-2 px-3.5 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Grade Levels</option>
                    @foreach($dbGrades as $gradeItem)
                        <option value="{{ $gradeItem }}" {{ request('grade_level') == $gradeItem ? 'selected' : '' }}>{{ $gradeItem }}</option>
                    @endforeach
                </select>

                <select name="strand" onchange="this.form.submit()" class="py-2 px-3.5 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Strands</option>
                    @foreach($dbStrands as $strandItem)
                        <option value="{{ $strandItem }}" {{ request('strand') == $strandItem ? 'selected' : '' }}>{{ $strandItem }}</option>
                    @endforeach
                </select>

                <select name="section" onchange="this.form.submit()" class="py-2 px-3.5 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Sections / Classrooms</option>
                    @foreach($dbSections as $sec)
                        @php
                            $secLabel = $sec->section_name;
                            if(!empty($sec->grade_level)) {
                                $secLabel = (str_starts_with(strtolower($sec->grade_level), 'grade') ? $sec->grade_level : 'Grade ' . $sec->grade_level) . ' - ' . $secLabel;
                            }
                        @endphp
                        <option value="{{ $sec->section_name }}" {{ request('section') == $sec->section_name ? 'selected' : '' }}>{{ $secLabel }}</option>
                    @endforeach
                </select>

                <select name="day" onchange="this.form.submit()" class="py-2 px-3.5 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Days</option>
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $d)
                        <option value="{{ $d }}" {{ request('day') == $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>

                @if(request()->hasAny(['grade_level', 'strand', 'section', 'day', 'search']))
                    <a href="{{ route('admin.schedules.index', ['view' => $currentView]) }}" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-black transition shrink-0 inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-3 w-full xl:w-auto justify-between xl:justify-end">
                <!-- Search Bar -->
                <form method="GET" action="{{ route('admin.schedules.index') }}" class="relative w-full xl:w-64">
                    <input type="hidden" name="view" value="{{ $currentView }}">
                    @if(request('grade_level')) <input type="hidden" name="grade_level" value="{{ request('grade_level') }}"> @endif
                    @if(request('strand')) <input type="hidden" name="strand" value="{{ request('strand') }}"> @endif
                    @if(request('section')) <input type="hidden" name="section" value="{{ request('section') }}"> @endif
                    @if(request('day')) <input type="hidden" name="day" value="{{ request('day') }}"> @endif
                    
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subject/teacher..." 
                           class="w-full pl-9 pr-3.5 py-2 text-xs font-bold border border-slate-200 rounded-xl focus:border-[#8b1818] outline-none bg-slate-50 transition">
                </form>

                <!-- VIEW MODE SWITCHER TOGGLE -->
                <div class="bg-slate-100 p-1 rounded-xl border border-slate-200 flex items-center shrink-0">
                    <button type="button" onclick="switchViewMode('grid')" 
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black flex items-center gap-1.5 transition cursor-pointer {{ $currentView === 'grid' ? 'bg-[#8b1818] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-calendar-days text-[11px]"></i>
                        <span class="hidden sm:inline">Timetable Grid</span>
                    </button>
                    <button type="button" onclick="switchViewMode('list')" 
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black flex items-center gap-1.5 transition cursor-pointer {{ $currentView === 'list' ? 'bg-[#8b1818] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-list-ul text-[11px]"></i>
                        <span class="hidden sm:inline">List View</span>
                    </button>
                </div>
            </div>
        </div>

        @if($currentView === 'grid')
            <!-- ================= VIEW MODE 1: MINIMIZED TIMETABLE MATRIX GRID VIEW ================= -->
            <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-sm overflow-hidden w-full">
                @php
                    $timetableSource = isset($allSchedules) ? $allSchedules : $schedules;
                    $timetableStart = strtotime('07:00 AM');
                    $timetableEnd = strtotime('05:00 PM');
                    $minutesPerGridRow = 15;
                    $gridRowsPerHour = 60 / $minutesPerGridRow;
                    $timetableLaneWidth = 200;
                    $timetableRows = [];
                    foreach ($timeSlots as $hourSlot) {
                        list($hourStart, $hourEnd) = explode(' - ', $hourSlot);
                        $hourStartSec = strtotime($hourStart);
                        $hourEndSec = strtotime($hourEnd);
                        for ($time = $hourStartSec; $time < $hourEndSec; $time += 30 * 60) {
                            $rowEnd = min($time + 30 * 60, $hourEndSec);
                            $timetableRows[] = [
                                'label' => date('g:i A', $time) . ' - ' . date('g:i A', $rowEnd),
                                'start_sec' => $time,
                                'end_sec' => $rowEnd,
                            ];
                        }
                    }
                    $dayTimetables = [];

                    foreach ($weekdays as $day) {
                        $daySchedules = $timetableSource->filter(function ($schedule) use ($day) {
                            $scheduleDay = trim((string) ($schedule->day ?? $schedule->day_of_week ?? ''));
                            return strcasecmp($scheduleDay, $day) === 0;
                        })->map(function ($schedule) use ($timetableStart, $timetableEnd, $minutesPerGridRow) {
                            if (empty($schedule->start_time) || empty($schedule->end_time)) {
                                return null;
                            }

                            $start = strtotime($schedule->start_time);
                            $end = strtotime($schedule->end_time);
                            if ($start === false || $end === false) {
                                return null;
                            }

                            $visibleStart = max($start, $timetableStart);
                            $visibleEnd = min($end, $timetableEnd);
                            if ($visibleEnd <= $visibleStart) {
                                return null;
                            }

                            $secondsPerGridRow = $minutesPerGridRow * 60;
                            return [
                                'schedule' => $schedule,
                                'start_sec' => $visibleStart,
                                'end_sec' => $visibleEnd,
                                'grid_start' => (int) floor(($visibleStart - $timetableStart) / $secondsPerGridRow),
                                'grid_end' => (int) ceil(($visibleEnd - $timetableStart) / $secondsPerGridRow),
                            ];
                        })->filter()->sortBy('start_sec')->values()->all();

                        $laneEnds = [];
                        foreach ($daySchedules as $index => $entry) {
                            $lane = null;
                            foreach ($laneEnds as $laneIndex => $laneEnd) {
                                if ($laneEnd <= $entry['start_sec']) {
                                    $lane = $laneIndex;
                                    break;
                                }
                            }

                            if ($lane === null) {
                                $lane = count($laneEnds);
                            }

                            $laneEnds[$lane] = $entry['end_sec'];
                            $daySchedules[$index]['lane'] = $lane;
                        }

                        $dayTimetables[$day] = [
                            'schedules' => $daySchedules,
                            'column_count' => max(1, count($laneEnds)),
                        ];
                    }
                @endphp

                <div class="px-6 py-3.5 border-b border-slate-200 bg-slate-50/80 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide">Master Schedule Timetable</h3>
                        @if(request('section'))
                            <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 text-[10px] font-black border border-amber-300">
                                Filtered: Section {{ request('section') }}
                            </span>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-500 font-semibold">07:00 AM – 05:00 PM <span class="text-slate-400">· Scroll to see the full week</span></span>
                </div>

                <div class="master-timetable-grid-scroll w-full">
                    <table class="master-timetable-grid-table text-left">
                        <thead>
                            <tr class="bg-[#8b1818] text-white text-[11px] font-black uppercase tracking-wider text-center divide-x divide-red-900">
                                <th class="time-axis py-3 px-3 bg-[#731414] whitespace-nowrap">Time Slot</th>
                                @foreach($weekdays as $day)
                                    <th class="bg-[#8b1818] py-3 px-3" style="min-width: {{ max($timetableLaneWidth, $dayTimetables[$day]['column_count'] * $timetableLaneWidth) }}px">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs font-semibold text-slate-800 bg-slate-50/20">
                            @foreach($timetableRows as $slotIndex => $slot)
                                @php
                                    $slotStartSec = $slot['start_sec'];
                                    $slotEndSec = $slot['end_sec'];
                                    $isLunchHour = $slotStartSec >= strtotime('12:00 PM') && $slotStartSec < strtotime('01:00 PM');
                                @endphp
                                <tr class="divide-x divide-slate-200 hover:bg-slate-50/60 transition {{ $isLunchHour ? 'bg-amber-50/20' : '' }}">
                                    <!-- TIME COLUMN -->
                                    <td class="time-axis px-2 text-center font-mono text-[10px] font-bold text-slate-600 bg-slate-100 whitespace-nowrap align-middle h-[40px]">
                                        {{ $slot['label'] }}
                                    </td>

                                    <!-- DAY COLUMNS -->
                                    @if($loop->first)
                                        @foreach($weekdays as $day)
                                            @php
                                                $dayLayout = $dayTimetables[$day];
                                            @endphp
                                            <td rowspan="{{ count($timetableRows) }}" class="p-0 align-top bg-white/40" style="min-width: {{ max($timetableLaneWidth, $dayLayout['column_count'] * $timetableLaneWidth) }}px">
                                                <div class="grid relative min-h-[800px]" style="grid-template-columns: repeat({{ $dayLayout['column_count'] }}, minmax({{ $timetableLaneWidth }}px, 1fr)); grid-template-rows: repeat({{ count($timeSlots) * $gridRowsPerHour }}, 20px)">
                                                    @foreach($timetableRows as $daySlotIndex => $daySlot)
                                                        @php
                                                            $daySlotStartSec = $daySlot['start_sec'];
                                                            $daySlotEndSec = $daySlot['end_sec'];
                                                            $isLunchRow = $daySlotStartSec >= strtotime('12:00 PM') && $daySlotStartSec < strtotime('01:00 PM');
                                                        @endphp
                                                        <div aria-hidden="true" class="z-0 border-b border-slate-200 {{ $isLunchRow ? 'bg-amber-50/20' : '' }}" style="grid-column: 1 / -1; grid-row: {{ ($daySlotIndex * 2) + 1 }} / span 2"></div>
                                                    @endforeach

                                                    @foreach($dayLayout['schedules'] as $entry)
                                                        @php
                                                            $schedItem = $entry['schedule'];
                                                            $subjectLabel = $schedItem->subject_name ?? $schedItem->subject ?? optional($schedItem->subjectRecord)->name ?? 'Subject';
                                                            $teacherLabel = $schedItem->teacher
                                                                ? trim($schedItem->teacher->first_name . ' ' . $schedItem->teacher->last_name)
                                                                : 'Unassigned';
                                                            $sectionLabel = trim(($schedItem->grade_level ? $schedItem->grade_level . ' ' : '') . $schedItem->section);
                                                            $timeLabel = date('g:i A', strtotime($schedItem->start_time)) . ' - ' . date('g:i A', strtotime($schedItem->end_time));
                                                            $visibleRows = $entry['grid_end'] - $entry['grid_start'];
                                                            $isCompact = $visibleRows < 4;
                                                            $detailsPosition = $entry['grid_start'] >= ($gridRowsPerHour * 5) ? 'above' : 'below';
                                                            $detailsAlignment = $entry['lane'] >= floor($dayLayout['column_count'] / 2) ? 'right' : 'left';
                                                        @endphp
                                                        <div
                                                            title="{{ $subjectLabel }} — {{ $sectionLabel }} — {{ $teacherLabel }} — {{ $timeLabel }}"
                                                            aria-label="{{ $subjectLabel }}, {{ $sectionLabel }}, {{ $teacherLabel }}, {{ $timeLabel }}. Focus or hover to see full details."
                                                            tabindex="0"
                                                            role="group"
                                                            class="schedule-grid-card relative z-10 m-0.5 min-w-0 rounded-md border border-slate-200 border-l-[3px] border-l-[#8b1818] bg-white p-1 shadow-2xs transition hover:border-slate-300 hover:shadow-sm"
                                                            style="grid-column: {{ $entry['lane'] + 1 }}; grid-row: {{ $entry['grid_start'] + 1 }} / {{ $entry['grid_end'] + 1 }}">
                                                            @if($visibleRows === 1)
                                                                <div class="flex min-w-0 items-center gap-1 leading-[8px]">
                                                                    <span class="shrink-0 truncate text-[6px] font-black uppercase text-slate-500">{{ $sectionLabel }}</span>
                                                                    <h4 class="truncate text-[8px] font-black text-slate-900">{{ $subjectLabel }}</h4>
                                                                </div>
                                                            @elseif($isCompact)
                                                                <div class="truncate text-[7px] font-black uppercase leading-[9px] text-slate-600">{{ $sectionLabel }}</div>
                                                                <h4 class="truncate text-[8px] font-black leading-[10px] text-slate-900">{{ $subjectLabel }}</h4>
                                                            @else
                                                                <div class="truncate rounded bg-slate-100 px-1 py-0.5 text-[7px] font-black uppercase leading-[9px] text-slate-700">
                                                                    {{ $sectionLabel }}
                                                                </div>
                                                                <h4 class="mt-0.5 truncate text-[9px] font-black leading-3 text-slate-900">{{ $subjectLabel }}</h4>
                                                                <p class="mt-0.5 truncate text-[8px] font-semibold leading-[10px] text-slate-500">
                                                                    <i class="fa-solid fa-user-tie mr-0.5 text-[7px] text-slate-400"></i>{{ $teacherLabel }}
                                                                </p>
                                                                <div class="mt-0.5 border-t border-slate-100 pt-0.5 font-mono text-[7px] font-bold leading-[9px] text-slate-600">{{ $timeLabel }}</div>
                                                            @endif

                                                            <div class="schedule-grid-card-details {{ $detailsPosition }} {{ $detailsAlignment }} rounded-lg border border-slate-200 border-l-4 border-l-[#8b1818] bg-white p-3 text-left shadow-xl">
                                                                <div class="truncate text-[9px] font-black uppercase tracking-wide text-slate-500">{{ $sectionLabel }}</div>
                                                                <h4 class="mt-1 whitespace-normal break-words text-xs font-black leading-4 text-slate-900">{{ $subjectLabel }}</h4>
                                                                <p class="mt-1 flex items-start gap-1 text-[10px] font-semibold leading-4 text-slate-600">
                                                                    <i class="fa-solid fa-user-tie mt-0.5 text-[9px] text-slate-400"></i>
                                                                    <span class="break-words">{{ $teacherLabel }}</span>
                                                                </p>
                                                                <div class="mt-1 border-t border-slate-100 pt-1 font-mono text-[10px] font-bold leading-4 text-slate-700">{{ $timeLabel }}</div>
                                                                <div class="mt-2 flex justify-end gap-1.5 border-t border-slate-100 pt-2">
                                                                    <button type="button" onclick="openEditScheduleModal({{ $schedItem }})" title="Edit schedule" aria-label="Edit {{ $subjectLabel }}"
                                                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-xs text-amber-900 hover:bg-amber-100">
                                                                        <i class="fa-solid fa-pen"></i>
                                                                    </button>
                                                                    <form action="{{ route('admin.schedules.destroy', $schedItem->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete schedule?');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" title="Delete schedule" aria-label="Delete {{ $subjectLabel }}"
                                                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-xs text-[#8b1818] hover:bg-red-100">
                                                                            <i class="fa-solid fa-trash-can"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            <!-- ================= VIEW MODE 2: FACULTY-GROUPED LIST VIEW ================= -->
            <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-sm overflow-hidden w-full">
                @php
                    $facultyGroups = isset($allSchedules) ? $allSchedules->groupBy(function($item) {
                        return $item->teacher_id ?? 0;
                    }) : collect();
                @endphp

                <div class="px-6 py-3.5 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide">Faculty Class Matrix Table</h3>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-black uppercase border border-amber-300">
                            1 Faculty = 1 Row
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-bold">
                        Total {{ $facultyGroups->count() }} faculty entries
                    </span>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-900 text-white text-xs font-black uppercase tracking-wider border-b border-slate-800">
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-6 w-1/4">Assigned Faculty</th>
                                <th class="py-3 px-6">Assigned Classes & Schedules</th>
                                <th class="py-3 px-6 text-right w-40">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs font-semibold text-slate-800">
                            @forelse($facultyGroups as $teacherId => $facultySchedules)
                                @php
                                    $firstItem = $facultySchedules->first();
                                    $teacherObj = $firstItem ? $firstItem->teacher : null;
                                    
                                    $classGroups = $facultySchedules->groupBy(function($s) {
                                        $subj = trim($s->subject_name ?? $s->subject ?? 'Subject');
                                        $sec = trim($s->section ?? 'Section');
                                        $grade = trim($s->grade_level ?? 'Grade');
                                        $strand = trim($s->strand ?? '');
                                        return "{$grade}|{$sec}|{$strand}|{$subj}";
                                    });
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition align-top">
                                    <td class="py-4 px-4 text-center font-bold text-slate-400 text-xs whitespace-nowrap align-top">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="py-4 px-6 align-top">
                                        @if($teacherObj)
                                            <div class="flex items-start gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-300 text-amber-950 font-black flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                                    {{ strtoupper(substr($teacherObj->first_name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <h4 class="font-black text-slate-900 text-xs leading-tight">
                                                        {{ $teacherObj->first_name }} {{ $teacherObj->last_name }}
                                                    </h4>
                                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                                        {{ $teacherObj->email }}
                                                    </p>
                                                    <div class="mt-2 flex flex-wrap gap-1">
                                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[9px] font-black border border-slate-200 uppercase">
                                                            {{ $classGroups->count() }} {{ Str::plural('Class', $classGroups->count()) }}
                                                        </span>
                                                        <span class="px-2 py-0.5 rounded bg-red-50 text-[#8b1818] text-[9px] font-black border border-red-200 uppercase">
                                                            {{ $facultySchedules->count() }} {{ Str::plural('Schedule', $facultySchedules->count()) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-start gap-3 text-slate-400">
                                                <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 text-slate-400 font-bold flex items-center justify-center text-xs shrink-0">
                                                    ?
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-slate-600 text-xs">Unassigned Faculty</h4>
                                                    <p class="text-[10px] text-slate-400 font-normal">Pending teacher assignment</p>
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="py-4 px-6 align-top">
                                        <div class="space-y-2.5">
                                            @foreach($classGroups as $classKey => $schedulesInGroup)
                                                @php
                                                    $classPrimary = $schedulesInGroup->first();
                                                @endphp
                                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200 shadow-2xs space-y-1.5">
                                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-1.5">
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-200 text-slate-900 text-[10px] font-black uppercase">
                                                                {{ $classPrimary->grade_level ?? 'Grade' }} - {{ $classPrimary->section }}
                                                            </span>
                                                            @if($classPrimary->strand)
                                                                <span class="px-1.5 py-0.5 rounded bg-red-50 text-[#8b1818] text-[9px] font-black uppercase">
                                                                    {{ $classPrimary->strand }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <h5 class="text-xs font-black text-slate-900">
                                                            {{ $classPrimary->subject_name ?? $classPrimary->subject }}
                                                        </h5>
                                                    </div>

                                                    <div class="pl-1 space-y-1">
                                                        @foreach($schedulesInGroup as $subSched)
                                                            <div class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-bold text-slate-800">{{ $subSched->day ?? $subSched->day_of_week }}:</span>
                                                                    <span class="font-mono text-[10px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200">
                                                                        {{ date('g:i A', strtotime($subSched->start_time)) }} – {{ date('g:i A', strtotime($subSched->end_time)) }}
                                                                    </span>
                                                                </div>

                                                                <div class="flex items-center gap-1">
                                                                    <button type="button" onclick="openEditScheduleModal({{ $subSched }})" title="Edit"
                                                                            class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-[9px] font-bold cursor-pointer">
                                                                        Edit
                                                                    </button>
                                                                    <form action="{{ route('admin.schedules.destroy', $subSched->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this slot?');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" title="Delete"
                                                                                class="px-2 py-0.5 rounded bg-red-50 hover:bg-red-100 text-[#8b1818] border border-red-200 text-[9px] font-bold cursor-pointer">
                                                                            Delete
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

                                    <td class="py-4 px-6 align-top text-right">
                                        <button type="button" onclick="openScheduleModalForTeacher({{ $teacherObj->id ?? 'null' }})"
                                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-[#8b1818] hover:text-white text-slate-800 border border-slate-300 text-xs font-black transition cursor-pointer inline-flex items-center gap-1">
                                            <i class="fa-solid fa-plus text-[9px]"></i>
                                            <span>Add Class</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-slate-400 font-bold italic">
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

<!-- ============================================================== -->
<!-- MODAL: DEDICATED PRINT / PDF PREVIEW FOR CLASSROOM & TEACHER   -->
<!-- ============================================================== -->
<div id="printScheduleModal" class="fixed inset-0 z-50 bg-slate-950/70 hidden items-center justify-center p-3 sm:p-6 overflow-y-auto" style="z-index: 9999;">
    <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col my-auto max-h-[95vh]">
        
        <!-- Modal Controls Bar (Hidden during actual print) -->
        <div class="modal-chrome px-6 py-4 bg-slate-900 text-white border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-print"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white">Class Schedule Print Preview</h3>
                    <p class="text-[11px] text-slate-300 font-medium">Select a classroom section or teacher, preview the card, then print or save as PDF.</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" onclick="executePrintCard()" 
                        class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider flex items-center gap-1.5 shadow-md cursor-pointer transition">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Print / Save as PDF</span>
                </button>
                <button type="button" onclick="closePrintScheduleModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center cursor-pointer transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Filter Selector Bar (Hidden during actual print) -->
        <div class="modal-chrome p-4 bg-slate-100 border-b border-slate-200 flex flex-wrap items-center gap-4 text-xs font-bold text-slate-800">
            <!-- Filter Type Radio Buttons -->
            <div class="flex items-center gap-3 bg-white px-3 py-1.5 rounded-xl border border-slate-300">
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="printType" value="section" checked onchange="changePrintMode('section')" class="text-[#8b1818] focus:ring-[#8b1818]">
                    <span>Per Classroom / Section</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="printType" value="teacher" onchange="changePrintMode('teacher')" class="text-[#8b1818] focus:ring-[#8b1818]">
                    <span>Per Faculty / Teacher</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="printType" value="master" onchange="changePrintMode('master')" class="text-[#8b1818] focus:ring-[#8b1818]">
                    <span>Master Timetable (All)</span>
                </label>
            </div>

            <!-- Section Selector Dropdown -->
            <div id="printSectionSelectWrapper" class="flex items-center gap-2">
                <label for="printSectionSelect" class="text-slate-600 font-bold uppercase text-[10px]">Select Section:</label>
                <select id="printSectionSelect" onchange="renderPrintPreview()" class="py-1.5 px-3 rounded-xl border border-slate-300 bg-white font-bold text-xs focus:border-[#8b1818] outline-none cursor-pointer">
                    @foreach($dbSections as $sec)
                        <option value="{{ $sec->section_name }}">
                            {{ !empty($sec->grade_level) ? (str_starts_with(strtolower($sec->grade_level), 'grade') ? $sec->grade_level : 'Grade ' . $sec->grade_level) . ' - ' : '' }}{{ $sec->section_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Teacher Selector Dropdown -->
            <div id="printTeacherSelectWrapper" class="hidden flex items-center gap-2">
                <label for="printTeacherSelect" class="text-slate-600 font-bold uppercase text-[10px]">Select Teacher:</label>
                <select id="printTeacherSelect" onchange="renderPrintPreview()" class="py-1.5 px-3 rounded-xl border border-slate-300 bg-white font-bold text-xs focus:border-[#8b1818] outline-none cursor-pointer">
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">Prof. {{ $t->first_name }} {{ $t->last_name }} ({{ $t->id_number ?? 'Faculty' }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- PRINTABLE SCHEDULE CARD CONTAINER (THE EXACT CARD THAT IS PRINTED / SAVED TO PDF) -->
        <div class="overflow-y-auto p-6 bg-slate-200/50 flex-1 flex justify-center">
            <div id="printableScheduleCard" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-300 w-full text-slate-900">
                
                <!-- Official Header for Print -->
                <div class="print-header text-center border-b-2 border-slate-900 pb-3 mb-4 space-y-1">
                    <div class="flex items-center justify-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#8b1818] text-amber-300 flex items-center justify-center font-black text-sm border-2 border-amber-400">
                            SIA
                        </div>
                        <div>
                            <h2 class="text-lg font-black tracking-tight text-slate-900 uppercase">SOUTHERN ISABELA ACADEMY, INC.</h2>
                            <p class="text-[10px] font-semibold text-slate-600">San Fabian, Echague, Isabela &bull; Academic Scheduling & Records Division</p>
                        </div>
                    </div>
                    <div class="pt-2 flex items-center justify-between text-xs border-t border-slate-200 mt-2">
                        <span class="font-black text-slate-800" id="printCardTitle">CLASS SCHEDULE TIMETABLE</span>
                        <span class="font-bold text-slate-600">Academic Year: <strong class="text-slate-900">{{ $activeSchoolYear ?? '2025-2026' }}</strong> &bull; 1st Semester</span>
                    </div>
                </div>

                <!-- Card Metadata Subheader -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 mb-4 flex items-center justify-between text-xs font-semibold">
                    <div id="printTargetInfo">
                        <!-- Populated by JS -->
                    </div>
                    <div class="text-right text-[10px] text-slate-500 font-mono">
                        Date Printed: {{ now()->format('M d, Y h:i A') }}
                    </div>
                </div>

                <!-- 7:00 AM – 5:00 PM Timetable Matrix -->
                <div class="overflow-x-auto w-full mb-4">
                    <table class="w-full border-collapse text-left text-xs border border-slate-300">
                        <thead>
                            <tr class="bg-slate-900 text-white font-black text-[10px] uppercase text-center">
                                <th class="py-2 px-2 border border-slate-700 w-28 bg-slate-950">Time Slot</th>
                                @foreach($weekdays as $day)
                                    <th class="py-2 px-2 border border-slate-700">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="printMatrixBody" class="divide-y divide-slate-200 text-[11px]">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- List of Assigned Subjects Table -->
                <div class="mt-4">
                    <h4 class="text-[11px] font-black uppercase text-slate-800 tracking-wider mb-2 border-b pb-1">
                        Course & Schedule Breakdown
                    </h4>
                    <table class="w-full text-left text-[11px] border border-slate-200">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 font-bold uppercase text-[9px] border-b">
                                <th class="p-1.5 border-r w-8 text-center">#</th>
                                <th class="p-1.5 border-r w-24">Code</th>
                                <th class="p-1.5 border-r">Subject Description</th>
                                <th class="p-1.5 border-r w-24">Day</th>
                                <th class="p-1.5 border-r w-36">Time Slot</th>
                                <th class="p-1.5" id="printTableExtraColHeader">Assigned Instructor</th>
                            </tr>
                        </thead>
                        <tbody id="printBreakdownBody" class="divide-y divide-slate-200">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Signatures Block for Academic Official Verification -->
                <div class="mt-8 pt-6 border-t border-slate-300 grid grid-cols-3 gap-6 text-center text-xs">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 mb-6">Prepared by:</p>
                        <div class="border-b border-slate-900 w-3/4 mx-auto"></div>
                        <p class="font-black text-slate-800 mt-1 text-[11px]" id="printSignPrepared">Class Adviser / Instructor</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 mb-6">Verified by:</p>
                        <div class="border-b border-slate-900 w-3/4 mx-auto"></div>
                        <p class="font-black text-slate-800 mt-1 text-[11px]">Academic Coordinator</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 mb-6">Approved by:</p>
                        <div class="border-b border-slate-900 w-3/4 mx-auto"></div>
                        <p class="font-black text-slate-800 mt-1 text-[11px]">School Principal / Head</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- INCLUDE CREATE & IMPORT MODALS -->
@include('admin.schedules.create')
@include('admin.schedules.import')

<!-- ================= Modal: Edit Class Schedule ================= -->
<div id="editScheduleModal" class="fixed inset-0 z-50 bg-slate-950/70 hidden items-center justify-center p-4 sm:p-6 transition-all duration-200" style="z-index: 9999;">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col max-h-[92vh] transform transition-all duration-200">
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

        <form id="editScheduleForm" method="POST" class="p-8 overflow-y-auto space-y-6">
            @csrf @method('PUT')
            <input type="hidden" id="edit_schedule_id" name="schedule_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Subject Name *</label>
                <input type="text" id="edit_subject_name" name="subject_name" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Assigned Faculty *</label>
                <select id="edit_teacher_id" name="teacher_id" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                    @foreach($teachers as $t) 
                        <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->email }})</option> 
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Grade *</label>
                    <select id="edit_grade_level" name="grade_level" required onchange="handleEditGradeChange();" class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Grade</option>
                        @foreach($dbGrades as $g) <option value="{{ $g }}">{{ $g }}</option> @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Section *</label>
                    <select id="edit_section" name="section" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Section</option>
                        @foreach($dbSections as $sec) <option value="{{ $sec->section_name }}" data-grade="{{ $sec->grade_level ?? '' }}">{{ $sec->section_name }}</option> @endforeach
                    </select>
                </div>

                <div id="edit_strand_container">
                    <label id="edit_strand_label" class="block text-xs font-bold text-slate-700 uppercase mb-2">Strand *</label>
                    <select id="edit_strand" name="strand" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Strand</option>
                        @foreach($dbStrands as $s) <option value="{{ $s }}">{{ $s }}</option> @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-3 bg-slate-50/80 p-4 rounded-2xl border-2 border-slate-200">
                <label class="block text-xs font-black text-slate-800 uppercase tracking-wide">
                    Day Selection *
                </label>
                <div class="grid grid-cols-7 gap-1.5 pt-1 w-full overflow-x-auto">
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayName)
                        <label class="flex items-center justify-center gap-1 px-1.5 py-2 rounded-xl bg-white border border-slate-200 hover:border-slate-400 cursor-pointer transition select-none group text-center">
                            <input type="checkbox" name="days[]" value="{{ $dayName }}" class="edit-day-checkbox w-3.5 h-3.5 text-[#8b1818] rounded border-slate-300 focus:ring-[#8b1818] cursor-pointer shrink-0">
                            <span class="text-[11px] font-bold text-slate-700">{{ substr($dayName, 0, 3) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Start Time *</label>
                    <input type="time" id="edit_start_time" name="start_time" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">End Time *</label>
                    <input type="time" id="edit_end_time" name="end_time" required class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
                </div>
            </div>

            <div class="pt-6 border-t-2 border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeEditScheduleModal()" class="px-6 py-3 rounded-2xl border-2 border-slate-300 text-xs font-bold hover:bg-slate-50 cursor-pointer transition">Cancel</button>
                <button type="submit" class="px-6 py-3 rounded-2xl bg-[#8b1818] text-white text-xs font-black hover:bg-[#731414] cursor-pointer shadow-md transition">Update Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= Modal: Success Alert ================= -->
<div id="successModal" class="fixed inset-0 z-50 bg-slate-950/70 {{ session('success') ? 'flex' : 'hidden' }} items-center justify-center p-4 transition-all duration-300" style="z-index: 9999;">
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

<!-- ================= Modal: Error Alert ================= -->
<div id="errorModal" class="fixed inset-0 z-50 bg-slate-950/70 {{ isset($errors) && $errors->any() ? 'flex' : 'hidden' }} items-center justify-center p-4 transition-all duration-300" style="z-index: 9999;">
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
    // Global datasets for JavaScript rendering
    const allSchedulesData = @json(isset($allSchedules) ? $allSchedules : $schedules);
    const teachersList = @json($teachers);
    const sectionsList = @json($dbSections);
    const weekdaysList = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const timeSlotsList = [
        '07:00 AM - 08:00 AM',
        '08:00 AM - 09:00 AM',
        '09:00 AM - 10:00 AM',
        '10:00 AM - 11:00 AM',
        '11:00 AM - 12:00 PM',
        '12:00 PM - 01:00 PM',
        '01:00 PM - 02:00 PM',
        '02:00 PM - 03:00 PM',
        '03:00 PM - 04:00 PM',
        '04:00 PM - 05:00 PM'
    ];

    function switchViewMode(mode) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', mode);
        window.location.href = url.toString();
    }

    // --- PRINT PREVIEW MODAL LOGIC ---
    function openPrintScheduleModal() {
        const modal = document.getElementById('printScheduleModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            renderPrintPreview();
        }
    }

    function closePrintScheduleModal() {
        const modal = document.getElementById('printScheduleModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    }

    function changePrintMode(mode) {
        const secWrapper = document.getElementById('printSectionSelectWrapper');
        const teachWrapper = document.getElementById('printTeacherSelectWrapper');

        if (mode === 'section') {
            secWrapper.classList.remove('hidden');
            teachWrapper.classList.add('hidden');
        } else if (mode === 'teacher') {
            secWrapper.classList.add('hidden');
            teachWrapper.classList.remove('hidden');
        } else {
            secWrapper.classList.add('hidden');
            teachWrapper.classList.add('hidden');
        }
        renderPrintPreview();
    }

    function parseTimeSec(timeStr) {
        if (!timeStr) return 0;
        const [time, modifier] = timeStr.trim().split(' ');
        let [hours, minutes] = time.split(':').map(Number);
        if (modifier === 'PM' && hours < 12) hours += 12;
        if (modifier === 'AM' && hours === 12) hours = 0;
        return hours * 3600 + (minutes || 0) * 60;
    }

    function formatTime12(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.split(':');
        let h = parseInt(parts[0]);
        let m = parts[1] || '00';
        let ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        h = h ? h : 12;
        return `${h}:${m} ${ampm}`;
    }

    function renderPrintPreview() {
        const mode = document.querySelector('input[name="printType"]:checked')?.value || 'section';
        const targetInfo = document.getElementById('printTargetInfo');
        const cardTitle = document.getElementById('printCardTitle');
        const matrixBody = document.getElementById('printMatrixBody');
        const breakdownBody = document.getElementById('printBreakdownBody');
        const tableHeader = document.getElementById('printTableExtraColHeader');
        const signPrepared = document.getElementById('printSignPrepared');

        let filtered = [];
        let label = '';
        let subLabel = '';

        if (mode === 'section') {
            const secName = document.getElementById('printSectionSelect').value;
            filtered = allSchedulesData.filter(s => s.section === secName || s.academic_section?.section_name === secName);
            
            const matchedSec = sectionsList.find(s => s.section_name === secName);
            const gr = matchedSec?.grade_level ? (String(matchedSec.grade_level).toLowerCase().startsWith('grade') ? matchedSec.grade_level : 'Grade ' + matchedSec.grade_level) : '';
            const strand = matchedSec?.strand ? `(${matchedSec.strand})` : '';
            
            label = `Classroom / Section: ${gr} - ${secName} ${strand}`;
            subLabel = `Total Enrolled Subjects: ${filtered.length} classes`;
            cardTitle.innerText = `OFFICIAL CLASSROOM SCHEDULE TIMETABLE`;
            tableHeader.innerText = 'Assigned Instructor';
            signPrepared.innerText = 'Class Adviser';
        } else if (mode === 'teacher') {
            const teacherId = parseInt(document.getElementById('printTeacherSelect').value);
            filtered = allSchedulesData.filter(s => parseInt(s.teacher_id) === teacherId);
            
            const matchedT = teachersList.find(t => t.id === teacherId);
            const tName = matchedT ? `${matchedT.first_name} ${matchedT.last_name}` : 'Faculty';
            const tEmail = matchedT ? matchedT.email : '';
            
            label = `Faculty Teaching Load: Prof. ${tName}`;
            subLabel = `Employee Email: ${tEmail} | Total Teaching Units: ${filtered.length} classes`;
            cardTitle.innerText = `FACULTY TEACHING LOAD & TIMETABLE`;
            tableHeader.innerText = 'Assigned Section';
            signPrepared.innerText = `Prof. ${tName} (Faculty)`;
        } else {
            filtered = allSchedulesData;
            label = `Master Institutional Schedule Matrix`;
            subLabel = `All Classrooms & Teaching Assignments (${filtered.length} total entries)`;
            cardTitle.innerText = `MASTER INSTITUTIONAL TIMETABLE`;
            tableHeader.innerText = 'Section / Teacher';
            signPrepared.innerText = 'Academic Scheduler';
        }

        targetInfo.innerHTML = `
            <div class="font-black text-sm text-slate-900">${label}</div>
            <div class="text-[11px] font-semibold text-slate-500 mt-0.5">${subLabel}</div>
        `;

        // Render Matrix Body
        let matrixHtml = '';
        timeSlotsList.forEach(slot => {
            const [startStr, endStr] = slot.split(' - ');
            const startSec = parseTimeSec(startStr);
            const endSec = parseTimeSec(endStr);
            const isLunch = startStr.startsWith('12:00');

            matrixHtml += `<tr class="${isLunch ? 'bg-amber-50/40' : ''}">`;
            matrixHtml += `<td class="py-1.5 px-2 font-mono text-[10px] font-bold text-center bg-slate-100/80 border border-slate-300 whitespace-nowrap">${slot}</td>`;

            weekdaysList.forEach(day => {
                const matches = filtered.filter(s => {
                    const sDay = (s.day || s.day_of_week || '').trim();
                    if (sDay.toLowerCase() !== day.toLowerCase()) return false;
                    const sSec = parseTimeSec(formatTime12(s.start_time));
                    const eSec = parseTimeSec(formatTime12(s.end_time));
                    return (sSec < endSec && eSec > startSec);
                });

                matrixHtml += `<td class="p-1 border border-slate-300 align-top min-h-[40px]">`;
                if (matches.length > 0) {
                    matches.forEach(m => {
                        const sSec = parseTimeSec(formatTime12(m.start_time));
                        const startsHere = (sSec >= startSec && sSec < endSec);
                        
                        if (startsHere) {
                            const subj = m.subject_name || m.subject || 'Subject';
                            const extra = mode === 'teacher' ? (m.section || 'Sec') : (m.teacher ? `${m.teacher.first_name} ${m.teacher.last_name}` : 'Faculty');
                            matrixHtml += `
                                <div class="bg-slate-100 p-1 rounded border border-slate-300 text-[9px] mb-1">
                                    <div class="font-black text-slate-900 leading-tight">${subj}</div>
                                    <div class="text-slate-600 font-semibold">${extra}</div>
                                    <div class="text-slate-500 font-mono text-[8px]">${formatTime12(m.start_time)} - ${formatTime12(m.end_time)}</div>
                                </div>
                            `;
                        } else {
                            matrixHtml += `<div class="text-[8px] font-bold text-amber-800 italic">↳ ${m.subject_name || m.subject}</div>`;
                        }
                    });
                } else {
                    matrixHtml += `<div class="text-center text-slate-300 text-[10px]">-</div>`;
                }
                matrixHtml += `</td>`;
            });

            matrixHtml += `</tr>`;
        });
        matrixBody.innerHTML = matrixHtml;

        // Render Breakdown List
        let breakdownHtml = '';
        if (filtered.length === 0) {
            breakdownHtml = `<tr><td colspan="6" class="p-4 text-center text-slate-400 italic">No class schedules allocated for this selection.</td></tr>`;
        } else {
            filtered.forEach((item, idx) => {
                const extra = mode === 'teacher' 
                    ? `${item.grade_level ? item.grade_level + ' - ' : ''}${item.section || 'N/A'}`
                    : (item.teacher ? `Prof. ${item.teacher.first_name} ${item.teacher.last_name}` : 'Unassigned');

                breakdownHtml += `
                    <tr class="hover:bg-slate-50">
                        <td class="p-1.5 text-center font-bold text-slate-400 border-r">${idx + 1}</td>
                        <td class="p-1.5 font-mono font-bold text-slate-800 border-r">${item.subject_code || 'SUBJ'}</td>
                        <td class="p-1.5 font-bold text-slate-900 border-r">${item.subject_name || item.subject || 'Unnamed Subject'}</td>
                        <td class="p-1.5 font-semibold text-slate-700 border-r">${item.day || item.day_of_week}</td>
                        <td class="p-1.5 font-mono text-[10px] text-slate-700 border-r">${formatTime12(item.start_time)} - ${formatTime12(item.end_time)}</td>
                        <td class="p-1.5 font-bold text-slate-800">${extra}</td>
                    </tr>
                `;
            });
        }
        breakdownBody.innerHTML = breakdownHtml;
    }

    function executePrintCard() {
        window.print();
    }

    // Modal Helpers
    function openScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        if (modal) { 
            modal.classList.remove('hidden'); 
            modal.classList.add('flex'); 
            document.body.classList.add('overflow-hidden'); 
        }
    }

    function openScheduleModalForTeacher(teacherId) {
        openScheduleModal();
        if (teacherId && teacherId !== 'null') {
            const select = document.getElementById('create_teacher_id');
            if (select) select.value = teacherId;
        }
    }

    function closeScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        if (modal) { 
            modal.classList.add('hidden'); 
            modal.classList.remove('flex'); 
            document.body.classList.remove('overflow-hidden'); 
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

        document.getElementById('edit_start_time').value = schedule.start_time || '';
        document.getElementById('edit_end_time').value = schedule.end_time || '';
        
        if (modal) { 
            modal.classList.remove('hidden'); 
            modal.classList.add('flex'); 
            document.body.classList.add('overflow-hidden'); 
        }
    }

    function closeEditScheduleModal() {
        const modal = document.getElementById('editScheduleModal');
        if (modal) { 
            modal.classList.add('hidden'); 
            modal.classList.remove('flex'); 
            document.body.classList.remove('overflow-hidden'); 
        }
    }

    function handleEditGradeChange() {
        const gradeVal = document.getElementById('edit_grade_level')?.value || '';
        const strandContainer = document.getElementById('edit_strand_container');
        const strandSelect = document.getElementById('edit_strand');
        const sectionSelect = document.getElementById('edit_section');

        const numGrade = parseInt(gradeVal.replace(/\D/g, ''));
        const isJHS = (numGrade >= 7 && numGrade <= 10);

        if (isJHS) {
            if (strandContainer) strandContainer.classList.add('hidden');
            if (strandSelect) { strandSelect.value = ''; strandSelect.removeAttribute('required'); }
        } else {
            if (strandContainer) strandContainer.classList.remove('hidden');
            if (strandSelect && gradeVal) strandSelect.setAttribute('required', 'required');
        }

        if (sectionSelect) {
            const cleanGradeNum = gradeVal ? gradeVal.replace(/\D/g, '') : '';
            Array.from(sectionSelect.options).forEach(opt => {
                if (!opt.value) return;
                const optGrade = opt.getAttribute('data-grade') || '';
                const cleanOptGradeNum = optGrade.replace(/\D/g, '');
                if (!cleanGradeNum || cleanOptGradeNum === cleanGradeNum) {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                } else {
                    opt.hidden = true; opt.disabled = true; opt.style.display = 'none';
                }
            });
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

    window.addEventListener('click', function(e) {
        if (e.target === document.getElementById('scheduleModal')) closeScheduleModal();
        if (e.target === document.getElementById('editScheduleModal')) closeEditScheduleModal();
        if (e.target === document.getElementById('printScheduleModal')) closePrintScheduleModal();
        if (e.target === document.getElementById('successModal')) closeSuccessModal();
        if (e.target === document.getElementById('errorModal')) closeErrorModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeScheduleModal();
            closeEditScheduleModal();
            closePrintScheduleModal();
            closeSuccessModal();
            closeErrorModal();
        }
    });
</script>
@endpush
@endsection