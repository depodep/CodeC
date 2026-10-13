@extends('layouts.app')

@section('title', 'Faculty Evaluation Report - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70">

    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reports') }}"
               class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Faculty Evaluation Report</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">360-degree evaluation scores and analytics for all faculty members</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()"
                    class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-wider flex items-center gap-2 transition cursor-pointer">
                <i class="fa-solid fa-print text-slate-500"></i> Print
            </button>
            <a href="{{ route('admin.evaluations.results') }}"
               class="px-4 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs">
                <i class="fa-solid fa-chart-bar text-amber-300"></i> Full Analytics
            </a>
        </div>
    </header>

    <main class="p-6 lg:p-10 max-w-[1400px] mx-auto space-y-6">

        {{-- Summary Cards --}}
        @php
            $evalFaculty  = \App\Models\User::where('role_id', 2)->count();
            $evalSubs     = \Illuminate\Support\Facades\Schema::hasTable('evaluation_submissions')
                ? \Illuminate\Support\Facades\DB::table('evaluation_submissions')->count() : 0;
            $evalAvg      = \Illuminate\Support\Facades\Schema::hasTable('evaluation_submissions')
                ? round(\Illuminate\Support\Facades\DB::table('evaluation_submissions')->avg('average_score') ?? 0, 2) : 0;
            $activeCycle  = \Illuminate\Support\Facades\Schema::hasTable('evaluation_cycles')
                ? \Illuminate\Support\Facades\DB::table('evaluation_cycles')->where('status', 'active')->first() : null;
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Faculty Members', 'value' => $evalFaculty, 'icon' => 'fa-chalkboard-user', 'color' => 'purple'],
                ['label' => 'Total Submissions', 'value' => $evalSubs, 'icon' => 'fa-file-pen', 'color' => 'blue'],
                ['label' => 'Average Score', 'value' => number_format((float)$evalAvg, 2) . ' / 5.0', 'icon' => 'fa-star', 'color' => 'amber'],
                ['label' => 'Active Cycle', 'value' => $activeCycle ? $activeCycle->name : 'None', 'icon' => 'fa-rotate', 'color' => $activeCycle ? 'emerald' : 'slate'],
            ] as $c)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-{{ $c['color'] }}-50 text-{{ $c['color'] }}-600 border border-{{ $c['color'] }}-100 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid {{ $c['icon'] }}"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $c['label'] }}</p>
                    <p class="text-lg font-black text-slate-900 mt-0.5 truncate">{{ $c['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Quick Links --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.evaluations.results') }}"
               class="group bg-white rounded-2xl border border-slate-200 hover:border-[#c7a0a0] p-5 shadow-xs hover:shadow-md transition flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-[#8b1818] flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-chart-bar"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-black text-slate-900 text-sm">Results & Analytics</p>
                    <p class="text-[11px] text-slate-500 font-semibold truncate">Detailed per-faculty breakdown with weighted scores</p>
                </div>
                <i class="fa-solid fa-arrow-right text-slate-300 group-hover:text-[#8b1818] ml-auto transition text-xs"></i>
            </a>

            <a href="{{ route('admin.evaluations.periods') }}"
               class="group bg-white rounded-2xl border border-slate-200 hover:border-amber-300 p-5 shadow-xs hover:shadow-md transition flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-black text-slate-900 text-sm">Manage Evaluations</p>
                    <p class="text-[11px] text-slate-500 font-semibold truncate">Setup cycles, questions, and weights</p>
                </div>
                <i class="fa-solid fa-arrow-right text-slate-300 group-hover:text-amber-600 ml-auto transition text-xs"></i>
            </a>

            <a href="{{ route('admin.evaluations.history') }}"
               class="group bg-white rounded-2xl border border-slate-200 hover:border-blue-300 p-5 shadow-xs hover:shadow-md transition flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-700 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-black text-slate-900 text-sm">Evaluation History</p>
                    <p class="text-[11px] text-slate-500 font-semibold truncate">Past cycles and archived results</p>
                </div>
                <i class="fa-solid fa-arrow-right text-slate-300 group-hover:text-blue-600 ml-auto transition text-xs"></i>
            </a>
        </div>

        {{-- Faculty Summary Table --}}
        @php
            $faculty = \App\Models\User::where('role_id', 2)->orderBy('last_name')->get();
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <p class="font-black text-slate-900 text-sm">Faculty Score Summary</p>
                <span class="text-[11px] font-bold text-slate-400">{{ $faculty->count() }} members</span>
            </div>
            @if($faculty->isEmpty())
                <div class="py-16 text-center">
                    <p class="text-sm font-black text-slate-400">No faculty accounts found</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Faculty Member</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Section Handled</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Student Evals</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Peer Evals</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Avg Score</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($faculty as $member)
                            @php
                                $studentCount = \Illuminate\Support\Facades\Schema::hasTable('evaluation_submissions')
                                    ? \Illuminate\Support\Facades\DB::table('evaluation_submissions')->where('teacher_id', $member->id)->count()
                                    : 0;
                                $peerCount = \Illuminate\Support\Facades\Schema::hasTable('peer_evaluations')
                                    ? \Illuminate\Support\Facades\DB::table('peer_evaluations')->where('evaluatee_id', $member->id)->count()
                                    : 0;
                                $avgScore = \Illuminate\Support\Facades\Schema::hasTable('evaluation_submissions')
                                    ? \Illuminate\Support\Facades\DB::table('evaluation_submissions')->where('teacher_id', $member->id)->avg('average_score')
                                    : null;
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5">
                                    <p class="font-black text-slate-900">{{ $member->full_name ?? ($member->last_name . ', ' . $member->first_name) }}</p>
                                    <p class="text-[11px] font-bold text-slate-400">{{ $member->email ?? '' }}</p>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">{{ $member->section ?? '—' }}</td>
                                <td class="px-5 py-3.5 font-bold text-slate-700">{{ $studentCount }}</td>
                                <td class="px-5 py-3.5 font-bold text-slate-700">{{ $peerCount }}</td>
                                <td class="px-5 py-3.5">
                                    @if($avgScore !== null)
                                        <span class="font-black text-slate-900">{{ number_format((float)$avgScore, 2) }}</span>
                                        <span class="text-slate-400 font-bold"> / 5.0</span>
                                    @else
                                        <span class="text-slate-400 font-bold">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('admin.evaluations.results') }}?search={{ urlencode($member->first_name . ' ' . $member->last_name) }}"
                                       class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 border border-red-200 text-[#8b1818] font-black text-[10px] uppercase tracking-wide transition inline-flex items-center gap-1">
                                        <i class="fa-solid fa-chart-bar text-[9px]"></i> Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </main>
</div>
@endsection
