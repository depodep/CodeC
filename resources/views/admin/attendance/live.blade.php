@extends('layouts.app')

@section('title', 'Live Attendance Feed - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-900"
     x-data="liveFeed()"
     x-init="startPolling()">

    {{-- ===== HEADER ===== --}}
    <header class="bg-slate-900 border-b border-slate-700/60 px-6 lg:px-10 py-4 flex items-center justify-between sticky top-0 z-20">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.attendance') }}"
               class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-base lg:text-lg font-black text-white tracking-tight">Live Attendance Feed</h1>
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-900/60 border border-emerald-700/60 text-emerald-400 text-[10px] font-black uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span x-text="isLive ? 'Live' : 'Paused'"></span>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Auto-refreshes every 5 seconds — NFC taps appear instantly</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[11px] font-bold text-slate-400" x-text="'Last updated: ' + lastUpdated"></span>
            <button @click="isLive = !isLive"
                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition cursor-pointer"
                    :class="isLive ? 'bg-amber-500/20 text-amber-400 border border-amber-700/60 hover:bg-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-700/60 hover:bg-emerald-500/30'">
                <i class="fa-solid mr-1" :class="isLive ? 'fa-pause' : 'fa-play'"></i>
                <span x-text="isLive ? 'Pause' : 'Resume'"></span>
            </button>
            <a href="{{ route('admin.attendance.export') }}"
               class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-black uppercase tracking-wider flex items-center gap-2 transition">
                <i class="fa-solid fa-file-csv text-emerald-400"></i> Export
            </a>
        </div>
    </header>

    {{-- ===== TODAY STATS BAR ===== --}}
    <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 lg:px-10 py-3 flex flex-wrap gap-6">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-slate-700 flex items-center justify-center text-slate-300 text-xs">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p class="text-[9px] font-black uppercase text-slate-500">Total Today</p>
                <p class="text-sm font-black text-white" x-text="stats.total"></p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-emerald-900/60 flex items-center justify-center text-emerald-400 text-xs">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-[9px] font-black uppercase text-slate-500">On-Time</p>
                <p class="text-sm font-black text-emerald-400" x-text="stats.ontime"></p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-amber-900/60 flex items-center justify-center text-amber-400 text-xs">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <p class="text-[9px] font-black uppercase text-slate-500">Late</p>
                <p class="text-sm font-black text-amber-400" x-text="stats.late"></p>
            </div>
        </div>
        <div class="ml-auto flex items-center gap-1.5 text-[11px] font-bold text-slate-500">
            <i class="fa-solid fa-rotate fa-spin text-slate-600" x-show="isLive"></i>
            <span x-show="isLive">Auto-refreshing…</span>
            <span x-show="!isLive" class="text-amber-500">Paused</span>
        </div>
    </div>

    {{-- ===== LIVE LOG LIST ===== --}}
    <main class="p-6 lg:p-10 max-w-[1200px] mx-auto">

        {{-- No records empty state --}}
        <template x-if="logs.length === 0">
            <div class="py-24 text-center">
                <div class="w-20 h-20 rounded-3xl bg-slate-800 text-slate-600 flex items-center justify-center text-4xl mx-auto mb-5">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
                <p class="text-base font-black text-slate-400">Waiting for NFC taps…</p>
                <p class="text-xs text-slate-600 font-semibold mt-1.5">Student attendance will appear here in real-time as they scan their cards.</p>
            </div>
        </template>

        <div class="space-y-3">
            <template x-for="(log, index) in logs" :key="log.id">
                <div class="bg-slate-800/70 border rounded-2xl px-5 py-4 flex items-center gap-4 transition-all duration-300"
                     :class="{
                        'border-emerald-700/60': log.status_upper === 'ON-TIME' || log.status_upper === 'PRESENT',
                        'border-amber-700/60':   log.status_upper === 'LATE',
                        'border-slate-700/40':   !['ON-TIME','PRESENT','LATE'].includes(log.status_upper),
                        'ring-2 ring-emerald-500/30 animate-pulse-once': index === 0 && isNew
                     }">
                    {{-- Status Indicator --}}
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm shrink-0"
                         :class="{
                            'bg-emerald-900/60 text-emerald-400': log.status_upper === 'ON-TIME' || log.status_upper === 'PRESENT',
                            'bg-amber-900/60 text-amber-400':     log.status_upper === 'LATE',
                            'bg-slate-700 text-slate-400':        !['ON-TIME','PRESENT','LATE'].includes(log.status_upper)
                         }">
                        <i class="fa-solid"
                           :class="{
                              'fa-circle-check': log.status_upper === 'ON-TIME' || log.status_upper === 'PRESENT',
                              'fa-clock':        log.status_upper === 'LATE',
                              'fa-question':     !['ON-TIME','PRESENT','LATE'].includes(log.status_upper)
                           }"></i>
                    </div>

                    {{-- Name & Section --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-black text-white text-sm truncate" x-text="log.name"></p>
                        <p class="text-[11px] font-bold text-slate-400 mt-0.5 truncate"
                           x-text="(log.id_number ? log.id_number + ' · ' : '') + (log.section || 'No section')"></p>
                    </div>

                    {{-- Time In / Out --}}
                    <div class="text-right shrink-0">
                        <p class="text-xs font-black text-slate-200" x-text="log.time_in_fmt || '—'"></p>
                        <p class="text-[10px] font-bold text-slate-500 mt-0.5"
                           x-text="log.time_out_fmt ? 'Out: ' + log.time_out_fmt : 'No time-out yet'"></p>
                    </div>

                    {{-- Status Badge --}}
                    <div class="shrink-0">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase"
                              :class="{
                                'bg-emerald-900/60 text-emerald-400 border border-emerald-700/60': log.status_upper === 'ON-TIME' || log.status_upper === 'PRESENT',
                                'bg-amber-900/60 text-amber-400 border border-amber-700/60':       log.status_upper === 'LATE',
                                'bg-slate-700 text-slate-400 border border-slate-600':             !['ON-TIME','PRESENT','LATE'].includes(log.status_upper)
                              }"
                              x-text="log.status || 'Unknown'"></span>
                    </div>

                    {{-- Date --}}
                    <p class="text-[10px] font-bold text-slate-600 shrink-0 hidden sm:block" x-text="log.date_fmt"></p>
                </div>
            </template>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
function liveFeed() {
    return {
        logs: @json($recentLogs->map(function($log) {
            $name     = trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? ''));
            $dateVal  = $log->attendance_date ?? $log->date ?? ($log->created_at ?? null);
            $statusUpper = strtoupper($log->status ?? '');
            return [
                'id'           => $log->id,
                'name'         => $name ?: 'Unknown Student',
                'id_number'    => $log->id_number ?? null,
                'section'      => $log->section ?? null,
                'status'       => $log->status ?? '',
                'status_upper' => $statusUpper,
                'time_in_fmt'  => !empty($log->time_in)  ? \Carbon\Carbon::parse($log->time_in)->format('g:i A')  : null,
                'time_out_fmt' => !empty($log->time_out) ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : null,
                'date_fmt'     => $dateVal ? \Carbon\Carbon::parse($dateVal)->format('M d') : '',
            ];
        })),

        isLive: true,
        isNew: false,
        lastUpdated: '—',
        pollingInterval: null,
        stats: { total: {{ $recentLogs->count() }}, ontime: {{ $recentLogs->filter(fn($l) => strtoupper($l->status ?? '') === 'ON-TIME')->count() }}, late: {{ $recentLogs->filter(fn($l) => strtoupper($l->status ?? '') === 'LATE')->count() }} },

        startPolling() {
            this.lastUpdated = new Date().toLocaleTimeString();
            this.pollingInterval = setInterval(() => {
                if (!this.isLive) return;
                this.fetchLatest();
            }, 5000);
        },

        fetchLatest() {
            fetch('/api/nfc/latest')
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    this.lastUpdated = new Date().toLocaleTimeString();
                    // If new tap comes in, prepend it
                    if (data.uid && (!this.logs.length || this.logs[0]?.tag_id !== data.uid)) {
                        // Re-fetch full list to get user details
                        this.reloadLogs();
                    }
                })
                .catch(() => {});
        },

        reloadLogs() {
            fetch('/admin/attendance/live', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(() => {
                    // Full page reload approach — simplest for a live feed
                    window.location.reload();
                });
        }
    };
}
</script>
@endpush
