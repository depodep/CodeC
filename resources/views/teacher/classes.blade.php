@extends('layouts.app')

@section('title', 'Classes & Schedules | SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-[#fcfbfb]" x-data="classesPage()">

    <!-- Sticky Top Page Header matching Standard Header Design -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-30 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-lg shadow-sm shrink-0">
                <i class="fa-solid fa-chalkboard-user text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Classes & Schedules</h1>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-[#590d0d] border border-amber-300 uppercase tracking-wide shadow-2xs">Faculty Portal</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-1">
                    <i class="fa-regular fa-calendar text-slate-400 mr-1.5"></i>{{ \Carbon\Carbon::now()->format('l, F d, Y') }} &bull; Assigned Teaching Loads, Timetable Matrix, and Academic Sections
                </p>
            </div>
        </div>

        <!-- Faculty Profile Badge matching Admin Header -->
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3 p-1.5 pr-4 rounded-2xl border-2 border-slate-200 text-left bg-white shadow-2xs">
                <div class="relative">
                    @if(!empty(auth()->user()->photo))
                        <img src="{{ asset('storage/' . auth()->user()->photo) }}" class="w-10 h-10 rounded-xl object-cover border border-amber-300 shadow-xs">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 font-black text-xs flex items-center justify-center shadow-xs">
                            {{ strtoupper(substr(auth()->user()->first_name ?? 'F', 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="hidden sm:block">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-black text-slate-900">
                            {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                        </span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Faculty Member</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="py-8 px-6 sm:px-10 lg:px-12 w-full space-y-6 flex-1 max-w-[1700px] mx-auto">

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-sm flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-sm font-black">&times;</button>
            </div>
        @endif

        <!-- Metric KPI Count Cards Above matching Admin Design -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Active School Year -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-amber-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Active Period</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">A.Y. {{ $activeSchoolYear }}</h3>
                    <span class="text-[11px] font-bold text-emerald-700 mt-0.5 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Current Academic Term
                    </span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>

            <!-- 2. Weekly Class Loads -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-blue-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Teaching Loads</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($totalLoads) }}</h3>
                    <span class="text-[11px] font-bold text-slate-500 mt-0.5 block">Weekly schedule blocks</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>

            <!-- 3. Assigned Sections -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-rose-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Assigned Sections</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($sectionsCount) }}</h3>
                    <span class="text-[11px] font-bold text-[#590d0d] mt-0.5 block">Advisory & Subject Classes</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border-2 border-rose-200 text-[#590d0d] flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>

            <!-- 4. Subject Courses -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-purple-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Course Subjects</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($subjectsCount) }}</h3>
                    <span class="text-[11px] font-bold text-purple-700 mt-0.5 block">Distinct subject titles</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 border-2 border-purple-200 text-purple-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>
        </div>

        <!-- Main Card Container with Neat Tabs -->
        <div class="bg-white rounded-3xl border-2 border-slate-200/90 shadow-xs overflow-hidden">

            <!-- Card Header with Neat Tabs -->
            <div class="bg-[#590d0d] px-6 pt-4 border-b-2 border-amber-400/80">
                <div class="flex items-center gap-2 overflow-x-auto pb-0 text-xs font-bold scrollbar-none">
                    
                    <!-- Tab 1: Weekly Timetable -->
                    <button type="button" @click="activeTab = 'timetable'"
                        class="relative shrink-0 flex items-center gap-2.5 px-5 py-3 rounded-t-2xl text-xs font-black transition-all cursor-pointer border-t-2 border-x-2"
                        :class="activeTab === 'timetable' 
                            ? 'bg-white text-[#590d0d] border-white shadow-xs translate-y-[2px] z-10' 
                            : 'text-white hover:text-amber-200 hover:bg-white/10 border-transparent'">
                        <span class="flex items-center justify-center w-6 h-6 rounded-lg text-xs"
                            :class="activeTab === 'timetable' ? 'bg-amber-100 text-[#590d0d]' : 'bg-black/20 text-amber-200'">
                            <i class="fa-solid fa-table-cells"></i>
                        </span>
                        <span>Weekly Timetable Matrix</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                            :class="activeTab === 'timetable' ? 'bg-[#590d0d] text-amber-300' : 'bg-white/20 text-white'"
                            x-text="filteredSchedules.length"></span>
                    </button>

                    <!-- Tab 2: Class Schedule List -->
                    <button type="button" @click="activeTab = 'list'"
                        class="relative shrink-0 flex items-center gap-2.5 px-5 py-3 rounded-t-2xl text-xs font-black transition-all cursor-pointer border-t-2 border-x-2"
                        :class="activeTab === 'list' 
                            ? 'bg-white text-[#590d0d] border-white shadow-xs translate-y-[2px] z-10' 
                            : 'text-white hover:text-amber-200 hover:bg-white/10 border-transparent'">
                        <span class="flex items-center justify-center w-6 h-6 rounded-lg text-xs"
                            :class="activeTab === 'list' ? 'bg-amber-100 text-[#590d0d]' : 'bg-black/20 text-amber-200'">
                            <i class="fa-solid fa-list-check"></i>
                        </span>
                        <span>Schedule List</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                            :class="activeTab === 'list' ? 'bg-[#590d0d] text-amber-300' : 'bg-white/20 text-white'"
                            x-text="filteredSchedules.length"></span>
                    </button>

                    <!-- Tab 3: Section & Academic Directory -->
                    <button type="button" @click="activeTab = 'sections'"
                        class="relative shrink-0 flex items-center gap-2.5 px-5 py-3 rounded-t-2xl text-xs font-black transition-all cursor-pointer border-t-2 border-x-2"
                        :class="activeTab === 'sections' 
                            ? 'bg-white text-[#590d0d] border-white shadow-xs translate-y-[2px] z-10' 
                            : 'text-white hover:text-amber-200 hover:bg-white/10 border-transparent'">
                        <span class="flex items-center justify-center w-6 h-6 rounded-lg text-xs"
                            :class="activeTab === 'sections' ? 'bg-amber-100 text-[#590d0d]' : 'bg-black/20 text-amber-200'">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>
                        <span>Sections & Academic Directory</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                            :class="activeTab === 'sections' ? 'bg-[#590d0d] text-amber-300' : 'bg-white/20 text-white'">
                            {{ $sections->count() }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Filter Toolbar (Filterable by Section, Search, and Day) -->
            <div x-show="activeTab === 'timetable' || activeTab === 'list'" class="p-4 sm:p-5 bg-slate-50/80 border-b border-slate-200 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                
                <!-- Search Input -->
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text"
                        x-model="searchQuery"
                        @input="applyFilter()"
                        placeholder="Search subject title, code, or section..." 
                        class="w-full pl-10 pr-9 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-xl focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d] outline-none bg-white transition shadow-2xs">
                    <button type="button" x-show="searchQuery" @click="searchQuery = ''; applyFilter()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                    <!-- Section Filter Dropdown (Filterable by Section) -->
                    <div class="relative shrink-0 w-full sm:w-auto">
                        <select x-model="selectedSection" @change="applyFilter()" class="w-full sm:w-auto py-2.5 pl-3.5 pr-8 text-xs font-black text-slate-800 bg-white border-2 border-slate-200 rounded-xl outline-none focus:border-[#590d0d] cursor-pointer shadow-2xs">
                            <option value="">All Assigned Sections ({{ $sectionsCount }})</option>
                            @foreach($teacherSectionNames as $secName)
                                <option value="{{ $secName }}">{{ $secName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Day Filter Dropdown -->
                    <div class="relative shrink-0 w-full sm:w-auto">
                        <select x-model="selectedDay" @change="applyFilter()" class="w-full sm:w-auto py-2.5 pl-3.5 pr-8 text-xs font-bold text-slate-700 bg-white border-2 border-slate-200 rounded-xl outline-none focus:border-[#590d0d] cursor-pointer shadow-2xs">
                            <option value="">All Days (Mon - Fri)</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>

                    <!-- View Switcher -->
                    <div class="flex items-center bg-slate-200/80 p-1 rounded-xl shrink-0">
                        <button type="button" @click="activeTab = 'timetable'"
                            class="px-3 py-1.5 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1.5"
                            :class="activeTab === 'timetable' ? 'bg-white text-[#590d0d] shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                            <i class="fa-solid fa-table-cells"></i>
                            <span class="hidden sm:inline">Matrix</span>
                        </button>
                        <button type="button" @click="activeTab = 'list'"
                            class="px-3 py-1.5 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1.5"
                            :class="activeTab === 'list' ? 'bg-white text-[#590d0d] shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                            <i class="fa-solid fa-list-check"></i>
                            <span class="hidden sm:inline">List</span>
                        </button>
                    </div>

                    <!-- Reset Button -->
                    <button type="button" @click="resetFilters()" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl transition border-2 border-slate-200 shadow-2xs cursor-pointer shrink-0">
                        Reset
                    </button>
                </div>
            </div>

            <!-- ================= TAB 1: WEEKLY TIMETABLE MATRIX ================= -->
            <section x-show="activeTab === 'timetable'" class="p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-calendar-days text-[#590d0d]"></i>
                            <span>Weekly Timetable Matrix</span>
                        </h2>
                        <p class="text-xs text-slate-400 font-semibold mt-0.5">
                            Showing <span class="font-bold text-slate-800" x-text="filteredSchedules.length"></span> classes
                            <template x-if="selectedSection">
                                <span>filtered by section: <strong class="text-[#590d0d]" x-text="selectedSection"></strong></span>
                            </template>
                            &bull; Click any class card to view its student roster
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-900 border border-amber-300">
                            <i class="fa-solid fa-circle-info text-amber-600"></i> Click card to view students
                        </span>
                    </div>
                </div>

                <!-- Matrix Table Grid -->
                <div class="border-2 border-slate-200 rounded-3xl overflow-hidden bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs min-w-[900px]">
                            <thead>
                                <tr class="bg-[#590d0d] text-white uppercase font-black tracking-wider text-[11px]">
                                    <th class="py-4 px-4 border-r border-red-900/40 w-1/5 text-center">Monday</th>
                                    <th class="py-4 px-4 border-r border-red-900/40 w-1/5 text-center">Tuesday</th>
                                    <th class="py-4 px-4 border-r border-red-900/40 w-1/5 text-center">Wednesday</th>
                                    <th class="py-4 px-4 border-r border-red-900/40 w-1/5 text-center">Thursday</th>
                                    <th class="py-4 px-4 w-1/5 text-center">Friday</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 font-semibold text-slate-800">
                                <tr>
                                    <template x-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']" :key="day">
                                        <td class="p-3.5 border-r border-slate-200 last:border-r-0 align-top space-y-3 bg-slate-50/25 min-h-[320px]">
                                            <!-- Classes in this day -->
                                            <template x-for="sched in getDaySchedules(day)" :key="sched.id">
                                                <a :href="'{{ url('/teacher/schedule') }}/' + sched.id + '/students'"
                                                   class="w-full p-3.5 rounded-2xl bg-white border-2 border-slate-200/90 text-left flex flex-col justify-between shadow-2xs transition hover:border-[#590d0d] hover:shadow-md hover:scale-[1.01] block group cursor-pointer relative overflow-hidden">
                                                    
                                                    <!-- Left Accent Bar -->
                                                    <div class="absolute left-0 inset-y-0 w-1.5 bg-[#590d0d] group-hover:w-2 transition-all"></div>

                                                    <div class="pl-2 space-y-1.5">
                                                        <div class="flex items-center justify-between gap-1.5">
                                                            <span class="text-[9px] font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"
                                                                x-text="sched.subject_code || (sched.subject_record ? sched.subject_record.code : 'SUBJ')"></span>
                                                            <span class="text-[9px] font-black text-[#590d0d] bg-amber-50 px-2 py-0.5 rounded border border-amber-200 shadow-2xs truncate max-w-[120px]"
                                                                x-text="'Sec: ' + (sched.normalized_section || 'Amber')"></span>
                                                        </div>

                                                        <h4 class="text-xs font-black text-slate-900 group-hover:text-[#590d0d] tracking-tight leading-snug transition"
                                                            x-text="sched.subject_name || sched.subject || (sched.subject_record ? sched.subject_record.name : 'Unnamed Subject')"></h4>
                                                    </div>

                                                    <div class="mt-3 pl-2 pt-2 border-t border-slate-100 text-[11px] font-bold text-slate-500 flex items-center justify-between">
                                                        <span class="font-mono text-[10px] tracking-tight text-slate-700">
                                                            <i class="fa-regular fa-clock mr-1 text-amber-600"></i>
                                                            <span x-text="formatTime(sched.start_time) + ' - ' + formatTime(sched.end_time)"></span>
                                                        </span>
                                                        <span class="text-[10px] text-[#590d0d] font-black group-hover:translate-x-0.5 transition-transform inline-flex items-center gap-1">
                                                            <i class="fa-solid fa-users text-[9px]"></i>
                                                            <span x-text="sched.student_count"></span>
                                                        </span>
                                                    </div>
                                                </a>
                                            </template>

                                            <!-- Empty state for day -->
                                            <div x-show="getDaySchedules(day).length === 0" class="h-32 w-full flex items-center justify-center text-slate-300 text-xs italic font-normal">
                                                No classes
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ================= TAB 2: CLASS SCHEDULE LIST VIEW ================= -->
            <section x-show="activeTab === 'list'" class="overflow-x-auto">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 bg-white">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-[#590d0d]"></i>
                            <span>Class Schedule List</span>
                        </h2>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">
                            Showing <span class="font-bold text-slate-800" x-text="filteredSchedules.length"></span> assigned class periods
                        </p>
                    </div>
                    <span class="text-[10px] uppercase tracking-widest font-black text-slate-400">Class Load Directory</span>
                </div>

                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 font-black text-[10px] uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="p-4 w-12 text-center">#</th>
                            <th class="p-4">Subject & Code</th>
                            <th class="p-4">Section & Placement</th>
                            <th class="p-4">Day & Time</th>
                            <th class="p-4 text-center">Enrolled Students</th>
                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        <template x-for="(sched, index) in filteredSchedules" :key="sched.id">
                            <tr class="hover:bg-amber-50/50 transition duration-150">
                                <td class="p-4 text-center text-slate-400 font-bold" x-text="index + 1"></td>
                                
                                <!-- Subject -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-red-50 text-[#590d0d] border border-red-200 flex items-center justify-center font-black text-xs shrink-0">
                                            <i class="fa-solid fa-book-bookmark"></i>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-900 block" x-text="sched.subject_name || sched.subject || 'Unnamed Subject'"></span>
                                            <span class="text-[10px] font-mono font-bold text-slate-400 block mt-0.5" x-text="'Code: ' + (sched.subject_code || 'SUBJ')"></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Section -->
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300 uppercase tracking-widest"
                                        x-text="sched.normalized_section || 'Amber'"></span>
                                    <span class="block text-[11px] text-slate-500 font-semibold mt-1"
                                        x-text="'Grade ' + (sched.grade_level || 'N/A') + (sched.strand ? ' · ' + sched.strand : '')"></span>
                                </td>

                                <!-- Day & Time -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200"
                                            x-text="sched.day || 'Day'"></span>
                                        <span class="font-mono text-xs font-bold text-slate-800">
                                            <i class="fa-regular fa-clock text-amber-600 mr-1 text-[11px]"></i>
                                            <span x-text="formatTime(sched.start_time) + ' - ' + formatTime(sched.end_time)"></span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Students Count -->
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fa-solid fa-users text-xs text-blue-500"></i>
                                        <span x-text="sched.student_count + ' Students'"></span>
                                    </span>
                                </td>

                                <!-- Action: View Students -->
                                <td class="p-4 text-center">
                                    <a :href="'{{ url('/teacher/schedule') }}/' + sched.id + '/students'" 
                                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#590d0d] hover:bg-[#741212] text-amber-300 font-black text-xs shadow-2xs transition">
                                        <i class="fa-solid fa-address-book text-xs"></i>
                                        <span>Class Roster</span>
                                    </a>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!filteredSchedules.length">
                            <td colspan="6" class="p-16 text-center text-slate-400">
                                <div class="mx-auto w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4 border border-slate-200 text-slate-300">
                                    <i class="fa-solid fa-calendar-xmark text-2xl"></i>
                                </div>
                                <span class="block font-black text-slate-600 text-sm">No scheduled classes found.</span>
                                <span class="text-xs text-slate-400 mt-1 block font-semibold">Try adjusting your section or day filter.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- ================= TAB 3: SECTIONS & ACADEMIC DIRECTORY ================= -->
            <section x-show="activeTab === 'sections'" class="p-6 space-y-8">
                
                <!-- School Year Overview Table -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 tracking-tight">Institutional School Years</h3>
                                <p class="text-xs text-slate-400 font-semibold">Overview of configured academic periods</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-2 border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-white text-[#590d0d] font-bold uppercase tracking-wider text-[11px] border-b-2 border-slate-200">
                                <tr>
                                    <th class="p-4 w-12 text-center">#</th>
                                    <th class="p-4">School Year</th>
                                    <th class="p-4">Start Date</th>
                                    <th class="p-4">End Date</th>
                                    <th class="p-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                                @forelse($schoolYears as $index => $sy)
                                    <tr class="hover:bg-amber-50/50 transition">
                                        <td class="p-4 text-center text-slate-400 font-bold">{{ $index + 1 }}</td>
                                        <td class="p-4 font-black text-slate-900">
                                            {{ $sy->name ?? $sy->school_year ?? $sy->academic_year ?? 'N/A' }}
                                        </td>
                                        <td class="p-4 text-xs font-semibold">
                                            <i class="fa-regular fa-calendar-check text-emerald-600 mr-1.5"></i>
                                            {{ isset($sy->start_date) && $sy->start_date ? \Carbon\Carbon::parse($sy->start_date)->format('M d, Y') : 'Not configured' }}
                                        </td>
                                        <td class="p-4 text-xs font-semibold">
                                            <i class="fa-regular fa-calendar-xmark text-rose-500 mr-1.5"></i>
                                            {{ isset($sy->end_date) && $sy->end_date ? \Carbon\Carbon::parse($sy->end_date)->format('M d, Y') : 'Not configured' }}
                                        </td>
                                        <td class="p-4 text-center">
                                            @if((isset($sy->is_active) && $sy->is_active) || (isset($sy->status) && strcasecmp($sy->status, 'Active') === 0))
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-widest">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-widest">
                                                    {{ $sy->status ?? 'Completed' }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-slate-400">
                                            No school year records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section Overview Table -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-rose-100 text-[#590d0d] flex items-center justify-center text-xs">
                                <i class="fa-solid fa-users-rectangle"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 tracking-tight">Academic Sections Overview</h3>
                                <p class="text-xs text-slate-400 font-semibold">Class sections, grade levels, and attendance time frames</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-2 border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-white text-[#590d0d] font-bold uppercase tracking-wider text-[11px] border-b-2 border-slate-200">
                                <tr>
                                    <th class="p-4 w-12 text-center">#</th>
                                    <th class="p-4">Section Name</th>
                                    <th class="p-4">Grade Level / Strand</th>
                                    <th class="p-4 text-center">Section Schedule</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                                @forelse($sections as $index => $section)
                                    @php
                                        $secName = $section->section_name ?? $section->name ?? 'N/A';
                                    @endphp
                                    <tr class="hover:bg-amber-50/50 transition">
                                        <td class="p-4 text-center text-slate-400 font-bold">{{ $index + 1 }}</td>
                                        <td class="p-4 font-black text-[#590d0d]">
                                            {{ $secName }}
                                        </td>
                                        <td class="p-4">
                                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                Grade {{ $section->grade_level ?? 'N/A' }} {{ isset($section->strand) && $section->strand ? '· ' . $section->strand : '' }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            @php
                                                $sectionSchedulePayload = [
                                                    'name' => $secName,
                                                    'grade' => $section->grade_level ?? 'N/A',
                                                    'schedules' => $section->schedule_list ?? [],
                                                ];
                                            @endphp
                                            <button type="button" @click="openSectionSchedule(@js($sectionSchedulePayload))" class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-[#590d0d] border border-amber-300 text-xs font-black transition cursor-pointer">
                                                <i class="fa-solid fa-calendar-days mr-1 text-[10px]"></i> View Timetable
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-slate-400">
                                            No section records found in the database.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

        </div>

    </main>

    <!-- Section timetable modal -->
    <div x-show="sectionScheduleModal" x-cloak class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4" @keydown.escape.window="sectionScheduleModal = null">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden border border-slate-200" @click.outside="sectionScheduleModal = null">
            <div class="px-6 py-4 bg-[#590d0d] text-white flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-amber-300 font-black">Section Timetable</p>
                    <h2 class="text-lg font-black" x-text="sectionScheduleModal ? sectionScheduleModal.name : ''"></h2>
                    <p class="text-xs text-amber-100/80 font-semibold" x-text="sectionScheduleModal ? 'Grade ' + sectionScheduleModal.grade : ''"></p>
                </div>
                <button type="button" @click="sectionScheduleModal = null" class="w-8 h-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition cursor-pointer" title="Close timetable">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="overflow-auto max-h-[65vh]">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 font-black text-[10px] uppercase tracking-wider border-b border-slate-200 sticky top-0">
                        <tr>
                            <th class="p-4">Day</th>
                            <th class="p-4">Time</th>
                            <th class="p-4">Subject</th>
                            <th class="p-4">Faculty</th>
                            <th class="p-4 text-center">Assignment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        <template x-for="(schedule, index) in (sectionScheduleModal ? sectionScheduleModal.schedules : [])" :key="index">
                            <tr class="hover:bg-amber-50/50 transition">
                                <td class="p-4 font-black text-[#590d0d]" x-text="schedule.day"></td>
                                <td class="p-4 font-mono text-xs font-bold text-slate-800">
                                    <i class="fa-regular fa-clock text-amber-600 mr-1"></i>
                                    <span x-text="schedule.start_time + ' - ' + schedule.end_time"></span>
                                </td>
                                <td class="p-4 font-black text-slate-900" x-text="schedule.subject"></td>
                                <td class="p-4 text-xs text-slate-600" x-text="schedule.teacher"></td>
                                <td class="p-4 text-center">
                                    <span x-show="schedule.is_mine" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase tracking-wider">
                                        <i class="fa-solid fa-user-check"></i> My Schedule
                                    </span>
                                    <span x-show="!schedule.is_mine" class="text-[10px] font-bold text-slate-400">Other Faculty</span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="sectionScheduleModal && !sectionScheduleModal.schedules.length">
                            <td colspan="5" class="p-12 text-center text-slate-400">No schedule has been assigned to this section.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function classesPage() {
    const rawSchedules = @js($mySchedules->map(fn($sched) => [
        'id' => $sched->id,
        'subject_name' => $sched->subject_name ?: ($sched->subject ?: optional($sched->subjectRecord)->name),
        'subject_code' => $sched->subject_code ?: optional($sched->subjectRecord)->code,
        'grade_level' => optional($sched->academicSection)->grade_level ?: $sched->grade_level,
        'strand' => optional($sched->academicSection)->strand ?: $sched->strand,
        'normalized_section' => $sched->normalized_section,
        'day' => trim($sched->day ?? $sched->day_of_week ?? ''),
        'start_time' => $sched->start_time,
        'end_time' => $sched->end_time,
        'student_count' => $sched->student_count ?? 0,
    ])->values());

    return {
        activeTab: @js($viewMode === 'sections' ? 'sections' : ($viewMode === 'list' ? 'list' : 'timetable')),
        schedules: rawSchedules,
        filteredSchedules: [],
        selectedSection: @js($selectedSection ?? ''),
        selectedDay: '',
        searchQuery: @js($search ?? ''),
        sectionScheduleModal: null,

        init() {
            this.applyFilter();
        },

        applyFilter() {
            let list = [...this.schedules];

            // 1. Filter by Section
            const section = (this.selectedSection || '').trim().toLowerCase();
            if (section) {
                list = list.filter(s => {
                    const sec = (s.normalized_section || '').toLowerCase();
                    return sec === section || sec.includes(section);
                });
            }

            // 2. Filter by Day
            const day = (this.selectedDay || '').trim().toLowerCase();
            if (day) {
                list = list.filter(s => {
                    const schedDay = (s.day || '').toLowerCase();
                    return schedDay === day || schedDay === 'daily' || schedDay.includes(day);
                });
            }

            // 3. Search Query
            const query = (this.searchQuery || '').trim().toLowerCase();
            if (query) {
                list = list.filter(s => {
                    const name = (s.subject_name || '').toLowerCase();
                    const code = (s.subject_code || '').toLowerCase();
                    const sec = (s.normalized_section || '').toLowerCase();
                    return name.includes(query) || code.includes(query) || sec.includes(query);
                });
            }

            // Sort by start_time
            list.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));

            this.filteredSchedules = list;
        },

        resetFilters() {
            this.selectedSection = '';
            this.selectedDay = '';
            this.searchQuery = '';
            this.applyFilter();
        },

        openSectionSchedule(section) {
            this.sectionScheduleModal = section;
        },

        getDaySchedules(targetDay) {
            const dayLower = targetDay.toLowerCase();
            return this.filteredSchedules.filter(s => {
                const sDay = (s.day || '').toLowerCase();
                return sDay === dayLower || sDay === 'daily' || sDay.includes(dayLower);
            });
        },

        formatTime(timeStr) {
            if (!timeStr) return '--:--';
            try {
                const parts = timeStr.split(':');
                let hours = parseInt(parts[0], 10);
                const minutes = parts[1] || '00';
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                return `${hours}:${minutes} ${ampm}`;
            } catch (e) {
                return timeStr;
            }
        }
    };
}
</script>
@endpush
@endsection
