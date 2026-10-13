@extends('layouts.guest')

@section('title', 'Start Attendance Session - SIATRACK')

@section('content')
<div class="fixed inset-0 z-50 h-screen w-screen overflow-hidden bg-white font-['Plus_Jakarta_Sans',sans-serif] text-gray-900 flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-yellow-400/15 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 h-[500px] w-[500px] rounded-full bg-red-600/15 blur-[140px] pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(circle, #701010 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="relative z-10 w-full max-w-5xl min-h-[560px] max-h-[660px] bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/20 border-2 border-red-900/20 ring-4 ring-yellow-400/30 grid grid-cols-1 lg:grid-cols-12 overflow-hidden">
        <div class="hidden lg:flex lg:col-span-5 relative bg-gradient-to-br from-[#701010] via-[#5a0909] to-[#360505] p-10 flex-col justify-between overflow-hidden">
            <div class="absolute inset-0 opacity-[0.05]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white p-2 shadow-xl border-2 border-yellow-400/80">
                    <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Logo" class="h-full w-full object-contain rounded-2xl">
                </div>
                <div>
                    <span class="text-lg font-black tracking-wider text-white">SIATRACK</span>
                    <p class="text-xs font-bold text-yellow-300">Southern Isabela Academy</p>
                </div>
            </div>
            <div class="relative z-10 my-auto py-8 text-white">
                <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-yellow-400/20 border border-yellow-400/40 text-yellow-300 text-sm font-black uppercase tracking-widest mb-5 shadow-md">
                    ATTENDANCE PROPER
                </div>
                <h3 class="text-3xl xl:text-4xl font-black tracking-tight leading-tight">
                    Start NFC <br><span class="text-yellow-300">Attendance.</span>
                </h3>
                <p class="mt-4 text-sm xl:text-base text-white/90 leading-relaxed font-medium max-w-xs">
                    Re-enter your credentials before opening the secure attendance scanner.
                </p>
            </div>
            <div class="relative z-10 flex items-center justify-between text-xs text-white/80 font-semibold border-t border-white/20 pt-4">
                <span>&copy; {{ date('Y') }}. All Rights Reserved.</span>
                <span class="text-yellow-300">SIATRACK</span>
            </div>
        </div>

        <div class="col-span-1 lg:col-span-7 p-6 sm:p-10 lg:p-14 flex flex-col justify-center relative overflow-y-auto bg-white">
            <div class="flex items-center gap-3 lg:hidden mb-8">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-md border-2 border-yellow-400 p-1.5">
                    <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Logo" class="h-full w-full object-contain">
                </div>
                <div>
                    <span class="text-base font-black text-[#701010]">SIATRACK</span>
                    <p class="text-xs font-semibold text-gray-500">Southern Isabela Academy</p>
                </div>
            </div>

            <div class="text-center border-b border-gray-100 pb-5 mb-7">
                <h1 class="text-3xl font-black tracking-tight text-gray-900">Start Attendance Session</h1>
                <p class="text-sm text-gray-500 font-semibold mt-2">Enter your password to access the attendance scanner</p>
            </div>

            @if($errors->any())
                <div class="mb-5 rounded-2xl bg-red-50 border border-red-200 p-4 text-red-600 text-sm">
                    <span class="font-bold">Authentication Error</span>
                    <p class="mt-1">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('attendance.start.submit') }}" class="space-y-5" autocomplete="off">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2" for="attendance_password">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input id="attendance_password" name="password" type="password" required autofocus autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full h-12 pl-12 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-base font-semibold text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#701010] focus:bg-white focus:ring-2 focus:ring-[#701010]/20 transition">
                        <button type="button" onclick="toggleAttendancePassword(this)"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-[#701010] transition"
                            aria-label="Show password">
                            <i class="fa-regular fa-eye text-base"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" style="background-color:#701010;color:#ffffff;border:1px solid rgba(250,204,21,.55);display:flex;align-items:center;justify-content:center;"
                    class="w-full h-12 rounded-xl hover:bg-[#5a0909] text-white text-sm font-black shadow-lg shadow-red-900/20 transition">
                    Start Attendance
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-bold text-gray-500 hover:text-[#a90000] transition">Log out</button>
            </form>
        </div>
    </div>
</div>
<script>
    function toggleAttendancePassword(button) {
        const input = document.getElementById('attendance_password');
        const icon = button.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            button.setAttribute('aria-label', 'Hide password');
            icon.className = 'fa-regular fa-eye-slash text-base';
        } else {
            input.type = 'password';
            button.setAttribute('aria-label', 'Show password');
            icon.className = 'fa-regular fa-eye text-base';
        }
    }
</script>
@endsection
