@php
    $questionType = $question->type ?? 'likert';
    $options = [];
    if (!empty($question->options)) {
        $options = is_array($question->options) ? $question->options : json_decode($question->options, true);
        if (!is_array($options)) {
            $options = array_map('trim', explode(',', (string) $question->options));
        }
    }
@endphp
<div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 hover:border-slate-300 transition">
    <div class="flex items-start gap-3">
        <span class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-black text-xs flex items-center justify-center shrink-0">{{ $question->order_num }}</span>
        <div class="flex-1 min-w-0">
            @if(!empty($question->category))
                <span class="text-[10px] font-black text-[#8b1818] uppercase tracking-wider block mb-1">{{ $question->category }}</span>
            @endif
            <p class="text-sm font-bold text-slate-800 leading-relaxed">{{ $question->question }}</p>

            @if($questionType === 'likert')
                <div class="flex flex-nowrap gap-2 mt-4 overflow-x-auto pb-1">
                    @foreach($scales as $scale)
                        @php $scaleLabel = preg_replace('/^\s*\d+\s*-\s*/', '', (string) $scale->label); @endphp
                        <label class="relative cursor-pointer flex-1 min-w-[72px]">
                            <span class="flex items-center justify-center gap-1.5 h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 transition text-xs font-black whitespace-nowrap">
                                <input type="radio" name="ratings[{{ $question->id }}]" value="{{ $scale->value }}" required class="w-3.5 h-3.5 accent-[#8b1818] shrink-0">
                                <span class="text-sm">{{ $scale->value }}</span><span class="text-[8px] uppercase tracking-wide">{{ $scaleLabel }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @elseif($questionType === 'multiple_choice')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4">
                    @foreach($options as $option)
                        <label class="flex items-center gap-2 p-3 rounded-xl border-2 border-slate-200 bg-slate-50 cursor-pointer has-[:checked]:border-[#8b1818] has-[:checked]:bg-red-50 text-xs font-bold text-slate-700"><input type="radio" name="ratings[{{ $question->id }}]" value="{{ $option }}" required class="accent-[#8b1818]">{{ $option }}</label>
                    @endforeach
                </div>
            @elseif($questionType === 'yes_no')
                <div class="flex gap-2 mt-4"><label class="flex items-center gap-2 px-4 py-3 rounded-xl border-2 border-slate-200 cursor-pointer text-xs font-black"><input type="radio" name="ratings[{{ $question->id }}]" value="Yes" required class="accent-[#8b1818]">Yes</label><label class="flex items-center gap-2 px-4 py-3 rounded-xl border-2 border-slate-200 cursor-pointer text-xs font-black"><input type="radio" name="ratings[{{ $question->id }}]" value="No" required class="accent-[#8b1818]">No</label></div>
            @elseif($questionType === 'open_ended')
                <textarea name="text_responses[{{ $question->id }}]" rows="3" required class="mt-4 w-full p-3 rounded-xl border-2 border-slate-200 text-sm font-semibold focus:border-[#8b1818] outline-none" placeholder="Enter your response..."></textarea>
            @endif
        </div>
    </div>
</div>
