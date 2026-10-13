@extends('layouts.app')

@section('title', 'System Configuration - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50" 
     x-data="{ 
         showAddSchoolYear: false, 
         showAllSchoolYears: false, 
         showEditDates: false, 
         showSmsTemplates: false,
         smsTesting: false, 
         smsResult: null,
         securityModal: false,
         actionType: '',
         adminPassword: '',
         passwordError: false,
         showModalPassword: false,
         isSubmitting: false,
         actionTitle: '',
         actionDesc: '',
         targetFormId: '',

         requestPassword(formId, title, desc) {
             this.targetFormId = formId;
             this.actionTitle = title;
             this.actionDesc = desc;
             this.adminPassword = '';
             this.passwordError = false;
             this.showModalPassword = false;
             this.securityModal = true;
             setTimeout(() => {
                 let el = document.getElementById('settingsSecPasswordInput');
                 if (el) el.focus();
             }, 50);
         },

         submitAuthorizedAction() {
             if (!this.adminPassword.trim()) {
                 this.passwordError = true;
                 return;
             }
             this.isSubmitting = true;
             const form = document.getElementById(this.targetFormId);
             if (form) {
                 const pwInput = form.querySelector('input[name=\'admin_password\']');
                 if (pwInput) pwInput.value = this.adminPassword;
                 form.submit();
             } else {
                 this.isSubmitting = false;
                 this.securityModal = false;
             }
         }
     }">
    
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">System Configuration</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Manage your administrator account, school year, messaging gateway, and NFC bridge.</p>
            </div>
        </div>
    </header>

    <main class="flex-1 p-6 lg:px-10 lg:py-8 space-y-8 max-w-[1600px] mx-auto w-full">
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="space-y-6 w-full">

        <!-- 1. School Year -->
        <section class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-black text-slate-900">School Year</h3>
                    <p class="text-xs text-slate-500 font-semibold mt-1">Current active school year and date range.</p>
                </div>
            </div>

            @if($activePeriod)
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/70 p-5">
                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider text-amber-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Active School Year
                    </div>
                    <p class="mt-2 text-2xl font-black text-slate-900">S.Y. {{ $activePeriod->school_year }}</p>
                    <p class="mt-2 text-xs font-bold text-slate-600">
                        {{ $activePeriod->start_date ? \Carbon\Carbon::parse($activePeriod->start_date)->format('M d, Y') : 'Start date not set' }}
                        <span class="mx-1 text-slate-400">-</span>
                        {{ $activePeriod->end_date ? \Carbon\Carbon::parse($activePeriod->end_date)->format('M d, Y') : 'End date not set' }}
                    </p>
                    <button type="button" @click="showEditDates = true" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white border border-amber-200 px-3 py-2 text-[11px] font-black text-amber-800 hover:bg-amber-100 transition">
                        <i class="fa-solid fa-pen-to-square"></i> Update Dates
                    </button>
                </div>
            @else
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-base font-black text-slate-800">No Active School Year</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Add a school year to begin configuring the academic calendar.</p>
                </div>
            @endif

            <div class="mt-5 grid grid-cols-2 gap-3">
                <button type="button" @click="showAllSchoolYears = true" class="px-3 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition">
                    <i class="fa-solid fa-list mr-1"></i> See All School Years
                </button>
                <button type="button" @click="showAddSchoolYear = true" class="px-3 py-3 rounded-xl bg-[#590d0d] hover:bg-[#701010] text-white text-xs font-black transition shadow-md">
                    <i class="fa-solid fa-plus mr-1 text-amber-300"></i> Add School Year
                </button>
            </div>
        </section>

        <!-- SMS API -->
        <div class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs">
            <div class="flex items-start gap-4 mb-5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">SMS API</h3>
                    <p class="text-xs text-slate-500 font-semibold mt-1">Configure the Android SMS Gateway API used for notifications.</p>
                </div>
            </div>

            <form id="smsSettingsForm" method="POST" action="{{ route('admin.settings.sms.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="admin_password" value="">
                <div>
                    <label for="sms_url" class="block text-xs font-black text-slate-700 mb-1.5">Gateway URL</label>
                    <input id="sms_url" name="url" type="url" required value="{{ old('url', $smsConfig['url']) }}"
                           class="w-full px-4 py-3 bg-slate-50/70 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>
                <div>
                    <label for="sms_login" class="block text-xs font-black text-slate-700 mb-1.5">Gateway Login / Username</label>
                    <input id="sms_login" name="login" type="text" required value="{{ old('login', $smsConfig['login']) }}"
                           class="w-full px-4 py-3 bg-slate-50/70 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>
                <div>
                    <label for="sms_password" class="block text-xs font-black text-slate-700 mb-1.5">Gateway Password</label>
                    <input id="sms_password" name="password" type="password" autocomplete="new-password" placeholder="{{ $smsPasswordConfigured ? 'Leave blank to keep the current password' : 'Enter gateway password' }}"
                           class="w-full px-4 py-3 bg-slate-50/70 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="validate_numbers" value="1" {{ old('validate_numbers', $smsConfig['validate_numbers']) ? 'checked' : '' }}> Validate Numbers</label>
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="enable_rate_limiting" value="1" {{ old('enable_rate_limiting', $smsConfig['enable_rate_limiting']) ? 'checked' : '' }}> Rate Limiting</label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label for="sms_timeout" class="block text-xs font-black text-slate-700 mb-1.5">Request Timeout</label><input id="sms_timeout" name="timeout" type="number" min="1" max="300" required value="{{ old('timeout', $smsConfig['timeout']) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></div>
                    <div><label for="sms_delay" class="block text-xs font-black text-slate-700 mb-1.5">Message Delay (sec)</label><input id="sms_delay" name="message_delay_seconds" type="number" min="0" required value="{{ old('message_delay_seconds', $smsConfig['message_delay_seconds']) }}" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></div>
                </div>
                <div class="flex items-center justify-between gap-3 pt-2">
                    <span class="text-[11px] font-bold" :class="smsResult?.success ? 'text-emerald-600' : 'text-slate-500'"><i class="fa-solid fa-clock mr-1"></i><span x-text="smsResult ? (smsResult.message + (smsResult.response_time_ms ? ' ' + smsResult.response_time_ms + ' ms' : '')) : '{{ $smsLastPing ? 'Last ping: ' . $smsLastPing : 'Last ping: Not tested' }}'"></span></span>
                    <div class="flex gap-2 flex-wrap justify-end">
                        <button type="button" @click="showSmsTemplates = true" class="px-3 py-2.5 bg-amber-100 text-amber-900 text-xs font-black rounded-xl border border-amber-300 cursor-pointer">
                            <i class="fa-solid fa-pen-to-square mr-1"></i>Edit SMS Templates
                        </button>
                        <button type="button" @click="smsTesting = true; smsResult = null; fetch('{{ route('admin.settings.sms.test') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}}).then(r => r.json()).then(data => smsResult = data).catch(() => smsResult = {success:false, message:'Unable to test SMS API.'}).finally(() => smsTesting = false)" :disabled="smsTesting" class="px-3 py-2.5 bg-slate-100 text-slate-700 text-xs font-black rounded-xl cursor-pointer"><i class="fa-solid fa-plug mr-1"></i><span x-text="smsTesting ? 'Testing...' : 'Test Connection'"></span></button>
                        <button type="button" @click="requestPassword('smsSettingsForm', 'Confirm SMS API Configuration', 'Updating SMS gateway credentials requires admin password verification.')" class="px-4 py-2.5 bg-[#590d0d] hover:bg-red-950 text-white text-xs font-black rounded-xl shadow-md transition cursor-pointer flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-amber-300"></i>Save Configuration</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SMS Template Editor -->
        <div x-show="showSmsTemplates" x-transition class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/70 p-4" x-cloak>
            <form method="POST" action="{{ route('admin.settings.sms-templates.update') }}" class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden">
                @csrf
                <div class="bg-[#590d0d] border-b-4 border-amber-400 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-white">Edit SMS Templates</h3>
                        <p class="text-[11px] text-amber-100 mt-1">Used by the automatic post-subject attendance monitor.</p>
                    </div>
                    <button type="button" @click="showSmsTemplates = false" class="text-white/70 hover:text-white cursor-pointer" aria-label="Close template editor">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="subject_absence_template" class="block text-xs font-black text-slate-700 mb-1.5">Subject Schedule Absence</label>
                        <textarea id="subject_absence_template" name="subject_absence_template" rows="4" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-800 focus:border-[#590d0d] focus:outline-none">{{ old('subject_absence_template', $smsTemplates['subject_absence_template']) }}</textarea>
                        <p class="mt-1.5 text-[11px] text-slate-500">Placeholders: <code>{student_name}</code>, <code>{subject_name}</code>, <code>{date}</code>, <code>{start_time}</code>, <code>{end_time}</code>, <code>{section_name}</code></p>
                    </div>
                    <div>
                        <label for="classroom_absence_template" class="block text-xs font-black text-slate-700 mb-1.5">Classroom/Section Absence</label>
                        <textarea id="classroom_absence_template" name="classroom_absence_template" rows="4" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-800 focus:border-[#590d0d] focus:outline-none">{{ old('classroom_absence_template', $smsTemplates['classroom_absence_template']) }}</textarea>
                        <p class="mt-1.5 text-[11px] text-slate-500">Placeholders: <code>{section_name}</code>, <code>{absent_students}</code>, <code>{subject_name}</code>, <code>{date}</code></p>
                    </div>
                </div>
                <div class="px-6 pb-6 flex justify-end gap-2">
                    <button type="button" @click="showSmsTemplates = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-black cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black cursor-pointer"><i class="fa-solid fa-floppy-disk mr-1"></i>Save Templates</button>
                </div>
            </form>
        </div>

        <!-- Administrator Account -->
        <section class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#8b1818] flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Administrator Account</h3>
                    <p class="text-xs text-slate-500 font-semibold mt-1">Update your name, email address, phone number, or password.</p>
                </div>
            </div>

            @if(session('profile_success'))
                <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl">
                    {{ session('profile_success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5" x-data="{ newPassword: '' }">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="admin_first_name" class="block text-xs font-black text-slate-700 mb-1.5">First Name <span class="text-red-600">*</span></label>
                        <input id="admin_first_name" name="first_name" type="text" required value="{{ old('first_name', $adminUser->first_name) }}"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                    <div>
                        <label for="admin_last_name" class="block text-xs font-black text-slate-700 mb-1.5">Last Name <span class="text-red-600">*</span></label>
                        <input id="admin_last_name" name="last_name" type="text" required value="{{ old('last_name', $adminUser->last_name) }}"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                    <div>
                        <label for="admin_email" class="block text-xs font-black text-slate-700 mb-1.5">Email <span class="text-red-600">*</span></label>
                        <input id="admin_email" name="email" type="email" required value="{{ old('email', $adminUser->email) }}"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                    <div>
                        <label for="admin_phone" class="block text-xs font-black text-slate-700 mb-1.5">Phone Number</label>
                        <input id="admin_phone" name="phone_number" type="text" value="{{ old('phone_number', $adminUser->phone_number ?? $adminUser->contact_number ?? '') }}"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                    <div>
                        <label for="admin_password" class="block text-xs font-black text-slate-700 mb-1.5">New Password</label>
                        <input id="admin_password" name="password" type="password" x-model="newPassword" autocomplete="new-password" placeholder="Leave blank to keep the current password"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                    <div>
                        <label for="admin_password_confirmation" class="block text-xs font-black text-slate-700 mb-1.5">Confirm New Password</label>
                        <input id="admin_password_confirmation" name="password_confirmation" type="password" :required="newPassword.length > 0" :disabled="newPassword.length === 0" autocomplete="new-password" placeholder="Enter only when changing password"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#590d0d] hover:bg-[#701010] text-white text-xs font-black transition shadow-md">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i> Save Account
                    </button>
                </div>
            </form>
        </section>

        <!-- NFC Bridge Package -->
        <section class="p-6 bg-white rounded-3xl border-2 border-slate-200 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 border-2 border-slate-700 text-amber-300 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">NFC Bridge Package</h3>
                        <p class="text-xs text-slate-500 font-semibold mt-1">
                            Download one complete package for the Windows computer connected to the ACR122U reader.
                        </p>
                        <p class="text-[11px] text-slate-400 font-semibold mt-2">
                            Includes the Python bridge, bundled runtime, ACR122U driver archive, and starter.
                        </p>
                    </div>
                </div>
                <a href="{{ route('admin.nfc.bridge.package') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#590d0d] hover:bg-[#701010] text-white text-xs font-black transition shadow-md shrink-0">
                    <i class="fa-solid fa-file-zipper text-amber-300"></i>
                    Download NFC Package
                </a>
            </div>
        </section>

    </div>

    <!-- Update Active School Year Dates Modal -->
    @if($activePeriod)
        <div x-show="showEditDates" x-transition class="fixed inset-0 flex items-center justify-center bg-slate-950/70 p-4" style="z-index: 9999; display: none;" x-cloak>
            <form id="updateDatesForm" method="POST" action="{{ route('admin.settings.school-year.dates.update') }}" class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl space-y-6">
                @csrf
                <input type="hidden" name="academic_period_id" value="{{ $activePeriod->id }}">
                <input type="hidden" name="admin_password" value="">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Update School Year Dates</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1">S.Y. {{ $activePeriod->school_year }}</p>
                    </div>
                    <button type="button" @click="showEditDates = false" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-2">Start Date</label>
                        <input name="start_date" type="date" required value="{{ $activePeriod->start_date }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-[#8b1818] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-2">End Date</label>
                        <input name="end_date" type="date" required value="{{ $activePeriod->end_date }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-[#8b1818] focus:outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showEditDates = false" class="px-5 py-3 rounded-xl bg-slate-100 text-slate-700 text-xs font-black cursor-pointer">Cancel</button>
                    <button type="button" @click="showEditDates = false; requestPassword('updateDatesForm', 'Confirm Update School Year Dates', 'Modifying active school year date range requires admin password verification.')" class="px-5 py-3 rounded-xl bg-[#590d0d] text-white text-xs font-black cursor-pointer flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-amber-300"></i>Update Dates</button>
                </div>
            </form>
        </div>
    @endif

    <!-- Add School Year Modal -->
    <div x-show="showAddSchoolYear" x-transition class="fixed inset-0 flex items-center justify-center bg-slate-950/70 p-4" style="z-index: 9999; display: none;" x-cloak>
        <form id="addSchoolYearForm" method="POST" action="{{ route('admin.school-year.periods.store') }}" class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl space-y-6">
            @csrf
            <input type="hidden" name="admin_password" value="">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Add School Year</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1">The school-year label is generated from the selected dates.</p>
                </div>
                <button type="button" @click="showAddSchoolYear = false" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-2">Start Date</label>
                    <input name="start_date" type="date" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-[#8b1818] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-2">End Date</label>
                    <input name="end_date" type="date" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-[#8b1818] focus:outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" @click="showAddSchoolYear = false" class="px-5 py-3 rounded-xl bg-slate-100 text-slate-700 text-xs font-black cursor-pointer">Cancel</button>
                <button type="button" @click="showAddSchoolYear = false; requestPassword('addSchoolYearForm', 'Confirm Add School Year', 'Adding a new academic school year requires admin password verification.')" class="px-5 py-3 rounded-xl bg-[#590d0d] text-white text-xs font-black cursor-pointer flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-amber-300"></i>Add School Year</button>
            </div>
        </form>
    </div>

    <!-- All School Years Modal -->
    <div x-show="showAllSchoolYears" x-transition class="fixed inset-0 flex items-center justify-center bg-slate-950/70 p-4" style="z-index: 9999; display: none;" x-cloak>
        <div @click.outside="showAllSchoolYears = false" class="bg-white rounded-3xl p-8 max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900">All School Years</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1">Select a year from the list to review its date range.</p>
                </div>
                <button type="button" @click="showAllSchoolYears = false" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="space-y-3">
                @forelse($schoolYearGroups as $schoolYear => $yearPeriods)
                    @php
                        $period = $yearPeriods->first(fn ($item) => filled($item->start_date) && filled($item->end_date)) ?? $yearPeriods->first();
                        $isActiveYear = $yearPeriods->contains(fn ($item) => (bool) $item->is_active);
                    @endphp
                    <div class="flex items-center justify-between gap-4 rounded-2xl border {{ $isActiveYear ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-slate-50/60' }} p-4">
                        <div>
                            <p class="text-sm font-black text-slate-900">S.Y. {{ $schoolYear }}</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1">
                                {{ $period->start_date ? \Carbon\Carbon::parse($period->start_date)->format('M d, Y') : 'Start date not set' }}
                                <span class="mx-1">-</span>
                                {{ $period->end_date ? \Carbon\Carbon::parse($period->end_date)->format('M d, Y') : 'End date not set' }}
                            </p>
                        </div>
                        @if($isActiveYear)
                            <span class="text-[10px] font-black uppercase text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i>Active</span>
                        @endif
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-5 text-center text-xs font-bold text-slate-500">No school years added.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Security Confirmation Modal -->
    <div x-show="securityModal" x-transition class="fixed inset-0 flex items-center justify-center bg-slate-950/70 p-4" style="z-index: 99999; display: none;" x-cloak>
        <form @submit.prevent="submitAuthorizedAction()" @click.outside="securityModal = false" class="bg-white rounded-3xl border border-slate-100 p-8 max-w-md w-full shadow-2xl space-y-6 text-center relative animate-in fade-in zoom-in-95">
            <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-sm bg-amber-50 border border-amber-200 text-amber-600">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div class="space-y-2">
                <h3 class="text-base font-black text-slate-900 tracking-tight" x-text="actionTitle || 'Admin Password Verification'"></h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed" x-text="actionDesc || 'Please enter your administrator password to authorize this action.'"></p>
            </div>

            <div class="space-y-2 text-left">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Admin Password</label>
                <div class="relative">
                    <input :type="showModalPassword ? 'text' : 'password'" id="settingsSecPasswordInput" x-model="adminPassword" @input="passwordError = false" @keydown.escape="securityModal = false" placeholder="Enter your current password..." 
                           autocomplete="current-password"
                           class="w-full bg-slate-50 border-2 rounded-2xl pl-4 pr-12 py-3.5 text-xs font-bold text-slate-900 focus:outline-none focus:bg-white transition"
                           :class="passwordError ? 'border-red-500 bg-red-50/30' : 'border-slate-200 focus:border-[#8b1818]'">
                    <button type="button" @click="showModalPassword = !showModalPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fa-solid text-sm" :class="showModalPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p x-show="passwordError" class="text-red-600 text-xs font-bold mt-1" style="display: none;" x-cloak>Please enter your password to proceed.</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <button @click="securityModal = false" type="button" :disabled="isSubmitting" class="w-full py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase tracking-wider cursor-pointer transition">
                    Cancel
                </button>
                <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 rounded-2xl text-white text-xs font-black uppercase tracking-wider shadow-lg transition cursor-pointer flex items-center justify-center gap-2 bg-[#8b1818] hover:bg-[#731414] shadow-red-950/20">
                    <span x-show="!isSubmitting">Confirm</span>
                    <span x-show="isSubmitting" style="display: none;" class="flex items-center gap-1.5">
                        <i class="fa-solid fa-spinner animate-spin text-xs"></i> Verifying...
                    </span>
                </button>
            </div>
        </form>
    </div>

    </main>
</div>
@endsection