@extends('layouts.app')

@section('title', 'Evaluation Management & Forms - SIATRACK Admin')

@section('content')
<div x-data="{ periodModal: false }" class="w-full min-h-screen bg-slate-100/60 pb-20 font-sans">
    
    <!-- Top Bar Navigation -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-6 sm:px-10 py-4 sticky top-0 z-30 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#590d0d] text-white flex items-center justify-center text-lg shadow-md shadow-red-950/20 shrink-0">
                <i class="fa-solid fa-clipboard-check text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">SIA Faculty Evaluation Forms</h1>
                    @if(isset($activeCycle) && $activeCycle)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300/60">
                            SY {{ $activeCycle->school_year }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 font-semibold">Start evaluations, manage evaluation questionnaires, and configure form structures</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button @click="periodModal = true" class="px-3.5 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider transition shadow-md shadow-red-950/20 flex items-center gap-2">
                <i class="fa-solid fa-play text-amber-300 text-xs"></i>
                <span>Start Evaluation</span>
            </button>
            <a href="{{ route('admin.evaluations.history') }}" class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-black text-xs uppercase tracking-wider transition border border-slate-300 shadow-2xs flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-500 text-xs"></i>
                <span>History</span>
            </a>
            <a href="{{ route('admin.evaluations.results') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white font-black text-xs uppercase tracking-wider transition shadow-2xs flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-amber-300 text-xs"></i>
                <span>Analytics</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto pt-6 px-4 sm:px-6 lg:px-8 space-y-6">
        
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 border-2 border-rose-300 text-rose-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-950"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        <!-- Active Evaluation Cycle Banner (NO SEMESTER) -->
        <div class="bg-gradient-to-r from-[#590d0d] via-[#731414] to-[#8b1818] rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-red-950/15 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-white/10 gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-amber-300">Active Evaluation Period</span>
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight mt-0.5">
                        {{ $activeCycle->name ?? 'No Active Evaluation Cycle' }}
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="periodModal = true" class="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white backdrop-blur-md border border-white/20 text-xs font-black transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-plus-circle text-amber-300"></i>
                        <span>Start New Evaluation</span>
                    </button>
                    @if(isset($activeCycle) && $activeCycle)
                        <form method="POST" action="{{ route('admin.evaluations.end-cycle', ['id' => $activeCycle->id]) }}" onsubmit="return confirm('End evaluation cycle permanently? Submissions will be locked and saved to history.');">
                            @csrf
                            <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black transition flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-circle-stop"></i>
                                <span>End Evaluation</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-black/20 backdrop-blur-sm rounded-2xl p-4 border border-white/10">
                    <p class="text-[10px] font-black uppercase tracking-wider text-red-200/70">School Year</p>
                    <h4 class="text-base font-extrabold text-white mt-1">SY {{ $activeCycle->school_year ?? '2026-2027' }}</h4>
                    <span class="text-[11px] font-semibold text-amber-300">Active Academic Year</span>
                </div>
                <div class="bg-black/20 backdrop-blur-sm rounded-2xl p-4 border border-white/10">
                    <p class="text-[10px] font-black uppercase tracking-wider text-red-200/70">Evaluation Date Range</p>
                    <h4 class="text-base font-extrabold text-white mt-1">
                        @if(isset($activeCycle->start_date) && $activeCycle->start_date)
                            {{ \Carbon\Carbon::parse($activeCycle->start_date)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($activeCycle->end_date)->format('M d, Y') }}
                        @else
                            <span class="text-slate-300 font-normal">Date Range Not Set</span>
                        @endif
                    </h4>
                    <span class="text-[11px] font-semibold text-red-200">Configured Window</span>
                </div>
                <div class="bg-black/20 backdrop-blur-sm rounded-2xl p-4 border border-white/10">
                    <p class="text-[10px] font-black uppercase tracking-wider text-red-200/70">Status</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ ($activeCycle->status ?? '') === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                        <h4 class="text-base font-black uppercase text-white">{{ strtoupper($activeCycle->status ?? 'Inactive') }}</h4>
                    </div>
                    <span class="text-[11px] font-semibold text-red-200">
                        {{ ($activeCycle->status ?? '') === 'active' ? 'Live for respondents' : 'Start an evaluation to open' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 4 Evaluator Forms Cards with [ Edit Form ] Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- 1. Principal's Evaluation Form Card -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 group hover:border-[#8b1818] transition">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-[#8b1818] border border-red-200 flex items-center justify-center text-xl shrink-0 group-hover:bg-[#8b1818] group-hover:text-white transition">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Principal's Evaluation</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Admin & Principal supervisory appraisal questionnaire</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-slate-600">
                        <span>Configured Items</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200">{{ $counts['principal'] ?? 0 }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.evaluations.forms.edit', ['type' => 'principal']) }}" class="w-full py-2.5 px-4 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider text-center transition shadow-xs flex items-center justify-center gap-2">
                    <i class="fa-solid fa-[#8b1818] fa-pen-to-square text-amber-300"></i>
                    <span>Edit Form</span>
                </a>
            </div>

            <!-- 2. Peer Evaluation Form Card -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 group hover:border-[#8b1818] transition">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-800 border border-blue-200 flex items-center justify-center text-xl shrink-0 group-hover:bg-blue-800 group-hover:text-white transition">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Peer Evaluation</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Faculty peer-to-peer collaboration rubric</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-slate-600">
                        <span>Configured Items</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200">{{ $counts['peer'] ?? 0 }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.evaluations.forms.edit', ['type' => 'peer']) }}" class="w-full py-2.5 px-4 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider text-center transition shadow-xs flex items-center justify-center gap-2">
                    <i class="fa-solid fa-[#8b1818] fa-pen-to-square text-amber-300"></i>
                    <span>Edit Form</span>
                </a>
            </div>

            <!-- 3. Student Evaluation Form Card -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 group hover:border-[#8b1818] transition">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center justify-center text-xl shrink-0 group-hover:bg-emerald-800 group-hover:text-white transition">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Student Evaluation</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Student rating of teaching performance & methodology</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-slate-600">
                        <span>Configured Items</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200">{{ $counts['student'] ?? 0 }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.evaluations.forms.edit', ['type' => 'student']) }}" class="w-full py-2.5 px-4 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider text-center transition shadow-xs flex items-center justify-center gap-2">
                    <i class="fa-solid fa-[#8b1818] fa-pen-to-square text-amber-300"></i>
                    <span>Edit Form</span>
                </a>
            </div>

            <!-- 4. Self Evaluation Form Card -->
            <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 group hover:border-[#8b1818] transition">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-900 border border-amber-200 flex items-center justify-center text-xl shrink-0 group-hover:bg-amber-800 group-hover:text-white transition">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Self Evaluation</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Teacher self-reflection & professional growth questionnaire</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-slate-600">
                        <span>Configured Items</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200">{{ $counts['self'] ?? 0 }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.evaluations.forms.edit', ['type' => 'self']) }}" class="w-full py-2.5 px-4 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider text-center transition shadow-xs flex items-center justify-center gap-2">
                    <i class="fa-solid fa-[#8b1818] fa-pen-to-square text-amber-300"></i>
                    <span>Edit Form</span>
                </a>
            </div>

        </div>

        <!-- Segmented Form Tabs Preview -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4">
            <div class="inline-flex p-1.5 bg-slate-200/80 rounded-2xl gap-1.5 border border-slate-300/70 w-full sm:w-auto overflow-x-auto">
                <a href="{{ url('admin/evaluations/periods?type=principal') }}" 
                   class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition whitespace-nowrap flex items-center gap-2 {{ $selectedType === 'principal' ? 'bg-white text-[#8b1818] shadow-sm font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/40' }}">
                    <i class="fa-solid fa-user-tie text-xs"></i>
                    <span>Principal's Evaluation</span>
                </a>

                <a href="{{ url('admin/evaluations/periods?type=peer') }}" 
                   class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition whitespace-nowrap flex items-center gap-2 {{ $selectedType === 'peer' ? 'bg-white text-[#8b1818] shadow-sm font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/40' }}">
                    <i class="fa-solid fa-users text-xs"></i>
                    <span>Peer Evaluation</span>
                </a>

                <a href="{{ url('admin/evaluations/periods?type=student') }}" 
                   class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition whitespace-nowrap flex items-center gap-2 {{ $selectedType === 'student' ? 'bg-white text-[#8b1818] shadow-sm font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/40' }}">
                    <i class="fa-solid fa-graduation-cap text-xs"></i>
                    <span>Student Evaluation</span>
                </a>

                <a href="{{ url('admin/evaluations/periods?type=self') }}" 
                   class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition whitespace-nowrap flex items-center gap-2 {{ $selectedType === 'self' ? 'bg-white text-[#8b1818] shadow-sm font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/40' }}">
                    <i class="fa-solid fa-user-pen text-xs"></i>
                    <span>Self Evaluation</span>
                </a>
            </div>

            <a href="{{ route('admin.evaluations.forms.edit', ['type' => $selectedType]) }}" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white font-black text-xs uppercase tracking-wider transition flex items-center gap-2 shrink-0">
                <i class="fa-solid fa-pen-to-square text-amber-300"></i>
                <span>Edit {{ strtoupper($selectedType) }} Questionnaire Structure</span>
            </a>
        </div>

        <!-- Categorized Indicators Display List (Read-only overview) -->
        <div class="space-y-6">
            @forelse($groupedQuestions ?? [] as $category => $items)
                <div class="bg-white border-2 border-slate-200/90 rounded-3xl overflow-hidden shadow-xs">
                    <!-- Category Header Bar -->
                    <div class="bg-slate-50/90 border-b-2 border-slate-200/90 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-6 rounded-full bg-[#8b1818]"></span>
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">{{ $category }}</h3>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-white border border-slate-200 text-slate-600 shadow-2xs">
                            {{ count($items) }} {{ Str::plural('Indicator', count($items)) }}
                        </span>
                    </div>

                    <!-- Items List -->
                    <div class="divide-y divide-slate-100">
                        @foreach($items as $q)
                            <div class="p-5 sm:px-8 flex items-start justify-between gap-4">
                                <div class="flex items-start gap-4 max-w-4xl">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 font-black text-xs flex items-center justify-center shrink-0 border border-slate-200 mt-0.5">
                                        {{ $q->order_num }}
                                    </div>
                                    <div class="space-y-1.5 flex-1">
                                        <p class="text-sm font-bold text-slate-800 leading-relaxed">{{ $q->question }}</p>
                                        
                                        @php
                                            $qType = $q->type ?? 'likert';
                                            $options = [];
                                            if (!empty($q->options)) {
                                                $options = is_array($q->options) ? $q->options : json_decode($q->options, true);
                                                if (!is_array($options) && is_string($q->options)) {
                                                    $options = array_map('trim', explode(',', $q->options));
                                                }
                                            }
                                        @endphp

                                        @if($qType === 'likert')
                                            <div class="flex items-center gap-2 pt-0.5">
                                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
                                                    <i class="fa-solid fa-sliders text-emerald-500"></i>
                                                    Likert Scale (1–5)
                                                </span>
                                            </div>
                                        @elseif($qType === 'yes_no')
                                            <div class="flex items-center gap-3 pt-0.5">
                                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-200">
                                                    <i class="fa-solid fa-circle-half-stroke text-blue-500"></i>
                                                    Yes / No
                                                </span>
                                                <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600">
                                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">Yes</span>
                                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">No</span>
                                                </div>
                                            </div>
                                        @elseif($qType === 'multiple_choice')
                                            <div class="space-y-1 pt-0.5">
                                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200">
                                                    <i class="fa-solid fa-list-check text-amber-600"></i>
                                                    Multiple Choice
                                                </span>
                                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                                    @foreach(($options ?: ['Option 1', 'Option 2']) as $opt)
                                                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200 flex items-center gap-1.5">
                                                            <i class="fa-regular fa-circle text-[10px] text-slate-400"></i>
                                                            {{ $opt }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @elseif($qType === 'open_ended')
                                            <div class="pt-0.5">
                                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-md border border-purple-200">
                                                    <i class="fa-solid fa-align-left text-purple-500"></i>
                                                    Open-Ended (Text)
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white border-2 border-dashed border-slate-300 rounded-3xl p-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">No Indicators Configured Yet</h4>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Click "Edit Form" above to open the Questionnaire Editor and structure your form.</p>
                </div>
            @endforelse
        </div>
    </main>

    <!-- Modal: Start New Evaluation (NO SEMESTER) -->
    <div x-show="periodModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="periodModal = false" class="bg-white rounded-3xl border-2 border-slate-200 max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b pb-4 border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-[#8b1818] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Start New Evaluation</h3>
                        <p class="text-xs text-slate-400 font-bold">Configure and launch evaluation period</p>
                    </div>
                </div>
                <button @click="periodModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-base"></i></button>
            </div>
            
            <form method="POST" action="{{ route('admin.evaluations.start-cycle') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Evaluation Name</label>
                    <input type="text" name="name" placeholder="e.g. Faculty Evaluation – Midterm" required class="w-full px-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">School Year Range</label>
                    <input type="text" name="school_year" value="2026-2027" placeholder="e.g. 2026-2027" required class="w-full px-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Start Date</label>
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">End Date</label>
                        <input type="date" name="end_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required class="w-full px-3 py-2 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="periodModal = false" class="px-5 py-2.5 rounded-xl bg-slate-100 font-black text-xs text-slate-600 uppercase">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#8b1818] font-black text-xs text-white uppercase shadow-md shadow-red-950/20">Start Evaluation</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection