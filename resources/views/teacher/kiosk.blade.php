<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIATRACK - Live NFC Kiosk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 30px rgba(255, 255, 255, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-[#8b1818] via-[#5c0f0f] to-[#1a0404] text-white min-h-screen flex flex-col justify-between p-6 select-none overflow-hidden">

    <!-- Top Bar with Professional Action Controls & Audio Toggle -->
    <header class="flex items-center justify-between border-b border-white/10 pb-4 px-2 w-full">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 bg-white/10 border border-white/20 rounded-2xl flex items-center justify-center text-white shadow-lg">
                <span class="font-black text-sm tracking-wider">SIA</span>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-wider text-white">SIATRACK NFC KIOSK</h1>
                <p class="text-[11px] text-red-200/75 font-semibold">Southern Isabela Academy &bull; Attendance Terminal</p>
            </div>
        </div>

        <div class="flex items-center gap-5">
            <!-- Clock & Date -->
            <div class="text-right">
                <p id="live_clock" class="text-xl font-black font-mono text-white tracking-widest">--:--:-- --</p>
                <p id="live_date" class="text-xs text-red-200/75 font-semibold">------ --, ----</p>
            </div>

            <!-- Header Controls (Audio Mute, Fullscreen & Exit) -->
            <div class="flex items-center gap-2 pl-4 border-l border-white/15">
                <button type="button" onclick="toggleAttendanceConfig()" title="Configure attendance" class="w-10 h-10 rounded-xl bg-amber-400/15 hover:bg-amber-400 border border-amber-300/60 text-amber-200 hover:text-red-950 flex items-center justify-center transition cursor-pointer shadow-md">
                    <i class="fa-solid fa-sliders text-sm"></i>
                </button>
                <button type="button" onclick="toggleAudio()" id="audio_toggle_btn" title="Toggle Sound FX" class="w-10 h-10 rounded-xl bg-black/30 hover:bg-black/50 border border-white/20 text-white flex items-center justify-center transition cursor-pointer shadow-md backdrop-blur-md">
                    <i class="fa-solid fa-volume-high text-sm text-red-300" id="audio_icon"></i>
                </button>
                <button type="button" onclick="toggleFullscreen()" title="Toggle Fullscreen" class="w-10 h-10 rounded-xl bg-black/30 hover:bg-black/50 border border-white/20 text-white flex items-center justify-center transition cursor-pointer shadow-md backdrop-blur-md">
                    <i class="fa-solid fa-expand text-sm text-red-300"></i>
                </button>
                <form method="POST" action="{{ route('attendance.end') }}">
                    @csrf
                    <button title="End Attendance Session" class="w-10 h-10 rounded-xl bg-black/30 hover:bg-black/50 border border-white/20 text-white flex items-center justify-center transition cursor-pointer shadow-md backdrop-blur-md">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm text-red-300"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div id="attendance_config_modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-3xl border border-amber-300/40 bg-[#3b0808] p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-300">Attendance setup</p>
                    <h2 class="mt-1 text-xl font-black text-white">Configure attendance</h2>
                </div>
                <button type="button" onclick="toggleAttendanceConfig()" class="h-9 w-9 rounded-xl border border-white/20 text-white/70 hover:bg-white/10 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="mt-5 space-y-3">
                <select id="attendance_type" onchange="updateAttendanceConfig()" class="w-full rounded-xl border border-white/25 bg-black/40 px-4 py-3 text-sm text-white focus:border-amber-300 focus:outline-none">
                    @if($isAdmin || $advisorySections->isNotEmpty())
                        <option value="advisory" @selected($defaultAttendanceType === 'advisory')>Advisory / Daily Attendance</option>
                    @endif
                    <option value="subject" @selected($defaultAttendanceType === 'subject')>Subject Class / Time In Only</option>
                </select>
                <select id="advisory_section_id" class="w-full rounded-xl border border-white/25 bg-black/40 px-4 py-3 text-sm text-white focus:border-amber-300 focus:outline-none">
                    @foreach($advisorySections as $section)
                        <option value="{{ $section->id }}" @selected($defaultSection && $defaultSection->id === $section->id)>{{ $section->section_name }} · Advisory</option>
                    @endforeach
                </select>
                <select id="schedule_id" class="w-full rounded-xl border border-white/25 bg-black/40 px-4 py-3 text-sm text-white focus:border-amber-300 focus:outline-none">
                    <option value="">Select today's class schedule</option>
                    @foreach($schedules as $schedule)
                        <option value="{{ $schedule->id }}" @selected($currentSchedule && $currentSchedule->id === $schedule->id)">
                            {{ $schedule->day ?: 'Any day' }} · {{ $schedule->subjectRecord->name ?? $schedule->subject_name ?? 'Subject' }} · {{ $schedule->academicSection->section_name ?? $schedule->section ?? 'Section' }} · {{ $schedule->time_slot }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" onclick="toggleAttendanceConfig()" class="rounded-xl border border-white/20 px-5 py-2.5 text-xs font-black text-white/70 hover:bg-white/10 hover:text-white">Cancel</button>
                <button type="button" onclick="toggleAttendanceConfig()" class="rounded-xl bg-amber-400 px-5 py-2.5 text-xs font-black text-red-950 hover:bg-amber-300">Use Configuration</button>
            </div>
        </div>
    </div>

    <!-- Center Scanner / Student Display Box -->
    <main class="w-full px-4 my-auto text-center py-4 space-y-4">
        
        <!-- Default Standby State with Recent Scans Ticker -->
        <div id="standby_box" class="space-y-5">
            <div class="relative w-40 h-40 mx-auto flex items-center justify-center">
                <div id="scanner_status_ring" class="w-36 h-36 rounded-full bg-white/10 border-2 border-white/20 flex items-center justify-center backdrop-blur-sm">
                    <i id="scanner_status_icon" class="fa-solid fa-wifi rotate-45 text-4xl text-white"></i>
                </div>
            </div>

            <div class="bg-black/30 border border-white/15 backdrop-blur-xl rounded-3xl p-8 max-w-2xl mx-auto shadow-2xl space-y-3">
                <div class="flex items-center justify-center gap-3">
                    <h2 id="scanner_status_title" class="text-2xl font-black tracking-tight text-white">WAITING FOR NFC SCANNER</h2>
                </div>
                <p id="scanner_status_message" class="text-xs text-red-100/80 font-medium">Connect the ACR122U reader and start the NFC bridge.</p>
                <div class="flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider">
                    <span id="scanner_status_dot" class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span id="scanner_status_label" class="text-amber-200">Waiting for NFC scanner</span>
                </div>

                <!-- Hidden/Testing input to capture physical/manual reader input -->
                <div class="pt-1 max-w-xs mx-auto">
                    <div id="current_attendance_context" class="mb-3 rounded-xl border border-emerald-300/40 bg-emerald-500/15 px-5 py-3 text-sm text-left text-emerald-100 min-h-[58px]"></div>
                    <p class="mb-2 text-xs text-white/60">Advisory scans record time in and time out. Subject scans record time in only.</p>
                    <input type="text" id="sim_uid_input" placeholder="Awaiting card tap..." autofocus
                           class="w-full bg-black/50 border border-white/25 rounded-xl px-4 py-2 text-xs text-center font-mono text-white placeholder-white/30 focus:border-white outline-none shadow-inner">
                </div>

                <!-- Recent Scan Ticker Feed -->
                <div class="mt-4 pt-3 border-t border-white/10 text-left">
                    <p class="text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-1.5 flex items-center justify-between">
                        <span>Recent Scan Feed</span>
                        <span class="text-[9px] text-emerald-400 font-mono">Live Ticker</span>
                    </p>
                    <div id="recent_scans_list" class="space-y-1 text-xs font-medium text-red-100/90 max-h-20 overflow-y-auto">
                        <div class="flex items-center justify-between text-white/40 italic text-[11px]">
                            <span>Waiting for initial student tap...</span>
                            <span class="font-mono text-[10px]">--:--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Result Card (Real Database Data) -->
        <div id="result_box" class="hidden bg-black/70 border-2 border-red-500/60 rounded-3xl p-12 shadow-2xl backdrop-blur-2xl max-w-5xl mx-auto text-left transition-all duration-300 transform scale-100">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-2 text-emerald-400 font-black text-base">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                    <span id="res_action_label">SUCCESSFUL ATTENDANCE LOG</span>
                </div>
                <span id="res_status_badge" class="bg-emerald-500 text-slate-950 px-5 py-2 rounded-full text-sm font-black uppercase tracking-wider shadow-md">
                    ON-TIME
                </span>
            </div>
            <div class="mt-4 rounded-2xl border border-amber-300/30 bg-amber-400/10 px-5 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-300">Attendance configured</p>
                <p id="res_attendance_context" class="mt-1 text-sm font-bold text-amber-100">--</p>
            </div>

            <!-- Student Name & Info Display Area -->
            <div class="flex items-center gap-8 mt-8">
                <div id="res_avatar_container" class="w-40 h-40 rounded-3xl overflow-hidden bg-black/40 border-2 border-red-500/40 flex items-center justify-center shrink-0 shadow-xl">
                    <i class="fa-regular fa-user text-6xl text-white/50"></i>
                </div>
                <div class="space-y-2.5 min-w-0">
                    <span class="inline-block text-[10px] font-extrabold uppercase tracking-widest text-red-400 bg-red-950/70 border border-red-500/30 px-3 py-0.5 rounded-md">Verified Student</span>
                    <h3 id="res_student_name" class="text-5xl sm:text-6xl font-black text-white tracking-tight leading-none break-words">Student Name</h3>
                    <p id="res_id_number" class="text-base font-mono text-red-200/90 font-bold">ID: 00000</p>
                    <p id="res_strand" class="text-base text-red-300/80 font-semibold">Grade 11 · STEM A</p>
                    <p id="res_scan_message" class="max-w-2xl text-sm font-semibold text-red-100/90">--</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mt-6 pt-4 border-t border-white/10 bg-black/40 p-4 rounded-2xl">
                <div>
                    <p class="text-[10px] text-red-200/60 font-bold uppercase tracking-wider">Time In Recorded</p>
                    <p id="res_time_in" class="text-lg font-mono font-black text-white mt-0.5">--:-- --</p>
                </div>
                <div>
                    <p class="text-[10px] text-red-200/60 font-bold uppercase tracking-wider">Time Out Recorded</p>
                    <p id="res_time_out" class="text-lg font-mono font-black text-white mt-0.5">--</p>
                </div>
                <div>
                    <p class="text-[10px] text-red-200/60 font-bold uppercase tracking-wider">Academic Section</p>
                    <p id="res_section" class="text-xs font-semibold text-red-100 mt-1">--</p>
                </div>
            </div>

            <!-- Confirm & Next Button -->
            <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-end">
                <button type="button" onclick="saveAttendanceRecord()" class="w-full py-3 rounded-xl bg-gradient-to-r from-red-600 to-[#8b1818] hover:from-red-500 hover:to-red-700 text-white font-black text-xs uppercase tracking-wider transition shadow-lg cursor-pointer flex items-center justify-center gap-2 border border-red-400/30">
                    <i class="fa-solid fa-floppy-disk text-sm"></i> Confirm & Next Student
                </button>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="text-center text-xs text-red-200/60 border-t border-white/10 pt-3 flex flex-col items-center justify-center gap-0.5 font-semibold px-2 w-full">
        <span>Southern Isabela Academy &bull; SIATRACK Attendance Management System</span>
        <span class="text-[10px] text-red-300/40">Powered by Web & Mobile Application Development &bull; All Rights Reserved &copy; 2026</span>
    </footer>

    <!-- Audio Beep FX -->
    <audio id="beep_success" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <script>
        // Real-Time Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('live_clock').innerText = now.toLocaleTimeString();
            document.getElementById('live_date').innerText = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        let lastProcessedTag = '';
        let lastProcessedTapToken = '';
        let resetTimer = null;
        let soundEnabled = true;
        let recentScans = [];
        let scannerStatus = 'waiting';
        let tapInFlight = false;
        let tapAbortController = null;
        let pollInFlight = false;
        let attendanceConfigVersion = 0;

        // Toggle Audio Chime
        function toggleAudio() {
            soundEnabled = !soundEnabled;
            const icon = document.getElementById('audio_icon');
            icon.className = soundEnabled ? "fa-solid fa-volume-high text-sm text-red-300" : "fa-solid fa-volume-xmark text-sm text-slate-400";
        }

        // Toggle Fullscreen Function
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        function toggleAttendanceConfig() {
            document.getElementById('attendance_config_modal').classList.toggle('hidden');
        }

        function updateAttendanceConfig() {
            attendanceConfigVersion++;
            if (tapAbortController) {
                tapAbortController.abort();
                tapAbortController = null;
            }
            tapInFlight = false;
            const type = document.getElementById('attendance_type').value;
            const advisorySection = document.getElementById('advisory_section_id');
            const schedule = document.getElementById('schedule_id');
            const context = document.getElementById('current_attendance_context');

            advisorySection.classList.toggle('hidden', type !== 'advisory');
            schedule.classList.toggle('hidden', type !== 'subject');

            if (type === 'advisory') {
                const section = advisorySection.options[advisorySection.selectedIndex];
                context.innerHTML = section && section.value
                    ? '<span class="block text-[10px] font-black uppercase tracking-wider text-emerald-300">Attendance today</span>' + section.text
                    : 'No advisory section configured.';
            } else {
                const selected = schedule.options[schedule.selectedIndex];
                context.innerHTML = selected && selected.value
                    ? '<span class="block text-[10px] font-black uppercase tracking-wider text-emerald-300">Current subject class</span>' + selected.text
                    : 'No active subject schedule.';
            }
            console.info('[NFC CONFIG]', {
                attendanceType: type,
                advisorySectionId: advisorySection.value || null,
                scheduleId: schedule.value || null,
                context: context.innerText
            });
        }

        document.getElementById('advisory_section_id').addEventListener('change', updateAttendanceConfig);
        document.getElementById('schedule_id').addEventListener('change', updateAttendanceConfig);
        updateAttendanceConfig();

        // Trigger Tap Function (Communicates with NfcAttendanceController)
        async function triggerTap(uid) {
            if (!uid || tapInFlight) return;
            const attendanceType = document.getElementById('attendance_type').value;
            const scheduleId = @json($currentSchedule?->id);
            const scheduleInput = document.getElementById('schedule_id');
            const selectedScheduleId = scheduleInput ? scheduleInput.value : scheduleId;
            const sectionInput = document.getElementById('advisory_section_id');
            const selectedSectionId = sectionInput ? sectionInput.value : null;
            if (attendanceType === 'subject' && !selectedScheduleId) {
                showScanNotice('Select the class schedule before scanning a card.', uid, 'CONFIGURATION REQUIRED');
                return;
            }
            if (attendanceType === 'advisory' && !selectedSectionId) {
                showScanNotice('Select the advisory section before scanning a card.', uid, 'CONFIGURATION REQUIRED');
                return;
            }

            tapInFlight = true;
            const requestConfigVersion = attendanceConfigVersion;
            tapAbortController = new AbortController();
            try {
                const response = await fetch('{{ route('attendance.tap') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ tag_id: uid, attendance_type: attendanceType, schedule_id: selectedScheduleId, advisory_section_id: selectedSectionId }),
                    signal: tapAbortController.signal
                });

                const data = await response.json();
                if (requestConfigVersion !== attendanceConfigVersion) {
                    console.info('[NFC SCAN] Ignoring response from an outdated attendance configuration.', {
                        uid,
                        requestConfigVersion,
                        attendanceConfigVersion
                    });
                    return;
                }

                console.info('[NFC SCAN]', {
                    uid,
                    success: data.success,
                    errorCode: data.error_code || null,
                    registered: data.is_registered ?? null,
                    student: data.student?.name || null,
                    studentSection: data.student?.section || null,
                    selectedSection: data.selected_section || document.getElementById('current_attendance_context').innerText,
                    message: data.message || null
                });

                if (data.success) {
                    // Play Sound if enabled
                    if (soundEnabled) {
                        document.getElementById('beep_success').play().catch(() => {});
                    }

                    // Push to Recent Scans Ticker Feed
                    recentScans.unshift({
                        name: data.student.name,
                        time: data.time,
                        status: data.status
                    });
                    if (recentScans.length > 3) recentScans.pop();
                    updateRecentScansTicker();

                    // Populate Real Student Details from Database
                    document.getElementById('res_student_name').innerText = data.student.name;
                    document.getElementById('res_id_number').innerText = 'ID: ' + data.student.id_number;
                    document.getElementById('res_strand').innerText = formatStudentPlacement(data.student);
                    document.getElementById('res_time_in').innerText = data.time_in || data.time || '--';
                    document.getElementById('res_time_out').innerText = data.time_out || '--';
                    document.getElementById('res_section').innerText = data.student.section;
                    document.getElementById('res_attendance_context').innerText = document.getElementById('current_attendance_context').innerText;
                    document.getElementById('res_action_label').innerText = data.action === 'TIME OUT'
                        ? 'TIME OUT RECORDED'
                        : (data.action === 'ALREADY_COMPLETED'
                            ? 'ATTENDANCE ALREADY COMPLETED'
                            : (data.action === 'ALREADY_LOGGED' ? 'ATTENDANCE ALREADY RECORDED' : 'SUCCESSFUL ATTENDANCE LOG'));
                    document.getElementById('res_scan_message').innerText = data.message || '--';
                    
                    // Status Badge Styling
                    const badge = document.getElementById('res_status_badge');
                    badge.innerText = data.status;
                    badge.className = data.status === 'ON-TIME' 
                        ? 'bg-emerald-500 text-slate-950 px-5 py-2 rounded-full text-sm font-black uppercase tracking-wider shadow-md'
                        : 'bg-amber-400 text-slate-950 px-5 py-2 rounded-full text-sm font-black uppercase tracking-wider shadow-md';

                    // Student Avatar / Photo
                    const avatarBox = document.getElementById('res_avatar_container');
                    if (data.student.avatar) {
                        avatarBox.innerHTML = `<img src="${data.student.avatar}" class="w-full h-full object-cover">`;
                    } else {
                        avatarBox.innerHTML = `<span class="text-4xl font-black text-red-400">${data.student.name.charAt(0)}</span>`;
                    }

                    // Show Success Result Card & Hide Standby View
                    document.getElementById('standby_box').classList.add('hidden');
                    document.getElementById('result_box').classList.remove('hidden');

                    if (resetTimer) clearTimeout(resetTimer);

                    // Auto-reset back to standby after 5 seconds
                    resetTimer = setTimeout(() => {
                        resetToStandby();
                    }, 5000);

                } else if (data.error_code === 'STUDENT_NOT_IN_CLASS' && data.student) {
                    showStudentNotice(data.student, data.message, data.selected_section, 'NOT RECORDED · NOT IN CLASS', 'NOT IN CLASS');
                } else if (data.error_code === 'NOT_IN_SCHEDULE' && data.student) {
                    showStudentNotice(data.student, data.message, data.selected_section, 'NOT RECORDED · NOT IN SCHEDULE', 'NOT IN SCHEDULE');
                } else {
                    showScanNotice(data.message || 'Unrecognized card.', uid, 'CARD NOT REGISTERED');
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Tap request failed:', err);
                    console.error('[NFC SCAN ERROR]', { uid, error: err });
                    showScanNotice('Unable to contact the attendance server. Please try again.', uid, 'SCAN ERROR');
                }
            } finally {
                tapInFlight = false;
                tapAbortController = null;
            }
        }

        function showStudentNotice(student, message, selectedSection, title, badgeText) {
            document.getElementById('res_action_label').innerText = title;
            document.getElementById('res_status_badge').innerText = badgeText;
            document.getElementById('res_status_badge').className = 'bg-amber-400 text-slate-950 px-5 py-2 rounded-full text-sm font-black uppercase tracking-wider shadow-md';
            document.getElementById('res_student_name').innerText = student.name;
            document.getElementById('res_id_number').innerText = 'ID: ' + student.id_number;
            document.getElementById('res_strand').innerText = formatStudentPlacement(student);
            document.getElementById('res_scan_message').innerText = message;
            document.getElementById('res_time_in').innerText = new Date().toLocaleTimeString();
            document.getElementById('res_time_out').innerText = '--';
            document.getElementById('res_section').innerText = `Student section: ${student.section} · Selected: ${selectedSection}`;
            document.getElementById('res_attendance_context').innerText = document.getElementById('current_attendance_context').innerText;
            document.getElementById('res_avatar_container').innerHTML = student.avatar
                ? `<img src="${student.avatar}" class="w-full h-full object-cover">`
                : `<span class="text-6xl font-black text-amber-300">${student.name.charAt(0)}</span>`;
            document.getElementById('standby_box').classList.add('hidden');
            document.getElementById('result_box').classList.remove('hidden');
            if (resetTimer) clearTimeout(resetTimer);
            resetTimer = setTimeout(resetToStandby, 5000);
        }

        function formatStudentPlacement(student) {
            const grade = student.grade_level || 'Grade level unavailable';
            const section = student.section && student.section !== 'Unassigned Section'
                ? student.section
                : '';

            return section ? `${grade} · ${section}` : grade;
        }

        function showScanNotice(message, uid, title) {
            document.getElementById('res_action_label').innerText = title;
            document.getElementById('res_status_badge').innerText = 'NOT RECORDED';
            document.getElementById('res_status_badge').className = 'bg-red-500 text-white px-5 py-2 rounded-full text-sm font-black uppercase tracking-wider shadow-md';
            document.getElementById('res_student_name').innerText = 'UNKNOWN NFC CARD';
            document.getElementById('res_id_number').innerText = 'UID: ' + (uid || 'Not detected');
            document.getElementById('res_strand').innerText = 'No student is linked to this card';
            document.getElementById('res_scan_message').innerText = message;
            document.getElementById('res_time_in').innerText = new Date().toLocaleTimeString();
            document.getElementById('res_time_out').innerText = '--';
            document.getElementById('res_section').innerText = 'Please assign this card to a student in NFC Management.';
            document.getElementById('res_attendance_context').innerText = document.getElementById('current_attendance_context').innerText;
            document.getElementById('res_avatar_container').innerHTML = '<i class="fa-solid fa-id-card text-6xl text-red-300"></i>';
            document.getElementById('standby_box').classList.add('hidden');
            document.getElementById('result_box').classList.remove('hidden');
            if (resetTimer) clearTimeout(resetTimer);
            resetTimer = setTimeout(resetToStandby, 5000);
        }

        // Update Recent Scans Ticker UI
        function updateRecentScansTicker() {
            const listContainer = document.getElementById('recent_scans_list');
            if (!listContainer) return;

            let html = '';
            recentScans.forEach(scan => {
                html += `
                    <div class="flex items-center justify-between bg-black/30 px-2.5 py-1 rounded-lg border border-white/5">
                        <span class="font-bold text-white truncate max-w-[150px]">${scan.name}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] px-1.5 py-0.5 rounded font-black ${scan.status === 'ON-TIME' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'}">${scan.status}</span>
                            <span class="font-mono text-[10px] text-red-200/60">${scan.time}</span>
                        </div>
                    </div>
                `;
            });
            listContainer.innerHTML = html;
        }

        // Manual Confirm Button Trigger
        function saveAttendanceRecord() {
            if (resetTimer) clearTimeout(resetTimer);
            resetToStandby();
        }

        function resetToStandby() {
            if (resetTimer) clearTimeout(resetTimer);
            document.getElementById('result_box').classList.add('hidden');
            document.getElementById('standby_box').classList.remove('hidden');
            document.getElementById('sim_uid_input').value = '';
            document.getElementById('res_scan_message').innerText = '--';
            document.getElementById('sim_uid_input').focus();
        }

        function updateScannerStatus(status) {
            scannerStatus = status || 'waiting';
            const ring = document.getElementById('scanner_status_ring');
            const icon = document.getElementById('scanner_status_icon');
            const title = document.getElementById('scanner_status_title');
            const message = document.getElementById('scanner_status_message');
            const dot = document.getElementById('scanner_status_dot');
            const label = document.getElementById('scanner_status_label');
            const states = {
                online: {
                    title: 'READY TO SCAN',
                    message: 'Please hold your SIA NFC Student ID near the ACR122U Reader.',
                    label: 'NFC scanner online',
                    color: 'bg-emerald-400',
                    text: 'text-emerald-200',
                    ring: 'pulse-ring border-emerald-300/70',
                    icon: 'text-emerald-100'
                },
                disconnected: {
                    title: 'NFC SCANNER OFFLINE',
                    message: 'The NFC bridge stopped responding. Check the reader and bridge connection.',
                    label: 'NFC scanner disconnected',
                    color: 'bg-red-400',
                    text: 'text-red-200',
                    ring: 'border-red-300/70',
                    icon: 'text-red-200'
                },
                waiting: {
                    title: 'WAITING FOR NFC SCANNER',
                    message: 'Connect the ACR122U reader and start the NFC bridge.',
                    label: 'Waiting for NFC scanner',
                    color: 'bg-amber-400',
                    text: 'text-amber-200',
                    ring: 'border-amber-300/70',
                    icon: 'text-amber-100'
                }
            };
            const state = states[scannerStatus] || states.waiting;
            title.innerText = state.title;
            message.innerText = state.message;
            label.innerText = state.label;
            dot.className = `w-2.5 h-2.5 rounded-full ${state.color}`;
            label.className = `text-${state.text.replace('text-', '')}`;
            ring.className = `w-36 h-36 rounded-full bg-white/10 border-2 flex items-center justify-center backdrop-blur-sm ${state.ring}`;
            icon.className = `fa-solid fa-wifi rotate-45 text-4xl ${state.icon}`;
        }

        // Real-Time Hardware Polling Loop (Checks for physical NFC taps every 1 second)
        async function pollLatestTap() {
            if (pollInFlight) return;
            pollInFlight = true;
            try {
                const res = await fetch('/api/nfc/latest', { signal: AbortSignal.timeout(1500) });
                const data = await res.json();
                updateScannerStatus(data.bridge_status || (data.bridge_online ? 'online' : 'waiting'));
                
                const tapToken = data.tap_token || data.card_uid;
                if (data.bridge_online && data.card_uid && tapToken !== lastProcessedTapToken) {
                    lastProcessedTag = data.card_uid;
                    lastProcessedTapToken = tapToken;
                    triggerTap(data.card_uid);
                }
            } catch (e) {
                updateScannerStatus('disconnected');
            } finally {
                pollInFlight = false;
            }
        }

        updateScannerStatus('waiting');
        pollLatestTap();
        setInterval(pollLatestTap, 750);

        // Manual Keyboard / Reader Input Support
        document.getElementById('sim_uid_input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const uid = this.value.trim();
                if (uid) {
                    lastProcessedTag = uid;
                    triggerTap(uid);
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>