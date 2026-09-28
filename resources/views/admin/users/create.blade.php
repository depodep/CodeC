@extends('layouts.app')

@section('title', 'Add New User - SIATRACK')

@section('content')
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <!-- Modal Overlay with Blurred Background -->
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-md flex items-start justify-center p-4 sm:p-6"
        x-data="{ 
            roleId: '{{ old('role_id', '3') }}',
            password: '',
            passwordConfirmation: '',
            showPassword: false,
            teacherType: '{{ old('teacher_type', '') }}',
            firstName: '{{ old('first_name', '') }}',
            lrn: '{{ old('id_number', '') }}',
            username: '{{ old('username', '') }}',
            gradeLevel: '{{ old('grade_level', '') }}',
            section: '{{ old('section', '') }}',
            isSHSGrade() {
                return ['Grade 11', 'Grade 12', '11', '12'].includes(this.gradeLevel);
            },
            usernameAuto: {{ old('username') ? 'false' : 'true' }},
            sections: @js($sections ?? []),
            getFilteredSections() {
                const grade = parseInt(String(this.gradeLevel).replace(/\D/g, ''), 10);
                return this.sections.filter(section => parseInt(String(section.grade_level).replace(/\D/g, ''), 10) === grade);
            },
            defaultPasswords: {
                '3': 'onesia@123',
                '2': 'siafaculty@123',
                '4': 'siamanagement@123'
            },
            init() {
                this.syncDefaultPassword();
            },
            syncDefaultPassword() {
                const defaultPass = this.defaultPasswords[this.roleId] || 'onesia@123';
                this.password = defaultPass;
                this.passwordConfirmation = defaultPass;
            },
            syncStudentUsername() {
                if (this.roleId === '3' && this.usernameAuto) {
                    this.username = (this.lrn.replace(/\D/g, '') + this.firstName.toLowerCase().replace(/[^a-z0-9]/g, '')).slice(0, 100);
                }
            }
         }">

        <div
            class="bg-white rounded-3xl border-2 border-slate-200 max-w-3xl w-full p-8 sm:p-10 space-y-8 shadow-2xl mt-0 mb-8 transform transition-all max-h-[calc(100vh-2rem)] overflow-y-auto no-scrollbar">

            <!-- Header & Close Button -->
            <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-12 h-12 rounded-2xl bg-[#8b1818] text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-red-950/20">
                        <i class="fa-solid fa-user-plus text-amber-300"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Add New User Account</h1>
                        <p class="text-xs font-bold text-slate-500 mt-0.5">Enroll and register a new user into the directory.</p>
                    </div>
                </div>

                <a href="{{ route('admin.users.index') }}"
                    class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </a>
            </div>

            <!-- Validation Errors Banner -->
            @if($errors->any())
                <div
                    class="p-4 bg-red-50 border-2 border-red-300 text-red-800 text-xs font-bold rounded-2xl flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-[#8b1818] text-base mt-0.5 shrink-0"></i>
                    <div class="space-y-1">
                        <span class="font-extrabold block">Please correct the following errors:</span>
                        <ul class="list-disc pl-4 space-y-0.5 text-[11px]">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}" autocomplete="off" class="space-y-8">
                @csrf

                <!-- Account Role Type Selection -->
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-2">Account Role Type</label>
                    <select name="role_id" x-model="roleId" @change="syncDefaultPassword()" required
                        class="w-full px-4 py-3.5 text-sm font-semibold text-slate-800 bg-white border border-slate-300 rounded-2xl focus:border-[#8b1818] outline-none">
                        <option value="3">Student</option>
                        <option value="2">Teacher</option>
                        <option value="4">Management</option>
                    </select>
                </div>
                <div x-show="roleId == '4'" x-cloak class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200">
                    <label class="flex items-center gap-2 text-xs font-black text-amber-900 cursor-pointer">
                        <input type="checkbox" name="is_principal" value="1" class="w-4 h-4 accent-[#8b1818]">
                        Designate as Principal Evaluator
                    </label>
                    <p class="text-[10px] text-amber-700 font-semibold mt-1 ml-6">Only this designated Management account may submit Principal evaluations.</p>
                </div>

                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Personal Information</h3>
                    
                    <!-- ROW 1: Last Name | First Name | Middle Name -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Last Name <span class="text-red-600">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                @input="$el.value = $el.value.toUpperCase()"
                                class="w-full px-4 py-3 text-sm font-semibold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>

                        <div x-show="roleId == '3'" class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Grade Level <span class="text-red-600">*</span></label>
                                <select name="grade_level" x-model="gradeLevel" required class="w-full px-4 py-3 text-sm font-semibold border border-slate-300 rounded-xl bg-white text-slate-700 focus:border-[#8b1818] outline-none">
                                    <option value="" disabled>Select Grade Level</option>
                                    @foreach($gradeLevels ?? [] as $grade)
                                        <option value="{{ $grade }}">{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Strand / Track <span x-show="isSHSGrade()" class="text-red-600">*</span></label>
                                <select name="strand" :disabled="!isSHSGrade()" :required="isSHSGrade()" class="w-full px-4 py-3 text-sm font-semibold border border-slate-300 rounded-xl bg-white text-slate-700 focus:border-[#8b1818] outline-none disabled:bg-slate-100">
                                    <option value="">Select Strand</option>
                                    @foreach($strands ?? [] as $strand)
                                        <option value="{{ $strand }}">{{ $strand }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">First Name <span class="text-red-600">*</span></label>
                            <input type="text" 
                                   name="first_name" 
                                   value="{{ old('first_name') }}"
                                   x-model="firstName"
                                   required
                                   @input="$el.value = $el.value.toUpperCase(); firstName = $el.value; syncStudentUsername()" 
                                   class="w-full px-4 py-3 text-sm font-semibold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Middle Name <span class="text-slate-400 font-semibold">(optional)</span></label>
                            <input type="text" 
                                   name="middle_name" 
                                   value="{{ old('middle_name') }}"
                                   @input="$el.value = $el.value.toUpperCase()" 
                                   class="w-full px-4 py-3 text-sm font-semibold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                    </div>

                    <!-- ROW 2: Student ID, LRN, Username, Gender -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <span x-text="roleId == '3' ? 'Student ID' : 'Employee ID'"></span><span class="text-red-600"> *</span>
                            </label>
                            <input type="text" name="student_id" value="{{ old('student_id') }}" required
                                placeholder="e.g. STU-2026-001"
                                class="w-full px-4 py-3 text-sm font-mono font-semibold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5"
                                   >                                   <span x-text="roleId == '3' ? 'LRN (12-Digit Learner Ref No)' : 'Employee ID / Username'"></span><span class="text-red-600"> *</span></label>
                            <input type="text" name="id_number" value="{{ old('id_number') }}" x-model="lrn" required
                                @input="if(roleId == '3') { $el.value = $el.value.replace(/\D/g, '').slice(0, 12); lrn = $el.value; syncStudentUsername() }"
                                placeholder="e.g. 103063080022"
                                class="w-full px-4 py-3 text-sm font-mono font-semibold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Gender <span class="text-red-600">*</span></label>
                            <select name="gender" required
                                class="w-full px-4 py-3 text-sm font-semibold border border-slate-300 rounded-xl bg-white text-slate-700 focus:border-[#8b1818] outline-none">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male" {{ old('gender') == 'Male' || old('gender') == '1' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' || old('gender') == '2' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="roleId == '3'">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Student Username <span class="text-red-600">*</span></label>
                        <input type="text" name="username" x-model="username" :required="roleId == '3'" @input="usernameAuto = false"
                            placeholder="Defaults to LRN + first name"
                            class="w-full px-4 py-3 text-sm font-mono font-semibold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        <p class="mt-1 text-[11px] font-semibold text-slate-500">Leave blank to generate a unique username from the LRN and first name.</p>
                    </div>

                    <!-- ROW 3: Section | Phone | Email -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Class Section <span x-show="roleId == '3'" class="text-red-600">*</span></label>
                            <select name="section" x-model="section" :required="roleId == '3'"
                                class="w-full px-4 py-3 text-sm font-semibold border border-slate-300 rounded-xl bg-white text-slate-700 focus:border-[#8b1818] outline-none">
                                <option value="">-- Select Section --</option>
                                <template x-for="sectionOption in getFilteredSections()" :key="sectionOption.id">
                                    <option :value="sectionOption.section_name" x-text="'Section ' + sectionOption.section_name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Contact Number <span x-show="roleId == '3'" class="text-red-600">*</span></label>
                            <input type="text" name="phone_number" value="{{ old('phone_number') }}" :required="roleId == '3'" maxlength="11"
                                pattern="09\d{9}" placeholder="09XXXXXXXXX" @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                class="w-full px-4 py-3 text-sm font-mono font-semibold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                        <div x-show="roleId != '3'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address <span class="text-red-600">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" :required="roleId != '3'"
                                class="w-full px-4 py-3 text-sm font-semibold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                        </div>
                    </div>

                    <!-- Guardian / Emergency Contact Information (Students Only) -->
                    <div class="space-y-4 pt-3 border-t border-slate-100" x-show="roleId == '3'">
                        <h4 class="text-xs font-black uppercase tracking-wider text-[#8b1818]">Parent / Guardian / Emergency Contact</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Full Name <span class="text-red-600">*</span></label>
                                <input type="text" name="parent_name" value="{{ old('parent_name') }}" :required="roleId == '3'"
                                    @input="$el.value = $el.value.toUpperCase()" placeholder="e.g. JUAN DELA CRUZ SR."
                                    class="w-full px-4 py-3 text-sm font-semibold uppercase border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Relationship to Student <span class="text-red-600">*</span></label>
                                <select name="parent_relationship" :required="roleId == '3'"
                                    class="w-full px-4 py-3 text-sm font-semibold border border-slate-300 rounded-xl bg-white text-slate-700 focus:border-[#8b1818] outline-none">
                                    <option value="" selected>Select Relationship</option>
                                    <option value="Father" {{ old('parent_relationship') == 'Father' ? 'selected' : '' }}>Father</option>
                                    <option value="Mother" {{ old('parent_relationship') == 'Mother' ? 'selected' : '' }}>Mother</option>
                                    <option value="Guardian" {{ old('parent_relationship') == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                                    <option value="Grandparent" {{ old('parent_relationship') == 'Grandparent' ? 'selected' : '' }}>Grandparent</option>
                                    <option value="Relative" {{ old('parent_relationship') == 'Relative' ? 'selected' : '' }}>Relative</option>
                                    <option value="Other" {{ old('parent_relationship') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Phone Number <span class="text-red-600">*</span></label>
                                <input type="text" name="parent_phone_number" value="{{ old('parent_phone_number') }}" :required="roleId == '3'"
                                    maxlength="11" pattern="09\d{9}" placeholder="09XXXXXXXXX"
                                    @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                    class="w-full px-4 py-3 text-sm font-mono font-semibold border border-slate-300 rounded-xl focus:border-[#8b1818] outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASSWORD SECTION -->
                <div class="space-y-4 pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Account Security Credentials</h3>
                        <button type="button" @click="showPassword = !showPassword"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition cursor-pointer">
                            <i class="fa-solid text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            <span x-text="showPassword ? 'Hide Password' : 'Show Password'"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required
                                minlength="8"
                                class="w-full px-4 py-3 text-sm font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] bg-slate-50/50 focus:bg-white outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm Password</label>
                            <input :type="showPassword ? 'text' : 'password'" name="password_confirmation"
                                x-model="passwordConfirmation" required minlength="8"
                                class="w-full px-4 py-3 text-sm font-mono font-bold border border-slate-300 rounded-xl focus:border-[#8b1818] bg-slate-50/50 focus:bg-white outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- FOOTER ACTIONS -->
                <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.users.index') }}"
                        class="px-8 py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase transition border border-slate-200 flex items-center">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-8 py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-red-950/20 cursor-pointer transition active:scale-[0.98]">
                        Save User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection