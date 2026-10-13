@extends('layouts.app')

@section('title', 'Admin Dashboard - SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    @include('admin.dashboard.partials.header')

    <main class="px-4 py-5 sm:px-6 lg:px-8 space-y-5">
        @php
            $attendanceTotal = (int) ($todayAttendance['total'] ?? 0);
            $attendancePresent = (int) ($todayAttendance['present'] ?? 0);
            $attendanceLate = (int) ($todayAttendance['late'] ?? 0);
            $attendanceRate = $attendanceTotal > 0
                ? round((($attendancePresent + $attendanceLate) / $attendanceTotal) * 100)
                : 0;
            $summaryCards = [
                [
                    'label' => 'Students',
                    'value' => number_format($totalStudents),
                    'meta' => 'Registered learners',
                    'icon' => 'fa-graduation-cap',
                    'href' => route('admin.students.analytics'),
                ],
                [
                    'label' => 'Faculty',
                    'value' => number_format($totalFaculty),
                    'meta' => number_format($activeFaculty) . ' active',
                    'icon' => 'fa-chalkboard-user',
                    'href' => route('admin.users.index', ['role' => 'faculty']),
                ],
                [
                    'label' => "Today's Attendance",
                    'value' => $attendanceRate . '%',
                    'meta' => number_format($attendancePresent + $attendanceLate) . ' of ' . number_format($attendanceTotal) . ' records',
                    'icon' => 'fa-clipboard-check',
                    'href' => route('admin.attendance'),
                ],
                [
                    'label' => 'Evaluation',
                    'value' => $activeEvaluation ? 'Active' : 'Closed',
                    'meta' => number_format($evalPendingCount) . ' pending',
                    'icon' => 'fa-star',
                    'href' => route('admin.evaluations.periods'),
                ],
            ];
            if ($nfcCards !== null) {
                $summaryCards[] = [
                    'label' => 'NFC Cards',
                    'value' => number_format($nfcCards),
                    'meta' => 'Active registrations',
                    'icon' => 'fa-id-card',
                    'href' => route('admin.nfc.binding'),
                ];
            }
            if ($smsSentToday !== null) {
                $summaryCards[] = [
                    'label' => 'SMS Today',
                    'value' => number_format($smsSentToday),
                    'meta' => 'Notification messages',
                    'icon' => 'fa-comment-sms',
                    'href' => route('admin.sms.sent-today'),
                ];
            }
        @endphp

        <section class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#8b1818]">System overview</p>
                <h2 class="text-xl font-black tracking-tight text-slate-900">Today at a glance</h2>
            </div>
            <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500">
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-1.5">
                    <i class="fa-solid fa-calendar-days mr-1 text-[#8b1818]"></i>{{ $activeSchoolYear }}
                </span>
                <a href="{{ route('admin.reports') }}" class="rounded-lg bg-[#590d0d] px-3 py-1.5 text-white transition hover:bg-[#721313]">
                    <i class="fa-solid fa-file-lines mr-1"></i>Reports
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><i class="fa-solid fa-chart-line text-xs"></i></span>
                            <h3 class="text-base font-black text-slate-900">Attendance Rate</h3>
                        </div>
                        <p id="attendanceRateSubtext" class="mt-1 text-[11px] font-bold text-slate-500">Attendance trend from recorded student logs.</p>
                    </div>
                    <a href="{{ route('admin.attendance.rate') }}" class="text-[11px] font-black text-[#8b1818] hover:underline">Analytics <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px]"></i></a>
                </div>
                <form id="dashboard-attendance-filters" class="dashboard-filter mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                    <input type="hidden" name="student_grade" value="{{ request('student_grade') }}">
                    <input type="hidden" name="student_section" value="{{ request('student_section') }}">
                    
                    <!-- 1. Timeframe / Date Filter: Today/Daily, Weekly, Monthly -->
                    <div class="min-w-[100px] flex-1 sm:flex-initial">
                        <label class="sr-only" for="att_timeframe">Date Filter</label>
                        <select id="att_timeframe" name="att_timeframe" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 focus:border-[#8b1818] outline-none">
                            <option value="today" @selected(($attendanceTimeframe ?? 'today') === 'today')>Daily</option>
                            <option value="weekly" @selected(($attendanceTimeframe ?? '') === 'weekly')>Weekly</option>
                            <option value="monthly" @selected(($attendanceTimeframe ?? '') === 'monthly')>Monthly</option>
                        </select>
                    </div>

                    <!-- 1b. Specific Date Picker (for Daily / Today) -->
                    <div id="att_day_control" class="min-w-[130px] flex-1 sm:flex-initial">
                        <input id="att_day" name="att_date" type="date"
                               value="{{ min(now()->toDateString(), $schoolYearEnd) }}"
                               min="{{ $schoolYearStart }}"
                               max="{{ min(now()->toDateString(), $schoolYearEnd) }}"
                               class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 focus:border-[#8b1818] outline-none">
                    </div>

                    <!-- 1c. Specific Week Picker (Populated within school year range) -->
                    <div id="att_week_control" class="min-w-[170px] flex-1 sm:flex-initial hidden">
                        <select id="att_week" name="att_week" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 focus:border-[#8b1818] outline-none"></select>
                    </div>

                    <!-- 1d. Specific Month Picker (Populated within school year range) -->
                    <div id="att_month_control" class="min-w-[140px] flex-1 sm:flex-initial hidden">
                        <select id="att_month" name="att_month" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 focus:border-[#8b1818] outline-none"></select>
                    </div>

                    <!-- 2. Grade Level Filter -->
                    <div class="min-w-[100px] flex-1 sm:flex-initial">
                        <label class="sr-only" for="att_grade">Attendance grade</label>
                        <select id="att_grade" name="att_grade" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 focus:border-[#8b1818] outline-none">
                            <option value="">All grades</option>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade }}" @selected(request('att_grade') == $grade)>{{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Section Filter -->
                    <div class="min-w-[110px] flex-1 sm:flex-initial">
                        <label class="sr-only" for="att_section">Attendance section</label>
                        <select id="att_section" name="att_section" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 focus:border-[#8b1818] outline-none">
                            <option value="">All sections</option>
                            @foreach($attendanceSectionOptions as $sectionOption)
                                <option value="{{ $sectionOption->section }}" data-grade="{{ $sectionOption->grade_level }}" @selected(request('att_section') == $sectionOption->section)>{{ $sectionOption->section }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <div class="grid gap-2 mb-4" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                    <div class="rounded-xl bg-emerald-50 p-3"><p class="text-[9px] font-black uppercase text-emerald-700">Rate</p><p id="attendanceRateValue" class="mt-1 text-lg font-black text-emerald-800">{{ $overallAttendanceRate }}%</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-[9px] font-black uppercase text-slate-500">Present</p><p id="attendancePresentValue" class="mt-1 text-lg font-black text-slate-900">{{ number_format($presentCount) }}</p></div>
                    <div class="rounded-xl bg-amber-50 p-3"><p class="text-[9px] font-black uppercase text-amber-700">Late</p><p id="attendanceLateValue" class="mt-1 text-lg font-black text-amber-800">{{ number_format($lateCount) }}</p></div>
                    <div class="rounded-xl bg-rose-50 p-3"><p class="text-[9px] font-black uppercase text-rose-700">Absent</p><p id="attendanceAbsentValue" class="mt-1 text-lg font-black text-rose-800">{{ number_format($absentCount) }}</p></div>
                </div>
                <div class="relative h-48">
                    <canvas id="attendanceTrendChart"></canvas>
                    @if(!$dashboardAttendance->count())
                        <p class="pointer-events-none absolute inset-0 flex items-center justify-center text-center text-[11px] font-bold text-slate-400">No attendance records found.<br>Record an NFC tap to populate this chart.</p>
                    @endif
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-[#8b1818]"><i class="fa-solid fa-star text-xs"></i></span>
                            <h3 class="text-base font-black text-slate-900">Faculty Evaluation</h3>
                        </div>
                        @if($selectedCycle)
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 border border-amber-200/80 px-2 py-0.5 text-[10px] font-black text-amber-800">
                                    <i class="fa-solid fa-calendar-check text-[9px] text-amber-600"></i>
                                    {{ $selectedCycle->name ?? 'Active Cycle' }}
                                </span>
                                @if(!empty($selectedCycle->start_date) && !empty($selectedCycle->end_date))
                                    <span class="text-[10px] font-bold text-slate-400">
                                        ({{ \Carbon\Carbon::parse($selectedCycle->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($selectedCycle->end_date)->format('M d, Y') }})
                                    </span>
                                @endif
                                @if(isset($selectedCycle->status))
                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.2 text-[9px] font-black uppercase tracking-wider {{ strtolower($selectedCycle->status) === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $selectedCycle->status }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <p class="mt-1 text-[11px] font-bold text-slate-500">Completion by evaluator group.</p>
                        @endif
                    </div>
                    <a href="{{ route('admin.evaluations.periods') }}" class="text-[11px] font-black text-[#8b1818] hover:underline whitespace-nowrap">Manage Evaluations <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px]"></i></a>
                </div>
                <div class="mb-4 flex items-center gap-4 rounded-xl bg-slate-50 p-3">
                    <div class="text-3xl font-black text-[#590d0d]">{{ $evalOverallRate }}%</div>
                    <div class="text-[10px] font-bold text-slate-500"><span class="font-black text-slate-900">{{ number_format($evalCompletedCount) }}</span> completed<br><span class="font-black text-amber-600">{{ number_format($evalPendingCount) }}</span> pending</div>
                </div>
                <div class="relative h-36">
                    <canvas id="facultyEvaluationChart"></canvas>
                    @if(!$evalCompletedCount && !$evalPendingCount)
                        <p class="pointer-events-none absolute inset-0 flex items-center justify-center text-center text-[11px] font-bold text-slate-400">No evaluation assignments found.</p>
                    @endif
                </div>
                {{-- Teachers who have been evaluated (Horizontal scrollable badges) --}}
                @if(!empty($evaluatedTeachers) && count($evaluatedTeachers) > 0)
                <div class="mt-3 border-t border-slate-100 pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Completed Evaluations</p>
                        <span class="text-[9px] font-bold text-slate-400"><i class="fa-solid fa-arrows-left-right mr-1 text-[8px]"></i>Scroll</span>
                    </div>
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-slate-200 scrollbar-track-transparent">
                        @foreach($evaluatedTeachers as $et)
                        <div class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200/80 px-3 py-2 shrink-0 shadow-2xs hover:bg-emerald-100/70 transition">
                            <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                            </span>
                            <span class="text-[11px] font-black text-slate-800 whitespace-nowrap">{{ $et['name'] }}</span>
                            <span class="text-[10px] font-black text-emerald-700 bg-white border border-emerald-200 px-1.5 py-0.5 rounded-md whitespace-nowrap shrink-0">{{ $et['count'] }} evals</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs lg:col-span-2 flex flex-col justify-between">
                <div class="mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-[#8b1818]">
                                <i class="fa-solid fa-chart-column text-xs"></i>
                            </span>
                            <h3 class="text-base font-black text-slate-900">Students by Grade</h3>
                        </div>
                        <p class="mt-1 text-[11px] font-bold text-slate-500">Gender breakdown (Male & Female) and enrollment total per grade level.</p>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center gap-3 text-[11px] font-black text-slate-700 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl shadow-2xs">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0" style="background-color: #3b82f6;"></span>
                                <span>Male</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0" style="background-color: #ec4899;"></span>
                                <span>Female</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="relative h-60">
                    <canvas id="gradeDistributionChart"></canvas>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Section Population</h3>
                        <p class="text-[11px] font-bold text-slate-500">Students enrolled per section.</p>
                    </div>
                    <i class="fa-solid fa-chart-pie text-[#8b1818]"></i>
                </div>
                <div class="h-60 flex items-center justify-center">
                    <canvas id="sectionPopulationChart"></canvas>
                </div>
            </article>
        </section>

        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Attendance chart period</p>
                </div>
                <select id="section-attendance-timeframe"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-700 outline-none focus:border-[#8b1818]">
                    <option value="today" @selected(($attendanceTimeframe ?? 'today') === 'today')>Today</option>
                    <option value="weekly" @selected(($attendanceTimeframe ?? '') === 'weekly')>Weekly</option>
                    <option value="monthly" @selected(($attendanceTimeframe ?? '') === 'monthly')>Monthly</option>
                    <option value="day">Day</option>
                </select>
                <div id="section-attendance-day-control">
                    <input id="section-attendance-day" type="date" value="{{ min(now()->toDateString(), $schoolYearEnd) }}" min="{{ $schoolYearStart }}" max="{{ $schoolYearEnd }}"
                           class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-[11px] font-bold text-slate-700">
                </div>
                <div id="section-attendance-week-control" class="hidden">
                    <select id="section-attendance-week" class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-[11px] font-bold text-slate-700"></select>
                </div>
                <div id="section-attendance-month-control" class="hidden">
                    <select id="section-attendance-month" class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-[11px] font-bold text-slate-700"></select>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <i class="fa-solid fa-chart-line text-xs"></i>
                            </span>
                            <h3 class="text-base font-black text-slate-900">By Advisory</h3>
                        </div>
                        <p class="mt-1 text-[11px] font-bold text-slate-500">Filtered attendance rate by section.</p>
                    </div>
                    <i class="fa-solid fa-chart-line text-emerald-700"></i>
                </div>
                <div class="h-52"><canvas id="attendanceAdvisoryChart"></canvas></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-700"><i class="fa-solid fa-book-open text-xs"></i></span>
                            <h3 class="text-base font-black text-slate-900">By Subject Attendance</h3>
                        </div>
                        <p class="mt-1 text-[11px] font-bold text-slate-500">Subject attendance rate by subject.</p>
                    </div>
                </div>
                <div class="h-52"><canvas id="attendanceSubjectChart"></canvas></div>
            </article>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900">Recent Activity</h3>
                    <p class="text-[11px] font-bold text-slate-500">Latest recorded events.</p>
                </div>
                <a href="{{ route('admin.audit-logs') }}" class="text-[11px] font-black text-[#8b1818] hover:underline">View all <i class="fa-solid fa-arrow-right ml-1 text-[9px]"></i></a>
            </div>
            <div class="grid grid-cols-1 gap-x-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse($recentActivity as $activity)
                    <div class="flex items-start gap-2.5 border-b border-slate-100 py-2.5">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#8b1818]"></span>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-black text-slate-800">{{ $activity->action ?? 'System activity' }}</p>
                            <p class="truncate text-[10px] font-bold text-slate-400">{{ $activity->description ?? ($activity->module ?? 'System') }}</p>
                        </div>
                        <time class="ml-auto whitespace-nowrap text-[9px] font-bold text-slate-400">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans(null, true) }}</time>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-[11px] font-bold text-slate-400 md:col-span-2 lg:col-span-3">No activity recorded yet.</div>
                @endforelse
            </div>
        </section>

    </main>
</div>

@include('admin.dashboard.partials.modals')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    const common = {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10, weight: 'bold' } } },
            x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } }
        }
    };

    // Common plugin for drawing values directly above or on bars / points
    const directValuesPlugin = {
        id: 'directValuesPlugin',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (!meta || meta.hidden) return;

                meta.data.forEach((element, index) => {
                    const val = dataset.data[index];
                    if (val === null || val === undefined) return;

                    ctx.save();
                    ctx.textAlign = 'center';

                    if (chart.config.type === 'line') {
                        // Show % or number above line data points
                        ctx.font = 'bold 11px sans-serif';
                        ctx.fillStyle = '#065f46';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(val + '%', element.x, element.y - 6);
                    } else if (chart.config.type === 'bar' && !chart.config.options.scales?.x?.stacked) {
                        // Standard bar charts (Faculty evaluation, Section attendance)
                        const isPercentage = chart.config.options.scales?.y?.ticks?.callback?.(100)?.includes?.('%');
                        const displayVal = isPercentage ? val + '%' : val;
                        
                        if (val > 0) {
                            const barHeight = Math.abs(element.base - element.y);
                            ctx.font = 'bold 11px sans-serif';
                            
                            // If bar is tall enough (>= 22px), place text INSIDE the bar near the top edge
                            // otherwise place it above the bar
                            if (barHeight >= 22) {
                                ctx.fillStyle = '#ffffff';
                                ctx.textBaseline = 'top';
                                ctx.fillText(displayVal, element.x, element.y + 5);
                            } else {
                                ctx.fillStyle = '#0f172a';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText(displayVal, element.x, element.y - 3);
                            }
                        }
                    }
                    ctx.restore();
                });
            });
        }
    };

    // Doughnut chart plugin to draw values inside/on slices
    const doughnutLabelsPlugin = {
        id: 'doughnutLabelsPlugin',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data) return;

            const total = chart.data.datasets[0].data.reduce((acc, v) => acc + (Number(v) || 0), 0);
            if (total === 0) return;

            meta.data.forEach((element, index) => {
                const val = chart.data.datasets[0].data[index];
                if (!val || val <= 0) return;

                // Center of the doughnut arc slice
                const centerAngle = (element.startAngle + element.endAngle) / 2;
                const midRadius = (element.innerRadius + element.outerRadius) / 2;
                const x = element.x + Math.cos(centerAngle) * midRadius;
                const y = element.y + Math.sin(centerAngle) * midRadius;

                // Check arc size to make sure label fits
                const arcLength = (element.endAngle - element.startAngle) * midRadius;
                if (arcLength < 16) return;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 12px sans-serif';
                ctx.fillStyle = '#ffffff';
                ctx.shadowColor = 'rgba(0,0,0,0.4)';
                ctx.shadowBlur = 3;
                ctx.fillText(val, x, y);
                ctx.restore();
            });
        }
    };

    const attendanceTrendChart = new Chart(document.getElementById('attendanceTrendChart'), {
        type: 'line',
        data: {
            labels: @json($attendanceTrendLabels),
            datasets: [{
                data: @json($attendanceTrendData),
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, .1)',
                fill: true,
                tension: .35,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#059669',
                borderWidth: 2
            }]
        },
        options: {
            ...common,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Attendance Rate: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                ...common.scales,
                y: { ...common.scales.y, min: 0, max: 100, ticks: { ...common.scales.y.ticks, callback: v => v + '%' }, grace: '10%' }
            }
        },
        plugins: [directValuesPlugin]
    });

    new Chart(document.getElementById('facultyEvaluationChart'), {
        type: 'bar',
        data: {
            labels: ['Students', 'Peers', 'Self'],
            datasets: [{
                data: [{{ $studentEvalRate }}, {{ $peerEvalRate }}, {{ $personalEvalRate }}],
                backgroundColor: ['#8b1818', '#d97706', '#2563eb'],
                borderRadius: 6,
                barThickness: 30
            }]
        },
        options: {
            ...common,
            scales: { ...common.scales, y: { ...common.scales.y, min: 0, max: 100, ticks: { ...common.scales.y.ticks, callback: v => v + '%' } } }
        },
        plugins: [directValuesPlugin]
    });

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

    const sectionPopulationChart = new Chart(document.getElementById('sectionPopulationChart'), {
        type: 'doughnut',
        data: {
            labels: @json($studentSectionLabels),
            datasets: [{
                data: @json($studentSectionTotals),
                backgroundColor: ['#8b1818', '#f59e0b', '#2563eb', '#059669', '#7c3aed', '#db2777'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        font: { size: 10, weight: 'bold' },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map(function(label, i) {
                                    const meta = chart.getDatasetMeta(0);
                                    const val = data.datasets[0].data[i] || 0;
                                    return {
                                        text: label + ' (' + val + ')',
                                        fillStyle: data.datasets[0].backgroundColor[i % data.datasets[0].backgroundColor.length],
                                        strokeStyle: '#fff',
                                        lineWidth: 1,
                                        hidden: isNaN(data.datasets[0].data[i]) || meta.data[i]?.hidden,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                }
            }
        },
        plugins: [doughnutLabelsPlugin]
    });

    const gradeDistributionChart = new Chart(document.getElementById('gradeDistributionChart'), {
        type: 'bar',
        data: {
            labels: @json($gradeLabels ?? $gradeDistribution->keys()->values()),
            datasets: [
                {
                    label: 'Female',
                    data: @json($gradeFemaleCounts ?? []),
                    backgroundColor: '#ec4899',
                    borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                    barThickness: 32,
                    stack: 'gradeStack'
                },
                {
                    label: 'Male',
                    data: @json($gradeMaleCounts ?? []),
                    backgroundColor: '#3b82f6',
                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    barThickness: 32,
                    stack: 'gradeStack'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(items) {
                            if (!items.length) return '';
                            const raw = items[0].label;
                            return String(raw).toLowerCase().startsWith('grade') ? raw : 'Grade ' + raw;
                        },
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y || 0;
                            return ' ' + label + ': ' + value;
                        },
                        footer: function(tooltipItems) {
                            let sum = 0;
                            tooltipItems.forEach(item => { sum += (item.parsed.y || 0); });
                            return 'Total: ' + sum;
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: 'bold' },
                        callback: function(val) {
                            const raw = this.getLabelForValue(val);
                            if (!raw || raw === 'Unassigned') return raw;
                            return String(raw).toLowerCase().startsWith('grade') ? raw : 'Grade ' + raw;
                        }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grace: '20%',
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { size: 10, weight: 'bold' }, precision: 0 }
                }
            }
        },
        plugins: [stackedBarLabelsPlugin]
    });

    const dbSectionsList = @json($dbAllSections ?? $attendanceSectionLabels ?? []);
    const makeAttendanceChart = (elementId, color) => new Chart(document.getElementById(elementId), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Attendance rate', data: [], backgroundColor: color, borderRadius: 5, barThickness: 24 }] },
        options: {
            ...common,
            animation: false,
            scales: { ...common.scales, y: { ...common.scales.y, min: 0, max: 100, ticks: { ...common.scales.y.ticks, callback: value => value + '%' } } }
        },
        plugins: [directValuesPlugin]
    });
    const attendanceAdvisoryChart = makeAttendanceChart('attendanceAdvisoryChart', '#059669');
    const attendanceSubjectChart = makeAttendanceChart('attendanceSubjectChart', '#2563eb');

    const students = @json($dashboardStudents);
    const attendanceLogs = @json($dashboardAttendance);
    const schoolYearStart = @json($schoolYearStart);
    const schoolYearEnd = @json($schoolYearEnd);
    const attended = log => log.attended === true || log.attended === 1;

    const matchesStudent = (student, filters) =>
        (!filters.student_grade || student.grade === filters.student_grade) &&
        (!filters.student_section || student.section === filters.student_section);
    const matchesAttendanceStudent = (student, filters) =>
        (!filters.att_grade || String(student.grade) === String(filters.att_grade)) &&
        (!filters.att_section || student.section === filters.att_section);

    function getWeekKey(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Monday
        const monday = new Date(d.setDate(diff));
        return 'Wk of ' + monday.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function getMonthKey(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    }

    function getDayKey(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function countWeekdays(start, end) {
        let count = 0;
        const cursor = new Date(start);
        while (cursor <= end) {
            const day = cursor.getDay();
            if (day !== 0 && day !== 6) count++;
            cursor.setDate(cursor.getDate() + 1);
        }
        return Math.max(1, count);
    }

    function percentage(value, total) {
        return total > 0 ? (Math.round((value / total) * 1000) / 10) + '%' : '0%';
    }

    function refreshDashboardCharts(includeStudentCharts = true) {
        const studentForm = document.querySelector('#student_grade')?.form;
        const attendanceForm = document.querySelector('#att_grade')?.form;
        const studentFilters = Object.fromEntries(new FormData(studentForm || document.createElement('form')));
        const attendanceFilters = Object.fromEntries(new FormData(attendanceForm || document.createElement('form')));

        if (includeStudentCharts) {
            // 1. Refresh Student distribution & Section breakdown
            const filteredStudents = students.filter(student => matchesStudent(student, studentFilters));

            const bySection = {};
            filteredStudents.forEach(student => { bySection[student.section] = (bySection[student.section] || 0) + 1; });
            sectionPopulationChart.data.labels = Object.keys(bySection);
            sectionPopulationChart.data.datasets[0].data = Object.values(bySection);
            sectionPopulationChart.update();

            const gradeMap = {};
            filteredStudents.forEach(student => {
                const g = student.grade || 'Unassigned';
                if (!gradeMap[g]) gradeMap[g] = { male: 0, female: 0, total: 0 };
                const isMale = ['male', 'm'].includes(student.gender);
                const isFemale = ['female', 'f'].includes(student.gender);
                if (isMale) gradeMap[g].male++;
                else if (isFemale) gradeMap[g].female++;
                gradeMap[g].total++;
            });

            const sortedGrades = Object.keys(gradeMap).sort((a, b) => {
                const na = parseInt(a), nb = parseInt(b);
                return (!isNaN(na) && !isNaN(nb)) ? na - nb : a.localeCompare(b);
            });

            const formatGradeLabel = g => {
                if (!g || g === 'Unassigned') return 'Unassigned';
                return String(g).toLowerCase().startsWith('grade') ? g : 'Grade ' + g;
            };

            gradeDistributionChart.data.labels = sortedGrades.map(formatGradeLabel);
            gradeDistributionChart.data.datasets[0].data = sortedGrades.map(g => gradeMap[g].female);
            gradeDistributionChart.data.datasets[1].data = sortedGrades.map(g => gradeMap[g].male);
            gradeDistributionChart.update();
        }

        // 2. Filter Attendance logs by Grade & Section for Section Charts & Main Attendance Rate
        const todayStr = new Date().toISOString().slice(0, 10);
        const sectionTimeframeVal = document.getElementById('section-attendance-timeframe')?.value || 'today';
        const dayInput = document.getElementById('section-attendance-day');
        const weekInput = document.getElementById('section-attendance-week');
        const monthInput = document.getElementById('section-attendance-month');
        const today = new Date(todayStr + 'T00:00:00');
        const schoolStart = new Date(schoolYearStart + 'T00:00:00');
        const schoolEnd = new Date(schoolYearEnd + 'T00:00:00');
        const boundedToday = new Date(Math.min(Math.max(today.getTime(), schoolStart.getTime()), schoolEnd.getTime()));
        
        let rangeEnd = new Date(boundedToday);
        let rangeStart = new Date(boundedToday);
        if (sectionTimeframeVal === 'today' || sectionTimeframeVal === 'day') {
            if (sectionTimeframeVal === 'day' && dayInput?.value) {
                rangeStart = new Date(dayInput.value + 'T00:00:00');
                rangeEnd = new Date(rangeStart);
            }
        } else if (sectionTimeframeVal === 'weekly' && weekInput?.value) {
            rangeStart = new Date(weekInput.value + 'T00:00:00');
            rangeEnd = new Date(rangeStart);
            rangeEnd.setDate(rangeEnd.getDate() + 6);
        } else if (sectionTimeframeVal === 'monthly' && monthInput?.value) {
            rangeStart = new Date(monthInput.value + '-01T00:00:00');
            rangeEnd = new Date(rangeStart.getFullYear(), rangeStart.getMonth() + 1, 0);
            if (rangeEnd > boundedToday &&
                rangeStart.getFullYear() === boundedToday.getFullYear() &&
                rangeStart.getMonth() === boundedToday.getMonth()) {
                rangeEnd = new Date(boundedToday);
            }
        } else if (sectionTimeframeVal === 'today') {
            rangeStart = new Date(boundedToday);
            rangeEnd = new Date(boundedToday);
        }
        rangeStart = new Date(Math.max(rangeStart.getTime(), schoolStart.getTime()));
        rangeEnd = new Date(Math.min(rangeEnd.getTime(), schoolEnd.getTime()));
        if (rangeStart > rangeEnd) rangeStart = new Date(rangeEnd);

        let filteredLogs = attendanceLogs.filter(log => {
            const matchGrade = !attendanceFilters.att_grade || String(log.grade) === String(attendanceFilters.att_grade);
            const matchSection = !attendanceFilters.att_section || log.section === attendanceFilters.att_section;
            if (!matchGrade || !matchSection) return false;

            const logDate = new Date(log.date + 'T00:00:00');
            return logDate >= rangeStart && logDate <= rangeEnd;
        });

        // Main Attendance Rate Card range (Daily/Today, Weekly, Monthly)
        const mainTimeframe = attendanceFilters.att_timeframe || 'today';
        const attDayInput = document.getElementById('att_day');
        const attWeekInput = document.getElementById('att_week');
        const attMonthInput = document.getElementById('att_month');
        let mainStart = new Date(boundedToday);
        let mainEnd = new Date(boundedToday);

        if (mainTimeframe === 'today') {
            if (attDayInput?.value) {
                mainStart = new Date(attDayInput.value + 'T00:00:00');
                mainEnd = new Date(mainStart);
            }
        } else if (mainTimeframe === 'weekly') {
            if (attWeekInput?.value) {
                mainStart = new Date(attWeekInput.value + 'T00:00:00');
                mainEnd = new Date(mainStart);
                mainEnd.setDate(mainEnd.getDate() + 6);
            } else {
                mainStart = new Date(boundedToday);
                mainStart.setDate(mainStart.getDate() - 6);
            }
        } else if (mainTimeframe === 'monthly') {
            if (attMonthInput?.value) {
                mainStart = new Date(attMonthInput.value + '-01T00:00:00');
                mainEnd = new Date(mainStart.getFullYear(), mainStart.getMonth() + 1, 0);
            } else {
                mainStart = new Date(boundedToday.getFullYear(), boundedToday.getMonth(), 1);
                mainEnd = new Date(boundedToday.getFullYear(), boundedToday.getMonth() + 1, 0);
            }
        }

        mainStart = new Date(Math.max(mainStart.getTime(), schoolStart.getTime()));
        mainEnd = new Date(Math.min(mainEnd.getTime(), schoolEnd.getTime(), boundedToday.getTime()));
        if (mainStart > mainEnd) mainStart = new Date(mainEnd);

        // Update Subtitle dynamically to show active date range
        const subtextEl = document.getElementById('attendanceRateSubtext');
        if (subtextEl) {
            const fmtOpts = { month: 'short', day: 'numeric', year: 'numeric' };
            if (mainStart.toDateString() === mainEnd.toDateString()) {
                subtextEl.textContent = `Attendance for ${mainStart.toLocaleDateString(undefined, fmtOpts)}`;
            } else {
                subtextEl.textContent = `Attendance from ${mainStart.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} to ${mainEnd.toLocaleDateString(undefined, fmtOpts)}`;
            }
        }

        const kpiLogs = attendanceLogs.filter(log => {
            if (attendanceFilters.att_grade && String(log.grade) !== String(attendanceFilters.att_grade)) return false;
            if (attendanceFilters.att_section && log.section !== attendanceFilters.att_section) return false;
            const logDate = new Date(log.date + 'T00:00:00');
            return logDate >= mainStart && logDate <= mainEnd;
        });

        // 3. Compute Attendance Trend Data based on Timeframe
        const trendBuckets = {};
        const trendEnrolled = students.filter(student => matchesAttendanceStudent(student, attendanceFilters)).length;

        if (mainTimeframe === 'today') {
            // For daily, generate days leading up to the selected day (or single day)
            const cursor = new Date(mainStart);
            cursor.setDate(cursor.getDate() - 6); // 7-day trend window ending on selected date
            while (cursor <= mainEnd) {
                if (cursor >= schoolStart) {
                    const key = getDayKey(cursor.toISOString().slice(0, 10));
                    trendBuckets[key] = { dates: new Set(), attendedIds: new Set(), sampleDate: new Date(cursor) };
                }
                cursor.setDate(cursor.getDate() + 1);
            }
            // Include logs within this 7-day window
            const trendStart = new Date(mainStart);
            trendStart.setDate(trendStart.getDate() - 6);
            attendanceLogs.filter(log => {
                if (attendanceFilters.att_grade && String(log.grade) !== String(attendanceFilters.att_grade)) return false;
                if (attendanceFilters.att_section && log.section !== attendanceFilters.att_section) return false;
                const logDate = new Date(log.date + 'T00:00:00');
                return logDate >= trendStart && logDate <= mainEnd;
            }).forEach(log => {
                const key = getDayKey(log.date);
                if (trendBuckets[key]) {
                    trendBuckets[key].dates.add(log.date);
                    if (attended(log)) trendBuckets[key].attendedIds.add(`${log.student_id}|${log.date}`);
                }
            });
        } else if (mainTimeframe === 'weekly') {
            // Populate all 7 days of the selected week (Mon to Sun)
            const cursor = new Date(mainStart);
            while (cursor <= mainEnd) {
                const key = getDayKey(cursor.toISOString().slice(0, 10));
                trendBuckets[key] = { dates: new Set(), attendedIds: new Set(), sampleDate: new Date(cursor) };
                cursor.setDate(cursor.getDate() + 1);
            }
            kpiLogs.forEach(log => {
                const key = getDayKey(log.date);
                if (trendBuckets[key]) {
                    trendBuckets[key].dates.add(log.date);
                    if (attended(log)) trendBuckets[key].attendedIds.add(`${log.student_id}|${log.date}`);
                }
            });
        } else if (mainTimeframe === 'monthly') {
            // Populate weeks in the selected month
            kpiLogs.forEach(log => {
                const key = getWeekKey(log.date);
                if (!trendBuckets[key]) {
                    trendBuckets[key] = { dates: new Set(), attendedIds: new Set(), sampleDate: new Date(log.date + 'T00:00:00') };
                }
                trendBuckets[key].dates.add(log.date);
                if (attended(log)) trendBuckets[key].attendedIds.add(`${log.student_id}|${log.date}`);
            });
        }

        let trendLabels = Object.keys(trendBuckets);
        let trendData = trendLabels.map(k => {
            const b = trendBuckets[k];
            let bucketStart = b.sampleDate;
            let bucketEnd = b.sampleDate;
            if (mainTimeframe === 'monthly') {
                const day = bucketStart.getDay();
                bucketStart = new Date(bucketStart);
                bucketStart.setDate(bucketStart.getDate() - (day === 0 ? 6 : day - 1));
                bucketEnd = new Date(bucketStart);
                bucketEnd.setDate(bucketEnd.getDate() + 6);
            }
            bucketStart = new Date(Math.max(bucketStart.getTime(), schoolStart.getTime(), mainStart.getTime()));
            bucketEnd = new Date(Math.min(bucketEnd.getTime(), schoolEnd.getTime(), boundedToday.getTime(), mainEnd.getTime()));
            const bucketDays = countWeekdays(bucketStart, bucketEnd);
            const expected = trendEnrolled * Math.max(1, bucketDays);
            return expected > 0 ? Math.min(100, Math.round((b.attendedIds.size / expected) * 1000) / 10) : 0;
        });

        if (trendLabels.length === 0) {
            trendLabels = [getDayKey(mainStart.toISOString().slice(0, 10))];
            trendData = [0];
        }

        attendanceTrendChart.data.labels = trendLabels;
        attendanceTrendChart.data.datasets[0].data = trendData;
        attendanceTrendChart.update();

        // 4. Update attendance by advisory and subject, using enrolled students as denominator.
        const byAttendanceSection = {};
        const bySubjectSection = {};
        dbSectionsList.forEach(sec => {
            byAttendanceSection[sec] = { total: 0, attendedIds: new Set() };
        });

        filteredLogs.forEach(log => {
            const key = log.attendance_type === 'subject' ? (log.subject || 'Unknown Subject') : log.section;
            const target = log.attendance_type === 'subject' ? bySubjectSection : byAttendanceSection;
            if (key) {
                if (!target[key]) {
                    target[key] = { total: 0, attendedIds: new Set() };
                }
                if (attended(log)) target[key].attendedIds.add(`${log.student_id}|${log.date}`);
            }
        });
        const enrolledBySection = {};
        students.filter(student => matchesAttendanceStudent(student, attendanceFilters)).forEach(student => {
            enrolledBySection[student.section] = (enrolledBySection[student.section] || 0) + 1;
        });
        const sectionDays = countWeekdays(rangeStart, rangeEnd);
        Object.keys(enrolledBySection).forEach(section => {
            if (byAttendanceSection[section]) byAttendanceSection[section].total = enrolledBySection[section] * sectionDays;
        });
        Object.keys(bySubjectSection).forEach(subject => {
            const subjectStudentIds = new Set(
                filteredLogs
                    .filter(log => log.attendance_type === 'subject' && (log.subject || 'Unknown Subject') === subject)
                    .map(log => String(log.student_id))
            );
            const subjectEnrollment = students.filter(student => matchesAttendanceStudent(student, attendanceFilters)).length;
            bySubjectSection[subject].total = Math.max(subjectStudentIds.size, subjectEnrollment) * sectionDays;
        });

        const updateSectionChart = (chart, values) => {
            chart.data.labels = Object.keys(values);
            chart.data.datasets[0].data = Object.values(values).map(val =>
                val.total > 0 ? Math.round((val.attendedIds.size / val.total) * 1000) / 10 : 0
            );
            chart.update('none');
        };
        updateSectionChart(attendanceAdvisoryChart, byAttendanceSection);
        updateSectionChart(attendanceSubjectChart, bySubjectSection);

        // 5. Update Stat Indicators (Rate, Present, Late, Absent)
        const present = new Set(kpiLogs.filter(log => ['present', 'on-time'].includes(String(log.status || '').toLowerCase())).map(log => `${log.student_id}|${log.date}`)).size;
        const late = new Set(kpiLogs.filter(log => String(log.status || '').toLowerCase() === 'late').map(log => `${log.student_id}|${log.date}`)).size;
        const total = students.filter(student => matchesAttendanceStudent(student, attendanceFilters)).length;
        const expectedStudentDays = total * countWeekdays(mainStart, mainEnd);
        const attendedStudentDays = new Set(
            kpiLogs.filter(attended).map(log => `${log.student_id}|${log.date}`)
        ).size;
        const overallRate = expectedStudentDays > 0
            ? Math.min(100, Math.round(attendedStudentDays / expectedStudentDays * 1000) / 10)
            : 0;

        document.getElementById('attendanceRateValue').textContent = overallRate + '%';
        const absent = Math.max(0, expectedStudentDays - attendedStudentDays);
        document.getElementById('attendancePresentValue').textContent = percentage(present, expectedStudentDays);
        document.getElementById('attendanceLateValue').textContent = percentage(late, expectedStudentDays);
        document.getElementById('attendanceAbsentValue').textContent = percentage(absent, expectedStudentDays);
    }

    document.querySelectorAll('.dashboard-filter').forEach(form => {
        form.addEventListener('submit', event => event.preventDefault());
        form.addEventListener('change', () => refreshDashboardCharts(form.id !== 'dashboard-attendance-filters'));
    });

    // Attendance Rate Card Period Controls (Daily Date, Week, Month)
    const attTimeframeSelect = document.getElementById('att_timeframe');
    const attDayInput = document.getElementById('att_day');
    const attWeekSelect = document.getElementById('att_week');
    const attMonthSelect = document.getElementById('att_month');
    const attDayControl = document.getElementById('att_day_control');
    const attWeekControl = document.getElementById('att_week_control');
    const attMonthControl = document.getElementById('att_month_control');

    const sectionTimeframe = document.getElementById('section-attendance-timeframe');
    sectionTimeframe?.addEventListener('change', () => refreshDashboardCharts(false));
    const sectionDay = document.getElementById('section-attendance-day');
    const sectionWeek = document.getElementById('section-attendance-week');
    const sectionMonth = document.getElementById('section-attendance-month');
    const dayControl = document.getElementById('section-attendance-day-control');
    const weekControl = document.getElementById('section-attendance-week-control');
    const monthControl = document.getElementById('section-attendance-month-control');
    const isoDate = date => date.toISOString().slice(0, 10);
    const activeEnd = new Date(schoolYearEnd + 'T00:00:00');
    const activeToday = new Date(Math.min(new Date().getTime(), activeEnd.getTime()));

    // Populate Weeks for both Attendance Rate card and Section charts
    for (let cursor = new Date(schoolYearStart + 'T00:00:00'); cursor <= activeEnd; cursor.setDate(cursor.getDate() + 7)) {
        const weekStart = new Date(cursor);
        const label = `Week of ${weekStart.toLocaleDateString(undefined, {month: 'short', day: 'numeric', year: 'numeric'})}`;
        const val = isoDate(weekStart);
        sectionWeek?.append(new Option(label, val));
        attWeekSelect?.append(new Option(label, val));
    }

    // Populate Months for both Attendance Rate card and Section charts
    for (let cursor = new Date(schoolYearStart + 'T00:00:00'); cursor <= activeEnd; cursor.setMonth(cursor.getMonth() + 1)) {
        const monthValue = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}`;
        const label = cursor.toLocaleDateString(undefined, {month: 'long', year: 'numeric'});
        sectionMonth?.append(new Option(label, monthValue));
        attMonthSelect?.append(new Option(label, monthValue));
    }

    // Set initial values bounded by school year and today
    if (sectionDay) sectionDay.value = isoDate(activeToday);
    if (attDayInput) attDayInput.value = isoDate(activeToday);

    const currentWeek = new Date(activeToday);
    currentWeek.setDate(currentWeek.getDate() - 6);
    if (sectionWeek) {
        const validWeeks = Array.from(sectionWeek.options).filter(option => new Date(option.value + 'T00:00:00') <= currentWeek);
        sectionWeek.value = validWeeks.length
            ? validWeeks[validWeeks.length - 1].value
            : sectionWeek.options[0]?.value;
    }
    if (attWeekSelect) {
        const validWeeks = Array.from(attWeekSelect.options).filter(option => new Date(option.value + 'T00:00:00') <= currentWeek);
        attWeekSelect.value = validWeeks.length
            ? validWeeks[validWeeks.length - 1].value
            : attWeekSelect.options[0]?.value;
    }

    const currentMonthVal = `${activeToday.getFullYear()}-${String(activeToday.getMonth() + 1).padStart(2, '0')}`;
    if (sectionMonth) sectionMonth.value = currentMonthVal;
    if (attMonthSelect) attMonthSelect.value = currentMonthVal;

    // Toggle Visibility of Attendance Rate card period controls
    const updateAttPeriodControls = () => {
        const value = attTimeframeSelect?.value || 'today';
        attDayControl?.classList.toggle('hidden', value !== 'today');
        attWeekControl?.classList.toggle('hidden', value !== 'weekly');
        attMonthControl?.classList.toggle('hidden', value !== 'monthly');
    };
    attTimeframeSelect?.addEventListener('change', () => {
        updateAttPeriodControls();
        refreshDashboardCharts(false);
    });
    attDayInput?.addEventListener('change', () => refreshDashboardCharts(false));
    attWeekSelect?.addEventListener('change', () => refreshDashboardCharts(false));
    attMonthSelect?.addEventListener('change', () => refreshDashboardCharts(false));
    updateAttPeriodControls();

    const updateSectionPeriodControls = () => {
        const value = sectionTimeframe?.value || 'day';
        dayControl?.classList.toggle('hidden', value !== 'day' && value !== 'today');
        weekControl?.classList.toggle('hidden', value !== 'weekly');
        monthControl?.classList.toggle('hidden', value !== 'monthly');
    };
    sectionTimeframe?.addEventListener('change', updateSectionPeriodControls);
    sectionDay?.addEventListener('change', () => refreshDashboardCharts(false));
    sectionWeek?.addEventListener('change', () => refreshDashboardCharts(false));
    sectionMonth?.addEventListener('change', () => refreshDashboardCharts(false));
    updateSectionPeriodControls();

    const gradeSelect = document.getElementById('att_grade');
    const sectionSelect = document.getElementById('att_section');
    const updateAttendanceSections = () => {
        const selectedGrade = gradeSelect?.value || '';
        if (!sectionSelect) return;
        Array.from(sectionSelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const optionGrade = String(option.dataset.grade || '');
            const matches = !selectedGrade || optionGrade === selectedGrade;
            option.hidden = !matches;
        });
        if (sectionSelect.value && sectionSelect.selectedOptions[0]?.hidden) {
            sectionSelect.value = '';
        }
    };
    gradeSelect?.addEventListener('change', updateAttendanceSections);
    updateAttendanceSections();

    // Populate the advisory and subject charts immediately on first render.
    refreshDashboardCharts();
});
</script>
@endpush
