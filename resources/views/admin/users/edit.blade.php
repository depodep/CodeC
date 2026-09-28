@extends('layouts.app')

@section('content')
<!-- Main Wrapper with Left Margin to Avoid Covering the Sidebar -->
<div class="min-h-screen flex items-center justify-center bg-slate-50/70 p-4 lg:p-8 overflow-y-auto"
     x-data="{
        firstName: @js(old('first_name', $user->first_name)),
        lrn: @js(old('id_number', $user->id_number)),
        username: @js(old('username', $user->username)),
        section: @js(old('section', $user->section)),
        sections: @js($sections ?? []),
        getFilteredSections() {
            const grade = parseInt(String(this.gradeLevel).replace(/\D/g, ''), 10);
            return this.sections.filter(section => parseInt(String(section.grade_level).replace(/\D/g, ''), 10) === grade);
        },
        gradeLevel: @js(old('grade_level', $user->grade_level)),
        isSHSGrade() {
            return ['Grade 11', 'Grade 12', '11', '12'].includes(this.gradeLevel);
        },
        usernameAuto: true,
        syncStudentUsername() {
            if (this.usernameAuto) {
                this.username = (this.lrn.replace(/\D/g, '') + this.firstName.toLowerCase().replace(/[^a-z0-9]/g, '')).slice(0, 100);
            }
        }
     }">
    
    <!-- Modal Card Box -->
    <div class="bg-white rounded-3xl border border-slate-200 p-8 pt-10 shadow-2xl relative w-full max-w-4xl my-8">
        
        <!-- Close / X Button -->
        <a href="{{ route('admin.users.index') }}" class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition shadow-xs z-10">
            <i class="fa-solid fa-xmark"></i>
        </a>

        <!-- Header with Icon -->
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-red-50 border-2 border-red-200 flex items-center justify-center text-[#8b1818] text-xl font-black shadow-xs">
                <i class="fa-solid fa-user-pen"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Edit User Account</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Update user profile details and academic section placement.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 text-xs font-bold rounded-2xl">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Account Role Type Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-900 mb-1.5">Account Role Type</label>
                <select name="role_id" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                    <option value="3" {{ old('role_id', $user->role_id) == 3 ? 'selected' : '' }}>Student</option>
                    <option value="2" {{ old('role_id', $user->role_id) == 2 ? 'selected' : '' }}>Teacher</option>
                    <option value="4" {{ old('role_id', $user->role_id) == 4 ? 'selected' : '' }}>Management</option>
                </select>
            </div>
            @if((int) old('role_id', $user->role_id) === 4)
                <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200">
                    <label class="flex items-center gap-2 text-xs font-black text-amber-900 cursor-pointer">
                        <input type="checkbox" name="is_principal" value="1" {{ old('is_principal', $user->is_principal) ? 'checked' : '' }} class="w-4 h-4 accent-[#8b1818]">
                        Designate as Principal Evaluator
                    </label>
                    <p class="text-[10px] text-amber-700 font-semibold mt-1 ml-6">Only this designated Management account may submit Principal evaluations.</p>
                </div>
            @endif

            <!-- SECTION 1: PERSONAL & ACADEMIC INFORMATION -->
            <div>
                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-wider mb-3">Personal & Academic Information</h3>
                
                <!-- ROW 1: Last Name | First Name | Middle Name -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Last Name <span class="text-red-600">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition uppercase">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">First Name <span class="text-red-600">*</span></label>
                        <input type="text" name="first_name" x-model="firstName" value="{{ old('first_name', $user->first_name) }}" required
                               @input="$el.value = $el.value.toUpperCase(); firstName = $el.value; syncStudentUsername()"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition uppercase">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition uppercase">
                    </div>
                    @if((int)$user->role_id === 3)
                        <div class="mb-4">
                            <label class="block text-xs font-black text-slate-700 mb-1.5">Student Username</label>
                            <input type="text" name="username" x-model="username" required @input="usernameAuto = false"
                                   placeholder="Defaults to LRN + first name"
                                   class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                            <p class="mt-1 text-[11px] font-semibold text-slate-500">Leave blank to generate a unique username from the LRN and first name.</p>
                        </div>
                    @else
                        <input type="hidden" name="username" value="{{ old('username', $user->username) }}">
                    @endif
                </div>

                <!-- ROW 2: 1st Student ID, 2nd LRN, 3rd Gender -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">
                            {{ (int)$user->role_id === 3 ? 'Student ID' : 'Employee ID' }}<span class="text-red-600"> *</span>
                        </label>
                        <input type="text" name="student_id" value="{{ old('student_id', $user->student_id) }}" required
                               placeholder="e.g. STU-2026-001"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">{{ (int)$user->role_id === 3 ? 'LRN (12-Digit Learner Ref No)' : 'Employee ID / Username' }}<span class="text-red-600"> *</span></label>
                        <input type="text" name="id_number" x-model="lrn" value="{{ old('id_number', $user->id_number) }}" required
                               @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 12); lrn = $el.value; syncStudentUsername()"
                               placeholder="e.g. 103063080022"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Gender <span class="text-red-600">*</span></label>
                        <select name="gender" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                            <option value="Male" {{ old('gender', $user->gender ?? '') == 'Male' || old('gender', $user->gender ?? '') == '1' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $user->gender ?? '') == 'Female' || old('gender', $user->gender ?? '') == '2' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>

                <!-- ROW 3: Class Section | Contact Number | Email Address -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Class Section</label>
                        <select name="section" x-model="section" {{ (int)$user->role_id === 3 ? 'required' : '' }} class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                            <option value="">-- Select Section --</option>
                            <template x-for="sectionOption in getFilteredSections()" :key="sectionOption.id">
                                <option :value="sectionOption.section_name" x-text="'Section ' + sectionOption.section_name"></option>
                            </template>
                        </select>
                    </div>

                    @if((int)$user->role_id === 3)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-black text-slate-700 mb-1.5">Grade Level <span class="text-red-600">*</span></label>
                                <select name="grade_level" x-model="gradeLevel" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800">
                                    @foreach($gradeLevels ?? [] as $grade)
                                        <option value="{{ $grade }}">{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-700 mb-1.5">Strand / Track <span x-show="isSHSGrade()" class="text-red-600">*</span></label>
                                <select name="strand" :disabled="!isSHSGrade()" :required="isSHSGrade()" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 disabled:bg-slate-100">
                                    <option value="">Select Strand</option>
                                    @foreach($strands ?? [] as $strand)
                                        <option value="{{ $strand }}" {{ old('strand', $user->strand) == $strand ? 'selected' : '' }}>{{ $strand }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Contact Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? $user->contact_number) }}" {{ (int)$user->role_id === 3 ? 'required' : '' }} maxlength="11" placeholder="09XXXXXXXXX"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    @if((int)$user->role_id !== 3)
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Email Address <span class="text-red-600">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                    @endif
                </div>

                @if((int)$user->role_id === 3)
                    <!-- Emergency Contact Section -->
                    <div class="pt-4 mt-4 border-t border-slate-100 space-y-3">
                        <h4 class="text-[11px] font-black text-[#8b1818] uppercase tracking-wider">Parent / Guardian / Emergency Contact</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Full Name</label>
                                <input type="text" name="parent_name" value="{{ old('parent_name', $user->parent_name) }}" required placeholder="e.g. JUAN DELA CRUZ SR."
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Relationship to Student</label>
                                <select name="parent_relationship" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                                    <option value="" selected>Select Relationship</option>
                                    <option value="Father" {{ old('parent_relationship', $user->parent_relationship) == 'Father' ? 'selected' : '' }}>Father</option>
                                    <option value="Mother" {{ old('parent_relationship', $user->parent_relationship) == 'Mother' ? 'selected' : '' }}>Mother</option>
                                    <option value="Guardian" {{ old('parent_relationship', $user->parent_relationship) == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                                    <option value="Grandparent" {{ old('parent_relationship', $user->parent_relationship) == 'Grandparent' ? 'selected' : '' }}>Grandparent</option>
                                    <option value="Relative" {{ old('parent_relationship', $user->parent_relationship) == 'Relative' ? 'selected' : '' }}>Relative</option>
                                    <option value="Other" {{ old('parent_relationship', $user->parent_relationship) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Emergency Contact Phone Number</label>
                                <input type="text" name="parent_phone_number" value="{{ old('parent_phone_number', $user->parent_phone_number) }}" required maxlength="11" placeholder="09XXXXXXXXX"
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 2: ACCOUNT SECURITY CREDENTIALS -->
            <div class="pt-4 border-t border-slate-100 space-y-3">
                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Account Security Credentials</h3>
                @php
                    $defaultPass = match((int)$user->role_id) {
                        2 => 'siafaculty@123',
                        4 => 'siamanagement@123',
                        default => 'onesia@123',
                    };
                @endphp
                <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="space-y-0.5">
                        <span class="font-black text-amber-950 flex items-center gap-1.5">
                            <i class="fa-solid fa-key text-amber-600"></i> Default Password Reset
                        </span>
                        <p class="text-slate-600 font-medium">
                            Reset password for this account back to default: 
                            <code class="px-1.5 py-0.5 bg-amber-100 text-amber-900 font-mono font-bold rounded border border-amber-200">{{ $defaultPass }}</code>
                        </p>
                    </div>
                    <label class="inline-flex items-center gap-2 font-black text-slate-800 cursor-pointer shrink-0 bg-white px-3 py-2 rounded-xl border border-amber-300 shadow-2xs">
                        <input type="checkbox" name="reset_to_default_password" value="1" class="w-4 h-4 text-[#8b1818] rounded border-slate-300 focus:ring-[#8b1818]">
                        <span>Reset to Default</span>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-2xl transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 bg-[#8b1818] hover:bg-opacity-90 text-white text-xs font-black rounded-2xl shadow-md transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Update Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection