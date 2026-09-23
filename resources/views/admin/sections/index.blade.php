@extends('layouts.app')

@section('title', 'Academic Structure - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60"
     x-data="{ 
         openGrades: {},
         activeFilter: 'all',
         editModal: false,
         editId: '',
         editGrade: '',
         editName: '',
         editStrand: '',
         editAdviserId: '',

         assignModal: false,
         assignSectionId: '',
         assignSectionName: '',
         assignGradeLevel: '',
         currentAdviserId: '',

         toggleGrade(grade) {
             this.openGrades[grade] = !this.openGrades[grade];
         },

         openEdit(id, grade, name, strand, adviserId) {
             this.editId = id;
             this.editGrade = grade;
             this.editName = name;
             this.editStrand = strand !== 'null' ? strand : '';
             this.editAdviserId = adviserId || '';
             this.editModal = true;
         },

         openAssign(id, name, grade, adviserId) {
             this.assignSectionId = id;
             this.assignSectionName = name;
             this.assignGradeLevel = grade;
             this.currentAdviserId = adviserId || '';
             this.assignModal = true;
         },

         isJuniorHigh(grade) {
             return /7|8|9|10/i.test(grade);
         },

         isSeniorHigh(grade) {
             return /11|12/i.test(grade);
         }
     }">

    <!-- Top Header Bar matching Master Timetable Matrix style -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-6 flex flex-col md:flex-row md:items-center justify-between gap-6 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#8b1818] text-white flex items-center justify-center text-lg shadow-sm shrink-0">
                <i class="fa-solid fa-layer-group text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Academic Structure</h1>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wide shadow-2xs">A.Y. {{ $activeSchoolYear ?? '2026-2027' }}</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-1">Manage Class Sections, Grade Levels, Strands, and Faculty Adviser Assignments</p>
            </div>
        </div>
    </header>

    <!-- Main Content Container matching Master Timetable Matrix container -->
    <main class="py-8 px-6 sm:px-10 lg:px-12 w-full space-y-6 flex-1 max-w-[1700px] mx-auto">

        @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-3 shadow-xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-3 shadow-xs">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border-2 border-red-200 text-red-800 text-xs font-bold flex items-center gap-3 shadow-xs">
            <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i>
            <span>Please complete all required fields correctly.</span>
        </div>
        @endif

        <!-- ================= MINI DASHBOARD KPI CARDS BAR (MATCHES IMAGE 3) ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Sections -->
            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Sections</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $totalSectionsCount ?? 0 }}</h3>
                    <p class="text-[10px] text-slate-500 font-bold mt-1">Across all grade levels</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl border border-amber-200 shrink-0">
                    <i class="fa-solid fa-cubes"></i>
                </div>
            </div>

            <!-- Junior High Sections -->
            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Junior High (7-10)</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $jhsSectionsCount ?? 0 }}</h3>
                    <p class="text-[10px] text-blue-600 font-bold mt-1">Grade 7 to 10 sections</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl border border-blue-200 shrink-0">
                    <i class="fa-solid fa-school"></i>
                </div>
            </div>

            <!-- Senior High Sections -->
            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Senior High (11-12)</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $shsSectionsCount ?? 0 }}</h3>
                    <p class="text-[10px] text-purple-600 font-bold mt-1">Grade 11 & 12 sections</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl border border-purple-200 shrink-0">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>

            <!-- Assigned Advisers -->
            <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Assigned Advisers</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $assignedAdvisersCount ?? 0 }} / {{ $totalSectionsCount ?? 0 }}</h3>
                    <p class="text-[10px] font-bold mt-1 {{ ($unassignedSectionsCount ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ ($unassignedSectionsCount ?? 0) > 0 ? ($unassignedSectionsCount . ' sections pending adviser') : 'All sections assigned' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl border border-emerald-200 shrink-0">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
        </div>


        <!-- Compact Form Card for Adding Sections -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 p-6 sm:p-8 shadow-xs space-y-5">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs border border-emerald-200 shrink-0">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Add New Class Section</h3>
                </div>
            </div>

            <form action="{{ route('admin.sections.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Grade Level</label>
                        <input type="text" name="grade_level" placeholder="E.G. GRADE 7 OR GRADE 11" required autocomplete="off"
                               oninput="this.value = this.value.toUpperCase()"
                               class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] uppercase transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Section Name</label>
                        <input type="text" name="section_name" placeholder="E.G. DIAMOND OR AMBER" required autocomplete="off"
                               oninput="this.value = this.value.toUpperCase()"
                               class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] uppercase transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Strand (Optional for SHS)</label>
                        <input type="text" name="strand" placeholder="E.G. STEM OR HUMSS" autocomplete="off"
                               oninput="this.value = this.value.toUpperCase()"
                               class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] uppercase transition shadow-2xs">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-[#8b1818] hover:bg-[#6b1212] text-white text-xs font-black uppercase tracking-wider transition shadow-md shadow-red-950/20 cursor-pointer flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> Save Section
                    </button>
                </div>
            </form>
        </div>

        <!-- Directory Container -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#8b1818] text-white flex items-center justify-center text-xs shadow-xs shrink-0">
                        <i class="fa-solid fa-list-check text-amber-300"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">Registered Sections Directory</h2>
                        <p class="text-[10px] text-slate-400 font-bold">Grouped by Grade Level & Assigned Faculty Advisers</p>
                    </div>
                </div>

                <!-- Junior / Senior Filter Tabs -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl border border-slate-200">
                    <button type="button" @click="activeFilter = 'all'" 
                            :class="activeFilter === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer">
                        All
                    </button>
                    <button type="button" @click="activeFilter = 'jhs'" 
                            :class="activeFilter === 'jhs' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer">
                        Junior High (7–10)
                    </button>
                    <button type="button" @click="activeFilter = 'shs'" 
                            :class="activeFilter === 'shs' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer">
                        Senior High (11–12)
                    </button>
                </div>
            </div>

            @php 
                // Sort grade levels numerically
                $sortedSections = $sections->sortBy(function($item) {
                    preg_match('/\d+/', $item->grade_level, $matches);
                    return isset($matches[0]) ? (int)$matches[0] : 0;
                });
                $groupedSections = $sortedSections->groupBy('grade_level'); 
            @endphp

            <div class="space-y-4">
                @forelse($groupedSections as $gradeLevel => $gradeSections)
                <div class="border-2 border-slate-200 rounded-2xl overflow-hidden transition bg-white shadow-2xs"
                     x-show="activeFilter === 'all' || (activeFilter === 'jhs' && isJuniorHigh('{{ $gradeLevel }}')) || (activeFilter === 'shs' && isSeniorHigh('{{ $gradeLevel }}'))">
                    
                    <!-- Accordion Header -->
                    <button type="button" @click="toggleGrade('{{ $gradeLevel }}')" 
                            class="w-full px-5 py-4 flex items-center justify-between bg-white hover:bg-slate-50/80 transition cursor-pointer text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-3.5 py-1.5 bg-[#8b1818] text-white font-black text-xs rounded-xl shadow-2xs uppercase">
                                {{ str_contains(strtolower($gradeLevel), 'grade') ? $gradeLevel : 'GRADE ' . $gradeLevel }}
                            </span>
                            <span class="text-xs font-bold text-slate-600">
                                {{ $gradeSections->count() }} {{ Str::plural('Section', $gradeSections->count()) }} Registered
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-400 font-bold text-xs">
                            <span class="text-[10px] font-black uppercase tracking-wider" x-text="openGrades['{{ $gradeLevel }}'] ? 'Hide Sections' : 'View Sections'"></span>
                            <i class="fa-solid fa-chevron-down transition-transform duration-200" :class="openGrades['{{ $gradeLevel }}'] ? 'rotate-180 text-[#8b1818]' : ''"></i>
                        </div>
                    </button>

                    <!-- Accordion Content Table -->
                    <div x-show="openGrades['{{ $gradeLevel }}']" x-transition.origin.top style="display: none;" class="border-t-2 border-slate-100 bg-white p-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-200 pb-2">
                                        <th class="py-3 px-4">Grade & Section</th>
                                        <th class="py-3 px-4">Academic Strand</th>
                                        <th class="py-3 px-4">Assigned Faculty Adviser</th>
                                        <th class="py-3 px-4">Enrolled Students</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                                    @foreach($gradeSections as $sec)
                                    <tr class="hover:bg-slate-50/70 transition align-middle">
                                        <!-- Grade & Section Name -->
                                        <td class="py-4 px-4 font-black text-slate-900 uppercase">
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                                <div>
                                                    <span class="text-sm font-black text-slate-900 block">{{ $sec->section_name }}</span>
                                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                                        {{ str_contains(strtolower($sec->grade_level), 'grade') ? $sec->grade_level : 'Grade ' . $sec->grade_level }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Strand -->
                                        <td class="py-4 px-4">
                                            @if($sec->strand)
                                            <span class="px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-900 rounded-lg font-black text-[10px] uppercase">
                                                {{ $sec->strand }}
                                            </span>
                                            @else
                                            <span class="text-slate-400 text-[10px] uppercase font-semibold">General JHS</span>
                                            @endif
                                        </td>

                                        <!-- Assigned Faculty Adviser -->
                                        <td class="py-4 px-4">
                                            @if($sec->adviser)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-900 font-black flex items-center justify-center text-xs border border-amber-300 shadow-2xs">
                                                        {{ strtoupper(substr($sec->adviser->first_name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <span class="font-black text-slate-900 block text-xs">
                                                            {{ $sec->adviser->first_name }} {{ $sec->adviser->last_name }}
                                                        </span>
                                                        <span class="text-[10px] text-slate-400 font-semibold block">{{ $sec->adviser->email }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <button type="button" @click="openAssign('{{ $sec->id }}', '{{ $sec->section_name }}', '{{ $sec->grade_level }}', '')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black transition cursor-pointer">
                                                    <i class="fa-solid fa-user-plus text-[9px]"></i>
                                                    <span>Assign Adviser</span>
                                                </button>
                                            @endif
                                        </td>

                                        <!-- Enrolled Students -->
                                        <td class="py-4 px-4 text-slate-600 font-bold">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-black">
                                                <i class="fa-solid fa-users text-[9px] text-slate-400"></i>
                                                <span>{{ $sec->student_count ?? 0 }} {{ Str::plural('Student', $sec->student_count ?? 0) }}</span>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-4 px-4 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <!-- Assign Teacher Button -->
                                                <button type="button" @click="openAssign('{{ $sec->id }}', '{{ $sec->section_name }}', '{{ $sec->grade_level }}', '{{ optional($sec->adviser)->id }}')"
                                                        title="Assign/Change Adviser"
                                                        class="w-8 h-8 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 flex items-center justify-center text-xs transition cursor-pointer">
                                                    <i class="fa-solid fa-user-tie"></i>
                                                </button>

                                                <!-- Edit Section Button -->
                                                <button type="button" @click="openEdit('{{ $sec->id }}', '{{ $sec->grade_level }}', '{{ $sec->section_name }}', '{{ $sec->strand }}', '{{ optional($sec->adviser)->id }}')"
                                                        title="Edit Section"
                                                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs transition cursor-pointer">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <!-- Delete Section Form -->
                                                <form action="{{ route('admin.sections.destroy', $sec->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this section?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            title="Delete Section"
                                                            class="w-8 h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 flex items-center justify-center text-xs transition cursor-pointer">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                @empty
                <div class="py-12 text-center text-slate-400 font-bold space-y-1 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="text-xl text-slate-300"><i class="fa-solid fa-folder-open"></i></div>
                    <p class="text-xs">No class sections registered yet. Use the form above to add records.</p>
                </div>
                @endforelse
            </div>
        </div>

    </main>

    <!-- ================= EDIT SECTION MODAL (WITH TEACHER ASSIGNMENT INCLUDED) ================= -->
    <div x-show="editModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4" style="display: none;" x-cloak>
        <div @click.outside="editModal = false" class="bg-white rounded-3xl border-2 border-slate-200 p-8 max-w-md w-full shadow-2xl space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Edit Class Section</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form :action="'/admin/sections/' + editId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Grade Level</label>
                    <input type="text" name="grade_level" x-model="editGrade" required autocomplete="off"
                           @input="editGrade = editGrade.toUpperCase()"
                           class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] uppercase transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Section Name</label>
                    <input type="text" name="section_name" x-model="editName" required autocomplete="off"
                           @input="editName = editName.toUpperCase()"
                           class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] uppercase transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Strand (Optional for SHS)</label>
                    <input type="text" name="strand" x-model="editStrand" autocomplete="off"
                           @input="editStrand = editStrand.toUpperCase()"
                           class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] uppercase transition shadow-2xs">
                </div>

                <!-- Teacher Assignment Field inside Edit Modal -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Assigned Faculty Adviser</label>
                    <select name="teacher_id" x-model="editAdviserId"
                            class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] transition cursor-pointer shadow-2xs">
                        <option value="">-- No Adviser (Unassigned) --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->first_name }} {{ $teacher->last_name }} ({{ $teacher->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-3">
                    <button @click="editModal = false" type="button" class="w-full py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase tracking-wider cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#6b1212] text-white text-xs font-black uppercase tracking-wider shadow-md transition cursor-pointer">
                        Update Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= ASSIGN ADVISER MODAL ================= -->
    <div x-show="assignModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4" style="display: none;" x-cloak>
        <div @click.outside="assignModal = false" class="bg-white rounded-3xl border-2 border-slate-200 p-8 max-w-md w-full shadow-2xl space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center text-xs font-black border border-amber-300">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Assign Faculty Adviser</h3>
                        <p class="text-[10px] text-slate-400 font-bold" x-text="'Section ' + assignSectionName + ' (' + assignGradeLevel + ')'"></p>
                    </div>
                </div>
                <button @click="assignModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form :action="'/admin/sections/' + assignSectionId + '/assign-adviser'" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Select Faculty Adviser</label>
                    <select name="teacher_id" x-model="currentAdviserId"
                            class="w-full bg-white border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] transition cursor-pointer shadow-2xs">
                        <option value="">-- No Adviser (Unassigned) --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->first_name }} {{ $teacher->last_name }} ({{ $teacher->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-500 font-medium mt-1.5">Selecting a teacher will update their advisory placement in User Management.</p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <button @click="assignModal = false" type="button" class="w-full py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase tracking-wider cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#6b1212] text-white text-xs font-black uppercase tracking-wider shadow-md transition cursor-pointer">
                        Save Adviser
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection