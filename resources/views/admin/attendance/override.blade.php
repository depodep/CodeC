@extends('layouts.app')

@section('title', 'Attendance Record - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70"
     x-data="overrideManager()">

    {{-- ===== HEADER ===== --}}
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.attendance') }}"
               class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Attendance Record</h1>
                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-black uppercase">Admin Only</span>
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Review attendance records by subject or advisory section</p>
            </div>
        </div>
    </header>

    {{-- ===== OVERRIDE MODAL ===== --}}
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display:none;">
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-md w-full p-6 shadow-2xl relative"
             @click.away="closeModal()">
            <button @click="closeModal()"
                    class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="flex items-center gap-3 mb-5">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Override Attendance</h3>
                    <p class="text-xs text-slate-500 font-semibold" x-text="selectedLog ? selectedLog.name : ''"></p>
                </div>
            </div>

            <template x-if="selectedLog">
                <form :action="'/admin/attendance/' + selectedLog.id + '/override'" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="date" value="{{ request('date', now()->toDateString()) }}">
                    <input type="hidden" name="section" value="{{ request('section') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="attendance_type" value="{{ $attendanceType }}">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <input type="hidden" name="advisory_section_id" value="{{ request('advisory_section_id') }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Time In</label>
                        <input type="time" name="time_in" :value="selectedLog.time_in_raw"
                               class="w-full px-3 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Time Out</label>
                        <input type="time" name="time_out" :value="selectedLog.time_out_raw"
                               class="w-full px-3 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Status</label>
                        <select name="status"
                                class="w-full px-3 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800">
                            <option value="ON-TIME" :selected="selectedLog.status_upper === 'ON-TIME'">On-Time</option>
                            <option value="LATE"    :selected="selectedLog.status_upper === 'LATE'">Late</option>
                            <option value="ABSENT"  :selected="selectedLog.status_upper === 'ABSENT'">Absent</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Required Reason</label>
                        <textarea name="override_reason" rows="2" required placeholder="Explain why this record must be corrected..."
                                  class="w-full px-3 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800 placeholder:text-slate-400 resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Administrator Password</label>
                        <input type="password" name="admin_password" required autocomplete="current-password"
                               placeholder="Confirm with your admin password"
                               class="w-full px-3 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800 placeholder:text-slate-400">
                        @error('admin_password')
                            <p class="mt-1 text-[11px] font-bold text-red-600">{{ $message }}</p>
                        @enderror
                        @error('override_reason')
                            <p class="mt-1 text-[11px] font-bold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <button type="button" @click="closeModal()"
                                class="py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs transition shadow-xs cursor-pointer">
                            <i class="fa-solid fa-check mr-1"></i> Apply Override
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <main class="p-6 lg:p-10 max-w-[1500px] mx-auto space-y-6">

        {{-- Flash --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-900 text-xs font-bold rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-600 text-base shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                <p class="mt-1 ml-7 text-[11px]">Reopen the record and submit the required reason and administrator password.</p>
            </div>
        @endif

        {{-- Info notice --}}
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 shrink-0"></i>
            <p class="text-xs font-bold text-amber-900">
                <span class="font-black">Override records with care.</span>
                All changes are logged in the audit trail. Only correct records when there is a valid reason (e.g., NFC reader error, student forgot to tap out).
            </p>
        </div>

        {{-- Filter Bar --}}
        <form id="attendance-record-filters" method="GET" action="{{ route('admin.attendance.record') }}"
              class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Date</label>
                <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}"
                       data-auto-submit
                       class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
            </div>
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Record Type</label>
                <select name="attendance_type" data-auto-submit class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                    <option value="advisory" @selected($attendanceType === 'advisory')>Advisory</option>
                    <option value="subject" @selected($attendanceType === 'subject')>Subject</option>
                </select>
            </div>
            @if($attendanceType === 'subject')
                <div class="flex flex-col gap-1 min-w-[190px]">
                    <label class="text-[10px] font-black uppercase text-slate-400">Subject</label>
                    <select name="subject_id" data-auto-submit class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) request('subject_id') === (string) $subject->id)>
                                {{ $subject->code }} — {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="flex flex-col gap-1 min-w-[190px]">
                    <label class="text-[10px] font-black uppercase text-slate-400">Advisory Section</label>
                    <select name="advisory_section_id" data-auto-submit class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700">
                        <option value="">All Advisory Sections</option>
                        @foreach($advisorySections as $advisorySection)
                            <option value="{{ $advisorySection->id }}" @selected((string) request('advisory_section_id') === (string) $advisorySection->id)>
                                Grade {{ $advisorySection->grade_level }} — {{ $advisorySection->section_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
                <label class="text-[10px] font-black uppercase text-slate-400">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or ID number..."
                       data-auto-submit-input
                       class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700 placeholder:text-slate-400">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition cursor-pointer">
                    <i class="fa-solid fa-search mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['date','section','search','attendance_type','subject_id','advisory_section_id']))
                    <a id="attendance-record-clear" href="{{ route('admin.attendance.record') }}"
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i> Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Records Table --}}
        <div id="attendance-record-content">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            @if($logs->isEmpty())
                <div class="py-20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto mb-4">
                        <i class="fa-solid fa-clipboard"></i>
                    </div>
                    <p class="text-sm font-black text-slate-500">No records to override</p>
                    <p class="text-xs text-slate-400 font-semibold mt-1">No attendance logs found for the selected filters.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    @php
                        $isSubject = $attendanceType === 'subject';
                        $sortLink = function (string $column) use ($sort, $direction) {
                            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
                            return request()->fullUrlWithQuery([
                                'sort' => $column,
                                'direction' => $nextDirection,
                                'page' => 1,
                            ]);
                        };
                    @endphp
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">#</th>
                                @foreach([
                                    'student' => 'Student',
                                    'section' => 'Section',
                                    'date' => 'Date',
                                    'time_in' => $isSubject ? 'Scanned Time' : 'Time In',
                                    'status' => 'Status',
                                ] as $column => $label)
                                    <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">
                                        <a href="{{ $sortLink($column) }}" class="inline-flex items-center gap-1.5 hover:text-[#8b1818] transition">
                                            {{ $label }}
                                            <i class="fa-solid fa-sort text-[9px] {{ $sort === $column ? 'text-[#8b1818]' : 'text-slate-300' }}"></i>
                                        </a>
                                    </th>
                                @endforeach
                                @unless($isSubject)
                                    <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">
                                        <a href="{{ $sortLink('time_out') }}" class="inline-flex items-center gap-1.5 hover:text-[#8b1818] transition">
                                            Time Out
                                            <i class="fa-solid fa-sort text-[9px] {{ $sort === 'time_out' ? 'text-[#8b1818]' : 'text-slate-300' }}"></i>
                                        </a>
                                    </th>
                                @endunless
                                <th class="px-5 py-3.5 font-black text-slate-500 uppercase tracking-wider text-[10px]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($logs as $log)
                            @php
                                $statusUp  = strtoupper($log->status ?? '');
                                $dateVal   = $log->attendance_date ?? $log->date ?? $log->created_at ?? null;
                                $logData   = [
                                    'id'           => $log->attendance_log_id,
                                    'name'         => trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')),
                                    'id_number'    => $log->id_number ?? null,
                                    'section'      => $log->section ?? null,
                                    'status'       => $log->status ?? '',
                                    'status_upper' => $statusUp,
                                    'time_in_raw'  => isset($log->time_in)  && $log->time_in  ? \Carbon\Carbon::parse($log->time_in)->format('H:i')  : '',
                                    'time_out_raw' => isset($log->time_out) && $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : '',
                                    'time_in_fmt'  => isset($log->time_in)  && $log->time_in  ? \Carbon\Carbon::parse($log->time_in)->format('g:i A')  : null,
                                    'time_out_fmt' => isset($log->time_out) && $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : null,
                                    'date_fmt'     => $dateVal ? \Carbon\Carbon::parse($dateVal)->format('M d, Y') : '—',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5 font-black text-slate-400">
                                    {{ ($logs->firstItem() ?? 1) + $loop->index }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-black text-slate-900">{{ trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: '—' }}</p>
                                    <p class="text-[11px] font-bold text-slate-400 mt-0.5">{{ $log->id_number ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">{{ $log->section ?? '--' }}</td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">{{ $logData['date_fmt'] }}</td>
                                <td class="px-5 py-3.5 font-bold text-slate-600">{{ $logData['time_in_fmt'] ?? '--' }}</td>
                                @unless($isSubject)
                                    <td class="px-5 py-3.5 font-bold text-slate-600">{{ $logData['time_out_fmt'] ?? '--' }}</td>
                                @endunless
                                <td class="px-5 py-3.5">
                                    @if($log->attendance_log_id)
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-black text-[10px] uppercase">Present</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg bg-red-50 border border-red-200 text-red-700 font-black text-[10px] uppercase">Absent</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($log->attendance_log_id)
                                    <button type="button"
                                            @click="openOverride({{ json_encode($logData) }})"
                                            class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 font-black text-[10px] uppercase tracking-wide transition cursor-pointer flex items-center gap-1.5">
                                        <i class="fa-solid fa-pen text-[9px]"></i> Override
                                    </button>
                                    @else
                                        <span class="text-[10px] font-black uppercase text-slate-400">No record</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($logs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="pt-4 border-t border-slate-200 flex flex-col lg:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-4 text-xs font-bold text-slate-500">
                    <span>Showing <span class="text-slate-800">{{ $logs->firstItem() ?? 0 }}</span> to <span class="text-slate-800">{{ $logs->lastItem() ?? 0 }}</span> of <span class="text-slate-800">{{ $logs->total() }}</span> students</span>
                    <label class="inline-flex items-center gap-2 whitespace-nowrap">
                        <span>Rows per page</span>
                        <select data-auto-submit name="per_page" form="attendance-record-filters"
                                class="px-2 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-black focus:outline-none focus:border-[#8b1818]">
                            @foreach([10, 25, 50] as $pageSize)
                                <option value="{{ $pageSize }}" @selected((int) request('per_page', 10) === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <nav class="flex items-center gap-2" aria-label="Attendance record pagination">
                    @if($logs->onFirstPage())
                        <span class="w-8 h-8 rounded-lg border border-slate-200 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-chevron-left text-[10px]"></i></span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 flex items-center justify-center"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
                    @endif
                    <span class="px-2 text-xs font-black text-slate-700">{{ $logs->currentPage() }} / {{ $logs->lastPage() }}</span>
                    @if($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 flex items-center justify-center"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                    @else
                        <span class="w-8 h-8 rounded-lg border border-slate-200 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-chevron-right text-[10px]"></i></span>
                    @endif
                </nav>
            </div>
        @endif

        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
function overrideManager() {
    return {
        showModal: false,
        selectedLog: null,
        openOverride(log) {
            this.selectedLog = log;
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
            this.selectedLog = null;
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('attendance-record-filters') ||
        !document.getElementById('attendance-record-content')) return;

    let requestController = null;
    let searchTimer = null;

    const loadAttendanceContent = async (url, pushHistory = true) => {
        if (requestController) requestController.abort();
        requestController = new AbortController();
        const currentContent = document.getElementById('attendance-record-content');
        const currentForm = document.getElementById('attendance-record-filters');
        if (!currentContent || !currentForm) return;
        currentContent.classList.add('opacity-50', 'pointer-events-none');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: requestController.signal
            });
            if (!response.ok) throw new Error(`Attendance record request failed (${response.status})`);

            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const nextContent = parsed.querySelector('#attendance-record-content');
            const nextForm = parsed.querySelector('#attendance-record-filters');
            if (!nextContent || !nextForm) throw new Error('Attendance record content was not returned.');

            currentContent.replaceWith(nextContent);
            currentForm.replaceWith(nextForm);
            if (pushHistory) window.history.pushState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('[ATTENDANCE RECORD]', error);
                window.location.assign(url);
            }
        } finally {
            requestController = null;
            const refreshedContent = document.getElementById('attendance-record-content');
            if (refreshedContent) refreshedContent.classList.remove('opacity-50', 'pointer-events-none');
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('#attendance-record-filters');
        if (!form) return;
        event.preventDefault();
        const params = new URLSearchParams(new FormData(form));
        params.delete('page');
        loadAttendanceContent(`${form.action}?${params.toString()}`);
    });

    document.addEventListener('change', (event) => {
        const control = event.target.closest('#attendance-record-filters [data-auto-submit]');
        if (!control) return;
        control.form.requestSubmit();
    });

    document.addEventListener('input', (event) => {
        const control = event.target.closest('#attendance-record-filters [data-auto-submit-input]');
        if (!control) return;
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => control.form.requestSubmit(), 350);
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('#attendance-record-content a, #attendance-record-clear');
        if (!link || !link.href || link.target === '_blank') return;
        event.preventDefault();
        loadAttendanceContent(link.href);
    });

    window.addEventListener('popstate', () => loadAttendanceContent(window.location.href, false));
});
</script>
@endpush
