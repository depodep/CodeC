@extends('layouts.app')

@section('title', 'Dashboard | SIATRACK')

@section('content')
<div class="p-6 md:p-8 max-w-7xl mx-auto w-full">

    <!-- Header Section -->
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-house text-[#590d0d] text-lg"></i>
            </div>
            Dashboard
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            Welcome back, <span class="font-bold text-[#590d0d]">{{ strtoupper($teacher->first_name . ' ' . $teacher->last_name) }}</span>!
        </p>
    </div>

    <!-- Active School Year Alert -->
    @if(!$activePeriod)
        <div class="mb-6 bg-rose-50 border border-rose-200 rounded-xl p-5 shadow-sm flex flex-col items-center justify-center text-center">
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-3">
                <i class="fa-solid fa-calendar-xmark text-xl"></i>
            </div>
            <h3 class="text-rose-800 font-black text-lg">No Active School Year</h3>
            <p class="text-rose-600 text-sm font-semibold mt-1">
                The current date ({{ now()->format('M d, Y') }}) is outside any school year date range.
            </p>
        </div>
    @else
        <div class="mb-6 bg-[#590d0d] rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between border-b-4 border-amber-400">
            <div>
                <h3 class="text-amber-300 font-black text-lg flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap"></i> Current Academic Period
                </h3>
                <p class="text-white text-sm font-medium mt-1 opacity-90">
                    <i class="fa-solid fa-calendar-day mr-1"></i> S.Y. {{ $activePeriod->school_year ?? 'N/A' }}
                </p>
            </div>
        </div>
    @endif

    @if($activeEvaluationCycle)
        <div class="mb-6 bg-emerald-50 border-2 border-emerald-200 rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clipboard-check text-lg"></i>
                </div>
                <div>
                    <h3 class="text-emerald-900 font-black text-base">Faculty Evaluation is Open</h3>
                    <p class="text-emerald-700 text-sm font-semibold mt-0.5">{{ $activeEvaluationCycle->name }}</p>
                    <p class="text-emerald-600 text-xs font-medium mt-1">
                        Submit your peer and self-evaluation before
                        {{ \Carbon\Carbon::parse($activeEvaluationCycle->end_date)->format('M d, Y') }}.
                    </p>
                </div>
            </div>
            <a href="{{ route('teacher.evaluations.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-sm shrink-0">
                <i class="fa-solid fa-arrow-right"></i>
                <span>Start Evaluation</span>
            </a>
        </div>
    @endif

    <!-- 4 TOP CARDS (SIATRACK Theme) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        
        <!-- Total Students -->
        <div class="bg-blue-600 rounded-xl p-5 text-white shadow-sm flex items-center justify-between relative overflow-hidden">
            <div class="z-10">
                <div class="text-3xl font-black">{{ $totalStudents }}</div>
                <div class="text-xs font-bold text-blue-200 uppercase tracking-wider mt-1">My Students</div>
            </div>
            <i class="fa-solid fa-users text-5xl opacity-20 z-10"></i>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white opacity-10 rounded-full"></div>
        </div>

        <!-- My Sections -->
        <div class="bg-[#590d0d] rounded-xl p-5 text-white shadow-sm flex items-center justify-between relative overflow-hidden">
            <div class="z-10">
                <div class="text-3xl font-black">{{ $mySectionsCount }}</div>
                <div class="text-xs font-bold text-amber-300 uppercase tracking-wider mt-1">My Sections</div>
            </div>
            <i class="fa-solid fa-chalkboard-user text-5xl opacity-20 z-10"></i>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white opacity-10 rounded-full"></div>
        </div>

        <!-- Present Today -->
        <div class="bg-emerald-500 rounded-xl p-5 text-white shadow-sm flex items-center justify-between relative overflow-hidden">
            <div class="z-10">
                <div class="text-3xl font-black">{{ $presentToday }}</div>
                <div class="text-xs font-bold text-emerald-100 uppercase tracking-wider mt-1">Present Today</div>
            </div>
            <i class="fa-regular fa-calendar-check text-5xl opacity-20 z-10"></i>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white opacity-10 rounded-full"></div>
        </div>

        <!-- Attendance Rate -->
        <div class="bg-amber-400 rounded-xl p-5 text-[#590d0d] shadow-sm flex items-center justify-between relative overflow-hidden">
            <div class="z-10">
                <div class="text-3xl font-black">{{ $attendanceRate }}%</div>
                <div class="text-xs font-bold opacity-80 uppercase tracking-wider mt-1">Today's Rate</div>
            </div>
            <i class="fa-solid fa-chart-line text-5xl opacity-20 z-10"></i>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-[#590d0d] opacity-5 rounded-full"></div>
        </div>
    </div>

    <!-- ATTENDANCE STATISTICS DASHBOARD -->
    <div class="mb-4">
        <h2 class="text-lg font-black text-[#590d0d] mb-4 flex items-center gap-2">
            <i class="fa-solid fa-chart-simple text-amber-500"></i> Attendance Statistics Dashboard
        </h2>
        
        <!-- Stats White Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <div class="text-2xl font-black text-blue-600">{{ $totalStudents }}</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">Total Students</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <div class="text-2xl font-black text-emerald-600">{{ $avgAttendanceRate }}%</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">Avg Attendance Rate</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <div class="text-2xl font-black text-amber-500">{{ $presentRecords }}</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">Present Records</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <div class="text-2xl font-black text-[#590d0d]">{{ $totalRecords }}</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">Total Records</div>
            </div>
        </div>
    </div>

    <!-- CHART SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Attendance Trend Chart -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-area text-blue-500"></i> 7-Day Attendance Trend
                </h3>
            </div>
            <div class="p-5">
                <canvas id="attendanceTrendChart" height="250"></canvas>
            </div>
        </div>

        <!-- System Activity Overview -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-[#590d0d] border-b-2 border-amber-400 px-5 py-3">
                <h3 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-bell"></i> System Alerts & Updates
                </h3>
            </div>
            <div class="p-5">
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">System Gateway Active</p>
                            <p class="text-xs text-gray-500 font-medium">iProgSMS gateway is ready to send notifications.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-id-card text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">NFC Reader Connected</p>
                            <p class="text-xs text-gray-500 font-medium">The attendance tapping module is online.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- CHART.JS SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
        
        // Data mula sa Controller
        const labels = @json($chartDates);
        const dataPoints = @json($chartPresents);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Present Students',
                    data: dataPoints,
                    borderColor: '#3b82f6', // Blue color
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3 // Para medyo curve ang linya
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Whole numbers lang para sa dami ng tao
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Tinago natin para mas malinis
                    }
                }
            }
        });
    });
</script>
@endsection