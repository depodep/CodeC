@extends('layouts.app')

@section('title', 'Messages | SIATRACK')

@section('content')
<div class="p-6 md:p-8 max-w-7xl mx-auto w-full">

    <!-- Page Header (SIATRACK Style) -->
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-comments text-[#590d0d] text-lg"></i>
            </div>
            Messages & SMS Logs
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            View and manage your communications and gateway history.
        </p>
    </div>

    <!-- Top Grid: Filters & Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        
        <!-- FILTERS CARD -->
        <div class="md:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-black text-[#590d0d] mb-4 flex items-center gap-2 uppercase tracking-wide">
                <i class="fa-solid fa-filter text-amber-500"></i> Filters
            </h3>
            
            <form method="GET" action="{{ route('teacher.messages') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wide">Student</label>
                    <select name="student_id" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->last_name }}, {{ $student->first_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

               <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wide">Status</label>
                    <select name="status" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        <option value="">All Status</option>
                        <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                        <option value="SENT" {{ request('status') == 'SENT' ? 'selected' : '' }}>Sent</option>
                        <option value="DELIVERED" {{ request('status') == 'DELIVERED' ? 'selected' : '' }}>Delivered</option>
                        <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wide">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wide">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="md:col-span-2 flex gap-3 mt-2">
                    <button type="submit" class="bg-[#590d0d] hover:bg-[#43090c] text-amber-300 px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-magnifying-glass"></i> Apply Filters
                    </button>
                    <a href="{{ route('teacher.messages') }}" class="bg-gray-100 border border-gray-300 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- STATISTICS CARD -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-black text-[#590d0d] mb-4 flex items-center gap-2 uppercase tracking-wide">
                <i class="fa-solid fa-chart-pie text-amber-500"></i> SMS Statistics
            </h3>
            <div class="grid grid-cols-2 gap-4 h-full pb-6 items-center">
                <div class="text-center bg-emerald-50 rounded-xl py-4 border border-emerald-100">
                    <div class="text-3xl font-black text-emerald-600">{{ $sentCount ?? 0 }}</div>
                    <div class="text-[10px] font-bold text-emerald-700 mt-1 uppercase tracking-widest">Delivered</div>
                </div>
                <div class="text-center bg-rose-50 rounded-xl py-4 border border-rose-100">
                    <div class="text-3xl font-black text-rose-600">{{ $failedCount ?? 0 }}</div>
                    <div class="text-[10px] font-bold text-rose-700 mt-1 uppercase tracking-widest">Failed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-500"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 font-bold text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-info text-blue-500"></i> {{ session('info') }}
        </div>
    @endif

    <!-- TABLE SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Table Action Bar (SIATRACK Maroon) -->
        <div class="bg-[#590d0d] px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-4 border-amber-400">
            <div class="text-amber-300 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="font-black text-sm tracking-wider uppercase">Message History</span>
                <span id="gatewayStatusContainer" class="text-[11px] font-medium text-amber-300/70 ml-2 italic hidden md:inline-block">
                    <i id="gatewayStatusIcon" class="fa-solid fa-link text-[9px] mr-1"></i> 
                    <span id="gatewayStatusText">Gateway active</span>
                </span>
            </div>
            
            <!-- ACTION BUTTONS CONTAINER -->
            <div class="flex flex-wrap items-center gap-2">
                
                <a href="{{ route('teacher.messages') }}" class="bg-white/10 text-white hover:bg-white/20 border border-white/20 px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-rotate-right"></i> Refresh
                </a>
                
                <button type="button" id="pingGatewayBtn" class="bg-amber-500/20 text-amber-300 hover:bg-amber-500/40 border border-amber-500/30 px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-satellite-dish"></i> <span>Ping Gateway</span>
                </button>

                <form method="POST" action="{{ route('teacher.messages.notify-absents') }}" class="inline" onsubmit="return confirm('System Confirmation: Magpapadala ng SMS alert sa mga magulang ng mga absent ngayong araw. Ituloy?');">
                    @csrf
                    <button type="submit" class="bg-amber-400 hover:bg-amber-500 text-[#590d0d] px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-paper-plane"></i> Notify Absents
                    </button>
                </form>

                <!-- PORMAL NA CLEAR LOGS BUTTON -->
                <form action="{{ route('teacher.messages.clear-logs') }}" method="POST" class="inline-block" onsubmit="return confirm('Security Warning: Buburahin nito ang lahat ng SMS history at records ngayong araw. Sigurado ka ba?');">
                    @csrf
                    <button type="submit" class="bg-red-900/20 hover:bg-red-900/40 text-rose-200 border border-red-800/30 px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2">
                        <i class="fa-solid fa-eraser"></i> Clear Daily Logs
                    </button>
                </form>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-600 font-black text-[10px] uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Date & Time</th>
                        <th class="p-4">Recipient</th>
                        <th class="p-4">Message Snippet</th>
                        <th class="p-4">Sender</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                    @forelse($messages as $msg)
                        <tr class="hover:bg-amber-50/50 transition duration-150">
                            
                            <td class="p-4 text-[12px] font-semibold text-gray-500">
                                {{ \Carbon\Carbon::parse($msg->created_at)->format('M d, Y') }}<br>
                                <span class="text-[10px] font-bold text-[#590d0d]">{{ \Carbon\Carbon::parse($msg->created_at)->format('h:i A') }}</span>
                            </td>
                            
                            <td class="p-4">
                                <span class="font-black text-[#590d0d]">{{ $msg->last_name ?? 'Student' }}, {{ $msg->first_name ?? '' }}</span>
                                <div class="text-[10px] text-gray-400 font-bold mt-0.5"><i class="fa-solid fa-phone text-[9px] mr-1"></i>{{ $msg->phone_number ?? 'N/A' }}</div>
                            </td>
                            
                            <td class="p-4">
                                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider mb-1 {{ (isset($msg->type) && $msg->type === 'ABSENT_ALERT') ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    {{ str_replace('_', ' ', $msg->type ?? 'MESSAGE') }}
                                </span>
                                <span class="truncate block max-w-sm text-[12px] text-gray-600 font-semibold" title="{{ $msg->message }}">
                                    {{ $msg->message }}
                                </span>
                            </td>

                            <td class="p-4 text-[11px] font-bold uppercase text-gray-500">
                                SIATRACK SYS
                            </td>
                            
                            <td class="p-4 text-center">
                                @php $status = strtoupper($msg->status ?? 'FAILED'); @endphp
                                
                                @if($status === 'DELIVERED' || $status === 'SENT')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ $status }}
                                    </span>
                                @elseif($status === 'PENDING')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> PENDING
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> FAILED
                                    </span>
                                @endif
                            </td>

                            <td class="p-4 text-center">
                                <button class="w-8 h-8 rounded-lg bg-red-50 text-[#590d0d] border border-red-100 hover:bg-[#590d0d] hover:text-amber-300 transition shadow-sm" title="View Details">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-16 text-center text-gray-400 font-medium text-sm">
                                <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                                    <i class="fa-solid fa-box-open text-3xl opacity-30"></i>
                                </div>
                                <span class="block font-bold text-gray-500">No messages found.</span>
                                <span class="text-xs mt-1 block">Adjust your filters or send a new message.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Real-time Ping Gateway Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pingBtn = document.getElementById('pingGatewayBtn');
    const statusContainer = document.getElementById('gatewayStatusContainer');
    const statusIcon = document.getElementById('gatewayStatusIcon');
    const statusText = document.getElementById('gatewayStatusText');
    
    if(pingBtn) {
        const btnText = pingBtn.querySelector('span');
        const btnIcon = pingBtn.querySelector('i');

        pingBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // 1. Loading State
            btnIcon.className = 'fa-solid fa-spinner fa-spin';
            btnText.textContent = 'Pinging...';
            pingBtn.disabled = true;
            pingBtn.classList.add('opacity-70', 'cursor-not-allowed');

            statusText.textContent = 'Checking...';
            statusIcon.className = 'fa-solid fa-spinner fa-spin text-[9px] mr-1';
            statusContainer.className = 'text-[11px] font-medium text-gray-300 ml-2 italic hidden md:inline-block';

            // 2. Fetch Ping Route
            fetch('{{ route("teacher.messages.ping") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    statusText.textContent = 'Gateway active';
                    statusContainer.className = 'text-[11px] font-medium text-emerald-400 ml-2 italic hidden md:inline-block';
                    statusIcon.className = 'fa-solid fa-link text-[9px] mr-1';
                } else {
                    statusText.textContent = 'Gateway offline';
                    statusContainer.className = 'text-[11px] font-medium text-rose-400 ml-2 italic hidden md:inline-block';
                    statusIcon.className = 'fa-solid fa-link-slash text-[9px] mr-1';
                }
            })
            .catch(error => {
                statusText.textContent = 'Connection failed';
                statusContainer.className = 'text-[11px] font-medium text-rose-400 ml-2 italic hidden md:inline-block';
                statusIcon.className = 'fa-solid fa-triangle-exclamation text-[9px] mr-1';
            })
            .finally(() => {
                btnIcon.className = 'fa-solid fa-satellite-dish';
                btnText.textContent = 'Ping Gateway';
                pingBtn.disabled = false;
                pingBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            });
        });
    }
});
</script>
@endsection