@extends('layouts.app')

@section('content')
<!-- Main Wrapper with Left Margin to Avoid Covering the Sidebar -->
<div class="lg:ml-72 min-h-screen flex items-center justify-center bg-slate-50/70 p-4 lg:p-8 overflow-y-auto">
    
    <!-- Modal Card Box -->
    <div class="bg-white rounded-3xl border border-slate-200 p-8 pt-10 shadow-2xl relative w-full max-w-4xl my-8">
        
        <!-- Close / X Button (Repositioned to prevent overlap) -->
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

            <!-- SECTION 1: PERSONAL INFORMATION & PLACEMENT -->
            <div>
                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-wider mb-3">Personal & Academic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Last Name -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>

                    <!-- First Name -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>

                    <!-- Employee ID / Student ID -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Employee ID / Student ID</label>
                        <input type="text" name="id_number" value="{{ old('id_number', $user->id_number) }}"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Gender</label>
                        <select name="gender" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                            <option value="Male" {{ old('gender', $user->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $user->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <!-- Class Section (Database Dropdown) -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Class Section</label>
                        <select name="section" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                            <option value="">-- Select Section --</option>
                            @foreach(App\Models\User::whereNotNull('section')->where('section', '!=', '')->distinct()->pluck('section') as $sec)
                                <option value="{{ $sec }}" {{ old('section', $user->section) == $sec ? 'selected' : '' }}>Section {{ $sec }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Contact Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? $user->contact_number) }}"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    <!-- Email Address -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                    </div>
                </div>
            </div>

            <!-- SECTION 2: ACCOUNT SECURITY CREDENTIALS -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Account Security Credentials</h3>
                    <button type="button" onclick="togglePasswordVisibility()" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5 bg-slate-100 px-3 py-1 rounded-xl transition cursor-pointer">
                        <i class="fa-solid fa-eye text-[10px]" id="eyeIcon"></i> <span id="toggleText">Show Passwords</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Current Password -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Current Password</label>
                        <input type="password" name="current_password" id="current_password" placeholder="current password"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">New Password</label>
                        <input type="password" name="password" id="password" placeholder="new password"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="confirm password"
                               class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition font-mono">
                    </div>
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

<script>
    function togglePasswordVisibility() {
        const currInput = document.getElementById('current_password');
        const passInput = document.getElementById('password');
        const confInput = document.getElementById('password_confirmation');
        const eyeIcon = document.getElementById('eyeIcon');
        const toggleText = document.getElementById('toggleText');

        const isPassword = currInput.type === 'password';

        currInput.type = isPassword ? 'text' : 'password';
        passInput.type = isPassword ? 'text' : 'password';
        confInput.type = isPassword ? 'text' : 'password';

        if (isPassword) {
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
            toggleText.textContent = 'Hide Passwords';
        } else {
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
            toggleText.textContent = 'Show Passwords';
        }
    }
</script>
@endsection