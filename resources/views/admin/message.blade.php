@extends('layouts.app')

@section('title', 'SMS Messages | SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70"
     x-data="smsAdminApp()"
     x-init="syncMessage(); pingGateway(); selectAllAbsenceSections()">

    {{-- ================================================================
         PAGE HEADER — matches admin dashboard / sections pattern exactly
         ================================================================ --}}
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-comments"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">SMS Messages</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">A.Y. {{ $activeSchoolYear ?? date('Y').'-'.(date('Y')+1) }}</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">View and manage outbound SMS messages, absence alerts, and dispatch analytics</p>
            </div>
        </div>

    </header>

    {{-- ================================================================
         MAIN CONTENT
         ================================================================ --}}
    <main class="p-6 lg:p-10 w-full space-y-6 flex-1">

        {{-- ================================================================
             TOP GRID: METRICS (LEFT 50%) + ABSENT TEMPLATE (RIGHT 50%)
             ================================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

            {{-- LEFT: METRICS GRID (2 columns) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 gap-4">

                {{-- Sent Today --}}
                <div class="p-5 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Sent Today</span>
                            <h2 class="text-4xl font-black text-emerald-600 tracking-tight mt-1">{{ number_format($totalSentToday ?? 0) }}</h2>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 flex items-center justify-center text-lg shadow-xs shrink-0">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400 font-semibold">Total outbound dispatches</span>
                </div>

                {{-- Absent Students --}}
                <div class="p-5 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Absent Students</span>
                            <h2 class="text-4xl font-black text-rose-600 tracking-tight mt-1">{{ number_format($absentStudentsCount ?? 0) }}</h2>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-rose-50 border-2 border-rose-200 text-rose-600 flex items-center justify-center text-lg shadow-xs shrink-0">
                            <i class="fa-solid fa-user-xmark"></i>
                        </div>
                    </div>
                    <span class="text-xs text-slate-400 font-semibold">Recorded absent today</span>
                </div>

                {{-- Successful --}}
                <div class="p-4 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-2">
                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Successful</span>
                    <h3 class="text-3xl font-black text-emerald-600 tracking-tight">{{ number_format($successSentToday ?? 0) }}</h3>
                    <span class="text-xs text-slate-400 font-semibold">Delivered</span>
                </div>

                {{-- Failed --}}
                <div class="p-4 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between space-y-2">
                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Failed</span>
                    <h3 class="text-3xl font-black text-red-600 tracking-tight">{{ number_format($failedSentToday ?? 0) }}</h3>
                    <span class="text-xs text-slate-400 font-semibold">Errors / Cooldown</span>
                </div>

                {{-- Pending (spans 2 cols on desktop) --}}
                <div class="col-span-2 p-4 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Pending Gateway</span>
                        <span class="text-xs text-slate-400 font-semibold">In dispatch queue</span>
                    </div>
                    <h3 class="text-3xl font-black text-amber-500 tracking-tight">{{ number_format($pendingSentToday ?? 0) }}</h3>
                </div>

            </div>

            {{-- RIGHT: ABSENT TEMPLATE CARD --}}
            <div class="bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col p-4 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-red-100 text-red-700 border border-red-300 uppercase flex items-center gap-1">
                            <i class="fa-solid fa-bell"></i> Absent Trigger
                        </span>
                        <h2 class="text-sm font-black text-slate-800">SMS Absence Template</h2>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                    <input type="date" x-model="absenceDate" @change="previewAbsenceDate()"
                           max="{{ \Carbon\Carbon::today()->toDateString() }}"
                           class="px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700"
                           aria-label="Absence preview date">
                    <button @click="templateOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-bold hover:bg-slate-700 transition-colors cursor-pointer">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Custom Absent Message
                    </button>
                    </div>
                </div>

                <div class="flex-1 p-5 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-xs leading-relaxed italic relative flex items-center">
                    <div>
                        <i class="fa-solid fa-quote-left text-slate-400 mr-1.5"></i>
                        <span id="currentTemplateText">{{ $defaultTemplate }}</span>
                        <i class="fa-solid fa-quote-right text-slate-400 ml-1.5"></i>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 text-slate-400 text-[11px] flex items-start gap-1.5">
                    <i class="fa-solid fa-circle-info text-blue-400 mt-0.5 shrink-0"></i>
                    <span>Auto-replace placeholders: <code class="bg-slate-100 px-1 rounded text-slate-600">{student_name}</code>, <code class="bg-slate-100 px-1 rounded text-slate-600">{date}</code>, <code class="bg-slate-100 px-1 rounded text-slate-600">{time}</code>, <code class="bg-slate-100 px-1 rounded text-slate-600">{section}</code>, <code class="bg-slate-100 px-1 rounded text-slate-600">{school_name}</code></span>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-3 space-y-2">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <span class="text-[11px] font-black uppercase tracking-wider text-rose-700">
                            Absence preview for <span x-text="absenceDate"></span>
                        </span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-100 text-rose-700 border border-rose-200">
                                <span x-text="absenceDispatchCount"></span> absent
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-blue-100 text-blue-700 border border-blue-200"
                                  x-show="absenceAlreadySentCount > 0">
                                <span x-text="absenceAlreadySentCount"></span> already sent
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200"
                                  x-show="absenceRemainingCount > 0">
                                <span x-text="absenceRemainingCount"></span> remaining
                            </span>
                        </div>
                    </div>
                    <button type="button" @click="sendAbsenceNotifications()"
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-[#590d0d] text-amber-300 border border-amber-400 text-xs font-black hover:bg-[#43090c] transition-colors cursor-pointer shadow-xs">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Absence Alerts (<span x-text="absenceRemainingCount"></span> to notify)
                    </button>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="ctx in absenceContexts.filter(c => c.type === 'advisory' && c.absent_count > 0)" :key="ctx.id">
                            <span class="px-2 py-0.5 rounded-lg bg-white border border-rose-100 text-[10px] font-bold text-slate-700 inline-flex items-center gap-1">
                                <span x-text="ctx.name"></span>:
                                <strong class="text-rose-700" x-text="ctx.absent_count"></strong>
                                <span class="text-blue-600 font-semibold" x-show="ctx.sent_count > 0" x-text="'(' + ctx.sent_count + ' sent)'"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MESSAGE HISTORY CARD WITH INTEGRATED FILTERS
             ================================================================ --}}
        <div class="bg-white rounded-2xl border-2 border-slate-200 shadow-xs overflow-hidden">

            {{-- Card Header --}}
            <div class="bg-[#590d0d] border-b-4 border-amber-400 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-black text-white flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-400"></i>
                        Message History
                    </h2>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('admin.message') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-colors">
                        <i class="fa-solid fa-rotate-right"></i> Refresh
                    </a>

                    {{-- Unified Ping Gateway Button with Inline Status --}}
                    <button @click="pingGateway()"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer border"
                            :class="{
                                'bg-amber-400/20 hover:bg-amber-400/30 text-amber-300 border-amber-400/40': pingState === 'idle',
                                'bg-amber-400/30 text-amber-200 border-amber-400/50 cursor-wait': pingState === 'loading',
                                'bg-emerald-600 text-white border-emerald-500 shadow-sm': pingState === 'online',
                                'bg-rose-700 text-white border-rose-600 shadow-sm': pingState === 'offline'
                            }"
                            :disabled="pingState === 'loading'">
                        <i class="fa-solid"
                           :class="{
                               'fa-satellite-dish': pingState === 'idle',
                               'fa-circle-notch fa-spin': pingState === 'loading',
                               'fa-circle-check': pingState === 'online',
                               'fa-circle-xmark': pingState === 'offline'
                           }"></i>
                        <span x-text="pingState === 'idle' ? 'Ping Gateway' :
                                      pingState === 'loading' ? 'Pinging Gateway...' :
                                      pingState === 'online' ? 'Online (' + pingLatency + 'ms)' :
                                      'Gateway Offline'"></span>
                    </button>

                    <button @click="composeOpen = true"
                            :disabled="pingState === 'offline'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-400 text-amber-950 text-xs font-black hover:bg-amber-300 transition-colors cursor-pointer shadow-xs disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane"></i> Send SMS
                    </button>
                </div>
            </div>

            {{-- Compact feedback stays with the action it describes. --}}
            @if(session('success') || session('error'))
                <div class="mx-5 mt-3 px-3 py-2 rounded-lg border text-xs font-bold flex items-center justify-between {{ session('success') ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800' }}">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid {{ session('success') ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-red-600' }}"></i>
                        {{ session('success') ?? session('error') }}
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100 cursor-pointer" aria-label="Dismiss notification">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            {{-- Integrated Search & History Filters (Above Table) --}}
            <div class="p-5 border-b-2 border-slate-100 bg-slate-50/50">
                <div class="flex flex-wrap items-end gap-3">
                    <form method="GET" action="{{ route('admin.message') }}" id="filterForm" class="flex flex-wrap items-end gap-3 flex-1 min-w-0">
                        {{-- Student / Recipient --}}
                        <div class="min-w-[220px] flex-1">
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Student / Recipient</label>
                            <div class="flex items-center border-2 border-slate-200 rounded-xl overflow-hidden focus-within:border-[#590d0d] bg-white transition-colors">
                                <span class="px-3 text-slate-400"><i class="fa-solid fa-user text-xs"></i></span>
                                <input type="text" name="recipient"
                                       class="flex-1 py-2 pr-3 text-xs text-slate-800 bg-transparent outline-none placeholder-slate-400"
                                       placeholder="Search name or phone..."
                                       value="{{ request('recipient') }}">
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="min-w-[170px] flex-1">
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
                            <select name="status"
                                    class="w-full py-2 px-3 text-xs text-slate-800 bg-white border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>

                        {{-- From Date --}}
                        <div class="min-w-[170px] flex-1">
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">From Date</label>
                            <input type="date" name="from_date"
                                   class="w-full py-2 px-3 text-xs text-slate-800 bg-white border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none"
                                   value="{{ request('from_date') }}">
                        </div>

                        {{-- To Date --}}
                        <div class="min-w-[170px] flex-1">
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">To Date</label>
                            <input type="date" name="to_date"
                                   class="w-full py-2 px-3 text-xs text-slate-800 bg-white border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none"
                                   value="{{ request('to_date') }}">
                        </div>

                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    </form>

                    <div class="flex flex-wrap items-center gap-2.5 ml-auto">
                        <button type="submit" form="filterForm"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition-colors cursor-pointer shadow-xs">
                            <i class="fa-solid fa-magnifying-glass"></i> Apply Filters
                        </button>
                        @if(($pendingSentToday ?? 0) > 0)
                            <form method="POST" action="{{ route('admin.sms.resend-pending-today') }}"
                                  onsubmit="return confirm('Resend all pending SMS messages from today?');" class="inline-block">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 text-amber-950 text-xs font-black hover:bg-amber-400 transition-colors cursor-pointer shadow-xs">
                                    <i class="fa-solid fa-rotate-right"></i> Resend Pending Today ({{ $pendingSentToday }})
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.message') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-black hover:bg-slate-300 transition-colors">
                            <i class="fa-solid fa-xmark"></i> Clear
                        </a>
                    </div>
                </div>
            </div>

            {{-- Data Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-100">
                            <th class="w-12 px-3 py-3.5 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3.5 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Recipient</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Message</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Sender</th>
                            <th class="px-4 py-3.5 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($messages as $msg)
                            @php
                                $recipientDisplayName = $msg->recipient_name ?: ($msg->student ? ($msg->student->full_name ?: trim(($msg->student->first_name ?? '') . ' ' . ($msg->student->last_name ?? ''))) : 'Parent/Contact');
                                $recipientNumber = $msg->contact_number ?: ($msg->student ? ($msg->student->parent_phone_number ?: ($msg->student->phone_number ?: $msg->student->contact_person_contact)) : null);
                                $recipientNumber = trim((string) $recipientNumber);
                                $recipientNumberDisplay = $recipientNumber !== '' ? $recipientNumber : 'No phone number';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <form id="resendPendingForm" method="POST" action="{{ route('admin.sms.resend-pending-today') }}" class="inline-block">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button type="button" @click="resendPendingOpen = true"
                                    <div class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ $msg->created_at->format('h:i A') }}</div>
                                </td>

                                {{-- Recipient --}}
                                <td class="px-4 py-4">
                                    <div class="text-xs font-black text-slate-800">{{ $recipientDisplayName }}</div>
                                    <div class="text-[11px] text-slate-400 font-semibold mt-0.5 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-slate-300 text-[10px]"></i>
                                        <span>{{ $recipientNumberDisplay }}</span>
                                    </div>
                                </td>

                                {{-- Message (truncated) --}}
                                <td class="px-4 py-4 max-w-xs">
                                    <div class="text-xs text-slate-600 truncate" title="{{ $msg->message }}">
                                        {{ \Illuminate\Support\Str::limit($msg->message, 70, '...') }}
                                    </div>
                                </td>

                                {{-- Sender --}}
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200">
                                        <i class="fa-solid fa-chalkboard-user text-slate-400 text-[10px]"></i>
                                        {{ $msg->sender_display_name }}
                                    </span>
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-4 py-4 text-center">
                                    @php $st = strtolower($msg->status); @endphp
                                    @if($st === 'delivered')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i> {{ $msg->status }}
                                        </span>
                                    @elseif($st === 'sent')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                                            <i class="fa-solid fa-paper-plane text-[10px]"></i> Accepted
                                        </span>
                                    @elseif($st === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                                            <i class="fa-solid fa-clock text-[10px]"></i> Queued
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-red-50 text-red-700 border border-red-200 uppercase">
                                            <i class="fa-solid fa-circle-xmark text-[10px]"></i> {{ $msg->status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-center">
                                    <button type="button"
                                            @click="loadDetails({{ $msg->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors cursor-pointer border border-slate-200">
                                        <i class="fa-solid fa-eye text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <i class="fa-solid fa-inbox text-5xl text-slate-200"></i>
                                        <div>
                                            <p class="text-sm font-black text-slate-500">No SMS records found</p>
                                            <p class="text-xs text-slate-400 mt-1">Adjust filters or compose a new SMS message.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($messages->hasPages())
                <div class="px-6 py-4 border-t-2 border-slate-100 bg-slate-50/50 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 flex-wrap text-xs text-slate-500 font-bold">
                        <label for="perPage" class="text-slate-600">Rows per page</label>
                        <select id="perPage" name="per_page" form="filterForm" onchange="this.form.submit()"
                                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 shadow-xs focus:border-[#590d0d] outline-none">
                            @foreach([10, 15, 30, 50] as $option)
                                <option value="{{ $option }}" {{ (string) request('per_page', 10) === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                        <span>
                            Showing {{ $messages->firstItem() }} to {{ $messages->lastItem() }} of {{ $messages->total() }} records
                        </span>
                    </div>

                    <div class="flex items-center gap-1 flex-wrap justify-end">
                        {{-- Prev --}}
                        @if($messages->onFirstPage())
                            <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed">
                                <i class="fa-solid fa-chevron-left"></i>
                            </span>
                        @else
                            <a href="{{ $messages->previousPageUrl() }}"
                               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition-colors">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach($messages->getUrlRange(max(1, $messages->currentPage()-2), min($messages->lastPage(), $messages->currentPage()+2)) as $page => $url)
                            @if($page == $messages->currentPage())
                                <span class="px-3 py-1.5 rounded-lg bg-[#590d0d] text-amber-300 text-xs font-black">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition-colors">{{ $page }}</a>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if($messages->hasMorePages())
                            <a href="{{ $messages->nextPageUrl() }}"
                               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition-colors">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed">
                                <i class="fa-solid fa-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                </div>
            @endif

        </div>

    </main>

    {{-- ================================================================
         MODAL: EDIT ABSENT TEMPLATE
         ================================================================ --}}
    <div x-show="templateOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-8 pb-8 px-4 bg-black/60 overflow-y-auto"
         @click.self="templateOpen = false"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden self-start"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Modal Header --}}
            <div class="bg-[#590d0d] rounded-t-2xl px-6 py-4 border-b-4 border-amber-400 flex items-center justify-between">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-400"></i> Edit Absence SMS Template
                </h3>
                <button @click="templateOpen = false" class="text-white/70 hover:text-white transition-colors cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.sms.update-template') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <p class="text-xs text-slate-500 font-semibold">
                        This template is automatically dispatched to parents/guardians when a student is recorded absent during morning gate scans.
                    </p>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Custom Message Text</label>
                        <textarea name="absence_sms_template" id="templateModalInput" rows="4"
                                  class="w-full px-3 py-2.5 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none resize-none"
                                  maxlength="500" required>{{ $defaultTemplate }}</textarea>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[11px] font-black text-slate-600 uppercase tracking-wider mb-2">Available Placeholders:</div>
                        <ul class="space-y-1 text-xs text-slate-500">
                            <li><code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-700">{student_name}</code> — Student's Full Name</li>
                            <li><code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-700">{date}</code> — Current Date</li>
                            <li><code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-700">{time}</code> — Scan Timestamp</li>
                            <li><code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-700">{section}</code> — Student Section</li>
                            <li><code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-700">{school_name}</code> — Southern Isabela Academy</li>
                        </ul>
                    </div>
                </div>

                <div class="px-6 pb-6 flex items-center justify-end gap-3">
                    <button type="button" @click="templateOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-black hover:bg-slate-200 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition-colors cursor-pointer shadow-xs">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================================================
         MODAL: COMPOSE / SEND SMS
         ================================================================ --}}
    <div x-show="composeOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-8 pb-8 px-4 bg-black/60 overflow-y-auto"
         @click.self="composeOpen = false"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden self-start"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Modal Header --}}
            <div class="bg-[#590d0d] rounded-t-2xl px-6 py-4 border-b-4 border-amber-400 flex items-center justify-between">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-amber-400"></i> Admin SMS Dispatch Console
                </h3>
                <button @click="composeOpen = false" class="text-white/70 hover:text-white transition-colors cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="sendSmsForm" @submit.prevent="submitSms($event)">
                @csrf
                <div class="p-6 space-y-5 max-h-[78vh] overflow-y-auto">

                    {{-- Recipient Mode --}}
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Recipient Target</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                            @foreach([
                                ['all_parents', 'All Parents'],
                                ['selected_students', 'Selected Students'],
                                ['specific_student', 'Specific Student'],
                                ['all_teachers', 'All Teachers'],
                                ['specific_teacher', 'Specific Teacher'],
                                ['custom', 'Custom Number'],
                            ] as [$val, $label])
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border-2 cursor-pointer transition-colors"
                                   :class="recipientMode === '{{ $val }}' ? 'border-[#590d0d] bg-red-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                                <input type="radio" name="recipient_mode" value="{{ $val }}"
                                       x-model="recipientMode"
                                       class="hidden">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors"
                                     :class="recipientMode === '{{ $val }}' ? 'border-[#590d0d]' : 'border-slate-300'">
                                    <div class="w-2 h-2 rounded-full bg-[#590d0d] transition-transform"
                                         :class="recipientMode === '{{ $val }}' ? 'scale-100' : 'scale-0'"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-700 leading-tight">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Selected Students Dropdown Picker --}}
                    <div x-show="recipientMode === 'selected_students'" x-transition x-cloak class="relative space-y-2">
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider">
                            Select Student Recipients
                        </label>

                        {{-- Toggle Button --}}
                        <button type="button"
                                @click="studentDropdownOpen = !studentDropdownOpen"
                                class="w-full flex items-center justify-between px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl hover:border-[#590d0d] transition-colors cursor-pointer text-left focus:outline-none">
                            <div class="flex items-center gap-2.5">
                                <i class="fa-solid fa-users text-[#590d0d]"></i>
                                <span class="text-xs font-bold text-slate-700">Choose Students to Receive SMS</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-[#590d0d] text-amber-300">
                                        <span x-text="selectedCount"></span> selected
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-400">
                                <span class="text-[11px] font-medium hidden sm:inline"
                                      x-text="studentDropdownOpen ? 'Click to collapse' : 'Click to expand'"></span>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
                                   :class="studentDropdownOpen ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        {{-- Inline Collapsible Panel (no absolute positioning) --}}
                        <div x-show="studentDropdownOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-y-95"
                             x-transition:enter-end="opacity-100 scale-y-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-y-100"
                             x-transition:leave-end="opacity-0 scale-y-95"
                             class="absolute left-0 right-0 z-30 p-3.5 rounded-2xl bg-white border-2 border-slate-200 shadow-2xl space-y-3">

                            <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
                                <div class="text-[11px] font-bold text-slate-500">
                                    Total available: <span class="font-black text-slate-700">{{ count($students) }} students</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="selectAllStudentsBtn"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-black hover:bg-slate-200 transition-colors cursor-pointer">
                                        <i class="fa-solid fa-check-double mr-1 text-emerald-600"></i> Select All
                                    </button>
                                    <button type="button" id="deselectAllStudentsBtn"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-black hover:bg-slate-200 transition-colors cursor-pointer">
                                        <i class="fa-solid fa-xmark mr-1 text-red-500"></i> Clear
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <select id="studentGradeFilter"
                                        class="py-1.5 px-2.5 text-xs text-slate-700 bg-slate-50 border border-slate-200 rounded-lg outline-none">
                                    <option value="">All grade levels</option>
                                    @foreach($students->pluck('grade_level')->filter()->unique()->sort()->values() as $grade)
                                        <option value="{{ strtolower((string) $grade) }}">Grade {{ $grade }}</option>
                                    @endforeach
                                </select>
                                <select id="studentSectionFilter"
                                        class="py-1.5 px-2.5 text-xs text-slate-700 bg-slate-50 border border-slate-200 rounded-lg outline-none">
                                    <option value="">All sections</option>
                                    @foreach($students->pluck('section')->filter()->unique()->sort()->values() as $section)
                                        <option value="{{ strtolower($section) }}">{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Search --}}
                            <div class="flex items-center border-2 border-slate-200 rounded-xl overflow-hidden focus-within:border-[#590d0d] bg-slate-50 transition-colors">
                                <span class="px-3 text-slate-400"><i class="fa-solid fa-search text-xs"></i></span>
                                <input type="text" id="searchStudentsList"
                                       class="flex-1 py-1.5 pr-3 text-xs text-slate-800 bg-transparent outline-none placeholder-slate-400"
                                       placeholder="Search student name or section...">
                            </div>

                            {{-- Scrollable Checkbox List --}}
                            <div class="max-h-52 overflow-y-auto border border-slate-100 rounded-xl bg-white divide-y divide-slate-100 shadow-inner" id="studentsCheckboxList">
                                @foreach($students as $st)
                                    @php
                                        $contact = $st->parent_phone_number ?: ($st->phone_number ?: $st->contact_person_contact);
                                        $hasContact = !empty($contact);
                                    @endphp
                                    <div class="student-select-item flex items-center justify-between px-3.5 py-2 {{ !$hasContact ? 'bg-red-50/40' : 'hover:bg-slate-50' }} transition-colors"
                                         data-name="{{ strtolower($st->full_name) }}"
                                         data-section="{{ strtolower($st->section ?? '') }}"
                                         data-grade="{{ strtolower($st->grade_level ?? '') }}">
                                        <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0 {{ !$hasContact ? 'opacity-60 cursor-not-allowed' : '' }}">
                                            <input class="student-chk w-3.5 h-3.5 rounded accent-[#590d0d] shrink-0"
                                                   type="checkbox"
                                                   name="student_ids[]"
                                                   value="{{ $st->id }}"
                                                   id="chkStudent_{{ $st->id }}"
                                                   {{ !$hasContact ? 'disabled' : '' }}>
                                            <span class="text-xs font-bold text-slate-800 truncate">{{ $st->last_name }}, {{ $st->first_name }}</span>
                                            @if(!empty($st->section))
                                                <span class="px-2 py-0.5 rounded-md text-[10px] bg-slate-100 text-slate-600 font-bold border border-slate-200 shrink-0">{{ $st->section }}</span>
                                            @endif
                                        </label>
                                        <div class="shrink-0 ml-3">
                                            @if($hasContact)
                                                <span class="text-[11px] font-semibold text-slate-500 flex items-center gap-1.5">
                                                    <i class="fa-solid fa-phone text-emerald-500 text-[10px]"></i> {{ $contact }}
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-600 border border-red-200 flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> No contact
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>


                    {{-- Specific Student Panel --}}
                    <div x-show="recipientMode === 'specific_student'" x-transition x-cloak>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Choose Student</label>
                        <select name="student_id" id="specificStudentSelect"
                                class="w-full py-2.5 px-3 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                            <option value="">-- Choose student --</option>
                            @foreach($students as $st)
                                @php $contact = $st->parent_phone_number ?: ($st->phone_number ?: $st->contact_person_contact); @endphp
                                <option value="{{ $st->id }}"
                                        data-name="{{ $st->full_name }}"
                                        data-section="{{ $st->section ?: 'Unassigned' }}"
                                        data-parent="{{ $st->parent_name ?: 'Parent/Guardian' }}"
                                        data-contact="{{ $contact ?: 'No contact registered' }}"
                                        data-hascontact="{{ !empty($contact) ? '1' : '0' }}">
                                    {{ $st->last_name }}, {{ $st->first_name }} ({{ $st->section ?: 'Section N/A' }})
                                </option>
                            @endforeach
                        </select>

                        {{-- Student Info Preview --}}
                        <div id="specificStudentInfoCard" class="mt-3 p-4 rounded-xl bg-slate-50 border-2 border-slate-200 hidden">
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-400 font-semibold block">Student Name</span>
                                    <strong class="text-slate-800 font-black" id="previewStudentName">-</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block">Section</span>
                                    <strong class="text-slate-800 font-black" id="previewStudentSection">-</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block">Parent/Guardian</span>
                                    <strong class="text-slate-800 font-black" id="previewParentName">-</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block">Contact Number</span>
                                    <strong class="text-[#590d0d] font-black" id="previewContactNumber">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Specific Teacher Panel --}}
                    <div x-show="recipientMode === 'specific_teacher'" x-transition x-cloak>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Choose Teacher / Faculty Member</label>
                        <select name="teacher_id" id="specificTeacherSelect"
                                class="w-full py-2.5 px-3 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                            <option value="">-- Choose teacher --</option>
                            @foreach($teachers as $tch)
                                <option value="{{ $tch->id }}" data-contact="{{ $tch->phone_number }}">
                                    {{ $tch->last_name }}, {{ $tch->first_name }} ({{ $tch->phone_number ?: 'No phone number' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Custom Number Panel --}}
                    <div x-show="recipientMode === 'custom'" x-transition x-cloak>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Custom Contact Number</label>
                                <input type="text" name="custom_number" id="customNumberInput"
                                       class="w-full py-2.5 px-3 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none"
                                       placeholder="09171234567 or +639171234567">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Recipient Name (Optional)</label>
                                <input type="text" name="custom_name"
                                       class="w-full py-2.5 px-3 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none"
                                       placeholder="e.g. Guidance Counselor">
                            </div>
                        </div>
                    </div>

                    {{-- Message Template --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Message Template</label>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-500 border border-slate-200 font-bold">Optional</span>
                        </div>
                        <select id="templateSelector" x-model="selectedTemplate" @change="applyTemplate($event.target.value)"
                                class="w-full py-2.5 px-3 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                            <option value="">-- Select a template to autofill --</option>
                            <option value="absent_today">Absent Today Notice</option>
                            <option value="reminder">Activity / Deadline Reminder</option>
                            <option value="meeting">Parent-Teacher Meeting Invitation</option>
                            <option value="exam">Upcoming Examination Advisory</option>
                            <option value="school_advisory">School Administration Advisory</option>
                        </select>
                    </div>

                    {{-- Message Textarea --}}
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Message Content</label>
                        <textarea name="message" id="messageTextarea" rows="4"
                                  class="w-full px-3 py-2.5 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none resize-none"
                                  maxlength="1000"
                                  placeholder="Type your message here..."
                                  x-model="messageText"
                                  @input="handleMessageInput()"
                                  required></textarea>

                        {{-- Char/SMS counter --}}
                        <div class="flex items-center justify-between mt-1.5 text-[11px] text-slate-400">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 font-bold">
                                    Characters: <strong class="text-slate-700" x-text="charCount">0</strong>/1000
                                </span>
                                <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 font-bold">
                                    Estimated SMS: <strong class="text-slate-700" x-text="smsCount">0</strong>
                                </span>
                            </div>
                            <span>GSM 160 chars per SMS segment</span>
                        </div>
                    </div>

                    {{-- Signature Toggle --}}
                    <div class="p-4 rounded-xl bg-slate-50 border-2 border-slate-200 space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="include_signature" id="signatureToggle" value="1"
                                   x-model="includeSignature"
                                   @change="syncMessage()"
                                   class="w-4 h-4 rounded accent-[#590d0d]">
                            <span class="text-xs font-black text-slate-700">Append Sender Signature Footer</span>
                        </label>

                        <div id="signatureDetailsContainer" x-show="includeSignature" x-transition class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">Sender Name</label>
                                <input type="text" name="signature_teacher"
                                       class="w-full py-1.5 px-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-lg focus:border-[#590d0d] outline-none"
                                       x-model="signatureTeacher"
                                       @input="updateFooter()"
                                       value="{{ $admin->full_name ?: 'Administration Office' }}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">Designation / Department</label>
                                <input type="text" name="signature_section"
                                       class="w-full py-1.5 px-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-lg focus:border-[#590d0d] outline-none"
                                       x-model="signatureSection"
                                       @input="updateFooter()"
                                       value="Office of the Principal">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">School Name</label>
                                <input type="text" name="signature_school"
                                       class="w-full py-1.5 px-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-lg focus:border-[#590d0d] outline-none"
                                       x-model="signatureSchool"
                                       @input="updateFooter()"
                                       value="Southern Isabela Academy">
                            </div>
                        </div>
                        <p x-show="includeSignature" class="text-[10px] text-slate-400">
                            The footer is included in the message box and will be sent exactly as previewed.
                        </p>
                    </div>

                    {{-- Alert Box --}}
                    <div id="modalAlertBox"></div>

                </div>

                {{-- Footer --}}
                <div class="px-6 pb-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" @click="composeOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-black hover:bg-slate-200 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="submitSendSmsBtn"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition-colors cursor-pointer shadow-xs">
                        <i class="fa-solid fa-paper-plane"></i> Send Outbound SMS
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================================================
         MODAL: ABSENCE NOTIFICATION CONFIRMATION / DISPATCH STATUS
         ================================================================ --}}
    <div x-show="absenceDispatchOpen"
         x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/70"
         @click.self="!absenceDispatchLoading && (absenceDispatchOpen = false)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[88vh]">
            <div class="bg-[#590d0d] border-b-4 border-amber-400 px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-300"></i>
                    <span x-show="!absenceDispatchLoading && !absenceDispatchResult">Confirm Absence Notifications</span>
                    <span x-show="absenceDispatchLoading" x-text="absenceDispatchPhase"></span>
                    <span x-show="absenceDispatchResult">Absence Dispatch Result</span>
                </h3>
                <button type="button" @click="absenceDispatchOpen = false"
                        :disabled="absenceDispatchLoading"
                        class="text-white/70 hover:text-white disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <template x-if="!absenceDispatchLoading && !absenceDispatchResult">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
                            <p class="text-xs text-slate-700">
                                Send notifications for <strong class="text-slate-900" x-text="absenceDate"></strong>
                            </p>
                            {{-- Target Tabs --}}
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                                <button type="button" @click="absenceFilterTab = 'all'"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black transition cursor-pointer"
                                        :class="absenceFilterTab === 'all' ? 'bg-[#590d0d] text-amber-300' : 'text-slate-600 hover:text-slate-900'">
                                    All (<span x-text="absenceContexts.length"></span>)
                                </button>
                                <button type="button" @click="absenceFilterTab = 'advisory'"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black transition cursor-pointer"
                                        :class="absenceFilterTab === 'advisory' ? 'bg-[#590d0d] text-amber-300' : 'text-slate-600 hover:text-slate-900'">
                                    Advisory (<span x-text="absenceContexts.filter(c => c.type === 'advisory').length"></span>)
                                </button>
                                <button type="button" @click="absenceFilterTab = 'subject'"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black transition cursor-pointer"
                                        :class="absenceFilterTab === 'subject' ? 'bg-[#590d0d] text-amber-300' : 'text-slate-600 hover:text-slate-900'">
                                    Subjects (<span x-text="absenceContexts.filter(c => c.type === 'subject').length"></span>)
                                </button>
                            </div>
                        </div>

                        {{-- Selection List Container --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-black uppercase tracking-wider text-slate-600">Select Classes to Notify</span>
                                <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                    <input type="checkbox"
                                           class="rounded accent-[#590d0d]"
                                           :checked="absenceContextsSelected()"
                                           @change="$event.target.checked ? selectAllAbsenceContexts() : deselectAllAbsenceContexts()">
                                    Select all in view
                                </label>
                            </div>

                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                <template x-for="ctx in visibleAbsenceContexts" :key="ctx.id">
                                    <label class="flex items-center justify-between gap-3 p-2.5 rounded-xl border bg-white cursor-pointer transition"
                                           :class="selectedAbsenceContextIds.includes(ctx.id) ? 'border-amber-400 bg-amber-50/50' : 'border-slate-200 hover:border-slate-300'">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <input type="checkbox"
                                                   class="rounded accent-[#590d0d] shrink-0"
                                                   :value="ctx.id"
                                                   x-model="selectedAbsenceContextIds"
                                                   @change="updateAbsenceSelection()">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="fa-solid text-[10px]"
                                                       :class="ctx.type === 'advisory' ? 'fa-users-viewfinder text-amber-700' : 'fa-book-open text-blue-600'"></i>
                                                    <span class="text-xs font-black text-slate-800 truncate" x-text="ctx.label"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-medium">
                                                    <span x-text="ctx.student_count + ' students'"></span>
                                                    <span x-show="ctx.teacher" x-text="' • ' + ctx.teacher"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0 flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black"
                                                  :class="ctx.absent_count > 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'"
                                                  x-text="ctx.absent_count + ' absent'"></span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-700 border border-blue-200"
                                                  x-show="ctx.sent_count > 0"
                                                  x-text="ctx.sent_count + ' sent'"></span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                  x-show="ctx.absent_count > 0 && ctx.remaining_count === 0">
                                                Done
                                            </span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Summary Bar --}}
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3.5 space-y-2">
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="bg-white/80 p-2 rounded-lg border border-amber-200">
                                    <div class="text-[10px] font-black uppercase text-rose-700">Total Absent</div>
                                    <div class="text-lg font-black text-rose-800" x-text="absenceDispatchCount"></div>
                                </div>
                                <div class="bg-white/80 p-2 rounded-lg border border-amber-200">
                                    <div class="text-[10px] font-black uppercase text-blue-700">Already Sent</div>
                                    <div class="text-lg font-black text-blue-800" x-text="absenceAlreadySentCount"></div>
                                </div>
                                <div class="bg-white/80 p-2 rounded-lg border border-amber-200">
                                    <div class="text-[10px] font-black uppercase text-emerald-700">To Notify</div>
                                    <div class="text-lg font-black text-emerald-800" x-text="absenceRemainingCount"></div>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium">
                                <i class="fa-solid fa-shield-halved text-emerald-600 mr-1"></i>
                                Duplicate prevention active: students already notified today will be safely skipped to avoid redundant SMS costs.
                            </p>
                        </div>
                    </div>
                </template>

                <template x-if="absenceDispatchLoading">
                    <div class="py-8 text-center space-y-4">
                        <i class="fa-solid fa-spinner fa-spin text-4xl text-[#590d0d]"></i>
                        <p class="text-sm font-black text-slate-800" x-text="absenceDispatchPhase"></p>
                        <p class="text-xs text-slate-500">Please keep this window open until the dispatch is complete.</p>
                    </div>
                </template>

                <template x-if="absenceDispatchResult">
                    <div class="space-y-4">
                        <div class="rounded-xl p-4 border-2"
                             :class="absenceDispatchResult.success ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'">
                            <div class="flex items-center gap-2 font-black text-xs"
                                 :class="absenceDispatchResult.success ? 'text-emerald-800' : 'text-red-800'">
                                <i class="fa-solid text-sm" :class="absenceDispatchResult.success ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                                <span x-text="absenceDispatchResult.message"></span>
                            </div>
                        </div>

                        {{-- 4 Stat Boxes: Total, Sent, Failed, Skipped --}}
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-2.5">
                                <div class="text-[9px] font-black uppercase text-slate-400">Total</div>
                                <div class="text-xl font-black text-slate-800" x-text="absenceDispatchResult.summary?.total ?? 0"></div>
                            </div>
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-2.5">
                                <div class="text-[9px] font-black uppercase text-emerald-600">Sent</div>
                                <div class="text-xl font-black text-emerald-700" x-text="absenceDispatchResult.summary?.sent ?? 0"></div>
                            </div>
                            <div class="rounded-xl bg-red-50 border border-red-200 p-2.5">
                                <div class="text-[9px] font-black uppercase text-red-600">Failed</div>
                                <div class="text-xl font-black text-red-700" x-text="absenceDispatchResult.summary?.failed ?? 0"></div>
                            </div>
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-2.5">
                                <div class="text-[9px] font-black uppercase text-amber-600">Skipped</div>
                                <div class="text-xl font-black text-amber-700" x-text="absenceDispatchResult.summary?.skipped ?? 0"></div>
                            </div>
                        </div>

                        {{-- Detailed Skipped Reasons list --}}
                        <template x-if="absenceDispatchResult.summary?.skipped_reasons?.length > 0">
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 space-y-1.5 text-left">
                                <p class="text-[10px] font-black text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Skipped Reasons
                                </p>
                                <div class="max-h-28 overflow-y-auto space-y-1 pr-1">
                                    <template x-for="(item, i) in absenceDispatchResult.summary.skipped_reasons" :key="i">
                                        <div class="flex items-start gap-1.5 text-[11px] text-amber-900 font-semibold">
                                            <i class="fa-solid fa-circle-minus text-amber-500 mt-0.5 shrink-0 text-[10px]"></i>
                                            <span x-text="item.reason"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2 shrink-0">
                <button type="button" x-show="!absenceDispatchLoading && !absenceDispatchResult"
                        @click="absenceDispatchOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-black hover:bg-slate-200 cursor-pointer">
                    Cancel
                </button>
                <button type="button" x-show="!absenceDispatchLoading && !absenceDispatchResult"
                        @click="dispatchAbsenceNotifications()"
                        :disabled="selectedAbsenceContextIds.length === 0"
                        class="px-4 py-2 rounded-xl bg-[#590d0d] text-amber-300 border-2 border-amber-400 text-xs font-black hover:bg-[#43090c] disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shadow-xs">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Confirm and Send (<span x-text="absenceRemainingCount"></span>)
                </button>
                <button type="button" x-show="!absenceDispatchLoading && absenceDispatchResult && !absenceDispatchResult.success"
                        @click="dispatchAbsenceNotifications(true)"
                        class="px-4 py-2 rounded-xl bg-amber-500 text-amber-950 text-xs font-black hover:bg-amber-400 cursor-pointer shadow-xs">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Resend
                </button>
                <button type="button" x-show="!absenceDispatchLoading && absenceDispatchResult"
                        @click="absenceDispatchOpen = false; window.location.reload()"
                        class="px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-black hover:bg-slate-700 cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         MODAL: RESEND PENDING SMS CONFIRMATION
         ================================================================ --}}
    <div x-show="resendPendingOpen"
         x-cloak
         class="fixed inset-0 z-[75] flex items-center justify-center p-4 bg-black/70"
         @click.self="!resendPendingLoading && (resendPendingOpen = false)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-[#590d0d] border-b-4 border-amber-400 px-6 py-4 flex items-center justify-between">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-rotate-right text-amber-300"></i>
                    <span x-show="!resendPendingLoading">Confirm Pending SMS Resend</span>
                    <span x-show="resendPendingLoading">Resending Pending SMS</span>
                </h3>
                <button type="button" @click="resendPendingOpen = false"
                        :disabled="resendPendingLoading"
                        class="text-white/70 hover:text-white disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                        aria-label="Close resend confirmation">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6">
                <template x-if="!resendPendingLoading">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
                            <div class="text-3xl font-black text-amber-700" x-text="resendPendingCount"></div>
                            <p class="mt-1 text-sm font-bold text-amber-900">pending SMS message<span x-show="resendPendingCount !== 1">s</span> will be resent.</p>
                        </div>
                        <p class="text-xs text-slate-500">The system will retry these messages through the SMS gateway. Failed deliveries will remain recorded for later retry.</p>
                    </div>
                </template>

                <template x-if="resendPendingLoading">
                    <div class="py-6 text-center space-y-4">
                        <i class="fa-solid fa-spinner fa-spin text-4xl text-[#590d0d]"></i>
                        <p class="text-sm font-black text-slate-800">Sending <span x-text="resendPendingCount"></span> pending message<span x-show="resendPendingCount !== 1">s</span>...</p>
                        <p class="text-xs text-slate-500">Please wait while the gateway processes the messages.</p>
                    </div>
                </template>
            </div>

            <div class="px-6 pb-6 flex justify-end gap-2">
                <button type="button" x-show="!resendPendingLoading" @click="resendPendingOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-black hover:bg-slate-200 cursor-pointer">Cancel</button>
                <button type="button" x-show="!resendPendingLoading" @click="confirmResendPending()"
                        class="px-4 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] cursor-pointer">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Confirm and Resend
                </button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         MODAL: VIEW MESSAGE DETAILS
         ================================================================ --}}
    <div x-show="detailsOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-8 pb-8 px-4 bg-black/60 overflow-y-auto"
         @click.self="detailsOpen = false"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden self-start"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="bg-slate-800 rounded-t-2xl px-6 py-4 flex items-center justify-between">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-amber-400"></i> SMS Dispatch Details
                </h3>
                <button @click="detailsOpen = false" class="text-white/70 hover:text-white transition-colors cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6">
                {{-- Loading --}}
                <template x-if="!detailsData">
                    <div class="flex flex-col items-center gap-3 py-8">
                        <i class="fa-solid fa-circle-notch fa-spin text-3xl text-slate-300"></i>
                        <p class="text-xs text-slate-400 font-bold">Loading message details...</p>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="detailsData && detailsData.error">
                    <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold text-center">
                        Failed to load message details.
                    </div>
                </template>

                {{-- Data --}}
                <template x-if="detailsData && !detailsData.error">
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-semibold block mb-0.5">Sent Timestamp</span>
                            <strong class="text-slate-800 font-black" x-text="detailsData.date_formatted"></strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block mb-0.5">Status</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-black uppercase"
                                  :class="{
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': detailsData.message.status === 'delivered',
                                    'bg-blue-50 text-blue-700 border border-blue-200': detailsData.message.status === 'sent',
                                    'bg-amber-50 text-amber-700 border border-amber-200': detailsData.message.status === 'pending',
                                    'bg-red-50 text-red-700 border border-red-200': detailsData.message.status === 'failed'
                                  }"
                                  x-text="detailsData.message.status === 'pending' ? 'queued' : detailsData.message.status"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block mb-0.5">Recipient Name</span>
                            <strong class="text-slate-800 font-black" x-text="detailsData.message.recipient_name || 'N/A'"></strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block mb-0.5">Contact Number</span>
                            <strong class="text-slate-800 font-black" x-text="detailsData.message.contact_number"></strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block mb-0.5">Sender</span>
                            <strong class="text-slate-800 font-black" x-text="detailsData.sender_name"></strong>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block mb-0.5">Gateway ID</span>
                            <code class="text-slate-600 text-[11px]" x-text="detailsData.message.gateway_message_id || 'N/A'"></code>
                        </div>
                        <div class="col-span-2">
                            <span class="text-slate-400 font-semibold block mb-0.5">Message Content</span>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed whitespace-pre-wrap font-medium"
                                 x-text="detailsData.message.message"></div>
                        </div>
                        <template x-if="detailsData.message.error_message">
                            <div class="col-span-2">
                                <span class="text-red-600 font-black block mb-0.5">Error Notice</span>
                                <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 font-medium"
                                     x-text="detailsData.message.error_message"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-6 pb-6 flex justify-end">
                <button @click="detailsOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-black hover:bg-slate-200 transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function smsAdminApp() {
    return {
        composeOpen: false,
        templateOpen: false,
        detailsOpen: false,
        resendPendingOpen: false,
        resendPendingLoading: false,
        resendPendingCount: {{ (int) $pendingSentToday }},
        absenceDispatchOpen: false,
        absenceDispatchLoading: false,
        absenceDispatchPhase: 'Checking SMS gateway connection...',
        absenceDispatchResult: null,
        absenceContexts: @json($absenceContexts),
        absenceFilterTab: 'all',
        selectedAbsenceContextIds: [],
        absenceDispatchCount: 0,
        absenceAlreadySentCount: 0,
        absenceRemainingCount: 0,
        studentDropdownOpen: false,
        detailsData: null,
        recipientMode: 'all_parents',
        includeSignature: true,
        baseMessage: '',
        signatureTeacher: @json($admin->full_name ?: 'Administration Office'),
        signatureSection: 'Office of the Principal',
        signatureSchool: 'Southern Isabela Academy',
        absenceDate: @json($today),
        selectedTemplate: '',
        renderedFooter: '',
        selectedCount: 0,
        charCount: 0,
        smsCount: 0,
        messageText: '',
        pingState: 'idle',
        pingLatency: null,

        init() {
            this.selectedAbsenceContextIds = this.absenceContexts
                .filter(c => (c.remaining_count || 0) > 0 || (c.absent_count || 0) > 0)
                .map(c => c.id);
            this.updateAbsenceSelection();
        },

        get visibleAbsenceContexts() {
            if (this.absenceFilterTab === 'advisory') return this.absenceContexts.filter(c => c.type === 'advisory');
            if (this.absenceFilterTab === 'subject') return this.absenceContexts.filter(c => c.type === 'subject');
            return this.absenceContexts;
        },

        absenceContextsSelected() {
            const visible = this.visibleAbsenceContexts;
            return visible.length > 0 && visible.every(c => this.selectedAbsenceContextIds.includes(c.id));
        },

        selectAllAbsenceContexts() {
            const visibleIds = this.visibleAbsenceContexts.map(c => c.id);
            this.selectedAbsenceContextIds = Array.from(new Set([...this.selectedAbsenceContextIds, ...visibleIds]));
            this.updateAbsenceSelection();
        },

        deselectAllAbsenceContexts() {
            const visibleIds = this.visibleAbsenceContexts.map(c => c.id);
            this.selectedAbsenceContextIds = this.selectedAbsenceContextIds.filter(id => !visibleIds.includes(id));
            this.updateAbsenceSelection();
        },

        updateAbsenceSelection() {
            const selected = this.absenceContexts.filter(c => this.selectedAbsenceContextIds.includes(c.id));
            this.absenceDispatchCount = selected.reduce((total, c) => total + (c.absent_count || 0), 0);
            this.absenceAlreadySentCount = selected.reduce((total, c) => total + (c.sent_count || 0), 0);
            this.absenceRemainingCount = selected.reduce((total, c) => total + (c.remaining_count || 0), 0);
        },

        updateCounts() {
            this.charCount = this.messageText.length;
            this.smsCount = this.messageText.length > 0 ? Math.ceil(this.messageText.length / 160) : 0;
        },

        signatureFooter() {
            let footer = "\n- " + (this.signatureTeacher || 'Administration Office');
            if (this.signatureSection) footer += " (" + this.signatureSection + ")";
            return footer + "\n" + (this.signatureSchool || 'Southern Isabela Academy');
        },

        stripFooter(value) {
            let cleaned = value || '';
            if (this.renderedFooter) {
                cleaned = cleaned.split(this.renderedFooter).join('');
            }
            const footerStart = cleaned.lastIndexOf('\n- ');
            return footerStart >= 0 ? cleaned.slice(0, footerStart) : cleaned;
        },

        syncMessage() {
            this.baseMessage = this.stripFooter(this.baseMessage);
            this.renderedFooter = this.includeSignature ? this.signatureFooter() : '';
            this.messageText = this.baseMessage + this.renderedFooter;
            this.updateCounts();
        },

        handleMessageInput() {
            this.baseMessage = this.includeSignature ? this.stripFooter(this.messageText) : this.messageText;
            this.updateCounts();
        },

        updateFooter() {
            this.syncMessage();
        },

        previewAbsenceDate() {
            const url = new URL(window.location.href);
            url.searchParams.set('absence_date', this.absenceDate);
            window.location.href = url.toString();
        },

        applyTemplate(key) {
            const date = this.absenceDate || new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const templates = {
                absent_today: {!! json_encode($defaultTemplate) !!},
                reminder: 'Reminding parent/guardian of [STUDENT_NAME] ([SECTION]) regarding upcoming school activity/deadline on [DATE]. Thank you.',
                meeting: 'Notice of Parent-Teacher Conference: You are invited to attend a meeting regarding [STUDENT_NAME] ([SECTION]) on [DATE]. Your presence is highly appreciated.',
                exam: 'Notice: Upcoming examinations for [SECTION] will commence on [DATE]. Please ensure [STUDENT_NAME] is well prepared. Thank you.',
                school_advisory: 'Important Advisory from Southern Isabela Academy: Please be advised regarding our upcoming academic event on [DATE]. Thank you.'
            };
            if (templates[key]) {
                this.baseMessage = templates[key].replace(/\[DATE\]/g, date).replace(/\{date\}/g, date);
                this.syncMessage();
            }
        },

        async sendAbsenceNotifications() {
            if (!this.selectedAbsenceContextIds.length) {
                this.selectedAbsenceContextIds = this.absenceContexts
                    .filter(c => (c.remaining_count || 0) > 0 || (c.absent_count || 0) > 0)
                    .map(c => c.id);
            }
            this.updateAbsenceSelection();
            this.absenceDispatchResult = null;
            this.absenceDispatchOpen = true;
        },

        async dispatchAbsenceNotifications(isRetry = false) {
            this.absenceDispatchLoading = true;
            this.absenceDispatchResult = null;
            try {
                this.absenceDispatchPhase = 'Checking SMS gateway connection...';
                const gatewayResponse = await fetch('{{ route('sms.ping') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const gateway = await gatewayResponse.json();
                if (!gateway.online) {
                    this.absenceDispatchResult = {
                        success: false,
                        message: 'SMS gateway is offline. No messages were sent.',
                        summary: { total: 0, sent: 0, failed: 0, skipped: 0, skipped_reasons: [] }
                    };
                    return;
                }

                this.absenceDispatchPhase = 'Gateway online. Dispatching absence alerts...';
                const response = await fetch('{{ route('admin.sms.send-absence-notifications') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        absence_date: this.absenceDate,
                        context_ids: this.selectedAbsenceContextIds,
                        retry: isRetry
                    })
                });
                const data = await response.json();
                this.absenceDispatchResult = {
                    success: response.ok && Boolean(data.success),
                    message: data.message || 'Absence notification dispatch completed.',
                    summary: data.summary || { total: 0, sent: 0, failed: 0, skipped: 0, skipped_reasons: [] }
                };
            } catch (error) {
                this.absenceDispatchResult = {
                    success: false,
                    message: 'Unable to contact the SMS dispatch endpoint.',
                    summary: { total: 0, sent: 0, failed: 0, skipped: 0, skipped_reasons: [] }
                };
            } finally {
                this.absenceDispatchLoading = false;
            }
        },

        async pingGateway() {
            this.pingState = 'loading';
            try {
                const r = await fetch('{{ route('sms.ping') }}');
                const d = await r.json();
                if (d.online) {
                    this.pingState = 'online';
                    this.pingLatency = d.latency;
                    setTimeout(() => { this.pingState = 'idle'; }, 3000);
                } else {
                    this.pingState = 'offline';
                }
            } catch(e) {
                this.pingState = 'offline';
            }
        },

        async pingOnLoad() {
            try {
                const r = await fetch('{{ route('sms.ping') }}');
                const d = await r.json();
                this.pingState = d.online ? 'online' : 'offline';
                if (d.online) this.pingLatency = d.latency;
            } catch(e) { this.pingState = 'offline'; }
        },

        async loadDetails(id) {
            this.detailsData = null;
            this.detailsOpen = true;
            try {
                const r = await fetch('/outbound-messages/' + id);
                const d = await r.json();
                this.detailsData = d;
            } catch(e) {
                this.detailsData = { error: true };
            }
        },

        async submitSms(event) {
            const form = event.target;
            const btn = form.querySelector('#submitSendSmsBtn');
            const alertBox = document.getElementById('modalAlertBox');
            if (alertBox) alertBox.innerHTML = '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending SMS...';
            }

            try {
                const resp = await fetch('{{ route('sms.send') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(form)
                });
                const data = await resp.json();
                if (resp.ok) {
                    if (alertBox) alertBox.innerHTML = '<div class="p-3 rounded-xl bg-emerald-50 border-2 border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2"><i class="fa-solid fa-circle-check"></i>' + data.message + '</div>';
                    setTimeout(() => { this.composeOpen = false; window.location.reload(); }, 1500);
                } else {
                    if (alertBox) alertBox.innerHTML = '<div class="p-3 rounded-xl bg-red-50 border-2 border-red-200 text-red-800 text-xs font-bold flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i>' + (data.message || 'Failed to send SMS.') + '</div>';
                }
            } catch(e) {
                if (alertBox) alertBox.innerHTML = '<div class="p-3 rounded-xl bg-red-50 border-2 border-red-200 text-red-800 text-xs font-bold">Network error. Please try again.</div>';
            }

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Send Outbound SMS';
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {

    // Student checkbox counter — syncs with Alpine x-text="selectedCount"
    function updateSelectedCount() {
        const count = document.querySelectorAll('.student-chk:checked').length;
        const root = document.querySelector('[x-data]');
        if (root && window.Alpine) {
            const state = Alpine.$data(root) || (root._x_dataStack && root._x_dataStack[0]);
            if (state) state.selectedCount = count;
        }
    }

    document.addEventListener('change', function(event) {
        if (event.target.matches('.student-chk')) updateSelectedCount();
    });
    updateSelectedCount();

    const selectAllBtn = document.getElementById('selectAllStudentsBtn');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.student-chk:not(:disabled)').forEach(function(chk) {
                if (!chk.closest('.student-select-item') || chk.closest('.student-select-item').style.display !== 'none') {
                    chk.checked = true;
                }
            });
            updateSelectedCount();
        });
    }

    const deselectAllBtn = document.getElementById('deselectAllStudentsBtn');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.student-chk').forEach(function(chk) { chk.checked = false; });
            updateSelectedCount();
        });
    }

    // Student search filter
    const searchStudentsList = document.getElementById('searchStudentsList');
    if (searchStudentsList) {
        function filterStudents() {
            const q = searchStudentsList.value.toLowerCase();
            const grade = (document.getElementById('studentGradeFilter')?.value || '').toLowerCase();
            const section = (document.getElementById('studentSectionFilter')?.value || '').toLowerCase();
            document.querySelectorAll('.student-select-item').forEach(function(item) {
                const name = (item.dataset.name || '').toLowerCase();
                const itemSection = (item.dataset.section || '').toLowerCase();
                const itemGrade = (item.dataset.grade || '').toLowerCase();
                item.style.display = (name.includes(q) || itemSection.includes(q))
                    && (!grade || itemGrade === grade)
                    && (!section || itemSection === section) ? '' : 'none';
            });
        }
        searchStudentsList.addEventListener('input', filterStudents);
        document.getElementById('studentGradeFilter')?.addEventListener('change', filterStudents);
        document.getElementById('studentSectionFilter')?.addEventListener('change', filterStudents);
    }

    // Specific student preview card
    const specificStudentSelect = document.getElementById('specificStudentSelect');
    if (specificStudentSelect) {
        specificStudentSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const card = document.getElementById('specificStudentInfoCard');
            if (opt.value) {
                document.getElementById('previewStudentName').textContent = opt.dataset.name || '-';
                document.getElementById('previewStudentSection').textContent = opt.dataset.section || '-';
                document.getElementById('previewParentName').textContent = opt.dataset.parent || '-';
                document.getElementById('previewContactNumber').textContent = opt.dataset.contact || '-';
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

});
</script>
@endpush

@endsection
