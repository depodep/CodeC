@extends('layouts.app')

@section('title', 'SMS Messages | SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70"
     x-data="smsTeacherApp()"
     x-init="pingGateway()">

    {{-- ================================================================
         PAGE HEADER — matches admin & teacher portal pattern
         ================================================================ --}}
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-comments"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">SMS Messages</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Faculty Portal</span>
                </div>
            </div>
        </div>

    </header>

    {{-- ================================================================
         MAIN CONTENT
         ================================================================ --}}
    <main class="p-6 lg:p-10 w-full space-y-6 flex-1">

        {{-- Flash Notifications --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs w-full">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 text-sm cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        {{-- ================================================================
             DISPATCH METRICS ROW (TOP)
             ================================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
            {{-- Total Sent --}}
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Total Sent</span>
                    <h2 class="text-4xl font-black text-emerald-600 tracking-tight mt-1">{{ number_format($totalSent ?? 0) }}</h2>
                    <span class="text-xs text-slate-400 font-semibold mt-0.5 block">Delivered dispatches</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 flex items-center justify-center text-xl shadow-xs shrink-0">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
            </div>

            {{-- Total Failed --}}
            <div class="p-5 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block">Total Failed</span>
                    <h2 class="text-4xl font-black text-red-600 tracking-tight mt-1">{{ number_format($totalFailed ?? 0) }}</h2>
                    <span class="text-xs text-slate-400 font-semibold mt-0.5 block">Errors / Rate-limiting cooldown (60s)</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-red-600 flex items-center justify-center text-xl shadow-xs shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            </div>

        {{-- Teacher-owned advisory and subject absence triggers --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border-2 border-slate-200 shadow-xs overflow-hidden flex flex-col justify-between">
            {{-- Card Header --}}
            <div class="bg-[#590d0d] border-b-4 border-amber-400 px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h2 class="text-sm font-black text-white flex items-center gap-2">
                        <i class="fa-solid fa-bell text-amber-400"></i> Absences
                    </h2>
                    <div class="flex items-center gap-1.5 bg-black/25 px-2 py-1 rounded-lg border border-white/10">
                        <i class="fa-solid fa-calendar-day text-[11px] text-amber-300"></i>
                        <input type="date"
                               x-model="absenceDate"
                               max="{{ \Carbon\Carbon::today()->toDateString() }}"
                               @change="previewAbsenceDate()"
                               class="bg-transparent text-white text-xs font-bold focus:outline-none cursor-pointer [color-scheme:dark]">
                    </div>
                </div>
                <button type="button" @click="openAbsenceSelectionModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-400 text-amber-950 text-xs font-black hover:bg-amber-300 transition cursor-pointer shadow-xs shrink-0">
                    <i class="fa-solid fa-paper-plane"></i> Send Absence Notifications (<span x-text="absenceRemainingCount"></span>)
                </button>
            </div>

            {{-- Compact Cards Grid Body --}}
            <div class="p-4 flex-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-72 sm:max-h-36 overflow-y-auto overflow-x-hidden pr-1">
                    {{-- Advisory Sections Cards --}}
                    <template x-for="context in absenceContexts.filter(c => c.type === 'advisory')" :key="context.id">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-amber-50/40 hover:bg-amber-50/70 transition flex items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center justify-center shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-users-viewfinder text-xs"></i>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-black text-slate-800 block truncate" x-text="context.label"></span>
                                    <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Advisory Section</span>
                                </div>
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-1">
                                <div class="flex items-center gap-1">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black inline-flex items-center gap-1"
                                          :class="context.absent_count > 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'">
                                        <i class="fa-solid fa-user-xmark text-[9px]"></i>
                                        <span x-text="context.absent_count"></span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-blue-100 text-blue-700 border border-blue-200"
                                          x-show="context.sent_count > 0"
                                          x-text="context.sent_count + ' sent'"></span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200"
                                          x-show="context.absent_count > 0 && context.remaining_count === 0">
                                        Done
                                    </span>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400" x-text="'/' + context.student_count + ' total'"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Subject Classes Cards --}}
                    <template x-for="context in absenceContexts.filter(c => c.type === 'subject')" :key="context.id">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-100/70 transition flex items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-book-open text-xs"></i>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-black text-slate-800 block truncate" x-text="context.label"></span>
                                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Subject Class</span>
                                </div>
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-1">
                                <div class="flex items-center gap-1">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black inline-flex items-center gap-1"
                                          :class="context.absent_count > 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'">
                                        <i class="fa-solid fa-user-xmark text-[9px]"></i>
                                        <span x-text="context.absent_count"></span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-blue-100 text-blue-700 border border-blue-200"
                                          x-show="context.sent_count > 0"
                                          x-text="context.sent_count + ' sent'"></span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200"
                                          x-show="context.absent_count > 0 && context.remaining_count === 0">
                                        Done
                                    </span>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400" x-text="'/' + context.student_count + ' total'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Empty state --}}
                <template x-if="!absenceContexts.length">
                    <div class="py-6 text-center text-slate-400">
                        <i class="fa-solid fa-calendar-xmark text-2xl text-slate-300 block mb-1.5"></i>
                        <p class="text-xs font-black text-slate-500">No advisory sections or subjects assigned.</p>
                    </div>
                </template>
            </div>
        </div>
        </div>

        {{-- ================================================================
             ABSENCE NOTIFICATION MODAL (Send Checklist)
             ================================================================ --}}
        <div x-show="absenceModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/60"
             @click.self="absenceModalOpen = false"
             x-cloak>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[88vh]"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                {{-- Modal Header --}}
                <div class="bg-[#590d0d] border-b-4 border-amber-400 px-6 py-4 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            <i class="fa-solid fa-bell text-amber-400"></i> Send Absence Notification
                        </h3>
                        <p class="text-[11px] text-amber-200 mt-0.5">Select which sections or classes to notify parents for <strong class="text-white" x-text="absenceDate"></strong>.</p>
                    </div>
                    <button @click="absenceModalOpen = false" class="text-white/70 hover:text-white cursor-pointer">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 overflow-y-auto flex-1">
                    {{-- Filter Tabs and Select All in view --}}
                    <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
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

                        {{-- Select all in view toggle --}}
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox"
                                   class="rounded accent-[#590d0d]"
                                   :checked="absenceContextsSelected()"
                                   @change="$event.target.checked ? selectAllAbsenceContexts() : deselectAllAbsenceContexts()">
                            Select all in view
                        </label>
                    </div>

                    {{-- Selection Checklist Container --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                        <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <template x-for="ctx in visibleAbsenceContexts" :key="ctx.id">
                                <label class="flex items-center justify-between gap-3 p-2.5 rounded-xl border bg-white cursor-pointer transition"
                                       :class="selectedAbsenceIds.includes(ctx.id) ? 'border-amber-400 bg-amber-50/50' : 'border-slate-200 hover:border-slate-300'">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="checkbox"
                                               class="rounded accent-[#590d0d] shrink-0"
                                               :value="ctx.id"
                                               x-model="selectedAbsenceIds"
                                               @change="updateAbsenceSelection()">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <i class="fa-solid text-[10px]"
                                                   :class="ctx.type === 'advisory' ? 'fa-users-viewfinder text-amber-700' : 'fa-book-open text-blue-600'"></i>
                                                <span class="text-xs font-black text-slate-800 truncate" x-text="ctx.label"></span>
                                            </div>
                                            <div class="text-[10px] text-slate-500 font-medium">
                                                <span x-text="ctx.student_count + ' students'"></span>
                                                <span class="text-amber-700 font-semibold uppercase tracking-wider text-[9px] ml-1" x-text="'• ' + (ctx.type === 'advisory' ? 'Advisory Section' : 'Subject Class')"></span>
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
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 space-y-2">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white/80 p-2 rounded-lg border border-amber-200">
                                <div class="text-[10px] font-black uppercase text-rose-700">Total Absent</div>
                                <div class="text-base font-black text-rose-800" x-text="absenceDispatchCount"></div>
                            </div>
                            <div class="bg-white/80 p-2 rounded-lg border border-amber-200">
                                <div class="text-[10px] font-black uppercase text-blue-700">Already Sent</div>
                                <div class="text-base font-black text-blue-800" x-text="absenceAlreadySentCount"></div>
                            </div>
                            <div class="bg-white/80 p-2 rounded-lg border border-amber-200">
                                <div class="text-[10px] font-black uppercase text-emerald-700">To Notify</div>
                                <div class="text-base font-black text-emerald-800" x-text="absenceRemainingCount"></div>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">
                            <i class="fa-solid fa-shield-halved text-emerald-600 mr-1"></i>
                            Duplicate prevention active: parents already notified today will be safely skipped to avoid redundant SMS.
                        </p>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
                    <div class="text-xs font-bold text-slate-500">
                        <span x-text="selectedAbsenceIds.length"></span> section(s) selected
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="absenceModalOpen = false"
                                class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-black hover:bg-slate-200 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" @click="openAbsenceConfirmation()"
                                :disabled="!selectedAbsenceIds.length || absenceLoading"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer shadow-xs">
                            <i class="fa-solid" :class="absenceLoading ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                            <span>Send Notifications (<span x-text="absenceRemainingCount"></span>)</span>
                        </button>
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
                    <a href="{{ route('teacher.message') }}"
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
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-400 text-amber-950 text-xs font-black hover:bg-amber-300 transition-colors cursor-pointer shadow-xs">
                        <i class="fa-solid fa-paper-plane"></i> Send SMS
                    </button>
                </div>
            </div>

            {{-- Integrated Search & History Filters (Compact Single Row) --}}
            <div class="px-5 py-3 border-b-2 border-slate-100 bg-slate-50/50">
                <form method="GET" action="{{ route('teacher.message') }}" id="filterForm">
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Student / Recipient --}}
                        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden focus-within:border-[#590d0d] bg-white transition-colors min-w-[180px] flex-1">
                            <span class="px-2.5 text-slate-400"><i class="fa-solid fa-user text-xs"></i></span>
                            <input type="text" name="recipient"
                                   class="flex-1 py-1.5 pr-3 text-xs text-slate-800 bg-transparent outline-none placeholder-slate-400"
                                   placeholder="Search name or phone..."
                                   value="{{ request('recipient') }}">
                        </div>

                        {{-- Status --}}
                        <select name="status"
                                class="py-1.5 px-3 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>

                        {{-- From Date --}}
                        <input type="date" name="from_date"
                               class="py-1.5 px-3 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none"
                               value="{{ request('from_date') }}"
                               placeholder="From">

                        {{-- To Date --}}
                        <input type="date" name="to_date"
                               class="py-1.5 px-3 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:border-[#590d0d] outline-none"
                               value="{{ request('to_date') }}"
                               placeholder="To">

                        {{-- Buttons --}}
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition-colors cursor-pointer shadow-xs shrink-0">
                            <i class="fa-solid fa-magnifying-glass"></i> Apply
                        </button>
                        <a href="{{ route('teacher.message') }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-200 text-slate-700 text-xs font-black hover:bg-slate-300 transition-colors shrink-0">
                            <i class="fa-solid fa-xmark"></i> Clear
                        </a>
                    </div>
                </form>
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
                    <tbody class="divide-y divide-slate-100" x-ref="historyBody">
                        @forelse($messages as $msg)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-3 py-4 text-center text-xs font-black text-slate-400">{{ $messages->firstItem() + $loop->index }}</td>
                                {{-- Date --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-black text-slate-800">{{ $msg->created_at->format('M d, Y') }}</div>
                                    <div class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ $msg->created_at->format('h:i A') }}</div>
                                </td>

                                {{-- Recipient --}}
                                <td class="px-4 py-4">
                                    <div class="text-xs font-black text-slate-800">{{ $msg->recipient_name ?: ($msg->student ? $msg->student->full_name : 'Parent/Contact') }}</div>
                                    <div class="text-[11px] text-slate-400 font-semibold mt-0.5 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-slate-300 text-[10px]"></i>
                                        {{ $msg->contact_number }}
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
                <div x-ref="historyPagination" class="px-6 py-4 border-t-2 border-slate-100 bg-slate-50/50 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 flex-wrap text-xs text-slate-500 font-bold">
                        <label for="teacherPerPage" class="text-slate-600">Rows per page</label>
                        <select id="teacherPerPage" name="per_page" form="filterForm" @change="changeHistoryPageSize($event)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 outline-none">
                            @foreach([10, 15, 30, 50] as $option)
                                <option value="{{ $option }}" {{ (string) request('per_page', 10) === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                        <span x-ref="historySummary">Showing {{ $messages->firstItem() }} to {{ $messages->lastItem() }} of {{ $messages->total() }} records</span>
                    </div>
                    <div class="flex items-center gap-1 flex-wrap justify-end">
                        {{-- Prev --}}
                        @if($messages->onFirstPage())
                            <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed">
                                <i class="fa-solid fa-chevron-left"></i>
                            </span>
                        @else
                            <a href="{{ $messages->previousPageUrl() }}" @click.prevent="loadHistoryUrl($event)"
                               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition-colors">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach($messages->getUrlRange(max(1, $messages->currentPage()-2), min($messages->lastPage(), $messages->currentPage()+2)) as $page => $url)
                            @if($page == $messages->currentPage())
                                <span class="px-3 py-1.5 rounded-lg bg-[#590d0d] text-amber-300 text-xs font-black">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" @click.prevent="loadHistoryUrl($event)" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition-colors">{{ $page }}</a>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if($messages->hasMorePages())
                            <a href="{{ $messages->nextPageUrl() }}" @click.prevent="loadHistoryUrl($event)"
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
         MODAL: COMPOSE / SEND SMS (Teacher)
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
                    <i class="fa-solid fa-paper-plane text-amber-400"></i> Faculty SMS Dispatch Console
                </h3>
                <button @click="composeOpen = false" class="text-white/70 hover:text-white transition-colors cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="sendSmsForm" @submit.prevent="submitSms($event)">
                @csrf
                <div class="p-6 space-y-5 max-h-[78vh] overflow-y-auto">

                    {{-- Recipient groups: kept inside Send SMS, not the absence trigger. --}}
                    <div class="flex items-center gap-2 overflow-x-auto border-b border-slate-200 pb-2">
                        <template x-for="tab in recipientFilterTabs" :key="tab.id">
                            <button type="button" @click="selectRecipientFilter(tab.id)"
                                    class="shrink-0 rounded-lg px-3 py-1.5 text-[11px] font-black whitespace-nowrap cursor-pointer"
                                    :class="recipientFilter === tab.id ? 'bg-[#590d0d] text-amber-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                <span x-text="tab.label"></span> <span class="opacity-70" x-text="'(' + tab.count + ')'" ></span>
                            </button>
                        </template>
                    </div>

                    {{-- Recipient Mode --}}
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Recipient Target</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            @foreach([
                                ['all_students', 'All Students (Parents)'],
                                ['selected_students', 'Selected Students'],
                                ['specific_student', 'Specific Student'],
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
                    <div x-show="recipientMode === 'selected_students'" x-transition x-cloak class="space-y-2">
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
                                    <span x-text="selectedStudentIds.length">0</span> selected
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
                             class="p-3.5 rounded-2xl bg-white border-2 border-slate-200 shadow-inner space-y-3">

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
                                         data-section="{{ strtolower($st->section ?? '') }}">
                                        <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0 {{ !$hasContact ? 'opacity-60 cursor-not-allowed' : '' }}">
                                            <input class="student-chk w-3.5 h-3.5 rounded accent-[#590d0d] shrink-0"
                                                   type="checkbox"
                                                   name="student_ids[]"
                                                   value="{{ $st->id }}"
                                                   id="chkStudent_{{ $st->id }}"
                                                   x-model="selectedStudentIds"
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
                        <select name="student_id" id="specificStudentSelect" x-model="specificStudentId" @change="if (selectedTemplateKey) applyTemplate(selectedTemplateKey)"
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

                    {{-- Message Template --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Message Template</label>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-500 border border-slate-200 font-bold">Optional</span>
                        </div>
                        <select id="templateSelector" x-model="selectedTemplateKey" @change="applyTemplate($event.target.value)"
                                class="w-full py-2.5 px-3 text-sm text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-[#590d0d] outline-none cursor-pointer">
                            <option value="">-- Select a template to autofill --</option>
                            <option value="absent_today">Absent Today Notice</option>
                            <option value="reminder">Activity / Deadline Reminder</option>
                            <option value="meeting">Parent-Teacher Meeting Invitation</option>
                            <option value="exam">Upcoming Examination Advisory</option>
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
                                  @input="updateCounts()"
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

                    {{-- Teacher Signature Toggle --}}
                    <div class="p-4 rounded-xl bg-slate-50 border-2 border-slate-200 space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="include_signature" id="signatureToggle" value="1"
                                   x-model="includeSignature"
                                   class="w-4 h-4 rounded accent-[#590d0d]">
                            <span class="text-xs font-black text-slate-700">Append Teacher Signature</span>
                        </label>

                        <div id="signatureDetailsContainer" x-show="includeSignature" x-transition class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">Teacher Name</label>
                                <input type="text" name="signature_teacher"
                                       class="w-full py-1.5 px-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-lg focus:border-[#590d0d] outline-none"
                                       value="{{ $teacher->full_name ?: 'Teacher' }}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">Section / Advisory</label>
                                <input type="text" name="signature_section"
                                       class="w-full py-1.5 px-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-lg focus:border-[#590d0d] outline-none"
                                       value="{{ $teacher->section ?: '' }}" placeholder="e.g. Grade 11 STEM A">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">School Name</label>
                                <input type="text" name="signature_school"
                                       class="w-full py-1.5 px-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-lg focus:border-[#590d0d] outline-none"
                                       value="Southern Isabela Academy">
                            </div>
                        </div>
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
         MODAL: TEACHER ABSENCE NOTIFICATION CONFIRMATION / RESULT
         ================================================================ --}}
    <div x-show="absenceConfirmOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/70" @click.self="!absenceLoading && (absenceConfirmOpen = false)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-[#590d0d] border-b-4 border-amber-400 px-5 py-3 flex items-center justify-between shrink-0">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-300"></i>
                    <span x-show="!absenceLoading && !absenceResult">Confirm Absence Notification</span>
                    <span x-show="absenceLoading">Processing Absence Notification</span>
                    <span x-show="absenceResult">Absence Notification Result</span>
                </h3>
                <button type="button" @click="absenceConfirmOpen = false" :disabled="absenceLoading" class="text-white/70 hover:text-white disabled:opacity-40 cursor-pointer" aria-label="Close absence notification">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <div class="p-5 overflow-y-auto space-y-3 flex-1">
                <template x-if="!absenceLoading && !absenceResult">
                    <div class="space-y-3">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5">
                            <p class="text-xs font-black text-amber-950">Send absence notifications?</p>
                            <div class="mt-1.5 space-y-1">
                                <template x-for="ctx in selectedAbsenceContexts" :key="ctx.id">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-amber-900 bg-white/70 px-2.5 py-1 rounded-lg border border-amber-200/60">
                                        <span class="truncate" x-text="ctx.label"></span>
                                        <div class="shrink-0 flex items-center gap-1.5 ml-2">
                                            <span class="text-rose-700" x-text="ctx.absent_count + ' absent'"></span>
                                            <span class="text-blue-700 text-[10px]" x-show="ctx.sent_count > 0" x-text="'(' + ctx.sent_count + ' sent)'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-2 text-[10px] text-slate-500 font-semibold">Only students absent on selected date will receive an SMS alert.</p>
                        </div>
                    </div>
                </template>
                <template x-if="absenceLoading">
                    <div class="py-3 space-y-4">
                        {{-- Step Tracker Compact --}}
                        <div class="flex items-center px-4 justify-between">
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shadow-2xs"
                                     :class="absencePhase === 'Checking SMS gateway...' ? 'bg-[#590d0d] text-amber-300 ring-2 ring-amber-400' : 'bg-emerald-100 text-emerald-700'">
                                    <i class="fa-solid" :class="absencePhase === 'Checking SMS gateway...' ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                </div>
                                <span class="text-[9px] font-bold text-slate-500">Gateway</span>
                            </div>
                            <div class="flex-1 h-0.5 mx-2 mb-4" :class="absencePhase !== 'Checking SMS gateway...' ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shadow-2xs"
                                     :class="absencePhase === 'Sending absence messages...' ? 'bg-[#590d0d] text-amber-300 ring-2 ring-amber-400' : 'bg-slate-100 text-slate-400'">
                                    <i class="fa-solid" :class="absencePhase === 'Sending absence messages...' ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                </div>
                                <span class="text-[9px] font-bold text-slate-500">Sending</span>
                            </div>
                            <div class="flex-1 h-0.5 mx-2 mb-4 bg-slate-200"></div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black bg-slate-100 text-slate-300 shadow-2xs">
                                    <i class="fa-solid fa-flag-checkered"></i>
                                </div>
                                <span class="text-[9px] font-bold text-slate-400">Result</span>
                            </div>
                        </div>
                        <div class="text-center space-y-0.5">
                            <p class="text-xs font-black text-slate-800" x-text="absencePhase"></p>
                            <p class="text-[10px] text-slate-400">Please wait while SMS dispatch completes...</p>
                        </div>
                    </div>
                </template>
                <template x-if="absenceResult">
                    <div class="space-y-3">
                        <div class="rounded-xl p-3 border" :class="absenceResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900'">
                            <div class="flex items-center gap-2 text-xs font-black">
                                <i class="fa-solid text-sm" :class="absenceResult.success ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-600'"></i>
                                <span x-text="absenceResult.message"></span>
                            </div>
                        </div>
                        <div x-show="absenceResult.summary" class="grid grid-cols-4 gap-2 text-center">
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-2.5"><div class="text-[9px] font-black text-slate-400 uppercase">Total</div><div class="text-xl font-black text-slate-800" x-text="absenceResult.summary?.total ?? 0"></div></div>
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-2.5"><div class="text-[9px] font-black text-emerald-600 uppercase">Sent</div><div class="text-xl font-black text-emerald-700" x-text="absenceResult.summary?.sent ?? 0"></div></div>
                            <div class="rounded-xl bg-red-50 border border-red-200 p-2.5"><div class="text-[9px] font-black text-red-600 uppercase">Failed</div><div class="text-xl font-black text-red-700" x-text="absenceResult.summary?.failed ?? 0"></div></div>
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-2.5"><div class="text-[9px] font-black text-amber-600 uppercase">Skipped</div><div class="text-xl font-black text-amber-700" x-text="absenceResult.summary?.skipped ?? 0"></div></div>
                        </div>

                        {{-- Skipped Reasons --}}
                        <template x-if="absenceResult.summary?.skipped_reasons?.length > 0">
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-2.5 space-y-1">
                                <p class="text-[10px] font-black text-amber-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Skipped Reasons
                                </p>
                                <div class="max-h-24 overflow-y-auto space-y-1 pr-1">
                                    <template x-for="(item, i) in absenceResult.summary.skipped_reasons" :key="i">
                                        <div class="flex items-start gap-1.5 text-[10px] text-amber-900 font-semibold">
                                            <i class="fa-solid fa-circle-minus text-amber-500 mt-0.5 shrink-0 text-[9px]"></i>
                                            <span x-text="item.reason"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 flex justify-end gap-2 shrink-0 bg-slate-50/50">
                <button type="button" x-show="!absenceLoading" @click="absenceConfirmOpen = false" class="px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-black hover:bg-slate-200 cursor-pointer">Close</button>
                <button type="button" x-show="!absenceLoading && !absenceResult" @click="sendTeacherAbsence()" class="px-3.5 py-1.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] cursor-pointer"><i class="fa-solid fa-paper-plane mr-1"></i> Confirm and Send</button>
                <button type="button" x-show="!absenceLoading && absenceResult && !absenceResult.success" @click="sendTeacherAbsence()" class="px-3.5 py-1.5 rounded-xl bg-amber-500 text-amber-950 text-xs font-black hover:bg-amber-400 cursor-pointer"><i class="fa-solid fa-rotate-right mr-1"></i> Resend</button>
            </div>
        </div>
    </div>

    {{-- Sending progress overlay --}}
    <div x-show="sendingSms" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <i class="fa-solid fa-paper-plane fa-bounce text-3xl text-[#590d0d]"></i>
            <h3 class="mt-4 text-base font-black text-slate-900">Sending SMS</h3>
            <p class="mt-1 text-xs font-bold text-slate-500" x-text="sendProgressMessage"></p>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-1/2 animate-pulse rounded-full bg-amber-400"></div></div>
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 overflow-y-auto"
         @click.self="detailsOpen = false"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-8 overflow-hidden"
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
function smsTeacherApp() {
    return {
        composeOpen: false,
        detailsOpen: false,
        absenceModalOpen: false,
        absenceConfirmOpen: false,
        absenceLoading: false,
        absenceResult: null,
        absencePhase: 'Checking SMS gateway...',
        absenceDate: @json($absenceDate ?? today()->toDateString()),
        absenceFilterTab: 'all',
        selectedAbsenceIds: [],
        absenceDispatchCount: 0,
        absenceAlreadySentCount: 0,
        absenceRemainingCount: 0,
        recipientFilter: 'all',
        recipientStudentCount: {{ count($students) }},
        messageTabs: @json($messageTabs),
        absenceContexts: @json($absenceContexts),
        studentDropdownOpen: false,
        detailsData: null,
        recipientMode: 'all_students',
        includeSignature: true,
        selectedStudentIds: [],
        specificStudentId: '',
        selectedTemplateKey: '',
        selectedCount: 0,
        charCount: 0,
        smsCount: 0,
        messageText: '',
        pingState: 'idle',
        pingLatency: null,
        sendingSms: false,
        sendProgressMessage: 'Checking SMS gateway connection...',

        init() {
            this.selectedAbsenceIds = this.absenceContexts
                .filter(c => (c.remaining_count || 0) > 0 || (c.absent_count || 0) > 0)
                .map(c => c.id);
            this.updateAbsenceSelection();
        },

        previewAbsenceDate() {
            const url = new URL(window.location.href);
            url.searchParams.set('absence_date', this.absenceDate);
            window.location.href = url.toString();
        },

        get visibleAbsenceContexts() {
            if (this.absenceFilterTab === 'advisory') return this.absenceContexts.filter(c => c.type === 'advisory');
            if (this.absenceFilterTab === 'subject') return this.absenceContexts.filter(c => c.type === 'subject');
            return this.absenceContexts;
        },

        absenceContextsSelected() {
            const visible = this.visibleAbsenceContexts;
            return visible.length > 0 && visible.every(c => this.selectedAbsenceIds.includes(c.id));
        },

        selectAllAbsenceContexts() {
            const visibleIds = this.visibleAbsenceContexts.map(c => c.id);
            this.selectedAbsenceIds = Array.from(new Set([...this.selectedAbsenceIds, ...visibleIds]));
            this.updateAbsenceSelection();
        },

        deselectAllAbsenceContexts() {
            const visibleIds = this.visibleAbsenceContexts.map(c => c.id);
            this.selectedAbsenceIds = this.selectedAbsenceIds.filter(id => !visibleIds.includes(id));
            this.updateAbsenceSelection();
        },

        updateAbsenceSelection() {
            const selected = this.absenceContexts.filter(c => this.selectedAbsenceIds.includes(c.id));
            this.absenceDispatchCount = selected.reduce((total, c) => total + (c.absent_count || 0), 0);
            this.absenceAlreadySentCount = selected.reduce((total, c) => total + (c.sent_count || 0), 0);
            this.absenceRemainingCount = selected.reduce((total, c) => total + (c.remaining_count || 0), 0);
        },

        openAbsenceSelectionModal() {
            if (!this.selectedAbsenceIds.length) {
                this.selectedAbsenceIds = this.absenceContexts
                    .filter(c => (c.remaining_count || 0) > 0 || (c.absent_count || 0) > 0)
                    .map(c => c.id);
            }
            this.updateAbsenceSelection();
            this.absenceModalOpen = true;
        },

        get selectedAbsenceContexts() {
            return this.absenceContexts.filter(context => this.selectedAbsenceIds.includes(context.id));
        },

        get selectedAbsenceContext() {
            return this.selectedAbsenceContexts[0] || null;
        },

        get recipientFilterTabs() {
            return [
                { id: 'all', label: 'All', count: this.recipientStudentCount },
                { id: 'advisory', label: 'Advisory', count: this.absenceContexts.filter(context => context.type === 'advisory').length },
                ...this.absenceContexts.filter(context => context.type === 'subject').map(context => ({ id: context.id, label: context.label, count: 1 })),
            ];
        },

        selectRecipientFilter(filter) {
            this.recipientFilter = filter;
            this.filterRecipientItems();
        },

        filterRecipientItems() {
            const query = (document.getElementById('searchStudentsList')?.value || '').toLowerCase().trim();
            const selectedContext = this.absenceContexts.find(context => context.id === this.recipientFilter);
            document.querySelectorAll('.student-select-item').forEach(item => {
                const matchesSearch = !query || (item.dataset.name || '').includes(query) || (item.dataset.section || '').includes(query);
                const matchesGroup = this.recipientFilter === 'all'
                    || (this.recipientFilter === 'advisory' && this.absenceContexts.filter(context => context.type === 'advisory').some(context => context.section.toLowerCase() === (item.dataset.section || '').toLowerCase()))
                    || (selectedContext && selectedContext.section.toLowerCase() === (item.dataset.section || '').toLowerCase());
                item.style.display = matchesSearch && matchesGroup ? '' : 'none';
            });
        },

        updateAbsenceContext() {
            this.absenceResult = null;
        },

        openAbsenceConfirmation() {
            if (!this.selectedAbsenceContexts.length) return;
            this.absenceResult = null;
            this.absenceModalOpen = false;
            this.absenceConfirmOpen = true;
        },

        async sendTeacherAbsence() {
            if (!this.selectedAbsenceContexts.length) return;
            const retry = Boolean(this.absenceResult && !this.absenceResult.success);
            this.absenceLoading = true;
            this.absenceResult = null;
            const total = { total: 0, sent: 0, failed: 0, skipped: 0, skipped_reasons: [] };
            const messages = [];
            try {
                this.absencePhase = 'Checking SMS gateway...';
                const gatewayResponse = await fetch('{{ route('sms.ping') }}', { headers: { 'Accept': 'application/json' } });
                const gateway = await gatewayResponse.json();
                if (!gateway.online) {
                    this.absenceResult = { success: false, message: 'SMS gateway is offline. No messages were sent.' };
                    return;
                }
                this.absencePhase = 'Sending absence messages...';
                for (const context of this.selectedAbsenceContexts) {
                    const response = await fetch('{{ route('teacher.messages.notify-absence') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ context_type: context.type, schedule_id: context.schedule_id, section_id: context.section_id, absence_date: this.absenceDate, retry })
                    });
                    const data = await response.json();
                    if (data.summary) {
                        total.total += data.summary.total || 0;
                        total.sent += data.summary.sent || 0;
                        total.failed += data.summary.failed || 0;
                        total.skipped += data.summary.skipped || 0;
                        if (data.summary.skipped_reasons) total.skipped_reasons.push(...data.summary.skipped_reasons);
                    }
                    if (data.message) messages.push(context.label + ': ' + data.message);
                }
                this.absenceResult = { success: total.sent > 0 || (total.skipped > 0 && total.failed === 0), message: messages.join(' '), summary: total };
            } catch (error) {
                this.absenceResult = { success: false, message: 'Unable to reach the SMS API. Please try again.' };
            } finally {
                this.absenceLoading = false;
            }
        },

        updateCounts() {
            this.charCount = this.messageText.length;
            this.smsCount = this.messageText.length > 0 ? Math.ceil(this.messageText.length / 160) : 0;
        },

        applyTemplate(key) {
            const date = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            this.selectedTemplateKey = key;
            const specificStudent = document.getElementById('specificStudentSelect')?.selectedOptions?.[0];
            const studentName = this.recipientMode === 'specific_student' && specificStudent?.dataset?.name
                ? specificStudent.dataset.name
                : '[STUDENT_NAME]';
            const section = this.recipientMode === 'specific_student' && specificStudent?.dataset?.section
                ? specificStudent.dataset.section
                : '[SECTION]';
            const templates = {
                absent_today: `Good day. This is to inform you that ${studentName} (${section}) was marked absent today, ${date}. Please contact SIA if you have any questions.`,
                reminder: `Reminding parent/guardian of ${studentName} (${section}) regarding an upcoming school activity/deadline on ${date}. Thank you.`,
                meeting: `Notice of Parent-Teacher Conference: You are invited to attend a meeting regarding ${studentName} (${section}) on ${date}. Your presence is highly appreciated.`,
                exam: `Notice: Upcoming examinations for ${section} will commence on ${date}. Please ensure ${studentName} is well prepared. Thank you.`
            };
            if (templates[key]) {
                this.messageText = templates[key]
                    .replace(/\[DATE\]|\{date\}/g, date)
                    .replace(/\[STUDENT_NAME\]|\{student_name\}/g, studentName)
                    .replace(/\[SECTION\]|\{section\}/g, section);
                this.updateCounts();
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

        async loadHistoryUrl(event) {
            event.preventDefault();
            await this.fetchHistory(event.currentTarget.href);
        },

        async changeHistoryPageSize(event) {
            const form = document.getElementById('filterForm');
            const params = new URLSearchParams(new FormData(form));
            params.set('per_page', event.target.value);
            params.set('page', '1');
            await this.fetchHistory(form.action + '?' + params.toString());
        },

        async fetchHistory(url) {
            const target = new URL(url, window.location.origin);
            target.searchParams.set('partial', '1');
            if (this.$refs.historyBody) {
                this.$refs.historyBody.innerHTML = '<tr><td colspan="7" class="px-6 py-10 text-center text-xs font-bold text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading messages...</td></tr>';
            }
            try {
                const response = await fetch(target.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('History request failed.');
                const payload = await response.json();
                this.renderHistory(payload);
                window.history.replaceState({}, '', target.pathname + '?' + [...target.searchParams].filter(([key]) => key !== 'partial').map(([key, value]) => encodeURIComponent(key) + '=' + encodeURIComponent(value)).join('&'));
            } catch (error) {
                if (this.$refs.historyBody) this.$refs.historyBody.innerHTML = '<tr><td colspan="7" class="px-6 py-10 text-center text-xs font-bold text-red-600">Unable to load message history.</td></tr>';
            }
        },

        escapeHistory(value) {
            return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
        },

        renderHistory(payload) {
            const pagination = payload.pagination;
            if (this.$refs.historyBody) {
                this.$refs.historyBody.innerHTML = payload.messages.length
                    ? payload.messages.map((message, index) => {
                        const status = message.status;
                        const badge = status === 'delivered' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : status === 'sent' ? 'bg-blue-50 text-blue-700 border-blue-200' : status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200';
                        const label = status === 'pending' ? 'Queued' : status === 'sent' ? 'Accepted' : status;
                        return `<tr class="hover:bg-slate-50/70 transition-colors"><td class="px-3 py-4 text-center text-xs font-black text-slate-400">${(pagination.from || 0) + index}</td><td class="px-6 py-4 whitespace-nowrap"><div class="text-xs font-black text-slate-800">${this.escapeHistory(message.created_at)}</div><div class="text-[11px] text-slate-400 font-semibold mt-0.5">${this.escapeHistory(message.time)}</div></td><td class="px-4 py-4"><div class="text-xs font-black text-slate-800">${this.escapeHistory(message.recipient_name)}</div><div class="text-[11px] text-slate-400 font-semibold mt-0.5"><i class="fa-solid fa-phone text-slate-300 text-[10px]"></i> ${this.escapeHistory(message.contact_number)}</div></td><td class="px-4 py-4 max-w-xs"><div class="text-xs text-slate-600 truncate">${this.escapeHistory(message.message)}</div></td><td class="px-4 py-4"><span class="inline-flex px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200">${this.escapeHistory(message.sender)}</span></td><td class="px-4 py-4 text-center"><span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-black ${badge} uppercase">${this.escapeHistory(label)}</span></td><td class="px-6 py-4 text-center"><button type="button" data-message-id="${message.id}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200"><i class="fa-solid fa-eye text-[10px]"></i></button></td></tr>`;
                    }).join('')
                    : '<tr><td colspan="7" class="px-6 py-16 text-center text-xs font-bold text-slate-400">No SMS records found.</td></tr>';
                this.$refs.historyBody.querySelectorAll('[data-message-id]').forEach(button => button.addEventListener('click', () => this.loadDetails(button.dataset.messageId)));
            }
            if (this.$refs.historySummary) this.$refs.historySummary.textContent = `Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total} records`;
            if (this.$refs.historyPagination) {
                const pages = [];
                const historyPageUrl = page => {
                    const pageTarget = new URL(window.location.href);
                    pageTarget.searchParams.set('page', page);
                    pageTarget.searchParams.set('per_page', pagination.per_page);
                    return pageTarget.toString();
                };
                for (let page = Math.max(1, pagination.current_page - 2); page <= Math.min(pagination.last_page, pagination.current_page + 2); page++) pages.push(page);
                this.$refs.historyPagination.querySelector('.justify-end').innerHTML = `<button type="button" data-history-url="${this.escapeHistory(pagination.previous_url || '')}" ${pagination.previous_url ? '' : 'disabled'} class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-chevron-left"></i></button>${pages.map(page => `<button type="button" data-history-url="${this.escapeHistory(historyPageUrl(page))}" class="px-3 py-1.5 rounded-lg border text-xs font-bold ${page === pagination.current_page ? 'bg-[#590d0d] text-amber-300' : 'bg-white text-slate-600 border-slate-200'}">${page}</button>`).join('')}<button type="button" data-history-url="${this.escapeHistory(pagination.next_url || '')}" ${pagination.next_url ? '' : 'disabled'} class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-bold disabled:opacity-40"><i class="fa-solid fa-chevron-right"></i></button>`;
                this.$refs.historyPagination.querySelectorAll('[data-history-url]').forEach(button => button.addEventListener('click', () => button.dataset.historyUrl && this.fetchHistory(button.dataset.historyUrl)));
            }
        },

        async submitSms(event) {
            const form = event.target;
            const btn = form.querySelector('#submitSendSmsBtn');
            const alertBox = document.getElementById('modalAlertBox');
            if (alertBox) alertBox.innerHTML = '';
            this.sendingSms = true;
            this.sendProgressMessage = 'Checking SMS gateway connection...';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending SMS...';
            }

            try {
                const gatewayResponse = await fetch('{{ route('sms.ping') }}', { headers: { 'Accept': 'application/json' } });
                const gateway = await gatewayResponse.json();
                if (!gateway.online) {
                    throw new Error('SMS gateway is offline. No messages were sent.');
                }
                this.sendProgressMessage = 'Gateway online. Sending message...';
                const resp = await fetch('{{ route('sms.send') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(form)
                });
                const data = await resp.json();
                if (resp.ok) {
                    this.sendProgressMessage = 'Message accepted by the SMS gateway.';
                    if (alertBox) alertBox.innerHTML = '<div class="p-3 rounded-xl bg-emerald-50 border-2 border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2"><i class="fa-solid fa-circle-check"></i>' + data.message + '</div>';
                    setTimeout(() => { this.composeOpen = false; window.location.reload(); }, 1500);
                } else {
                    if (alertBox) alertBox.innerHTML = '<div class="p-3 rounded-xl bg-red-50 border-2 border-red-200 text-red-800 text-xs font-bold flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i>' + (data.message || 'Failed to send SMS.') + '</div>';
                }
            } catch(e) {
                const failureMessage = e?.message || 'Network error. Please try again.';
                if (alertBox) alertBox.innerHTML = '<div class="p-3 rounded-xl bg-red-50 border-2 border-red-200 text-red-800 text-xs font-bold">' + failureMessage + '</div>';
            }

            this.sendingSms = false;

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
        const checkedStudents = Array.from(document.querySelectorAll('.student-chk:checked'));
        const selectedStudentIds = checkedStudents.map(chk => chk.value);
        const count = selectedStudentIds.length;
        const root = document.querySelector('[x-data]');
        if (root && root._x_dataStack) {
            root._x_dataStack[0].selectedStudentIds = selectedStudentIds;
            root._x_dataStack[0].selectedCount = count;
        } else if (root && window.Alpine) {
            const alpineData = Alpine.$data(root);
            alpineData.selectedStudentIds = selectedStudentIds;
            alpineData.selectedCount = count;
        }
    }

    document.querySelectorAll('.student-chk').forEach(function(chk) {
        chk.addEventListener('change', updateSelectedCount);
    });

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
        searchStudentsList.addEventListener('keyup', function() {
            const root = document.querySelector('[x-data]');
            if (root && window.Alpine) Alpine.$data(root).filterRecipientItems();
        });
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
