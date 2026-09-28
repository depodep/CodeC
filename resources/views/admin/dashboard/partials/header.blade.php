<!-- Top Header Bar -->
<header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
    <div>
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-table-cells-large text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Admin Dashboard</h1>
                   
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">
                    <i class="fa-regular fa-calendar text-slate-400 mr-1"></i>
                    {{ \Carbon\Carbon::now()->format('l, F d, Y') }}
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <button type="button" 
                onclick="openEditProfileModal()"
                title="Click to edit profile"
                class="group flex items-center gap-3 p-1.5 pr-4 rounded-2xl hover:bg-slate-100 border-2 border-slate-200 hover:border-slate-300 transition text-left bg-white shadow-2xs cursor-pointer">
            <div class="relative">
                <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-white font-black text-xs flex items-center justify-center shadow-xs group-hover:scale-105 transition">
                    {{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? 'D', 0, 1)) }}
                </div>
                <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-white border border-slate-200 rounded-full flex items-center justify-center text-[9px] text-slate-600 shadow-2xs group-hover:text-[#590d0d]">
                    <i class="fa-solid fa-pen"></i>
                </span>
            </div>
            <div class="hidden sm:block">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-black text-slate-900 group-hover:text-[#590d0d] transition">
                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </span>
                    <i class="fa-solid fa-pen-to-square text-[10px] text-slate-400 group-hover:text-[#590d0d] transition opacity-0 group-hover:opacity-100"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Administrator</span>
            </div>
        </button>
    </div>
</header>