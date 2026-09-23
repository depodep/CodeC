@extends('layouts.app')

@section('title', 'Student Management | SIATRACK')

@section('content')
<div class="p-6 md:p-8 max-w-7xl mx-auto w-full">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-user-graduate text-[#590d0d] text-lg"></i>
            </div>
            Student Management
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            Manage student records, view academic details, and monitor registered NFC cards.
        </p>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-500"></i> {{ session('success') }}
        </div>
    @endif

    <!-- FILTER SECTION (Gaya ng sa reference) -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('teacher.students') }}" class="flex flex-col md:flex-row gap-3">
            
            <!-- Search Bar -->
            <div class="relative flex-grow">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search name or LRN..." class="w-full pl-10 text-sm font-medium border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
            </div>

            <!-- Section Dropdown -->
            <div class="md:w-48">
                <select name="section" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                    <option value="">All Sections</option>
                    @foreach($sectionsList as $sec)
                        <option value="{{ $sec }}" {{ ($sectionFilter ?? '') == $sec ? 'selected' : '' }}>
                            {{ $sec }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="{{ route('teacher.students') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-sm border border-gray-300">
                    <i class="fa-solid fa-rotate-right"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- MAIN TABLE CONTAINER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Table Header & Action Bar -->
        <div class="bg-[#590d0d] px-5 py-3 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-4 border-amber-400">
            
            <div class="flex items-center gap-3 text-white">
                <i class="fa-solid fa-list text-amber-300"></i>
                <span class="font-black tracking-wider uppercase text-sm">Student Records</span>
                <span class="bg-white/20 text-amber-300 px-2.5 py-0.5 rounded-full text-[11px] font-black border border-white/10">
                    {{ $totalStudents ?? 0 }} Students
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Add Student Button -->
                <button type="button" class="bg-white text-[#590d0d] hover:bg-gray-100 px-3 py-1.5 rounded-md text-xs font-black transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add Student
                </button>
                
                <!-- Export Button -->
                <a href="#" class="bg-white/10 text-white hover:bg-white/20 border border-white/20 px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-download"></i> Export
                </a>

                <!-- Sort A-Z / Z-A Dropdown -->
                <form method="GET" action="{{ route('teacher.students') }}" class="inline-flex ml-2">
                    <input type="hidden" name="search" value="{{ $search ?? '' }}">
                    <input type="hidden" name="section" value="{{ $sectionFilter ?? '' }}">
                    <select name="sort" onchange="this.form.submit()" class="text-xs font-bold border-none rounded-md bg-white text-[#590d0d] focus:ring-0 cursor-pointer py-1.5 pl-3 pr-8 shadow-sm">
                        <option value="asc" {{ ($sortOrder ?? 'asc') == 'asc' ? 'selected' : '' }}>A - Z</option>
                        <option value="desc" {{ ($sortOrder ?? '') == 'desc' ? 'selected' : '' }}>Z - A</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Table Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 font-black text-[10px] uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4 w-10 text-center">#</th>
                        <th class="p-4 w-12 text-center">Photo</th>
                        <th class="p-4">Name & LRN</th>
                        <th class="p-4">Section & Grade</th>
                        <th class="p-4 text-center">Gender</th>
                        <th class="p-4">Contact Info</th>
                        <th class="p-4 text-center">NFC Binding</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                    @forelse($students as $index => $student)
                        <tr class="hover:bg-amber-50/50 transition duration-150">
                            
                            <!-- Index -->
                            <td class="p-4 text-center text-gray-400 font-bold">{{ $index + 1 }}</td>
                            
                            <!-- Photo (Avatar Placeholder if no photo) -->
                            <td class="p-4 text-center">
                                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center mx-auto border border-gray-300">
                                    <i class="fa-solid fa-user text-xs"></i>
                                </div>
                            </td>
                            
                            <!-- Name & LRN -->
                            <td class="p-4">
                                <span class="font-black text-[#590d0d] block">{{ $student->last_name }}, {{ $student->first_name }}</span>
                                <span class="text-[10px] font-bold text-gray-500 mt-0.5 block">LRN: <span class="text-gray-400 font-medium">{{ $student->id_number ?? 'N/A' }}</span></span>
                            </td>
                            
                            <!-- Section & Grade -->
                            <td class="p-4">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-widest">
                                    {{ $student->section ?? 'UNASSIGNED' }}
                                </span>
                                <span class="text-[10px] font-semibold text-gray-400 block mt-1">
                                    Grade/Strand: {{ $student->strand ?? 'N/A' }}
                                </span>
                            </td>

                            <!-- Gender -->
                            <td class="p-4 text-center">
                                @if(strtolower($student->gender) === 'male' || $student->gender == 1)
                                    <span class="text-xs font-bold text-blue-500"><i class="fa-solid fa-mars mr-1"></i> Male</span>
                                @elseif(strtolower($student->gender) === 'female' || $student->gender == 2)
                                    <span class="text-xs font-bold text-pink-500"><i class="fa-solid fa-venus mr-1"></i> Female</span>
                                @else
                                    <span class="text-xs text-gray-400">N/A</span>
                                @endif
                            </td>

                            <!-- Contact Info -->
                            <td class="p-4">
                                <div class="text-[11px] text-gray-600 font-medium flex items-center gap-1.5">
                                    <i class="fa-solid fa-phone text-gray-400"></i> {{ $student->parent_contact ?? $student->contact_number ?? 'No Number' }}
                                </div>
                                <div class="text-[11px] text-gray-600 font-medium flex items-center gap-1.5 mt-1">
                                    <i class="fa-solid fa-envelope text-gray-400"></i> {{ $student->email ?? 'No Email' }}
                                </div>
                            </td>

                            <!-- NFC Binding Status -->
                            <td class="p-4 text-center">
                                @if(!empty($student->nfc_uid))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-widest" title="UID: {{ $student->nfc_uid }}">
                                        <i class="fa-solid fa-id-card text-emerald-500"></i> Bound
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[9px] font-black bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-widest">
                                        <i class="fa-solid fa-link-slash text-rose-500"></i> No Card
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button class="w-7 h-7 rounded bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white transition shadow-sm" title="View Details">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                    <button class="w-7 h-7 rounded bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-500 hover:text-white transition shadow-sm" title="Edit Student">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <!-- Blank State if No Records -->
                        <tr>
                            <td colspan="8" class="p-16 text-center text-gray-400">
                                <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                                    <i class="fa-solid fa-user-xmark text-3xl opacity-30"></i>
                                </div>
                                <span class="block font-bold text-gray-500">No student records found.</span>
                                <span class="text-xs mt-1 block">Try adjusting your search or filters.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Footer Info -->
        <div class="p-3.5 bg-gray-50 border-t border-gray-100 text-[11px] font-semibold text-gray-500 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 shrink-0"><i class="fa-solid fa-info text-[9px]"></i></div>
                This list dynamically updates based on real-time database enrollments.
            </div>
        </div>

    </div>
</div>
@endsection