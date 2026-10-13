@extends('layouts.app')

@section('title', 'Faculty Evaluation Management - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60 font-sans">

    @php
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $academicStart = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
        $academicEnd = $academicStart + 1;
        $activeAcademicYear = $academicStart . '-' . $academicEnd;

        $totalStudents = \App\Models\User::where('role_id', 3)->count();
        $evalProgress = $evalProgress ?? 0;
    @endphp

    <!-- ================= Top Professional Header Bar ================= -->
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shadow-red-950/10 shrink-0">
                <i class="fa-solid fa-award text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Faculty Evaluation Management</h1>
                    <span class="px-3 py-1 rounded-lg text-xs font-black bg-amber-50 text-amber-900 border border-amber-200 uppercase tracking-wider">
                        A.Y. {{ $activeSchoolYear ?? '2027-2028' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Manage 360-degree evaluation forms (Student, Peer, Self), criteria, and analytical insights</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2.5 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-200 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-black text-emerald-800 uppercase tracking-wide">Session Active</span>
            </div>
        </div>
    </header>

    <!-- ================= Main Content Container ================= -->
    <main class="p-6 lg:p-10 w-full space-y-8 flex-1 max-w-[1600px] mx-auto">

        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs font-bold rounded-xl flex items-center gap-3 shadow-xs w-full">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- ================= KPI Summary Metric Cards ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 w-full">
            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Overall Average Score</p>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight mt-1 font-mono">
                            {{ $averageScore > 0 ? number_format((float)$averageScore, 1) : '0.0' }} <span class="text-sm text-slate-400 font-bold">/ 5.0</span>
                        </h3>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 text-[#8b1818] flex items-center justify-center text-lg shadow-2xs">
                        <i class="fa-solid fa-star"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-500">
                    <span>Institutional Benchmark</span>
                    <span class="text-emerald-600 font-black">Passed</span>
                </div>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Total Submissions</p>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight mt-1 font-mono">{{ number_format($totalEvaluations ?? 0) }}</h3>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-lg shadow-2xs">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-500">
                    <span>Reviews Logged</span>
                    <span class="text-blue-600 font-black">Synced</span>
                </div>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Faculty Evaluated</p>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight mt-1 font-mono">{{ number_format($totalFaculty ?? 0) }}</h3>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-lg shadow-2xs">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-500">
                    <span>Active Personnel</span>
                    <span class="text-amber-600 font-black">Monitored</span>
                </div>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider">DepEd Schedule</p>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight mt-1">3-Term Framework</h3>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-lg shadow-2xs">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-500">
                    <span>Active Cycle</span>
                    <span class="text-emerald-700 font-black">Term 1</span>
                </div>
            </div>
        </div>

        <!-- ================= Control Center & Criteria Builder Grid ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full">
            <div class="lg:col-span-7 bg-white p-6 lg:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6 flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-black">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight">Evaluation Control Center</h2>
                    </div>
                    <span class="text-xs font-extrabold text-slate-400 uppercase">System Settings</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                    <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Evaluation State</span>
                            <span id="statusLabel" class="{{ \Illuminate\Support\Facades\Cache::get('evaluations_open', false) ? 'text-emerald-700' : 'text-slate-500' }} text-xs font-black uppercase tracking-tight flex items-center gap-1.5 mt-1">
                                <span class="w-2 h-2 rounded-full {{ \Illuminate\Support\Facades\Cache::get('evaluations_open', false) ? 'bg-emerald-500' : 'bg-slate-400' }}"></span> 
                                {{ \Illuminate\Support\Facades\Cache::get('evaluations_open', false) ? 'ACTIVE' : 'CLOSED' }}
                            </span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="evalToggle" {{ \Illuminate\Support\Facades\Cache::get('evaluations_open', false) ? 'checked' : '' }} onchange="confirmToggleEval(this)" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#8b1818]"></div>
                        </label>
                    </div>

                    <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Active Period</span>
                        <div class="flex items-center gap-2 pt-1">
                            <select class="w-full py-1.5 px-2 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-800 outline-none focus:border-[#8b1818]">
                                <option selected>{{ $activeAcademicYear }}</option>
                            </select>
                            <select class="w-full py-1.5 px-2 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-800 outline-none focus:border-[#8b1818]">
                                <option>Term 1</option>
                                <option>Term 2</option>
                                <option>Term 3</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between text-xs font-black">
                        <span class="text-slate-700 uppercase tracking-wider text-[11px]">Student Submission Progress</span>
                        <span class="text-[#8b1818] font-mono">{{ $evalProgress }}% Complete</span>
                    </div>
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200 p-0.5">
                        <div class="h-full bg-gradient-to-r from-[#8b1818] to-amber-500 rounded-full transition-all duration-500" style="width: {{ $evalProgress }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 bg-white p-6 lg:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6 flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center text-sm font-black border border-amber-200/60">
                            <i class="fa-solid fa-file-pen"></i>
                        </div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight">Criteria & Reports</h2>
                    </div>
                    <span class="text-xs font-extrabold text-slate-400 uppercase">Framework</span>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <button type="button" onclick="openCriteriaModal()" class="w-full py-3.5 px-5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-[#8b1818] text-slate-800 font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-2xs transition flex items-center justify-between cursor-pointer group">
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-list-check text-[#8b1818] text-sm"></i>
                            <span>Edit Evaluation Criteria / Forms</span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-slate-400 group-hover:text-[#8b1818] transition"></i>
                    </button>

                    <a href="{{ route('admin.reports.evaluation') }}" class="w-full py-3.5 px-5 bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-sm transition flex items-center justify-center gap-2.5">
                        <i class="fa-solid fa-file-arrow-down text-sm text-amber-300"></i>
                        <span>Export Full Report (CSV / PDF)</span>
                    </a>
                </div>

                <div class="text-center">
                    <p class="text-[11px] text-slate-400 font-semibold">Configures rubrics for student, peer, and self-evaluation forms.</p>
                </div>
            </div>
        </div>

        <!-- ================= Search & Filter Toolbar with Section Filter ================= -->
        <form method="GET" action="{{ url()->current() }}" class="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-4 items-start md:items-center justify-between w-full">
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <select name="section" onchange="this.form.submit()" class="py-2.5 px-3.5 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-700 focus:bg-white focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Sections</option>
                    @if(isset($sections))
                        @foreach($sections as $sec)
                            <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>Section: {{ $sec }}</option>
                        @endforeach
                    @endif
                </select>

                @if(request()->hasAny(['section', 'rating_range', 'search']))
                    <a href="{{ url()->current() }}" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-black transition shrink-0 shadow-2xs inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif
            </div>

            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search teacher or student name..." 
                       class="w-full pl-9 pr-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none bg-slate-50 focus:bg-white transition shadow-2xs">
            </div>
        </form>

        <!-- ================= 3-WAY EVALUATION FORM TAB NAVIGATION ================= -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex flex-wrap items-center gap-3">
                <button onclick="switchTab('student')" id="tabStudentBtn" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm">
                    <i class="fa-solid fa-graduation-cap mr-1.5"></i> Student Evaluations
                </button>
                <button onclick="switchTab('peer')" id="tabPeerBtn" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">
                    <i class="fa-solid fa-users mr-1.5"></i> Faculty Peer Evaluations
                </button>
                <button onclick="switchTab('self')" id="tabSelfBtn" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">
                    <i class="fa-solid fa-user-pen mr-1.5"></i> Faculty Self Evaluations
                </button>
            </div>

            <button type="button" onclick="toggleAnonymousMode()" id="anonToggleBtn" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-black uppercase tracking-wider transition flex items-center gap-2 cursor-pointer shadow-2xs">
                <i class="fa-solid fa-user-secret" id="anonIcon"></i>
                <span id="anonBtnText">Anonymous Mode: OFF</span>
            </button>
        </div>

        <!-- Include Separated Tab Views from evaluations folder -->
        @include('admin.evaluations.student')
        @include('admin.evaluations.peer')
        @include('admin.evaluations.self')

    </main>
</div>

@include('admin.evaluations.modals')

@push('scripts')
<script>
    let isAnonymous = false;

    function toggleAnonymousMode() {
        isAnonymous = !isAnonymous;
        const btn = document.getElementById('anonToggleBtn');
        const icon = document.getElementById('anonIcon');
        const btnText = document.getElementById('anonBtnText');
        const nameElements = document.querySelectorAll('.student-name-text');

        if (isAnonymous) {
            btn.className = "px-4 py-2 rounded-xl bg-[#8b1818] text-white text-xs font-black uppercase tracking-wider transition flex items-center gap-2 cursor-pointer shadow-sm";
            icon.className = "fa-solid fa-user-secret text-amber-300";
            btnText.innerText = "Anonymous Mode: ON";
            nameElements.forEach(el => { el.innerText = "🔒 Anonymous Student"; });
        } else {
            btn.className = "px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-black uppercase tracking-wider transition flex items-center gap-2 cursor-pointer shadow-2xs";
            icon.className = "fa-solid fa-user-secret";
            btnText.innerText = "Anonymous Mode: OFF";
            nameElements.forEach(el => { el.innerText = el.getAttribute('data-real-name'); });
        }
    }

    function switchTab(tab) {
        const studentBtn = document.getElementById('tabStudentBtn');
        const peerBtn = document.getElementById('tabPeerBtn');
        const selfBtn = document.getElementById('tabSelfBtn');
        const anonToggleBtn = document.getElementById('anonToggleBtn');

        const studentContent = document.getElementById('tabStudentContent');
        const peerContent = document.getElementById('tabPeerContent');
        const selfContent = document.getElementById('tabSelfContent');

        [studentBtn, peerBtn, selfBtn].forEach(btn => {
            btn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-white text-slate-600 hover:bg-slate-100 border border-slate-200";
        });
        [studentContent, peerContent, selfContent].forEach(content => content.classList.add('hidden'));

        if (tab === 'student') {
            studentBtn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm";
            studentContent.classList.remove('hidden');
            anonToggleBtn.classList.remove('hidden');
        } else if (tab === 'peer') {
            peerBtn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm";
            peerContent.classList.remove('hidden');
            anonToggleBtn.classList.remove('hidden');
        } else if (tab === 'self') {
            selfBtn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm";
            selfContent.classList.remove('hidden');
            anonToggleBtn.classList.add('hidden');
        }
    }

    function openFullReportModal(teacherName, section, score, comment) {
        document.getElementById('reportTeacherName').innerText = "Report: " + teacherName;
        document.getElementById('reportMeta').innerText = "Class Section: " + section;
        document.getElementById('reportScoreDisplay').innerText = "★ " + score.toFixed(1) + " / 5.0";
        document.getElementById('reportComment').innerText = '"' + comment + '"';

        const modal = document.getElementById('fullReportModal');
        const container = document.getElementById('fullReportContainer');
        modal.classList.remove('hidden');
        setTimeout(() => { container.classList.remove('scale-95', 'opacity-0'); container.classList.add('scale-100', 'opacity-100'); }, 10);
    }

    function closeFullReportModal() {
        const modal = document.getElementById('fullReportModal');
        const container = document.getElementById('fullReportContainer');
        container.classList.remove('scale-100', 'opacity-100');
        container.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    let pendingToggleState = true;
    let toggleElementRef = null;

    function confirmToggleEval(checkbox) {
        toggleElementRef = checkbox;
        pendingToggleState = checkbox.checked;
        const modal = document.getElementById('evalModal');
        const container = document.getElementById('modalContainer');
        document.getElementById('modalTitle').innerText = pendingToggleState ? "Activate Evaluation Session?" : "Deactivate Evaluation Session?";
        document.getElementById('modalDesc').innerText = pendingToggleState ? "Turning this ON will allow submissions across all forms." : "Turning this OFF will close evaluation submissions instantly.";
        modal.classList.remove('hidden');
        setTimeout(() => { container.classList.remove('scale-95', 'opacity-0'); container.classList.add('scale-100', 'opacity-100'); }, 10);
    }

    function closeEvalModal(confirmed) {
        const modal = document.getElementById('evalModal');
        const container = document.getElementById('modalContainer');
        const statusLabel = document.getElementById('statusLabel');

        if (confirmed && toggleElementRef) {
            fetch('/admin/evaluations/toggle-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: pendingToggleState })
            }).then(r => r.json()).then(data => {
                if (data.is_open) {
                    statusLabel.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span> ACTIVE';
                    statusLabel.className = "text-xs font-black text-emerald-700 uppercase tracking-tight flex items-center gap-1.5 mt-1";
                } else {
                    statusLabel.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-400"></span> CLOSED';
                    statusLabel.className = "text-xs font-black text-slate-500 uppercase tracking-tight flex items-center gap-1.5 mt-1";
                }
            });
        } else if (toggleElementRef) {
            toggleElementRef.checked = !pendingToggleState;
        }

        container.classList.remove('scale-100', 'opacity-100');
        container.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    function openCriteriaModal() {
        const modal = document.getElementById('criteriaModal');
        const container = document.getElementById('criteriaModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => { container.classList.remove('scale-95', 'opacity-0'); container.classList.add('scale-100', 'opacity-100'); }, 10);
    }

    function closeCriteriaModal() {
        const modal = document.getElementById('criteriaModal');
        const container = document.getElementById('criteriaModalContainer');
        container.classList.remove('scale-100', 'opacity-100');
        container.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }
</script>
@endpush
@endsection