@extends('layouts.app')

@section('title', 'School Year Management - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-slate-100 to-zinc-100 relative"
     x-data="{ 
         securityModal: {{ session('error') && str_contains(strtolower(session('error')), 'password') ? 'true' : 'false' }},
         actionType: 'update',
         adminPassword: '',
         passwordError: false,
         serverPasswordError: '{{ session('error') && str_contains(strtolower(session('error')), 'password') ? session('error') : '' }}',
         showModalPassword: false,
         showAddPeriod: false,
         showSchoolYears: false,
         startDate: '',
         endDate: '',
         isSubmitting: false,
         formatError: false,
         successModal: {{ session('success') ? 'true' : 'false' }},
         errorModal: {{ session('error') && !str_contains(strtolower(session('error')), 'password') ? 'true' : 'false' }},
         academicYear: @js($activeSchoolYear),

         updateSchoolYearPreview() {
             if (!this.startDate || !this.endDate) return '';
             const startYear = this.startDate.substring(0, 4);
             const endYear = this.endDate.substring(0, 4);
             return `${startYear}-${endYear}`;
         },

         validateFormat() {
             const regex = /^\d{4}-\d{4}$/;
             return regex.test(this.academicYear);
         },

         triggerSecurity(type) {
             if (!this.validateFormat()) {
                 this.formatError = true;
                 return;
             }
             this.formatError = false;
             this.actionType = type;
             this.adminPassword = '';
             this.passwordError = false;
             this.serverPasswordError = '';
             this.showModalPassword = false;
             this.securityModal = true;
             setTimeout(() => {
                 let input = document.getElementById('modalSecPasswordInput');
                 if(input) input.focus();
             }, 50);
         },

         submitAction() {
             if (!this.adminPassword.trim()) {
                 this.passwordError = true;
                 this.serverPasswordError = '';
                 return;
             }
             this.isSubmitting = true;
             this.passwordError = false;

             if (this.actionType === 'update') {
                 let passwordInput = document.getElementById('update_admin_password');
                 if (passwordInput) {
                     passwordInput.value = this.adminPassword;
                 }

                 let mainForm = document.getElementById('syUpdateForm');
                 if (mainForm) {
                     mainForm.submit();
                 } else {
                     this.isSubmitting = false;
                     alert('Form not found.');
                 }
             }
         }
     }">

    <!-- Top Executive Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-8 lg:px-16 py-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#8b1818] to-rose-700 text-white flex items-center justify-center text-xl shadow-lg shadow-red-950/25 shrink-0">
                <i class="fa-solid fa-calendar-check text-amber-300"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">School Year Configuration</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Manage the active institutional school year for SIATRACK</p>
            </div>
        </div>

        <!-- Live Status Pill -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" @click="showSchoolYears = true" class="inline-flex items-center gap-2 px-4 py-3 bg-slate-100 border border-slate-200 rounded-2xl text-xs font-black text-slate-700 hover:bg-slate-200 transition">
                <i class="fa-solid fa-list"></i> All School Years
            </button>
            <div class="flex items-center gap-2.5 px-5 py-3 bg-amber-50 border border-amber-200/80 rounded-2xl shadow-2xs">
                <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                <span class="text-xs font-black text-amber-900 uppercase tracking-wide">
                    Active: A.Y. {{ $activeSchoolYear }}
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="p-8 lg:p-16 w-full max-w-6xl mx-auto space-y-10 flex-1 flex flex-col justify-center">
        
        <!-- Professional Active Period Management Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-10 lg:p-14 shadow-2xl shadow-slate-200/60 space-y-10 w-full">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-red-50 text-[#8b1818] border border-red-100 flex items-center justify-center text-2xl font-black shrink-0 shadow-xs">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Active School Year</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Select the school year used by current records while preserving archives.</p>
                    </div>
                </div>
                <button type="button" @click="showAddPeriod = true" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-black hover:bg-amber-100 transition">
                    <i class="fa-solid fa-plus"></i>
                    Add School Year
                </button>
            </div>

            <form id="syUpdateForm" action="{{ route('admin.school-year.update') }}" method="POST" class="space-y-8">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- School Year Dropdown -->
                    <div class="space-y-3">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-600">School Year</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                                <i class="fa-solid fa-calendar-days text-sm"></i>
                            </span>
                            <select id="sy_input" name="academic_year" x-model="academicYear" @change="formatError = false" required
                                    class="w-full appearance-none bg-slate-50/80 border-2 border-slate-200/90 rounded-2xl pl-12 pr-10 py-4 text-base font-black text-slate-900 focus:outline-none focus:bg-white focus:border-[#8b1818] transition shadow-2xs">
                                @foreach($schoolYears as $schoolYear)
                                    <option value="{{ $schoolYear }}">{{ $schoolYear }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400"><i class="fa-solid fa-chevron-down text-xs"></i></span>
                        </div>
                        <p class="text-xs text-slate-400 font-semibold pl-1 mt-1">Select an existing academic year.</p>
                    </div>
                </div>

                <input type="hidden" name="admin_password" id="update_admin_password">

                <div class="pt-6">
                    <button type="button" @click="triggerSecurity('update')" 
                            class="w-full py-5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black uppercase tracking-widest transition shadow-xl shadow-red-950/25 cursor-pointer flex items-center justify-center gap-3">
                        <i class="fa-solid fa-shield-halved text-amber-300 text-base"></i>
                        <span>Save & Authorize Active Period Changes</span>
                    </button>
                </div>
            </form>
        </div>

    </main>

    <!-- ================= FULL-SCREEN MODALS ================= -->

    <!-- Add School Year Modal -->
    <div x-show="showAddPeriod" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <form method="POST" action="{{ route('admin.school-year.periods.store') }}" class="bg-white rounded-3xl border border-slate-100 p-8 max-w-lg w-full shadow-2xl space-y-6">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Add School Year</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1">Select the date range. The school-year label will be generated automatically.</p>
                </div>
                <button type="button" @click="showAddPeriod = false" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-2">Start Date</label>
                    <input name="start_date" type="date" required x-model="startDate" @change="updateSchoolYearPreview()" value="{{ old('start_date') }}"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800 focus:border-[#8b1818] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-2">End Date</label>
                    <input name="end_date" type="date" required x-model="endDate" @change="updateSchoolYearPreview()" value="{{ old('end_date') }}"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800 focus:border-[#8b1818] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-2">School Year</label>
                    <input type="text" readonly :value="updateSchoolYearPreview() || 'Select dates first'"
                           class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-black text-amber-900 focus:outline-none">
                    <p class="text-[11px] text-slate-400 font-semibold mt-1">Automatically generated from the selected dates.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" @click="showAddPeriod = false" class="px-5 py-3 rounded-2xl bg-slate-100 text-slate-700 text-xs font-black">Cancel</button>
                <button type="submit" class="px-5 py-3 rounded-2xl bg-[#8b1818] text-white text-xs font-black shadow-lg"><i class="fa-solid fa-plus mr-2 text-amber-300"></i>Add School Year</button>
            </div>
        </form>
    </div>

    <!-- All School Years Modal -->
    <div x-show="showSchoolYears" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div @click.outside="showSchoolYears = false" class="bg-white rounded-3xl border border-slate-100 p-8 max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900">All School Years</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1">Review every school year and its configured date range.</p>
                </div>
                <button type="button" @click="showSchoolYears = false" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="space-y-3">
                @forelse($schoolYearGroups as $schoolYear => $yearPeriods)
                    <div class="rounded-2xl border {{ $schoolYear === $activeSchoolYear ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-slate-50/60' }} p-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <span class="text-sm font-black text-slate-900">A.Y. {{ $schoolYear }}</span>
                            @if($schoolYear === $activeSchoolYear)
                                <span class="text-[10px] font-black uppercase tracking-wide text-amber-800 bg-amber-100 border border-amber-200 rounded-full px-2.5 py-1">Current Year</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @php($period = $yearPeriods->first())
                                <div class="flex items-center justify-between gap-3 rounded-xl bg-white border border-slate-200 px-3 py-2">
                                    <div>
                                        <p class="text-[10px] font-semibold text-slate-400">
                                            {{ $period->start_date ? \Carbon\Carbon::parse($period->start_date)->format('M d, Y') : 'No start date' }}
                                            -
                                            {{ $period->end_date ? \Carbon\Carbon::parse($period->end_date)->format('M d, Y') : 'No end date' }}
                                        </p>
                                    </div>
                                    @if($period->is_active)
                                        <i class="fa-solid fa-circle-check text-emerald-500 text-sm" title="Active period"></i>
                                    @endif
                                </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-5 text-center text-xs font-bold text-slate-500">No school years have been configured yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- General Error Modal Popup -->
    <div x-show="errorModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div @click.outside="errorModal = false" class="bg-white border border-slate-100 shadow-2xl rounded-3xl p-10 max-w-md w-full text-center space-y-6 animate-in fade-in zoom-in-95">
            <div class="w-20 h-20 rounded-2xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center text-3xl mx-auto shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="space-y-2">
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Action Failed</h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed">
                    {{ session('error') }}
                </p>
            </div>
            <button @click="errorModal = false" type="button" class="w-full py-4 rounded-2xl text-white text-xs font-black uppercase tracking-wider transition shadow-lg cursor-pointer bg-slate-900 hover:bg-slate-800 shadow-slate-900/20">
                Try Again
            </button>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="successModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div @click.outside="successModal = false" class="bg-white border border-slate-100 shadow-2xl rounded-3xl p-10 max-w-md w-full text-center space-y-6 animate-in fade-in zoom-in-95">
            <div class="w-20 h-20 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="space-y-2">
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Successfully Updated!</h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed">
                    {{ session('success') }}
                </p>
            </div>
            <button @click="successModal = false" type="button" class="w-full py-4 rounded-2xl text-white text-xs font-black uppercase tracking-wider transition shadow-lg cursor-pointer bg-slate-900 hover:bg-slate-800 shadow-slate-900/20">
                Got It
            </button>
        </div>
    </div>

    <!-- Security Confirmation Modal (May Inline Password Error sa Baba) -->
    <div x-show="securityModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <form @submit.prevent="submitAction()" @click.outside="securityModal = false" class="bg-white rounded-3xl border border-slate-100 p-10 max-w-lg w-full shadow-2xl space-y-8 text-center relative animate-in fade-in zoom-in-95">
            
            <div class="w-20 h-20 rounded-2xl mx-auto flex items-center justify-center text-3xl shadow-sm bg-amber-50 border border-amber-200 text-amber-600">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div class="space-y-3">
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Confirm Academic Period Change</h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed">
                    You are about to change the active school year. Dashboards and reports will adjust while preserving past records as history.
                </p>
            </div>

            <div class="space-y-3 text-left">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Admin Password Verification</label>
                <div class="relative">
                    <input :type="showModalPassword ? 'text' : 'password'" id="modalSecPasswordInput" x-model="adminPassword" @input="passwordError = false; serverPasswordError = ''" @keydown.escape="securityModal = false" placeholder="Enter your current password..." 
                           autocomplete="current-password"
                           class="w-full bg-slate-50 border-2 rounded-2xl pl-4 pr-12 py-4 text-xs font-bold text-slate-900 focus:outline-none focus:bg-white transition"
                           :class="(passwordError || serverPasswordError) ? 'border-red-500 bg-red-50/30' : 'border-slate-200 focus:border-[#8b1818]'">
                    <button type="button" @click="showModalPassword = !showModalPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fa-solid text-sm" :class="showModalPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <!-- Inline Error Message sa Baba ng Password Input -->
                <p x-show="passwordError" class="text-red-600 text-xs font-bold mt-1" style="display: none;" x-cloak>Please enter your password to proceed.</p>
                <p x-show="serverPasswordError" class="text-red-600 text-xs font-bold mt-1" x-text="serverPasswordError" style="display: none;" x-cloak></p>
            </div>

            <div class="border border-slate-100 p-4 rounded-2xl bg-slate-50 text-left text-xs font-bold text-slate-700 flex items-center justify-between">
                <span>Target Period:</span>
                <span class="text-[#8b1818] font-black text-sm" x-text="'A.Y. ' + academicYear"></span>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <button @click="securityModal = false" type="button" :disabled="isSubmitting" class="w-full py-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase tracking-wider cursor-pointer transition">
                    Cancel
                </button>
                <button type="submit" :disabled="isSubmitting" class="w-full py-4 rounded-2xl text-white text-xs font-black uppercase tracking-wider shadow-lg transition cursor-pointer flex items-center justify-center gap-2 bg-[#8b1818] hover:bg-[#731414] shadow-red-950/20">
                    <span x-show="!isSubmitting">Authorize Change</span>
                    <span x-show="isSubmitting" style="display: none;" class="flex items-center gap-1.5">
                        <i class="fa-solid fa-spinner animate-spin text-xs"></i> Processing...
                    </span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection