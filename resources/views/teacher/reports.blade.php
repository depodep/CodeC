@extends('layouts.app')

@section('title', 'Reports & Analytics | SIATRACK')

@section('content')
<div class="p-6 md:p-8 max-w-7xl mx-auto w-full">
    
    <!-- Page Header (SIATRACK Theme) -->
    <div class="mb-8">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-chart-pie text-[#590d0d] text-lg"></i>
            </div>
            Reports & Analytics
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            Generate and view attendance summaries, class lists, and evaluation records.
        </p>
    </div>

    <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="bg-[#590d0d] px-4 py-2.5 text-sm font-bold text-amber-300 flex items-center gap-2">
            <i class="fa-solid fa-magnifying-glass"></i> Report Filters
        </div>
        <div class="p-4 md:p-5">
            <form id="teacher-attendance-report-filters" method="GET" action="{{ route('teacher.reports', [], false) }}" class="overflow-x-auto">
                <div class="grid min-w-[1000px] grid-cols-7 gap-3 items-end">
                <label class="text-xs font-bold text-gray-700">
                    <i class="fa-solid fa-filter mr-1"></i> Report by
                    <select name="report_by" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                        <option value="section" @selected($filters['report_by'] === 'section')>Section</option>
                        <option value="subject" @selected($filters['report_by'] === 'subject')>Subject</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-gray-700">
                    <i class="fa-regular fa-calendar mr-1"></i> Report period
                    <select name="period" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                        <option value="daily" @selected($filters['period'] === 'daily')>Daily</option>
                        <option value="weekly" @selected($filters['period'] === 'weekly')>Weekly</option>
                        <option value="monthly" @selected($filters['period'] === 'monthly')>Monthly</option>
                    </select>
                </label>
                <div id="teacher-report-section-filter" class="{{ $filters['report_by'] === 'section' ? '' : 'hidden' }}">
                    <label class="text-xs font-bold text-gray-700">
                        <i class="fa-solid fa-people-group mr-1"></i> Select section
                        <select name="section_id" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                            @forelse($advisorySections as $section)
                                <option value="{{ $section->id }}" @selected((string)($filters['section_id'] ?? '') === (string)$section->id)>
                                    Grade {{ $section->grade_level }} · {{ $section->section_name }}
                                </option>
                            @empty
                                <option value="">No assigned sections</option>
                            @endforelse
                        </select>
                    </label>
                </div>
                <div id="teacher-report-subject-filter" class="{{ $filters['report_by'] === 'subject' ? '' : 'hidden' }}">
                    <label class="text-xs font-bold text-gray-700">
                        <i class="fa-solid fa-book-open mr-1"></i> Select subject
                        <select name="schedule_id" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                            @forelse($myClasses as $class)
                                <option value="{{ $class->id }}" @selected((string)($filters['schedule_id'] ?? '') === (string)$class->id)>
                                    {{ $class->subject_name ?: $class->subjectRecord?->name ?: $class->subject_code ?: 'Class' }} · {{ $class->academicSection?->section_name ?: $class->section ?: 'Section' }}
                                </option>
                            @empty
                                <option value="">No assigned subjects</option>
                            @endforelse
                        </select>
                    </label>
                </div>
                <label class="text-xs font-bold text-gray-700">
                    <i class="fa-solid fa-calendar-day mr-1"></i>
                    <span id="teacher-report-period-label">{{ $filters['period'] === 'weekly' ? 'Select week' : ($filters['period'] === 'monthly' ? 'Select month' : 'Select date') }}</span>
                    <input id="teacher-report-period-value" type="{{ $filters['period'] === 'weekly' ? 'week' : ($filters['period'] === 'monthly' ? 'month' : 'date') }}" name="period_value" value="{{ $filters['period_value'] }}" data-default-date="{{ now()->toDateString() }}" aria-label="{{ $filters['period'] === 'weekly' ? 'Select week' : ($filters['period'] === 'monthly' ? 'Select month' : 'Select date') }}" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                </label>
                <label class="text-xs font-bold text-gray-700">
                    Status
                    <select name="status" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                        <option value="">All statuses</option>
                        @foreach(['ON-TIME' => 'Present / on time', 'LATE' => 'Late', 'ABSENT' => 'Absent'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold text-gray-700">
                    Rows per page
                    <select name="per_page" class="mt-1 block w-full px-3 py-2 text-xs rounded-md border border-gray-300 bg-white text-gray-800">
                        @foreach([10, 20] as $count)
                            <option value="{{ $count }}" @selected((int)($filters['per_page'] ?? 10) === $count)>{{ $count }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" id="clear-teacher-attendance-filters" class="px-3 py-2 rounded-md border border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-bold">
                        Reset
                    </button>
                </div>
                @if($errors->any())
                    <p class="sm:col-span-2 lg:col-span-7 text-xs font-bold text-red-600">{{ $errors->first() }}</p>
                @endif
                </div>
            </form>
        </div>
    </section>

    <div id="teacher-attendance-report-error" class="hidden mb-4 text-xs font-bold text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3" role="alert"></div>
    @include('teacher.partials.attendance-report-results')

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <a href="{{ route('teacher.students') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4 hover:border-emerald-300 transition">
            <span class="w-11 h-11 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-users-rectangle"></i></span>
            <span><strong class="block text-sm text-gray-800">Class Masterlist</strong><small class="text-xs text-gray-500">View your students and sections.</small></span>
        </a>
        <a href="{{ route('teacher.evaluation.report') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4 hover:border-purple-300 transition">
            <span class="w-11 h-11 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center"><i class="fa-solid fa-star-half-stroke"></i></span>
            <span><strong class="block text-sm text-gray-800">Evaluation Results</strong><small class="text-xs text-gray-500">Review your evaluation summaries.</small></span>
        </a>
    </div>

    <!-- Active Classes Summary Table (Real Data) -->
    <div>
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-book-open text-amber-500"></i>
            My Reporting Sections (Active Classes)
        </h2>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-[#590d0d] text-amber-300 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-4 w-12">#</th>
                            <th class="p-4">Subject / Course</th>
                            <th class="p-4">Section Name</th>
                            <th class="p-4">Schedule</th>
                            <th class="p-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-600 font-medium">
                        @forelse($myClasses as $index => $class)
                            <tr class="hover:bg-amber-50/50 transition duration-150">
                                <td class="p-4 font-bold text-gray-400">{{ $index + 1 }}</td>
                                <td class="p-4 font-black text-[#590d0d]">
                                    {{ $class->subject_name ?: $class->subjectRecord?->name ?: $class->subject_code ?: 'Subject Not Set' }}
                                </td>
                                <td class="p-4">
                                    <span class="bg-[#590d0d]/10 text-[#590d0d] px-2.5 py-1 rounded-md text-xs font-bold border border-[#590d0d]/20">
                                        {{ $class->academicSection?->section_name ?: $class->section ?: 'N/A' }}
                                    </span>
                                </td>
                                <td class="p-4 text-[12px] font-semibold text-gray-500">
                                    <i class="fa-regular fa-clock mr-1 text-amber-500"></i>
                                    {{ $class->start_time ? \Carbon\Carbon::parse($class->start_time)->format('g:i A') : '--:--' }} - {{ $class->end_time ? \Carbon\Carbon::parse($class->end_time)->format('g:i A') : '--:--' }}
                                </td>
                                <td class="p-4 text-center">
                                    <!-- Dinudugtong ang classList view para mas detalyadong report -->
                                    <a href="{{ route('teacher.reports', ['report_by' => 'subject', 'period' => 'daily', 'report_date' => now()->toDateString(), 'schedule_id' => $class->id]) }}" class="inline-flex w-8 h-8 rounded-lg bg-teal-50 text-teal-600 border border-teal-200 hover:bg-teal-500 hover:text-white transition items-center justify-center" title="View schedule attendance">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <!-- Kung walang assigned classes sa teacher sa database -->
                            <tr>
                                <td colspan="5" class="p-10 text-center text-gray-400 font-medium text-sm">
                                    <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3 border border-gray-100">
                                        <i class="fa-solid fa-box-open text-3xl opacity-50"></i>
                                    </div>
                                    No active class sections assigned to you for reporting.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3.5 bg-gray-50 border-t border-gray-100 text-xs font-semibold text-gray-500 flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-amber-500"></i> 
                Select a schedule to view its recorded subject attendance and time-ins.
            </div>
        </div>
    </div>

</div>
<script>
(() => {
    const form = document.getElementById('teacher-attendance-report-filters');
    const error = document.getElementById('teacher-attendance-report-error');
    if (!form) return;
    let activeRequest;

    function toggleReportSelection() {
        const reportBy = form.elements.report_by?.value || 'section';
        const sectionFilter = document.getElementById('teacher-report-section-filter');
        const subjectFilter = document.getElementById('teacher-report-subject-filter');
        if (sectionFilter) sectionFilter.classList.toggle('hidden', reportBy !== 'section');
        if (subjectFilter) subjectFilter.classList.toggle('hidden', reportBy !== 'subject');
    }

    function dateFromPeriodValue(period, value) {
        if (period === 'weekly') {
            const match = value.match(/^(\d{4})-W(\d{2})$/);
            if (!match) return new Date();
            const date = new Date(Number(match[1]), 0, 4);
            date.setDate(date.getDate() - ((date.getDay() + 6) % 7) + (Number(match[2]) - 1) * 7);
            return date;
        }
        if (period === 'monthly') return new Date(`${value}-01T12:00:00`);
        return new Date(`${value}T12:00:00`);
    }

    function periodValueForDate(date, period) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        if (period === 'monthly') return `${year}-${month}`;
        if (period === 'weekly') {
            const utcDate = new Date(Date.UTC(year, date.getMonth(), date.getDate()));
            const weekday = utcDate.getUTCDay() || 7;
            utcDate.setUTCDate(utcDate.getUTCDate() + 4 - weekday);
            const isoYear = utcDate.getUTCFullYear();
            const yearStart = new Date(Date.UTC(isoYear, 0, 1));
            const week = Math.ceil((((utcDate - yearStart) / 86400000) + 1) / 7);
            return `${isoYear}-W${String(week).padStart(2, '0')}`;
        }
        return `${year}-${month}-${day}`;
    }

    function syncPeriodInput(date = null) {
        const period = form.elements.period.value;
        const input = document.getElementById('teacher-report-period-value');
        const previousPeriod = input.dataset.period || period;
        const selectedDate = date || dateFromPeriodValue(previousPeriod, input.value);
        input.type = period === 'weekly' ? 'week' : (period === 'monthly' ? 'month' : 'date');
        input.value = periodValueForDate(selectedDate, period);
        input.setAttribute('aria-label', period === 'weekly' ? 'Select week' : (period === 'monthly' ? 'Select month' : 'Select date'));
        document.getElementById('teacher-report-period-label').textContent = period === 'weekly' ? 'Select week' : (period === 'monthly' ? 'Select month' : 'Select date');
        input.dataset.period = period;
    }

    async function refreshResults(pushState = true, targetUrl = null) {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const url = targetUrl ? new URL(targetUrl, window.location.href) : new URL(form.action);
        if (!targetUrl) {
            new FormData(form).forEach((value, key) => {
                if (value !== '') url.searchParams.set(key, value);
            });
            url.searchParams.set('page', '1');
        }
        error.classList.add('hidden');
        document.getElementById('teacher-attendance-report-results')?.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: activeRequest.signal
            });
            if (!response.ok) {
                const problem = await response.json().catch(() => ({}));
                throw new Error(Object.values(problem.errors ?? {}).flat()[0] ?? problem.message ?? 'The report could not be updated. Please try again.');
            }
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updated = page.getElementById('teacher-attendance-report-results');
            const current = document.getElementById('teacher-attendance-report-results');
            if (!updated || !current) throw new Error('The report response was incomplete. Please reload the page.');
            current.replaceWith(updated);
            if (pushState) history.pushState({}, '', url);
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                error.textContent = exception instanceof TypeError
                    ? 'Could not reach the attendance report. Check the server connection and reload the page.'
                    : exception.message;
                error.classList.remove('hidden');
            }
        } finally {
            document.getElementById('teacher-attendance-report-results')?.removeAttribute('aria-busy');
        }
    }

    toggleReportSelection();
    syncPeriodInput(dateFromPeriodValue(form.elements.period.value, document.getElementById('teacher-report-period-value').value));
    form.addEventListener('submit', event => {
        event.preventDefault();
        refreshResults();
    });
    form.addEventListener('change', event => {
        if (event.target.name === 'report_by') {
            toggleReportSelection();
        } else if (event.target.name === 'period') {
            syncPeriodInput();
        }
        refreshResults();
    });
    document.getElementById('clear-teacher-attendance-filters').addEventListener('click', () => {
        form.querySelectorAll('[name]').forEach(field => {
            if (field.name === 'report_by') {
                field.value = 'section';
            } else if (field.name === 'period') {
                field.value = 'daily';
            } else if (field.name === 'period_value') {
                field.value = field.dataset.defaultDate;
            } else if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });
        toggleReportSelection();
        syncPeriodInput(new Date(`${document.getElementById('teacher-report-period-value').dataset.defaultDate}T12:00:00`));
        refreshResults();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('#teacher-attendance-report-results a[href]');
        if (!link) return;
        const pageUrl = new URL(link.href, window.location.href);
        if (pageUrl.origin !== window.location.origin || pageUrl.pathname !== window.location.pathname || !pageUrl.searchParams.has('page')) return;
        event.preventDefault();
        refreshResults(true, pageUrl.href);
    });
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        form.querySelectorAll('[name]').forEach(field => {
            if (field.name !== 'period_value' && params.has(field.name)) field.value = params.get(field.name);
            else if (field.name === 'report_by' && !params.has(field.name)) field.value = 'section';
            else if (field.name === 'period' && !params.has(field.name)) field.value = 'daily';
            else if (field.name === 'period_value') return;
            else if (field.tagName === 'SELECT') field.selectedIndex = 0;
            else if (field.name === 'per_page') field.value = '10';
            else field.value = '';
        });
        toggleReportSelection();
        const periodValue = params.get('period_value');
        syncPeriodInput(periodValue
            ? dateFromPeriodValue(form.elements.period.value, periodValue)
            : new Date(`${document.getElementById('teacher-report-period-value').dataset.defaultDate}T12:00:00`));
        refreshResults(false, window.location.href);
    });
})();
</script>
@endsection