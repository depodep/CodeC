@extends('layouts.app')

@section('title', 'Admin Dashboard - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70">

    <!-- Top Header Bar -->
    @include('admin.dashboard.partials.header')

    <!-- Main Single-Page Content Container (3 Large Vertical Rows) -->
    <main class="py-8 px-4 sm:px-6 lg:px-10 w-full space-y-8 flex-1">

        <!-- ================================================================= -->
        <!-- ROW 1: TOTAL STUDENTS                                            -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs p-6 lg:p-8 space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h2 class="text-lg lg:text-xl font-black text-slate-900 tracking-tight">Total Students</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Active student population and demographic distribution metrics.</p>
                    </div>
                </div>

                <!-- Prominent Counter Display -->
                <div class="flex items-center gap-3 bg-slate-50 px-5 py-2.5 rounded-2xl border border-slate-200">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-wider">Enrolled Count:</span>
                    <span class="text-2xl font-black text-slate-900">{{ number_format($totalStudents) }}</span>
                </div>
            </div>

            <!-- Compact Inline Filters Form -->
            <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">School Year</label>
                    <select name="student_sy" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        @foreach($schoolYears ?? ['2027-2028'] as $sy)
                            <option value="{{ $sy }}" {{ request('student_sy', $activeSchoolYear) == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Year Level</label>
                    <select name="student_grade" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        <option value="">All Year Levels</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('student_grade') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Section</label>
                    <select name="student_section" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        <option value="">All Sections</option>
                        @foreach($sections ?? [] as $sec)
                            <option value="{{ $sec }}" {{ request('student_section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Gender</label>
                    <select name="student_gender" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        <option value="">All Genders</option>
                        <option value="Male" {{ request('student_gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('student_gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <a href="{{ route('admin.dashboard') }}" class="w-full py-2 px-3 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition text-center">
                        Reset Filters
                    </a>
                </div>
            </form>

            <!-- Integrated Bar Graph Container -->
            <div style="height: 250px; position: relative; width: 100%;">
                <canvas id="studentDistributionChart"></canvas>
            </div>
        </div>


        <!-- ================================================================= -->
        <!-- ROW 2: ATTENDANCE RATE                                           -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs p-6 lg:p-8 space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h2 class="text-lg lg:text-xl font-black text-slate-900 tracking-tight">Attendance Rate</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Real-time gate scan performance and daily attendance breakdown.</p>
                    </div>
                </div>

                <!-- Prominent Percentage & Summary Values -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-emerald-50 border border-emerald-200 px-4 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-emerald-800 uppercase tracking-wider block">Overall Rate</span>
                        <span class="text-xl font-black text-emerald-700">{{ $overallAttendanceRate }}%</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Present</span>
                        <span class="text-xs font-black text-slate-800">{{ number_format($presentCount) }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Late</span>
                        <span class="text-xs font-black text-amber-600">{{ number_format($lateCount) }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Absent</span>
                        <span class="text-xs font-black text-rose-600">{{ number_format($absentCount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Compact Inline Attendance Filters -->
            <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">School Year</label>
                    <select name="att_sy" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        @foreach($schoolYears ?? ['2027-2028'] as $sy)
                            <option value="{{ $sy }}" {{ request('att_sy', $activeSchoolYear) == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Year Level</label>
                    <select name="att_grade" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        <option value="">All Year Levels</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('att_grade') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Section</label>
                    <select name="att_section" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        <option value="">All Sections</option>
                        @foreach($sections ?? [] as $sec)
                            <option value="{{ $sec }}" {{ request('att_section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Gender</label>
                    <select name="att_gender" onchange="this.form.submit()" class="w-full py-2 px-2.5 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                        <option value="">All Genders</option>
                        <option value="Male" {{ request('att_gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('att_gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Specific Date</label>
                    <input type="date" name="att_date" value="{{ request('att_date') }}" onchange="this.form.submit()" class="w-full py-1.5 px-2 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none">
                </div>

                <div class="flex items-end">
                    <a href="{{ route('admin.dashboard') }}" class="w-full py-2 px-3 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition text-center">
                        Reset Filters
                    </a>
                </div>
            </form>

            <!-- Integrated Attendance Trend Line Graph -->
            <div style="height: 250px; position: relative; width: 100%;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>


        <!-- ================================================================= -->
        <!-- ROW 3: FACULTY EVALUATION                                        -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs p-6 lg:p-8 space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#590d0d] flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <h2 class="text-lg lg:text-xl font-black text-slate-900 tracking-tight">Faculty Evaluation</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Completion tracking across student, peer, and self-evaluation categories.</p>
                    </div>
                </div>

                <!-- Overall Completion & Counts Summary -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-red-50 border border-red-200 px-4 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-[#590d0d] uppercase tracking-wider block">Overall Completion</span>
                        <span class="text-xl font-black text-[#590d0d]">{{ $evalOverallRate }}%</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Completed</span>
                        <span class="text-xs font-black text-slate-900">{{ number_format($evalCompletedCount) }}</span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-2xl text-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Pending</span>
                        <span class="text-xs font-black text-amber-600">{{ number_format($evalPendingCount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Evaluator Type Breakdown Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-black text-slate-700">Students Evaluator</span>
                        <span class="font-black text-[#590d0d]">{{ $studentEvalRate }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-[#590d0d] h-full rounded-full" style="width: {{ $studentEvalRate }}%"></div>
                    </div>
                    <p class="text-[10px] font-semibold text-slate-500">Students evaluating assigned faculty members.</p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-black text-slate-700">Peers Evaluator</span>
                        <span class="font-black text-amber-600">{{ $peerEvalRate }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full" style="width: {{ $peerEvalRate }}%"></div>
                    </div>
                    <p class="text-[10px] font-semibold text-slate-500">Faculty assessing fellow faculty members.</p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-black text-slate-700">Personal / Self</span>
                        <span class="font-black text-blue-600">{{ $personalEvalRate }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-blue-600 h-full rounded-full" style="width: {{ $personalEvalRate }}%"></div>
                    </div>
                    <p class="text-[10px] font-semibold text-slate-500">Self-evaluation assessments submitted by teachers.</p>
                </div>
            </div>

            <!-- Integrated Comparison Bar Graph -->
            <div style="height: 220px; position: relative; width: 100%;">
                <canvas id="facultyEvaluationChart"></canvas>
            </div>
        </div>

    </main>
</div>

<!-- Profile Modal Include -->
@include('admin.dashboard.partials.modals')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    // 1. Total Students Distribution Bar Chart
    const studentCtx = document.getElementById('studentDistributionChart').getContext('2d');
    new Chart(studentCtx, {
        type: 'bar',
        data: {
            labels: @json($studentGraphLabels),
            datasets: [{
                label: 'Student Count',
                data: @json($studentGraphData),
                backgroundColor: ['#f59e0b', '#3b82f6'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { weight: 'bold' }, precision: 0 } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

    // 2. Attendance Trend Line Chart
    const attCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    new Chart(attCtx, {
        type: 'line',
        data: {
            labels: @json($attendanceTrendLabels),
            datasets: [{
                label: 'Attendance Rate (%)',
                data: @json($attendanceTrendData),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { min: 0, max: 100, grid: { color: '#f1f5f9' }, ticks: { font: { weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

    // 3. Faculty Evaluation Comparison Bar Chart
    const evalCtx = document.getElementById('facultyEvaluationChart').getContext('2d');
    new Chart(evalCtx, {
        type: 'bar',
        data: {
            labels: ['Students', 'Peers', 'Personal / Self'],
            datasets: [{
                label: 'Completion Rate (%)',
                data: [{{ $studentEvalRate }}, {{ $peerEvalRate }}, {{ $personalEvalRate }}],
                backgroundColor: ['#590d0d', '#f59e0b', '#2563eb'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { min: 0, max: 100, grid: { color: '#f1f5f9' }, ticks: { font: { weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });
});
</script>
@endpush