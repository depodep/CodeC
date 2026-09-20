<aside x-data="{ collapsed: false }" 
       @toggle-sidebar.window="collapsed = !collapsed"
       :class="collapsed ? 'w-20' : 'w-72'" 
       class="bg-[#590d0d] border-r border-red-950/40 flex flex-col justify-between shrink-0 h-screen fixed top-0 left-0 z-30 shadow-2xl transition-all duration-300">
    
    <!-- Top Accent Trim -->
    <div class="h-1.5 bg-gradient-to-r from-amber-400 via-amber-200 to-amber-400 w-full shrink-0"></div>

    <div class="flex-1 flex flex-col min-h-0">
        <!-- Brand Header & Hamburger Toggle -->
        <div class="p-4 border-b border-red-900/40 flex items-center justify-between bg-black/15 overflow-hidden">
            <div class="flex items-center gap-3 min-w-0" x-show="!collapsed" x-transition>
                <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-xs flex items-center justify-center shrink-0 overflow-hidden">
                    <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-base font-black tracking-tight text-white">SIATRACK</span>
                    </div>
                    <p class="text-[11px] font-bold text-red-200/70 truncate">Southern Isabela Academy</p>
                </div>
            </div>

            <!-- Minimized Logo View -->
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 flex items-center justify-center shrink-0 mx-auto" x-show="collapsed" x-transition>
                <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Logo" class="w-full h-full object-contain">
            </div>

            <!-- Hamburger Toggle Button -->
            <button @click="collapsed = !collapsed; window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: collapsed }))" 
                    class="p-2 rounded-xl bg-red-950/60 hover:bg-red-950 text-amber-300 hover:text-white transition cursor-pointer shrink-0 border border-red-900/60 ml-2"
                    title="Toggle Sidebar">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="p-3 space-y-5 overflow-y-auto flex-1 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-red-950/50 [&::-webkit-scrollbar-thumb]:rounded-full">
            
            {{-- ==================== ADMIN NAVIGATION ==================== --}}
            @if(auth()->user()->role_id == 1 || auth()->user()->role_id == 4)
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Management Menu</p>
                    <div class="space-y-1 text-sm font-bold">
                        <!-- Dashboard -->
                        <a href="{{ route('admin.dashboard') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Dashboard">
                            <i class="fa-solid fa-table-cells-large text-base {{ request()->routeIs('admin.dashboard') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                           <span class="text-[13px]" x-show="!collapsed">Dashboard</span>
                        </a>

                        <!-- User Management -->
                        <div class="space-y-1">
                            <a href="{{ route('admin.users.index') }}" 
                               class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 {{ request()->routeIs('admin.users*') ? 'bg-red-900/80 text-amber-300' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                               title="User Management">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-users text-base {{ request()->routeIs('admin.users*') ? 'text-amber-300' : 'text-amber-300 group-hover:text-white' }}"></i>
                                    <span class="text-[13px]" x-show="!collapsed">User Management</span>
                                </div>
                            </a>
                        </div>

                        <!-- NFC Management -->
                        <a href="{{ route('admin.nfc.binding') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('admin.nfc*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="NFC Management">
                            <i class="fa-solid fa-id-card text-base {{ request()->routeIs('admin.nfc*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">NFC Management</span>
                        </a>

                        <!-- SMS Sent Today -->
                        <a href="{{ route('admin.sms.sent-today') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('admin.sms.sent-today*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="SMS Sent Today">
                            <i class="fa-solid fa-comment-sms text-base {{ request()->routeIs('admin.sms.sent-today*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">SMS Sent Today</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Modules & Setup</p>
                    <div class="space-y-1 text-sm font-bold">
                        
                        <!-- Attendance Monitoring with Sub-items (Fixed highlighting scope) -->
                        <div x-data="{ open: {{ request()->routeIs('admin.attendance.live', 'admin.attendance.override') ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" 
                                class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 {{ request()->routeIs('admin.attendance.live', 'admin.attendance.override') ? 'bg-red-900/80 text-amber-300' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-id-card-clip text-base {{ request()->routeIs('admin.attendance.live', 'admin.attendance.override') ? 'text-amber-300' : 'text-amber-300 group-hover:text-white' }}"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Attendance Monitoring</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="!collapsed"></i>
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="pl-11 pr-2 space-y-1 py-1">
                                <a href="{{ route('admin.attendance.live') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">Live Tap Feed</a>
                                <a href="{{ route('admin.attendance.override') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">Manual Attendance Override</a>
                            </div>
                        </div>

                        <!-- Faculty Evaluation with Sub-items (Fixed highlighting scope) -->
                        <div x-data="{ open: {{ request()->routeIs('admin.evaluations.periods', 'admin.evaluations.results') ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" 
                                class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 {{ request()->routeIs('admin.evaluations.periods', 'admin.evaluations.results') ? 'bg-red-900/80 text-amber-300' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-star-half-stroke text-base {{ request()->routeIs('admin.evaluations.periods', 'admin.evaluations.results') ? 'text-amber-300' : 'text-amber-300 group-hover:text-white' }}"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="!collapsed"></i>
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="pl-11 pr-2 space-y-1 py-1">
                                <a href="{{ route('admin.evaluations.periods') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">Evaluation Periods & Questions</a>
                                <a href="{{ route('admin.evaluations.results') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">Results & Ratings Overview</a>
                            </div>
                        </div>


                        <!-- Reports with Sub-items -->
                        <div x-data="{ open: {{ request()->routeIs('admin.reports*') ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" 
                                    class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 {{ request()->routeIs('admin.reports*') ? 'bg-red-900/80 text-amber-300' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-file-invoice text-base {{ request()->routeIs('admin.reports*') ? 'text-amber-300' : 'text-amber-300 group-hover:text-white' }}"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Reports</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="!collapsed"></i>
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="pl-11 pr-2 space-y-1 py-1">
                                <a href="{{ route('admin.reports.attendance') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">Attendance Logs</a>
                                <a href="{{ route('admin.reports.sf2') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">SF2 (DepEd Compliant)</a>
                                <a href="{{ route('admin.reports.evaluation') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">Faculty Evaluation Summary</a>
                                <a href="{{ route('admin.reports.users') }}" class="block py-1.5 px-3 rounded-lg text-xs font-bold text-red-200/80 hover:text-amber-300 hover:bg-red-900/40 transition">User Master List</a>
                            </div>
                        </div>

                        <!-- System Audit Logs -->
                        <a href="{{ route('admin.audit-logs') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('admin.audit-logs*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="System Audit Logs">
                            <i class="fa-solid fa-shield-halved text-base {{ request()->routeIs('admin.audit-logs*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                           <span class="text-[13px]" x-show="!collapsed">System Audit Logs</span>
                        </a>

                        <!-- Settings (Direct Single Link - No Dropdown) -->
                        <a href="{{ route('admin.settings') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('admin.settings*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Settings">
                            <i class="fa-solid fa-gear text-base {{ request()->routeIs('admin.settings*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Settings</span>
                        </a>

                    </div>
                </div>

           {{-- ==================== TEACHER NAVIGATION ==================== --}}
            @elseif(auth()->user()->role_id === 2)
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Faculty Portal</p>
                    <div class="space-y-1 text-sm font-bold">
                        
                        <!-- Dashboard -->
                        <a href="{{ route('teacher.dashboard') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.dashboard*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Dashboard">
                            <i class="fa-solid fa-gauge text-base {{ request()->routeIs('teacher.dashboard*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Dashboard</span>
                        </a>

                        <!-- School Year and Sections -->
                        <a href="{{ route('teacher.school-years') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.school-years*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="School Year and Sections">
                            <i class="fa-solid fa-layer-group text-base {{ request()->routeIs('teacher.school-years*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">School Year & Sections</span>
                        </a>

                        <!-- Student -->
                        <a href="{{ route('teacher.students') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.students*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Student">
                            <i class="fa-solid fa-user-graduate text-base {{ request()->routeIs('teacher.students*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Student</span>
                        </a>

                        <!-- Message -->
                        <a href="{{ route('teacher.messages') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.messages*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Message">
                            <i class="fa-solid fa-envelope text-base {{ request()->routeIs('teacher.messages*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Message</span>
                        </a>

                        <!-- Attendance -->
                        <a href="{{ route('teacher.attendance') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.attendance*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Attendance">
                            <i class="fa-solid fa-clipboard-user text-base {{ request()->routeIs('teacher.attendance*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Attendance</span>
                        </a>                                        
                        
                        <!-- Class Schedule -->
                        <a href="{{ route('teacher.schedules') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.schedules*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Class Schedule">
                            <i class="fa-solid fa-calendar-days text-base {{ request()->routeIs('teacher.schedules*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Class Schedule</span>
                        </a>

                        <!-- Report -->
                        <a href="{{ route('teacher.reports') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.reports*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Report">
                            <i class="fa-solid fa-chart-pie text-base {{ request()->routeIs('teacher.reports*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Report</span>
                        </a>

                        <!-- Faculty Evaluation -->
                        <a href="{{ route('teacher.evaluations.index') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('teacher.evaluations*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Faculty Evaluation">
                            <i class="fa-solid fa-star-half-stroke text-base {{ request()->routeIs('teacher.evaluations*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                        </a>

                        <!-- Manage Account -->
                        <a href="{{ route('profile.edit') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('profile.edit') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Manage Account">
                            <i class="fa-solid fa-user-gear text-base {{ request()->routeIs('profile.edit') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Manage Account</span>
                        </a>
                    </div>
                </div>
            
            {{-- ==================== STUDENT NAVIGATION ==================== --}}
            @elseif(auth()->user()->role_id === 3)
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Student Portal</p>
                    <div class="space-y-1 text-sm font-bold">
                        <a href="{{ route('student.dashboard') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('student.dashboard*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="My Dashboard">
                            <i class="fa-solid fa-gauge text-base {{ request()->routeIs('student.dashboard*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">My Dashboard</span>
                        </a>
                        <a href="{{ route('student.attendance') }}" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('student.attendance*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200' }}"
                           title="Attendance Records">
                            <i class="fa-solid fa-calendar-check text-base {{ request()->routeIs('student.attendance*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white' }}"></i>
                            <span class="text-[13px]" x-show="!collapsed">Attendance Records</span>
                        </a>
                    </div>
                </div>
            @endif

        </nav>
    </div>

    <!-- Bottom Logout -->
    <div class="p-3 border-t border-red-900/40 bg-black/15">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center justify-center gap-3 py-2.5 px-3 rounded-xl text-xs font-black text-white hover:text-amber-950 hover:bg-amber-400 bg-red-950/50 border border-red-900/50 transition cursor-pointer shadow-xs"
                    title="Sign Out">
                <i class="fa-solid fa-right-from-bracket text-sm text-amber-300 group-hover:text-amber-950"></i>
                <span x-show="!collapsed">Sign Out</span>
            </button>
       </form>
    </div>

</aside>