@extends('layouts.app')

@section('title', 'Reports - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70">

    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex items-center gap-4 sticky top-0 z-20 shadow-xs">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shrink-0">
            <i class="fa-solid fa-file-lines"></i>
        </div>
        <div>
            <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Reports Center</h1>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Review institutional attendance, evaluation, and enrollment reports</p>
        </div>
    </header>

    <main class="p-6 lg:p-10 max-w-[1400px] mx-auto space-y-8">

        {{-- Summary strip --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Students',          'value' => number_format($totalStudents), 'icon' => 'fa-graduation-cap', 'color' => 'blue'],
                ['label' => 'Faculty',            'value' => number_format($totalFaculty),  'icon' => 'fa-chalkboard-user','color' => 'purple'],
                ['label' => 'Attendance Records', 'value' => number_format($totalLogs),     'icon' => 'fa-clipboard-check','color' => 'emerald'],
                ['label' => 'Evaluations',        'value' => number_format($totalEvals),    'icon' => 'fa-star',           'color' => 'amber'],
            ] as $c)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-{{ $c['color'] }}-50 text-{{ $c['color'] }}-600 border border-{{ $c['color'] }}-100 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid {{ $c['icon'] }}"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $c['label'] }}</p>
                    <p class="text-2xl font-black text-slate-900 mt-0.5">{{ $c['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Report Cards --}}
        <div>
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-4">Available Reports</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                {{-- Attendance Report --}}
                <a href="{{ route('admin.reports.attendance') }}"
                   class="group bg-white rounded-2xl border border-slate-200 hover:border-emerald-300 p-6 shadow-xs hover:shadow-md transition flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">On-screen</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-base">Attendance Report</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">Review daily advisory scans and schedule-class time-ins by section and date.</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-black text-emerald-700 group-hover:underline mt-auto">
                        Generate Report <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </div>
                </a>

                {{-- Evaluation Report --}}
                <a href="{{ route('admin.reports.evaluation') }}"
                   class="group bg-white rounded-2xl border border-slate-200 hover:border-[#c7a0a0] p-6 shadow-xs hover:shadow-md transition flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-100 text-[#8b1818] flex items-center justify-center text-xl">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-red-50 text-[#8b1818] border border-red-200">CSV / PDF</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-base">Faculty Evaluation Report</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">360-degree evaluation scores by teacher, including student, peer, and self-evaluation breakdowns.</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-black text-[#8b1818] group-hover:underline mt-auto">
                        Generate Report <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </div>
                </a>

                {{-- SF2 / Enrollment Report --}}
                <a href="{{ route('admin.reports.sf2') }}"
                   class="group bg-white rounded-2xl border border-slate-200 hover:border-blue-300 p-6 shadow-xs hover:shadow-md transition flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-100 text-blue-700 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-table-list"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200">DepEd SF2</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-base">SF2 Learner Attendance</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">DepEd School Form 2 — daily attendance summary per section for compliance and submission.</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-black text-blue-700 group-hover:underline mt-auto">
                        Generate Report <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </div>
                </a>

                {{-- Users / Enrollment Report --}}
                <a href="{{ route('admin.reports.users') }}"
                   class="group bg-white rounded-2xl border border-slate-200 hover:border-purple-300 p-6 shadow-xs hover:shadow-md transition flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 text-purple-700 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200">CSV</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-base">User Directory Report</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">Complete directory of all students, faculty, and management accounts registered in the system.</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-black text-purple-700 group-hover:underline mt-auto">
                        Generate Report <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </div>
                </a>

            </div>
        </div>

    </main>
</div>
@endsection
