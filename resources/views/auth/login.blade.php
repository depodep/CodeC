@extends('layouts.guest')

@section('title', 'SIATRACK Login')

@section('content')

<div class="fixed inset-0 z-50 h-screen w-screen overflow-hidden bg-white font-['Plus_Jakarta_Sans',sans-serif] text-gray-900 flex items-center justify-center p-4 sm:p-6 lg:p-8">

    <!-- Subtle Red & Yellow Ambient Background Glows -->
    <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-yellow-400/15 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 h-[500px] w-[500px] rounded-full bg-red-600/15 blur-[140px] pointer-events-none"></div>

    <!-- Background Grid Pattern Accent -->
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(circle, #701010 1px, transparent 1px); background-size: 24px 24px;"></div>

    <!-- Main Container Card with Glowing Border Effect -->
    <div class="relative z-10 w-full max-w-5xl h-full max-h-[660px] bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/20 border-2 border-red-900/20 ring-4 ring-yellow-400/30 grid grid-cols-1 lg:grid-cols-12 overflow-hidden">

        <!-- ========================================================= -->
        <!-- LEFT BRANDING PANEL (5 cols) -->
        <!-- ========================================================= -->
        <div class="hidden lg:flex lg:col-span-5 relative bg-gradient-to-br from-[#701010] via-[#5a0909] to-[#360505] p-10 flex-col justify-between overflow-hidden">
            
            <!-- Pattern Overlay -->
            <div class="absolute inset-0 opacity-[0.05]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 20px 20px;"></div>

            <!-- Top Header Logo (Enlarged) -->
            <div class="relative z-10 flex items-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white p-2 shadow-xl border-2 border-yellow-400/80">
                    <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Logo" class="h-full w-full object-contain rounded-2xl">
                </div>
                <div>
                    <span class="text-lg font-black tracking-wider text-white">SIATRACK</span>
                    <p class="text-xs font-bold text-yellow-300">Southern Isabela Academy</p>
                </div>
            </div>

            <!-- Center Welcome Copy -->
            <div class="relative z-10 my-auto py-8 text-white">
                <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-yellow-400/20 border border-yellow-400/40 text-yellow-300 text-sm font-black uppercase tracking-widest mb-5 shadow-md">
    BE A SIAN
</div>
<h3 class="text-3xl xl:text-4xl font-black tracking-tight leading-tight">
    NFC Attendance & <br><span class="text-yellow-300">Evaluations.</span>
</h3>
<p class="mt-4 text-sm xl:text-base text-white/90 leading-relaxed font-medium max-w-xs">
    Secure NFC-based attendance tracking and digital faculty evaluations designed for Southern Isabela Academy.
</p>
            </div>

            <!-- Bottom Footer Badge -->
<div class="relative z-10 flex items-center justify-between text-xs text-white/80 font-semibold border-t border-white/20 pt-4">
    <span>&copy; {{ date('Y') }}. All Rights Reserved.</span>
</div>
        </div>

        <!-- ========================================================= -->
        <!-- RIGHT LOGIN FORM PANEL (7 cols) -->
        <!-- ========================================================= -->
        <div class="col-span-1 lg:col-span-7 p-6 sm:p-10 lg:p-12 flex flex-col justify-center relative overflow-y-auto bg-white text-gray-900">
            
            <!-- Mobile Header Logo -->
            <div class="flex items-center gap-3 lg:hidden mb-6">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-md border-2 border-yellow-400 p-1.5">
                    <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Logo" class="h-full w-full object-contain">
                </div>
                <div>
                    <span class="text-base font-black text-[#701010]">SIATRACK</span>
                    <p class="text-xs font-semibold text-gray-500">Southern Isabela Academy</p>
                </div>
            </div>

            <!-- Centered Header Title with Right Tag Design Element -->
            <div class="flex items-end justify-between border-b border-gray-100 pb-4 mb-6">
                <div class="text-center w-full">
                    <h1 class="text-3xl font-black tracking-tight text-gray-900">Sign In</h1>
                    <p class="text-sm text-gray-500 font-semibold mt-1">Enter your credentials to access your dashboard</p>
                </div>
            </div>

            <!-- Error Box Alert -->
            @if ($errors->any())
                <div class="mb-5 rounded-2xl bg-red-50 border border-red-200 p-4 text-red-600 text-sm">
                    <div class="flex items-start gap-2.5">
                        <svg class="h-5 w-5 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <span class="font-bold">Authentication Error</span>
                            <ul class="mt-0.5 space-y-0.5 text-red-500">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-5" autocomplete="off">
                @csrf

               <!-- Username / Email -->
                <div>
                   <label for="username" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">Username / Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="off"
                            placeholder="Enter username or email"
                            class="w-full h-12 pl-12 pr-4 bg-gray-50 border border-gray-200 rounded-xl text-base font-semibold text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#701010] focus:bg-white focus:ring-2 focus:ring-[#701010]/20 transition">
                    </div>
                </div>  

                <!-- Password -->
                <div>
                    <label for="login_password" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password" id="login_password" name="password" required autocomplete="new-password"
                            placeholder="••••••••••••"
                            class="w-full h-12 pl-12 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-base font-semibold text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#701010] focus:bg-white focus:ring-2 focus:ring-[#701010]/20 transition">

                        <!-- Toggle Pass Button -->
                        <button type="button" onclick="togglePass('login_password', this)"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-700 transition"
                            aria-label="Toggle password visibility">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Row -->
                <div class="flex items-center justify-between text-sm pt-1">
                    <label class="flex items-center gap-2.5 cursor-pointer text-gray-700 hover:text-gray-900 transition font-medium">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 bg-gray-50 text-[#701010] focus:ring-[#701010]/30 h-4 w-4">
                        <span>Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full h-12 mt-2 bg-[#701010] hover:bg-[#5a0909] text-white font-extrabold text-base rounded-xl shadow-lg shadow-red-900/25 flex items-center justify-center gap-2.5 transition active:scale-[0.99] border border-yellow-400/40">
                    <span>Sign In</span>
                
                </button>
            </form>

        </div>

    </div>

</div>

<!-- Overrides & Toggle Script -->
<style>
    input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus {
        -webkit-text-fill-color: #1f2937 !important;
        -webkit-box-shadow: 0 0 0px 1000px #f9fafb inset !important;
        transition: background-color 5000s ease-in-out 0s;
    }
</style>

<script>
    function togglePass(inputId, button) {
        const input = document.getElementById(inputId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.03 10.03 0 012.164-3.522M9.88 9.88l3.24 3.24M3 3l18 18"/></svg>`;
        } else {
            input.type = 'password';
            button.innerHTML = `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
        }
    }
</script>

@endsection