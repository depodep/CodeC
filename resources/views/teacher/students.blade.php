@extends('layouts.app')

@section('title', 'My Students | SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-[#fcfbfb]" x-data="studentPage()">

    <!-- Sticky Top Page Header matching Admin Header Design -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-30 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-lg shadow-sm shrink-0">
                <i class="fa-solid fa-user-graduate text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">My Students</h1>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-[#590d0d] border border-amber-300 uppercase tracking-wide shadow-2xs">Faculty Portal</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-1">
                    <i class="fa-regular fa-calendar text-slate-400 mr-1.5"></i>{{ \Carbon\Carbon::now()->format('l, F d, Y') }} &bull; Advisory and subject classes assigned to your faculty account
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

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 font-bold text-sm flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-sm font-black">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold space-y-1 shadow-2xs">
                <div class="flex items-center gap-2 font-black text-sm mb-1 text-rose-900">
                    <i class="fa-solid fa-triangle-exclamation"></i> Please correct the following errors:
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Metric KPI Count Cards Above matching Admin Design -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Total Students Enrolled -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-blue-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Total Enrolled</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($totalStudents) }}</h3>
                    <span class="text-[11px] font-bold text-slate-500 mt-0.5 block">Across all assignments</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>

            <!-- 2. Advisory Students -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-amber-400 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Advisory Students</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($advisoryCount) }}</h3>
                    <span class="text-[11px] font-bold text-amber-700 mt-0.5 block">Editable records</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
            </div>

            <!-- 3. Subject Students -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-rose-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Subject Load</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($subjectCount) }}</h3>
                    <span class="text-[11px] font-bold text-slate-500 mt-0.5 block">Course timetable</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border-2 border-rose-200 text-[#590d0d] flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>

            <!-- 4. NFC Cards Bound -->
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs hover:shadow-md hover:border-emerald-300 transition flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">NFC Credentials</p>
                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ number_format($boundCount) }}</h3>
                    <span class="text-[11px] font-bold text-emerald-600 mt-0.5 block">{{ $unboundCount }} unassigned</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-id-card"></i>
                </div>
            </div>
        </div>

        <!-- Student Records Card Container -->
        <div class="bg-white rounded-3xl border-2 border-slate-200/90 shadow-xs overflow-hidden">

            <!-- Card Top Header Bar with Neat Tabs -->
            <div class="bg-[#590d0d] px-6 pt-4 border-b-2 border-amber-400/80">
                <div class="flex items-center gap-2 overflow-x-auto pb-0 text-xs font-bold scrollbar-none">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button type="button" @click="setActiveTab(tab.id)"
                            class="relative shrink-0 flex items-center gap-2.5 px-5 py-3 rounded-t-2xl text-xs font-black transition-all cursor-pointer border-t-2 border-x-2"
                            :class="activeTab === tab.id 
                                ? 'bg-white text-[#590d0d] border-white shadow-xs translate-y-[2px] z-10' 
                                : 'text-white hover:text-amber-200 hover:bg-white/10 border-transparent'">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg text-xs"
                                :class="activeTab === tab.id 
                                    ? (tab.type === 'advisory' ? 'bg-amber-100 text-[#590d0d]' : 'bg-red-50 text-[#590d0d]') 
                                    : 'bg-black/20 text-amber-200'">
                                <i :class="tab.type === 'advisory' ? 'fa-solid fa-users-viewfinder' : 'fa-solid fa-book-open'"></i>
                            </span>
                            <span x-text="tab.label"></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                :class="activeTab === tab.id 
                                    ? 'bg-[#590d0d] text-amber-300' 
                                    : 'bg-white/20 text-white'"
                                x-text="tab.students.length"></span>
                        </button>
                    </template>
                    <span x-show="!tabs.length" class="text-white/80 text-xs font-bold py-3">No advisory or subject assignments found.</span>
                </div>
            </div>

            <!-- Instant Search and Filter Toolbar (Updates on changes, No Section filter) -->
            <div class="p-4 sm:p-5 bg-slate-50/80 border-b border-slate-200 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text"
                        x-model="searchQuery"
                        @input="applyFilter()"
                        placeholder="Search student name, LRN, email, or contact..." 
                        class="w-full pl-10 pr-9 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-xl focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d] outline-none bg-white transition shadow-2xs">
                    <button type="button" x-show="searchQuery" @click="searchQuery = ''; applyFilter()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                    <!-- NFC Card Status Filter -->
                    <div class="relative shrink-0 w-full sm:w-auto">
                        <select x-model="nfcFilter" @change="applyFilter()" class="w-full sm:w-auto py-2.5 pl-3.5 pr-8 text-xs font-bold text-slate-700 bg-white border-2 border-slate-200 rounded-xl outline-none focus:border-[#590d0d] cursor-pointer shadow-2xs">
                            <option value="all">All NFC Status</option>
                            <option value="bound">Bound Cards Only</option>
                            <option value="unbound">No Card Only</option>
                        </select>
                    </div>

                    <!-- Sort Order Dropdown -->
                    <div class="relative shrink-0 w-full sm:w-auto">
                        <select x-model="sortOrder" @change="applyFilter()" class="w-full sm:w-auto py-2.5 pl-3.5 pr-8 text-xs font-bold text-slate-700 bg-white border-2 border-slate-200 rounded-xl outline-none focus:border-[#590d0d] cursor-pointer shadow-2xs">
                            <option value="asc">Name: A to Z</option>
                            <option value="desc">Name: Z to A</option>
                        </select>
                    </div>

                    <!-- Apply Button (Updates on click) -->
                    <button type="button" @click="applyFilter()" class="px-5 py-2.5 bg-[#590d0d] hover:bg-[#741212] text-amber-300 font-black text-xs rounded-xl transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                        <i class="fa-solid fa-filter text-[10px]"></i>
                        <span>Apply</span>
                    </button>

                    <!-- Reset Button -->
                    <button type="button" @click="resetFilters()" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl transition border-2 border-slate-200 shadow-2xs cursor-pointer shrink-0">
                        Reset
                    </button>
                    <button type="button" x-show="tabs.find(tab => tab.id === activeTab)?.type === 'advisory'" @click="modal = 'add'" class="px-4 py-2.5 rounded-xl bg-[#590d0d] hover:bg-[#741212] text-amber-300 text-xs font-black transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Add Student</span>
                    </button>
                </div>
            </div>

            <!-- Active Tab Content -->
            <template x-for="tab in tabs" :key="tab.id">
                <section x-show="activeTab === tab.id" class="overflow-x-auto">
                    <!-- Tab Subheader -->
                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs"
                                :class="tab.type === 'advisory' ? 'bg-amber-100 text-[#590d0d]' : 'bg-slate-100 text-slate-700'">
                                <i :class="tab.type === 'advisory' ? 'fa-solid fa-users-viewfinder' : 'fa-solid fa-book-open'"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-black text-slate-900" x-text="tab.label"></p>
                                </div>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5">
                                    Showing <span class="font-bold text-slate-800" x-text="filteredStudents.length"></span> of <span class="font-bold text-slate-800" x-text="tab.students.length"></span> enrolled students
                                </p>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-black text-slate-400">Official Student Records</span>
                    </div>

                    <!-- Student Table -->
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-500 font-black text-[10px] uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="p-4 w-12 text-center">#</th>
                                <th class="p-4">Student</th>
                                <th class="p-4">Academic Placement</th>
                                <th class="p-4">Contact Details</th>
                                <th class="p-4 text-center">NFC</th>
                                <th class="p-4 text-center">Attendance</th>
                                <th class="p-4 text-center">Evaluation</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            <template x-for="(student, index) in filteredStudents" :key="student.id">
                                <tr class="hover:bg-amber-50/50 transition duration-150">
                                    <!-- Index -->
                                    <td class="p-4 text-center text-slate-400 font-bold" x-text="index + 1"></td>

                                    <!-- Student Name & LRN -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-slate-600 shrink-0">
                                                <span x-text="student.first_name ? student.first_name.charAt(0) : 'S'"></span>
                                            </div>
                                            <div>
                                                <span class="font-black text-[#590d0d] block hover:underline cursor-pointer" @click="viewStudent(student)" x-text="student.last_name + ', ' + student.first_name + (student.middle_name ? ' ' + student.middle_name : '')"></span>
                                                <span class="text-[10px] text-slate-500 font-bold mt-0.5 block" x-text="'LRN: ' + (student.id_number || 'N/A')"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Academic Placement -->
                                    <td class="p-4">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-widest"
                                            x-text="'Grade ' + (student.grade_level || tab.grade || 'N/A')"></span>
                                        <span class="block text-[11px] text-slate-500 font-semibold mt-1" x-text="'Section: ' + (student.section || tab.section || 'Unassigned')"></span>
                                        <template x-if="!['7', '8', '9', '10'].includes(String(student.grade_level || tab.grade || '').replace('Grade ', '').trim())">
                                            <span class="block text-[11px] text-slate-500 font-semibold mt-0.5" x-text="'Strand: ' + (student.strand || 'General')"></span>
                                        </template>
                                    </td>

                                    <!-- Contact Details -->
                                    <td class="p-4 text-xs text-slate-600">
                                        <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                            <i class="fa-solid fa-phone text-slate-400 text-[10px]"></i>
                                            <span x-text="student.phone_number || 'No student phone'"></span>
                                        </div>
                                        <div class="mt-1 text-[11px] text-slate-500" x-text="student.parent_name ? student.parent_name + ' · ' + (student.parent_phone_number || 'No emergency phone') : 'No emergency contact'"></div>
                                    </td>

                                    <!-- NFC Status Badge (NO link or button to contact admin) -->
                                    <td class="p-4 text-center">
                                        <template x-if="student.nfc_card && student.nfc_card.tag_id">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200" title="UID Assigned">
                                                <i class="fa-solid fa-id-card text-emerald-500"></i> Bound
                                            </span>
                                        </template>
                                        <template x-if="!student.nfc_card || !student.nfc_card.tag_id">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                                <i class="fa-solid fa-circle-xmark text-rose-400"></i> No Card
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Attendance Status -->
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider"
                                            :class="student.attendance_status === 'Present' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                            <i class="fa-solid" :class="student.attendance_status === 'Present' ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                                            <span x-text="student.attendance_status || 'Absent'"></span>
                                        </span>
                                    </td>

                                    <!-- Evaluation Status -->
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider"
                                            :class="student.evaluation_status === 'Done' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                                            <i class="fa-solid" :class="student.evaluation_status === 'Done' ? 'fa-check' : 'fa-minus'"></i>
                                            <span x-text="student.evaluation_status || 'Not Active'"></span>
                                        </span>
                                    </td>

                                    <!-- Actions Column (Only Advisory Teacher Can Edit) -->
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- View Details Button (Available to all teachers) -->
                                            <button @click="viewStudent(student)" class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white transition flex items-center justify-center shadow-2xs cursor-pointer" title="View details">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </button>

                                            <!-- Edit Student Button (ONLY visible and enabled for student's Advisory Teacher) -->
                                            <template x-if="student.can_edit">
                                                <button @click="editStudent(student)" class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-500 hover:text-white transition flex items-center justify-center shadow-2xs cursor-pointer" title="Edit student information">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </button>
                                            </template>

                                            <!-- Locked indicator for non-advisory teachers -->
                                            <template x-if="!student.can_edit">
                                                <span class="w-8 h-8 rounded-xl bg-slate-50 text-slate-300 border border-slate-200 flex items-center justify-center text-xs" title="Only the advisory teacher can update this student">
                                                    <i class="fa-solid fa-lock text-[10px]"></i>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty Search / Filter State -->
                            <tr x-show="!filteredStudents.length">
                                <td colspan="8" class="p-16 text-center text-slate-400">
                                    <div class="mx-auto w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4 border border-slate-200 text-slate-300">
                                        <i class="fa-solid fa-users-slash text-2xl"></i>
                                    </div>
                                    <span class="block font-black text-slate-600 text-sm">No students found matching your criteria.</span>
                                    <span class="text-xs text-slate-400 mt-1 block font-semibold">Try clearing the search query or changing your filter selections.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </template>
        </div>

    </main>

    <!-- Add Student Modal -->
    <div x-show="modal === 'add'" x-cloak class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4" @keydown.escape.window="modal = null">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-y-auto border border-slate-200" @click.outside="modal = null">
            <div class="px-6 py-4 bg-[#590d0d] text-white flex justify-between items-center rounded-t-3xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400 text-[#590d0d] flex items-center justify-center text-base font-black"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-amber-300 font-black">New Student</p>
                        <h2 class="text-lg font-black">Add Student to My Section</h2>
                    </div>
                </div>
                <button type="button" @click="modal = null" class="w-8 h-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition cursor-pointer" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('teacher.students.store') }}" class="p-6 space-y-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="block text-xs font-black text-slate-700">First Name <span class="text-rose-500">*</span><input name="first_name" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                    <label class="block text-xs font-black text-slate-700">Middle Name <span class="text-slate-400 font-semibold">(optional)</span><input name="middle_name" class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                    <label class="block text-xs font-black text-slate-700">Last Name <span class="text-rose-500">*</span><input name="last_name" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="block text-xs font-black text-slate-700">LRN / Student ID <span class="text-rose-500">*</span><input name="id_number" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-mono font-bold"></label>
                    <label class="block text-xs font-black text-slate-700">Gender <span class="text-rose-500">*</span><select name="gender" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"><option value="" disabled selected>Select gender</option><option value="Male">Male</option><option value="Female">Female</option></select></label>
                    <label class="block text-xs font-black text-slate-700">Grade Level <span class="text-rose-500">*</span><input name="grade_level" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="block text-xs font-black text-slate-700">Section <span class="text-rose-500">*</span><select name="section" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"><option value="" disabled selected>Select your section</option>@foreach($advisorySections as $section)<option value="{{ $section->section_name }}">{{ $section->section_name }}</option>@endforeach</select></label>
                    <label class="block text-xs font-black text-slate-700">Strand / Track <span class="text-rose-500">*</span><input name="strand" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                    <label class="block text-xs font-black text-slate-700">Phone Number <span class="text-rose-500">*</span><input name="phone_number" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="block text-xs font-black text-slate-700">Emergency Contact Name <span class="text-rose-500">*</span><input name="parent_name" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                    <label class="block text-xs font-black text-slate-700">Relationship <span class="text-rose-500">*</span><select name="parent_relationship" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"><option value="" disabled selected>Select relationship</option><option>Father</option><option>Mother</option><option>Guardian</option><option>Grandparent</option><option>Relative</option><option>Other</option></select></label>
                    <label class="block text-xs font-black text-slate-700">Emergency Contact Phone <span class="text-rose-500">*</span><input name="parent_phone_number" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold"></label>
                </div>
                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-200"><button type="button" @click="modal = null" class="px-5 py-2.5 rounded-xl border-2 border-slate-200 text-xs font-black text-slate-600">Cancel</button><button type="submit" class="px-6 py-2.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black">Add Student</button></div>
            </form>
        </div>
    </div>

    <!-- Student Detail View Modal -->
    <div x-show="modal === 'view'" x-cloak class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4" @keydown.escape.window="modal = null">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-slate-200" @click.outside="modal = null">
            <div class="px-6 py-4 bg-[#590d0d] text-white flex justify-between items-center rounded-t-3xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 text-amber-300 flex items-center justify-center text-base">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-amber-300 font-black">Student Details</p>
                        <h2 class="text-lg font-black" x-text="selected ? selected.last_name + ', ' + selected.first_name + (selected.middle_name ? ' ' + selected.middle_name : '') : ''"></h2>
                    </div>
                </div>
                <button @click="modal = null" class="w-8 h-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <template x-if="selected">
                <div class="p-6 space-y-5 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">LRN / Student ID</span>
                            <p class="font-black text-slate-800 text-sm mt-0.5" x-text="selected.id_number || 'N/A'"></p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">Username</span>
                            <p class="font-black text-slate-800 text-sm mt-0.5" x-text="selected.username || 'N/A'"></p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">Academic Placement</span>
                            <p class="font-bold text-slate-800 text-sm mt-0.5" x-text="'Grade ' + (selected.grade_level || 'N/A') + ' · ' + (selected.section || 'Unassigned')"></p>
                            <template x-if="!['7', '8', '9', '10'].includes(String(selected.grade_level || '').replace('Grade ', '').trim())">
                                <span class="text-[11px] text-slate-500 font-semibold block" x-text="'Strand: ' + (selected.strand || 'General')"></span>
                            </template>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">NFC Binding</span>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                                    :class="selected.nfc_card && selected.nfc_card.tag_id ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                    x-text="selected.nfc_card && selected.nfc_card.tag_id ? 'Card Bound: ' + selected.nfc_card.tag_id : 'No Card Assigned'"></span>
                            </div>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">Student Phone</span>
                            <p class="font-bold text-slate-800 text-sm mt-0.5" x-text="selected.phone_number || 'N/A'"></p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200">
                        <span class="text-amber-900 text-[10px] font-black uppercase tracking-wider block">Emergency Contact</span>
                        <p class="font-black text-slate-900 text-sm mt-1" x-text="selected.parent_name || 'Not provided'"></p>
                        <p class="text-xs text-slate-600 font-semibold mt-0.5" x-text="(selected.parent_relationship || 'Relationship not provided') + ' &bull; ' + (selected.parent_phone_number || 'No contact phone')"></p>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <div>
                            <!-- Advisory status message -->
                            <template x-if="selected.can_edit">
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check"></i> You are this student's advisory teacher.
                                </span>
                            </template>
                            <template x-if="!selected.can_edit">
                                <span class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-lock text-slate-400"></i> Only the advisory teacher can update this record.
                                </span>
                            </template>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="modal = null" class="px-4 py-2 rounded-xl border-2 border-slate-200 text-slate-700 text-xs font-black hover:bg-slate-50 transition cursor-pointer">Close</button>
                            <button x-show="selected.can_edit" @click="editStudent(selected)" class="px-5 py-2 rounded-xl bg-[#590d0d] hover:bg-[#741212] text-amber-300 text-xs font-black shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                <span>Edit Student</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Edit Student Modal (Only accessible if advisory teacher) -->
    <div x-show="modal === 'edit'" x-cloak class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4" @keydown.escape.window="modal = null">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto border border-slate-200" @click.outside="modal = null">
            <div class="px-6 py-4 bg-[#590d0d] text-white flex justify-between items-center rounded-t-3xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400 text-[#590d0d] flex items-center justify-center text-base font-black">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-amber-300 font-black">Advisory Student Update</p>
                        <h2 class="text-lg font-black" x-text="selected ? selected.last_name + ', ' + selected.first_name : 'Edit Student'"></h2>
                    </div>
                </div>
                <button @click="modal = null" class="w-8 h-8 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" :action="'{{ url('/teacher/students') }}/' + (selected ? selected.id : '')" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <label class="block text-xs font-black text-slate-700">
                        First Name <span class="text-rose-500">*</span>
                        <input type="text" name="first_name" x-model="form.first_name" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                    <label class="block text-xs font-black text-slate-700">
                        Middle Name <span class="text-slate-400 font-semibold">(optional)</span>
                        <input type="text" name="middle_name" x-model="form.middle_name" class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                    <label class="block text-xs font-black text-slate-700">
                        Last Name <span class="text-rose-500">*</span>
                        <input type="text" name="last_name" x-model="form.last_name" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <label class="block text-xs font-black text-slate-700">
                        LRN / Student ID <span class="text-rose-500">*</span>
                        <input type="text" name="id_number" x-model="form.id_number" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                    <label class="block text-xs font-black text-slate-700">
                        Phone Number <span class="text-rose-500">*</span>
                        <input type="text" name="phone_number" x-model="form.phone_number" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <label class="block text-xs font-black text-slate-700">
                        Gender <span class="text-rose-500">*</span>
                        <select name="gender" x-model="form.gender" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d]">
                            <option value="" disabled>Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </label>
                    <label class="block text-xs font-black text-slate-700">
                        Grade Level
                        <input type="text" name="grade_level" x-model="form.grade_level" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                    <label class="block text-xs font-black text-slate-700">
                        Section
                        <input type="text" name="section" x-model="form.section" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <label class="block text-xs font-black text-slate-700 md:col-span-1">
                        Strand / Track <span class="text-rose-500">*</span>
                        <input type="text" name="strand" x-model="form.strand" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                    <label class="block text-xs font-black text-slate-700 md:col-span-1">
                        Emergency Contact Name <span class="text-rose-500">*</span>
                        <input type="text" name="parent_name" x-model="form.parent_name" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                    <label class="block text-xs font-black text-slate-700 md:col-span-1">
                        Emergency Contact Phone <span class="text-rose-500">*</span>
                        <input type="text" name="parent_phone_number" x-model="form.parent_phone_number" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d] focus:ring-1 focus:ring-[#590d0d]">
                    </label>
                </div>

                <label class="block text-xs font-black text-slate-700">
                    Relationship <span class="text-rose-500">*</span>
                    <select name="parent_relationship" x-model="form.parent_relationship" required class="mt-1 w-full min-h-11 px-3.5 py-2.5 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#590d0d]">
                        <option value="" disabled>Select relationship</option>
                        <option value="Father">Father</option>
                        <option value="Mother">Mother</option>
                        <option value="Guardian">Guardian</option>
                        <option value="Grandparent">Grandparent</option>
                        <option value="Relative">Relative</option>
                        <option value="Other">Other</option>
                    </select>
                </label>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-200">
                    <button type="button" @click="modal = null" class="px-5 py-2.5 rounded-xl border-2 border-slate-200 text-xs font-black text-slate-600 hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#590d0d] hover:bg-[#741212] text-amber-300 text-xs font-black shadow-xs transition cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function studentPage() {
    const rawTabs = @js($tabs->map(fn($tab) => [
        'id' => $tab['id'],
        'type' => $tab['type'],
        'label' => $tab['label'],
        'grade' => $tab['grade'],
        'section' => $tab['section'],
        'students' => $tab['students']->map(fn($s) => [
            'id' => $s->id,
            'first_name' => $s->first_name,
            'middle_name' => $s->middle_name,
            'last_name' => $s->last_name,
            'id_number' => $s->id_number,
            'username' => $s->username,
            'email' => $s->email,
            'phone_number' => $s->phone_number,
            'gender' => $s->gender,
            'grade_level' => $s->grade_level,
            'strand' => $s->strand,
            'section' => $s->section,
            'attendance_status' => $s->attendance_status ?? 'Absent',
            'evaluation_status' => $s->evaluation_status ?? 'Not Active',
            'parent_name' => $s->parent_name,
            'parent_relationship' => $s->parent_relationship,
            'parent_phone_number' => $s->parent_phone_number,
            'can_edit' => (bool) ($s->can_edit ?? false),
            'nfc_card' => $s->nfcCard ? ['tag_id' => $s->nfcCard->tag_id] : null,
        ])
    ])->values());

    return {
        tabs: rawTabs,
        activeTab: rawTabs.length ? rawTabs[0].id : null,
        searchQuery: @js($search ?? ''),
        nfcFilter: 'all',
        sortOrder: @js($sortOrder ?? 'asc'),
        modal: null,
        selected: null,
        form: {},
        filteredStudents: [],

        init() {
            this.applyFilter();
        },

        setActiveTab(tabId) {
            this.activeTab = tabId;
            this.applyFilter();
        },

        applyFilter() {
            const currentTab = this.tabs.find(t => t.id === this.activeTab);
            if (!currentTab) {
                this.filteredStudents = [];
                return;
            }

            let list = [...currentTab.students];

            // Instant Search Query filter
            const query = (this.searchQuery || '').trim().toLowerCase();
            if (query) {
                list = list.filter(s => {
                    const fullName = `${s.first_name || ''} ${s.middle_name || ''} ${s.last_name || ''}`.toLowerCase();
                    const revName = `${s.last_name || ''} ${s.first_name || ''}`.toLowerCase();
                    const lrn = (s.id_number || '').toLowerCase();
                    const email = (s.email || '').toLowerCase();
                    const phone = (s.phone_number || '').toLowerCase();
                    return fullName.includes(query) || revName.includes(query) || lrn.includes(query) || email.includes(query) || phone.includes(query);
                });
            }

            // NFC Status filter
            if (this.nfcFilter === 'bound') {
                list = list.filter(s => s.nfc_card && s.nfc_card.tag_id);
            } else if (this.nfcFilter === 'unbound') {
                list = list.filter(s => !s.nfc_card || !s.nfc_card.tag_id);
            }

            // Sort order
            list.sort((a, b) => {
                const nameA = (a.last_name || '').toLowerCase();
                const nameB = (b.last_name || '').toLowerCase();
                if (this.sortOrder === 'desc') {
                    return nameB.localeCompare(nameA);
                }
                return nameA.localeCompare(nameB);
            });

            this.filteredStudents = list;
        },

        resetFilters() {
            this.searchQuery = '';
            this.nfcFilter = 'all';
            this.sortOrder = 'asc';
            this.applyFilter();
        },

        viewStudent(student) {
            this.selected = student;
            this.modal = 'view';
        },

        editStudent(student) {
            // Strict authorization check: only advisory teacher can edit
            if (!student.can_edit) {
                alert('Only the advisory teacher can update this student’s information.');
                return;
            }
            this.selected = student;
            this.form = { ...student };
            this.modal = 'edit';
        }
    };
}
</script>
@endpush
@endsection
