@extends('layouts.app')

@section('title', 'Student Attendance Records | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Attendance Records</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Personal History</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">
                    Official NFC Tap In / Tap Out history for {{ $student->first_name }} {{ $student->last_name }} (Section {{ $student->section }})
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </header>

    <main class="px-6 lg:px-10 py-6 space-y-6 max-w-[1700px] mx-auto">
        <!-- Attendance Stats Counters -->
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Attendance Rate</p>
                    <p class="text-2xl font-black {{ $attendanceRate >= 80 ? 'text-emerald-600' : 'text-amber-600' }} mt-1">{{ $attendanceRate }}%</p>
                    <span class="text-[10px] font-bold text-slate-400">Total logs evaluated</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-chart-pie"></i>
                </span>
            </div>

            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">On-Time Present</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($attendancePresent) }}</p>
                    <span class="text-[10px] font-bold text-emerald-600">On schedule taps</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-check"></i>
                </span>
            </div>

            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Late Arrivals</p>
                    <p class="text-2xl font-black text-amber-700 mt-1">{{ number_format($attendanceLate) }}</p>
                    <span class="text-[10px] font-bold text-amber-600">Recorded tardy</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock"></i>
                </span>
            </div>

            <div class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Recorded Absences</p>
                    <p class="text-2xl font-black text-rose-700 mt-1">{{ number_format($attendanceAbsent) }}</p>
                    <span class="text-[10px] font-bold text-rose-600">Missed classes</span>
                </div>
                <span class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-xmark"></i>
                </span>
            </div>
        </section>

        <!-- Attendance Logs Table -->
        <section class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b-2 border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-base font-black text-slate-900">Attendance Log History</h2>
                    <p class="text-[11px] font-bold text-slate-500">Tap entries recorded at school NFC stations</p>
                </div>
                <span class="px-3 py-1 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black text-slate-700">
                    {{ count($studentLogs) }} Log Entries
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-black uppercase tracking-wider text-slate-600">
                            <th class="py-3 px-6">Date</th>
                            <th class="py-3 px-6">Time In</th>
                            <th class="py-3 px-6">Time Out</th>
                            <th class="py-3 px-6">Status</th>
                            <th class="py-3 px-6">SMS Notification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                        @forelse($studentLogs as $log)
                            @php
                                $statusUpper = strtoupper($log->status ?? 'PRESENT');
                                $isLate = str_contains($statusUpper, 'LATE');
                                $isAbsent = str_contains($statusUpper, 'ABSENT');
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-6 font-bold text-slate-900">
                                    {{ !empty($log->attendance_date) ? date('M d, Y', strtotime($log->attendance_date)) : date('M d, Y', strtotime($log->created_at)) }}
                                </td>
                                <td class="py-3.5 px-6 font-mono font-bold text-slate-800">
                                    {{ !empty($log->time_in) ? date('h:i A', strtotime($log->time_in)) : '--:--' }}
                                </td>
                                <td class="py-3.5 px-6 font-mono font-bold text-slate-800">
                                    {{ !empty($log->time_out) ? date('h:i A', strtotime($log->time_out)) : '--:--' }}
                                </td>
                                <td class="py-3.5 px-6">
                                    @if($isAbsent)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 uppercase">
                                            Absent
                                        </span>
                                    @elseif($isLate)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">
                                            Late
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                            On-Time
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ ($log->sms_status ?? '') === 'SENT' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $log->sms_status ?? 'NOT SENT' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    <i class="fa-regular fa-clipboard text-3xl mb-2 text-slate-300 block"></i>
                                    <p class="text-xs font-black text-slate-600">No attendance logs recorded yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
@endsection
