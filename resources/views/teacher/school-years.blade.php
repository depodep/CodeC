@extends('layouts.app')

@section('title', 'School Year & Sections | SIATRACK')

@section('content')
<div class="p-6 md:p-8 max-w-7xl mx-auto w-full">

    <!-- Page Header (SIATRACK Style) -->
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-layer-group text-[#590d0d] text-lg"></i>
            </div>
            School Year and Sections
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            View real-time school year and section information. Contact administrator to add new records.
        </p>
    </div>

    <!-- SCHOOL YEAR OVERVIEW -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        
        <!-- Header -->
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-[#590d0d]"></i> School Year Overview
            </h2>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-[#590d0d] text-amber-300 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-4 w-12 text-center">#</th>
                        <th class="p-4">School Year</th>
                        <th class="p-4">Start Date</th>
                        <th class="p-4">End Date</th>
                        <th class="p-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-600 font-medium">
                    @forelse($schoolYears as $index => $sy)
                        <tr class="hover:bg-amber-50/50 transition duration-150">
                            <td class="p-4 text-center text-gray-400 font-bold">{{ $index + 1 }}</td>
                            
                            <td class="p-4 font-black text-gray-800">
                                {{ $sy->school_year ?? $sy->academic_year ?? 'N/A' }}
                            </td>
                            
                            <td class="p-4">
                                @if(isset($sy->start_date))
                                    <i class="fa-solid fa-calendar text-gray-400 mr-1"></i> {{ \Carbon\Carbon::parse($sy->start_date)->format('M d, Y') }}
                                @else
                                    <span class="text-gray-400 italic">Not set</span>
                                @endif
                            </td>
                            
                            <td class="p-4">
                                @if(isset($sy->end_date))
                                    <i class="fa-solid fa-calendar-check text-gray-400 mr-1"></i> {{ \Carbon\Carbon::parse($sy->end_date)->format('M d, Y') }}
                                @else
                                    <span class="text-gray-400 italic">Not set</span>
                                @endif
                            </td>
                            
                            <td class="p-4 text-center">
                                @if(isset($sy->is_active) && $sy->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @elseif(isset($sy->status))
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-600 border border-gray-200 uppercase tracking-widest">
                                        {{ $sy->status }}
                                    </span>
                                @else
                                     <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-600 border border-gray-200 uppercase tracking-widest">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-3 block opacity-30"></i>
                                <span class="font-bold">No school year records found in the database.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Footer Note -->
        <div class="p-3.5 bg-gray-50 border-t border-gray-100 text-[11px] font-semibold text-gray-500 flex items-center gap-2">
            <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 shrink-0"><i class="fa-solid fa-info text-[9px]"></i></div>
            To create new school years, please contact your administrator.
        </div>
    </div>

    <!-- SECTION OVERVIEW -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-4">
        
        <!-- Header -->
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-users-rectangle text-[#590d0d]"></i> Section Overview
            </h2>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-[#590d0d] text-amber-300 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-4 w-12 text-center">#</th>
                        <th class="p-4">Section Name</th>
                        <th class="p-4">Grade Level / Strand</th>
                        <th class="p-4 text-center">AM Time In</th>
                        <th class="p-4 text-center">AM Time Out</th>
                        <th class="p-4 text-center">PM Time In</th>
                        <th class="p-4 text-center">PM Time Out</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-600 font-medium">
                    @forelse($sections as $index => $section)
                        <tr class="hover:bg-amber-50/50 transition duration-150">
                            <td class="p-4 text-center text-gray-400 font-bold">{{ $index + 1 }}</td>
                            
                            <!-- ITO ANG NA-UPDATE PARA IWAS ERROR -->
                            <td class="p-4 font-black text-[#590d0d]">
                                {{ $section->section_name ?? $section->name ?? 'N/A' }}
                            </td>
                            
                            <td class="p-4">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $section->grade_level ?? $section->strand ?? 'N/A' }}
                                </span>
                            </td>
                            
                            <!-- DYNAMIC TIME CHECKING -->
                            @php 
                                $amIn = $section->am_time_in ?? $section->am_in ?? null;
                                $amOut = $section->am_time_out ?? $section->am_out ?? null;
                                $pmIn = $section->pm_time_in ?? $section->pm_in ?? null;
                                $pmOut = $section->pm_time_out ?? $section->pm_out ?? null;
                            @endphp

                            <td class="p-4 text-center text-[12px] font-semibold">{{ $amIn ? \Carbon\Carbon::parse($amIn)->format('h:i A') : '--:--' }}</td>
                            <td class="p-4 text-center text-[12px] font-semibold">{{ $amOut ? \Carbon\Carbon::parse($amOut)->format('h:i A') : '--:--' }}</td>
                            <td class="p-4 text-center text-[12px] font-semibold">{{ $pmIn ? \Carbon\Carbon::parse($pmIn)->format('h:i A') : '--:--' }}</td>
                            <td class="p-4 text-center text-[12px] font-semibold">{{ $pmOut ? \Carbon\Carbon::parse($pmOut)->format('h:i A') : '--:--' }}</td>
                            
                            <td class="p-4 text-center">
                                <button class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-500 hover:text-white transition shadow-sm" title="View Details">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-3 block opacity-30"></i>
                                <span class="font-bold">No section records found in the database.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Footer Note -->
        <div class="p-3.5 bg-gray-50 border-t border-gray-100 text-[11px] font-semibold text-gray-500 flex items-center gap-2">
            <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 shrink-0"><i class="fa-solid fa-info text-[9px]"></i></div>
            Sections help organize students and generate detailed analytics.
        </div>
    </div>

</div>
@endsection