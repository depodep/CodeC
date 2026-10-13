@extends('layouts.app')

@section('title', 'Teacher Dashboard | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <div class="flex items-center gap-2"><h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Teacher Dashboard</h1><span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Faculty Portal</span></div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Attendance overview for {{ $teacher->first_name }} {{ $teacher->last_name }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black text-slate-700"><i class="fa-solid fa-calendar-days text-[#8b1818] mr-1"></i>{{ $activePeriod?->school_year ?? 'No active school year' }}</span>
            <a href="{{ route('teacher.attendance') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c]"><i class="fa-solid fa-clipboard-check"></i> Attendance Records</a>
            <a href="{{ route('attendance.start') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-400 text-amber-950 text-xs font-black hover:bg-amber-300"><i class="fa-solid fa-play"></i> Start Attendance</a>
        </div>
    </header>

    <main class="px-6 lg:px-10 py-6 space-y-5 max-w-[1700px] mx-auto">
        @if(!empty($isEvaluationActive) && empty($allTeacherEvaluationsDone))
            <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-4 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 border border-amber-300 flex items-center justify-center shrink-0 text-base">
                        <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-black text-amber-950">Faculty Evaluation is Now Active</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-200 text-amber-900 border border-amber-300 uppercase">Action Required</span>
                        </div>
                        <p class="text-xs font-bold text-amber-800 mt-0.5">
                            {{ $activeEvaluationCycle->name ?? 'Active Evaluation Cycle' }} is ongoing.
                            @if(empty($selfEvaluationDone) && empty($peerEvaluationDone))
                                Please complete your <span class="underline">Self-Evaluation</span> and <span class="underline">Peer Evaluation</span>.
                            @elseif(empty($selfEvaluationDone))
                                Your <span class="underline">Self-Evaluation</span> is still pending.
                            @elseif(empty($peerEvaluationDone))
                                Your <span class="underline">Peer Evaluation</span> is still pending.
                            @endif
                        </p>
                    </div>
                </div>
                <a href="{{ route('teacher.evaluations.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition shrink-0 shadow-xs">
                    <i class="fa-solid fa-clipboard-check"></i> Evaluate Now
                </a>
            </div>
        @endif

        @if(!$activePeriod)<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-900"><i class="fa-solid fa-calendar-xmark mr-2"></i>No active school year is configured. Dashboard data is limited to the fallback date range.</div>@endif

        <section class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach([
                ['label' => 'Students', 'value' => $totalStudents, 'color' => 'blue', 'icon' => 'fa-users', 'href' => route('teacher.students')],
                ['label' => 'Advisory Class', 'value' => $advisoryCount, 'color' => 'amber', 'icon' => 'fa-users-viewfinder', 'href' => route('teacher.students')],
                ['label' => 'Subjects', 'value' => $subjectCount, 'color' => 'rose', 'icon' => 'fa-book-open', 'href' => route('teacher.schedules')],
                ['label' => 'Classes Attendance Rate', 'value' => $classAttendanceRate . '%', 'color' => 'emerald', 'icon' => 'fa-chart-line', 'href' => route('teacher.attendance')],
                ['label' => 'Advisory Attendance Rate', 'value' => $advisoryAttendanceRate . '%', 'color' => 'slate', 'icon' => 'fa-users-viewfinder', 'href' => route('teacher.attendance')],
            ] as $card)
                <a href="{{ $card['href'] }}" class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3 hover:border-[#8b1818] transition"><div><p class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $card['label'] }}</p><p class="text-2xl font-black text-slate-900 mt-1">{{ is_numeric($card['value']) ? number_format((float) $card['value']) : $card['value'] }}</p></div><span class="w-10 h-10 rounded-xl bg-{{ $card['color'] }}-50 border border-{{ $card['color'] }}-200 text-{{ $card['color'] }}-700 flex items-center justify-center shrink-0"><i class="fa-solid {{ $card['icon'] }}"></i></span></a>
            @endforeach
        </section>

        <form id="dashboardFilterForm" method="GET" action="{{ route('teacher.dashboard') }}" class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">View</label>
                <select id="filterRange" name="range" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold focus:border-[#8b1818] outline-none">
                    <option value="daily" @selected($range === 'daily')>Daily</option>
                    <option value="weekly" @selected($range === 'weekly')>Weekly</option>
                    <option value="monthly" @selected($range === 'monthly')>Monthly</option>
                </select>
            </div>
            <div>
                <label id="filterDateLabel" class="block text-[10px] font-black uppercase text-slate-500 mb-1">{{ $range === 'weekly' ? 'Week date' : ($range === 'monthly' ? 'Month' : 'Date') }}</label>
                <input id="filterDate" type="date" name="date" value="{{ $selectedDate->toDateString() }}" min="{{ $schoolYearStart->toDateString() }}" max="{{ $schoolYearEnd->toDateString() }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold focus:border-[#8b1818] outline-none">
            </div>
            <div class="flex items-center gap-2 pb-2">
                <span id="dateRangeText" class="text-xs font-bold text-slate-500">{{ $rangeStart->format('M d, Y') }} - {{ $rangeEnd->format('M d, Y') }}</span>
                <span id="filterLoadingIndicator" class="hidden text-xs font-bold text-[#8b1818] items-center gap-1">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> Updating...
                </span>
            </div>
        </form>

        {{-- ROW 1: TWO STATISTICAL TREND CHARTS (Volume + Rate %) --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Chart 1: Attendance Volume / Status Trend --}}
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Attendance Trend</h2>
                        <p id="attendanceTrendSubtext" class="text-[11px] font-bold text-slate-500">{{ ucfirst($range) }} attendance volume within the active school year</p>
                    </div>
                    <i class="fa-solid fa-chart-area text-[#8b1818]"></i>
                </div>
                <div class="relative h-64">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </article>

            {{-- Chart 2: Attendance Rate Trends (Per Advisory Class & Per Subject Class) --}}
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Attendance Rate Trend (%)</h2>
                        <p id="attendanceRateTrendSubtext" class="text-[11px] font-bold text-slate-500">Advisory Class vs. Subject Classes rate over time</p>
                    </div>
                    <i class="fa-solid fa-chart-line text-emerald-600"></i>
                </div>
                <div class="relative h-64">
                    <canvas id="attendanceRateTrendChart"></canvas>
                </div>
            </article>
        </section>

        {{-- ROW 2: TWO BOTTOM CARDS (Student Evaluation Progress & Subject Class Students) --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Card 1: Student Evaluation Progress --}}
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            @if(!empty($isEvaluationActive))
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h2 class="text-base font-black text-slate-900">Student Evaluation Progress</h2>
                                    @if(!empty($allClassesCompleted))
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-600 text-white border border-emerald-700 uppercase flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check text-[8px]"></i> All Completed
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase animate-pulse">Active Cycle</span>
                                    @endif
                                </div>
                                <p class="text-[11px] font-bold text-slate-500">{{ $activeEvaluationCycle->name ?? 'Active Evaluation' }}</p>
                            @else
                                <h2 class="text-base font-black text-slate-900">Students by Gender</h2>
                                <p class="text-[11px] font-bold text-slate-500">Assigned students</p>
                            @endif
                        </div>
                        <i class="fa-solid {{ !empty($isEvaluationActive) ? 'fa-clipboard-check text-emerald-600' : 'fa-venus-mars text-[#8b1818]' }}"></i>
                    </div>

                    @if(!empty($isEvaluationActive))
                        {{-- Overall Evaluation Percentage Banner --}}
                        <div class="mt-2 p-3 {{ !empty($allClassesCompleted) ? 'bg-emerald-100/80 border-emerald-300' : 'bg-emerald-50/70 border-emerald-200' }} border rounded-xl flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-black uppercase text-emerald-800 tracking-wider">Evaluation Completion</span>
                                <div class="flex items-baseline gap-1.5 mt-0.5">
                                    <span id="evalPercentText" class="text-2xl font-black text-emerald-700">{{ $evaluationPercentage }}%</span>
                                    <span id="evalCountsText" class="text-xs font-bold text-emerald-600">({{ $evaluatedStudentsCount }} of {{ $totalStudents }} students)</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black uppercase text-slate-400 block">Classes Done</span>
                                <span class="text-xs font-black {{ !empty($allClassesCompleted) ? 'text-emerald-700' : 'text-slate-700' }}">
                                    {{ $completedClassesCount ?? 0 }} / {{ $totalClassesHandled ?? 0 }} classes
                                </span>
                            </div>
                        </div>

                        {{-- Two Doughnut Charts side-by-side: Advisory Class Evaluation & Overall Student Evaluation --}}
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            {{-- Doughnut 1: Advisory Class Evaluation --}}
                            <div class="flex flex-col items-center p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[11px] font-black text-slate-800 mb-1">Advisory Evaluation</span>
                                <div class="relative h-28 w-full flex items-center justify-center">
                                    <canvas id="classesEvalChart"></canvas>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 mt-1 text-center truncate max-w-full" title="{{ $advisorySectionTitle ?? '' }}">
                                    @if(!empty($advisoryEvalTotal))
                                        {{ $advisoryEvalCount ?? 0 }} of {{ $advisoryEvalTotal }} evaluated
                                    @else
                                        No advisory section
                                    @endif
                                </span>
                            </div>

                            {{-- Doughnut 2: Overall Student Evaluation --}}
                            <div class="flex flex-col items-center p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[11px] font-black text-slate-800 mb-1">Overall Evaluation</span>
                                <div class="relative h-28 w-full flex items-center justify-center">
                                    <canvas id="overallEvalChart"></canvas>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 mt-1 text-center">
                                    {{ $evaluatedStudentsCount }} of {{ $totalStudents }} evaluated
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-around text-xs font-bold text-slate-600">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Completed / Evaluated</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span> Pending</span>
                        </div>
                    @else
                        {{-- Fallback: Gender Chart --}}
                        <div class="relative h-48 mt-3">
                            <canvas id="genderChart"></canvas>
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-around text-xs font-bold text-slate-600">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Male: <strong class="text-slate-800">{{ $genderCounts['Male'] }}</strong></span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> Female: <strong class="text-slate-800">{{ $genderCounts['Female'] }}</strong></span>
                        </div>
                    @endif
                </div>
            </article>

            {{-- Card 2: Students by Subject Class --}}
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Subject Class Students</h2>
                        <p class="text-[11px] font-bold text-slate-500">Enrolled student count by gender (Male & Female)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-3 text-[10px] font-black text-slate-700 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full inline-block bg-blue-600"></span>
                                <span>Male</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full inline-block bg-rose-500"></span>
                                <span>Female</span>
                            </span>
                        </div>
                        <i class="fa-solid fa-book-open text-[#8b1818]"></i>
                    </div>
                </div>

                <div class="relative h-64">
                    <canvas id="subjectChart"></canvas>
                </div>
            </article>
        </section>

    </main>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let attendanceTrendChart = null;
    let classesEvalChart = null;
    let overallEvalChart = null;
    let genderChart = null;
    let subjectChart = null;

    // Stacked Bar Labels Plugin: draws counts inside each segment (Female & Male) and total on top
    const stackedBarLabelsPlugin = {
        id: 'stackedBarLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const femaleMeta = chart.getDatasetMeta(0);
            const maleMeta = chart.getDatasetMeta(1);
            if (!femaleMeta || !maleMeta) return;

            const length = Math.max(femaleMeta.data.length, maleMeta.data.length);
            for (let i = 0; i < length; i++) {
                const femaleBar = femaleMeta.data[i];
                const maleBar = maleMeta.data[i];
                const femaleVal = Number(chart.data.datasets[0]?.data[i]) || 0;
                const maleVal = Number(chart.data.datasets[1]?.data[i]) || 0;
                const total = femaleVal + maleVal;

                ctx.save();
                ctx.textAlign = 'center';

                // Female count inside bottom segment (Pink)
                if (femaleVal > 0 && femaleBar) {
                    const h0 = Math.abs(femaleBar.base - femaleBar.y);
                    if (h0 >= 14) {
                        ctx.font = 'bold 11px sans-serif';
                        ctx.fillStyle = '#ffffff';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(femaleVal, femaleBar.x, (femaleBar.y + femaleBar.base) / 2);
                    }
                }

                // Male count inside top segment (Blue)
                if (maleVal > 0 && maleBar) {
                    const h1 = Math.abs(maleBar.base - maleBar.y);
                    if (h1 >= 14) {
                        ctx.font = 'bold 11px sans-serif';
                        ctx.fillStyle = '#ffffff';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(maleVal, maleBar.x, (maleBar.y + maleBar.base) / 2);
                    }
                }

                // Total count on top of the stacked bar
                if (total > 0) {
                    const topY = Math.min(
                        femaleBar ? femaleBar.y : Infinity,
                        maleBar && maleVal > 0 ? maleBar.y : (femaleBar ? femaleBar.y : Infinity)
                    );
                    const posX = (maleBar || femaleBar).x;
                    if (isFinite(topY)) {
                        ctx.font = 'bold 12px sans-serif';
                        ctx.fillStyle = '#0f172a';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(total, posX, topY - 4);
                    }
                }

                ctx.restore();
            }
        }
    };

    // Doughnut center percentage text plugin
    const doughnutCenterTextPlugin = (centerText) => ({
        id: 'doughnutCenterText_' + Math.random().toString(36).substr(2, 9),
        afterDraw(chart) {
            const { ctx, chartArea: { top, bottom, left, right } } = chart;
            const x = (left + right) / 2;
            const y = (top + bottom) / 2;
            ctx.save();
            ctx.font = 'black 14px sans-serif';
            ctx.fillStyle = '#0f172a';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(centerText, x, y);
            ctx.restore();
        }
    });

    let attendanceRateTrendChart = null;

    // 1. Initialize Attendance Volume/Status Line Chart
    const initTrendChart = (labels, presents, lates, absents) => {
        const ctx = document.getElementById('attendanceTrendChart');
        if (!ctx) return;
        if (attendanceTrendChart) attendanceTrendChart.destroy();
        attendanceTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Present', data: presents, borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.12)', fill: true, tension: 0.35 },
                    { label: 'Late', data: lates, borderColor: '#d97706', backgroundColor: 'transparent', tension: 0.35 },
                    { label: 'Absent', data: absents, borderColor: '#e11d48', backgroundColor: 'transparent', tension: 0.35 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    };

    // 1b. Initialize Attendance Rate (%) Trends Line Chart (Advisory vs Subject)
    const initRateTrendChart = (labels, advisoryRates, subjectRates) => {
        const ctx = document.getElementById('attendanceRateTrendChart');
        if (!ctx) return;
        if (attendanceRateTrendChart) attendanceRateTrendChart.destroy();
        attendanceRateTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Advisory Class (%)', data: advisoryRates, borderColor: '#d97706', backgroundColor: 'rgba(217,119,6,.08)', fill: true, tension: 0.35 },
                    { label: 'Subject Classes (%)', data: subjectRates, borderColor: '#0284c7', backgroundColor: 'rgba(2,132,199,.08)', fill: true, tension: 0.35 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.dataset.label + ': ' + (ctx.parsed.y || 0) + '%'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: (val) => val + '%'
                        }
                    }
                }
            }
        });
    };

    // 2. Initialize Evaluation Doughnut Charts
    const initEvaluationCharts = () => {
        @if(!empty($isEvaluationActive))
            // A. Advisory Class Evaluation Doughnut
            const classesCanvas = document.getElementById('classesEvalChart');
            if (classesCanvas) {
                if (classesEvalChart) classesEvalChart.destroy();
                const advisoryEvalCount = {{ $advisoryEvalCount ?? 0 }};
                const advisoryEvalTotal = {{ $advisoryEvalTotal ?? 0 }};
                const pending = Math.max(0, advisoryEvalTotal - advisoryEvalCount);
                const advisoryPercent = advisoryEvalTotal > 0 ? Math.round(({{ $advisoryEvalPercentage ?? 0 }})) : 0;

                classesEvalChart = new Chart(classesCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Evaluated', 'Pending'],
                        datasets: [{
                            data: [advisoryEvalCount, pending],
                            backgroundColor: ['#059669', '#cbd5e1'],
                            borderWidth: 0
                        }]
                    },
                    plugins: [doughnutCenterTextPlugin(advisoryPercent + '%')],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // B. Overall Students Evaluation Doughnut
            const overallCanvas = document.getElementById('overallEvalChart');
            if (overallCanvas) {
                if (overallEvalChart) overallEvalChart.destroy();
                const evaluated = {{ $evaluatedStudentsCount ?? 0 }};
                const totalStus = {{ $totalStudents ?? 0 }};
                const pendingStus = Math.max(0, totalStus - evaluated);
                const overallPercent = {{ $evaluationPercentage ?? 0 }};

                overallEvalChart = new Chart(overallCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Evaluated', 'Pending'],
                        datasets: [{
                            data: [evaluated, pendingStus],
                            backgroundColor: ['#059669', '#cbd5e1'],
                            borderWidth: 0
                        }]
                    },
                    plugins: [doughnutCenterTextPlugin(overallPercent + '%')],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: { legend: { display: false } }
                    }
                });
            }
        @else
            const genderCanvas = document.getElementById('genderChart');
            if (genderCanvas) {
                if (genderChart) genderChart.destroy();
                genderChart = new Chart(genderCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Male', 'Female'],
                        datasets: [{
                            data: [{{ $genderCounts['Male'] ?? 0 }}, {{ $genderCounts['Female'] ?? 0 }}],
                            backgroundColor: ['#3b82f6', '#ec4899'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        @endif
    };

    // 3. Initialize Stacked Bar Chart with Female (bottom) & Male (top)
    const initStackedBarChart = (canvasId, labels, femaleData, maleData) => {
        const ctx = document.getElementById(canvasId);
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Female',
                        data: femaleData,
                        backgroundColor: '#ec4899',
                        borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                        barThickness: 34,
                        stack: 'genderStack'
                    },
                    {
                        label: 'Male',
                        data: maleData,
                        backgroundColor: '#3b82f6',
                        borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                        barThickness: 34,
                        stack: 'genderStack'
                    }
                ]
            },
            plugins: [stackedBarLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 22, bottom: 5 }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11, weight: '600' },
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            title: (tooltipItems) => {
                                const raw = tooltipItems[0]?.label;
                                if (Array.isArray(raw)) return raw.join(' - ');
                                return raw || '';
                            },
                            label: (context) => {
                                const val = context.parsed.y || 0;
                                return ' ' + context.dataset.label + ': ' + val + ' students';
                            },
                            footer: (tooltipItems) => {
                                const total = tooltipItems.reduce((acc, item) => acc + (item.parsed.y || 0), 0);
                                return 'Total Enrolled: ' + total + ' students';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grace: '15%'
                    },
                    x: {
                        stacked: true,
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            font: { size: 10, weight: 'bold' },
                            color: '#334155'
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    };

    // Initial render
    initTrendChart(@json($chartDates), @json($chartPresents), @json($chartLates), @json($chartAbsents));
    initRateTrendChart(@json($chartDates), @json($chartAdvisoryRates), @json($chartSubjectRates));
    initEvaluationCharts();
    subjectChart = initStackedBarChart('subjectChart', @json($subjectSummary->pluck('label')), @json($subjectFemaleCounts), @json($subjectMaleCounts));

    // Dynamic Filter Trigger (JavaScript process on change, remove apply button)
    const rangeSelect = document.getElementById('filterRange');
    const dateInput = document.getElementById('filterDate');
    const dateRangeText = document.getElementById('dateRangeText');
    const loadingIndicator = document.getElementById('filterLoadingIndicator');
    const dateLabel = document.getElementById('filterDateLabel');
    const trendSubtext = document.getElementById('attendanceTrendSubtext');
    const rateTrendSubtext = document.getElementById('attendanceRateTrendSubtext');

    const updateDashboardData = async () => {
        const range = rangeSelect.value;
        const date = dateInput.value;

        if (dateLabel) {
            dateLabel.textContent = range === 'weekly' ? 'Week date' : (range === 'monthly' ? 'Month' : 'Date');
        }

        loadingIndicator.classList.remove('hidden');
        loadingIndicator.classList.add('inline-flex');

        try {
            const url = new URL('{{ route('teacher.dashboard') }}', window.location.origin);
            url.searchParams.set('range', range);
            url.searchParams.set('date', date);

            const res = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error('Failed to load dashboard data');
            const data = await res.json();

            // Update URL without page reload
            window.history.replaceState({}, '', url.toString());

            // 1. Update text indicators
            if (dateRangeText && data.rangeStartFormatted && data.rangeEndFormatted) {
                dateRangeText.textContent = `${data.rangeStartFormatted} - ${data.rangeEndFormatted}`;
            }
            if (trendSubtext) {
                const rangeCapitalized = range.charAt(0).toUpperCase() + range.slice(1);
                trendSubtext.textContent = `${rangeCapitalized} attendance volume within the active school year`;
            }
            if (rateTrendSubtext) {
                const rangeCapitalized = range.charAt(0).toUpperCase() + range.slice(1);
                rateTrendSubtext.textContent = `${rangeCapitalized} Advisory vs. Subject attendance rate (%)`;
            }

            // 2. Update Attendance Trend Charts
            initTrendChart(data.chartDates, data.chartPresents, data.chartLates, data.chartAbsents);
            initRateTrendChart(data.chartDates, data.chartAdvisoryRates, data.chartSubjectRates);

            // 3. Update Subject Stacked Bar Chart
            if (subjectChart) subjectChart.destroy();
            subjectChart = initStackedBarChart('subjectChart', data.subjectLabels, data.subjectFemaleCounts, data.subjectMaleCounts);

        } catch (err) {
            console.error('Error fetching dashboard data:', err);
        } finally {
            loadingIndicator.classList.add('hidden');
            loadingIndicator.classList.remove('inline-flex');
        }
    };

    // Attach change event listeners (pure javascript process on change)
    if (rangeSelect) rangeSelect.addEventListener('change', updateDashboardData);
    if (dateInput) dateInput.addEventListener('change', updateDashboardData);
});
</script>
@endpush
@endsection
