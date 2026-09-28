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
                    'href' => route('admin.evaluations.monitoring'),
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

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-3 2xl:grid-cols-6">
            @foreach($summaryCards as $card)
                <a href="{{ $card['href'] }}" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-xs transition hover:-translate-y-0.5 hover:border-[#c7a0a0] hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-2 truncate text-2xl font-black tracking-tight text-slate-900">{{ $card['value'] }}</p>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-red-100 bg-red-50 text-[#8b1818]">
                            <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                        </span>
                    </div>
                    <p class="mt-2 truncate text-[10px] font-bold text-slate-500">{{ $card['meta'] }}</p>
                </a>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><i class="fa-solid fa-chart-line text-xs"></i></span>
                            <h3 class="text-base font-black text-slate-900">Attendance Rate</h3>
                        </div>
                        <p class="mt-1 text-[11px] font-bold text-slate-500">Seven-day attendance trend from recorded student logs.</p>
                    </div>
                    <a href="{{ route('admin.attendance.rate') }}" class="text-[11px] font-black text-[#8b1818] hover:underline">Analytics <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px]"></i></a>
                </div>
                <form class="dashboard-filter mb-4 grid grid-cols-2 gap-2 rounded-xl border border-slate-100 bg-slate-50 p-2 sm:grid-cols-3">
                    <input type="hidden" name="student_grade" value="{{ request('student_grade') }}">
                    <input type="hidden" name="student_section" value="{{ request('student_section') }}">
                    <label class="sr-only" for="att_grade">Attendance grade</label>
                    <select id="att_grade" name="att_grade" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700">
                        <option value="">All grades</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade }}" @selected(request('att_grade') == $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="att_section">Attendance section</label>
                    <select id="att_section" name="att_section" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700">
                        <option value="">All sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section }}" @selected(request('att_section') == $section)>{{ $section }}</option>
                        @endforeach
                    </select>
                    <select name="att_date" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700">
                        <option value="">All dates</option>
                        <option value="{{ now()->toDateString() }}" @selected(request('att_date') == now()->toDateString())>Today</option>
                    </select>
                </form>
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="rounded-xl bg-emerald-50 p-3"><p class="text-[9px] font-black uppercase text-emerald-700">Rate</p><p id="attendanceRateValue" class="mt-1 text-lg font-black text-emerald-800">{{ $overallAttendanceRate }}%</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-[9px] font-black uppercase text-slate-500">Present</p><p id="attendancePresentValue" class="mt-1 text-lg font-black text-slate-900">{{ number_format($presentCount) }}</p></div>
                    <div class="rounded-xl bg-amber-50 p-3"><p class="text-[9px] font-black uppercase text-amber-700">Late</p><p id="attendanceLateValue" class="mt-1 text-lg font-black text-amber-800">{{ number_format($lateCount) }}</p></div>
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
                        <p class="mt-1 text-[11px] font-bold text-slate-500">Completion by evaluator group.</p>
                    </div>
                    <a href="{{ route('admin.evaluations.monitoring') }}" class="text-[11px] font-black text-[#8b1818] hover:underline">Monitor <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px]"></i></a>
                </div>
                <div class="mb-4 flex items-center gap-4 rounded-xl bg-slate-50 p-3">
                    <div class="text-3xl font-black text-[#590d0d]">{{ $evalOverallRate }}%</div>
                    <div class="text-[10px] font-bold text-slate-500"><span class="font-black text-slate-900">{{ number_format($evalCompletedCount) }}</span> completed<br><span class="font-black text-amber-600">{{ number_format($evalPendingCount) }}</span> pending</div>
                </div>
                <div class="relative h-48">
                    <canvas id="facultyEvaluationChart"></canvas>
                    @if(!$evalCompletedCount && !$evalPendingCount)
                        <p class="pointer-events-none absolute inset-0 flex items-center justify-center text-center text-[11px] font-bold text-slate-400">No evaluation assignments found.</p>
                    @endif
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Student Distribution</h3>
                        <p class="text-[11px] font-bold text-slate-500">Gender breakdown of registered students.</p>
                    </div>
                    <a href="{{ route('admin.students.analytics') }}" class="text-[11px] font-black text-[#8b1818] hover:underline">Details <i class="fa-solid fa-arrow-right ml-1 text-[9px]"></i></a>
                </div>
                <form class="dashboard-filter mb-4 flex flex-wrap gap-2 rounded-xl border border-slate-100 bg-slate-50 p-2">
                    <label class="sr-only" for="student_grade">Student grade</label>
                    <select id="student_grade" name="student_grade" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700">
                        <option value="">All grades</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade }}" @selected(request('student_grade') == $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="student_section">Student section</label>
                    <select id="student_section" name="student_section" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700">
                        <option value="">All sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section }}" @selected(request('student_section') == $section)>{{ $section }}</option>
                        @endforeach
                    </select>
                    <select name="student_gender" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700">
                        <option value="">All genders</option>
                        <option value="Male" @selected(request('student_gender') === 'Male')>Male</option>
                        <option value="Female" @selected(request('student_gender') === 'Female')>Female</option>
                    </select>
                </form>
                <div class="h-40"><canvas id="studentDistributionChart"></canvas></div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Section Population</h3>
                        <p class="text-[11px] font-bold text-slate-500">Students enrolled per section.</p>
                    </div>
                    <i class="fa-solid fa-chart-pie text-[#8b1818]"></i>
                </div>
                <div class="h-40"><canvas id="sectionPopulationChart"></canvas></div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Students by Grade</h3>
                        <p class="text-[11px] font-bold text-slate-500">Enrollment volume per grade level.</p>
                    </div>
                    <i class="fa-solid fa-chart-column text-[#8b1818]"></i>
                </div>
                <div class="h-52"><canvas id="gradeDistributionChart"></canvas></div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Attendance by Section</h3>
                        <p class="text-[11px] font-bold text-slate-500">Filtered attendance rate by section.</p>
                    </div>
                    <i class="fa-solid fa-chart-line text-emerald-700"></i>
                </div>
                <div class="h-52"><canvas id="attendanceSectionChart"></canvas></div>
            </article>
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

        <section class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="mr-2 text-[10px] font-black uppercase tracking-wider text-slate-400">Quick actions</span>
            <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-[#590d0d] px-3 py-2 text-[10px] font-black text-white hover:bg-[#721313]"><i class="fa-solid fa-user-plus mr-1"></i>Add user</a>
            <a href="{{ route('admin.nfc.binding') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-[10px] font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-id-card mr-1"></i>Register NFC</a>
            <a href="{{ route('admin.attendance.live') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-[10px] font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-tower-broadcast mr-1"></i>Live attendance</a>
            <a href="{{ route('admin.evaluations.periods') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-[10px] font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-gear mr-1"></i>Evaluation setup</a>
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
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10, weight: 'bold' } } },
            x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } }
        }
    };
    const attendanceTrendChart = new Chart(document.getElementById('attendanceTrendChart'), {
        type: 'line',
        data: { labels: @json($attendanceTrendLabels), datasets: [{ data: @json($attendanceTrendData), borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, .1)', fill: true, tension: .35, pointRadius: 3, borderWidth: 2 }] },
        options: { ...common, scales: { ...common.scales, y: { ...common.scales.y, min: 0, max: 100 } } }
    });
    new Chart(document.getElementById('facultyEvaluationChart'), {
        type: 'bar',
        data: { labels: ['Students', 'Peers', 'Self'], datasets: [{ data: [{{ $studentEvalRate }}, {{ $peerEvalRate }}, {{ $personalEvalRate }}], backgroundColor: ['#8b1818', '#d97706', '#2563eb'], borderRadius: 5, barThickness: 24 }] },
        options: { ...common, scales: { ...common.scales, y: { ...common.scales.y, min: 0, max: 100 } } }
    });
    const studentDistributionChart = new Chart(document.getElementById('studentDistributionChart'), {
        type: 'bar',
        data: { labels: @json($studentGraphLabels), datasets: [{ data: @json($studentGraphData), backgroundColor: ['#f59e0b', '#3b82f6'], borderRadius: 5, barThickness: 32 }] },
        options: { ...common, scales: { ...common.scales, y: { ...common.scales.y, beginAtZero: true, ticks: { ...common.scales.y.ticks, precision: 0 } } } }
    });
    const sectionPopulationChart = new Chart(document.getElementById('sectionPopulationChart'), {
        type: 'doughnut',
        data: {
            labels: @json($studentSectionLabels),
            datasets: [{ data: @json($studentSectionTotals), backgroundColor: ['#8b1818', '#f59e0b', '#2563eb', '#059669', '#7c3aed', '#db2777'], borderWidth: 2, borderColor: '#fff' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } } } }
    });
    const gradeDistributionChart = new Chart(document.getElementById('gradeDistributionChart'), {
        type: 'bar',
        data: { labels: @json($gradeDistribution->keys()->values()), datasets: [{ label: 'Students', data: @json($gradeDistribution->values()), backgroundColor: '#8b1818', borderRadius: 5, barThickness: 24 }] },
        options: { ...common, scales: { ...common.scales, y: { ...common.scales.y, beginAtZero: true, ticks: { ...common.scales.y.ticks, precision: 0 } } } }
    });
    const attendanceSectionChart = new Chart(document.getElementById('attendanceSectionChart'), {
        type: 'bar',
        data: { labels: @json($attendanceSectionLabels), datasets: [{ label: 'Attendance rate', data: @json($attendanceSectionRates), backgroundColor: '#059669', borderRadius: 5, barThickness: 24 }] },
        options: { ...common, scales: { ...common.scales, y: { ...common.scales.y, min: 0, max: 100, ticks: { ...common.scales.y.ticks, callback: value => value + '%' } } } }
    });

    const students = @json($dashboardStudents);
    const attendanceLogs = @json($dashboardAttendance);
    const attended = log => log.attended === true || log.attended === 1;
    const matchesStudent = (student, filters) =>
        (!filters.grade || student.grade === filters.grade) &&
        (!filters.section || student.section === filters.section) &&
        (!filters.gender || ['male', 'm'].includes(student.gender) === (filters.gender === 'Male'));
    const matchesAttendance = (log, filters) =>
        (!filters.grade || log.grade === filters.grade) &&
        (!filters.section || log.section === filters.section) &&
        (!filters.date || log.date === filters.date);

    function refreshDashboardCharts() {
        const studentForm = document.querySelector('#student_grade')?.form;
        const attendanceForm = document.querySelector('#att_grade')?.form;
        const studentFilters = Object.fromEntries(new FormData(studentForm || document.createElement('form')));
        const attendanceFilters = Object.fromEntries(new FormData(attendanceForm || document.createElement('form')));
        const filteredStudents = students.filter(student => matchesStudent(student, studentFilters));
        const genderCounts = ['male', 'female'].map(gender => filteredStudents.filter(student => gender === 'male'
            ? ['male', 'm'].includes(student.gender)
            : ['female', 'f'].includes(student.gender)).length);
        studentDistributionChart.data.datasets[0].data = genderCounts;
        studentDistributionChart.update();

        const bySection = {};
        filteredStudents.forEach(student => { bySection[student.section] = (bySection[student.section] || 0) + 1; });
        sectionPopulationChart.data.labels = Object.keys(bySection);
        sectionPopulationChart.data.datasets[0].data = Object.values(bySection);
        sectionPopulationChart.update();

        const byGrade = {};
        filteredStudents.forEach(student => { byGrade[student.grade] = (byGrade[student.grade] || 0) + 1; });
        gradeDistributionChart.data.labels = Object.keys(byGrade);
        gradeDistributionChart.data.datasets[0].data = Object.values(byGrade);
        gradeDistributionChart.update();

        const filteredLogs = attendanceLogs.filter(log => matchesAttendance(log, attendanceFilters));
        const present = filteredLogs.filter(log => String(log.status || '').toLowerCase() === 'on-time').length;
        const late = filteredLogs.filter(log => String(log.status || '').toLowerCase() === 'late').length;
        const byAttendanceSection = {};
        filteredLogs.forEach(log => {
            byAttendanceSection[log.section] ||= { total: 0, attended: 0 };
            byAttendanceSection[log.section].total++;
            if (attended(log)) byAttendanceSection[log.section].attended++;
        });
        attendanceSectionChart.data.labels = Object.keys(byAttendanceSection);
        attendanceSectionChart.data.datasets[0].data = Object.values(byAttendanceSection).map(value =>
            value.total ? Math.round((value.attended / value.total) * 1000) / 10 : 0);
        attendanceSectionChart.update();
        const total = filteredLogs.length;
        const rate = total ? Math.round(filteredLogs.filter(attended).length / total * 1000) / 10 : 0;
        document.getElementById('attendanceRateValue').textContent = rate + '%';
        document.getElementById('attendancePresentValue').textContent = present.toLocaleString();
        document.getElementById('attendanceLateValue').textContent = late.toLocaleString();
    }

    document.querySelectorAll('.dashboard-filter').forEach(form => {
        form.addEventListener('submit', event => event.preventDefault());
        form.addEventListener('change', refreshDashboardCharts);
    });
});
</script>
@endpush
