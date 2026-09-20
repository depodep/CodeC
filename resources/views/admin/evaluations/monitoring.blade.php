@extends('layouts.app')

@section('title', 'Faculty Evaluation Monitoring - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70 p-6 lg:p-8 space-y-6">

    <!-- 1. PAGE HEADER -->
    <div class="bg-white p-5 lg:p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <!-- Back Button to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs transition shrink-0" title="Back to Dashboard">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-star-half-stroke"></i>
            </div>
            <div>
                <h1 class="text-lg lg:text-xl font-black text-slate-900 tracking-tight">Faculty Evaluation Monitoring</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Track faculty evaluation progress, completion rates, and pending assignments.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- School Year & Evaluation Period Form -->
            <form method="GET" action="{{ route('admin.evaluations.monitoring') }}" id="filterForm" class="flex flex-wrap items-center gap-2">
                <select name="school_year" onchange="this.form.submit()" class="px-3 py-2 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $activeSchoolYear == $sy ? 'selected' : '' }}>A.Y. {{ $sy }}</option>
                    @endforeach
                </select>

                <select name="evaluation_period" onchange="this.form.submit()" class="px-3 py-2 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                    @foreach($evaluationPeriods as $ep)
                        <option value="{{ $ep }}" {{ $activePeriod == $ep ? 'selected' : '' }}>{{ $ep }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- EVALUATION PERIOD STATUS BANNER -->
    <div class="px-5 py-4 bg-white border border-slate-200 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xs font-bold shrink-0">
                <i class="fa-regular fa-calendar-days"></i>
            </div>
            <div class="text-xs">
                <span class="font-bold text-slate-900">Period: {{ $periodInfo['name'] }}</span>
                <span class="text-slate-400 mx-1.5">|</span>
                <span class="text-slate-500">Duration: <strong class="text-slate-700">{{ $periodInfo['start_date'] }} – {{ $periodInfo['end_date'] }}</strong></span>
            </div>
        </div>
        <div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wide">
                {{ $periodInfo['status'] }}
            </span>
        </div>
    </div>

    <!-- 2. OVERALL EVALUATION SUMMARY -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Overall Completion</span>
            <div class="flex items-baseline justify-between mt-2">
                <h3 class="text-2xl font-black text-[#590d0d] tracking-tight">{{ $overallRate }}%</h3>
            </div>
            <div class="w-full bg-slate-100 h-1 rounded-full overflow-hidden mt-3">
                <div class="bg-[#590d0d] h-full rounded-full" style="width: {{ $overallRate }}%"></div>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Completed</span>
            <div class="flex items-center justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($completedCount) }}</h3>
                <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Done</span>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pending / Not Yet</span>
            <div class="flex items-center justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($notYetEvaluatedCount) }}</h3>
                <span class="text-[11px] font-bold text-amber-600 flex items-center gap-1"><i class="fa-solid fa-clock"></i> Action</span>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Expected</span>
            <div class="flex items-center justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($totalExpected) }}</h3>
                <span class="text-[11px] font-bold text-slate-400">Target</span>
            </div>
        </div>
    </div>

    <!-- 3. EVALUATION COMPLETION BY TYPE -->
    <div class="space-y-3">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Completion by Evaluator Type</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($typesData as $key => $type)
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">{{ $type['label'] }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-red-50 text-[#590d0d] border border-red-200">{{ $type['percentage'] }}%</span>
                </div>
                <div class="flex items-baseline justify-between text-xs">
                    <span class="font-extrabold text-slate-900 text-sm">{{ $type['completed'] }} / {{ $type['expected'] }}</span>
                    <span class="text-slate-400 font-medium">completed</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-[#590d0d] h-full rounded-full" style="width: {{ $type['percentage'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 4 & 5. GRAPHS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Evaluation Completion Progress</h3>
            <div class="h-56 relative w-full">
                <canvas id="progressChart"></canvas>
            </div>
        </div>

        <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Evaluation Completion Trend</h3>
            <div class="h-56 relative w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 6. FILTERS & SEARCH SECTION -->
    <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Filter & Search Records</h3>
        <form method="GET" action="{{ route('admin.evaluations.monitoring') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input type="hidden" name="school_year" value="{{ $activeSchoolYear }}">
            <input type="hidden" name="evaluation_period" value="{{ $activePeriod }}">
            
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Evaluator Type</label>
                <select name="evaluator_type" class="w-full p-2 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none">
                    <option value="">All Types</option>
                    <option value="student" {{ request('evaluator_type') == 'student' ? 'selected' : '' }}>Students</option>
                    <option value="peer" {{ request('evaluator_type') == 'peer' ? 'selected' : '' }}>Peers</option>
                    <option value="personal" {{ request('evaluator_type') == 'personal' ? 'selected' : '' }}>Personal / Self</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Department</label>
                <select name="department" class="w-full p-2 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full p-2 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or ID Number..." class="w-full p-2 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none">
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-1">
                <a href="{{ route('admin.evaluations.monitoring', ['school_year' => $activeSchoolYear, 'evaluation_period' => $activePeriod]) }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">Reset</a>
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#590d0d] hover:bg-[#430909] text-white text-xs font-bold shadow-xs">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- 7. FACULTY EVALUATION SUMMARY TABLE -->
    <div class="p-5 lg:p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Faculty Evaluation Summary</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase font-bold tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">Faculty Name</th>
                        <th class="py-3 px-4 text-center">Faculty ID</th>
                        <th class="py-3 px-4">Department</th>
                        <th class="py-3 px-4 text-center">Student Evals</th>
                        <th class="py-3 px-4 text-center">Peer Evals</th>
                        <th class="py-3 px-4 text-center">Self Evals</th>
                        <th class="py-3 px-4 text-center">Overall</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($facultySummary as $fac)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-extrabold text-slate-900">{{ $fac->name }}</td>
                        <td class="py-3 px-4 text-center font-mono text-[11px] text-slate-500">{{ $fac->id_number }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $fac->department }}</td>
                        <td class="py-3 px-4 text-center font-mono">{{ $fac->student_progress }}</td>
                        <td class="py-3 px-4 text-center font-mono">{{ $fac->peer_progress }}</td>
                        <td class="py-3 px-4 text-center font-mono">{{ $fac->personal_progress }}</td>
                        <td class="py-3 px-4 text-center font-black text-[#590d0d]">{{ $fac->overall_rate }}%</td>
                        <td class="py-3 px-4 text-center">
                            @if($fac->status === 'Completed')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">In Progress</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button onclick="openEvaluationModal('{{ $fac->name }}', '{{ $fac->department }}')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-[#590d0d] hover:text-white text-slate-700 text-[11px] font-bold transition">View</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center text-slate-400 font-medium">No faculty summary records found matching your filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 9. NOT YET EVALUATED SECTION -->
    <div class="p-5 lg:p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Evaluations Not Yet Completed</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase font-bold tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">Evaluator</th>
                        <th class="py-3 px-4">Evaluator Type</th>
                        <th class="py-3 px-4">Faculty Being Evaluated</th>
                        <th class="py-3 px-4 text-center">Period</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($pendingEvaluations as $pend)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $pend->evaluator }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $pend->evaluator_type }}</td>
                        <td class="py-3 px-4 text-slate-700 font-bold">{{ $pend->faculty }}</td>
                        <td class="py-3 px-4 text-center text-slate-500 font-mono text-[11px]">{{ $pend->period }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-900 hover:text-white text-slate-700 text-[11px] font-bold transition">View</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400 font-medium">All pending evaluations have been completed.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 10. EVALUATION DETAILS MODAL -->
<div id="evaluationModal" class="fixed inset-0 z-50 bg-slate-950/60 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Evaluation Assignment Details</h3>
            <button onclick="closeEvaluationModal()" class="text-slate-400 hover:text-slate-700 font-bold text-sm"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="space-y-2.5 text-xs text-slate-600 font-medium">
            <p><strong>Evaluator Name:</strong> <span id="modalEvaluator" class="text-slate-900 font-bold">Juan Dela Cruz</span></p>
            <p><strong>Evaluator Type:</strong> <span id="modalType" class="text-slate-900 font-bold">Personal / Self-Evaluation</span></p>
            <p><strong>Faculty Being Evaluated:</strong> <span id="modalFaculty" class="text-slate-900 font-bold">Same Faculty Member (Self)</span></p>
            <p><strong>Department:</strong> <span id="modalDept" class="text-slate-900 font-bold">Information Technology</span></p>
            <p><strong>Evaluation Period:</strong> <span class="text-slate-900 font-bold">1st Semester 2026-2027</span></p>
            <p><strong>Date Assigned:</strong> <span class="text-slate-900 font-bold">September 1, 2026</span></p>
            <p><strong>Current Status:</strong> <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span></p>
        </div>
        <div class="flex justify-end pt-3 border-t border-slate-100">
            <button onclick="closeEvaluationModal()" class="px-4 py-1.5 rounded-xl bg-[#590d0d] text-white text-xs font-bold">Close</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Progress Bar Chart
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    new Chart(progressCtx, {
        type: 'bar',
        data: {
            labels: ['Students', 'Peers', 'Personal / Self'],
            datasets: [{
                data: [
                    {{ $typesData['student']['percentage'] }}, 
                    {{ $typesData['peer']['percentage'] }}, 
                    {{ $typesData['personal']['percentage'] }}
                ],
                backgroundColor: ['#590d0d', '#f59e0b', '#3b82f6'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });

    // Trend Line Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                data: [
                    {{ $trendData[0]['rate'] }}, 
                    {{ $trendData[1]['rate'] }}, 
                    {{ $trendData[2]['rate'] }}, 
                    {{ $trendData[3]['rate'] }}
                ],
                borderColor: '#590d0d',
                backgroundColor: 'rgba(89, 13, 13, 0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
});

function openEvaluationModal(name, dept) {
    document.getElementById('evaluationModal').classList.remove('hidden');
    document.getElementById('evaluationModal').classList.add('flex');
}

function closeEvaluationModal() {
    document.getElementById('evaluationModal').classList.add('hidden');
    document.getElementById('evaluationModal').classList.remove('flex');
}
</script>
@endpush
@endsection