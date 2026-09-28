@extends('layouts.app')

@section('title', 'Manage Account | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70 p-6 md:p-8">
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-3"><span class="w-10 h-10 rounded-xl bg-white border-2 border-amber-300 text-[#590d0d] flex items-center justify-center"><i class="fa-solid fa-user-gear"></i></span> Manage Account</h1>
            <p class="text-sm font-semibold text-slate-500 mt-2">Review your student information and update your username or password.</p>
        </div>

        @if(session('success'))<div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-bold">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-bold">{{ $errors->first() }}</div>@endif

        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs overflow-hidden">
            <div class="p-6 md:p-8">
                <h2 class="text-lg font-black text-[#590d0d] border-b border-slate-100 pb-3">Student Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div><span class="block text-[10px] font-black uppercase text-slate-400">Student Name</span><p class="mt-1 text-sm font-black text-slate-800">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</p></div>
                    <div><span class="block text-[10px] font-black uppercase text-slate-400">Student ID / LRN</span><p class="mt-1 text-sm font-black text-slate-800">{{ $user->id_number ?? 'Not set' }}</p></div>
                    <div><span class="block text-[10px] font-black uppercase text-slate-400">Grade | Section | Strand</span><p class="mt-1 text-sm font-black text-slate-800">{{ $user->grade_level ?? 'Not set' }} | {{ $user->section ?? 'Not set' }} | {{ $user->strand ?? 'Not set' }}</p></div>
                    <div><span class="block text-[10px] font-black uppercase text-slate-400">School</span><p class="mt-1 text-sm font-black text-slate-800">Southern Isabela Academy, Angadanan Campus</p></div>
                </div>

                <h2 class="text-lg font-black text-[#590d0d] border-b border-slate-100 pb-3 mt-8">Account Credentials</h2>
                <form action="{{ route('student.profile.update') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <label class="block text-xs font-black text-slate-700">Username <span class="text-rose-500">*</span><input type="text" name="username" value="{{ old('username', $user->username) }}" required class="mt-1 w-full min-h-11 px-4 py-3 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#8b1818] outline-none"></label>
                    <label class="block text-xs font-black text-slate-700">Current Password <span class="text-rose-500">*</span> <span class="text-slate-400 font-semibold">(required only when changing password)</span><input type="password" name="current_password" class="mt-1 w-full min-h-11 px-4 py-3 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#8b1818] outline-none"></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="block text-xs font-black text-slate-700">New Password <span class="text-slate-400 font-semibold">(optional)</span><input type="password" name="password" class="mt-1 w-full min-h-11 px-4 py-3 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#8b1818] outline-none" placeholder="Leave blank to keep current"></label>
                        <label class="block text-xs font-black text-slate-700">Confirm New Password<input type="password" name="password_confirmation" class="mt-1 w-full min-h-11 px-4 py-3 rounded-xl border-2 border-slate-200 text-sm font-bold focus:border-[#8b1818] outline-none"></label>
                    </div>
                    <div class="flex justify-end pt-4 border-t border-slate-100"><button type="submit" class="px-6 py-3 rounded-xl bg-[#590d0d] text-amber-300 font-black text-xs"><i class="fa-solid fa-floppy-disk mr-1"></i> Save Credentials</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
