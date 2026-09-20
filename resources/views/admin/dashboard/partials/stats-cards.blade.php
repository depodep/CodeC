<!-- ================= Section 1: KPI Summary Metrics ================= -->
<div class="space-y-3 w-full">
    <div class="flex items-center justify-between">
        <p class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-[#590d0d] text-xs"></i>
            <span>Institutional Metrics Overview</span>
        </p>
    </div>

    <!-- 3-column grid layout -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">

        <!-- 1. Total Students Enrolled -->
        <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-4 hover:shadow-md hover:border-amber-300 transition w-full">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Total Students</p>
                    <h3 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($totalStudents ?? 0) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>

            <!-- Compact Student Filters Form -->
            <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <input type="hidden" name="school_year" value="{{ $activeSchoolYear ?? '2027-2028' }}">
                <select name="grade_level" onchange="this.form.submit()" class="py-1.5 px-2 text-[11px] font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl outline-none cursor-pointer">
                    <option value="">Year Level: All</option>
                    @foreach($gradeLevels ?? [] as $gl)
                        <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                    @endforeach
                </select>

                <select name="section" onchange="this.form.submit()" class="py-1.5 px-2 text-[11px] font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl outline-none cursor-pointer">
                    <option value="">Section: All</option>
                    @foreach($sections ?? [] as $sec)
                        <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                    @endforeach
                </select>
            </form>

            <div style="height: 60px; width: 100%; position: relative;"><canvas id="studentSparkline" class="pointer-events-none"></canvas></div>

            <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-bold">
                <a href="{{ route('admin.students.analytics') }}" class="text-amber-700 hover:underline inline-flex items-center gap-1.5">
                    <span>View Details</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- 2. Daily Attendance Rate -->
        <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-4 hover:shadow-md hover:border-red-300 transition w-full">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Attendance Rate</p>
                    <h3 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight mt-1">{{ $attendanceRate ?? '0%' }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#590d0d] flex items-center justify-center text-xl shadow-xs shrink-0">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
            </div>

            <!-- Compact Attendance Breakdown Summary -->
            <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-center">
                <div>
                    <span class="text-[9px] font-black text-slate-400 block uppercase">Present</span>
                    <span class="text-xs font-black text-slate-800">{{ number_format($presentTodayCount ?? 0) }}</span>
                </div>
                <div>
                    <span class="text-[9px] font-black text-slate-400 block uppercase">Late</span>
                    <span class="text-xs font-black text-amber-600">{{ number_format($lateTodayCount ?? 0) }}</span>
                </div>
                <div>
                    <span class="text-[9px] font-black text-slate-400 block uppercase">Absent</span>
                    <span class="text-xs font-black text-rose-600">{{ number_format($absentCount ?? 60) }}</span>
                </div>
            </div>

            <div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mb-2">
                    <div class="bg-[#590d0d] h-full rounded-full transition-all duration-500" style="width: {{ min(100, (float)($attendanceRate ?? 0)) }}%"></div>
                </div>
                <div style="height: 26px; width: 100%; position: relative; margin: 4px 0;"><canvas id="attendanceSparkline" class="pointer-events-none"></canvas></div>
                <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-bold">
                    <a href="{{ route('admin.attendance.rate') }}" class="text-[#590d0d] hover:underline inline-flex items-center gap-1.5">
                        <span>View Details</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Faculty Evaluation Progress -->
        <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-4 hover:shadow-md hover:border-blue-300 transition w-full">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Faculty Evaluation</p>
                    <h3 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight mt-1">{{ $evalProgress ?? '0%' }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
            </div>

            <!-- Evaluator Breakdown Types (Students, Peers, Personal/Self) -->
            <div class="space-y-2 pt-1">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-slate-700">Students</span>
                    <span class="font-extrabold text-slate-900">{{ $studentEvalRate ?? '80' }}% completed</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-[#590d0d] h-full rounded-full" style="width: {{ $studentEvalRate ?? 80 }}%"></div>
                </div>

                <div class="flex items-center justify-between text-[11px] pt-0.5">
                    <span class="font-bold text-slate-700">Peers</span>
                    <span class="font-extrabold text-slate-900">{{ $peerEvalRate ?? '71' }}% completed</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-amber-500 h-full rounded-full" style="width: {{ $peerEvalRate ?? 71 }}%"></div>
                </div>

                <div class="flex items-center justify-between text-[11px] pt-0.5">
                    <span class="font-bold text-slate-700">Personal (Self)</span>
                    <span class="font-extrabold text-slate-900">{{ $personalEvalRate ?? '73' }}% completed</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-blue-600 h-full rounded-full" style="width: {{ $personalEvalRate ?? 73 }}%"></div>
                </div>
            </div>

            <!-- Completion Status Summary Indicator -->
            <div class="flex items-center justify-between pt-1 text-[11px] font-bold text-slate-600 border-t border-slate-100">
                <span>Completed: <strong class="text-slate-900">{{ number_format($evalCompletedCount ?? 156) }}</strong></span>
                <span class="text-amber-600">Not Yet Evaluated: {{ number_format($evalPendingCount ?? 44) }}</span>
            </div>

            <div class="pt-3.5 border-t border-slate-100 flex items-center justify-end text-xs text-slate-600 font-bold">
                <a href="{{ route('admin.evaluations.monitoring') }}" class="text-blue-600 hover:underline inline-flex items-center gap-1.5">
                    <span>View Details</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

    </div>
</div>