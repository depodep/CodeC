@extends('layouts.app')

@section('title', 'Faculty Evaluation Results - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60 font-sans">

    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shadow-red-950/15 shrink-0">
                <i class="fa-solid fa-chart-pie text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Faculty Evaluation Scorecard</h1>
                    @if($isPublished)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300 uppercase tracking-wide flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-[9px]"></i> Published
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wide flex items-center gap-1">
                            <i class="fa-solid fa-clock text-amber-600 text-[9px]"></i> Unpublished / In Review
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">
                    Official evaluation performance breakdown, category analytics, and anonymous feedback
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <!-- Cycle Selector Form -->
            @if(isset($allCycles) && $allCycles->isNotEmpty())
                <form method="GET" action="{{ route('teacher.evaluations.results') }}" class="flex items-center gap-2">
                    <label class="sr-only" for="cycle_id">Evaluation Cycle</label>
                    <select name="cycle_id" id="cycle_id" onchange="this.form.submit()"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white text-slate-800 border-2 border-slate-200 hover:border-[#8b1818] focus:border-[#8b1818] outline-none transition cursor-pointer shadow-2xs">
                        @foreach($allCycles as $cycle)
                            <option value="{{ $cycle->id }}" @selected($selectedCycle && $selectedCycle->id == $cycle->id)>
                                {{ $cycle->name }} {{ $cycle->is_active ? '★ (Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <!-- Navigation Button to Peer/Self Evaluation -->
            <a href="{{ route('teacher.evaluations.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-black text-xs uppercase tracking-wider transition border-2 border-slate-200 shrink-0 cursor-pointer shadow-2xs hover:border-[#8b1818]">
                <i class="fa-solid fa-star-half-stroke text-[#8b1818]"></i>
                <span>Submit Evaluations</span>
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="py-8 px-6 sm:px-10 lg:px-12 w-full space-y-8 flex-1 max-w-[1600px] mx-auto">

        <!-- ================================================================= -->
        <!-- STATE 1: UNPUBLISHED / RESULTS PENDING RELEASE                    -->
        <!-- ================================================================= -->
        @if(!$isPublished)
            <div class="bg-white rounded-3xl border-2 border-slate-200 p-8 sm:p-12 text-center shadow-xs space-y-6 max-w-3xl mx-auto my-6">
                <div class="w-20 h-20 rounded-3xl bg-amber-50 border-2 border-amber-200 text-amber-600 flex items-center justify-center text-3xl mx-auto shadow-xs">
                    <i class="fa-solid fa-hourglass-half animate-pulse"></i>
                </div>
                
                <div class="space-y-2">
                    <span class="px-3 py-1 rounded-full text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-widest inline-block">
                        Done Evaluating · Awaiting Publication
                    </span>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                        Evaluation Results Pending Publication
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 font-medium leading-relaxed max-w-xl mx-auto">
                        Your evaluations for <strong class="text-slate-900">{{ $selectedCycle ? $selectedCycle->name : 'this academic cycle' }}</strong> have been recorded in the system. 
                        Official ratings, performance scorecards, actionable category insights, and student feedback will automatically unlock here once published by the Academic Administration and Principal.
                    </p>
                </div>

                <!-- Info Tiles -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-slate-100 text-left">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] uppercase font-black text-slate-400 block">Cycle Name</span>
                        <span class="font-bold text-slate-800 text-xs truncate block">{{ $selectedCycle ? $selectedCycle->name : 'N/A' }}</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] uppercase font-black text-slate-400 block">Evaluation Status</span>
                        <span class="font-bold text-amber-700 text-xs flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> Results Under Review
                        </span>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] uppercase font-black text-slate-400 block">Publication Notice</span>
                        <span class="font-bold text-slate-700 text-xs">Awaiting Admin Release</span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ route('teacher.evaluations.index') }}" 
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider shadow-md shadow-red-950/20 transition cursor-pointer">
                        <i class="fa-solid fa-users text-amber-300"></i>
                        <span>Check Peer & Self Evaluation Forms</span>
                    </a>
                </div>
            </div>

        <!-- ================================================================= -->
        <!-- STATE 2: PUBLISHED EVALUATION RESULTS SCORECARD                  -->
        <!-- ================================================================= -->
        @else

            <!-- HERO SECTION: OVERALL SCORE & COMPONENT BREAKDOWN -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Overall Rating Hero Card -->
                <article class="lg:col-span-4 rounded-3xl bg-gradient-to-br from-[#8b1818] via-[#701313] to-[#4e0c0c] text-white p-7 shadow-lg shadow-red-950/20 flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-36 h-36 rounded-full bg-white/5 pointer-events-none"></div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase tracking-widest text-amber-300 bg-white/10 px-3 py-1 rounded-full border border-white/10">
                                Institutional Final Score
                            </span>
                            @if($publishedAt)
                                <span class="text-[10px] text-white/70 font-semibold">
                                    {{ $publishedAt->format('M d, Y') }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <div class="text-5xl sm:text-6xl font-black tracking-tight text-white flex items-baseline gap-1">
                                <span>{{ $overallWeightedScore !== null ? number_format($overallWeightedScore, 2) : 'N/A' }}</span>
                                <span class="text-xl font-bold text-amber-300">/ 5.00</span>
                            </div>
                            <div class="mt-2">
                                <span class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $descriptorClass }} shadow-xs inline-block">
                                    {{ $overallDescriptor }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-white/10 mt-6 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-white/60 uppercase block">Total Evaluators</span>
                            <span class="font-black text-white text-base">{{ number_format($totalSubmissions) }} Reviews</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-white/60 uppercase block">Evaluation Cycle</span>
                            <span class="font-bold text-amber-200 truncate block text-xs">{{ $selectedCycle ? $selectedCycle->name : 'Current' }}</span>
                        </div>
                    </div>
                </article>

                <!-- 4 Component Score Tiles (Student, Peer, Self, Principal) -->
                <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- 1. Student Evaluation (50%) -->
                    <div class="rounded-3xl bg-white border-2 border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-base shrink-0 border border-emerald-200">
                                    <i class="fa-solid fa-user-graduate"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900 uppercase">Student Ratings</h3>
                                    <span class="text-[10px] text-slate-500 font-bold">{{ $studentCount }} Learner Submissions</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                {{ $weights['student'] ?? 50 }}% Weight
                            </span>
                        </div>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <span class="text-3xl font-black text-slate-900">
                                    {{ $studentAvg !== null ? number_format($studentAvg, 2) : '--' }}
                                </span>
                                <span class="text-xs font-bold text-slate-400">/ 5.00</span>
                            </div>
                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-lg">
                                {{ $studentAvg ? ($studentAvg >= 4.5 ? 'Highly Favorable' : ($studentAvg >= 3.5 ? 'Satisfactory' : 'Needs Focus')) : 'No submissions' }}
                            </span>
                        </div>
                    </div>

                    <!-- 2. Peer Faculty Evaluation (30%) -->
                    <div class="rounded-3xl bg-white border-2 border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-base shrink-0 border border-blue-200">
                                    <i class="fa-solid fa-chalkboard-user"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900 uppercase">Peer Evaluations</h3>
                                    <span class="text-[10px] text-slate-500 font-bold">{{ $peerCount }} Colleague Reviews</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-900 border border-blue-300">
                                {{ $weights['peer'] ?? 30 }}% Weight
                            </span>
                        </div>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <span class="text-3xl font-black text-slate-900">
                                    {{ $peerAvg !== null ? number_format($peerAvg, 2) : '--' }}
                                </span>
                                <span class="text-xs font-bold text-slate-400">/ 5.00</span>
                            </div>
                            <span class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-1 rounded-lg">
                                {{ $peerAvg ? ($peerAvg >= 4.5 ? 'High Collaboration' : 'Professional') : 'No peer reviews' }}
                            </span>
                        </div>
                    </div>

                    <!-- 3. Self Evaluation (10%) -->
                    <div class="rounded-3xl bg-white border-2 border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-base shrink-0 border border-amber-200">
                                    <i class="fa-solid fa-user-pen"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900 uppercase">Self Appraisal</h3>
                                    <span class="text-[10px] text-slate-500 font-bold">{{ $selfCount > 0 ? 'Submitted' : 'Pending' }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                {{ $weights['self'] ?? 10 }}% Weight
                            </span>
                        </div>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <span class="text-3xl font-black text-slate-900">
                                    {{ $selfAvg !== null ? number_format($selfAvg, 2) : '--' }}
                                </span>
                                <span class="text-xs font-bold text-slate-400">/ 5.00</span>
                            </div>
                            <span class="text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-1 rounded-lg">
                                Self-Rating Score
                            </span>
                        </div>
                    </div>

                    <!-- 4. Principal / Management (10%) -->
                    <div class="rounded-3xl bg-white border-2 border-slate-200 p-5 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-base shrink-0 border border-purple-200">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900 uppercase">Principal Rating</h3>
                                    <span class="text-[10px] text-slate-500 font-bold">Administration Review</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-purple-100 text-purple-900 border border-purple-300">
                                {{ $weights['principal'] ?? 10 }}% Weight
                            </span>
                        </div>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <span class="text-3xl font-black text-slate-900">
                                    {{ $principalAvg !== null ? number_format($principalAvg, 2) : '--' }}
                                </span>
                                <span class="text-xs font-bold text-slate-400">/ 5.00</span>
                            </div>
                            <span class="text-[11px] font-bold text-purple-700 bg-purple-50 px-2 py-1 rounded-lg">
                                Executive Oversight
                            </span>
                        </div>
                    </div>

                </div>

            </section>

            <!-- SECTION 2: CATEGORY BREAKDOWN & ACTIONABLE IMPROVEMENT SUGGESTIONS -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left: Category Performance Breakdown List -->
                <div class="lg:col-span-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-red-50 text-[#8b1818] flex items-center justify-center text-xs">
                                <i class="fa-solid fa-bars-progress"></i>
                            </div>
                            <h2 class="text-base font-black text-slate-900">Criteria & Category Ratings</h2>
                        </div>
                        <span class="text-[11px] font-bold text-slate-400">Scale: 1.00 (Low) – 5.00 (High)</span>
                    </div>

                    <div class="bg-white rounded-3xl border-2 border-slate-200 p-6 shadow-xs space-y-4">
                        @forelse($categoryMetrics as $idx => $cat)
                            <div class="space-y-1.5 pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-black text-slate-800 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold flex items-center justify-center shrink-0">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span>{{ $cat->short_name }}</span>
                                    </span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="font-black text-slate-900 text-xs">{{ $cat->formatted_avg }}</span>
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $cat->badge_class }}">
                                            {{ $cat->descriptor }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Sleek Percentage Bar -->
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $cat->average >= 4.5 ? 'bg-emerald-500' : ($cat->average >= 3.5 ? 'bg-blue-500' : ($cat->average >= 2.5 ? 'bg-amber-500' : 'bg-rose-500')) }}" 
                                         style="width: {{ $cat->percentage }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400 text-xs font-bold">
                                No individual category metrics recorded for this cycle.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Right: Targeted Strengths & Actionable Pedagogical Suggestions -->
                <div class="lg:col-span-6 space-y-4">
                    
                    <!-- 1. Top Strength Highlight -->
                    @if($topStrength)
                        <div class="bg-emerald-50/80 rounded-3xl border-2 border-emerald-300 p-6 shadow-2xs space-y-2">
                            <div class="flex items-center gap-2 text-emerald-900 font-black text-xs uppercase tracking-wider">
                                <i class="fa-solid fa-medal text-emerald-600 text-base"></i>
                                <span>Key Area of Strength</span>
                            </div>
                            <h3 class="text-base font-black text-emerald-950">
                                {{ $topStrength->short_name }} ({{ $topStrength->formatted_avg }} / 5.00)
                            </h3>
                            <p class="text-xs text-emerald-800 font-medium leading-relaxed">
                                Evaluators consistently praised your proficiency in this area. Maintain these strong practices as an institutional model for excellence.
                            </p>
                        </div>
                    @endif

                    <!-- 2. Actionable Improvement Focus -->
                    @if(!empty($improvementSuggestions))
                        <div class="bg-white rounded-3xl border-2 border-amber-300 p-6 shadow-xs space-y-4">
                            <div class="flex items-center justify-between gap-2 border-b border-amber-100 pb-3">
                                <div class="flex items-center gap-2 text-amber-900 font-black text-xs uppercase tracking-wider">
                                    <i class="fa-solid fa-bullseye text-amber-600 text-base"></i>
                                    <span>Recommended Improvement Focus</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                    Target Area
                                </span>
                            </div>

                            <div class="space-y-1">
                                <h3 class="text-base font-black text-slate-900">
                                    {{ $improvementSuggestions['focus'] ?? 'Teaching Methodology' }}
                                </h3>
                                <p class="text-xs text-slate-600 font-medium">
                                    {{ $improvementSuggestions['summary'] ?? 'Practical pedagogical recommendations to elevate your instructional outcomes.' }}
                                </p>
                            </div>

                            <!-- Bulleted Actionable Strategies -->
                            <div class="space-y-2.5 pt-1">
                                <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 block">Actionable Teaching Strategies:</span>
                                <ul class="space-y-2">
                                    @foreach($improvementSuggestions['tips'] ?? [] as $tip)
                                        <li class="flex items-start gap-2.5 text-xs text-slate-700 font-medium">
                                            <i class="fa-solid fa-check-circle text-amber-600 text-xs mt-0.5 shrink-0"></i>
                                            <span>{{ $tip }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                </div>

            </section>

            <!-- SECTION 3: STRICTLY ANONYMOUS EVALUATOR FEEDBACK & COMMENTS -->
            <section class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-900">Anonymous Evaluator Feedback & Remarks</h2>
                            <p class="text-[11px] text-slate-500 font-semibold">Comments submitted during this evaluation cycle (Evaluator identities are protected)</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-600 text-[11px] font-black border border-slate-200">
                        {{ $anonymousComments->count() }} {{ Str::plural('Comment', $anonymousComments->count()) }}
                    </span>
                </div>

                <!-- Anonymity Assurance Banner -->
                <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 flex items-center gap-3 text-xs text-blue-900">
                    <i class="fa-solid fa-user-shield text-blue-600 text-lg shrink-0"></i>
                    <p class="font-medium">
                        <strong>Privacy Notice:</strong> All student and faculty evaluator names, IDs, LRNs, and sections are strictly redacted to foster constructive, honest, and objective feedback.
                    </p>
                </div>

                <!-- Comments Wall Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($anonymousComments as $comment)
                        <div class="bg-white rounded-2xl border-2 border-slate-100 hover:border-slate-300 p-5 shadow-2xs space-y-3 transition flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black border flex items-center gap-1.5 {{ $comment->role_badge }}">
                                        <i class="fa-solid {{ $comment->icon }} text-[9px]"></i>
                                        <span>{{ $comment->role_label }}</span>
                                    </span>
                                    <div class="flex items-center gap-1 text-amber-500 font-black text-xs">
                                        <i class="fa-solid fa-star text-[10px]"></i>
                                        <span>{{ $comment->score_format }}</span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-800 font-semibold italic leading-relaxed pt-1">
                                    "{{ $comment->comment }}"
                                </p>
                            </div>
                            
                            @if($comment->date)
                                <div class="pt-2 border-t border-slate-100 text-[10px] text-slate-400 font-bold flex items-center justify-between">
                                    <span>Verified Evaluation Log</span>
                                    <span>{{ $comment->date }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full p-12 text-center text-slate-400 font-bold bg-white rounded-3xl border-2 border-slate-200">
                            <i class="fa-solid fa-comment-slash text-3xl mb-2 text-slate-300 block"></i>
                            No written comments submitted for this evaluation cycle.
                        </div>
                    @endforelse
                </div>
            </section>

        @endif

    </main>
</div>
@endsection
