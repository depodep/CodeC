@extends('layouts.app')

@section('title', 'NFC Card Binding - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-slate-100 to-zinc-100 relative" 
     x-data="{ 
         searchQuery: decodeURIComponent('{{ request('student_name', '') }}').replace(/\+/g, ' '), 
         selectedStudent: '{{ request('student_id', '') }}', 
         tagId: (function() {
             let selId = '{{ request('student_id', '') }}';
             if (!selId) return '';
             @foreach($users as $st)
                 if (String('{{ $st->id }}') === String(selId)) {
                     return '{{ $st->nfcCard ? strtoupper(trim($st->nfcCard->tag_id)) : '' }}';
                 }
             @endforeach
             return '';
         })(),
         showDuplicateModal: false,
         showLockedModal: false,
         showUnbindModal: false,
         openDropdown: false,
         registrySearch: '',
         conflictDetails: { uid: '', studentName: '', lrn: '' },
         unbindData: { id: '', name: '', uid: '' },
         
         showToast: {{ session('success') || session('duplicate_error') || session('error') ? 'true' : 'false' }},
         toastMessage: '{{ session('success') ?? session('duplicate_error') ?? session('error') ?? '' }}',
         toastType: '{{ session('duplicate_error') || session('error') ? 'error' : 'success' }}',

         allStudents: [
             @foreach($users as $student)
                 { 
                     id: '{{ $student->id }}', 
                     name: '{{ addslashes($student->last_name) }}, {{ addslashes($student->first_name) }}', 
                     lrn: '{{ $student->id_number ?? 'Unassigned ID' }}',
                     hasCard: {{ $student->nfcCard ? 'true' : 'false' }},
                     cardUid: '{{ $student->nfcCard ? strtoupper(trim($student->nfcCard->tag_id)) : '' }}'
                 },
             @endforeach
         ],

         get filteredStudents() {
             if (!this.searchQuery) return this.allStudents;
             let query = this.searchQuery.toLowerCase();
             return this.allStudents.filter(s => s.name.toLowerCase().includes(query) || s.lrn.toLowerCase().includes(query));
         },

         boundCardsList: [
             @foreach($boundCards as $card)
             {
                 id: '{{ $card->id }}',
                 tag_id: '{{ strtoupper(trim($card->tag_id)) }}',
                 user_id: '{{ $card->user_id }}',
                 student_name: '{{ addslashes(($card->user->last_name ?? '') . ', ' . ($card->user->first_name ?? '')) }}',
                 lrn: '{{ $card->user->id_number ?? 'Unassigned' }}',
                 date: '{{ $card->created_at ? $card->created_at->format('M d, Y h:i A') : 'N/A' }}'
             },
             @endforeach
         ],

         get filteredBoundCards() {
             if (!this.registrySearch) return this.boundCardsList;
             let query = this.registrySearch.toLowerCase();
             return this.boundCardsList.filter(c => 
                 c.student_name.toLowerCase().includes(query) || 
                 c.lrn.toLowerCase().includes(query) || 
                 c.tag_id.toLowerCase().includes(query)
             );
         },

         get existingCardForStudent() {
             if (!this.selectedStudent) return null;
             let boundMatch = this.boundCardsList.find(c => String(c.user_id) === String(this.selectedStudent));
             if (boundMatch) return boundMatch;
             let foundStudent = this.allStudents.find(s => String(s.id) === String(this.selectedStudent));
             if (foundStudent && foundStudent.hasCard) return { tag_id: foundStudent.cardUid };
             return null;
         },

         selectStudent(student) {
             this.selectedStudent = student.id;
             this.searchQuery = student.name + ' (' + student.lrn + ')';
             this.openDropdown = false;
             
             // Pinapanatili ang na-scan na tag kung wala pang card ang estudyante
             this.tagId = student.hasCard ? student.cardUid : (this.tagId || '');

             // Auto focus sa tag input kapag walang existing card ang student
             if (!student.hasCard) {
                 this.$nextTick(() => {
                     let tagInput = document.getElementById('hardware_tag_input');
                     if (tagInput) tagInput.focus();
                 });
             }
         },

         clearSelection() {
             this.searchQuery = '';
             this.selectedStudent = '';
             this.tagId = '';
             this.openDropdown = false;
         },

         copyUid(uid) {
             navigator.clipboard.writeText(uid);
             alert('Card UID ' + uid + ' copied to clipboard!');
         },

         openUnbindingModal(card) {
             this.unbindData = { id: card.id, name: card.student_name, uid: card.tag_id };
             this.showUnbindModal = true;
         },

         handleFormSubmit() {
             if (!this.selectedStudent) {
                 alert('Please select a student from the list before saving.');
                 return;
             }
             let selectedObj = this.allStudents.find(s => String(s.id) === String(this.selectedStudent));
             if (selectedObj && selectedObj.hasCard) {
                 this.showLockedModal = true;
                 return;
             }
             let cleanTag = this.tagId.trim().toUpperCase();
             if (!cleanTag) {
                 alert('Please tap an NFC card or enter a UID.');
                 return;
             }
             let duplicate = this.boundCardsList.find(c => c.tag_id === cleanTag && String(c.user_id) !== String(this.selectedStudent));
             if (duplicate) {
                 this.conflictDetails = { uid: cleanTag, studentName: duplicate.student_name, lrn: duplicate.lrn };
                 this.showDuplicateModal = true;
                 return;
             }
             this.$refs.bindingForm.submit();
         },

         initPolling() {
             setInterval(async () => {
                 let selectedObj = this.allStudents.find(s => String(s.id) === String(this.selectedStudent));
                 if (selectedObj && selectedObj.hasCard) return;
                 try {
                     let response = await fetch('/api/nfc/latest');
                     let data = await response.json();
                     let scannedUid = data.card_uid || data.tag_id || data.uid;
                     if (scannedUid && this.tagId !== scannedUid) {
                         this.tagId = scannedUid;
                     }
                 } catch (e) {}
             }, 1000);
         }
     }" 
     x-init="initPolling()">

    <!-- 1. UNIFORM TOAST MODAL -->
    <div x-show="showToast" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div class="bg-white border border-slate-100 shadow-2xl rounded-3xl p-8 max-w-sm w-full text-center space-y-5 animate-in fade-in zoom-in-95">
            <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-white shadow-xl text-2xl"
                 :class="toastType === 'success' ? 'bg-emerald-600 shadow-emerald-600/30' : 'bg-red-600 shadow-red-600/30'">
                <i class="fa-solid" :class="toastType === 'success' ? 'fa-check' : 'fa-triangle-exclamation'"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-black text-slate-900 tracking-tight" x-text="toastType === 'success' ? 'Action Successful!' : 'Notice'"></h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed" x-text="toastMessage"></p>
            </div>
            <button @click="showToast = false" type="button" class="w-full py-3.5 rounded-2xl text-white text-xs font-black uppercase tracking-wider transition shadow-lg cursor-pointer bg-slate-900 hover:bg-slate-800 shadow-slate-900/20">
                Okay, Got It
            </button>
        </div>
    </div>

    <!-- 2. UNIFORM LOCKED MODAL -->
    <div x-show="showLockedModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div @click.outside="showLockedModal = false" class="bg-white rounded-3xl border border-slate-100 p-8 max-w-md w-full shadow-2xl space-y-5 text-center relative animate-in fade-in zoom-in-95">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-2xl mx-auto shadow-sm">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-black text-slate-900 tracking-tight">Active Credential Locked</h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed">This user profile already possesses an active NFC hardware configuration.</p>
            </div>
            <button @click="showLockedModal = false" type="button" class="w-full py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-red-950/20 cursor-pointer transition">Understood</button>
        </div>
    </div>

    <!-- 3. UNIFORM UNBIND MODAL -->
    <div x-show="showUnbindModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div class="bg-white rounded-3xl border border-slate-100 p-8 max-w-md w-full shadow-2xl space-y-5 text-center relative animate-in fade-in zoom-in-95">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto shadow-sm">
                <i class="fa-solid fa-link-slash"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-black text-slate-900 tracking-tight">Authorize Card Unlinking</h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed">
                    Please select the reason for unlinking. Both options will unbind the card, but "Replacement" will redirect you to assign a new card immediately.
                </p>
            </div>
            
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-left space-y-3 text-xs">
                <div class="flex justify-between pb-2 border-b border-slate-200">
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Student Name:</span>
                    <span class="font-black text-slate-900" x-text="unbindData.name"></span>
                </div>
                <div class="flex justify-between pb-2 border-b border-slate-200">
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Card UID:</span>
                    <span class="font-black text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200" x-text="unbindData.uid"></span>
                </div>

                <!-- REASON SELECTION FORM -->
                <form id="unbindForm" :action="'{{ url('admin/nfc/binding') }}/' + unbindData.id" method="POST" class="space-y-2 pt-1">
                    @csrf
                    @method('DELETE')
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Reason for Unbinding:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-rose-500 transition text-xs font-bold text-slate-700">
                            <input type="radio" name="unbind_reason" value="lost" required class="text-rose-600 focus:ring-rose-500">
                            <span>Lost Card</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-rose-500 transition text-xs font-bold text-slate-700">
                            <input type="radio" name="unbind_reason" value="replacement" required class="text-rose-600 focus:ring-rose-500">
                            <span>Replacement</span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <button @click="showUnbindModal = false" type="button" class="w-full py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase tracking-wider cursor-pointer transition">
                    Cancel
                </button>
                <button type="submit" form="unbindForm" class="w-full py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-rose-950/20 cursor-pointer transition">
                    Confirm Unbind
                </button>
            </div>
        </div>
    </div>

    <!-- Top Executive Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-6 lg:px-10 py-5 flex items-center justify-between sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#8b1818] to-rose-700 text-white flex items-center justify-center text-base shadow-lg shadow-red-950/25 shrink-0">
                <i class="fa-solid fa-id-card-clip text-amber-300"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-900 tracking-tight">NFC Hardware Binding Directory</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">High-capacity contactless badge management system</p>
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="p-6 lg:p-10 w-full max-w-7xl mx-auto space-y-8 flex-1">
        
        <!-- SECTION 1: Big Full-Width Assignment Panel -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-8 lg:p-10 shadow-xl shadow-slate-200/50 space-y-6 w-full relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#8b1818] via-rose-600 to-amber-500"></div>

            <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                <div class="w-11 h-11 rounded-2xl bg-red-50 text-[#8b1818] border border-red-100 flex items-center justify-center text-base font-black shrink-0 shadow-xs">
                    <i class="fa-solid fa-terminal"></i>
                </div>
                <div>
                    <h3 class="text-base font-black uppercase tracking-wider text-slate-900">New Card Assignment Panel</h3>
                    <p class="text-xs text-slate-500 font-bold mt-0.5">Map student identity credentials directly to physical NFC hardware tags</p>
                </div>
            </div>

            <form x-ref="bindingForm" action="{{ route('admin.nfc.binding.store') }}" method="POST" class="space-y-6" @submit.prevent="handleFormSubmit()">
                @csrf
                <input type="hidden" name="user_id" :value="selectedStudent">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student Selector -->
                    <div class="space-y-2 relative">
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Select Student Account</label>
                        <div class="relative">
                            <input type="text" x-model="searchQuery" @focus="openDropdown = true" placeholder="Search student name or LRN..." autocomplete="off" required
                                   class="w-full bg-slate-50/70 border-2 border-slate-200/80 rounded-2xl px-4 py-4 pl-12 pr-10 text-sm font-bold text-slate-900 focus:outline-none focus:border-[#8b1818] focus:bg-white transition shadow-2xs">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400"><i class="fa-solid fa-magnifying-glass text-sm"></i></div>
                            <template x-if="searchQuery.length > 0">
                                <button type="button" @click="clearSelection()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer"><i class="fa-solid fa-xmark text-base"></i></button>
                            </template>
                        </div>

                        <!-- Dropdown -->
                        <div x-show="openDropdown && searchQuery.length > 0" @click.away="openDropdown = false" 
                             class="absolute left-0 right-0 z-30 mt-2 bg-white border-2 border-slate-200 rounded-2xl shadow-2xl max-h-64 overflow-y-auto divide-y divide-slate-100" style="display: none;" x-cloak>
                            <template x-for="student in filteredStudents" :key="student.id">
                                <div @click="selectStudent(student)" class="px-5 py-4 hover:bg-red-50/50 cursor-pointer transition flex items-center justify-between text-xs font-bold text-slate-800">
                                    <span class="text-sm font-extrabold" x-text="student.name"></span>    
                                    <span class="text-xs bg-slate-100 px-3 py-1 rounded-xl text-slate-700 font-black" x-text="student.lrn"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Hardware Card UID -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Hardware Card UID</label>
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200" x-show="!existingCardForStudent">
                                <i class="fa-solid fa-wifi mr-1 text-[9px] animate-pulse"></i> Ready for NFC tap
                            </span>
                        </div>
                        <div class="relative">
                            <input type="text" id="hardware_tag_input" name="tag_id" x-model="tagId" :readonly="existingCardForStudent !== null" placeholder="Awaiting card scan..." required autocomplete="off"
                                   @keydown.enter.prevent="if(!selectedStudent) { alert('Please select a student account first.'); } else { handleFormSubmit(); }"
                                   class="w-full border-2 rounded-2xl px-4 py-4 pl-12 text-sm font-bold uppercase tracking-wider transition shadow-2xs"
                                   :class="existingCardForStudent !== null ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed' : 'bg-slate-50/70 border-slate-200/80 text-slate-900 focus:outline-none focus:border-[#8b1818] focus:bg-white'">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400"><i class="fa-solid fa-microchip text-sm"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Warning box if card exists -->
                <template x-if="existingCardForStudent">
                    <div class="p-4 bg-amber-50 border-2 border-amber-200 rounded-2xl flex items-center gap-4 text-amber-900 text-xs">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg shrink-0"></i>
                        <p class="text-xs font-bold leading-relaxed">Student already bound to UID: <strong class="underline text-sm" x-text="existingCardForStudent.tag_id"></strong>. Input field is locked.</p>
                    </div>
                </template>

                <button type="submit" class="w-full py-4 rounded-2xl text-white text-xs font-black uppercase tracking-wider transition cursor-pointer bg-[#8b1818] hover:bg-[#731414] shadow-lg shadow-red-950/25 active:scale-[0.99] flex items-center justify-center gap-3"
                        :class="existingCardForStudent !== null ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''">
                    <i class="fa-solid fa-shield-check text-base text-amber-300"></i>Bind NFC Credential
                </button>
            </form>
        </div>

        <!-- SECTION 2: Full-Width Registry Directory Table with Unbind Action -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-200/50 overflow-hidden">
            <div class="p-6 lg:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 bg-white">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-sm font-black shadow-md">
                        <i class="fa-solid fa-database text-amber-300"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 tracking-tight">Active NFC Registry Directory</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">High-performance data grid supporting long student names and records</p>
                    </div>
                </div>

                <!-- Global Table Search -->
                <div class="relative w-full sm:w-80">
                    <input type="text" x-model="registrySearch" placeholder="Filter by name, LRN, or UID..." autocomplete="off"
                           class="w-full bg-slate-50/70 border-2 border-slate-200/80 rounded-2xl px-4 py-3 pl-11 pr-10 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#8b1818] focus:bg-white transition shadow-2xs">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400"><i class="fa-solid fa-magnifying-glass text-xs"></i></div>
                    <template x-if="registrySearch.length > 0">
                        <button type="button" @click="registrySearch = ''" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
                    </template>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-black tracking-wider text-xs border-y border-slate-200/80">
                            <th class="py-4 px-6">Hardware UID</th>
                            <th class="py-4 px-6">Student Name</th>
                            <th class="py-4 px-6">LRN</th>
                            <th class="py-4 px-6">Date Registered</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-800">
                        <template x-for="card in filteredBoundCards" :key="card.tag_id">
                            <tr class="hover:bg-red-50/20 transition group">
                                <td class="py-4 px-6 font-black text-slate-900">
                                    <span class="px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs shadow-2xs inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-microchip text-[10px] text-amber-700"></i>
                                        <span x-text="card.tag_id"></span>
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-extrabold text-slate-900 max-w-xs" :title="card.student_name">
                                    <div class="flex items-center gap-3">
                                        <span class="truncate" x-text="card.student_name"></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-600 font-bold" x-text="card.lrn"></td>
                                <td class="py-4 px-6 text-slate-500 font-medium" x-text="card.date"></td>
                                
                                <!-- Actions with Secure Unbind Button -->
                                <td class="py-4 px-6 text-right whitespace-nowrap space-x-2">
                                    <button type="button" @click="copyUid(card.tag_id)" class="px-3.5 py-2 bg-slate-100 hover:bg-[#8b1818] hover:text-white text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs" title="Copy UID">
                                        <i class="fa-regular fa-copy text-xs"></i> Copy
                                    </button>
                                    <button type="button" @click="openUnbindingModal(card)" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs" title="Unbind Card">
                                        <i class="fa-solid fa-link-slash text-xs"></i> Unbind
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredBoundCards.length === 0">
                            <tr>
                                <td colspan="5" class="py-16 text-center text-slate-400 font-bold space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-lg"><i class="fa-solid fa-folder-open"></i></div>
                                    <p class="text-xs">No active NFC records found in database.</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="p-5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between px-7">
                <span class="text-xs font-bold text-slate-500"></span>
                <span class="text-xs font-bold text-slate-600">
                    Total Records: <strong class="text-slate-900 font-black" x-text="filteredBoundCards.length"></strong>
                </span>
            </div>
        </div>
    </main>

    <!-- 4. UNIFORM CONFLICT MODAL -->
    <div x-show="showDuplicateModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4" style="display: none;" x-cloak>
        <div @click.outside="showDuplicateModal = false" class="bg-white rounded-3xl border border-slate-100 p-8 max-w-md w-full shadow-2xl space-y-5 text-center relative animate-in fade-in zoom-in-95">
            <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center text-2xl mx-auto shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-black text-slate-900 tracking-tight">Assignment Conflict Detected</h3>
                <p class="text-xs font-semibold text-slate-500 leading-relaxed">This identifier is already mapped to a separate user profile.</p>
            </div>
            <button @click="showDuplicateModal = false" type="button" class="w-full py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-red-950/20 cursor-pointer transition">Acknowledge & Retry</button>
        </div>
    </div>
</div>
@endsection