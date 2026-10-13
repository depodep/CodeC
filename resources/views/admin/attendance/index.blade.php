@extends('layouts.app')

@section('title', 'Attendance Logs - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70">

    {{-- ===== HEADER ===== --}}
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shrink-0">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Attendance Logs</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Daily NFC tap records for all registered students</p>
            </div>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('admin.attendance.live') }}"
               class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs">
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span> Live Feed
            </a>
            <a href="{{ route('admin.attendance.record') }}"
               class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs">
                <i class="fa-solid fa-pen-to-square"></i> Override
            </a>
            <a href="{{ route('admin.attendance.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
               class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs">
                <i class="fa-solid fa-file-csv text-emerald-600"></i> Export CSV
            </a>
        </div>
    </header>

    <main class="p-6 lg:p-10 max-w-[1700px] mx-auto space-y-6">

        {{-- Flash --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total Records', 'value' => $total,   'color' => 'slate',   'icon' => 'fa-list'],
                ['label' => 'On-Time',        'value' => $present, 'color' => 'emerald', 'icon' => 'fa-circle-check'],
                ['label' => 'Late',           'value' => $late,    'color' => 'amber',   'icon' => 'fa-clock'],
                ['label' => 'Absent/Other',   'value' => $absent,  'color' => 'rose',    'icon' => 'fa-circle-xmark'],
            ] as $card)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-{{ $card['color'] }}-50 border border-{{ $card['color'] }}-100 text-{{ $card['color'] }}-600 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid {{ $card['icon'] }}"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $card['label'] }}</p>
                    <p class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($card['value']) }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('admin.attendance') }}"
              class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Date</label>
                <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}"
                       class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
            </div>
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Section</label>
                <select name="section" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                    <option value="">All Sections</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec }}" @selected(request('section') === $sec)>{{ $sec }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1 min-w-[120px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Status</label>
                <select name="status" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                    <option value="">All Status</option>
                    <option value="ON-TIME" @selected(request('status') === 'ON-TIME')>On-Time</option>
                    <option value="LATE"    @selected(request('status') === 'LATE')>Late</option>
                </select>
            </div>
            <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or ID number..."
                       class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700 placeholder:text-slate-400">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition shadow-xs cursor-pointer">
                    <i class="fa-solid fa-search mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['date', 'section', 'status', 'search']))
                    <a href="{{ route('admin.attendance') }}"
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition shadow-xs inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i> Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            @if($logs->isEmpty())
                <div class="py-20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto mb-4">
                        <i class="fa-solid fa-clipboard"></i>
                    </div>
                    <p class="text-sm font-black text-slate-500">No attendance records found</p>
                    <p class="text-xs text-slate-400 font-semibold mt-1">
                        {{ request()->hasAny(['date','section','status','search']) ? 'Try adjusting your filters.' : 'NFC taps will appear here once students scan in.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">#</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Student</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">ID Number</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Section</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Date</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Time In</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Time Out</th>
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($logs as $i => $log)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5 font-bold text-slate-400">
                                    {{ ($logs->currentPage() - 1) * $logs->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-5 py-3.5 font-black text-slate-900">
                                    {{ trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5 font-mono font-bold text-slate-600">
                                    {{ $log->id_number ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">{{ $log->section ?? '—' }}</td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">
                                    {{ isset($log->attendance_date) ? \Carbon\Carbon::parse($log->attendance_date)->format('M d, Y') : (isset($log->date) ? \Carbon\Carbon::parse($log->date)->format('M d, Y') : '—') }}
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">
                                    {{ isset($log->time_in) && $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i A') : '—' }}
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">
                                    {{ isset($log->time_out) && $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    @php $status = strtoupper($log->status ?? ''); @endphp
                                    @if($status === 'ON-TIME' || $status === 'PRESENT')
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-black text-[10px] uppercase">On-Time</span>
                                    @elseif($status === 'LATE')
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 font-black text-[10px] uppercase">Late</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 font-black text-[10px] uppercase">{{ $status ?: 'Unknown' }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($logs->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $logs->links() }}
                </div>
                @endif
            @endif
        </div>

    </main>
</div>
@endsection
