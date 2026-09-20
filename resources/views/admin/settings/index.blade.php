@extends('layouts.app')

@section('title', 'Settings Dashboard - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70 p-6 lg:p-10 space-y-8">
    
    <!-- Top Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border-2 border-slate-200 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">System Settings & Academic Setup</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Manage institutional configurations, school years, sections, and schedules</p>
            </div>
        </div>
    </div>

    <!-- Settings & Academic Setup Hub Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">

        <!-- 1. School Year & Semesters -->
        <a href="{{ route('admin.school-year') }}" class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs hover:border-amber-300 hover:shadow-md transition flex items-start gap-4 group block">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="space-y-1 flex-1">
                <h3 class="text-base font-black text-slate-900 group-hover:text-amber-700 transition">School Year & Semesters</h3>
                <p class="text-xs text-slate-500 font-semibold">Establish active academic calendars, grading periods, and active terms.</p>
            </div>
        </a>

        <!-- 2. Section & Class Management -->
        <a href="{{ route('admin.sections') }}" class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs hover:border-blue-300 hover:shadow-md transition flex items-start gap-4 group block">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="space-y-1 flex-1">
                <h3 class="text-base font-black text-slate-900 group-hover:text-blue-700 transition">Section & Class Management</h3>
                <p class="text-xs text-slate-500 font-semibold">Organize student groups, grade placement tracks, and sections.</p>
            </div>
        </a>

        <!-- 3. Class Schedules & Rooms -->
        <a href="{{ route('admin.schedules.index') }}" class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs hover:border-emerald-300 hover:shadow-md transition flex items-start gap-4 group block">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="space-y-1 flex-1">
                <h3 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition">Class Schedules & Rooms</h3>
                <p class="text-xs text-slate-500 font-semibold">Set daily time tables, room assignments, and subject schedules.</p>
            </div>
        </a>

    </div>

</div>
@endsection