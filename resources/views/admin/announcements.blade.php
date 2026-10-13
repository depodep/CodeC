@extends('layouts.app')

@section('title', 'Announcements - SIATRACK')

@section('content')
<div class="w-full min-h-screen bg-slate-50/70"
     x-data="{ showCreate: false }">

    {{-- ===== HEADER ===== --}}
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#601010] text-white flex items-center justify-center text-xl shadow-md shrink-0">
                <i class="fa-solid fa-bullhorn text-amber-300"></i>
            </div>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Announcements</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Post notices and updates to students, faculty, or all users</p>
            </div>
        </div>
        <button @click="showCreate = true"
                class="px-5 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-xs cursor-pointer">
            <i class="fa-solid fa-plus"></i> New Announcement
        </button>
    </header>

    {{-- ===== CREATE MODAL ===== --}}
    <div x-show="showCreate"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display:none;">
        <div class="bg-white rounded-3xl border-2 border-slate-200 max-w-lg w-full p-6 sm:p-8 shadow-2xl relative"
             @click.away="showCreate = false">
            <button @click="showCreate = false"
                    class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Post Announcement</h3>
                    <p class="text-xs text-slate-500 font-semibold">Visible to selected user groups</p>
                </div>
            </div>

            <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Title</label>
                    <input type="text" name="title" required maxlength="255"
                           placeholder="e.g., No classes on Monday"
                           class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800 placeholder:text-slate-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Message</label>
                    <textarea name="message" required rows="4"
                              placeholder="Enter the full announcement message here..."
                              class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800 placeholder:text-slate-400 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5">Target Audience</label>
                    <select name="target" class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-200 focus:border-[#8b1818] focus:outline-none text-xs font-bold text-slate-800">
                        <option value="all">All Users</option>
                        <option value="students">Students Only</option>
                        <option value="faculty">Faculty Only</option>
                        <option value="management">Management Only</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <button type="button" @click="showCreate = false"
                            class="py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="py-3 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-bullhorn text-xs"></i> Post Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <main class="p-6 lg:p-10 max-w-[1200px] mx-auto space-y-6">

        {{-- Flash --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-900 text-xs font-bold rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Search / Count bar --}}
        <form method="GET" action="{{ route('admin.announcements') }}"
              class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
            <p class="text-xs font-black text-slate-500 shrink-0">
                {{ number_format($total) }} announcement{{ $total !== 1 ? 's' : '' }} total
            </p>
            <div class="flex gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-72">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search announcements..."
                           class="w-full pl-8 pr-3 py-2 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:border-[#8b1818] text-slate-700 placeholder:text-slate-400">
                </div>
                <button type="submit"
                        class="px-4 py-2 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition cursor-pointer">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.announcements') }}"
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                    </a>
                @endif
            </div>
        </form>

        {{-- Announcements List --}}
        @if($announcements->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs py-24 text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-400 flex items-center justify-center text-3xl mx-auto mb-4">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <p class="text-sm font-black text-slate-500">No announcements yet</p>
                <p class="text-xs text-slate-400 font-semibold mt-1">Click "New Announcement" to post your first notice.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($announcements as $ann)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex gap-4">
                    {{-- Icon --}}
                    @php
                        $targetColor = match($ann->target ?? 'all') {
                            'students'   => 'emerald',
                            'faculty'    => 'blue',
                            'management' => 'purple',
                            default      => 'amber',
                        };
                        $targetLabel = match($ann->target ?? 'all') {
                            'students'   => 'Students',
                            'faculty'    => 'Faculty',
                            'management' => 'Management',
                            default      => 'All Users',
                        };
                    @endphp
                    <div class="w-10 h-10 rounded-xl bg-{{ $targetColor }}-50 border border-{{ $targetColor }}-100 text-{{ $targetColor }}-600 flex items-center justify-center text-base shrink-0 mt-0.5">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-black text-slate-900 text-sm">{{ $ann->title }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-0.5 rounded-md bg-{{ $targetColor }}-50 border border-{{ $targetColor }}-200 text-{{ $targetColor }}-700 font-black text-[10px] uppercase">
                                        {{ $targetLabel }}
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-400">
                                        {{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <form action="{{ route('admin.announcements.destroy', $ann->id) }}" method="POST" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center transition cursor-pointer"
                                        onclick="return confirm('Delete this announcement?')">
                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                </button>
                            </form>
                        </div>
                        <p class="mt-2 text-xs font-semibold text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $ann->message }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($announcements->hasPages())
            <div class="mt-4">
                {{ $announcements->links() }}
            </div>
            @endif
        @endif

    </main>
</div>
@endsection
