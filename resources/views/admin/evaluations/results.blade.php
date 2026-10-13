@extends('layouts.app')

@section('title', 'Faculty Evaluation Analytics & Results - SIATRACK Admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div x-data="{
        mainTab: 'faculty', // 'faculty' or 'submissions'
        detailModal: false,
        answersModal: false,
        selectedAnswersSub: null,
        fromDetailModal: false,
        modalTab: 'submissions', // 'submissions', 'breakdown', 'feedback'
        selectedFaculty: null,
        submissionsFilter: 'all',
        submissionsSearch: '',
        allSubmissionsList: {{ json_encode($allSubmissions ?? []) }},

        openBreakdown(faculty, tab = 'submissions') {
            this.selectedFaculty = faculty;
            this.modalTab = tab;
            this.detailModal = true;
        },

        openAnswersModal(sub) {
            this.selectedAnswersSub = sub;
            this.fromDetailModal = this.detailModal; // remember if we came from detailModal
            this.detailModal = false;               // close detailModal first
            this.answersModal = true;
        },

        closeAnswersModal() {
            this.answersModal = false;
            this.selectedAnswersSub = null;
            if (this.fromDetailModal) {
                this.detailModal = true;            // go back to detailModal if that's where we came from
                this.fromDetailModal = false;
            }
        },

        get filteredSubmissions() {
            let list = this.allSubmissionsList || [];
            if (this.submissionsFilter !== 'all') {
                list = list.filter(s => s.type === this.submissionsFilter);
            }
            if (this.submissionsSearch && this.submissionsSearch.trim() !== '') {
                const query = this.submissionsSearch.toLowerCase().trim();
                list = list.filter(s => 
                    (s.evaluator_name && s.evaluator_name.toLowerCase().includes(query)) ||
                    (s.evaluatee_name && s.evaluatee_name.toLowerCase().includes(query)) ||
                    (s.evaluator_id_number && s.evaluator_id_number.toLowerCase().includes(query)) ||
                    (s.comments && s.comments.toLowerCase().includes(query)) ||
                    (s.type_label && s.type_label.toLowerCase().includes(query))
                );
            }
            return list;
        }
    }" 
    class="w-full min-h-screen bg-slate-50/70 pb-16">

    <!-- Header -->
    <header class="bg-white border-b-2 border-slate-200 pl-8 lg:pl-12 pr-6 lg:pr-8 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('admin.evaluations.periods') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition" title="Back to Periods">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Faculty Evaluation Results</h1>
                    @if(isset($selectedCycle) && $selectedCycle)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300/60">
                            SY {{ $selectedCycle->school_year }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">
                    @if(isset($selectedCycle) && $selectedCycle)
                        Showing results for: <span class="text-slate-900 font-extrabold">{{ $selectedCycle->name }}</span> ({{ \Carbon\Carbon::parse($selectedCycle->start_date)->format('M d') }} &ndash; {{ \Carbon\Carbon::parse($selectedCycle->end_date)->format('M d, Y') }})
                    @else
                        Evaluated data from evaluators and teacher score publishing
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('admin.evaluations.results') }}" class="flex items-center gap-2">
                <select name="cycle_id" onchange="this.form.submit()" class="px-4 py-2.5 rounded-xl bg-white border-2 border-slate-200 text-xs font-black text-slate-800 outline-none focus:border-[#8b1818]">
                    <option value="">-- Select Evaluation Cycle --</option>
                    @foreach($allCycles ?? [] as $cycleItem)
                        <option value="{{ $cycleItem->id }}" {{ (isset($selectedCycle) && $selectedCycle->id == $cycleItem->id) ? 'selected' : '' }}>
                            {{ $cycleItem->name }} (SY {{ $cycleItem->school_year }})
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.evaluations.history') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-wider transition">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> History
            </a>
        </div>
    </header>

    <main class="max-w-[1700px] mx-auto pt-6 px-4 sm:px-6 lg:px-8 space-y-6">
        
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        <!-- Top Quantitative Number Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                <p class="text-[10px] font-black uppercase text-slate-400">Institutional Mean</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h3 class="text-2xl font-black text-[#8b1818]">{{ $overallInstMean }}</h3>
                    <span class="text-xs font-bold text-slate-400">/ 100</span>
                </div>
                <span class="text-[10px] font-bold text-slate-500 mt-0.5 block">Weighted Global Average</span>
            </div>

            <div class="bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                <p class="text-[10px] font-black uppercase text-slate-400">Completion Rate</p>
                <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $completionRate }}%</h3>
                <span class="text-[10px] font-bold text-emerald-600 mt-0.5 block">{{ $totalEvaluated }} of {{ $totalFaculty }} Evaluated</span>
            </div>

            <div class="bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                <p class="text-[10px] font-black uppercase text-slate-400">Total Submissions</p>
                <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $totalSubmissions }}</h3>
                <span class="text-[10px] font-bold text-slate-500 mt-0.5 block">Evaluations Recorded</span>
            </div>

            <div class="bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                <p class="text-[10px] font-black uppercase text-slate-400">Publish Status</p>
                <h3 class="text-2xl font-black text-blue-700 mt-0.5">{{ $publishedCount }} / {{ $totalFaculty }}</h3>
                <span class="text-[10px] font-bold text-slate-500 mt-0.5 block truncate" title="{{ $lastPublishedAt ? 'Last Published: ' . $lastPublishedAt->format('M d, Y h:i A') : 'No results published yet' }}">
                    {{ $lastPublishedAt ? 'Last: ' . $lastPublishedAt->format('M d, Y') : 'Not Published' }}
                </span>
            </div>

            <div class="bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                <p class="text-[10px] font-black uppercase text-slate-400">Highest Mean</p>
                <h3 class="text-2xl font-black text-emerald-700 mt-0.5">{{ $highestScore }}</h3>
                <span class="text-[10px] font-bold text-slate-500 mt-0.5 block">Top Benchmark</span>
            </div>

            <div class="bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                <p class="text-[10px] font-black uppercase text-slate-400">Lowest Mean</p>
                <h3 class="text-2xl font-black text-amber-700 mt-0.5">{{ $lowestScore }}</h3>
                <span class="text-[10px] font-bold text-slate-500 mt-0.5 block">Needs Attention</span>
            </div>
        </div>

        @if(isset($selectedCycle) && $selectedCycle)
            <div class="flex flex-wrap items-center gap-2 px-1 text-[10px] font-black uppercase tracking-wider text-slate-500">
                <span class="mr-1">Current weights:</span>
                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">Student {{ $weights['student'] }}%</span>
                <span class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-800 border border-purple-200">Principal {{ $weights['principal'] }}%</span>
                <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">Self {{ $weights['self'] }}%</span>
                <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-800 border border-blue-200">Peer {{ $weights['peer'] }}%</span>
            </div>
        @endif

        <!-- Visual Analytics Graphs Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Graph 1: Rating Distribution (Doughnut Chart) -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-5 shadow-xs flex flex-col justify-between space-y-3">
                <div>
                    <h3 class="text-base font-black text-slate-900">Rating Distribution</h3>
                    <p class="text-xs text-slate-500 font-bold">Faculty categorization for this evaluation</p>
                </div>
                <div class="relative w-full h-48 flex items-center justify-center">
                    <canvas id="distributionChart"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-[11px] font-bold">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#10b981]"></span> Outstanding ({{ $distributionValues[0] }})</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#3b82f6]"></span> Very Satisfactory ({{ $distributionValues[1] }})</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b]"></span> Satisfactory ({{ $distributionValues[2] }})</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span> Needs Imp. ({{ $distributionValues[3] }})</span>
                </div>
            </div>

            <!-- Graph 2: Individual Faculty Comparison (Bar Chart) -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-5 shadow-xs lg:col-span-2 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Faculty Comparative Performance</h3>
                        <p class="text-xs text-slate-500 font-bold">Weighted faculty score benchmark (out of 100)</p>
                    </div>
                    <span class="text-xs font-black text-slate-400">Scale 0 - 100</span>
                </div>
                <div class="relative w-full h-56">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
        </div>

        <!-- MAIN VIEW SWITCHER TABS: Faculty Performance vs. Evaluator Submissions Feed -->
        <div class="flex items-center gap-3 border-b-2 border-slate-200 pt-2">
            <button type="button" 
                    @click="mainTab = 'faculty'"
                    :class="mainTab === 'faculty' ? 'border-[#8b1818] text-[#8b1818] bg-white' : 'border-transparent text-slate-500 hover:text-slate-800 bg-transparent'"
                    class="px-5 py-3 border-b-2 -mb-[2px] font-black text-xs uppercase tracking-wider flex items-center gap-2 rounded-t-xl transition">
                <i class="fa-solid fa-users-viewfinder text-sm"></i>
                <span>Faculty Performance Summary</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="mainTab === 'faculty' ? 'bg-red-100 text-[#8b1818]' : 'bg-slate-200 text-slate-600'">
                    {{ $totalFaculty }}
                </span>
            </button>

            <button type="button" 
                    @click="mainTab = 'submissions'"
                    :class="mainTab === 'submissions' ? 'border-[#8b1818] text-[#8b1818] bg-white' : 'border-transparent text-slate-500 hover:text-slate-800 bg-transparent'"
                    class="px-5 py-3 border-b-2 -mb-[2px] font-black text-xs uppercase tracking-wider flex items-center gap-2 rounded-t-xl transition">
                <i class="fa-solid fa-file-signature text-sm"></i>
                <span>Evaluator Submissions Stream</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="mainTab === 'submissions' ? 'bg-red-100 text-[#8b1818]' : 'bg-slate-200 text-slate-600'">
                    {{ $totalSubmissions }}
                </span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 1: FACULTY PERFORMANCE SUMMARY TABLE  -->
        <!-- ========================================== -->
        <div x-show="mainTab === 'faculty'" class="space-y-4">
            <!-- Search & Bulk Publish Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white border-2 border-slate-200/90 rounded-3xl p-3.5 shadow-xs">
                <form method="GET" action="{{ route('admin.evaluations.results') }}" class="relative max-w-md w-full">
                    @if(isset($selectedCycle) && $selectedCycle)
                        <input type="hidden" name="cycle_id" value="{{ $selectedCycle->id }}">
                    @endif
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Search faculty name or email..." 
                           class="w-full pl-11 pr-4 py-2 rounded-2xl border-2 border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 placeholder-slate-400 focus:border-[#8b1818] outline-none shadow-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 text-xs"></i>
                </form>

                @if(isset($selectedCycle) && $selectedCycle)
                    <div class="flex items-center gap-2.5 shrink-0">
                        <form method="POST" action="{{ route('admin.evaluations.toggle-all-publish') }}" onsubmit="return confirm('Publish evaluation results for ALL faculty members in this cycle?');">
                            @csrf
                            <input type="hidden" name="evaluation_cycle_id" value="{{ $selectedCycle->id }}">
                            <input type="hidden" name="is_published" value="1">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-white"></i>
                                <span>Publish All</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.evaluations.toggle-all-publish') }}" onsubmit="return confirm('Unpublish evaluation results for ALL faculty members in this cycle?');">
                            @csrf
                            <input type="hidden" name="evaluation_cycle_id" value="{{ $selectedCycle->id }}">
                            <input type="hidden" name="is_published" value="0">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-black text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-2">
                                <i class="fa-solid fa-circle-xmark text-rose-400"></i>
                                <span>Unpublish All</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Detailed Table with Numbering & Submissions Action -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead>
                            <tr class="border-b-2 border-slate-200 bg-slate-50/80 text-[11px] font-black uppercase text-slate-500 tracking-wider">
                                <th class="py-3.5 px-3 text-center w-10 whitespace-nowrap">#</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Faculty Member</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Faculty ID / Email</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Evaluations Submitted</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Weighted Score</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Mean Score</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Descriptive Rating</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Published Status</th>
                                <th class="py-3.5 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-800">
                            @forelse($facultyMetrics as $index => $member)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono font-bold">{{ $index + 1 }}</td>
                                    <td class="py-2.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-red-100 border border-red-200 text-[#8b1818] font-black text-[11px] flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h4 class="font-extrabold text-slate-900 leading-tight text-xs">{{ $member->name }}</h4>
                                                @if(!empty($member->is_adviser))
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200" title="Assigned Section Adviser">
                                                        <i class="fa-solid fa-user-shield text-[9px] text-amber-600"></i>
                                                        <span>{{ $member->role_display }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-[10px] font-bold text-slate-500">{{ $member->role_display ?? 'Subject Teacher' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-4 whitespace-nowrap">
                                        <p class="text-xs font-mono text-slate-700 leading-tight">{{ $member->email }}</p>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">ID: {{ $member->id_number }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <button type="button" 
                                                @click="openBreakdown({{ json_encode($member) }}, 'submissions')"
                                                class="group flex flex-col items-center justify-center gap-1 mx-auto cursor-pointer focus:outline-none"
                                                title="Click to view all evaluator submissions for {{ $member->name }}">
                                            <!-- Top Line: Total Submitted Pill -->
                                            <span class="px-3 py-1 rounded-full bg-slate-900 text-white font-black text-xs shadow-xs group-hover:bg-[#8b1818] transition flex items-center gap-1.5">
                                                <i class="fa-solid fa-file-circle-check text-[11px] text-amber-300"></i>
                                                <span>{{ $member->total_submissions }} Submitted</span>
                                            </span>

                                            <!-- Bottom Line: Evaluator Type Breakdown (Principal, Peer, Student, Self) -->
                                            <div class="flex items-center justify-center gap-1 text-[10px] font-extrabold mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-800 border border-purple-200/80" title="Principal Evaluations">Principal: {{ $member->principal_count ?? 0 }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200/80" title="Peer Evaluations">Peer: {{ $member->peer_count ?? 0 }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200/80" title="Student Evaluations">Student: {{ $member->student_count ?? 0 }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200/80" title="Self Evaluations">Self: {{ $member->self_count ?? 0 }}</span>
                                            </div>
                                        </button>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <span class="text-base font-black text-[#8b1818]">{{ number_format($member->weighted_score, 2) }}</span>
                                        <span class="block text-[10px] font-bold text-slate-400">/ 100</span>
                                        <div class="flex items-center justify-center gap-1 mt-1 text-[9px] font-black">
                                            <span class="text-emerald-700" title="Student weighted contribution">S {{ $member->student_weighted !== null ? number_format($member->student_weighted, 1) : '--' }}</span>
                                            <span class="text-purple-700" title="Principal weighted contribution">P {{ $member->principal_weighted !== null ? number_format($member->principal_weighted, 1) : '--' }}</span>
                                            <span class="text-amber-700" title="Self weighted contribution">SE {{ $member->self_weighted !== null ? number_format($member->self_weighted, 1) : '--' }}</span>
                                            <span class="text-blue-700" title="Peer weighted contribution">PE {{ $member->peer_weighted !== null ? number_format($member->peer_weighted, 1) : '--' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                        @if($member->overall_mean !== null || $member->peer_avg !== null)
                                            <div class="inline-flex items-baseline gap-1">
                                                <span class="text-xs font-black text-slate-900">{{ number_format($member->overall_mean ?? $member->peer_avg, 2) }}</span>
                                                <span class="text-[10px] font-bold text-slate-400">/ 5.00</span>
                                            </div>
                                        @else
                                            <span class="text-xs font-bold text-slate-400">--</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black border {{ $member->badge_class }}">
                                            {{ $member->descriptor }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                        @if(isset($selectedCycle) && $selectedCycle)
                                            <form method="POST" action="{{ route('admin.evaluations.toggle-publish') }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="evaluation_cycle_id" value="{{ $selectedCycle->id }}">
                                                <input type="hidden" name="teacher_id" value="{{ $member->id }}">
                                                <input type="hidden" name="is_published" value="{{ $member->is_published ? 0 : 1 }}">
                                                
                                                @if($member->is_published)
                                                    <button type="submit" class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300 hover:bg-emerald-200 transition flex items-center gap-1 mx-auto" title="Click to Unpublish result (Published on {{ $member->published_at ? $member->published_at->format('M d, Y') : 'Unknown' }})">
                                                        <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i>
                                                        <span>Published</span>
                                                    </button>
                                                @else
                                                    <button type="submit" class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-slate-100 text-slate-600 border border-slate-300 hover:bg-slate-200 transition flex items-center gap-1 mx-auto" title="Click to Publish result to Teacher Dashboard">
                                                        <i class="fa-solid fa-circle-xmark text-slate-400 text-[10px]"></i>
                                                        <span>Unpublished</span>
                                                    </button>
                                                @endif
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Select Cycle</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap">
                                        <button @click="openBreakdown({{ json_encode($member) }}, 'submissions')" 
                                                class="px-3.5 py-1.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white text-[11px] font-black uppercase tracking-wider transition shadow-xs flex items-center gap-1.5 ml-auto">
                                            <i class="fa-solid fa-eye text-amber-300"></i>
                                            <span>View Submissions</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-xs font-bold text-slate-400">
                                        No faculty records found matching your search query.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 2: ALL EVALUATOR SUBMISSIONS STREAM   -->
        <!-- ========================================== -->
        <div x-show="mainTab === 'submissions'" class="space-y-4">
            
            <!-- Filters & Search Bar for Evaluator Submissions -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white border-2 border-slate-200 rounded-3xl p-4 shadow-xs">
                
                <!-- Category Filter Pills -->
                <div class="flex flex-wrap items-center gap-2 text-xs font-black">
                    <button type="button"
                            @click="submissionsFilter = 'all'"
                            :class="submissionsFilter === 'all' ? 'bg-[#8b1818] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3.5 py-1.5 rounded-xl transition uppercase text-[11px]">
                        All ({{ count($allSubmissions) }})
                    </button>
                    <button type="button"
                            @click="submissionsFilter = 'student'"
                            :class="submissionsFilter === 'student' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100'"
                            class="px-3.5 py-1.5 rounded-xl transition uppercase text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Students ({{ $allSubmissions->where('type', 'student')->count() }})</span>
                    </button>
                    <button type="button"
                            @click="submissionsFilter = 'peer'"
                            :class="submissionsFilter === 'peer' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100'"
                            class="px-3.5 py-1.5 rounded-xl transition uppercase text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-user-tie"></i>
                        <span>Peers ({{ $allSubmissions->where('type', 'peer')->count() }})</span>
                    </button>
                    <button type="button"
                            @click="submissionsFilter = 'self'"
                            :class="submissionsFilter === 'self' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100'"
                            class="px-3.5 py-1.5 rounded-xl transition uppercase text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-user-check"></i>
                        <span>Self ({{ $allSubmissions->where('type', 'self')->count() }})</span>
                    </button>
                    <button type="button"
                            @click="submissionsFilter = 'principal'"
                            :class="submissionsFilter === 'principal' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 text-purple-800 border border-purple-200 hover:bg-purple-100'"
                            class="px-3.5 py-1.5 rounded-xl transition uppercase text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Principal ({{ $allSubmissions->where('type', 'principal')->count() }})</span>
                    </button>
                </div>

                <!-- Instant Submissions Search Filter -->
                <div class="relative max-w-sm w-full">
                    <input type="text" 
                           x-model="submissionsSearch"
                           placeholder="Filter by evaluator, teacher, comments..." 
                           class="w-full pl-10 pr-4 py-2 rounded-2xl border-2 border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 placeholder-slate-400 focus:border-[#8b1818] outline-none shadow-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Master Submissions Feed Table -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1050px]">
                        <thead>
                            <tr class="border-b-2 border-slate-200 bg-slate-50/80 text-[11px] font-black uppercase text-slate-500 tracking-wider">
                                <th class="py-3.5 px-3 text-center w-10">#</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Evaluator</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Target Faculty (Evaluatee)</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Evaluator Type</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Score Given</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Date Submitted</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Written Feedback / Remarks</th>
                                <th class="py-3.5 px-4 text-center whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-800">
                            <template x-for="(sub, sIdx) in filteredSubmissions" :key="sub.id || sIdx">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-3 text-center text-slate-400 font-mono font-bold" x-text="sIdx + 1"></td>
                                    
                                    <!-- Evaluator Info -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-black text-[11px] flex items-center justify-center shrink-0">
                                                <i :class="'fa-solid ' + sub.icon"></i>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-slate-900 leading-tight text-xs" x-text="sub.evaluator_name"></p>
                                                <p class="text-[10px] font-bold text-slate-400" x-text="sub.evaluator_role + (sub.evaluator_id_number ? ' • ID: ' + sub.evaluator_id_number : '')"></p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Faculty Being Evaluated -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-red-50 border border-red-200 text-[#8b1818] font-black text-[10px] flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-chalkboard-user"></i>
                                            </div>
                                            <span class="font-bold text-slate-900" x-text="sub.evaluatee_name"></span>
                                        </div>
                                    </td>

                                    <!-- Evaluator Type Badge -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <span :class="'px-2.5 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider inline-flex items-center gap-1.5 ' + sub.badge_class">
                                            <i :class="'fa-solid ' + sub.icon + ' text-[10px]'"></i>
                                            <span x-text="sub.type_label"></span>
                                        </span>
                                    </td>

                                    <!-- Score -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <div class="inline-flex items-baseline gap-1">
                                            <span class="text-sm font-black text-slate-900" x-text="Number(sub.score).toFixed(2)"></span>
                                            <span class="text-[10px] font-bold text-slate-400">/ 5.00</span>
                                        </div>
                                        <span :class="'block text-[9px] font-black px-2 py-0.5 rounded mt-0.5 ' + sub.descriptor_class" x-text="sub.descriptor"></span>
                                    </td>

                                    <!-- Submission Date -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap text-slate-600 font-mono text-[11px]" x-text="sub.date_formatted"></td>

                                    <!-- Qualitative Comments -->
                                    <td class="py-3 px-4 max-w-sm">
                                        <template x-if="sub.comments && sub.comments.trim() !== ''">
                                            <p class="text-xs font-semibold text-slate-700 italic" :title="sub.comments">
                                                "<span x-text="sub.comments"></span>"
                                            </p>
                                        </template>
                                        <template x-if="!sub.comments || sub.comments.trim() === ''">
                                            <span class="text-slate-300 italic text-[11px]">No written feedback provided</span>
                                        </template>
                                    </td>

                                    <!-- Action Button -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <button type="button"
                                                @click="openAnswersModal(sub)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-[#8b1818] text-white text-[11px] font-black transition shadow-xs group">
                                            <i class="fa-solid fa-list-ol text-[10px] text-slate-300 group-hover:text-white transition"></i>
                                            <span>View Answers</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredSubmissions.length === 0">
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-xs font-bold text-slate-400">
                                        No evaluation submissions found matching your selected filter.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- ============================================================== -->
    <!-- MODAL: DETAILED FACULTY SUBMISSIONS, APPRAISAL & REMARKS       -->
    <!-- ============================================================== -->
    <div x-show="detailModal" x-cloak @click.self="detailModal = false" class="fixed inset-0 overflow-hidden bg-slate-950/70 flex items-center justify-center p-4" style="z-index: 50;">
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-3xl w-full p-6 sm:p-8 space-y-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto overscroll-contain">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b pb-4 border-slate-100">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 border-2 border-red-200 text-[#8b1818] font-black text-base flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-900 leading-tight" x-text="selectedFaculty ? selectedFaculty.name : ''"></h3>
                        <p class="text-xs font-bold text-slate-500 mt-0.5">
                            <span x-text="selectedFaculty && selectedFaculty.id_number ? 'ID: ' + selectedFaculty.id_number + ' • ' : ''"></span>
                            <span x-text="selectedFaculty ? selectedFaculty.email : ''"></span>
                            <template x-if="selectedFaculty && selectedFaculty.role_display">
                                <span class="ml-1 text-[#8b1818] font-extrabold" x-text="'(' + selectedFaculty.role_display + ')'"></span>
                            </template>
                        </p>
                    </div>
                </div>
                <button @click="detailModal = false" class="text-slate-400 hover:text-slate-600 text-lg transition p-1"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Score Summary Cards in Modal -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Total Submissions</span>
                    <p class="text-lg font-black text-slate-900 mt-0.5" x-text="selectedFaculty ? (selectedFaculty.total_submissions || 0) + ' Submitted' : '0'"></p>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Calculated Mean</span>
                    <p class="text-lg font-black text-[#8b1818] mt-0.5" x-text="selectedFaculty && (selectedFaculty.overall_mean || selectedFaculty.peer_avg) ? Number(selectedFaculty.overall_mean || selectedFaculty.peer_avg).toFixed(2) + ' / 5.0' : '--'"></p>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Weighted Score</span>
                    <p class="text-lg font-black text-emerald-700 mt-0.5" x-text="selectedFaculty ? Number(selectedFaculty.weighted_score).toFixed(2) + ' / 100' : '--'"></p>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Rating Bracket</span>
                    <p class="text-xs font-black text-slate-800 mt-1.5 truncate" x-text="selectedFaculty ? selectedFaculty.descriptor : '--'"></p>
                </div>
            </div>

            <!-- Modal Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-slate-200 pt-1">
                <button type="button" 
                        @click="modalTab = 'submissions'"
                        :class="modalTab === 'submissions' ? 'border-[#8b1818] text-[#8b1818]' : 'border-transparent text-slate-400 hover:text-slate-600'"
                        class="px-4 py-2 border-b-2 -mb-[1px] font-black text-xs uppercase tracking-wider flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Evaluator Submissions</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700" x-text="selectedFaculty && selectedFaculty.submissions ? selectedFaculty.submissions.length : 0"></span>
                </button>

                <button type="button" 
                        @click="modalTab = 'breakdown'"
                        :class="modalTab === 'breakdown' ? 'border-[#8b1818] text-[#8b1818]' : 'border-transparent text-slate-400 hover:text-slate-600'"
                        class="px-4 py-2 border-b-2 -mb-[1px] font-black text-xs uppercase tracking-wider flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Appraisal Weights</span>
                </button>

                <button type="button" 
                        @click="modalTab = 'feedback'"
                        :class="modalTab === 'feedback' ? 'border-[#8b1818] text-[#8b1818]' : 'border-transparent text-slate-400 hover:text-slate-600'"
                        class="px-4 py-2 border-b-2 -mb-[1px] font-black text-xs uppercase tracking-wider flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-comments"></i>
                    <span>Qualitative Feedback</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700" x-text="selectedFaculty && selectedFaculty.comments ? selectedFaculty.comments.length : 0"></span>
                </button>
            </div>

            <!-- TAB 1: LIST OF SUBMISSIONS BY EVALUATORS -->
            <div x-show="modalTab === 'submissions'" class="space-y-3">
                <template x-if="selectedFaculty && selectedFaculty.submissions && selectedFaculty.submissions.length > 0">
                    <div class="space-y-3">
                        <template x-for="(sub, index) in selectedFaculty.submissions" :key="sub.id || index">
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 hover:border-slate-300 transition space-y-2.5">
                                
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span :class="'px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider inline-flex items-center gap-1 ' + sub.badge_class">
                                            <i :class="'fa-solid ' + sub.icon + ' text-[9px]'"></i>
                                            <span x-text="sub.type_label"></span>
                                        </span>
                                        <span class="text-xs font-black text-slate-900" x-text="sub.evaluator_name"></span>
                                        <span class="text-[10px] font-bold text-slate-400" x-text="sub.evaluator_role + (sub.evaluator_id_number ? ' (' + sub.evaluator_id_number + ')' : '')"></span>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400 font-mono" x-text="sub.date_formatted"></span>
                                </div>

                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black uppercase text-slate-400">Score Given:</span>
                                        <span class="text-sm font-black text-slate-900" x-text="Number(sub.score).toFixed(2) + ' / 5.00'"></span>
                                        <span :class="'text-[10px] font-black px-2 py-0.5 rounded ' + sub.descriptor_class" x-text="sub.descriptor"></span>
                                    </div>
                                    <button type="button"
                                            @click="openAnswersModal(sub)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-900 hover:bg-[#8b1818] text-white text-[11px] font-black transition shadow-xs group">
                                        <i class="fa-solid fa-list-ol text-[10px] text-slate-300 group-hover:text-white transition"></i>
                                        <span>View Answers</span>
                                    </button>
                                </div>

                                <template x-if="sub.comments && sub.comments.trim() !== ''">
                                    <div class="p-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 italic">
                                        "<span x-text="sub.comments"></span>"
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!selectedFaculty || !selectedFaculty.submissions || selectedFaculty.submissions.length === 0">
                    <div class="py-12 text-center text-xs font-bold text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-file-circle-xmark text-2xl text-slate-300 mb-2 block"></i>
                        No submitted evaluation forms recorded for this faculty member yet.
                    </div>
                </template>
            </div>

            <!-- TAB 2: WEIGHT BREAKDOWN -->
            <div x-show="modalTab === 'breakdown'" class="space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                        <span class="text-[10px] font-black uppercase tracking-wider block text-emerald-700">Student ({{ $weights['student'] }}%)</span>
                        <p class="text-lg font-black mt-1" x-text="selectedFaculty && selectedFaculty.student_avg !== null ? Number(selectedFaculty.student_avg).toFixed(2) + ' / 5.0' : '--'"></p>
                        <span class="text-[10px] font-bold text-emerald-600 block mt-0.5" x-text="selectedFaculty && selectedFaculty.student_weighted !== null ? Number(selectedFaculty.student_weighted).toFixed(1) + ' pts' : '--'"></span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900">
                        <span class="text-[10px] font-black uppercase tracking-wider block text-purple-700">Principal ({{ $weights['principal'] }}%)</span>
                        <p class="text-lg font-black mt-1" x-text="selectedFaculty && selectedFaculty.principal_avg !== null ? Number(selectedFaculty.principal_avg).toFixed(2) + ' / 5.0' : '--'"></p>
                        <span class="text-[10px] font-bold text-purple-600 block mt-0.5" x-text="selectedFaculty && selectedFaculty.principal_weighted !== null ? Number(selectedFaculty.principal_weighted).toFixed(1) + ' pts' : '--'"></span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900">
                        <span class="text-[10px] font-black uppercase tracking-wider block text-amber-700">Self ({{ $weights['self'] }}%)</span>
                        <p class="text-lg font-black mt-1" x-text="selectedFaculty && selectedFaculty.self_avg !== null ? Number(selectedFaculty.self_avg).toFixed(2) + ' / 5.0' : '--'"></p>
                        <span class="text-[10px] font-bold text-amber-600 block mt-0.5" x-text="selectedFaculty && selectedFaculty.self_weighted !== null ? Number(selectedFaculty.self_weighted).toFixed(1) + ' pts' : '--'"></span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900">
                        <span class="text-[10px] font-black uppercase tracking-wider block text-blue-700">Peer ({{ $weights['peer'] }}%)</span>
                        <p class="text-lg font-black mt-1" x-text="selectedFaculty && selectedFaculty.peer_avg !== null ? Number(selectedFaculty.peer_avg).toFixed(2) + ' / 5.0' : '--'"></p>
                        <span class="text-[10px] font-bold text-blue-600 block mt-0.5" x-text="selectedFaculty && selectedFaculty.peer_weighted !== null ? Number(selectedFaculty.peer_weighted).toFixed(1) + ' pts' : '--'"></span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
                    <p class="font-extrabold text-slate-900">Formula & Weighted Score Calculation</p>
                    <p class="font-mono text-[11px] text-slate-500">Weighted Score = (Student / 5 * {{ $weights['student'] }}) + (Principal / 5 * {{ $weights['principal'] }}) + (Self / 5 * {{ $weights['self'] }}) + (Peer / 5 * {{ $weights['peer'] }})</p>
                </div>
            </div>

            <!-- TAB 3: QUALITATIVE FEEDBACK & COMMENTS -->
            <div x-show="modalTab === 'feedback'" class="space-y-3">
                <template x-if="selectedFaculty && selectedFaculty.comments && selectedFaculty.comments.length > 0">
                    <div class="space-y-2.5">
                        <template x-for="(comment, index) in selectedFaculty.comments" :key="index">
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 space-y-1.5">
                                <p class="italic">"<span x-text="comment.text || comment"></span>"</p>
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 not-italic pt-1 border-t border-slate-200/50">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-user text-[9px]"></i>
                                        <span class="text-slate-800 font-extrabold" x-text="comment.author || 'Anonymous'"></span>
                                        <span x-text="comment.role ? ' • ' + comment.role : ''"></span>
                                    </div>
                                    <span class="font-mono" x-text="comment.date || ''"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!selectedFaculty || !selectedFaculty.comments || selectedFaculty.comments.length === 0">
                    <div class="py-10 text-center text-xs font-bold text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        No qualitative comments or feedback submitted for this faculty yet.
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end pt-3 border-t border-slate-100">
                <button type="button" @click="detailModal = false" class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 uppercase transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: EVALUATOR'S QUESTION-BY-QUESTION ANSWERS & RATINGS       -->
    <!-- ============================================================== -->
    <div x-show="answersModal" x-cloak @click.self="closeAnswersModal()" class="fixed inset-0 overflow-hidden bg-slate-950/70 flex items-center justify-center p-4" style="z-index: 60;">
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-3xl w-full p-6 sm:p-8 space-y-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto overscroll-contain">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                            Evaluator Response Sheet
                        </span>
                        <template x-if="selectedAnswersSub">
                            <span :class="'px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider inline-flex items-center gap-1 ' + selectedAnswersSub.badge_class">
                                <i :class="'fa-solid ' + selectedAnswersSub.icon + ' text-[9px]'"></i>
                                <span x-text="selectedAnswersSub.type_label"></span>
                            </span>
                        </template>
                    </div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight" x-text="selectedAnswersSub ? selectedAnswersSub.evaluator_name : 'Evaluator Answers'"></h2>
                    <p class="text-xs font-semibold text-slate-500">
                        Evaluating: <span class="font-extrabold text-slate-900" x-text="selectedAnswersSub ? selectedAnswersSub.evaluatee_name : '--'"></span>
                        <template x-if="selectedAnswersSub && selectedAnswersSub.date_formatted">
                            <span> &bull; Submitted on <span class="font-mono text-slate-700" x-text="selectedAnswersSub.date_formatted"></span></span>
                        </template>
                    </p>
                </div>
                <button type="button" @click="closeAnswersModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition shrink-0">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Score Summary Card -->
            <template x-if="selectedAnswersSub">
                <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-50 to-slate-100/60 border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-800 font-black shadow-xs">
                            <i :class="'fa-solid ' + selectedAnswersSub.icon + ' text-lg text-slate-700'"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase text-slate-400 block tracking-wider">Overall Average Given</span>
                            <div class="flex items-baseline gap-1.5 mt-0.5">
                                <span class="text-2xl font-black text-slate-900" x-text="Number(selectedAnswersSub.score).toFixed(2)"></span>
                                <span class="text-xs font-bold text-slate-400">/ 5.00</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 block tracking-wider">Descriptor Rating</span>
                        <span :class="'inline-block text-xs font-black px-3 py-1 rounded-xl mt-1 ' + selectedAnswersSub.descriptor_class" x-text="selectedAnswersSub.descriptor"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 block tracking-wider">Questions Answered</span>
                        <span class="text-sm font-black text-slate-800 mt-1 block" x-text="(selectedAnswersSub.question_responses ? selectedAnswersSub.question_responses.length : 0) + ' Questions'"></span>
                    </div>
                </div>
            </template>

            <!-- Qualitative Remarks if provided -->
            <template x-if="selectedAnswersSub && selectedAnswersSub.comments && selectedAnswersSub.comments.trim() !== ''">
                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-1">
                    <span class="text-[10px] font-black uppercase text-amber-800 tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-comment-dots text-xs"></i>
                        <span>Evaluator Written Feedback / Remarks</span>
                    </span>
                    <p class="text-xs font-semibold text-slate-800 italic" x-text="'&quot;' + selectedAnswersSub.comments + '&quot;'"></p>
                </div>
            </template>

            <!-- Questions & Answers List -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-slate-400"></i>
                        <span>Detailed Responses Per Question</span>
                    </h3>
                    <div class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> 5-Outstanding
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> 4-Very Sat.
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> 3-Satisfactory
                    </div>
                </div>

                <template x-if="selectedAnswersSub && selectedAnswersSub.question_responses && selectedAnswersSub.question_responses.length > 0">
                    <div class="space-y-5"
                         x-data="{
                            get groupedByCategory() {
                                const groups = {};
                                const qs = selectedAnswersSub ? (selectedAnswersSub.question_responses || []) : [];
                                qs.forEach(q => {
                                    const cat = q.category || 'General';
                                    if (!groups[cat]) groups[cat] = [];
                                    groups[cat].push(q);
                                });
                                return Object.entries(groups).map(([cat, items]) => {
                                    const rated = items.filter(q => q.rating !== null && q.rating !== undefined);
                                    const avg = rated.length > 0 ? (rated.reduce((s, q) => s + q.rating, 0) / rated.length) : null;
                                    return { category: cat, items, avg };
                                });
                            }
                         }">
                        <template x-for="(group, gIdx) in groupedByCategory" :key="gIdx">
                            <div class="rounded-2xl border-2 border-slate-100 overflow-hidden">
                                {{-- Category Header --}}
                                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-[#8b1818] text-white text-[10px] font-black flex items-center justify-center shrink-0" x-text="gIdx + 1"></span>
                                        <span class="text-xs font-black text-slate-800 uppercase tracking-wide" x-text="group.category"></span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-[10px] font-bold text-slate-400">Section Avg:</span>
                                        <template x-if="group.avg !== null">
                                            <span :class="
                                                group.avg >= 4.5 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                                                group.avg >= 3.5 ? 'bg-blue-100 text-blue-800 border-blue-300' :
                                                group.avg >= 2.5 ? 'bg-amber-100 text-amber-800 border-amber-300' :
                                                'bg-red-100 text-red-800 border-red-300'"
                                                class="px-2.5 py-0.5 rounded-lg text-xs font-black border"
                                                x-text="Number(group.avg).toFixed(2) + ' / 5.0'">
                                            </span>
                                        </template>
                                        <template x-if="group.avg === null">
                                            <span class="text-xs font-bold text-slate-400">--</span>
                                        </template>
                                    </div>
                                </div>
                                {{-- Questions within category --}}
                                <div class="divide-y divide-slate-100">
                                    <template x-for="(q, qIdx) in group.items" :key="q.id || qIdx">
                                        <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 transition">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                <span class="w-6 h-6 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 font-mono font-black text-[10px] flex items-center justify-center shrink-0 mt-0.5" x-text="q.number || (qIdx + 1)"></span>
                                                <p class="text-xs font-semibold text-slate-700 leading-snug" x-text="q.question"></p>
                                            </div>
                                            <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-center pl-9 sm:pl-0">
                                                <div class="flex items-center gap-0.5">
                                                    <template x-for="star in 5" :key="star">
                                                        <i :class="star <= q.rating ? 'fa-solid fa-star text-amber-400' : 'fa-regular fa-star text-slate-200'" class="text-[11px]"></i>
                                                    </template>
                                                </div>
                                                <span :class="'px-2.5 py-1 rounded-xl text-xs font-black border shadow-2xs whitespace-nowrap ' + q.badge_color" x-text="q.rating + ' • ' + q.rating_label"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>


                <template x-if="!selectedAnswersSub || !selectedAnswersSub.question_responses || selectedAnswersSub.question_responses.length === 0">
                    <div class="py-10 text-center text-xs font-bold text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        No question breakdown records available for this submission.
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end pt-3 border-t border-slate-100">
                <button type="button" @click="answersModal = false" class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 uppercase transition">Close</button>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = {!! json_encode($barLabels ?? []) !!};
        const scores = {!! json_encode($barScores ?? []) !!};
        const distValues = {!! json_encode($distributionValues ?? [0,0,0,0,0]) !!};

        // Custom plugin to render data inside Doughnut chart (center count + slice counts)
        const doughnutDataLabelsPlugin = {
            id: 'doughnutDataLabels',
            afterDraw(chart) {
                if (chart.config.type !== 'doughnut') return;
                const { ctx, chartArea: { top, bottom, left, right } } = chart;
                if (!left || !right) return;
                const centerX = (left + right) / 2;
                const centerY = (top + bottom) / 2;

                // 1. Center text: total evaluated count
                const totalEvaluated = distValues.slice(0, 4).reduce((a, b) => a + b, 0);
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.font = '900 24px Inter, system-ui, sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(totalEvaluated, centerX, centerY - 8);

                ctx.font = 'bold 9px Inter, system-ui, sans-serif';
                ctx.fillStyle = '#64748b';
                ctx.letterSpacing = '1px';
                ctx.fillText('EVALUATED', centerX, centerY + 14);
                ctx.restore();

                // 2. Numerical count directly inside each slice with value > 0
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    meta.data.forEach((element, index) => {
                        const val = dataset.data[index];
                        if (val > 0) {
                            const pos = element.tooltipPosition();
                            ctx.save();
                            ctx.font = '900 13px Inter, system-ui, sans-serif';
                            ctx.fillStyle = '#ffffff';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.shadowColor = 'rgba(0, 0, 0, 0.45)';
                            ctx.shadowBlur = 4;
                            ctx.fillText(val, pos.x, pos.y);
                            ctx.restore();
                        }
                    });
                });
            }
        };

        // Custom plugin to render numerical data directly above/inside Bar chart bars
        const barDataLabelsPlugin = {
            id: 'barDataLabels',
            afterDatasetsDraw(chart) {
                if (chart.config.type !== 'bar') return;
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    meta.data.forEach((bar, index) => {
                        const val = dataset.data[index];
                        if (val !== null && val !== undefined) {
                            const num = Number(val);
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';

                            // Render score value
                            const text = num > 0 ? num.toFixed(1) : '0';
                            ctx.font = '900 12px Inter, system-ui, sans-serif';
                            ctx.fillStyle = '#8b1818';

                            // Draw badge / text right above bar
                            ctx.fillText(text, bar.x, bar.y - 4);
                            ctx.restore();
                        }
                    });
                });
            }
        };

        const distCtx = document.getElementById('distributionChart');
        if (distCtx) {
            new Chart(distCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Needs Improvement', 'Pending'],
                    datasets: [{
                        data: distValues,
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#cbd5e1'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                plugins: [doughnutDataLabelsPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: { legend: { display: false } }
                }
            });
        }

        const compCtx = document.getElementById('comparisonChart');
        if (compCtx) {
            new Chart(compCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Mean Rating',
                        data: scores,
                        backgroundColor: '#8b1818',
                        borderRadius: 8
                    }]
                },
                plugins: [barDataLabelsPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 24
                        }
                    },
                    scales: {
                        y: { 
                            min: 0, 
                            max: 100, 
                            ticks: { stepSize: 20 } 
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
@endsection