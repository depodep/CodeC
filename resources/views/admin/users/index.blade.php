@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Header Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-black text-slate-900">User Management</h1>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Manage all institutional accounts, faculty schedules, and student directory</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.export', ['type' => $roleFilter ?? 'all']) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-file-excel"></i> Export CSV
            </a>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-[#8b1818] hover:bg-opacity-90 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-user-plus"></i> Add New User
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- CATEGORIZATION TABS BAR -->
    @php
        $activeRole = strtolower($roleFilter ?? 'all');
    @endphp
    <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-1">
        <a href="{{ route('admin.users.index', ['role' => 'all', 'search' => $search ?? '']) }}" 
           class="px-4 py-2.5 rounded-2xl text-xs font-black transition flex items-center gap-2 border cursor-pointer whitespace-nowrap {{ $activeRole === 'all' ? 'bg-[#8b1818] text-white border-[#8b1818] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            <i class="fa-solid fa-users"></i>
            <span>All Accounts</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $activeRole === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-800' }}">
                {{ $totalUsers ?? 0 }}
            </span>
        </a>

        <a href="{{ route('admin.users.index', ['role' => 'admin', 'search' => $search ?? '']) }}" 
           class="px-4 py-2.5 rounded-2xl text-xs font-black transition flex items-center gap-2 border cursor-pointer whitespace-nowrap {{ $activeRole === 'admin' ? 'bg-[#8b1818] text-white border-[#8b1818] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            <i class="fa-solid fa-user-shield"></i>
            <span>Admin</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $activeRole === 'admin' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-900' }}">
                {{ $adminCount ?? 0 }}
            </span>
        </a>

        <a href="{{ route('admin.users.index', ['role' => 'faculty', 'search' => $search ?? '']) }}" 
           class="px-4 py-2.5 rounded-2xl text-xs font-black transition flex items-center gap-2 border cursor-pointer whitespace-nowrap {{ in_array($activeRole, ['faculty', 'teacher']) ? 'bg-[#8b1818] text-white border-[#8b1818] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            <i class="fa-solid fa-chalkboard-user"></i>
            <span>Faculty</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ in_array($activeRole, ['faculty', 'teacher']) ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-900' }}">
                {{ $facultyCount ?? 0 }}
            </span>
        </a>

        <a href="{{ route('admin.users.index', ['role' => 'student', 'search' => $search ?? '']) }}" 
           class="px-4 py-2.5 rounded-2xl text-xs font-black transition flex items-center gap-2 border cursor-pointer whitespace-nowrap {{ in_array($activeRole, ['student', 'students']) ? 'bg-[#8b1818] text-white border-[#8b1818] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Students</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ in_array($activeRole, ['student', 'students']) ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-900' }}">
                {{ $studentCount ?? 0 }}
            </span>
        </a>
    </div>

    <!-- Single Main Container -->
    <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs overflow-hidden">
        
        <!-- Search Bar Header inside the container -->
        <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4 bg-slate-50/50">
            <span class="px-3 py-1.5 bg-slate-100 text-slate-800 text-xs font-black rounded-xl border border-slate-200 uppercase tracking-wide">
                Showing {{ ucfirst($activeRole) }} Directory ({{ isset($users) && method_exists($users, 'total') ? $users->total() : count($users) }})
            </span>
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full md:w-80 relative">
                @if($roleFilter && $roleFilter !== 'all')
                    <input type="hidden" name="role" value="{{ $roleFilter }}">
                @endif
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-search text-xs"></i>
                </span>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search name, ID, or email..." 
                       class="w-full pl-9 pr-4 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] font-semibold text-slate-800">
            </form>
        </div>

        <!-- Accounts Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-black text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">#</th>
                        <th class="py-3.5 px-6">Name / Account</th>
                        <th class="py-3.5 px-6">ID Number / Username</th>
                        <th class="py-3.5 px-6">Role & Academic Placement / Schedule</th>
                        <th class="py-3.5 px-6">Email / Contact</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($users ?? [] as $user)
                        <tr class="hover:bg-slate-50/80 transition align-top">
                            <!-- Numbering Column -->
                            <td class="py-4 px-4 text-center font-bold text-slate-400 text-xs whitespace-nowrap">
                                {{ method_exists($users, 'firstItem') && $users->firstItem() ? $users->firstItem() + $loop->index : $loop->iteration }}
                            </td>

                            <!-- Name / Account -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-[#8b1818] text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <span class="text-slate-900 font-black block">{{ $user->last_name }}, {{ $user->first_name }}</span>
                                        @if(!empty($user->nfcCard?->tag_id))
                                            <span class="text-[10px] font-mono text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                NFC: {{ $user->nfcCard->tag_id }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- ID Number -->
                            <td class="py-4 px-6 font-mono text-slate-600 font-bold">
                                {{ $user->id_number ?? $user->email ?? 'N/A' }}
                            </td>

                            <!-- Role Categorized + Advisory Class + Scheduled Subjects -->
                            <td class="py-4 px-6">
                                @php
                                    $isTeacher = (int)($user->role_id ?? 0) === 2 || in_array(strtolower($user->role ?? ''), ['teacher', 'faculty']);
                                    $isStudent = (int)($user->role_id ?? 0) === 3 || strtolower($user->role ?? '') === 'student';
                                    $isAdmin   = (int)($user->role_id ?? 0) === 1 || strtolower($user->role ?? '') === 'admin';
                                    $isDirector= (int)($user->role_id ?? 0) === 4 || strtolower($user->role ?? '') === 'director';

                                    $roleBadge = match(true) {
                                        $isAdmin => ['bg' => 'bg-amber-100 text-amber-900 border-amber-300', 'label' => 'Administrator'],
                                        $isTeacher => ['bg' => 'bg-blue-100 text-blue-900 border-blue-300', 'label' => 'Faculty / Teacher'],
                                        $isStudent => ['bg' => 'bg-emerald-100 text-emerald-900 border-emerald-300', 'label' => 'Student'],
                                        $isDirector => ['bg' => 'bg-purple-100 text-purple-900 border-purple-300', 'label' => 'Director / Viewer'],
                                        default => ['bg' => 'bg-slate-100 text-slate-800 border-slate-300', 'label' => 'User']
                                    };
                                @endphp

                                <div class="space-y-2">
                                    <!-- Role Badge -->
                                    <div>
                                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black border uppercase {{ $roleBadge['bg'] }}">
                                            {{ $roleBadge['label'] }}
                                        </span>
                                    </div>

                                    <!-- FACULTY SPECIFIC DETAILS: Advisory Class & Scheduled Subjects (Section + Subject Name) -->
                                    @if($isTeacher)
                                        <!-- Advisory Class -->
                                        <div class="pt-1">
                                            @if($user->section || $user->grade_level)
                                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-50 text-amber-950 text-[11px] font-black border border-amber-300">
                                                    <i class="fa-solid fa-crown text-[10px] text-amber-600"></i>
                                                    <span>Adviser: {{ $user->grade_level ? 'Grade ' . $user->grade_level . ' - ' : '' }}{{ $user->section }}</span>
                                                </div>
                                            @else
                                                <span class="text-[10px] text-slate-400 font-bold italic">
                                                    Subject Teacher (No Advisory Class)
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Scheduled Subjects List (Only Section + Subject Name) -->
                                        @php
                                            $facultySchedules = isset($user->classSchedules) ? $user->classSchedules
                                                ->map(function($sched) {
                                                    $subj = trim($sched->subject_name ?? $sched->subject ?? optional($sched->subjectRecord)->name ?? '');
                                                    $sec = trim($sched->section ?? optional($sched->academicSection)->section_name ?? '');
                                                    if (!$subj && !$sec) return null;
                                                    return ($sec ? $sec : 'No Section') . ' - ' . ($subj ? $subj : 'No Subject');
                                                })
                                                ->filter()
                                                ->unique()
                                                ->values() : collect();
                                        @endphp

                                        <div class="pt-1 space-y-1">
                                            <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Scheduled Classes:</span>
                                            @forelse($facultySchedules as $schedText)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200 mr-1 mb-1">
                                                    <i class="fa-solid fa-book-bookmark text-[9px] text-[#8b1818]"></i>
                                                    <span>{{ $schedText }}</span>
                                                </span>
                                            @empty
                                                <span class="text-[10px] text-slate-400 font-medium italic block">No subjects scheduled</span>
                                            @endforelse
                                        </div>
                                    @endif

                                    <!-- STUDENT SPECIFIC DETAILS: Grade Level, Strand, Section -->
                                    @if($isStudent && ($user->grade_level || $user->section))
                                        <div class="text-[11px] text-slate-700 font-semibold flex items-center gap-2 flex-wrap pt-0.5">
                                            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-800 font-bold border border-slate-200">
                                                Grade {{ $user->grade_level }}
                                            </span>
                                            @if($user->strand)
                                                <span class="px-2 py-0.5 bg-red-50 text-[#8b1818] rounded font-black border border-red-200">
                                                    {{ $user->strand }}
                                                </span>
                                            @endif
                                            @if($user->section)
                                                <span class="text-slate-600 font-bold">
                                                    Section: {{ $user->section }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Email / Contact -->
                            <td class="py-4 px-6 text-slate-600">
                                <div>{{ $user->email ?? 'No email provided' }}</div>
                                @if(!empty($user->phone_number))
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $user->phone_number }}</div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center justify-center transition" title="Edit User">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>

                                    <!-- Remove / Delete Button (Huwag ipakita kung sarili mong account) -->
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this user account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 inline-flex items-center justify-center transition" title="Remove User">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                                <i class="fa-solid fa-users-slash text-2xl mb-2 block text-slate-300"></i>
                                No institutional accounts found matching filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(isset($users) && method_exists($users, 'links'))
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif

    </div>
</div>
@endsection