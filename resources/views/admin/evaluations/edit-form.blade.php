@extends('layouts.app')

@section('title', 'Edit Evaluation Form - SIATRACK Admin')

@section('content')
<style>
    #evaluation-editor-actions {
        left: 18rem;
    }

    html.sidebar-collapsed #evaluation-editor-actions {
        left: 5rem;
    }
</style>
<div x-data="formBuilder()" class="w-full min-h-screen bg-slate-100/60 pb-28 font-sans">
    
    <!-- Top Header -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-6 sm:px-10 py-4 sticky top-0 z-30 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('admin.evaluations.periods') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition" title="Back to Evaluation Forms">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Edit Evaluation Form</h1>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-bold text-slate-500">Evaluator Type:</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                        {{ $formTypeName }}
                    </span>
                    <span class="text-[11px] font-bold text-slate-400">&bull; Version {{ $form->version ?? 1 }}</span>
                </div>
                @if(isset($formVersions) && $formVersions->isNotEmpty())
                    <details class="mt-2 text-[10px] font-bold text-slate-500">
                        <summary class="cursor-pointer text-[#8b1818]">Previous saved versions</summary>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($formVersions as $formVersion)
                                <form method="POST" action="{{ route('admin.evaluations.forms.versions.restore', ['type' => $type, 'version' => $formVersion->version]) }}" onsubmit="return confirm('Restore version {{ $formVersion->version }}? The current form will be archived first.');">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-slate-200 bg-white px-2 py-1 hover:border-[#8b1818] hover:text-[#8b1818]">
                                        v{{ $formVersion->version }} · {{ \Carbon\Carbon::parse($formVersion->created_at)->format('M d, Y') }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>

    </header>

    <main class="max-w-6xl mx-auto pt-6 px-4 sm:px-6 lg:px-8 space-y-6">
        
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

        <!-- Form for Submitting JSON payload -->
        <form id="save-form-element" method="POST" action="{{ route('admin.evaluations.forms.save', ['type' => $type]) }}">
            @csrf
            <input type="hidden" name="title" :value="formTitle">
            <input type="hidden" name="instructions" :value="instructions">
            <input type="hidden" name="form_data" :value="JSON.stringify({ rating_scales: ratingScales, sections: sections })">
        </form>

        <!-- 1. Form Information Card -->
        <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 sm:p-8 space-y-4 shadow-xs">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-xl bg-red-100 text-[#8b1818] font-black text-xs flex items-center justify-center">
                    <i class="fa-solid fa-heading"></i>
                </div>
                <h3 class="text-base font-black text-slate-900">Form Header & Information</h3>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Form Title</label>
                    <input type="text" x-model="formTitle" placeholder="e.g. Student Evaluation of Teaching Performance" required class="w-full px-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Instructions for Evaluators</label>
                    <textarea x-model="instructions" rows="3" placeholder="Instructions displayed at the top of evaluation form..." class="w-full px-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]"></textarea>
                </div>
            </div>
        </div>

        <!-- 2. Configurable Rating Scale Management Card -->
        <div class="bg-white border-2 border-slate-200 rounded-3xl p-6 sm:p-8 space-y-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-900 font-black text-xs flex items-center justify-center">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Rating Scale Configuration</h3>
                        <p class="text-[11px] text-slate-400 font-bold">Customize scale values, descriptive labels, and N/A options</p>
                    </div>
                </div>
                <button type="button" @click="addScaleOption()" class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-plus text-amber-600"></i>
                    <span>Add Scale Option</span>
                </button>
            </div>

            <div class="space-y-2.5">
                <template x-for="(scale, sIdx) in ratingScales" :key="sIdx">
                    <div class="flex items-center gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                        <div class="w-16">
                            <label class="text-[10px] font-bold text-slate-400 block mb-0.5">Value</label>
                            <input type="text" x-model="scale.value" class="w-full px-2.5 py-1.5 text-xs font-mono font-black text-center border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818] bg-white">
                        </div>
                        <div class="flex-1">
                            <label class="text-[10px] font-bold text-slate-400 block mb-0.5">Descriptive Rating Label</label>
                            <input type="text" x-model="scale.label" placeholder="e.g. Always Manifested" class="w-full px-3 py-1.5 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818] bg-white">
                        </div>
                        <div class="flex items-center gap-1 pt-4">
                            <button type="button" @click="moveScaleUp(sIdx)" :disabled="sIdx === 0" class="p-1.5 text-slate-400 hover:text-slate-700 disabled:opacity-30" title="Move Up"><i class="fa-solid fa-arrow-up text-xs"></i></button>
                            <button type="button" @click="moveScaleDown(sIdx)" :disabled="sIdx === ratingScales.length - 1" class="p-1.5 text-slate-400 hover:text-slate-700 disabled:opacity-30" title="Move Down"><i class="fa-solid fa-arrow-down text-xs"></i></button>
                            <button type="button" @click="removeScaleOption(sIdx)" class="p-1.5 text-slate-400 hover:text-rose-600" title="Remove"><i class="fa-solid fa-trash-can text-xs"></i></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 3. Hierarchical Sections & Subheadings Builder -->
        <div class="space-y-6">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Questionnaire Hierarchy Builder</h3>
                    <p class="text-xs text-slate-500 font-bold">Manage headings, subheadings, and question indicators with automatic numbering</p>
                </div>
                <button type="button" @click="addSection()" class="px-4 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-2 shrink-0">
                    <i class="fa-solid fa-plus text-amber-300 text-xs"></i>
                    <span>Add Section</span>
                </button>
            </div>

            <!-- Empty State when no sections exist -->
            <div x-show="sections.length === 0" class="bg-white border-2 border-dashed border-slate-300 rounded-3xl p-10 text-center space-y-4 shadow-xs">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center mx-auto text-base">
                    <i class="fa-solid fa-folder-plus"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="text-base font-black text-slate-900">No Sections in Evaluation Form</h4>
                    <p class="text-xs text-slate-500 font-semibold max-w-sm mx-auto">Click below to add your first heading section and start adding questions.</p>
                </div>
                <button type="button" @click="addSection()" class="px-6 py-3 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider transition shadow-md inline-flex items-center gap-2">
                    <i class="fa-solid fa-plus text-amber-300 text-xs"></i>
                    <span>Add First Section</span>
                </button>
            </div>

            <template x-for="(sec, secIdx) in sections" :key="secIdx">
                <div class="bg-white border-2 border-slate-200 rounded-3xl overflow-hidden shadow-xs transition">
                    <!-- Section Header Bar -->
                    <div class="bg-slate-50 px-6 py-4 border-b-2 border-slate-200 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 flex-1">
                            <button type="button" @click="sec.expanded = !sec.expanded" class="text-slate-500 hover:text-slate-900 transition mr-1">
                                <i class="fa-solid text-sm" :class="sec.expanded ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            </button>

                            <!-- Left-Side Reorder Controls for Section -->
                            <div class="flex items-center gap-1 bg-white px-2 py-1 rounded-xl border border-slate-200 shadow-2xs shrink-0">
                                <button type="button" @click="moveSectionUp(secIdx)" :disabled="secIdx === 0" class="p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30" title="Move Section Up"><i class="fa-solid fa-arrow-up text-xs"></i></button>
                                <button type="button" @click="moveSectionDown(secIdx)" :disabled="secIdx === sections.length - 1" class="p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30" title="Move Section Down"><i class="fa-solid fa-arrow-down text-xs"></i></button>
                            </div>

                            <!-- Automatic Section Roman Prefix Badge -->
                            <span class="px-2.5 py-1 rounded-xl bg-red-100 text-[#8b1818] border border-red-200 font-mono font-black text-xs shrink-0" x-text="toRoman(secIdx + 1)"></span>

                            <!-- Section Title Input -->
                            <div class="flex-1 max-w-2xl">
                                <input type="text" x-model="sec.title" placeholder="Section Title (e.g. INSTRUCTIONAL COMPETENCE)" class="w-full px-3 py-1.5 text-xs font-black uppercase text-slate-900 border border-slate-300 rounded-xl outline-none focus:border-[#8b1818] bg-white">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="removeSection(secIdx)" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Delete Section"><i class="fa-solid fa-trash-can text-xs"></i></button>
                        </div>
                    </div>

                    <!-- Section Body -->
                    <div x-show="sec.expanded" class="p-6 space-y-6">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Section Description (Optional)</label>
                            <input type="text" x-model="sec.description" placeholder="Optional description detailing section scope..." class="w-full px-3 py-1.5 text-xs font-semibold border border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                        </div>

                        <!-- Direct Questions under Section -->
                        <div x-show="sec.direct_questions && sec.direct_questions.length > 0" class="space-y-3 pl-2">
                            <span class="text-[11px] font-black uppercase text-slate-400 tracking-wider block mb-1">Direct Questions under Section</span>
                            <template x-for="(q, qIdx) in sec.direct_questions" :key="qIdx">
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-3">
                                    <div class="flex items-start gap-3">
                                        <!-- Left-Side Reorder Controls & Automatic Question Number for Direct Question -->
                                        <div class="flex items-center gap-1.5 shrink-0 mt-1">
                                            <div class="flex items-center gap-0.5 bg-white px-1.5 py-1 rounded-lg border border-slate-200 shadow-2xs">
                                                <button type="button" @click="moveDirectQuestionUp(secIdx, qIdx)" :disabled="qIdx === 0" class="p-0.5 text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="fa-solid fa-arrow-up text-[10px]"></i></button>
                                                <button type="button" @click="moveDirectQuestionDown(secIdx, qIdx)" :disabled="qIdx === sec.direct_questions.length - 1" class="p-0.5 text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="fa-solid fa-arrow-down text-[10px]"></i></button>
                                            </div>
                                            <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 font-mono text-xs font-black flex items-center justify-center shrink-0" x-text="qIdx + 1"></span>
                                        </div>

                                        <div class="flex-1 space-y-2">
                                            <textarea x-model="q.question" rows="2" placeholder="Type question / indicator statement..." class="w-full px-3 py-2 text-xs font-bold border border-slate-200 rounded-xl outline-none focus:border-[#8b1818] bg-white"></textarea>
                                            
                                            <div class="flex flex-wrap items-center gap-3">
                                                <div class="flex items-center gap-1.5">
                                                    <label class="text-[10px] font-bold text-slate-400">Type:</label>
                                                    <select x-model="q.type" class="px-2.5 py-1 text-xs font-bold border border-slate-200 rounded-lg outline-none bg-white">
                                                        <option value="likert">Likert Scale (1-5)</option>
                                                        <option value="multiple_choice">Multiple Choice</option>
                                                        <option value="yes_no">Yes / No</option>
                                                        <option value="open_ended">Open-Ended (Text)</option>
                                                    </select>
                                                </div>

                                                <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                                                    <input type="checkbox" x-model="q.is_required" class="text-[#8b1818] rounded border-slate-300">
                                                    <span>Required</span>
                                                </label>
                                            </div>

                                            <!-- Options for MCQ -->
                                            <div x-show="q.type === 'multiple_choice'" class="space-y-1.5 pt-1">
                                                <label class="text-[10px] font-bold text-slate-400 block">Multiple Choice Options</label>
                                                <p class="text-[10px] font-semibold text-slate-500">Enter one option per value, separated by commas. Example: Option 1, Option 2, Option 3.</p>
                                                <input type="text" :value="q.options ? q.options.join(', ') : ''" @input="q.options = $event.target.value.split(',').map(s=>s.trim())" placeholder="Option 1, Option 2, Option 3 (comma separated)" class="w-full px-3 py-1 text-xs border border-slate-200 rounded-lg outline-none bg-white">
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1 shrink-0 pt-1">
                                            <button type="button" @click="removeDirectQuestion(secIdx, qIdx)" class="p-1 text-slate-400 hover:text-rose-600" title="Delete Question"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Subheadings under Section -->
                        <div class="space-y-4">
                            <template x-for="(sub, subIdx) in sec.subheadings" :key="subIdx">
                                <div class="bg-blue-50/40 p-5 rounded-2xl border-2 border-blue-100 space-y-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 flex-1">
                                            <!-- Left-Side Reorder Controls & Automatic Subheading Letter for Subheading -->
                                            <div class="flex items-center gap-1 bg-white px-2 py-1 rounded-xl border border-blue-200 shadow-2xs shrink-0">
                                                <button type="button" @click="moveSubheadingUp(secIdx, subIdx)" :disabled="subIdx === 0" class="p-0.5 text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="fa-solid fa-arrow-up text-xs"></i></button>
                                                <button type="button" @click="moveSubheadingDown(secIdx, subIdx)" :disabled="subIdx === sec.subheadings.length - 1" class="p-0.5 text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="fa-solid fa-arrow-down text-xs"></i></button>
                                            </div>

                                            <span class="px-2.5 py-1 rounded-xl bg-blue-100 text-blue-900 border border-blue-200 font-mono font-black text-xs shrink-0" x-text="toAlpha(subIdx)"></span>

                                            <input type="text" x-model="sub.title" placeholder="Subheading Title (e.g. Classroom Management)" class="w-full px-3 py-1.5 text-xs font-bold text-blue-950 border border-blue-200 rounded-xl outline-none focus:border-blue-600 bg-white">
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" @click="removeSubheading(secIdx, subIdx)" class="p-1 text-slate-400 hover:text-rose-600" title="Delete Subheading"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                        </div>
                                    </div>

                                    <!-- Questions under Subheading -->
                                    <div class="space-y-3 pl-3">
                                        <template x-for="(subQ, sqIdx) in sub.questions" :key="sqIdx">
                                            <div class="bg-white p-4 rounded-2xl border border-slate-200 space-y-3 shadow-2xs">
                                                <div class="flex items-start gap-3">
                                                    <!-- Left-Side Reorder Controls & Automatic Question Number for Subheading Question -->
                                                    <div class="flex items-center gap-1.5 shrink-0 mt-1">
                                                        <div class="flex items-center gap-0.5 bg-slate-50 px-1.5 py-1 rounded-lg border border-slate-200 shadow-2xs">
                                                            <button type="button" @click="moveSubQuestionUp(secIdx, subIdx, sqIdx)" :disabled="sqIdx === 0" class="p-0.5 text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="fa-solid fa-arrow-up text-[10px]"></i></button>
                                                            <button type="button" @click="moveSubQuestionDown(secIdx, subIdx, sqIdx)" :disabled="sqIdx === sub.questions.length - 1" class="p-0.5 text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="fa-solid fa-arrow-down text-[10px]"></i></button>
                                                        </div>
                                                        <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-900 font-mono text-xs font-black flex items-center justify-center shrink-0" x-text="sqIdx + 1"></span>
                                                    </div>

                                                    <div class="flex-1 space-y-2">
                                                        <textarea x-model="subQ.question" rows="2" placeholder="Type question statement..." class="w-full px-3 py-2 text-xs font-bold border border-slate-200 rounded-xl outline-none focus:border-[#8b1818]"></textarea>

                                                        <div class="flex flex-wrap items-center gap-3">
                                                            <div class="flex items-center gap-1.5">
                                                                <label class="text-[10px] font-bold text-slate-400">Type:</label>
                                                                <select x-model="subQ.type" class="px-2.5 py-1 text-xs font-bold border border-slate-200 rounded-lg outline-none bg-white">
                                                                    <option value="likert">Likert Scale (1-5)</option>
                                                                    <option value="multiple_choice">Multiple Choice</option>
                                                                    <option value="yes_no">Yes / No</option>
                                                                    <option value="open_ended">Open-Ended (Text)</option>
                                                                </select>
                                                            </div>

                                                            <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                                                                <input type="checkbox" x-model="subQ.is_required" class="text-[#8b1818] rounded border-slate-300">
                                                                <span>Required</span>
                                                            </label>
                                                        </div>

                                                        <!-- Options for MCQ -->
                                                        <div x-show="subQ.type === 'multiple_choice'" class="space-y-1.5 pt-1">
                                                            <label class="text-[10px] font-bold text-slate-400 block">Multiple Choice Options</label>
                                                            <p class="text-[10px] font-semibold text-slate-500">Enter one option per value, separated by commas. Example: Option 1, Option 2, Option 3.</p>
                                                            <input type="text" :value="subQ.options ? subQ.options.join(', ') : ''" @input="subQ.options = $event.target.value.split(',').map(s=>s.trim())" placeholder="Option 1, Option 2, Option 3 (comma separated)" class="w-full px-3 py-1 text-xs border border-slate-200 rounded-lg outline-none bg-white">
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-1 shrink-0 pt-1">
                                                        <button type="button" @click="removeSubQuestion(secIdx, subIdx, sqIdx)" class="p-1 text-slate-400 hover:text-rose-600" title="Delete Question"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <div x-show="!sub.questions || sub.questions.length === 0" class="py-3 text-center text-xs font-bold text-slate-400 border border-dashed border-slate-300 rounded-xl">
                                            No questions under this subheading yet.
                                        </div>

                                        <!-- Bottom Button: Add Question under Subheading -->
                                        <div class="pt-2">
                                            <button type="button" @click="addSubQuestion(secIdx, subIdx)" class="w-full py-2.5 rounded-xl bg-white hover:bg-blue-100/60 text-blue-800 border-2 border-dashed border-blue-300 text-xs font-bold transition flex items-center justify-center gap-2">
                                                <i class="fa-solid fa-plus text-xs"></i>
                                                <span>Add Question under this Subheading</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Bottom Buttons inside Section Card Body -->
                        <div class="pt-4 border-t border-slate-200/80 flex flex-wrap items-center gap-3">
                            <button type="button" @click="addDirectQuestion(secIdx)" class="px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-bold transition flex items-center gap-2 shadow-2xs">
                                <i class="fa-solid fa-plus text-emerald-600 text-xs"></i>
                                <span>Add Question under Section</span>
                            </button>
                            <button type="button" @click="addSubheading(secIdx)" class="px-4 py-2.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-900 border border-blue-300 text-xs font-bold transition flex items-center gap-2 shadow-2xs">
                                <i class="fa-solid fa-plus text-blue-600 text-xs"></i>
                                <span>Add Subheading</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Bottom Button for Entire Questionnaire Builder: Add New Section (Visible when sections exist) -->
            <div x-show="sections.length > 0" class="pt-4 flex justify-center">
                <button type="button" @click="addSection()" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider transition shadow-lg shadow-red-950/20 flex items-center justify-center gap-2.5">
                    <i class="fa-solid fa-plus text-amber-300 text-sm"></i>
                    <span>Add New Section</span>
                </button>
            </div>
        </div>

    </main>

    <!-- Sticky Bottom Bar -->
    <div id="evaluation-editor-actions" class="fixed right-0 bottom-0 bg-white/95 backdrop-blur-md border-t border-slate-200 px-6 py-3.5 z-30 shadow-lg flex items-center justify-between transition-[left] duration-300">
        <a href="{{ route('admin.evaluations.periods') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition">Cancel</a>
        <div class="flex items-center gap-3">
            <button type="button" @click="previewModal = true" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition flex items-center gap-2">
                <i class="fa-solid fa-eye text-blue-600"></i>
                <span>Preview Form</span>
            </button>
            <button type="button" @click="submitForm()" class="px-6 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider transition shadow-md shadow-red-950/20 flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </div>

    <!-- Preview Form Modal -->
    <div x-show="previewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
        <div @click.away="previewModal = false" class="bg-white rounded-3xl border-2 border-slate-200 max-w-4xl w-full p-6 sm:p-10 space-y-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-4 border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-900 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Live Form Preview</h3>
                        <p class="text-xs text-slate-400 font-bold">Read-only preview of evaluator experience</p>
                    </div>
                </div>
                <button @click="previewModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <!-- Preview Content -->
            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200 space-y-6">
                <div>
                    <h2 class="text-xl font-black text-slate-900" x-text="formTitle"></h2>
                    <p class="text-xs font-semibold text-slate-600 mt-1 italic" x-text="instructions"></p>
                </div>

                <!-- Rating Scale Legend -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 space-y-2">
                    <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Rating Scale Legend</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-bold text-slate-700">
                        <template x-for="scale in ratingScales" :key="scale.value">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-900 font-black text-xs flex items-center justify-center" x-text="scale.value"></span>
                                <span x-text="scale.label"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Preview Sections -->
                <template x-for="(sec, secIdx) in sections" :key="secIdx">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 space-y-4">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-wide">
                            <span x-text="toRoman(secIdx + 1) + ' '"></span>
                            <span x-text="sec.title"></span>
                        </h4>
                        <p x-show="sec.description" class="text-xs text-slate-500 font-semibold" x-text="sec.description"></p>

                        <!-- Direct Questions -->
                        <div x-show="sec.direct_questions && sec.direct_questions.length > 0" class="space-y-4 pt-2">
                            <template x-for="(q, qIdx) in sec.direct_questions" :key="qIdx">
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                    <p class="text-xs font-bold text-slate-800"><span x-text="qIdx + 1 + '. '"></span><span x-text="q.question"></span></p>

                                    <!-- Render Rating Scale Radios -->
                                    <template x-if="q.type === 'likert'">
                                        <div class="flex items-center gap-3 pt-1">
                                            <template x-for="scale in ratingScales" :key="scale.value">
                                                <label class="flex items-center gap-1.5 text-xs font-bold text-slate-600 cursor-pointer">
                                                    <input type="radio" :name="'prev_dq_' + secIdx + '_' + qIdx" disabled class="text-[#8b1818]">
                                                    <span x-text="scale.value"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Render Yes / No Radios -->
                                    <template x-if="q.type === 'yes_no'">
                                        <div class="flex items-center gap-4 pt-1">
                                            <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                                                <input type="radio" disabled class="text-[#8b1818]">
                                                <span>Yes</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                                                <input type="radio" disabled class="text-[#8b1818]">
                                                <span>No</span>
                                            </label>
                                        </div>
                                    </template>

                                    <!-- Render MCQ -->
                                    <template x-if="q.type === 'multiple_choice'">
                                        <div class="space-y-1.5 pt-1">
                                            <template x-for="(opt, optIdx) in getOptions(q.options)" :key="optIdx">
                                                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                                                    <input type="radio" disabled class="text-[#8b1818]">
                                                    <span x-text="opt"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Render Open Ended -->
                                    <template x-if="q.type === 'open_ended'">
                                        <textarea rows="2" disabled placeholder="Evaluator text response..." class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-xl bg-white"></textarea>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Subheadings -->
                        <template x-for="(sub, subIdx) in sec.subheadings" :key="subIdx">
                            <div class="space-y-3 pt-3">
                                <h5 class="text-xs font-black text-blue-900 uppercase">
                                    <span x-text="toAlpha(subIdx) + ' '"></span>
                                    <span x-text="sub.title"></span>
                                </h5>
                                <template x-for="(subQ, sqIdx) in sub.questions" :key="sqIdx">
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                        <p class="text-xs font-bold text-slate-800"><span x-text="sqIdx + 1 + '. '"></span><span x-text="subQ.question"></span></p>
                                        
                                        <!-- Render Likert Scale -->
                                        <template x-if="subQ.type === 'likert'">
                                            <div class="flex items-center gap-3 pt-1">
                                                <template x-for="scale in ratingScales" :key="scale.value">
                                                    <label class="flex items-center gap-1.5 text-xs font-bold text-slate-600 cursor-pointer">
                                                        <input type="radio" disabled class="text-[#8b1818]">
                                                        <span x-text="scale.value"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Render Yes / No Radios -->
                                        <template x-if="subQ.type === 'yes_no'">
                                            <div class="flex items-center gap-4 pt-1">
                                                <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                                                    <input type="radio" disabled class="text-[#8b1818]">
                                                    <span>Yes</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                                                    <input type="radio" disabled class="text-[#8b1818]">
                                                    <span>No</span>
                                                </label>
                                            </div>
                                        </template>

                                        <!-- Render MCQ -->
                                        <template x-if="subQ.type === 'multiple_choice'">
                                            <div class="space-y-1.5 pt-1">
                                                <template x-for="(opt, optIdx) in getOptions(subQ.options)" :key="optIdx">
                                                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                                                        <input type="radio" disabled class="text-[#8b1818]">
                                                        <span x-text="opt"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Render Open Ended -->
                                        <template x-if="subQ.type === 'open_ended'">
                                            <textarea rows="2" disabled placeholder="Evaluator text response..." class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-xl bg-white"></textarea>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

<script>
function formBuilder() {
    return {
        formTitle: @json($form->title ?? ($formTypeName . ' of Teaching Performance')),
        instructions: @json($form->instructions ?? 'Please evaluate the faculty member based on your experience using the rating scale provided below.'),
        ratingScales: @json($ratingScales ?? []),
        sections: @json($sections ?? []),
        previewModal: false,

        getOptions(optVal) {
            if (Array.isArray(optVal)) {
                const filtered = optVal
                    .flatMap(s => {
                        if (typeof s !== 'string') return [s];
                        try {
                            const parsed = JSON.parse(s);
                            return Array.isArray(parsed) ? parsed : [s];
                        } catch (error) {
                            return [s];
                        }
                    })
                    .filter(s => s && String(s).trim().length > 0);
                return filtered.length > 0 ? filtered : ['Option 1', 'Option 2'];
            }
            if (typeof optVal === 'string') {
                let value = optVal.trim();
                try {
                    const decoded = JSON.parse(value);
                    if (Array.isArray(decoded)) return this.getOptions(decoded);
                    if (typeof decoded === 'string') value = decoded;
                } catch (error) {
                    // Treat legacy comma-separated values as-is.
                }
                const parsed = value.split(',').map(s => s.trim()).filter(s => s.length > 0);
                return parsed.length > 0 ? parsed : ['Option 1', 'Option 2'];
            }
            return ['Option 1', 'Option 2'];
        },

        toRoman(num) {
            if (!num || num <= 0) return 'I.';
            const lookup = {M:1000,CM:900,D:500,CD:400,C:100,XC:90,L:50,XL:40,X:10,IX:9,V:5,IV:4,I:1};
            let roman = '';
            let n = parseInt(num);
            for (let i in lookup) {
                while (n >= lookup[i]) {
                    roman += i;
                    n -= lookup[i];
                }
            }
            return roman + '.';
        },

        toAlpha(num) {
            return String.fromCharCode(65 + (num % 26)) + '.';
        },

        addScaleOption() {
            const nextVal = this.ratingScales.length + 1;
            this.ratingScales.push({ value: String(nextVal), label: 'New Scale Option', order_num: nextVal });
        },
        moveScaleUp(idx) {
            if (idx > 0) {
                const temp = this.ratingScales[idx];
                this.ratingScales[idx] = this.ratingScales[idx - 1];
                this.ratingScales[idx - 1] = temp;
            }
        },
        moveScaleDown(idx) {
            if (idx < this.ratingScales.length - 1) {
                const temp = this.ratingScales[idx];
                this.ratingScales[idx] = this.ratingScales[idx + 1];
                this.ratingScales[idx + 1] = temp;
            }
        },
        removeScaleOption(idx) {
            this.ratingScales.splice(idx, 1);
        },

        addSection() {
            this.sections.push({
                title: 'NEW SECTION TITLE',
                description: '',
                expanded: true,
                subheadings: [],
                direct_questions: []
            });
        },
        moveSectionUp(secIdx) {
            if (secIdx > 0) {
                const temp = this.sections[secIdx];
                this.sections[secIdx] = this.sections[secIdx - 1];
                this.sections[secIdx - 1] = temp;
            }
        },
        moveSectionDown(secIdx) {
            if (secIdx < this.sections.length - 1) {
                const temp = this.sections[secIdx];
                this.sections[secIdx] = this.sections[secIdx + 1];
                this.sections[secIdx + 1] = temp;
            }
        },
        removeSection(secIdx) {
            if (confirm('Delete this entire section and its questions?')) {
                this.sections.splice(secIdx, 1);
            }
        },

        addSubheading(secIdx) {
            if (!this.sections[secIdx].subheadings) {
                this.sections[secIdx].subheadings = [];
            }
            this.sections[secIdx].subheadings.push({
                title: 'Subheading Title',
                description: '',
                questions: []
            });
        },
        moveSubheadingUp(secIdx, subIdx) {
            if (subIdx > 0) {
                const temp = this.sections[secIdx].subheadings[subIdx];
                this.sections[secIdx].subheadings[subIdx] = this.sections[secIdx].subheadings[subIdx - 1];
                this.sections[secIdx].subheadings[subIdx - 1] = temp;
            }
        },
        moveSubheadingDown(secIdx, subIdx) {
            if (subIdx < this.sections[secIdx].subheadings.length - 1) {
                const temp = this.sections[secIdx].subheadings[subIdx];
                this.sections[secIdx].subheadings[subIdx] = this.sections[secIdx].subheadings[subIdx + 1];
                this.sections[secIdx].subheadings[subIdx + 1] = temp;
            }
        },
        removeSubheading(secIdx, subIdx) {
            if (confirm('Delete this subheading?')) {
                this.sections[secIdx].subheadings.splice(subIdx, 1);
            }
        },

        addDirectQuestion(secIdx) {
            if (!this.sections[secIdx].direct_questions) {
                this.sections[secIdx].direct_questions = [];
            }
            this.sections[secIdx].direct_questions.push({
                question: '',
                type: 'likert',
                is_required: true,
                options: ['Option 1', 'Option 2']
            });
        },
        moveDirectQuestionUp(secIdx, qIdx) {
            if (qIdx > 0) {
                const temp = this.sections[secIdx].direct_questions[qIdx];
                this.sections[secIdx].direct_questions[qIdx] = this.sections[secIdx].direct_questions[qIdx - 1];
                this.sections[secIdx].direct_questions[qIdx - 1] = temp;
            }
        },
        moveDirectQuestionDown(secIdx, qIdx) {
            if (qIdx < this.sections[secIdx].direct_questions.length - 1) {
                const temp = this.sections[secIdx].direct_questions[qIdx];
                this.sections[secIdx].direct_questions[qIdx] = this.sections[secIdx].direct_questions[qIdx + 1];
                this.sections[secIdx].direct_questions[qIdx + 1] = temp;
            }
        },
        removeDirectQuestion(secIdx, qIdx) {
            this.sections[secIdx].direct_questions.splice(qIdx, 1);
        },

        addSubQuestion(secIdx, subIdx) {
            if (!this.sections[secIdx].subheadings[subIdx].questions) {
                this.sections[secIdx].subheadings[subIdx].questions = [];
            }
            this.sections[secIdx].subheadings[subIdx].questions.push({
                question: '',
                type: 'likert',
                is_required: true,
                options: ['Option 1', 'Option 2']
            });
        },
        moveSubQuestionUp(secIdx, subIdx, sqIdx) {
            if (sqIdx > 0) {
                const temp = this.sections[secIdx].subheadings[subIdx].questions[sqIdx];
                this.sections[secIdx].subheadings[subIdx].questions[sqIdx] = this.sections[secIdx].subheadings[subIdx].questions[sqIdx - 1];
                this.sections[secIdx].subheadings[subIdx].questions[sqIdx - 1] = temp;
            }
        },
        moveSubQuestionDown(secIdx, subIdx, sqIdx) {
            if (sqIdx < this.sections[secIdx].subheadings[subIdx].questions.length - 1) {
                const temp = this.sections[secIdx].subheadings[subIdx].questions[sqIdx];
                this.sections[secIdx].subheadings[subIdx].questions[sqIdx] = this.sections[secIdx].subheadings[subIdx].questions[sqIdx + 1];
                this.sections[secIdx].subheadings[subIdx].questions[sqIdx + 1] = temp;
            }
        },
        removeSubQuestion(secIdx, subIdx, sqIdx) {
            this.sections[secIdx].subheadings[subIdx].questions.splice(sqIdx, 1);
        },

        submitForm() {
            document.getElementById('save-form-element').submit();
        }
    };
}
</script>
@endsection
