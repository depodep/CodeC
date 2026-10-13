@extends('layouts.app')

@section('title', 'Manage Account | SIATRACK')

@section('content')
<div class="p-6 md:p-8 max-w-4xl mx-auto w-full">
    
    <!-- Page Header (SIATRACK Theme) -->
    <div class="mb-8">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-user-gear text-[#590d0d] text-lg"></i>
            </div>
            Manage Account
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            Update your faculty profile, contact information, and security settings.
        </p>
    </div>

    <!-- Success Message Alert -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3 font-semibold text-sm shadow-sm">
            <i class="fa-solid fa-circle-check text-green-500 text-lg"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Profile Edit Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ auth()->user()->role_id == 3 ? route('student.profile.update') : (Route::has('teacher.profile.update') && auth()->user()->role_id == 2 ? route('teacher.profile.update') : route('profile.update')) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="p-6 md:p-8">
                
                <h2 class="text-lg font-bold text-[#590d0d] mb-6 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-amber-500"></i> Personal Information
                </h2>

                <!-- Profile Photo -->
                <div class="mb-6 flex flex-col md:flex-row items-center gap-6">
                    <div class="w-24 h-24 rounded-full bg-gray-100 border-4 border-amber-100 overflow-hidden shrink-0">
                        @if(auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Profile Photo" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <i class="fa-solid fa-user text-4xl"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Profile Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#590d0d]/10 file:text-[#590d0d] hover:file:bg-[#590d0d]/20 transition cursor-pointer">
                        @error('photo') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                    <!-- First Name -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', auth()->user()->first_name) }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                        @error('first_name') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', auth()->user()->last_name) }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                        @error('last_name') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- ID Number -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Faculty ID Number <span class="text-red-500">*</span></label>
                        <input type="text" name="id_number" value="{{ old('id_number', auth()->user()->id_number) }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-bold text-[#590d0d] outline-none transition">
                        @error('id_number') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                            <option value="1" {{ old('gender', auth()->user()->gender) == 1 ? 'selected' : '' }}>Male</option>
                            <option value="2" {{ old('gender', auth()->user()->gender) == 2 ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <h2 class="text-lg font-bold text-[#590d0d] mb-6 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-address-book text-amber-500"></i> Contact & Security
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <!-- Email -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                        @error('email') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', auth()->user()->phone_number) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition" placeholder="e.g. 09123456789">
                        @error('phone_number') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">New Password (Optional)</label>
                        <input type="password" name="password" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition" placeholder="Leave blank to keep current">
                        @error('password') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                    </div>
                </div>

            </div>

            <!-- Footer Action -->
            <div class="bg-gray-50 px-6 py-4 md:px-8 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="reset" class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-200 transition">
                    Reset
                </button>
                <button type="submit" class="bg-[#590d0d] hover:bg-red-950 text-amber-300 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection