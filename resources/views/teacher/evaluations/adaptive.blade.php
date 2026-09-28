@extends('layouts.app')

@section('title', 'Faculty Evaluation Portal - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-100/60" x-data="{ tab: 'peer' }">
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-[#8b1818] text-amber-300 flex items-center justify-center text-lg"><i class="fa-solid fa-star-half-stroke"></i></div>
            <div>
                <h1 class="text-xl font-black text-slate-900">Faculty Evaluation Portal</h1>
                <p class="text-xs text-slate-500 font-semibold">Complete the active institutional evaluation forms.</p>
            </div>
        </div>
    </header>

    <main class="p-5 lg:p-10 max-w-[1500px] mx-auto space-y-6">
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold rounded-2xl">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold rounded-2xl">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold rounded-2xl">{{ $errors->first() }}</div>
        @endif

        @if($allTeacherEvaluationsDone)
            @if($publishedEvaluationResult)
                @php
                    $result = $publishedEvaluationResult;
                    $resultLabels = ['student' => 'Student', 'principal' => 'Principal', 'self' => 'Self', 'peer' => 'Peer'];
                @endphp
                <div class="bg-white border-2 border-emerald-200 rounded-3xl p-6 shadow-xs space-y-5">
                    <div class="flex items-start gap-4"><div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="fa-solid fa-ranking-star"></i></div><div><h2 class="text-base font-black text-emerald-900">Your evaluation result is published</h2><p class="text-xs text-emerald-700 font-semibold mt-1">Thank you for completing your evaluation. The administrator has published your result.</p></div></div>
                    <div class="flex items-end gap-2 border-t border-slate-100 pt-4"><span class="text-4xl font-black text-[#8b1818]">{{ number_format($result->weighted_score, 2) }}</span><span class="text-sm font-bold text-slate-400 mb-1">/ 100 weighted score</span></div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($resultLabels as $type => $label)
                            @php $average = $result->averages[$type]; $contribution = $average === null ? null : (($average / 5) * $result->weights[$type]); @endphp
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200"><span class="block text-[10px] font-black uppercase text-slate-500">{{ $label }} ({{ $result->weights[$type] }}%)</span><strong class="block text-lg font-black text-slate-800 mt-1">{{ $average === null ? '--' : number_format($average, 2) }}</strong><span class="text-[10px] font-bold text-slate-400">Contribution: {{ $contribution === null ? '--' : number_format($contribution, 2) }}</span></div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="p-6 bg-emerald-50 border-2 border-emerald-200 rounded-2xl flex items-start gap-4"><div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-check"></i></div><div><h2 class="text-base font-black text-emerald-900">Evaluation completed. Thank you!</h2><p class="text-xs text-emerald-700 font-semibold mt-1">Your Peer and Self evaluations have been saved. Please wait for the administrator to publish the evaluation results.</p><div class="flex flex-wrap gap-2 mt-3 text-[10px] font-black uppercase tracking-wider"><span class="px-2.5 py-1 rounded-full bg-white text-emerald-700 border border-emerald-200"><i class="fa-solid fa-check mr-1"></i> Peer Done</span><span class="px-2.5 py-1 rounded-full bg-white text-emerald-700 border border-emerald-200"><i class="fa-solid fa-check mr-1"></i> Self Done</span></div></div></div>
            @endif
        @elseif(!$evaluationActive)
            @if($evaluationCompleted)
                <div class="p-6 bg-emerald-50 border-2 border-emerald-200 rounded-2xl flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h2 class="text-base font-black text-emerald-900">Thank you for evaluating.</h2>
                        <p class="text-xs text-emerald-700 font-semibold mt-1">The {{ $evaluationStatusCycle->name ?? 'evaluation cycle' }} is closed. Your completed responses have been saved.</p>
                        <div class="flex flex-wrap gap-2 mt-3 text-[10px] font-black uppercase tracking-wider">
                            @if($peerEvaluationDone)<span class="px-2.5 py-1 rounded-full bg-white text-emerald-700 border border-emerald-200"><i class="fa-solid fa-check mr-1"></i> Peer Done</span>@endif
                            @if($selfEvaluationDone)<span class="px-2.5 py-1 rounded-full bg-white text-emerald-700 border border-emerald-200"><i class="fa-solid fa-check mr-1"></i> Self Done</span>@endif
                        </div>
                    </div>
                </div>
            @else
                <div class="p-6 bg-slate-50 border-2 border-slate-200 rounded-2xl flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-lock"></i></div>
                    <div><h2 class="text-base font-black text-slate-800">No Active Faculty Evaluation</h2><p class="text-xs text-slate-500 font-semibold mt-1">The administrator has not opened an evaluation cycle.</p></div>
                </div>
            @endif
        @else
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-4">
                <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-lock-open"></i></div><div><h2 class="text-sm font-black text-emerald-900">{{ $activeCycle->name }}</h2><p class="text-xs text-emerald-700 font-semibold">Active from {{ \Carbon\Carbon::parse($activeCycle->start_date)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($activeCycle->end_date)->format('M d, Y') }}</p></div></div>
                <span class="px-3 py-1 rounded-full bg-white text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase">Active Cycle</span>
            </div>

            <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                <button type="button" @click="tab = 'peer'" :class="tab === 'peer' ? 'bg-[#8b1818] text-white' : 'bg-white text-slate-600 border border-slate-200'" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider"><i class="fa-solid fa-users mr-1.5"></i> Peer Evaluation</button>
                <button type="button" @click="tab = 'self'" :class="tab === 'self' ? 'bg-[#8b1818] text-white' : 'bg-white text-slate-600 border border-slate-200'" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider"><i class="fa-solid fa-user-pen mr-1.5"></i> Self Evaluation</button>
            </div>

            <section x-show="tab === 'peer'">
                <form action="{{ route('teacher.evaluations.peer.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 shadow-xs">
                        <div class="border-b border-slate-100 pb-5">
                            <h2 class="text-lg font-black text-slate-900">{{ $peerForm->title ?? 'Faculty Peer Evaluation' }}</h2><p class="text-xs text-slate-500 font-semibold mt-1">{{ $peerForm->instructions ?? '' }}</p>
                        </div>
                        @if($hasPeerLikertQuestions)
                            <div class="mt-5 p-4 rounded-2xl bg-blue-50 border border-blue-200">
                                <p class="text-xs font-black text-blue-900">Likert Rating Guide</p>
                                <p class="text-[11px] text-blue-800 font-semibold mt-1">Select one radio rating for every Likert question: {{ $peerRatingScales->map(fn($scale) => $scale->value . ' - ' . $scale->label)->implode(' | ') }}.</p>
                            </div>
                        @endif
                        <div class="mt-5 p-4 sm:p-5 rounded-2xl bg-slate-50 border-2 border-slate-200">
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Select Faculty Member <span class="text-rose-600">*</span></p>
                            <select name="evaluatee_id" required class="mt-2 w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-white text-xs font-bold focus:border-[#8b1818] outline-none">
                                <option value="">Choose a colleague</option>
                                @foreach($peers as $peer)
                                    <option value="{{ $peer->id }}" @disabled($evaluatedPeerIds->contains($peer->id))>{{ $peer->first_name }} {{ $peer->last_name }}{{ $evaluatedPeerIds->contains($peer->id) ? ' - Already Evaluated' : '' }}</option>
                                @endforeach
                            </select>
                            @php $evaluatedPeers = $peers->filter(fn($peer) => $evaluatedPeerIds->contains($peer->id)); @endphp
                            @if($evaluatedPeers->isNotEmpty())
                                <p class="mt-2 text-[11px] font-bold text-slate-500">Already evaluated: {{ $evaluatedPeers->map(fn($peer) => $peer->first_name . ' ' . $peer->last_name)->implode(', ') }}</p>
                            @endif
                        </div>
                    </div>
                    @foreach($peerSections as $section)
                        <div class="bg-white border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs">
                            <div class="px-6 py-4 bg-slate-50 border-b-2 border-slate-200 flex items-center justify-between"><h3 class="text-sm font-black text-slate-900 uppercase">{{ $section->title }}</h3><span class="text-[10px] font-black text-slate-500">{{ $section->direct_questions->count() + $section->subheadings->sum(fn($sub) => $sub->questions->count()) }} indicators</span></div>
                            <div class="p-4 sm:p-6 space-y-5">
                                @foreach($section->direct_questions as $question)
                                    @include('teacher.evaluations.partials.question-card', ['question' => $question, 'scales' => $peerRatingScales])
                                @endforeach
                                @foreach($section->subheadings as $subheading)
                                    <div class="space-y-3"><h4 class="text-sm font-black text-[#8b1818]">{{ $subheading->title }}</h4>@foreach($subheading->questions as $question) @include('teacher.evaluations.partials.question-card', ['question' => $question, 'scales' => $peerRatingScales]) @endforeach</div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div class="bg-white border-2 border-slate-200 rounded-3xl p-6"><label class="text-xs font-black text-slate-700">Comments <span class="text-slate-400 font-semibold">(optional)</span><textarea name="comments" rows="4" class="mt-2 w-full p-4 rounded-xl border-2 border-slate-200 text-sm font-semibold focus:border-[#8b1818] outline-none"></textarea></label><button type="submit" class="mt-5 w-full py-3.5 rounded-xl bg-[#8b1818] text-white font-black text-xs uppercase">Submit Peer Evaluation</button></div>
                </form>
            </section>

            <section x-show="tab === 'self'" x-cloak>
                <form action="{{ route('teacher.evaluations.self.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 shadow-xs"><h2 class="text-lg font-black text-slate-900">{{ $selfForm->title ?? 'Teacher Self-Evaluation' }}</h2><p class="text-xs text-slate-500 font-semibold mt-1">{{ $selfForm->instructions ?? '' }}</p></div>
                    @foreach($selfSections as $section)
                        <div class="bg-white border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs"><div class="px-6 py-4 bg-slate-50 border-b-2 border-slate-200"><h3 class="text-sm font-black text-slate-900 uppercase">{{ $section->title }}</h3></div><div class="p-4 sm:p-6 space-y-5">@foreach($section->direct_questions as $question) @include('teacher.evaluations.partials.question-card', ['question' => $question, 'scales' => $selfRatingScales]) @endforeach @foreach($section->subheadings as $subheading)<div class="space-y-3"><h4 class="text-sm font-black text-[#8b1818]">{{ $subheading->title }}</h4>@foreach($subheading->questions as $question) @include('teacher.evaluations.partials.question-card', ['question' => $question, 'scales' => $selfRatingScales]) @endforeach</div>@endforeach</div></div>
                    @endforeach
                    <div class="bg-white border-2 border-slate-200 rounded-3xl p-6"><label class="text-xs font-black text-slate-700">Reflection / Action Summary <span class="text-slate-400 font-semibold">(optional)</span><textarea name="comments" rows="6" class="mt-2 w-full p-4 rounded-xl border-2 border-slate-200 text-sm font-semibold focus:border-[#8b1818] outline-none"></textarea></label><button type="submit" class="mt-5 w-full py-3.5 rounded-xl bg-[#8b1818] text-white font-black text-xs uppercase">Submit Self Evaluation</button></div>
                </form>
            </section>
        @endif
    </main>
</div>
@endsection
