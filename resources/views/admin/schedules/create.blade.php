<div id="scheduleModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 sm:p-6">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col max-h-[92vh]">
        
        <!-- Modal Header -->
        <div class="px-8 py-5 border-b-2 border-slate-100 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#8b1818] text-amber-300 flex items-center justify-center shadow-xs">
                    <i class="fa-solid fa-calendar-plus text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900">Create Class Schedule</h3>
                    <p class="text-[11px] font-bold text-slate-500">Bind teacher, section & time slots for active school year</p>
                </div>
            </div>
            <button type="button" onclick="closeScheduleModal()" class="w-9 h-9 rounded-xl hover:bg-slate-200 text-slate-500 font-bold cursor-pointer transition">✕</button>
        </div>

        <!-- Real-time Conflict Alert Box -->
        <div id="createConflictAlert" class="hidden mx-8 mt-6 p-4 rounded-2xl bg-red-50 border-2 border-red-200 text-red-900 text-xs font-bold space-y-1">
            <div class="flex items-center gap-2 text-[#8b1818] font-black text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span id="createConflictTitle">Schedule Conflict Detected</span>
            </div>
            <p id="createConflictText" class="text-slate-700 leading-relaxed font-semibold mt-1"></p>
        </div>

        <form id="createScheduleForm" action="{{ route('admin.schedules.store') }}" method="POST" class="p-8 overflow-y-auto space-y-6">
            @csrf

            <!-- Subject Name -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Subject Name *</label>
                <input type="text" name="subject_name" required placeholder="e.g., General Mathematics"
                       class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
            </div>

            <!-- Assigned Faculty -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Assigned Faculty *</label>
                <select id="create_teacher_id" name="teacher_id" required onchange="triggerCreateConflictCheck()"
                        class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                    <option value="">-- Select Faculty Member --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }} ({{ $t->email }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Grade, Section, Strand -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- GRADE -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Grade *</label>
                    <select id="create_grade_level" name="grade_level" required onchange="handleCreateGradeChange(); triggerCreateConflictCheck();"
                            class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Grade</option>
                        @foreach($dbGrades as $g) 
                            <option value="{{ $g }}">{{ $g }}</option> 
                        @endforeach
                    </select>
                </div>

                <!-- SECTION (Filtered by selected Grade level) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Section *</label>
                    <select id="create_section" name="section" required onchange="triggerCreateConflictCheck()"
                            class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Section</option>
                        @foreach($dbSections as $sec) 
                            <option value="{{ $sec->section_name }}" data-grade="{{ $sec->grade_level ?? '' }}">{{ $sec->section_name }}</option> 
                        @endforeach
                    </select>
                </div>

                <!-- STRAND (Hidden when Grade 7-10 is selected) -->
                <div id="create_strand_container">
                    <label id="create_strand_label" class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Strand *</label>
                    <select id="create_strand_select" name="strand" required
                            class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white cursor-pointer transition shadow-2xs">
                        <option value="">Select Strand</option>
                        @foreach($dbStrands as $s) 
                            <option value="{{ $s }}">{{ $s }}</option> 
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- DAY SELECTION CHECKLIST (FULLY HORIZONTAL SINGLE ROW) -->
            <div class="space-y-3 bg-slate-50/80 p-4 rounded-2xl border-2 border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wide">
                            Select Days *
                        </label>
                        <p class="text-[10px] text-slate-500 font-bold">Check all days for this class schedule</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="selectPresetDays(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])"
                                class="px-2.5 py-1 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 text-[11px] font-black uppercase transition border border-amber-300 cursor-pointer">
                            Mon - Fri
                        </button>
                        <button type="button" onclick="selectPresetDays(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])"
                                class="px-2.5 py-1 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-bold uppercase transition border border-slate-300 cursor-pointer">
                            Mon - Sat
                        </button>
                        <button type="button" onclick="clearAllDays()"
                                class="px-2.5 py-1 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 text-[11px] font-bold uppercase transition border border-red-200 cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Checkbox Checklist Grid - Fully Horizontal 7-column layout -->
                <div class="grid grid-cols-7 gap-1.5 pt-1 w-full overflow-x-auto">
                    @php
                        $weekDaysList = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    @endphp
                    @foreach($weekDaysList as $dayName)
                        <label class="flex items-center justify-center gap-1 sm:gap-1.5 px-1.5 sm:px-2.5 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-400 cursor-pointer transition select-none shadow-2xs group text-center">
                            <input type="checkbox" name="days[]" value="{{ $dayName }}" 
                                   class="create-day-checkbox w-3.5 h-3.5 text-[#8b1818] rounded border-slate-300 focus:ring-[#8b1818] cursor-pointer shrink-0"
                                   onchange="updateCreateDayBadges(); triggerCreateConflictCheck();">
                            <span class="text-[11px] sm:text-xs font-bold text-slate-700 group-hover:text-slate-900 whitespace-nowrap">
                                {{ substr($dayName, 0, 3) }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <!-- Selected Days Badges Container -->
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Selected Days:</span>
                    <div id="createSelectedDaysBadges" class="flex flex-wrap gap-2 min-h-[30px] items-center">
                        <span class="text-xs text-slate-400 italic">No days selected yet. Please check boxes above.</span>
                    </div>
                </div>
            </div>

            <!-- START TIME & END TIME -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Start Time *</label>
                    <input type="time" id="create_start_time" name="start_time" required onchange="triggerCreateConflictCheck()"
                           class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">End Time *</label>
                    <input type="time" id="create_end_time" name="end_time" required onchange="triggerCreateConflictCheck()"
                           class="w-full py-3 px-4 text-sm font-semibold rounded-2xl border-2 border-slate-200 focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
                </div>
            </div>

            <!-- Submit Action Bar -->
            <div class="pt-6 border-t-2 border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400">All fields marked with * are required</span>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeScheduleModal()"
                            class="px-5 py-3 rounded-2xl border-2 border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="createSubmitBtn"
                            class="px-6 py-3 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black uppercase tracking-wider transition shadow-md shadow-red-950/20 cursor-pointer">
                        Save Schedule
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>