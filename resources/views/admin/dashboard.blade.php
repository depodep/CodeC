@extends('layouts.app')

@section('title', 'Admin Dashboard - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70">

    <!-- Top Header Bar with School Year Scope -->
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                    <i class="fa-solid fa-table-cells-large"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Admin Dashboard</h1>
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">A.Y. {{ $activeSchoolYear ?? '2027-2028' }}</span>
                    </div>
                    <p class="text-xs text-slate-500 font-bold mt-0.5">
                        <i class="fa-regular fa-calendar text-slate-400 mr-1"></i>
                        {{ \Carbon\Carbon::now()->format('l, F d, Y') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-600">School Year:</span>
                <select name="school_year" onchange="this.form.submit()" class="py-1.5 px-3 text-xs font-bold text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                    @foreach($schoolYears ?? ['2026-2027', '2027-2028', '2028-2029'] as $sy)
                        <option value="{{ $sy }}" {{ ($activeSchoolYear ?? '2027-2028') == $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </header>

    <!-- Main Content Container (3 Essential Cards Only) -->
    <main class="p-6 lg:p-10 w-full space-y-8 flex-1">

        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs w-full">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 text-sm cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        <!-- 3-Column Balanced Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 w-full">

            <!-- ========================================================== -->
            <!-- 1. TOTAL STUDENTS CARD                                     -->
            <!-- ========================================================== -->
            <div class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Total Students</span>
                            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($totalStudents ?? 1248) }}</h2>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                    </div>

                    <!-- Compact Filters Form -->
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <input type="hidden" name="school_year" value="{{ $activeSchoolYear ?? '2027-2028' }}">
                        <select name="grade_level" onchange="this.form.submit()" class="py-1.5 px-2 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl outline-none cursor-pointer">
                            <option value="">Year Level: All</option>
                            @foreach($gradeLevels ?? [] as $gl)
                                <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                            @endforeach
                        </select>

                        <select name="section" onchange="this.form.submit()" class="py-1.5 px-2 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl outline-none cursor-pointer">
                            <option value="">Section: All</option>
                            @foreach($sections ?? [] as $sec)
                                <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                            @endforeach
                        </select>
                    </form>

                    <!-- Compact Student Graph -->
                    <div class="h-36 relative w-full pt-1">
                        <canvas id="studentCardChart"></canvas>
                    </div>
                </div>

                <!-- View Details Action -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <a href="{{ route('admin.students.analytics') }}" class="inline-flex items-center gap-1.5 text-xs font-black text-[#590d0d] hover:underline">
                        <span>View Details</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>


            <!-- ========================================================== -->
            <!-- 2. ATTENDANCE RATE CARD                                    -->
            <!-- ========================================================== -->
            <div class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Attendance Rate</span>
                            <h2 class="text-3xl lg:text-4xl font-black text-emerald-600 tracking-tight mt-1">{{ $overallAttendanceRate ?? '94.2' }}%</h2>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                    </div>

                    <!-- Compact Attendance Breakdown Summary -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200 text-center">
                        <div>
                            <span class="text-[10px] font-black text-slate-400 block uppercase">Present</span>
                            <span class="text-xs font-black text-slate-800">{{ number_format($presentCount ?? 1120) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-slate-400 block uppercase">Late</span>
                            <span class="text-xs font-black text-amber-600">{{ number_format($lateCount ?? 68) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-slate-400 block uppercase">Absent</span>
                            <span class="text-xs font-black text-rose-600">{{ number_format($absentCount ?? 60) }}</span>
                        </div>
                    </div>

                    <!-- Compact Progress Visual Indicator -->
                    <div class="space-y-1.5 pt-2">
                        <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase">
                            <span>Operational Rate</span>
                            <span>{{ $overallAttendanceRate ?? '94.2' }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden flex">
                            <div class="bg-emerald-500 h-full" style="width: {{ $overallAttendanceRate ?? 94.2 }}%"></div>
                            <div class="bg-amber-400 h-full" style="width: 3%"></div>
                            <div class="bg-rose-400 h-full" style="width: 2.8%"></div>
                        </div>
                    </div>
                </div>

                <!-- View Details Action -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <a href="{{ route('admin.attendance.rate') }}" class="inline-flex items-center gap-1.5 text-xs font-black text-[#590d0d] hover:underline">
                        <span>View Details</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>


            <!-- ========================================================== -->
            <!-- 3. FACULTY EVALUATION CARD                                 -->
            <!-- ========================================================== -->
            <div class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Faculty Evaluation</span>
                            <h2 class="text-3xl lg:text-4xl font-black text-[#590d0d] tracking-tight mt-1">{{ $evalOverallRate ?? '78' }}%</h2>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#590d0d] flex items-center justify-center text-xl shadow-xs shrink-0">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                    </div>

                    <!-- Evaluator Types Breakdown (Students, Peers, Personal/Self) -->
                    <div class="space-y-2.5 pt-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">Students</span>
                            <span class="font-extrabold text-slate-900">{{ $studentEvalRate ?? '80' }}% completed</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-[#590d0d] h-full rounded-full" style="width: {{ $studentEvalRate ?? 80 }}%"></div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="font-bold text-slate-700">Peers</span>
                            <span class="font-extrabold text-slate-900">{{ $peerEvalRate ?? '71' }}% completed</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full" style="width: {{ $peerEvalRate ?? 71 }}%"></div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="font-bold text-slate-700">Personal (Self)</span>
                            <span class="font-extrabold text-slate-900">{{ $personalEvalRate ?? '73' }}% completed</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full" style="width: {{ $personalEvalRate ?? 73 }}%"></div>
                        </div>
                    </div>

                    <!-- Completion Status Summary Indicator -->
                    <div class="flex items-center justify-between pt-2 text-xs font-bold text-slate-600 border-t border-slate-100">
                        <span>Completed: <strong class="text-slate-900">{{ number_format($evalCompletedCount ?? 156) }}</strong></span>
                        <span class="text-amber-600">Not Yet Evaluated: {{ number_format($evalPendingCount ?? 44) }}</span>
                    </div>
                </div>

                <!-- View Details Action -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <a href="{{ route('admin.evaluations.monitoring') }}" class="inline-flex items-center gap-1.5 text-xs font-black text-[#590d0d] hover:underline">
                        <span>View Details</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

        </div>

    </main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Compact Student Distribution Chart inside Card 1
    const ctx = document.getElementById('studentCardChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [{{ $maleStudents ?? 640 }}, {{ $femaleStudents ?? 608 }}],
                backgroundColor: ['#3b82f6', '#ec4899'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { display: false, beginAtZero: true },
                x: { ticks: { font: { size: 10, weight: 'bold' }, color: '#64748b' }, grid: { display: false } }
            }
        }
    });
});
</script>
@endpush