@extends('layouts.app')

@section('title', 'SMS Management - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50">
    
    <!-- Shared SIA admin page header -->
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-comment-sms"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">SMS Management</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Absent-student notifications and SMS gateway monitoring</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div x-data="{ open: false, testing: false, result: null }" class="relative flex items-center gap-2">
                <button type="button" @click="testing = true; result = null; fetch('{{ route('admin.settings.sms.test') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(response => response.json()).then(data => result = data).catch(() => result = { success: false, message: 'Unable to reach SMS gateway.' }).finally(() => testing = false)" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white shadow-sm hover:bg-emerald-700">
                    <i class="fa-solid fa-satellite-dish"></i><span x-text="testing ? 'Pinging...' : 'Ping SMS API'"></span>
                </button>
                <button type="button" @click="open = !open" class="h-9 w-9 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" aria-label="SMS actions">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 top-11 z-30 w-48 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                    <a href="{{ route('admin.settings') }}" class="block rounded-lg px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-gear mr-2 text-slate-400"></i>Configure SMS API</a>
                </div>
                <div x-show="result" x-cloak class="absolute right-0 top-11 z-20 w-72 rounded-xl border bg-white px-3 py-2 text-[10px] font-bold shadow-lg" :class="result?.success ? 'border-emerald-200 text-emerald-700' : 'border-rose-200 text-rose-700'">
                    <i class="fa-solid mr-1" :class="result?.success ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                    <span x-text="result ? result.message : ''"></span>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.sms.sent-today') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $today }}" onchange="this.form.submit()" class="py-2 px-3 text-xs font-bold border-2 border-slate-200 rounded-xl bg-white focus:border-emerald-600 outline-none">
            </form>
        </div>
    </header>

    <div class="flex-1 p-6 lg:px-10 lg:py-8 space-y-6 max-w-[1600px] mx-auto w-full">
    <!-- Flash Notifications -->
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

    <!-- Current SMS Template Notice Box -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-quote-left text-emerald-600"></i>
                <span>SMS Absence Template</span>
            </span>
            <span class="text-[10px] font-black px-2.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">AUTOMATIC ABSENT TRIGGER ONLY</span>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-700 leading-relaxed italic">
            "{{ $defaultTemplate }}"
        </div>
        <div class="flex justify-end">
            <button type="button" onclick="openEditTemplateModal()" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-[11px] font-black text-white hover:bg-slate-700">
                <i class="fa-solid fa-pen-to-square"></i> Edit SMS Absence Template
            </button>
        </div>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 w-full">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">SMS Sent Today</span>
            <h3 class="text-2xl lg:text-3xl font-black text-emerald-600 tracking-tight mt-2">{{ number_format($totalSentToday) }}</h3>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Absent Students</span>
            <h3 class="text-2xl lg:text-3xl font-black text-rose-600 tracking-tight mt-2">{{ number_format($absentStudentsCount) }}</h3>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Successfully Sent</span>
            <h3 class="text-2xl lg:text-3xl font-black text-emerald-700 tracking-tight mt-2">{{ number_format($successSent) }}</h3>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Failed</span>
            <h3 class="text-2xl lg:text-3xl font-black text-rose-600 tracking-tight mt-2">{{ number_format($failedSent) }}</h3>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Pending</span>
            <h3 class="text-2xl lg:text-3xl font-black text-amber-600 tracking-tight mt-2">{{ number_format($pendingSent) }}</h3>
        </div>
    </div>

    <!-- SMS Activity / Traffic Line Graph -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-5 w-full">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">SMS Activity Today</h3>
                <p class="text-xs text-slate-400 font-bold mt-0.5">Hourly distribution of parent absent notifications</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="smsActivityChart"></canvas>
        </div>
    </div>

    <!-- Failed SMS Notifications Section (Quick Action) -->
    @if(count($failedSmsList) > 0)
    <div class="bg-rose-50 p-4 sm:p-5 rounded-2xl border border-rose-200 shadow-sm space-y-5 w-full">
        <div class="flex items-center justify-between pb-4 border-b border-rose-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-rose-950 tracking-tight">Failed SMS Notifications Requiring Attention</h3>
                    <p class="text-xs text-rose-700 font-semibold mt-0.5">Parents who did not successfully receive the absent notification</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-200 text-rose-900 border border-rose-300">{{ count($failedSmsList) }} Failed</span>
        </div>

        <div class="overflow-x-auto rounded-2xl border-2 border-rose-200 bg-white">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-rose-100/70 text-rose-900 uppercase font-black tracking-wider border-b-2 border-rose-200">
                    <tr>
                        <th class="py-4 px-5">Student</th>
                        <th class="py-4 px-5">Parent / Guardian</th>
                        <th class="py-4 px-5">Phone Number</th>
                        <th class="py-4 px-5">Reason</th>
                        <th class="py-4 px-5 text-center">Attempted At</th>
                        <th class="py-4 px-5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rose-100 font-semibold text-slate-800">
                    @foreach($failedSmsList as $fail)
                    <tr class="hover:bg-rose-50/50">
                        <td class="py-4 px-5 font-extrabold text-slate-900">{{ $fail->first_name }} {{ $fail->last_name }}</td>
                        <td class="py-4 px-5 text-slate-700">{{ $fail->parent_name }}</td>
                        <td class="py-4 px-5 font-mono text-slate-600">{{ $fail->masked_phone }}</td>
                        <td class="py-4 px-5 text-rose-600 font-bold">{{ $fail->fail_reason ?? 'Invalid phone number / Gateway timeout' }}</td>
                        <td class="py-4 px-5 text-center font-mono text-slate-600">{{ $fail->time_sent }}</td>
                        <td class="py-4 px-5 text-center">
                            <form action="{{ route('admin.sms.retry', 1) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-xs transition cursor-pointer">
                                    Retry SMS
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Absent Students Notification Status Table -->
    @if(false)
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-5 w-full">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Absent Students Notification Status</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Real-time status of absent notifications dispatched today</p>
            </div>
            <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-extrabold">
                Automatic Dispatch Active
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider border-b-2 border-slate-200">
                    <tr>
                        <th class="py-4 px-5">Student</th>
                        <th class="py-4 px-5 text-center">Student ID</th>
                        <th class="py-4 px-5 text-center">Section</th>
                        <th class="py-4 px-5">Parent / Guardian</th>
                        <th class="py-4 px-5 text-center">Phone Number</th>
                        <th class="py-4 px-5 text-center">Time Sent</th>
                        <th class="py-4 px-5 text-center">SMS Status</th>
                        <th class="py-4 px-5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($absentNotifications ?? [] as $abs)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-4 px-5 font-extrabold text-slate-900">{{ $abs->first_name }} {{ $abs->last_name }}</td>
                        <td class="py-4 px-5 text-center font-mono font-bold text-slate-700">{{ $abs->id_number ?? 'N/A' }}</td>
                        <td class="py-4 px-5 text-center font-bold text-slate-700">Section {{ $abs->section ?? 'N/A' }}</td>
                        <td class="py-4 px-5 text-slate-700">{{ $abs->parent_name }}</td>
                        <td class="py-4 px-5 text-center font-mono text-slate-600">{{ $abs->masked_phone }}</td>
                        <td class="py-4 px-5 text-center font-mono text-slate-700">{{ $abs->time_sent }}</td>
                        <td class="py-4 px-5 text-center">
                            @if(strtoupper($abs->sms_status) === 'SENT')
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">Sent</span>
                            @elseif(strtoupper($abs->sms_status) === 'FAILED')
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">Failed</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">Pending</span>
                            @endif
                        </td>
                        <td class="py-4 px-5 text-center flex items-center justify-center gap-2">
                            <button type="button" onclick="openMessageModal('{{ addslashes($abs->message) }}', '{{ $abs->first_name }} {{ $abs->last_name }}')" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                                View Message
                            </button>
                            @if($abs->sms_status === 'Failed')
                            <form action="{{ route('admin.sms.retry', 1) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer">
                                    Retry
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400 font-bold">
                            <i class="fa-solid fa-circle-check text-3xl text-emerald-500 mb-2"></i>
                            <p>No absent students recorded today. No automated SMS needed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Detailed SMS Log Table -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-5 w-full">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Detailed SMS Logs</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Complete historical and daily database audit log of dispatched parent alerts</p>
            </div>
            <span class="px-3 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-extrabold">
                Audit Trail
            </span>
        </div>

        <form method="GET" action="{{ route('admin.sms.sent-today') }}" class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-4">
            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-700">
                <i class="fa-solid fa-filter text-emerald-600"></i>
                <span>Filter SMS Logs</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <select name="school_year" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border border-slate-200 rounded-xl bg-white focus:border-emerald-600 outline-none cursor-pointer">
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ request('school_year', $activeSchoolYear) == $sy ? 'selected' : '' }}>S.Y. {{ $sy }}</option>
                    @endforeach
                </select>
                <select name="section" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border border-slate-200 rounded-xl bg-white focus:border-emerald-600 outline-none cursor-pointer">
                    <option value="">All Sections</option>
                    @foreach($sections ?? [] as $sec)
                        <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>Section {{ $sec }}</option>
                    @endforeach
                </select>
                <select name="status" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border border-slate-200 rounded-xl bg-white focus:border-emerald-600 outline-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="Sent" {{ request('status') == 'Sent' ? 'selected' : '' }}>Sent</option>
                    <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                </select>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or Student ID..." class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border border-slate-200 rounded-xl bg-white focus:border-emerald-600 outline-none">
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.sms.sent-today') }}" class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-600 text-xs font-extrabold rounded-xl border border-slate-200">Reset</a>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-sm">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Apply Filters
                </button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider border-b-2 border-slate-200">
                    <tr>
                        <th class="py-4 px-4 text-center">#</th>
                        <th class="py-4 px-5">Student</th>
                        <th class="py-4 px-5">Student ID</th>
                        <th class="py-4 px-5">Section</th>
                        <th class="py-4 px-5">Parent / Guardian</th>
                        <th class="py-4 px-5 text-center">Phone Number</th>
                        <th class="py-4 px-5 text-center">Time Sent</th>
                        <th class="py-4 px-5 text-center">Status</th>
                        <th class="py-4 px-5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($detailedLogs ?? [] as $log)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-4 px-4 text-center font-black text-slate-400">{{ method_exists($detailedLogs, 'firstItem') && $detailedLogs->firstItem() ? $detailedLogs->firstItem() + $loop->index : $loop->iteration }}</td>
                        <td class="py-4 px-5 font-extrabold text-slate-900">{{ $log->first_name }} {{ $log->last_name }}</td>
                        <td class="py-4 px-5 font-mono font-bold text-slate-700">{{ $log->id_number ?? 'N/A' }}</td>
                        <td class="py-4 px-5 font-bold text-slate-700">{{ $log->section ?? 'N/A' }}</td>
                        <td class="py-4 px-5 text-slate-700">{{ $log->parent_name }}</td>
                        <td class="py-4 px-5 text-center font-mono text-slate-600">{{ substr($log->phone_number, 0, 2) . '••••••' . substr($log->phone_number, -3) }}</td>
                        <td class="py-4 px-5 text-center font-mono text-slate-600">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</td>
                        <td class="py-4 px-5 text-center">
                            @if(strtoupper($log->status) === 'SENT')
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">Sent</span>
                            @elseif(strtoupper($log->status) === 'PENDING')
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">Pending</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">Failed</span>
                            @endif
                        </td>
                        <td class="py-4 px-5 text-center">
                            <button type="button" onclick="openMessageModal('{{ addslashes($log->message) }}', '{{ $log->first_name }} {{ $log->last_name }}')" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                                View Message
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400 font-bold">
                            <i class="fa-solid fa-folder-open text-3xl text-slate-300 mb-2"></i>
                            <p>No SMS logs found matching the selected parameters.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($detailedLogs ?? null, 'links'))
        <div class="pt-2">
            {{ $detailedLogs->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
</div>
<!-- Modal: Edit SMS Template -->
<div id="editTemplateModal" class="fixed inset-0 z-50 bg-slate-950/60 hidden items-center justify-center p-4 backdrop-blur-xs">
    <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-2xl max-w-xl w-full overflow-hidden flex flex-col">
        <div class="px-6 py-5 bg-slate-50 border-b-2 border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Edit Absence SMS Template</h3>
                    <p class="text-xs text-slate-500 font-semibold">Modify the automated notification sent to parents of absent students</p>
                </div>
            </div>
            <button type="button" onclick="closeEditTemplateModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="{{ route('admin.sms.update-template') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Message Template</label>
                <textarea name="absence_sms_template" id="templateTextarea" rows="4" onkeyup="updateCharCount(this)" required class="w-full p-3.5 text-xs font-medium text-slate-800 border-2 border-slate-200 rounded-xl focus:border-emerald-600 outline-none">{{ $defaultTemplate }}</textarea>
                <div class="flex items-center justify-between mt-1 text-[11px] font-bold text-slate-400">
                    <span>Available Placeholders: <code class="text-emerald-700">{student_name}, {date}, {time}, {section}, {school_name}</code></span>
                    <span id="charCounter">0 / 500 chars</span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeEditTemplateModal()" class="px-5 py-2.5 rounded-xl border-2 border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-extrabold cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition cursor-pointer">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: View Exact SMS Message -->
<div id="viewMessageModal" class="fixed inset-0 z-50 bg-slate-950/60 hidden items-center justify-center p-4 backdrop-blur-xs">
    <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-2xl max-w-md w-full overflow-hidden flex flex-col">
        <div class="px-6 py-5 bg-slate-50 border-b-2 border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-comment-sms"></i>
                </div>
                <div>
                    <h3 id="modalStudentName" class="text-base font-black text-slate-900 tracking-tight">SMS Message Preview</h3>
                    <p class="text-xs text-slate-500 font-semibold">Exact text dispatched to parent/guardian</p>
                </div>
            </div>
            <button type="button" onclick="closeMessageModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="p-4 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-xs font-medium text-emerald-950 leading-relaxed font-mono" id="modalMessageContent">
                <!-- Message content injected via JS -->
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeMessageModal()" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // SMS Activity Line Chart
    const trafficLabels = {!! json_encode(array_keys($hourlyTraffic)) !!};
    const trafficValues = {!! json_encode(array_values($hourlyTraffic)) !!};

    const ctx = document.getElementById('smsActivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trafficLabels,
            datasets: [{
                label: 'SMS Dispatched',
                data: trafficValues,
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#059669',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { weight: 'bold' } }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

    const ta = document.getElementById('templateTextarea');
    if (ta) updateCharCount(ta);
});

function openEditTemplateModal() {
    document.getElementById('editTemplateModal').classList.remove('hidden');
    document.getElementById('editTemplateModal').classList.add('flex');
}

function closeEditTemplateModal() {
    document.getElementById('editTemplateModal').classList.add('hidden');
    document.getElementById('editTemplateModal').classList.remove('flex');
}

function openMessageModal(message, studentName) {
    document.getElementById('modalStudentName').innerText = studentName + ' - SMS Notice';
    document.getElementById('modalMessageContent').innerText = '"' + message + '"';
    document.getElementById('viewMessageModal').classList.remove('hidden');
    document.getElementById('viewMessageModal').classList.add('flex');
}

function closeMessageModal() {
    document.getElementById('viewMessageModal').classList.add('hidden');
    document.getElementById('viewMessageModal').classList.remove('flex');
}

function updateCharCount(textarea) {
    const len = textarea.value.length;
    document.getElementById('charCounter').innerText = len + ' / 500 chars';
}
</script>
@endsection