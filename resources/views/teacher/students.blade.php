<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Directory - Teacher Portal - SIATRACK</title>
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

    <!-- MAIN CONTENT -->
    <div style="margin-left: 288px;" class="flex-1 min-h-screen p-8 bg-[#f8fafc]">

        <!-- Header Profile Bar -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-200/60">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-amber-50 text-[#5c0d11] border border-amber-200 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </span>
                    Student Directory
                </h1>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">View and manage real-time master list of enrolled students.</p>
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

        <!-- Success Alert Notification -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        <!-- Error Alert Notification -->
        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
                    <span>May error sa pag-add ng student:</span>
                </div>
                <ul class="list-disc list-inside font-medium text-[11px] ml-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- MASTER LIST CARD -->
        <div class="sia-card p-6">
            
            <!-- Toolbar Header -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-users text-amber-600"></i>
                    <h3 class="font-black text-slate-900 text-base">Master List</h3>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="searchStudentInput" onkeyup="filterStudents()" placeholder="Search student name or ID..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-amber-400">
                    </div>

                    <!-- Add Student Button -->
                    <button type="button" onclick="openAddStudentModal()" class="px-4 py-2.5 bg-[#5c0d11] hover:bg-[#45090c] text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2 shrink-0">
                        <i class="fa-solid fa-user-plus text-amber-300"></i>
                        <span>Add Student</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#5c0d11] text-white text-[11px] font-bold uppercase tracking-wider rounded-t-xl">
                            <th class="py-3 px-4 rounded-tl-xl w-12">#</th>
                            <th class="py-3 px-4">Student ID / LRN</th>
                            <th class="py-3 px-4">Full Name</th>
                            <th class="py-3 px-4">Gender</th>
                            <th class="py-3 px-4">Section / Strand</th>
                            <th class="py-3 px-4">Contact / Email</th>
                            <th class="py-3 px-4 rounded-tr-xl text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody" class="divide-y divide-slate-100">
                        <?php if(isset($students) && count($students) > 0): ?>
                            <?php $i = 1; foreach($students as $std): ?>
                                <tr class="hover:bg-slate-50/80 transition student-row">
                                    <td class="py-3.5 px-4 font-mono text-slate-400"><?php echo $i++; ?></td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                        <span class="bg-amber-50 text-amber-900 border border-amber-200 px-2 py-0.5 rounded text-[11px]">
                                            <?php echo e($std->id_number ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900 student-name">
                                        <?php echo e($std->last_name); ?>, <?php echo e($std->first_name); ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 font-semibold">
                                        <?php echo ($std->gender == 1 || strtolower($std->gender) == 'male') ? 'Male' : 'Female'; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 font-medium">
                                        <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] font-bold text-slate-700">
                                            <?php echo e($std->strand ?? 'STEM'); ?> - <?php echo e($std->section ?? 'Amber'); ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-500">
                                        <?php echo e($std->email ?? 'N/A'); ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button class="px-2.5 py-1 bg-slate-100 hover:bg-amber-100 text-slate-600 hover:text-amber-900 rounded-lg text-xs font-bold transition">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-16 text-center text-slate-400 font-medium">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-regular fa-folder-open text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-500">No student records found in the database.</p>
                                    <p class="text-xs text-slate-400 mt-1">Click the "+ Add Student" button to register new students.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer indicator -->
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <i class="fa-solid fa-circle-info text-amber-500"></i>
                <span>List displays all registered users with the Student Role (role_id = 3).</span>
            </div>

        </div>

    </div>

    <!-- ========================================== -->
    <!-- ADD STUDENT MODAL -->
    <!-- ========================================== -->
    <div id="addStudentModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-[#5c0d11] flex items-center justify-center font-bold">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-lg">Add New Student</h3>
                        <p class="text-xs text-slate-400 font-medium">Register a student to the SIATRACK database</p>
                    </div>
                </div>
                <button type="button" onclick="closeAddStudentModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form method="POST" action="{{ route('teacher.students.store') }}">
                @csrf
                <div class="space-y-4 text-xs">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">First Name *</label>
                            <input type="text" name="first_name" required placeholder="e.g. Juan" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Last Name *</label>
                            <input type="text" name="last_name" required placeholder="e.g. Dela Cruz" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Student ID / LRN *</label>
                            <input type="text" name="id_number" required placeholder="e.g. STD-2026-001" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-mono font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Gender *</label>
                            <select name="gender" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Email Address *</label>
                        <input type="email" name="email" required placeholder="student@sia.edu.ph" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Strand / Track</label>
                            <select name="strand" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                                <option value="STEM">STEM</option>
                                <option value="ABM">ABM</option>
                                <option value="HUMSS">HUMSS</option>
                                <option value="TVL">TVL</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Section</label>
                            <input type="text" name="section" placeholder="e.g. Amber" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Default Password <span class="text-slate-400 font-normal">(Optional: Default is password123)</span></label>
                        <input type="password" name="password" placeholder="Leave empty for password123" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-400 font-semibold text-slate-800">
                    </div>

                </div>

                <!-- Modal Actions -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeAddStudentModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#5c0d11] hover:bg-[#45090c] text-white font-bold rounded-xl shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                        Save Student
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Live Search Script & Modal Toggle -->
    <script>
        function openAddStudentModal() {
            document.getElementById('addStudentModal').classList.remove('hidden');
        }

        function closeAddStudentModal() {
            document.getElementById('addStudentModal').classList.add('hidden');
        }

        function filterStudents() {
            let input = document.getElementById('searchStudentInput').value.toLowerCase();
            let rows = document.querySelectorAll('.student-row');

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
</body>
</html>