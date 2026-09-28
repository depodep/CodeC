@extends('layouts.app')

@section('title', 'Faculty Evaluation Portal - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60 font-sans">

    <!-- ================= Top Professional Header Bar ================= -->
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shadow-red-950/10 shrink-0">
                <i class="fa-solid fa-star-half-stroke text-amber-300"></i>
            </div>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Faculty Evaluation Portal</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Submit Peer and Self-evaluations based on official institutional criteria table</p>
            </div>
        </div>
    </header>

    <!-- ================= Main Content Container ================= -->
    <main class="p-6 lg:p-10 w-full space-y-8 flex-1 max-w-[1500px] mx-auto">

        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs font-bold rounded-xl flex items-center gap-3 shadow-xs w-full">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-900 text-xs font-bold rounded-xl shadow-xs w-full">
                <p class="uppercase font-black mb-1">Please fix the following errors:</p>
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!$evaluationActive)
            <div class="p-6 bg-slate-50 border-2 border-slate-200 rounded-2xl flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-800">No Active Faculty Evaluation</h2>
                    <p class="text-xs text-slate-500 font-semibold mt-1">Faculty selection, peer evaluation, and self-evaluation are currently closed. The administrator must start an evaluation cycle before submissions can be made.</p>
                </div>
            </div>
        @else
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-unlock"></i></div>
                    <div>
                        <h2 class="text-sm font-black text-emerald-900">{{ $activeCycle->name }}</h2>
                        <p class="text-xs text-emerald-700 font-semibold">Active from {{ \Carbon\Carbon::parse($activeCycle->start_date)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($activeCycle->end_date)->format('M d, Y') }}</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full bg-white text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase tracking-wider">Active Cycle</span>
            </div>

        <!-- Tab Navigation Buttons -->
        <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
            <button onclick="switchTeacherTab('peer')" id="tabPeerBtn" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm">
                <i class="fa-solid fa-users mr-1.5"></i> Peer Evaluation
            </button>
            <button onclick="switchTeacherTab('self')" id="tabSelfBtn" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">
                <i class="fa-solid fa-user-pen mr-1.5"></i> Self Evaluation
            </button>
        </div>

        <!-- ================= PEER EVALUATION TAB ================= -->
        <div id="tabPeerContent" class="space-y-6">
            <form action="{{ route('teacher.evaluations.peer.store') }}" method="POST" class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                @csrf
                <div class="border-b border-slate-100 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight">{{ $peerForm->title ?? 'Faculty Peer Evaluation Form' }}</h2>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $peerForm->instructions ?? 'Evaluate a colleague based on official criteria matrix.' }}</p>
                    </div>
                    <div class="w-full md:w-72">
                        <label class="block text-[10px] font-black text-slate-700 uppercase mb-1">Select Faculty Member *</label>
                        <select name="evaluatee_id" required class="w-full py-2 px-3 text-xs font-bold rounded-lg border border-slate-300 bg-slate-50 focus:bg-white focus:border-[#8b1818] outline-none transition cursor-pointer">
                            <option value="">-- Choose Peer --</option>
                            @foreach($peers as $peer)
                                <option value="{{ $peer->id }}" @disabled($evaluatedPeerIds->contains($peer->id))>
                                    Prof. {{ $peer->first_name }} {{ $peer->last_name }}{{ $evaluatedPeerIds->contains($peer->id) ? ' (Already Evaluated)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Table Format Matrix -->
                <div class="overflow-x-auto rounded-xl border border-slate-300">
                    <table class="w-full border-collapse bg-white text-left text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-slate-900 border-b border-slate-300">
                                <th rowspan="2" class="p-3.5 font-black uppercase text-center border-r border-slate-300 w-12">#</th>
                                <th rowspan="2" class="p-3.5 font-black uppercase border-r border-slate-300">CRITERIA</th>
                                <th colspan="5" class="p-2 font-black uppercase text-center tracking-wider bg-slate-200/70">RATING</th>
                            </tr>
                            <tr class="bg-slate-50 text-slate-700 border-b border-slate-300 text-[11px] font-bold text-center">
                                <th colspan="2" class="py-1.5 px-2 border-r border-slate-200 text-[#8b1818]">High (5 - 4)</th>
                                <th colspan="2" class="py-1.5 px-2 border-r border-slate-200 text-amber-700">Moderate (3 - 2)</th>
                                <th class="py-1.5 px-2 text-slate-600">Low (1)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($peerQuestions as $index => $q)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-3.5 text-center font-bold text-slate-500 border-r border-slate-200">{{ $index + 1 }}</td>
                                    <td class="p-3.5 font-bold text-slate-800 border-r border-slate-200">
                                        @if($q->category)
                                            <span class="text-[10px] font-black text-[#8b1818] uppercase tracking-wider block mb-0.5">{{ $q->category }}</span>
                                        @endif
                                        {{ $q->question }}
                                    </td>
                                    <!-- Radio Buttons for Ratings 5, 4, 3, 2, 1 -->
                                    @foreach([5, 4, 3, 2, 1] as $score)
                                        <td class="p-3 text-center border-r border-slate-100 last:border-r-0 w-14">
                                            <label class="flex items-center justify-center cursor-pointer">
                                                <input type="radio" name="ratings[{{ $q->id }}]" value="{{ $score }}" required class="w-4 h-4 text-[#8b1818] accent-[#8b1818] cursor-pointer">
                                                <span class="ml-1.5 text-xs font-bold text-slate-600 md:hidden">{{ $score }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-500 font-bold">No peer evaluation questions available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2">Comments & Recommendations (Optional)</label>
                    <textarea name="comments" rows="3" placeholder="Provide constructive feedback or remarks..." class="w-full p-4 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-[#8b1818] outline-none transition"></textarea>
                </div>

                <button type="submit" class="w-full py-3.5 bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-sm transition cursor-pointer" {{ !$peers->whereNotIn('id', $evaluatedPeerIds)->count() ? 'disabled' : '' }}>
                    Submit Peer Evaluation
                </button>
            </form>
        </div>

        <!-- ================= SELF EVALUATION TAB ================= -->
        <div id="tabSelfContent" class="space-y-6 hidden">
            <form action="{{ route('teacher.evaluations.self.store') }}" method="POST" class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                @csrf
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-black text-slate-900 tracking-tight">{{ $selfForm->title ?? 'Faculty Self-Evaluation Form' }}</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $selfForm->instructions ?? 'Reflect on your own performance based on institutional standards.' }}</p>
                </div>

                <!-- Table Format Matrix for Self Evaluation -->
                <div class="overflow-x-auto rounded-xl border border-slate-300">
                    <table class="w-full border-collapse bg-white text-left text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-slate-900 border-b border-slate-300">
                                <th rowspan="2" class="p-3.5 font-black uppercase text-center border-r border-slate-300 w-12">#</th>
                                <th rowspan="2" class="p-3.5 font-black uppercase border-r border-slate-300">CRITERIA</th>
                                <th colspan="5" class="p-2 font-black uppercase text-center tracking-wider bg-slate-200/70">RATING</th>
                            </tr>
                            <tr class="bg-slate-50 text-slate-700 border-b border-slate-300 text-[11px] font-bold text-center">
                                <th colspan="2" class="py-1.5 px-2 border-r border-slate-200 text-[#8b1818]">High (5 - 4)</th>
                                <th colspan="2" class="py-1.5 px-2 border-r border-slate-200 text-amber-700">Moderate (3 - 2)</th>
                                <th class="py-1.5 px-2 text-slate-600">Low (1)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($selfQuestions as $index => $q)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-3.5 text-center font-bold text-slate-500 border-r border-slate-200">{{ $index + 1 }}</td>
                                    <td class="p-3.5 font-bold text-slate-800 border-r border-slate-200">
                                        @if($q->category)
                                            <span class="text-[10px] font-black text-[#8b1818] uppercase tracking-wider block mb-0.5">{{ $q->category }}</span>
                                        @endif
                                        {{ $q->question }}
                                    </td>
                                    <!-- Radio Buttons for Ratings 5, 4, 3, 2, 1 -->
                                    @foreach([5, 4, 3, 2, 1] as $score)
                                        <td class="p-3 text-center border-r border-slate-100 last:border-r-0 w-14">
                                            <label class="flex items-center justify-center cursor-pointer">
                                                <input type="radio" name="ratings[{{ $q->id }}]" value="{{ $score }}" required class="w-4 h-4 text-[#8b1818] accent-[#8b1818] cursor-pointer">
                                                <span class="ml-1.5 text-xs font-bold text-slate-600 md:hidden">{{ $score }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-500 font-bold">No self-evaluation questions available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase mb-2">Personal Reflection / Development Goals (Optional)</label>
                    <textarea name="comments" rows="3" placeholder="Write your professional reflections or goals..." class="w-full p-4 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-[#8b1818] outline-none transition"></textarea>
                </div>

                <button type="submit" class="w-full py-3.5 bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-sm transition cursor-pointer" {{ $selfEvaluationDone ? 'disabled' : '' }}>
                    Submit Self Evaluation
                </button>
            </form>
        </div>

        @endif

    </main>
</div>

@push('scripts')
<script>
    function switchTeacherTab(tab) {
        const peerBtn = document.getElementById('tabPeerBtn');
        const selfBtn = document.getElementById('tabSelfBtn');
        const peerContent = document.getElementById('tabPeerContent');
        const selfContent = document.getElementById('tabSelfContent');

        [peerBtn, selfBtn].forEach(btn => {
            btn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-white text-slate-600 hover:bg-slate-100 border border-slate-200";
        });
        [peerContent, selfContent].forEach(content => content.classList.add('hidden'));

        if (tab === 'peer') {
            peerBtn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm";
            peerContent.classList.remove('hidden');
        } else if (tab === 'self') {
            selfBtn.className = "px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition cursor-pointer bg-[#8b1818] text-white shadow-sm";
            selfContent.classList.remove('hidden');
        }
    }
</script>
@endpush
@endsection