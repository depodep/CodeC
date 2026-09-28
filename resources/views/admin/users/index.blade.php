@extends('layouts.app')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50" x-data="{
    activeModal: null, // 'viewUser', 'viewSchedule', 'resetPassword', 'createUser', 'editUser'
    selectedUser: null,
    menuUser: null,
    menuPos: { top: 0, right: 0 },
    activeDayTab: 'ALL',
    sections: @js($sections ?? []),

    // ADD USER FORM STATE
    createForm: {
        role_id: '3',
        last_name: '',
        first_name: '',
        middle_name: '',
        student_id: '',
        id_number: '',
        username: '',
        usernameAuto: true,
        gender: '',
        email: '',
        phone_number: '',
        parent_name: '',
        parent_relationship: '',
        parent_phone_number: '',
        teacher_type: 'Subject Teacher',
        grade_level: '',
        strand: '',
        section: '',
        department: 'Executive Management',
        password: 'onesia@123',
        password_confirmation: 'onesia@123',
        showPassword: false
    },

    // EDIT USER FORM STATE
    editForm: {
        id: null,
        role_id: '3',
        last_name: '',
        first_name: '',
        middle_name: '',
        student_id: '',
        id_number: '',
        username: '',
        usernameAuto: true,
        gender: '',
        email: '',
        phone_number: '',
        parent_name: '',
        parent_relationship: '',
        parent_phone_number: '',
        teacher_type: 'Subject Teacher',
        grade_level: '',
        strand: '',
        section: '',
        department: 'Executive Management',
        reset_to_default_password: false
    },

    toggleMenu(event, user) {
        if (this.menuUser && this.menuUser.id === user.id) {
            this.menuUser = null;
            return;
        }
        const rect = event.currentTarget.getBoundingClientRect();
        this.menuPos = {
            top: rect.bottom + 6,
            right: window.innerWidth - rect.right
        };
        this.menuUser = user;
    },
    closeMenu() {
        this.menuUser = null;
    },
    viewUser(user) {
        this.closeMenu();
        this.selectedUser = user;
        this.activeModal = 'viewUser';
    },
    viewSchedule(user) {
        this.closeMenu();
        this.selectedUser = user;
        this.activeDayTab = 'ALL';
        this.activeModal = 'viewSchedule';
    },
    resetPasswordUser(user) {
        this.closeMenu();
        this.selectedUser = user;
        this.activeModal = 'resetPassword';
    },
    openCreateModal() {
        this.closeMenu();
        this.syncCreateDefaultPassword();
        this.activeModal = 'createUser';
    },
    openEditModal(user) {
        this.closeMenu();
        this.selectedUser = user;
        const roleId = String(user.role_id || (user.role === 'admin' ? 1 : (user.role === 'teacher' ? 2 : 3)));
        let genderVal = user.gender_name;
        if (!genderVal) {
            const g = String(user.gender || '').toLowerCase();
            genderVal = (g === '1' || g === 'male') ? 'Male' : ((g === '2' || g === 'female') ? 'Female' : '');
        }

        const isTeacher = (roleId === '2');
        const teacherType = (isTeacher && user.section && user.section !== '') ? 'Adviser' : 'Subject Teacher';

        this.editForm = {
            id: user.id,
            role_id: roleId,
            last_name: user.last_name || '',
            first_name: user.first_name || '',
            middle_name: user.middle_name || '',
            student_id: user.student_id || '',
            id_number: user.id_number || '',
            username: user.username || '',
            usernameAuto: true,
            gender: genderVal,
            email: user.email || '',
            phone_number: user.phone_number || '',
            parent_name: user.parent_name || '',
            parent_relationship: user.parent_relationship || '',
            parent_phone_number: user.parent_phone_number || '',
            teacher_type: teacherType,
            grade_level: user.grade_level || '',
            strand: user.strand || '',
            section: user.section || '',
            department: user.section || 'Executive Management',
            reset_to_default_password: false
        };
        this.activeModal = 'editUser';
    },
    closeModal() {
        this.activeModal = null;
        this.selectedUser = null;
    },
    syncCreateDefaultPassword() {
        const defaultPass = this.getDefaultPasswordForRole(this.createForm.role_id);
        this.createForm.password = defaultPass;
        this.createForm.password_confirmation = defaultPass;
    },
    studentUsername(form) {
        const lrn = String(form.id_number || '').replace(/\D/g, '');
        const firstName = String(form.first_name || '').toLowerCase().replace(/[^a-z0-9]/g, '');
        return (lrn + firstName).slice(0, 100);
    },
    syncStudentUsername(form) {
        if (String(form.role_id) === '3' && form.usernameAuto) {
            form.username = this.studentUsername(form);
        }
    },
    markUsernameManual(form) {
        form.usernameAuto = false;
    },
    getDefaultPasswordForRole(roleId) {
        if (String(roleId) === '2') return 'siafaculty@123';
        if (String(roleId) === '4') return 'siamanagement@123';
        return 'onesia@123';
    },
    isSHSGrade(gradeVal) {
        if (!gradeVal) return false;
        const g = parseInt(String(gradeVal).replace(/\D/g, ''), 10);
        return g === 11 || g === 12;
    },
    getFilteredSections(gradeVal) {
        if (!gradeVal) return [];
        const targetG = parseInt(String(gradeVal).replace(/\D/g, ''), 10);
        return this.sections.filter(s => {
            if (!s.grade_level) return false;
            const secG = parseInt(String(s.grade_level).replace(/\D/g, ''), 10);
            return secG === targetG;
        });
    },
    getFilteredStrands(gradeVal) {
        if (!gradeVal || !this.isSHSGrade(gradeVal)) return [];
        const targetG = parseInt(String(gradeVal).replace(/\D/g, ''), 10);
        let list = this.sections
            .filter(s => s.grade_level && parseInt(String(s.grade_level).replace(/\D/g, ''), 10) === targetG && s.strand)
            .map(s => String(s.strand).trim().toUpperCase());
        if (list.length === 0) {
            return ['TVL-ICT', 'STEM', 'ABM', 'HUMSS'];
        }
        return [...new Set(list)];
    },
    formatGender(user) {
        if (!user) return 'Not Specified';
        if (user.gender_name) return user.gender_name;
        const g = String(user.gender || '').toLowerCase().trim();
        if (g === '1' || g === 'male') return 'Male';
        if (g === '2' || g === 'female') return 'Female';
        return user.gender || 'Not Specified';
    },
    formatFullName(user) {
        if (!user) return '';
        let name = user.last_name + ', ' + user.first_name;
        if (user.middle_name) {
            name += ' ' + user.middle_name;
        }
        return name;
    },
    getStrand(user) {
        if (!user) return null;
        const grade = parseInt(user.grade_level, 10);
        if (grade >= 11 && user.strand && user.strand.toUpperCase() !== 'N/A') {
            return user.strand;
        }
        return null;
    },
    formatTime(timeStr) {
        if (!timeStr) return '--:--';
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let hours = parseInt(parts[0], 10);
        const minutes = parts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return `${hours}:${minutes} ${ampm}`;
    },
    getSchedulesByDay(dayName) {
        if (!this.selectedUser || !this.selectedUser.class_schedules) return [];
        if (dayName === 'ALL') return this.selectedUser.class_schedules;
        return this.selectedUser.class_schedules.filter(s => {
            const d = (s.day || '').trim().toLowerCase();
            return d === dayName.toLowerCase();
        });
    },
    getDayCount(dayName) {
        return this.getSchedulesByDay(dayName).length;
    }
}">

    {{-- ===== PAGE HEADER ===== --}}
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">User Management</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Manage administrators, faculty, and student accounts</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-slate-600">
                <i class="fa-solid fa-users text-[#8b1818]"></i>
                {{ number_format($totalUsers ?? 0) }} total accounts
            </span>
            <a href="{{ route('admin.users.export', ['type' => $roleFilter ?? 'admin']) }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl transition shadow-sm">
                <i class="fa-solid fa-file-excel"></i> Export
            </a>
            <button type="button" @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-user-plus text-amber-300"></i> Add New User
            </button>
        </div>
    </header>

    <main class="flex-1 p-6 lg:px-10 lg:py-8 space-y-6 max-w-[1600px] mx-auto w-full">
    <!-- Flash Alert Banners -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    <!-- ACCOUNT MONITORING -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Accounts</span>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($totalUsers ?? 0) }}</p>
            <span class="text-[10px] font-bold text-slate-400">All registered users</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600">Students</span>
            <p class="mt-2 text-2xl font-black text-emerald-700">{{ number_format($studentCount ?? 0) }}</p>
            <span class="text-[10px] font-bold text-slate-400">{{ number_format($maleCount ?? 0) }} male · {{ number_format($femaleCount ?? 0) }} female</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-blue-600">Faculty</span>
            <p class="mt-2 text-2xl font-black text-blue-700">{{ number_format($facultyCount ?? 0) }}</p>
            <span class="text-[10px] font-bold text-slate-400">Teachers and advisers</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-purple-600">Management</span>
            <p class="mt-2 text-2xl font-black text-purple-700">{{ number_format($managementCount ?? 0) }}</p>
            <span class="text-[10px] font-bold text-slate-400">Management accounts</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-600">Active</span>
            <p class="mt-2 text-2xl font-black text-amber-700">{{ number_format($activeUserCount ?? 0) }}</p>
            <span class="text-[10px] font-bold text-slate-400">Accounts enabled</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-rose-600">Inactive</span>
            <p class="mt-2 text-2xl font-black text-rose-700">{{ number_format($inactiveUserCount ?? 0) }}</p>
            <span class="text-[10px] font-bold text-slate-400">Accounts disabled</span>
        </div>
    </section>

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border-2 border-red-200 text-red-800 text-xs font-bold rounded-2xl flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-[#8b1818] text-base mt-0.5"></i>
            <div>
                <span class="font-extrabold block">Submission Error:</span>
                <ul class="list-disc pl-4 space-y-0.5 text-[11px]">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- CATEGORIZATION ROLE TABS BAR -->
    @php
        $activeRole = strtolower($roleFilter ?? 'faculty');
    @endphp
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-200">

        <!-- Faculty / Teachers Tab -->
        <a href="{{ route('admin.users.index', ['role' => 'faculty']) }}" 
           class="px-5 py-3 rounded-t-2xl text-xs font-black transition flex items-center gap-2.5 border-t-2 border-x border-b-0 cursor-pointer whitespace-nowrap {{ in_array($activeRole, ['faculty', 'teacher']) ? 'bg-white text-[#8b1818] border-t-[#8b1818] border-x-slate-200 -mb-px shadow-2xs' : 'bg-slate-50 text-slate-600 border-transparent hover:bg-slate-100' }}">
            <i class="fa-solid fa-chalkboard-user text-blue-500"></i>
            <span>Faculty / Teachers</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ in_array($activeRole, ['faculty', 'teacher']) ? 'bg-red-50 text-[#8b1818]' : 'bg-slate-200 text-slate-700' }}">
                {{ $facultyCount ?? 0 }}
            </span>
        </a>

        <!-- Students Tab -->
        <a href="{{ route('admin.users.index', ['role' => 'student']) }}" 
           class="px-5 py-3 rounded-t-2xl text-xs font-black transition flex items-center gap-2.5 border-t-2 border-x border-b-0 cursor-pointer whitespace-nowrap {{ in_array($activeRole, ['student', 'students']) ? 'bg-white text-[#8b1818] border-t-[#8b1818] border-x-slate-200 -mb-px shadow-2xs' : 'bg-slate-50 text-slate-600 border-transparent hover:bg-slate-100' }}">
            <i class="fa-solid fa-graduation-cap text-emerald-500"></i>
            <span>Students Directory</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ in_array($activeRole, ['student', 'students']) ? 'bg-red-50 text-[#8b1818]' : 'bg-slate-200 text-slate-700' }}">
                {{ $studentCount ?? 0 }}
            </span>
        </a>

        <!-- Management Tab -->
        <a href="{{ route('admin.users.index', ['role' => 'management']) }}" 
           class="px-5 py-3 rounded-t-2xl text-xs font-black transition flex items-center gap-2.5 border-t-2 border-x border-b-0 cursor-pointer whitespace-nowrap {{ in_array($activeRole, ['director', 'management']) ? 'bg-white text-[#8b1818] border-t-[#8b1818] border-x-slate-200 -mb-px shadow-2xs' : 'bg-slate-50 text-slate-600 border-transparent hover:bg-slate-100' }}">
            <i class="fa-solid fa-building-user text-purple-500"></i>
            <span>Management</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ in_array($activeRole, ['director', 'management']) ? 'bg-red-50 text-[#8b1818]' : 'bg-slate-200 text-slate-700' }}">
                {{ $managementCount ?? $directorCount ?? 0 }}
            </span>
        </a>

        <!-- Optional Global View: All Accounts -->
        <a href="{{ route('admin.users.index', ['role' => 'all']) }}" 
           class="px-4 py-3 rounded-t-2xl text-xs font-black transition flex items-center gap-2 border-t-2 border-x border-b-0 cursor-pointer whitespace-nowrap ml-auto {{ $activeRole === 'all' ? 'bg-white text-[#8b1818] border-t-[#8b1818] border-x-slate-200 -mb-px' : 'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100' }}">
            <i class="fa-solid fa-users text-slate-400"></i>
            <span>All Accounts</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700">
                {{ $totalUsers ?? 0 }}
            </span>
        </a>
    </div>

    <!-- MAIN CONTAINER WITH DYNAMIC FILTERS & TABLE -->
    <div class="bg-white rounded-b-3xl rounded-tr-3xl border-2 border-slate-200 shadow-xs overflow-hidden">
        
        <!-- DYNAMIC TOOLBAR: DYNAMIC FILTERS PER TAB -->
        <div class="p-5 border-b border-slate-200 bg-slate-50/70 space-y-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                <input type="hidden" name="role" value="{{ $activeRole }}">

                <!-- Left Side: Search Bar -->
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-search text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search ?? '' }}" 
                           placeholder="Search name, ID, email..." 
                           class="w-full pl-9 pr-4 py-2.5 text-xs bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-[#8b1818] font-bold text-slate-800 shadow-2xs">
                </div>

                <!-- Right Side: Dynamic Filter Dropdowns based on Active Tab -->
                <div class="flex flex-wrap items-center gap-3">
                    
                    <!-- Account Status Filter -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-500">Status:</span>
                        <select name="status" onchange="this.form.submit()" 
                                class="px-3 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl outline-none focus:border-[#8b1818] cursor-pointer">
                            <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
                            <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                        </select>
                    </div>

                    <!-- FACULTY SPECIFIC FILTERS -->
                    @if(in_array($activeRole, ['faculty', 'teacher']))
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-500">Designation:</span>
                            <select name="designation" onchange="this.form.submit()" 
                                    class="px-3 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl outline-none focus:border-[#8b1818] cursor-pointer">
                                <option value="" {{ empty($designationFilter) ? 'selected' : '' }}>All Designations</option>
                                <option value="adviser" {{ ($designationFilter ?? '') === 'adviser' ? 'selected' : '' }}>Advisers Only</option>
                                <option value="subject_teacher" {{ ($designationFilter ?? '') === 'subject_teacher' ? 'selected' : '' }}>Subject Teachers</option>
                            </select>
                        </div>
                    @endif

                    <!-- STUDENT SPECIFIC FILTERS -->
                    @if(in_array($activeRole, ['student', 'students']))
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-500">Grade:</span>
                            <select name="grade_level" onchange="this.form.submit()" 
                                    class="px-3 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl outline-none focus:border-[#8b1818] cursor-pointer">
                                <option value="" {{ empty($gradeFilter) ? 'selected' : '' }}>All Grades</option>
                                @foreach($gradeLevels ?? [] as $grade)
                                    <option value="{{ $grade }}" {{ ($gradeFilter ?? '') == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-500">Strand:</span>
                            <select name="strand" onchange="this.form.submit()" {{ ((int) preg_replace('/\D+/', '', (string) ($gradeFilter ?? '')) >= 7 && (int) preg_replace('/\D+/', '', (string) ($gradeFilter ?? '')) <= 10) ? 'disabled' : '' }}
                                    class="px-3 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl outline-none focus:border-[#8b1818] cursor-pointer">
                                <option value="" {{ empty($strandFilter) ? 'selected' : '' }}>N/A for Grades 7–10</option>
                                @foreach($strands ?? [] as $strand)
                                    <option value="{{ $strand }}" {{ ($strandFilter ?? '') == $strand ? 'selected' : '' }}>{{ $strand }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-500">Section:</span>
                            <select name="section" onchange="this.form.submit()" 
                                    class="px-3 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl outline-none focus:border-[#8b1818] cursor-pointer">
                                <option value="" {{ empty($sectionFilter) ? 'selected' : '' }}>All Sections</option>
                                @foreach($sectionList ?? [] as $sec)
                                    <option value="{{ $sec }}" {{ ($sectionFilter ?? '') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition">
                        Filter
                    </button>

                    @if(!empty($search) || ($statusFilter ?? 'all') !== 'all' || !empty($gradeFilter) || !empty($strandFilter) || !empty($sectionFilter) || !empty($designationFilter))
                        <a href="{{ route('admin.users.index', ['role' => $activeRole]) }}" 
                           class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1"
                           title="Clear Filters">
                            <i class="fa-solid fa-rotate-left text-[10px]"></i> Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- ACCOUNTS TABLE (DYNAMIC BY ACTIVE TAB) -->
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-[10px] font-black text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">#</th>

                        @if($activeRole === 'admin')
                            <!-- ADMIN TAB HEADERS -->
                            <th class="py-3.5 px-6">Administrator Name</th>
                            <th class="py-3.5 px-6">Employee ID / Username</th>
                            <th class="py-3.5 px-6">Email / Contact</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6">Date Registered</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>

                        @elseif(in_array($activeRole, ['faculty', 'teacher']))
                            <!-- FACULTY TAB HEADERS -->
                            <th class="py-3.5 px-6">Faculty Name</th>
                            <th class="py-3.5 px-6">Employee ID</th>
                            <th class="py-3.5 px-6">Designation / Placement</th>
                            <th class="py-3.5 px-6">Schedule Summary</th>
                            <th class="py-3.5 px-6">Email / Contact</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>

                        @elseif(in_array($activeRole, ['student', 'students']))
                            <!-- STUDENT TAB HEADERS -->
                            <th class="py-3.5 px-6">Student Name</th>
                            <th class="py-3.5 px-6">LRN / Student ID</th>
                            <th class="py-3.5 px-6">Grade & Strand</th>
                            <th class="py-3.5 px-6">Section Placement</th>
                            <th class="py-3.5 px-6">Guardian Contact</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>

                        @elseif(in_array($activeRole, ['director', 'management']))
                            <!-- MANAGEMENT TAB HEADERS -->
                            <th class="py-3.5 px-6">Management Name</th>
                            <th class="py-3.5 px-6">Employee ID</th>
                            <th class="py-3.5 px-6">Department / Office Placement</th>
                            <th class="py-3.5 px-6">Email / Contact</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>

                        @else
                            <!-- ALL ACCOUNTS HEADERS -->
                            <th class="py-3.5 px-6">Name / Account</th>
                            <th class="py-3.5 px-6">ID Number</th>
                            <th class="py-3.5 px-6">Role & Placement</th>
                            <th class="py-3.5 px-6">Email / Contact</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($users ?? [] as $user)
                        @php
                            $isTeacher  = (int)($user->role_id ?? 0) === 2 || in_array(strtolower($user->role ?? ''), ['teacher', 'faculty']);
                            $isStudent  = (int)($user->role_id ?? 0) === 3 || strtolower($user->role ?? '') === 'student';
                            $isAdmin    = (int)($user->role_id ?? 0) === 1 || strtolower($user->role ?? '') === 'admin';
                            $isManagement = (int)($user->role_id ?? 0) === 4 || in_array(strtolower($user->role ?? ''), ['director', 'management']);

                            $isActive   = (bool)($user->is_active ?? true);

                            // Schedule Calculations for Faculty
                            $schedCount = isset($user->classSchedules) ? $user->classSchedules->count() : 0;
                            $sectionCount = isset($user->classSchedules) ? $user->classSchedules->pluck('section')->filter()->unique()->count() : 0;
                            if($sectionCount === 0 && isset($user->classSchedules)) {
                                $sectionCount = $user->classSchedules->pluck('section_id')->filter()->unique()->count();
                            }
                        @endphp

                        <tr class="hover:bg-slate-50/90 transition align-middle">
                            <td class="py-3.5 px-4 text-center font-bold text-slate-400 text-xs whitespace-nowrap">
                                {{ method_exists($users, 'firstItem') && $users->firstItem() ? $users->firstItem() + $loop->index : $loop->iteration }}
                            </td>

                            <!-- ADMINISTRATORS TAB VIEW -->
                            @if($activeRole === 'admin')
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($user->first_name ?? 'A', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? 'D', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="text-slate-900 font-black block">{{ $user->last_name }}, {{ $user->first_name }}{{ !empty($user->middle_name) ? ' ' . $user->middle_name : '' }}</span>
                                            <span class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded font-bold border border-amber-200">System Administrator</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-6 font-mono text-slate-700 font-bold">
                                    {{ $user->id_number ?? 'ADM-' . str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                                </td>

                                <td class="py-3.5 px-6 text-slate-600">
                                    <div class="font-bold text-slate-800 text-xs">{{ $user->email ?? 'No email' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $user->phone_number ?? 'No phone' }}</div>
                                </td>

                                <td class="py-3.5 px-6">
                                    @if($isActive)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">ACTIVE</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">INACTIVE</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-6 text-slate-500 text-xs">
                                    {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}
                                </td>

                            <!-- FACULTY TAB VIEW -->
                            @elseif(in_array($activeRole, ['faculty', 'teacher']))
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($user->first_name ?? 'F', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? 'T', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="text-slate-900 font-black block">{{ $user->last_name }}, {{ $user->first_name }}{{ !empty($user->middle_name) ? ' ' . $user->middle_name : '' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-6 font-mono text-slate-700 font-bold">
                                    {{ $user->id_number ?? 'TCH-' . str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                                </td>

                                <td class="py-3.5 px-6">
                                    @if(!empty($user->section) || !empty($user->grade_level))
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-50 text-amber-950 text-[11px] font-black border border-amber-300">
                                            <i class="fa-solid fa-crown text-[10px] text-amber-600"></i>
                                            <span>Adviser: {{ $user->grade_level ? 'Grade ' . $user->grade_level . ' - ' : '' }}{{ $user->section }}</span>
                                        </div>
                                    @else
                                        <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-900 text-[11px] font-bold border border-blue-200">
                                            Subject Teacher
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-6">
                                    @if($schedCount > 0)
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-800 rounded-xl text-[11px] font-bold border border-slate-200">
                                                <i class="fa-solid fa-book-bookmark text-[#8b1818] mr-1"></i>
                                                {{ $schedCount }} {{ Str::plural('Class', $schedCount) }} {{ $sectionCount > 0 ? "· {$sectionCount} " . Str::plural('Section', $sectionCount) : '' }}
                                            </span>
                                            <button type="button" @click="viewSchedule({{ json_encode($user) }})" 
                                                    class="text-[11px] font-bold text-[#8b1818] hover:underline cursor-pointer flex items-center gap-1">
                                                <i class="fa-solid fa-calendar-days text-[10px]"></i> View Schedule
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">No schedule assigned</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-6 text-slate-600">
                                    <div class="font-bold text-slate-800 text-xs">{{ $user->email ?? 'No email' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $user->phone_number ?? 'No phone' }}</div>
                                </td>

                                <td class="py-3.5 px-6">
                                    @if($isActive)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">ACTIVE</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">INACTIVE</span>
                                    @endif
                                </td>

                            <!-- STUDENTS TAB VIEW -->
                            @elseif(in_array($activeRole, ['student', 'students']))
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($user->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? 'T', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="text-slate-900 font-black block">
                                                {{ $user->last_name }}, {{ $user->first_name }}{{ !empty($user->middle_name) ? ' ' . $user->middle_name : '' }}
                                            </span>
                                            @if(!empty($user->nfcCard?->tag_id))
                                                <span class="text-[10px] font-mono text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                    NFC: {{ $user->nfcCard->tag_id }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-6">
                                    <div class="font-mono text-slate-700 font-bold text-xs">
                                        {{ $user->id_number ?? 'LRN-' . str_pad($user->id, 5, '0', STR_PAD_LEFT) }}
                                    </div>
                                    @if(!empty($user->student_id))
                                        <div class="text-[10px] text-slate-500 font-mono font-bold">
                                            ID: {{ $user->student_id }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-1.5">
                                        @if($user->grade_level)
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded font-bold text-[11px] border border-slate-200">
                                                Grade {{ $user->grade_level }}
                                            </span>
                                        @endif
                                        @if((int)($user->grade_level ?? 0) >= 11 && !empty($user->strand) && strtoupper($user->strand) !== 'N/A')
                                            <span class="px-2 py-0.5 bg-red-50 text-[#8b1818] rounded font-black text-[11px] border border-red-200">
                                                {{ $user->strand }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-800 text-xs">
                                        {{ $user->section ?? 'Unassigned' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-6 text-slate-600">
                                    @if($user->parent_name || $user->parent_phone_number)
                                        <div class="font-bold text-slate-800 text-xs">
                                            {{ $user->parent_name ?? 'Guardian' }}
                                            @if(!empty($user->parent_relationship))
                                                <span class="text-[10px] text-slate-500 font-semibold">({{ $user->parent_relationship }})</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $user->parent_phone_number ?? 'No phone' }}</div>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">No parent info</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-6">
                                    @if($isActive)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">ACTIVE</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">INACTIVE</span>
                                    @endif
                                </td>

                            <!-- MANAGEMENT TAB VIEW -->
                            @elseif(in_array($activeRole, ['director', 'management']))
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-purple-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($user->first_name ?? 'M', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? 'G', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="text-slate-900 font-black block">{{ $user->last_name }}, {{ $user->first_name }}{{ !empty($user->middle_name) ? ' ' . $user->middle_name : '' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-6 font-mono text-slate-700 font-bold">
                                    {{ $user->id_number ?? 'MNG-' . str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                                </td>

                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-purple-50 text-purple-950 text-[11px] font-black border border-purple-200">
                                        <i class="fa-solid fa-building-user text-[10px] text-purple-600"></i>
                                        <span>{{ $user->section ?? 'Executive Management' }}</span>
                                    </span>
                                </td>

                                <td class="py-3.5 px-6 text-slate-600">
                                    <div class="font-bold text-slate-800 text-xs">{{ $user->email ?? 'No email' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $user->phone_number ?? 'No phone' }}</div>
                                </td>

                                <td class="py-3.5 px-6">
                                    @if($isActive)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">ACTIVE</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">INACTIVE</span>
                                    @endif
                                </td>

                            <!-- ALL ACCOUNTS TAB VIEW -->
                            @else
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#8b1818] text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? 'S', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="text-slate-900 font-black block">{{ $user->last_name }}, {{ $user->first_name }}{{ !empty($user->middle_name) ? ' ' . $user->middle_name : '' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-6 font-mono text-slate-700 font-bold">
                                    {{ $user->id_number ?? 'ID-' . $user->id }}
                                </td>

                                <td class="py-3.5 px-6">
                                    @php
                                        $roleBadge = match(true) {
                                            $isAdmin => ['bg' => 'bg-amber-100 text-amber-900 border-amber-300', 'label' => 'Administrator'],
                                            $isTeacher => ['bg' => 'bg-blue-100 text-blue-900 border-blue-300', 'label' => 'Faculty'],
                                            $isStudent => ['bg' => 'bg-emerald-100 text-emerald-900 border-emerald-300', 'label' => 'Student'],
                                            default => ['bg' => 'bg-purple-100 text-purple-900 border-purple-300', 'label' => 'Management']
                                        };
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black border uppercase {{ $roleBadge['bg'] }}">
                                        {{ $roleBadge['label'] }}
                                    </span>
                                    @if($isManagement && !empty($user->section))
                                        <div class="text-[11px] font-bold text-purple-950 mt-1">
                                            {{ $user->section }}
                                        </div>
                                    @elseif($isTeacher && (!empty($user->section) || !empty($user->grade_level)))
                                        <div class="text-[11px] font-bold text-slate-600 mt-1">
                                            {{ $user->section ? ($user->grade_level ? 'Grade ' . $user->grade_level . ' - ' : '') . $user->section : 'Subject Teacher' }}
                                        </div>
                                    @elseif($isStudent && !empty($user->section))
                                        <div class="text-[11px] font-bold text-slate-600 mt-1">
                                            {{ $user->grade_level ? 'Grade ' . $user->grade_level . ' - ' : '' }}{{ $user->section }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-6 text-slate-600">
                                    <div class="font-bold text-slate-800 text-xs">{{ $user->email ?? 'No email' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $user->phone_number ?? 'No phone' }}</div>
                                </td>

                                <td class="py-3.5 px-6">
                                    @if($isActive)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">ACTIVE</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">INACTIVE</span>
                                    @endif
                                </td>
                            @endif

                            <!-- ACTIONS COLUMN: TRIGGER FOR GLOBAL FIXED DROPDOWN MENU -->
                            <td class="py-3.5 px-6 text-right">
                                <button type="button" 
                                        @click.stop="toggleMenu($event, {{ json_encode($user) }})" 
                                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 inline-flex items-center justify-center transition focus:outline-none cursor-pointer">
                                    <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                <i class="fa-solid fa-users-slash text-3xl mb-3 block text-slate-300"></i>
                                No accounts found matching the specified tab and filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if(isset($users) && method_exists($users, 'links'))
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    </main>

    <!-- GLOBAL FIXED KEBAB DROPDOWN MENU -->
    <div x-show="menuUser" 
         @click.away="closeMenu()"
         @scroll.window="closeMenu()"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="fixed w-52 rounded-2xl shadow-2xl bg-white border border-slate-200 ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 z-50 text-xs text-left"
         :style="`top: ${menuPos.top}px; right: ${menuPos.right}px;`"
         style="display: none;">
        
        <template x-if="menuUser">
            <div>
                <div class="py-1">
                    <button @click="viewUser(menuUser)" 
                            class="w-full text-left px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-bold flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-id-card text-slate-400 w-4"></i> View Profile Details
                    </button>

                    <template x-if="menuUser.class_schedules && menuUser.class_schedules.length > 0">
                        <button @click="viewSchedule(menuUser)" 
                                class="w-full text-left px-4 py-2.5 text-[#8b1818] hover:bg-red-50 font-bold flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-calendar-days text-[#8b1818] w-4"></i> View Schedule
                        </button>
                    </template>

                    <button type="button" @click="openEditModal(menuUser)" 
                            class="w-full text-left px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-bold flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-pen text-slate-400 w-4"></i> Edit Account
                    </button>
                </div>

                <div class="py-1">
                    <template x-if="menuUser.id != {{ auth()->id() }}">
                        <form :action="'{{ url('/admin/users') }}/' + menuUser.id + '/toggle-status'" method="POST" 
                              @submit="return confirm('Change status for ' + menuUser.first_name + ' ' + menuUser.last_name + '?');">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-bold flex items-center gap-2 cursor-pointer">
                                <template x-if="menuUser.is_active">
                                    <span class="flex items-center gap-2 text-slate-700">
                                        <i class="fa-solid fa-ban text-amber-500 w-4"></i> Disable Account
                                    </span>
                                </template>
                                <template x-if="!menuUser.is_active">
                                    <span class="flex items-center gap-2 text-slate-700">
                                        <i class="fa-solid fa-circle-check text-emerald-500 w-4"></i> Activate Account
                                    </span>
                                </template>
                            </button>
                        </form>
                    </template>

                </div>

                <template x-if="menuUser.id != {{ auth()->id() }}">
                    <div class="py-1">
                        <form :action="'{{ url('/admin/users') }}/' + menuUser.id" method="POST" 
                              @submit="return confirm('Are you sure you want to permanently delete user account: ' + menuUser.first_name + ' ' + menuUser.last_name + '?');">
                            @csrf
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-rose-600 hover:bg-rose-50 font-bold flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-trash w-4"></i> Delete Account
                            </button>
                        </form>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- MODAL 1: VIEW USER / ACCOUNT PROFILE DETAILS MODAL -->
    <div x-show="activeModal === 'viewUser'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-start justify-center p-4 pt-4"
         style="display: none;">
        
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative my-8"
             @click.away="closeModal()">
            
            <button @click="closeModal()" class="absolute top-6 right-6 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer z-50" style="right: 1.5rem !important; top: 1.5rem !important; left: auto !important;">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <template x-if="selectedUser">
                <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                    <div class="w-14 h-14 rounded-2xl bg-[#8b1818] text-white font-black text-xl flex items-center justify-center shadow-md">
                        <span x-text="(selectedUser.first_name ? selectedUser.first_name[0] : 'U') + (selectedUser.last_name ? selectedUser.last_name[0] : 'S')"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-slate-900" x-text="formatFullName(selectedUser)"></h2>
                            <template x-if="selectedUser.is_active">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">ACTIVE</span>
                            </template>
                            <template x-if="!selectedUser.is_active">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">INACTIVE</span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-500 font-bold" x-text="selectedUser.role_id == 3 ? ('Username: ' + (selectedUser.username || 'Not set')) : selectedUser.email"></p>
                    </div>
                </div>
            </template>

            <template x-if="selectedUser">
                <div class="space-y-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 block">Student ID / Employee ID</span>
                            <span class="font-mono font-bold text-slate-800" x-text="selectedUser.student_id || selectedUser.id_number || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 block">LRN Number</span>
                            <span class="font-mono font-bold text-slate-800" x-text="selectedUser.id_number || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 block">Gender</span>
                            <span class="font-bold text-slate-800" x-text="formatGender(selectedUser)"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 block">Phone</span>
                            <span class="font-mono font-bold text-slate-800" x-text="selectedUser.phone_number || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 block">Grade Level</span>
                            <span class="font-bold text-slate-800" x-text="selectedUser.grade_level ? 'Grade ' + selectedUser.grade_level : 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 block">Section / Office</span>
                            <span class="font-bold text-slate-800" x-text="selectedUser.section || 'N/A'"></span>
                        </div>
                    </div>

                    <template x-if="selectedUser.nfc_card && selectedUser.nfc_card.tag_id">
                        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-emerald-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-id-card text-emerald-600"></i> Bound NFC Card UID
                            </span>
                            <span class="font-mono font-black text-emerald-800" x-text="selectedUser.nfc_card.tag_id"></span>
                        </div>
                    </template>
                    <template x-if="selectedUser.role_id == 3 && (!selectedUser.nfc_card || !selectedUser.nfc_card.tag_id)">
                        <a :href="'{{ route('admin.nfc.binding') }}?student_id=' + selectedUser.id"
                           class="p-3 bg-amber-50 rounded-xl border border-amber-200 flex items-center justify-between text-xs hover:bg-amber-100 transition">
                            <span class="font-bold text-amber-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-link text-amber-600"></i> No NFC tag bound
                            </span>
                            <span class="px-3 py-1.5 rounded-lg bg-[#8b1818] text-white font-black">Bind NFC Tag</span>
                        </a>
                    </template>

                    <template x-if="selectedUser.parent_name || selectedUser.parent_phone_number">
                        <div class="p-4 bg-amber-50/60 rounded-2xl border border-amber-200 space-y-1">
                            <span class="text-[10px] font-black uppercase text-amber-900 tracking-wider block">Guardian / Emergency Contact</span>
                            <div class="text-xs font-bold text-slate-800" x-text="(selectedUser.parent_name || 'N/A') + (selectedUser.parent_relationship ? ' (' + selectedUser.parent_relationship + ')' : '')"></div>
                            <div class="text-xs font-mono text-slate-600" x-text="selectedUser.parent_phone_number || 'No contact number'"></div>
                        </div>
                    </template>

                    <template x-if="selectedUser.class_schedules && selectedUser.class_schedules.length > 0">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-black text-slate-800 block">Schedule & Assignments</span>
                                <span class="text-[11px] text-slate-500 font-bold" x-text="selectedUser.class_schedules.length + ' Weekly Classes Assigned'"></span>
                            </div>
                            <button type="button" @click="viewSchedule(selectedUser)" class="px-4 py-2 bg-[#8b1818] text-white text-xs font-bold rounded-xl hover:bg-[#731414] transition shadow-xs cursor-pointer">
                                <i class="fa-solid fa-calendar-days mr-1"></i> View Schedule Timetable
                            </button>
                        </div>
                    </template>
                </div>
            </template>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button @click="openEditModal(selectedUser)" class="px-6 py-2.5 bg-[#8b1818] hover:bg-[#731414] text-white font-bold text-xs rounded-xl transition shadow-xs cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-pen text-[10px]"></i> Update Profile
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: FACULTY SCHEDULE TIMETABLE MODAL -->
    <div x-show="activeModal === 'viewSchedule'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-start justify-center p-4"
         style="display: none;">
        
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-3xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative mt-0 mb-8 max-h-[calc(100vh-2rem)] overflow-y-auto"
             @click.away="closeModal()">
            
            <button @click="closeModal()" class="absolute top-6 right-6 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer z-50" style="right: 1.5rem !important; top: 1.5rem !important; left: auto !important;">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <template x-if="selectedUser">
                <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                    <div class="w-12 h-12 rounded-2xl bg-[#8b1818] text-white font-black text-xl flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-calendar-days text-amber-300"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-[#8b1818] tracking-tight" x-text="formatFullName(selectedUser)"></h2>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-sm font-bold text-slate-500 uppercase tracking-wide" x-text="selectedUser.role_id == 3 ? 'Student Class Schedule' : 'Faculty Instruction Schedule'"></span>
                            <span class="text-[11px] text-slate-400 font-mono" x-text="'(' + (selectedUser.student_id || selectedUser.id_number || 'ID-' + selectedUser.id) + ')'"></span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="selectedUser">
                <div class="space-y-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-red-50/50 p-4 rounded-2xl border border-red-200/80">
                        <div>
                            <span class="text-[10px] uppercase font-black text-red-900/60 block">Total Weekly Classes</span>
                            <span class="text-sm font-black text-[#8b1818]" x-text="(selectedUser.class_schedules ? selectedUser.class_schedules.length : 0) + ' Classes'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-black text-red-900/60 block" x-text="selectedUser.role_id == 3 ? 'Grade & Section' : 'Designation'"></span>
                            <span class="text-xs font-black text-slate-800" x-text="selectedUser.role_id == 3 ? (selectedUser.grade_level ? 'Grade ' + selectedUser.grade_level : '') + (selectedUser.section ? ' - ' + selectedUser.section : '') : (selectedUser.section || selectedUser.grade_level ? 'Adviser (' + (selectedUser.section || 'Advisory') + ')' : 'Subject Teacher')"></span>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <span class="text-[10px] uppercase font-black text-red-900/60 block" x-text="selectedUser.role_id == 3 ? 'Username' : 'Email Address'"></span>
                                    <span class="text-xs font-bold text-slate-800 truncate block" x-text="selectedUser.role_id == 3 ? selectedUser.username : selectedUser.email"></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 border-b border-slate-200 text-xs">
                        <template x-for="day in ['ALL', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day">
                            <button type="button" @click="activeDayTab = day" 
                                    class="px-3.5 py-2 rounded-xl font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                                    :class="activeDayTab === day ? 'bg-[#8b1818] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                                <span x-text="day === 'ALL' ? 'All Days' : day"></span>
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black"
                                      :class="activeDayTab === day ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-800'"
                                      x-text="getDayCount(day)"></span>
                            </button>
                        </template>
                    </div>

                    <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                        <template x-if="getSchedulesByDay(activeDayTab).length === 0">
                            <div class="p-8 text-center text-slate-400 font-bold bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                <i class="fa-solid fa-calendar-xmark text-2xl text-slate-300 block mb-1"></i>
                                No scheduled classes on <span x-text="activeDayTab === 'ALL' ? 'any day' : activeDayTab"></span>.
                            </div>
                        </template>

                        <template x-for="sched in getSchedulesByDay(activeDayTab)" :key="sched.id">
                            <div class="p-4 bg-white rounded-2xl border-2 border-slate-100 hover:border-slate-300 shadow-2xs transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-black text-slate-900" x-text="sched.subject_name || sched.subject || 'Subject'"></h4>
                                        <template x-if="sched.subject_code">
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-mono font-bold rounded border border-slate-200" x-text="sched.subject_code"></span>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-600 font-bold">
                                        <template x-if="selectedUser.role_id != 3">
                                            <span class="flex items-center gap-1 text-slate-700">
                                                <i class="fa-solid fa-users text-slate-400 text-[11px]"></i>
                                                Section: <strong class="text-slate-900" x-text="sched.section || 'N/A'"></strong>
                                            </span>
                                        </template>
                                        <template x-if="selectedUser.role_id == 3">
                                            <span class="flex items-center gap-1 text-slate-700">
                                                <i class="fa-solid fa-chalkboard-user text-slate-400 text-[11px]"></i>
                                                Teacher: <strong class="text-slate-900" x-text="sched.teacher ? (sched.teacher.first_name + ' ' + sched.teacher.last_name) : 'TBA'"></strong>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div class="sm:text-right shrink-0 space-y-1">
                                    <span class="px-3 py-1 bg-red-50 text-[#8b1818] font-black rounded-lg text-xs border border-red-200 inline-block" x-text="sched.day || 'Day'"></span>
                                    <div class="text-xs font-mono font-black text-slate-800 flex items-center sm:justify-end gap-1">
                                        <i class="fa-solid fa-clock text-slate-400 text-[11px]"></i>
                                        <span x-text="formatTime(sched.start_time) + ' - ' + formatTime(sched.end_time)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <a href="{{ route('admin.schedules.index') }}" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-xs flex items-center gap-2 text-center cursor-pointer">
                    <i class="fa-solid fa-calendar-plus text-[10px]"></i> Manage Schedules
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL 3: QUICK RESET PASSWORD MODAL -->
    <div x-show="activeModal === 'resetPassword'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-start justify-center p-4"
         style="display: none;">
        
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl relative my-8"
             @click.away="closeModal()">
            
            <button @click="closeModal()" class="absolute top-6 right-6 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer z-50" style="right: 1.5rem !important; top: 1.5rem !important; left: auto !important;">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-lg font-black shrink-0">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900">Reset User Password</h2>
                    <p class="text-xs text-slate-500 font-bold" x-text="selectedUser ? formatFullName(selectedUser) : ''"></p>
                </div>
            </div>

            <template x-if="selectedUser">
                <form :action="'{{ url('/admin/users') }}/' + selectedUser.id + '/reset-password'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">New Password</label>
                        <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters"
                               class="w-full px-4 py-3 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required minlength="8" placeholder="Re-type password"
                               class="w-full px-4 py-3 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="closeModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-[#8b1818] hover:bg-[#731414] text-white font-bold text-xs rounded-xl shadow-md transition cursor-pointer">
                            Save New Password
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 4: INLINE ADD NEW USER ACCOUNT MODAL                             -->
    <!-- ===================================================================== -->
    <div x-show="activeModal === 'createUser'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-start justify-center p-4"
         style="display: none;">
        
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-3xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative my-8 max-h-[90vh] overflow-y-auto"
             @click.away="closeModal()">
            
            <button @click="closeModal()" class="absolute top-6 right-6 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer z-50" style="right: 1.5rem !important; top: 1.5rem !important; left: auto !important;">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100">
                <div class="w-12 h-12 rounded-2xl bg-[#8b1818] text-white flex items-center justify-center text-xl shrink-0 shadow-md">
                    <i class="fa-solid fa-user-plus text-amber-300"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Add New User Account</h2>
                    <p class="text-xs font-bold text-slate-500 mt-0.5">Enroll and register a new user into the directory.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" autocomplete="off" class="space-y-6">
                @csrf

                <!-- Account Role Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-900 mb-1.5">Account Role Type</label>
                    <select name="role_id" x-model="createForm.role_id" @change="syncCreateDefaultPassword()" required
                        class="w-full px-4 py-3 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        <option value="3">Student</option>
                        <option value="2">Teacher</option>
                        <option value="4">Management</option>
                    </select>
                </div>

                <!-- SECTION 1: PERSONAL INFORMATION -->
                <div class="space-y-4">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Personal Information</h3>

                    <!-- ROW 1: Last Name | First Name | Middle Name -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Last Name <span class="text-red-600">*</span></label>
                            <input type="text" name="last_name" x-model="createForm.last_name" required
                                @input="$el.value = $el.value.toUpperCase()" placeholder="LAST NAME"
                                class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">First Name <span class="text-red-600">*</span></label>
                            <input type="text" name="first_name" x-model="createForm.first_name" required
                                @input="$el.value = $el.value.toUpperCase(); createForm.first_name = $el.value; syncStudentUsername(createForm)" placeholder="FIRST NAME"
                                class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Middle Name</label>
                            <input type="text" name="middle_name" x-model="createForm.middle_name"
                                @input="$el.value = $el.value.toUpperCase()" placeholder="MIDDLE NAME"
                                class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                    </div>

                    <!-- ROW 2: Student ID | LRN / Employee ID | Gender -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <span x-text="createForm.role_id == '3' ? 'Student ID' : 'Employee ID'"></span><span class="text-red-600"> *</span>
                            </label>
                            <input type="text" name="student_id" x-model="createForm.student_id" required
                                placeholder="e.g. STU-2026-001"
                                class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <span x-text="createForm.role_id == '3' ? 'LRN (12-Digit Learner Ref No)' : 'Employee ID / Username'"></span><span class="text-red-600"> *</span>
                            </label>
                            <input type="text" name="id_number" x-model="createForm.id_number" required
                                @input="if(createForm.role_id == '3') { $el.value = $el.value.replace(/\D/g, '').slice(0, 12); createForm.id_number = $el.value; syncStudentUsername(createForm) }"
                                placeholder="e.g. 103063080022"
                                class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Gender <span class="text-red-600">*</span></label>
                            <select name="gender" x-model="createForm.gender" required
                                class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="createForm.role_id == '3'">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Student Username <span class="text-red-600">*</span></label>
                        <input type="text" name="username" x-model="createForm.username" :required="createForm.role_id == '3'" @input="markUsernameManual(createForm)" placeholder="Defaults to LRN + first name"
                            class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        <p class="mt-1 text-[11px] font-semibold text-slate-500">Leave blank to generate a unique username from the LRN and first name.</p>
                    </div>

                    <!-- ROW 3: Phone Number | Email Address -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Contact Number <span x-show="createForm.role_id == '3'" class="text-red-600">*</span></label>
                            <input type="text" name="phone_number" x-model="createForm.phone_number" :required="createForm.role_id == '3'" maxlength="11"
                                pattern="09\d{9}" placeholder="09XXXXXXXXX" @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div x-show="createForm.role_id != '3'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address <span class="text-red-600">*</span></label>
                            <input type="email" name="email" x-model="createForm.email" :required="createForm.role_id != '3'" placeholder="user@siatrack.edu.ph"
                                class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                    </div>
                </div>

                <!-- ROLE SPECIFIC DYNAMIC SECTIONS -->

                <!-- 1. STUDENT ACADEMIC PLACEMENT & GUARDIAN SECTION (Students Only) -->
                <div x-show="createForm.role_id == '3'" class="space-y-4 pt-4 border-t border-slate-200">
                    <h3 class="text-xs font-black text-[#8b1818] uppercase tracking-wider">Student Academic Placement</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Grade Level <span class="text-red-600">*</span></label>
                            <select name="grade_level" x-model="createForm.grade_level" @change="createForm.strand = ''"
                                :required="createForm.role_id == '3'"
                                class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                <option value="" disabled selected>Select Grade Level</option>
                                @foreach($gradeLevels ?? [] as $grade)
                                    <option value="{{ $grade }}">{{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Strand / Track <span x-show="isSHSGrade(createForm.grade_level)" class="text-red-600">*</span>
                                <span class="text-slate-400 font-normal" x-text="isSHSGrade(createForm.grade_level) ? '(Senior High Only)' : '(N/A for Junior High)'"></span>
                            </label>
                            <select name="strand" x-model="createForm.strand" :disabled="!isSHSGrade(createForm.grade_level)" :required="createForm.role_id == '3' && isSHSGrade(createForm.grade_level)"
                                class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" selected x-text="isSHSGrade(createForm.grade_level) ? 'Select Strand' : 'N/A (Junior High School)'"></option>
                                <template x-for="strand in getFilteredStrands(createForm.grade_level)" :key="strand">
                                    <option :value="strand" x-text="strand"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Section Placement <span class="text-red-600">*</span></label>
                            <select name="section" x-model="createForm.section" :required="createForm.role_id == '3'"
                                class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                <option value="" disabled selected>Select Section</option>
                                <template x-for="sec in getFilteredSections(createForm.grade_level)" :key="sec.id">
                                    <option :value="sec.section_name ?? sec.name" x-text="sec.section_name ?? sec.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <h4 class="text-xs font-black uppercase tracking-wider text-[#8b1818]">Parent / Guardian / Emergency Contact</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Full Name <span class="text-red-600">*</span></label>
                                <input type="text" name="parent_name" x-model="createForm.parent_name" :required="createForm.role_id == '3'"
                                    @input="$el.value = $el.value.toUpperCase()" placeholder="e.g. JUAN DELA CRUZ SR."
                                    class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Relationship to Student</label>
                                <select name="parent_relationship" x-model="createForm.parent_relationship" :required="createForm.role_id == '3'"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                    <option value="" selected>Select Relationship</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Grandparent">Grandparent</option>
                                    <option value="Relative">Relative</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Phone Number</label>
                                <input type="text" name="parent_phone_number" x-model="createForm.parent_phone_number" :required="createForm.role_id == '3'"
                                    maxlength="11" pattern="09\d{9}" placeholder="09XXXXXXXXX"
                                    @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                    class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TEACHER FACULTY ROLE ASSIGNMENT SECTION (Teachers Only) -->
                <div x-show="createForm.role_id == '2'" class="space-y-4 pt-4 border-t border-slate-200">
                    <h3 class="text-xs font-black text-blue-600 uppercase tracking-wider">Faculty Role Assignment</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Teacher Designation <span class="text-red-600">*</span></label>
                        <select name="teacher_type" x-model="createForm.teacher_type" :required="createForm.role_id == '2'"
                            class="w-full px-4 py-2.5 text-xs font-bold border-2 border-slate-300 rounded-xl bg-white text-slate-900 focus:border-[#8b1818] outline-none">
                            <option value="Subject Teacher">Subject Teacher</option>
                            <option value="Adviser">Adviser</option>
                        </select>
                    </div>

                    <div x-show="createForm.teacher_type === 'Adviser'" class="p-4 bg-red-50/60 border border-red-200 rounded-2xl space-y-3">
                        <span class="text-xs font-black uppercase text-[#8b1818] block">Advisory Class Placement</span>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Advisory Grade Level <span class="text-red-600">*</span></label>
                                <select name="grade_level" x-model="createForm.grade_level" :required="createForm.role_id == '2' && createForm.teacher_type === 'Adviser'"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                    <option value="" disabled selected>Select Grade Level</option>
                                    @foreach($gradeLevels ?? [] as $grade)
                                        <option value="{{ $grade }}">{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Advisory Section <span class="text-red-600">*</span></label>
                                <select name="section" x-model="createForm.section" :required="createForm.role_id == '2' && createForm.teacher_type === 'Adviser'"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                    <option value="" disabled selected>Select Advisory Section</option>
                                    <template x-for="sec in getFilteredSections(createForm.grade_level)" :key="sec.id">
                                        <option :value="sec.section_name ?? sec.name" x-text="sec.section_name ?? sec.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div x-show="createForm.teacher_type === 'Subject Teacher'" class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-xs font-bold text-blue-900 flex items-center gap-2">
                        <i class="fa-solid fa-chalkboard-user text-blue-600 text-sm"></i>
                        <span>Subject Teacher — Handles assigned instruction subjects across sections without a fixed advisory class.</span>
                    </div>
                </div>

                <!-- 3. MANAGEMENT SECTION (Management Only) -->
                <div x-show="createForm.role_id == '4'" class="space-y-4 pt-4 border-t border-slate-200">
                    <h3 class="text-xs font-black text-purple-700 uppercase tracking-wider">Administrative Department & Office Placement</h3>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Department / Management Office <span class="text-red-600">*</span></label>
                        <select name="department" x-model="createForm.department" :required="createForm.role_id == '4'"
                            class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                            <option value="Executive Management">Executive Management</option>
                            <option value="Academic Operations & Affairs">Academic Operations & Affairs</option>
                            <option value="Student Services & Affairs">Student Services & Affairs</option>
                            <option value="Finance & Administration">Finance & Administration</option>
                            <option value="IT System Administration">IT System Administration</option>
                        </select>
                    </div>
                </div>

                <!-- SECTION 4: ACCOUNT SECURITY CREDENTIALS -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <div class="p-3.5 bg-red-50/60 border border-red-200/80 rounded-2xl flex items-center justify-between text-xs">
                        <div>
                            <span class="font-black text-[#8b1818] block">Default Account Password</span>
                            <span class="text-slate-600 font-bold">New account will be initialized with: <code class="px-1.5 py-0.5 bg-white font-mono text-[#8b1818] border border-red-200 rounded" x-text="getDefaultPasswordForRole(createForm.role_id)"></code></span>
                        </div>
                        <button type="button" @click="createForm.showPassword = !createForm.showPassword"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 px-3 py-1.5 rounded-xl transition cursor-pointer">
                            <i class="fa-solid text-xs" :class="createForm.showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            <span x-text="createForm.showPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                            <input :type="createForm.showPassword ? 'text' : 'password'" name="password" x-model="createForm.password" required minlength="8"
                                class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] bg-slate-50/50 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm Password</label>
                            <input :type="createForm.showPassword ? 'text' : 'password'" name="password_confirmation" x-model="createForm.password_confirmation" required minlength="8"
                                class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] bg-slate-50/50 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="closeModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-[#8b1818] hover:bg-[#731414] text-white font-bold text-xs rounded-xl shadow-md transition cursor-pointer">
                        Save User Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 5: INLINE EDIT USER ACCOUNT MODAL                               -->
    <!-- ===================================================================== -->
    <div x-show="activeModal === 'editUser'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-3xl w-full p-6 sm:p-8 space-y-6 shadow-2xl my-8 max-h-[90vh] overflow-y-auto"
             @click.away="closeModal()">
            
            <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100">
                <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#8b1818] flex items-center justify-center text-xl shrink-0 shadow-xs">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Update Account</h2>
                    <p class="text-xs font-bold text-slate-500 mt-0.5">Update user profile details and academic section placement.</p>
                </div>
                <button @click="closeModal()" class="w-9 h-9 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer shrink-0">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <template x-if="editForm.id">
                <form :action="'{{ url('/admin/users') }}/' + editForm.id" method="POST" autocomplete="off" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="role_id" :value="editForm.role_id">

                    <!-- SECTION 1: PERSONAL INFORMATION -->
                    <div class="space-y-4">
                        <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Personal Information</h3>

                        <!-- ROW 1: Last Name | First Name | Middle Name -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Last Name <span class="text-red-600">*</span></label>
                                <input type="text" name="last_name" x-model="editForm.last_name" required
                                    @input="$el.value = $el.value.toUpperCase()" placeholder="LAST NAME"
                                    class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">First Name <span class="text-red-600">*</span></label>
                                <input type="text" name="first_name" x-model="editForm.first_name" required
                                        @input="$el.value = $el.value.toUpperCase(); editForm.first_name = $el.value; syncStudentUsername(editForm)" placeholder="FIRST NAME"
                                    class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Middle Name</label>
                                <input type="text" name="middle_name" x-model="editForm.middle_name"
                                    @input="$el.value = $el.value.toUpperCase()" placeholder="MIDDLE NAME"
                                    class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                        </div>

                        <!-- ROW 2: Student ID | LRN / Employee ID | Gender -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    <span x-text="editForm.role_id == '3' ? 'Student ID' : 'Employee ID'"></span><span class="text-red-600"> *</span>
                                </label>
                                <input type="text" name="student_id" x-model="editForm.student_id" required
                                    placeholder="e.g. STU-2026-001"
                                    class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    <span x-text="editForm.role_id == '3' ? 'LRN (12-Digit Learner Ref No)' : 'Employee ID / Username'"></span><span class="text-red-600"> *</span>
                                </label>
                                <input type="text" name="id_number" x-model="editForm.id_number" required
                                    @input="if(editForm.role_id == '3') { $el.value = $el.value.replace(/\D/g, '').slice(0, 12); editForm.id_number = $el.value; syncStudentUsername(editForm) }"
                                    placeholder="e.g. 103063080022"
                                    class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                            <div x-show="editForm.role_id == '3'">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Student Username <span class="text-red-600">*</span></label>
                                <input type="text" name="username" x-model="editForm.username" :required="editForm.role_id == '3'" @input="markUsernameManual(editForm)" placeholder="Defaults to LRN + first name"
                                    class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Gender <span class="text-red-600">*</span></label>
                                <select name="gender" x-model="editForm.gender" required
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                    <option value="" disabled>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>

                        <!-- ROW 3: Phone Number | Email Address -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Contact Number <span x-show="editForm.role_id == '3'" class="text-red-600">*</span></label>
                                <input type="text" name="phone_number" x-model="editForm.phone_number" :required="editForm.role_id == '3'" maxlength="11"
                                    pattern="09\d{9}" placeholder="09XXXXXXXXX" @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                    class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                            <div x-show="editForm.role_id != '3'">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address <span class="text-red-600">*</span></label>
                                <input type="email" name="email" x-model="editForm.email" :required="editForm.role_id != '3'" placeholder="user@siatrack.edu.ph"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- ROLE SPECIFIC DYNAMIC SECTIONS -->

                    <!-- 1. STUDENT ACADEMIC PLACEMENT & GUARDIAN SECTION (Students Only) -->
                    <div x-show="editForm.role_id == '3'" class="space-y-4 pt-4 border-t border-slate-200">
                        <h3 class="text-xs font-black text-[#8b1818] uppercase tracking-wider">Student Academic Placement</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Grade Level <span class="text-red-600">*</span></label>
                                <select name="grade_level" x-model="editForm.grade_level" @change="editForm.strand = ''"
                                    :required="editForm.role_id == '3'"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                    <option value="" disabled selected>Select Grade Level</option>
                                    @foreach($gradeLevels ?? [] as $grade)
                                        <option value="{{ $grade }}">{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Strand / Track <span x-show="isSHSGrade(editForm.grade_level)" class="text-red-600">*</span>
                                    <span class="text-slate-400 font-normal" x-text="isSHSGrade(editForm.grade_level) ? '(Senior High Only)' : '(N/A for Junior High)'"></span>
                                </label>
                                <select name="strand" x-model="editForm.strand" :disabled="!isSHSGrade(editForm.grade_level)" :required="editForm.role_id == '3' && isSHSGrade(editForm.grade_level)"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none disabled:bg-slate-100 disabled:text-slate-400">
                                    <option value="" selected x-text="isSHSGrade(editForm.grade_level) ? 'Select Strand' : 'N/A (Junior High School)'"></option>
                                    <template x-for="strand in getFilteredStrands(editForm.grade_level)" :key="strand">
                                        <option :value="strand" x-text="strand"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Section Placement <span class="text-red-600">*</span></label>
                                <select name="section" x-model="editForm.section" :required="editForm.role_id == '3'"
                                    class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                    <option value="" disabled selected>Select Section</option>
                                    <template x-for="sec in getFilteredSections(editForm.grade_level)" :key="sec.id">
                                        <option :value="sec.section_name ?? sec.name" x-text="sec.section_name ?? sec.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-3 pt-3 border-t border-slate-100">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#8b1818]">Parent / Guardian / Emergency Contact</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Full Name</label>
                                    <input type="text" name="parent_name" x-model="editForm.parent_name" :required="editForm.role_id == '3'"
                                        @input="$el.value = $el.value.toUpperCase()" placeholder="e.g. JUAN DELA CRUZ SR."
                                        class="w-full px-4 py-2.5 text-xs font-bold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Relationship to Student</label>
                                    <select name="parent_relationship" x-model="editForm.parent_relationship" :required="editForm.role_id == '3'"
                                        class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                        <option value="" selected>Select Relationship</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Guardian">Guardian</option>
                                        <option value="Grandparent">Grandparent</option>
                                        <option value="Relative">Relative</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Phone Number</label>
                                    <input type="text" name="parent_phone_number" x-model="editForm.parent_phone_number" :required="editForm.role_id == '3'"
                                        maxlength="11" pattern="09\d{9}" placeholder="09XXXXXXXXX"
                                        @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                        class="w-full px-4 py-2.5 text-xs font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. TEACHER FACULTY ROLE ASSIGNMENT SECTION (Teachers Only) -->
                    <div x-show="editForm.role_id == '2'" class="space-y-4 pt-4 border-t border-slate-200">
                        <h3 class="text-xs font-black text-blue-600 uppercase tracking-wider">Faculty Role Assignment</h3>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Teacher Designation <span class="text-red-600">*</span></label>
                            <select name="teacher_type" x-model="editForm.teacher_type" :required="editForm.role_id == '2'"
                                class="w-full px-4 py-2.5 text-xs font-bold border-2 border-slate-300 rounded-xl bg-white text-slate-900 focus:border-[#8b1818] outline-none">
                                <option value="Subject Teacher">Subject Teacher</option>
                                <option value="Adviser">Adviser</option>
                            </select>
                        </div>

                        <div x-show="editForm.teacher_type === 'Adviser'" class="p-4 bg-red-50/60 border border-red-200 rounded-2xl space-y-3">
                            <span class="text-xs font-black uppercase text-[#8b1818] block">Advisory Class Placement</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Advisory Grade Level <span class="text-red-600">*</span></label>
                                    <select name="grade_level" x-model="editForm.grade_level" :required="editForm.role_id == '2' && editForm.teacher_type === 'Adviser'"
                                        class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                        <option value="" disabled selected>Select Grade Level</option>
                                        @foreach($gradeLevels ?? [] as $grade)
                                            <option value="{{ $grade }}">{{ $grade }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Advisory Section <span class="text-red-600">*</span></label>
                                    <select name="section" x-model="editForm.section" :required="editForm.role_id == '2' && editForm.teacher_type === 'Adviser'"
                                        class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                        <option value="" disabled selected>Select Advisory Section</option>
                                        <template x-for="sec in getFilteredSections(editForm.grade_level)" :key="sec.id">
                                            <option :value="sec.section_name ?? sec.name" x-text="sec.section_name ?? sec.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div x-show="editForm.teacher_type === 'Subject Teacher'" class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-xs font-bold text-blue-900 flex items-center gap-2">
                            <i class="fa-solid fa-chalkboard-user text-blue-600 text-sm"></i>
                            <span>Subject Teacher — Handles assigned instruction subjects across sections without a fixed advisory class.</span>
                        </div>
                    </div>

                    <!-- 3. MANAGEMENT SECTION (Management Only) -->
                    <div x-show="editForm.role_id == '4'" class="space-y-4 pt-4 border-t border-slate-200">
                        <h3 class="text-xs font-black text-purple-700 uppercase tracking-wider">Administrative Department & Office Placement</h3>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Department / Management Office <span class="text-red-600">*</span></label>
                            <select name="department" x-model="editForm.department" :required="editForm.role_id == '4'"
                                class="w-full px-4 py-2.5 text-xs font-bold border border-slate-300 rounded-xl bg-white text-slate-800 focus:border-[#8b1818] outline-none">
                                <option value="Executive Management">Executive Management</option>
                                <option value="Academic Operations & Affairs">Academic Operations & Affairs</option>
                                <option value="Student Services & Affairs">Student Services & Affairs</option>
                                <option value="Finance & Administration">Finance & Administration</option>
                                <option value="IT System Administration">IT System Administration</option>
                            </select>
                        </div>
                    </div>

                    <!-- SECTION 4: ACCOUNT SECURITY CREDENTIALS -->
                    <div class="space-y-3 pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Account Security Credentials</h3>
                        <div class="p-4 bg-amber-50/80 border border-amber-200/90 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="space-y-0.5">
                                <span class="font-black text-amber-950 flex items-center gap-1.5">
                                    <i class="fa-solid fa-key text-amber-600"></i> Default Password Reset
                                </span>
                                <p class="text-slate-600 font-medium">
                                    Reset password for this account back to default: 
                                    <code class="px-1.5 py-0.5 bg-amber-100 text-amber-900 font-mono font-bold rounded border border-amber-200" x-text="getDefaultPasswordForRole(editForm.role_id)"></code>
                                </p>
                            </div>
                            <label class="inline-flex items-center gap-2 font-black text-slate-800 cursor-pointer shrink-0 bg-white px-3 py-2 rounded-xl border border-amber-300 shadow-2xs">
                                <input type="checkbox" name="reset_to_default_password" value="1" x-model="editForm.reset_to_default_password" class="w-4 h-4 text-[#8b1818] rounded border-slate-300 focus:ring-[#8b1818]">
                                <span>Reset to Default</span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="closeModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-[#8b1818] hover:bg-[#731414] text-white font-bold text-xs rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Update Account
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@section('scripts')
@endsection
@endsection