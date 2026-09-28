<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - SIATRACK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f4f6f9] text-slate-800 antialiased h-screen w-screen overflow-hidden flex">

@php
    // Kukunin nito yung live status mula sa Admin Toggle Switch
    $evalOpen = \Illuminate\Support\Facades\Cache::get('evaluations_open', false);
@endphp

<!-- ================= Unified SIATRACK Sidebar (Fixed Height) ================= -->
<aside class="w-72 bg-[#8b1818] text-white flex flex-col justify-between shrink-0 h-screen sticky top-0 overflow-hidden shadow-xl z-30">
    <div>
        <!-- Brand Header -->
        <div class="p-6 border-b border-white/10 flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-amber-300 shadow-inner shrink-0">
                <i class="fa-solid fa-graduation-cap text-xl"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-black tracking-tight leading-none text-white">SIATRACK</span>
                </div>
                <p class="text-xs font-bold text-amber-200/80 uppercase tracking-wider mt-1">Southern Isabela Academy</p>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="p-4 space-y-2.5 text-sm font-bold">
            <!-- 1. Dashboard (Active) -->
            <a href="{{ route('student.dashboard') }}" 
               class="flex items-center gap-3.5 px-4 py-3.5 bg-white/15 text-white rounded-2xl shadow-sm border-l-4 border-amber-400 transition">
                <i class="fa-solid fa-gauge-high text-base text-amber-300"></i>
                <span class="font-black text-sm">Dashboard</span>
            </a>

          <!-- 2. Faculty Performance Evaluation -->
            @if($isEvaluationOpen)
                <a href="{{ route('student.evaluations.index') }}" 
                   class="flex items-center justify-between px-4 py-3.5 text-red-100 hover:bg-white/10 hover:text-white rounded-2xl transition group">
                    <div class="flex items-center gap-3.5">
                        <i class="fa-solid fa-users-viewfinder text-base text-amber-400 group-hover:scale-110 transition-transform"></i>
                        <span class="font-extrabold text-sm">Faculty Evaluation</span>
                    </div>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                </a>
            @else
                <div class="flex items-center justify-between px-4 py-3.5 text-red-200/40 rounded-2xl cursor-not-allowed select-none" 
                     title="Faculty evaluation is currently closed by administration.">
                    <div class="flex items-center gap-3.5">
                        <i class="fa-solid fa-lock text-base"></i>
                        <span class="font-extrabold text-sm">Faculty Evaluation</span>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-black px-2 py-0.5 rounded bg-black/20 text-red-200/50">
                        Closed
                    </span>
                </div>
            @endif
        </nav>
    </div>

    <!-- Student Profile Info (Dynamic from Registration) -->
    <div class="p-5 border-t border-white/10 bg-black/15 shrink-0">
        <div class="flex items-center gap-3.5 px-1">
            <div class="w-11 h-11 rounded-2xl bg-amber-400 text-[#8b1818] font-black flex items-center justify-center text-sm shrink-0 shadow-xs">
                {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-black text-white truncate leading-tight">{{ $student->first_name }} {{ $student->last_name }}</p>
                <p class="text-xs font-bold text-amber-200/80 truncate mt-0.5">
                    {{ $student->section ? 'Section ' . $student->section : ($student->id_number ?? 'Student ID') }}
                </p>
            </div>
        </div>
    </div>
</aside>

<!-- ================= Main Fixed-Screen Content Area ================= -->
<main class="flex-1 flex flex-col h-screen min-h-0 min-w-0 overflow-y-auto">

    <!-- Top Header Bar with Clickable Profile & Dropdown -->
    <header class="bg-white border-b border-slate-200 px-8 lg:px-12 py-3.5 flex items-center justify-between shrink-0 z-20 shadow-xs">
        <div>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-xl bg-[#8b1818] text-white text-xs font-black uppercase tracking-wider shadow-xs">
                <i class="fa-solid fa-user-graduate text-sm text-amber-300"></i>
                <span>STUDENT PORTAL</span>
            </span>
        </div>

        <!-- Clickable Profile Trigger -->
        <div class="relative" id="userMenuContainer">
            <button type="button" 
                    id="userMenuButton"
                    onclick="toggleUserMenu()" 
                    class="flex items-center gap-3.5 py-1 px-3 rounded-2xl hover:bg-slate-100 border-2 border-transparent hover:border-slate-200 transition focus:outline-none cursor-pointer">
                <span class="text-sm font-bold text-slate-500 hidden sm:inline">
                    Welcome <span class="font-black text-slate-900 uppercase">{{ $student->last_name }}, {{ $student->first_name }}</span>!
                </span>
                <div class="w-10 h-10 rounded-2xl bg-red-50 border-2 border-red-200 flex items-center justify-center text-[#8b1818] font-black text-sm shadow-xs">
                    {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
                </div>
                <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
            </button>

            <!-- Dropdown Popover -->
            <div id="userDropdown" 
                 class="hidden absolute right-0 mt-3 w-64 bg-white rounded-3xl border-2 border-slate-200 shadow-2xl p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-black text-slate-900 truncate">{{ $student->first_name }} {{ $student->last_name }}</p>
                    <p class="text-xs font-semibold text-slate-400 truncate mt-0.5">{{ $student->username }}</p>
                    <span class="inline-block mt-2 px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-red-50 text-[#8b1818] border border-red-200">
                        {{ $student->id_number ?? 'LRN Not Set' }}
                    </span>
                </div>

                <div class="pt-2">
                    <a href="{{ route('student.profile.edit') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-black text-slate-700 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-user-gear text-sm text-[#8b1818]"></i>
                        <span>Account Settings</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" 
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-black text-red-600 hover:bg-red-50 transition">
                            <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                            <span>Sign Out Account</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Fixed Workspace Body -->
    <div class="flex-1 p-6 lg:p-8 max-w-7xl w-full mx-auto flex flex-col gap-6 min-h-0">

        <!-- Student Master Information Banner (Direct Registration Data) -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 p-6 lg:p-7 shadow-xs space-y-1.5 shrink-0">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Student Name</p>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</h1>
            <p class="text-sm font-bold text-blue-600">Username: {{ $student->username }}</p>
            <p class="text-sm sm:text-base font-extrabold text-amber-700 leading-snug">
                {{ $student->grade_level ?? 'Grade Level Not Set' }} | Section {{ $student->section ?? 'Not Assigned' }} | {{ $student->strand ?? 'Strand Not Set' }}
            </p>
            <p class="text-sm font-black text-[#8b1818]">
                Southern Isabela Academy, Angadanan Campus
            </p>
        </div>

        <!-- Attendance Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 shrink-0">
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Overall Attendance Rate</p>
                <div class="flex items-end gap-2 mt-2"><span class="text-3xl font-black text-[#8b1818]">{{ $attendanceRate }}%</span><span class="text-xs font-bold text-slate-400 mb-1">{{ $attendancePresent }}/{{ $attendanceTotal }} present</span></div>
            </div>
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Attendance Records</p>
                <span class="block text-3xl font-black text-blue-700 mt-2">{{ $attendanceTotal }}</span>
                <span class="text-xs font-semibold text-slate-500">Recorded attendance entries</span>
            </div>
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Enrolled Subjects</p>
                <span class="block text-3xl font-black text-emerald-700 mt-2">{{ count($schedules) }}</span>
                <span class="text-xs font-semibold text-slate-500">Current class schedules</span>
            </div>
        </div>

        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs shrink-0">
            <div class="px-6 py-4 border-b-2 border-slate-100 flex items-center justify-between"><h2 class="text-lg font-black text-[#8b1818]">Attendance by Subject</h2><i class="fa-solid fa-chart-column text-amber-500"></i></div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                @forelse($subjectAttendance as $subject)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between"><div><p class="text-sm font-black text-slate-800">{{ $subject->subject_name }}</p><p class="text-[11px] font-semibold text-slate-500">{{ $subject->rate === null ? 'No linked attendance records yet' : $subject->present . ' of ' . $subject->total . ' present' }}</p></div><span class="text-lg font-black {{ $subject->rate === null ? 'text-slate-400' : ($subject->rate >= 75 ? 'text-emerald-700' : 'text-rose-700') }}">{{ $subject->rate === null ? '--' : $subject->rate . '%' }}</span></div>
                @empty
                    <p class="md:col-span-2 text-xs font-semibold text-slate-500">No enrolled subjects found.</p>
                @endforelse
            </div>
        </div>

        <!-- Full-Height Subject Details Section (Internal Scroll Area) -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs">
            
            <!-- Table Header Strip -->
            <div class="px-8 py-4 border-b-2 border-slate-100 flex items-center justify-between shrink-0">
                <h2 class="text-lg font-black text-[#8b1818] tracking-tight">Subject Details/Information</h2>
                <span class="px-4 py-1.5 rounded-full text-xs font-black bg-red-50 text-[#8b1818] border border-red-200">
                    {{ count($schedules) }} Enrolled Subjects
                </span>
            </div>

            <!-- Scrollable Content Table Box -->
            <div class="p-6">
                <div class="border-2 border-slate-200 rounded-2xl shadow-2xs">
                    
                    <!-- Table Subheader Strip -->
                    <div class="bg-red-50/70 border-b-2 border-slate-200 px-8 py-3.5 flex items-center justify-between text-xs font-black text-slate-700 uppercase tracking-wider shrink-0">
                        <span>SUBJECTDETAILS</span>
                        <span class="pr-12">INSTRUCTOR</span>
                    </div>

                    <!-- Inner Scrollable Subject Rows Container -->
                    <div class="divide-y-2 divide-slate-100">
                        @forelse($schedules as $index => $schedule)
                            <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6 hover:bg-red-50/20 transition duration-150">
                                
                                <!-- Left Subject Info -->
                                <div class="flex items-start gap-5">
                                    <span class="text-slate-400 font-black text-lg pt-0.5">{{ $index + 1 }}.</span>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-3.5">
                                            <i class="fa-solid fa-folder text-amber-500 text-xl shrink-0"></i>
                                            <span class="font-black text-slate-900 text-lg sm:text-xl leading-snug">
                                                {{ $schedule->subject_code ?? 'SUBJ' }} {{ $schedule->subject_name }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-slate-600 font-bold pl-9">
                                            <span class="text-slate-400 font-semibold">Schedule:</span> {{ $schedule->time_slot ?? ($schedule->start_time . ' - ' . $schedule->end_time) }} {{ $schedule->day }}
                                        </p>
                                        <p class="text-sm text-slate-800 font-extrabold pl-9">
                                            <span class="text-slate-400 font-semibold">Subject Teacher:</span> 
                                            {{ $schedule->teacher ? $schedule->teacher->last_name . ', ' . $schedule->teacher->first_name : 'To be assigned' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Instructor Circular Avatar -->
                                <div class="shrink-0 flex items-center justify-center sm:pr-8">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-slate-100 border-2 border-slate-300 overflow-hidden flex items-center justify-center shadow-md">
                                        @if($schedule->teacher)
                                            <div class="w-full h-full bg-gradient-to-tr from-red-100 to-amber-50 flex items-center justify-center text-[#8b1818] font-black text-lg">
                                                {{ strtoupper(substr($schedule->teacher->first_name, 0, 1)) }}{{ strtoupper(substr($schedule->teacher->last_name, 0, 1)) }}
                                            </div>
                                        @else
                                            <i class="fa-solid fa-user-tie text-slate-400 text-2xl"></i>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        @empty
                            <div class="py-16 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-4xl mb-2 text-slate-300 block"></i>
                                <h3 class="text-lg font-black text-slate-700">No Enrolled Subjects Found</h3>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    Class schedules will automatically appear here once registered for {{ $student->grade_level ?? 'your level' }} {{ $student->strand ?? '' }} {{ $student->section ? '(Section ' . $student->section . ')' : '' }}.
                                </p>
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>

<script>
    function toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    }

    // Close dropdown menu when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('userMenuContainer');
        const dropdown = document.getElementById('userDropdown');
        if (container && dropdown && !container.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });
</script>

</body>
</html>