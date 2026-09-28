@extends('layouts.app')

@section('title', ($schedule->subject_name ?: 'Class List') . ' | SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-[#fcfbfb]">

    <!-- Sticky Top Page Header matching Standard Header Design -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-30 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <a href="{{ route('teacher.classes') }}" class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-base shadow-xs shrink-0 transition" title="Back to Classes">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $schedule->subject_name ?: 'Class List' }}</h1>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-[#590d0d] border border-amber-300 uppercase tracking-wide shadow-2xs">
                        Section {{ $schedule->section }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-1">
                    <i class="fa-regular fa-calendar text-slate-400 mr-1.5"></i>{{ \Carbon\Carbon::now()->format('l, F d, Y') }} &bull; Enrolled Class List &bull; Total Enrolled: <strong class="text-slate-900">{{ $students->count() }} Students</strong>
                </p>
            </div>
        </div>

        <!-- Faculty Profile Badge matching Admin Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('teacher.classes') }}" class="px-4 py-2.5 rounded-xl border-2 border-slate-200 text-xs font-black text-slate-700 hover:bg-slate-100 transition shadow-2xs flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-[#590d0d]"></i>
                <span>All Classes</span>
            </a>
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

        <!-- Card Container -->
        <div class="bg-white rounded-3xl border-2 border-slate-200/90 shadow-xs overflow-hidden">
            <!-- Card Header -->
            <div class="bg-[#590d0d] px-6 py-4 border-b-2 border-amber-400/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 text-amber-300 flex items-center justify-center text-base">
                        <i class="fa-solid fa-address-book"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-white tracking-tight">Official Student Class List</h2>
                        <p class="text-xs text-amber-200/80 font-bold mt-0.5">Enrolled students in {{ $schedule->subject_name }} (Section {{ $schedule->section }})</p>
                    </div>
                </div>
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-black/25 text-amber-300 font-black text-xs">
                        <i class="fa-solid fa-users"></i> {{ $students->count() }} Students Enrolled
                    </span>
                </div>
            </div>

            @if($students->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-500 font-black text-[10px] uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="p-4 w-12 text-center">#</th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4">School ID / LRN</th>
                                <th class="p-4">Academic Placement</th>
                                <th class="p-4">Username</th>
                                <th class="p-4">Contact Details</th>
                                <th class="p-4 text-center">Attendance</th>
                                <th class="p-4 text-center">Evaluation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            @foreach($students as $index => $student)
                                <tr class="hover:bg-amber-50/50 transition duration-150">
                                    <td class="p-4 text-center text-slate-400 font-bold">{{ $index + 1 }}</td>
                                    
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                                {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->last_name ?? 'P', 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="font-black text-[#590d0d] block">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</span>
                                                <span class="text-[10px] text-slate-400 font-bold block mt-0.5">{{ $student->email ?? 'No email on record' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 font-mono font-bold text-slate-800">
                                        {{ $student->id_number ?? 'N/A' }}
                                    </td>

                                    <td class="p-4 text-xs">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-widest">
                                            Grade {{ $student->grade_level ?? 'N/A' }}
                                        </span>
                                        @php $grade = (int) filter_var((string) ($student->grade_level ?? ''), FILTER_SANITIZE_NUMBER_INT); @endphp
                                        @if($grade < 7 || $grade > 10)
                                            <span class="block text-[11px] text-slate-500 font-semibold mt-1">{{ $student->strand ?? 'General' }}</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-xs font-semibold text-slate-600">
                                        {{ $student->username ?? 'N/A' }}
                                    </td>

                                    <td class="p-4 text-xs text-slate-600">
                                        <div class="flex items-center gap-1 font-bold text-slate-800">
                                            <i class="fa-solid fa-phone text-slate-400 text-[10px]"></i>
                                            {{ $student->phone_number ?? 'No student phone' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            {{ $student->parent_name ? $student->parent_name . ' · ' . ($student->parent_phone_number ?? 'No contact phone') : 'No emergency contact' }}
                                        </div>
                                    </td>

                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider {{ ($student->attendance_status ?? 'Absent') === 'Present' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            <i class="fa-solid {{ ($student->attendance_status ?? 'Absent') === 'Present' ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                            {{ $student->attendance_status ?? 'Absent' }}
                                        </span>
                                    </td>

                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider {{ ($student->evaluation_status ?? 'Not Active') === 'Done' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            <i class="fa-solid {{ ($student->evaluation_status ?? 'Not Active') === 'Done' ? 'fa-check' : 'fa-minus' }}"></i>
                                            {{ $student->evaluation_status ?? 'Not Active' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-20 text-center text-slate-400">
                    <div class="w-20 h-20 rounded-3xl bg-slate-50 border-2 border-dashed border-slate-200 text-slate-300 flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">
                        <i class="fa-solid fa-users-slash"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-800">No Students Found</h3>
                    <p class="text-xs text-slate-400 font-semibold mt-1 max-w-sm mx-auto">There are currently no students registered under section "{{ $schedule->section }}".</p>
                </div>
            @endif
        </div>

    </main>

</div>
@endsection