<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Attendance - Teacher Portal - SIATRACK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sia-card { background: #ffffff; border: 1.5px solid #f1f5f9; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased min-h-screen flex">

    <!-- REUSABLE MAROON SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN DASHBOARD CONTENT -->
    <div style="margin-left: 288px;" class="flex-1 min-h-screen p-8 bg-[#f8fafc]">
        
        <!-- Header Profile Bar -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-200/60">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Student Attendance Monitor</h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Southern Isabela Academy &bull; Real-Time NFC Tap Logs & Attendance Monitor</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 border border-amber-300 flex items-center justify-center font-bold text-amber-900 text-xs">
                    {{ substr(Auth::user()->first_name ?? 'P', 0, 1) }}{{ substr(Auth::user()->last_name ?? 'G', 0, 1) }}
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">{{ Auth::user()->first_name ?? 'Prof.' }} {{ Auth::user()->last_name ?? 'Teacher' }}</div>
                    <div class="text-[10px] text-amber-700 font-semibold">Faculty Member</div>
                </div>
            </div>
        </div>

        <!-- Metric Counter Cards (Real Data) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="sia-card p-5 flex items-center justify-between border-l-4 border-l-emerald-500">
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">On-Time Arrivals</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $presentCount ?? 0 }}</h3>
                    <span class="text-[11px] font-semibold text-emerald-600 mt-1 inline-block">Present & On Schedule</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>

            <div class="sia-card p-5 flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Late Arrivals</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $lateCount ?? 0 }}</h3>
                    <span class="text-[11px] font-semibold text-amber-600 mt-1 inline-block">Past Morning Cutoff</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>

            <div class="sia-card p-5 flex items-center justify-between border-l-4 border-l-rose-500">
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Total Scanned</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $totalScanned ?? 0 }}</h3>
                    <span class="text-[11px] font-semibold text-rose-600 mt-1 inline-block">Total NFC Taps</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
            </div>
        </div>

        <!-- Live NFC Kiosk Launcher Banner -->
        <div class="rounded-3xl bg-gradient-to-r from-[#5c0d11] to-[#8b1818] p-6 text-white shadow-xl mb-8 flex flex-col md:flex-row items-center justify-between gap-6 border border-red-950/20">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-black/30 border border-white/20 flex items-center justify-center text-amber-300 text-2xl shrink-0">
                    <i class="fa-solid fa-wifi rotate-45"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            TERMINAL ONLINE &bull; READY
                        </span>
                    </div>
                    <h3 class="text-xl font-black tracking-wide">Live NFC Attendance Terminal</h3>
                    <p class="text-xs text-red-100/75 mt-0.5">Launch the dedicated station for tap identification and real-time database recording.</p>
                </div>
            </div>
            <a href="{{ route('teacher.kiosk') }}" class="px-6 py-3.5 bg-white text-slate-950 hover:bg-amber-300 rounded-2xl font-black text-xs uppercase tracking-wider transition shadow-lg shrink-0 flex items-center gap-2">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Launch Kiosk Terminal
            </a>
        </div>

        <!-- FULL-WIDTH ATTENDANCE RECORDS TABLE -->
        <div class="sia-card p-6 w-full">
            
            <!-- Filters Toolbar -->
            <form method="GET" action="{{ route('teacher.attendance') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200 uppercase tracking-wider">Filtered Date</span>
                    <h3 class="font-black text-slate-900 text-base">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }}
                    </h3>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or ID..." class="pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-amber-400">
                    </div>

                    <!-- Status Filter -->
                    <select name="status" class="px-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold rounded-xl text-slate-700 outline-none focus:border-amber-400">
                        <option value="">All Statuses</option>
                        <option value="ON-TIME" {{ request('status') == 'ON-TIME' ? 'selected' : '' }}>On-Time</option>
                        <option value="LATE" {{ request('status') == 'LATE' ? 'selected' : '' }}>Late</option>
                    </select>

                    <!-- Date Picker -->
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="px-3 py-2 bg-slate-50 border border-slate-200 text-xs font-semibold rounded-xl text-slate-700 outline-none focus:border-amber-400 cursor-pointer">
                    <input type="hidden" name="date_to" value="{{ $dateFrom }}">

                    <button type="submit" class="px-4 py-2 bg-[#5c0d11] hover:bg-[#43090c] text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>

                    <a href="{{ route('teacher.attendance') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">Student</th>
                            <th class="py-3.5 px-4">LRN / ID Number</th>
                            <th class="py-3.5 px-4">Strand / Track</th>
                            <th class="py-3.5 px-4">Time In</th>
                            <th class="py-3.5 px-4">Time Out</th>
                            <th class="py-3.5 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (isset($recentLogs) && count($recentLogs) > 0): ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 text-sm"><?php echo e($log->last_name); ?>, <?php echo e($log->first_name); ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-500 font-semibold">
                                        <?php echo e($log->id_number ?? 'N/A'); ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 font-medium">
                                        <span class="bg-slate-100 px-2.5 py-1 rounded-md text-[11px] font-bold text-slate-700">
                                            <?php echo e($log->strand ?? 'Academic Track'); ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                        <i class="fa-regular fa-clock text-amber-500 mr-1"></i>
                                        <?php echo e($log->time_in ?? \Carbon\Carbon::parse($log->created_at)->format('h:i A')); ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-500">
                                        <?php echo e(!empty($log->time_out) ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '--:-- --'); ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?php echo ($log->status ?? '') === 'ON-TIME' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
                                            <?php echo e($log->status ?? 'ON-TIME'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-16 text-center text-slate-400 font-medium">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-regular fa-folder-open text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-500">No attendance logs found in the database for this date.</p>
                                    <p class="text-xs text-slate-400 mt-1">Students will automatically appear here once their NFC tags are scanned.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="pt-5 border-t border-slate-100 mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400 font-medium">
                <div>
                    Showing <span class="font-bold text-slate-700"><?php echo isset($recentLogs) ? $recentLogs->count() : 0; ?></span> record(s)
                </div>
                <div class="flex items-center gap-3">
                    <?php if(isset($recentLogs) && method_exists($recentLogs, 'links')): ?>
                        <?php echo $recentLogs->links(); ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</body>
</html>