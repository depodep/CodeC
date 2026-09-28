@extends('layouts.app')

@section('title', 'Faculty Evaluation Analytics & Results - SIATRACK Admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div x-data="{
        detailModal: false,
        selectedFaculty: null,
        openBreakdown(faculty) {
            this.selectedFaculty = faculty;
            this.detailModal = true;
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
                        Quantitative appraisal metrics and teacher score publishing
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
                <span class="text-[10px] font-bold text-slate-500 mt-0.5 block">Feedback Slips</span>
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

        <!-- Detailed Table with Numbering & Compact Rows -->
        <div class="bg-white border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="border-b-2 border-slate-200 bg-slate-50/80 text-[11px] font-black uppercase text-slate-500 tracking-wider">
                            <th class="py-3.5 px-3 text-center w-10 whitespace-nowrap">#</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Faculty Member</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Faculty ID / Email</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Submissions Breakdown</th>
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
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <!-- Top Line: Total Submitted -->
                                        <span class="px-2.5 py-0.5 rounded-full bg-slate-900 text-white font-black text-xs shadow-2xs">
                                            {{ $member->total_submissions ?? $member->peer_count }} Submitted
                                        </span>

                                        <!-- Bottom Line: Evaluator Type Breakdown (Principal, Peer, Student, Self) -->
                                        <div class="flex items-center justify-center gap-1 text-[10px] font-extrabold mt-0.5">
                                            <span class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-800 border border-purple-200/80" title="Principal Evaluations">Principal: {{ $member->principal_count ?? 0 }}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200/80" title="Peer Evaluations">Peer: {{ $member->peer_count ?? 0 }}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200/80" title="Student Evaluations">Student: {{ $member->student_count ?? 0 }}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200/80" title="Self Evaluations">Self: {{ $member->self_count ?? 0 }}</span>
                                        </div>
                                    </div>
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
                                    @if($member->peer_avg !== null)
                                        <div class="inline-flex items-baseline gap-1">
                                            <span class="text-xs font-black text-slate-900">{{ number_format($member->peer_avg, 2) }}</span>
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
                                    <button @click="openBreakdown({{ json_encode($member) }})" 
                                            class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-black uppercase tracking-wider transition">
                                        View Feedback
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
    </main>

    <!-- Modal: Feedback and Breakdown -->
    <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="detailModal = false" class="bg-white rounded-3xl border-2 border-slate-200 max-w-xl w-full p-8 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b pb-4 border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#8b1818] text-white flex items-center justify-center text-base">
                        <i class="fa-solid fa-comment-dots text-amber-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900" x-text="selectedFaculty ? selectedFaculty.name : ''"></h3>
                        <p class="text-xs font-bold text-slate-400">Evaluation Remarks & Rating Summary</p>
                    </div>
                </div>
                <button @click="detailModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Score Cards in Modal -->
            <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400">Calculated Mean</span>
                    <p class="text-xl font-black text-[#8b1818] mt-0.5" x-text="selectedFaculty && selectedFaculty.peer_avg ? Number(selectedFaculty.peer_avg).toFixed(2) + ' / 5.00' : 'No Submissions Yet'"></p>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400">Rating Bracket</span>
                    <p class="text-sm font-black text-slate-800 mt-1" x-text="selectedFaculty ? selectedFaculty.descriptor : ''"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[10px] font-black uppercase tracking-wider">
                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200">Student<br><span class="text-sm" x-text="selectedFaculty && selectedFaculty.student_weighted !== null ? Number(selectedFaculty.student_weighted).toFixed(2) : '--'"></span></div>
                <div class="p-2.5 rounded-xl bg-purple-50 text-purple-800 border border-purple-200">Principal<br><span class="text-sm" x-text="selectedFaculty && selectedFaculty.principal_weighted !== null ? Number(selectedFaculty.principal_weighted).toFixed(2) : '--'"></span></div>
                <div class="p-2.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200">Self<br><span class="text-sm" x-text="selectedFaculty && selectedFaculty.self_weighted !== null ? Number(selectedFaculty.self_weighted).toFixed(2) : '--'"></span></div>
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-800 border border-blue-200">Peer<br><span class="text-sm" x-text="selectedFaculty && selectedFaculty.peer_weighted !== null ? Number(selectedFaculty.peer_weighted).toFixed(2) : '--'"></span></div>
            </div>

            <!-- Written Comments -->
            <div class="space-y-3">
                <h4 class="text-xs font-black uppercase text-slate-900 tracking-wider">Qualitative Comments & Feedback</h4>
                
                <template x-if="selectedFaculty && selectedFaculty.comments && selectedFaculty.comments.length > 0">
                    <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                        <template x-for="(comment, index) in selectedFaculty.comments" :key="index">
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 italic">
                                "<span x-text="comment"></span>"
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!selectedFaculty || !selectedFaculty.comments || selectedFaculty.comments.length === 0">
                    <div class="py-6 text-center text-xs font-bold text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        No qualitative comments submitted for this faculty yet.
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" @click="detailModal = false" class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 uppercase transition">Close</button>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = {!! json_encode($barLabels ?? []) !!};
        const scores = {!! json_encode($barScores ?? []) !!};
        const distValues = {!! json_encode($distributionValues ?? [0,0,0,0,0]) !!};

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
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { min: 0, max: 100, ticks: { stepSize: 20 } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
@endsection