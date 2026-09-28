@extends('layouts.app')

@section('title', 'NFC Management - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50"
     x-data="nfcManager()"
     x-init="init()">

    {{-- ===== BIND NFC MODAL ===== --}}
    <div x-show="modal.open" x-cloak style="display:none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 md:p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl relative flex flex-col overflow-hidden"
             style="max-height: 90vh;"
             @click.outside="if(modal.step < 4) closeModal()">

            {{-- Colored top bar --}}
            <div class="h-1.5 w-full shrink-0"
                 :class="{
                     'bg-gradient-to-r from-[#8b1818] to-rose-500': modal.step <= 2,
                     'bg-gradient-to-r from-amber-400 to-yellow-500': modal.step === 3,
                     'bg-gradient-to-r from-emerald-500 to-green-400': modal.step === 4
                 }"></div>

            {{-- Close Button --}}
            <button @click="closeModal()" x-show="modal.step !== 4"
                    class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center z-10 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            {{-- STEP 1: Select Student --}}
            <div x-show="modal.step === 1" class="flex flex-col overflow-hidden" style="max-height: calc(90vh - 6px);">
                <div class="p-6 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-100 text-[#8b1818] flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-user-graduate text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-900">Select Student</h2>
                            <p class="text-xs text-slate-500 font-bold mt-0.5">Step 1 of 3 — Choose a student without an active NFC card</p>
                        </div>
                    </div>
                    {{-- Step indicator --}}
                    <div class="flex items-center gap-2 mt-4">
                        <span class="w-8 h-1.5 rounded-full bg-[#8b1818]"></span>
                        <span class="w-8 h-1.5 rounded-full bg-slate-200"></span>
                        <span class="w-8 h-1.5 rounded-full bg-slate-200"></span>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="p-4 border-b border-slate-100 flex gap-3 shrink-0">
                    <div class="relative flex-1">
                        <input type="text" x-model="modal.search"
                               placeholder="Search name or LRN..."
                               class="w-full text-xs font-bold pl-9 pr-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <button x-show="modal.search" @click="modal.search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                    <select x-model="modal.sectionFilter"
                            class="text-xs font-bold px-3 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer">
                        <option value="">All Sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->section_name }}">{{ $sec->section_name }} (Gr. {{ $sec->grade_level }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Student list --}}
                <div class="overflow-y-auto flex-1 divide-y divide-slate-50">
                    <template x-for="(student, index) in unboundStudentsFiltered" :key="student.id">
                        <button type="button" @click="selectStudent(student)"
                                class="w-full flex items-center justify-between px-5 py-3.5 hover:bg-red-50/50 transition cursor-pointer text-left group">
                            <div class="flex items-center gap-3">
                                <span class="w-5 text-right text-[10px] font-black text-slate-400" x-text="index + 1"></span>
                                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black shrink-0 group-hover:bg-[#8b1818] group-hover:text-white transition">
                                    <i class="fa-solid fa-user-graduate"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-black text-slate-900" x-text="student.name"></div>
                                    <div class="text-xs text-slate-500 font-bold" x-text="'Grade ' + student.grade + ' · ' + student.section"></div>
                                </div>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-lg" x-text="student.lrn"></span>
                        </button>
                    </template>
                    <template x-if="unboundStudentsFiltered.length === 0">
                        <div class="py-16 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-user-slash text-lg"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-400" x-text="unboundStudents.length === 0 ? 'All students already have NFC cards bound.' : 'No students match your search.'"></p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- STEP 2: Scan NFC Card --}}
            <div x-show="modal.step === 2" class="p-5 sm:p-6 md:p-7 space-y-4 md:space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-wifi text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900">Scan NFC Card</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Step 2 of 3 — Place card on the ACR122U reader</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="w-8 h-1.5 rounded-full bg-[#8b1818]"></span>
                    <span class="w-8 h-1.5 rounded-full bg-[#8b1818]"></span>
                    <span class="w-8 h-1.5 rounded-full bg-slate-200"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-500">Reader Status</div>
                        <div class="mt-1 text-xs font-black"
                             :class="nfc.bridgeStatus === 'online' ? 'text-emerald-700' : 'text-rose-700'"
                             x-text="nfc.bridgeStatus === 'online' ? 'Connected' : 'Disconnected'"></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-500">Scanning</div>
                        <div class="mt-1 text-xs font-black"
                             :class="nfc.uid ? 'text-emerald-700' : (nfc.bridgeStatus === 'online' ? 'text-amber-700' : 'text-rose-700')"
                             x-text="nfc.uid ? 'Card Detected' : (nfc.bridgeStatus === 'online' ? 'Waiting for Card' : 'Waiting for NFC Reader')"></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-500">Detected UID</div>
                        <div class="mt-1 text-xs font-black font-mono text-slate-800" x-text="nfc.uid || '—'"></div>
                    </div>
                </div>

                {{-- Selected student summary --}}
                <div class="p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#8b1818] text-white flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user-graduate text-sm"></i>
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900" x-text="modal.selectedStudent?.name"></div>
                        <div class="text-xs font-bold text-slate-500" x-text="'Grade ' + modal.selectedStudent?.grade + ' · ' + modal.selectedStudent?.section + ' · ' + modal.selectedStudent?.lrn"></div>
                    </div>
                    <button @click="modal.step = 1; modal.selectedStudent = null; stopPolling(false)" class="ml-auto text-xs font-bold text-slate-400 hover:text-slate-600 cursor-pointer transition">
                        Change
                    </button>
                </div>

                {{-- NFC Reader state --}}
                <div class="rounded-2xl border-2 overflow-hidden"
                     :class="nfc.uid ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50/50'">
                    <div class="px-5 py-4 flex items-center justify-between border-b"
                         :class="nfc.uid ? 'border-emerald-200' : 'border-amber-200'">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full animate-pulse"
                                 :class="nfc.uid ? 'bg-emerald-500' : 'bg-amber-500'"></div>
                            <span class="text-xs font-black uppercase tracking-wider"
                                  :class="nfc.uid ? 'text-emerald-800' : 'text-amber-800'"
                                  x-text="nfc.uid ? 'NFC Card Detected' : 'Waiting for NFC Card'"></span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">ACR122U / NFC Reader</span>
                    </div>
                    <div class="px-5 py-5">
                        <template x-if="!nfc.uid">
                            <div class="flex flex-col items-center py-4 gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-amber-100 border-2 border-amber-200 text-amber-600 flex items-center justify-center">
                                    <i class="fa-solid fa-id-card text-2xl"></i>
                                </div>
                                <p class="text-xs font-bold text-amber-800 text-center">Place the NFC card on the ACR122U reader surface.</p>
                            </div>
                        </template>
                        <template x-if="nfc.uid">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-check text-base"></i>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-wider text-emerald-700 mb-0.5">Detected UID</div>
                                    <div class="text-base font-black font-mono text-emerald-900" x-text="nfc.uid"></div>
                                </div>
                                <template x-if="!nfc.boundCard">
                                    <button @click="proceedToConfirm()"
                                            class="ml-auto px-4 py-2 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition cursor-pointer">
                                        Continue <i class="fa-solid fa-arrow-right ml-1"></i>
                                    </button>
                                </template>
                                <template x-if="nfc.boundCard">
                                    <button type="button" disabled
                                            class="ml-auto px-4 py-2 bg-slate-300 text-slate-600 text-xs font-black rounded-xl cursor-not-allowed">
                                        Use Different Card
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <template x-if="nfc.boundCard">
                    <div class="p-4 bg-rose-50 border-2 border-rose-200 rounded-2xl flex items-start gap-3 text-rose-800">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 shrink-0"></i>
                        <div class="text-xs font-bold">
                            <div>NFC card already bound</div>
                            <div class="font-semibold mt-0.5" x-text="nfc.boundCard.student_name"></div>
                            <div class="font-semibold mt-1 text-rose-700">Use a different card or replace the existing binding.</div>
                        </div>
                    </div>
                </template>
                <template x-if="nfc.uid && !nfc.boundCard">
                    <div class="p-4 bg-emerald-50 border-2 border-emerald-200 rounded-2xl flex items-start gap-3 text-emerald-800">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 shrink-0"></i>
                        <div class="text-xs font-bold">
                            <div>NFC card is available</div>
                            <div class="font-semibold mt-0.5">This card is not currently bound to another student.</div>
                        </div>
                    </div>
                </template>

                <div class="flex gap-3 pt-1">
                    <button @click="modal.step = 1; stopPolling()" class="flex-1 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition cursor-pointer">
                        <i class="fa-solid fa-arrow-left mr-1.5"></i> Back
                    </button>
                </div>
            </div>

            {{-- STEP 3: Confirm Binding --}}
            <div x-show="modal.step === 3" class="p-5 sm:p-6 md:p-7 space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-shield-check text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900">NFC Card Detected</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Step 3 of 3 — Review the selected student and detected card before binding</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-8 h-1.5 rounded-full bg-[#8b1818]"></span>
                    <span class="w-8 h-1.5 rounded-full bg-[#8b1818]"></span>
                    <span class="w-8 h-1.5 rounded-full bg-[#8b1818]"></span>
                </div>

                <div class="rounded-2xl border-2 border-slate-200 overflow-hidden divide-y divide-slate-100">
                    <div class="p-4 bg-slate-50">
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Selected Student</div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#8b1818] text-white flex items-center justify-center shrink-0 text-sm">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="font-black text-slate-900 text-sm" x-text="modal.selectedStudent?.name"></div>
                                <div class="text-xs font-bold text-slate-500" x-text="'Grade ' + modal.selectedStudent?.grade + ' · ' + modal.selectedStudent?.section"></div>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5" x-text="'LRN: ' + modal.selectedStudent?.lrn"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Detected UID</div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-microchip text-amber-600"></i>
                            <span class="font-black font-mono text-amber-900 text-base" x-text="nfc.uid"></span>
                        </div>
                    </div>
                </div>

                <template x-if="modal.error">
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-rose-800 text-xs font-bold">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 shrink-0"></i>
                        <span x-text="modal.error"></span>
                    </div>
                </template>

                <div class="flex gap-3">
                    <button @click="modal.step = 2; modal.error = ''; startPolling()" class="flex-1 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition cursor-pointer">
                        <i class="fa-solid fa-rotate mr-1.5"></i> Re-scan Card
                    </button>
                    <button @click="bindCard()" :disabled="modal.loading"
                            class="flex-1 py-3 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black transition cursor-pointer disabled:opacity-60 flex items-center justify-center gap-2">
                        <template x-if="modal.loading">
                            <i class="fa-solid fa-circle-notch animate-spin"></i>
                        </template>
                        <template x-if="!modal.loading">
                            <i class="fa-solid fa-shield-check text-amber-300"></i>
                        </template>
                        <span x-text="modal.loading ? 'Binding...' : 'Bind NFC Credential'"></span>
                    </button>
                </div>
            </div>

            {{-- STEP 4: Success --}}
            <div x-show="modal.step === 4" class="p-5 sm:p-6 md:p-8 text-center space-y-5">
                <div class="w-18 h-18 rounded-3xl bg-emerald-100 border-2 border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto w-20 h-20">
                    <i class="fa-solid fa-circle-check text-4xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900">NFC Card Bound!</h2>
                    <p class="text-xs text-slate-500 font-bold mt-1">The NFC credential has been successfully assigned.</p>
                </div>

                <div class="rounded-2xl border-2 border-emerald-200 bg-emerald-50 overflow-hidden divide-y divide-emerald-100 text-left">
                    <div class="px-5 py-3 flex justify-between items-center">
                        <span class="text-[10px] font-black uppercase text-emerald-700">Student</span>
                        <span class="text-sm font-black text-slate-900" x-text="modal.selectedStudent?.name"></span>
                    </div>
                    <div class="px-5 py-3 flex justify-between items-center">
                        <span class="text-[10px] font-black uppercase text-emerald-700">Grade & Section</span>
                        <span class="text-xs font-bold text-slate-700" x-text="'Grade ' + modal.selectedStudent?.grade + ' · ' + modal.selectedStudent?.section"></span>
                    </div>
                    <div class="px-5 py-3 flex justify-between items-center">
                        <span class="text-[10px] font-black uppercase text-emerald-700">Card UID</span>
                        <span class="text-sm font-black font-mono text-emerald-900" x-text="nfc.uid"></span>
                    </div>
                </div>

                <button @click="closeModal()" class="w-full py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    {{-- ===== REMOVE NFC MODAL ===== --}}
    <div x-show="removeModal.open" x-cloak style="display:none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative overflow-hidden"
             @click.outside="if(!removeModal.loading) removeModal.open = false">
            <div class="h-1.5 w-full bg-gradient-to-r from-rose-600 to-red-500"></div>
            <button @click="removeModal.open = false" x-show="!removeModal.loading"
                    class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center z-10 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
            <div class="p-5 sm:p-6 md:p-7 space-y-5 text-center">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto">
                    <i class="fa-solid fa-link-slash"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Remove NFC Binding?</h3>
                    <p class="text-xs text-slate-500 font-semibold mt-1 leading-relaxed">Removing this binding will make the student available again for a new NFC card assignment.</p>
                </div>

                <div class="rounded-2xl border-2 border-slate-200 overflow-hidden divide-y divide-slate-100 text-left">
                    <div class="px-5 py-3 flex justify-between">
                        <span class="text-[10px] font-black uppercase text-slate-400">Student</span>
                        <span class="text-xs font-black text-slate-900" x-text="removeModal.card?.student_name"></span>
                    </div>
                    <div class="px-5 py-3 flex justify-between">
                        <span class="text-[10px] font-black uppercase text-slate-400">Card UID</span>
                        <span class="text-xs font-black font-mono text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200" x-text="removeModal.card?.tag_id"></span>
                    </div>
                </div>

                <template x-if="removeModal.error">
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-800" x-text="removeModal.error"></div>
                </template>

                <div class="flex gap-3">
                    <button @click="removeModal.open = false" :disabled="removeModal.loading"
                            class="flex-1 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition cursor-pointer disabled:opacity-60">
                        Cancel
                    </button>
                    <button @click="removeCard()" :disabled="removeModal.loading"
                            class="flex-1 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black transition cursor-pointer disabled:opacity-60 flex items-center justify-center gap-2">
                        <template x-if="removeModal.loading">
                            <i class="fa-solid fa-circle-notch animate-spin"></i>
                        </template>
                        <span x-text="removeModal.loading ? 'Removing...' : 'Confirm Remove'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== REPLACE NFC MODAL ===== --}}
    <div x-show="replaceModal.open" x-cloak style="display:none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative overflow-hidden"
             @click.outside="if(!replaceModal.loading && replaceModal.step !== 2) replaceModal.open = false">
            <div class="h-1.5 w-full"
                 :class="replaceModal.step === 3 ? 'bg-gradient-to-r from-emerald-500 to-green-400' : 'bg-gradient-to-r from-indigo-500 to-blue-400'"></div>
            <button @click="replaceModal.open = false" x-show="!replaceModal.loading"
                    class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center z-10 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            {{-- Replace Step 1: Confirm replacement start --}}
            <div x-show="replaceModal.step === 1" class="p-5 sm:p-6 md:p-7 space-y-5 text-center">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-2xl mx-auto">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Replace NFC Card</h3>
                    <p class="text-xs text-slate-500 font-semibold mt-1 leading-relaxed">The existing card binding will be replaced. Tap a new card to assign.</p>
                </div>
                <div class="rounded-2xl border-2 border-slate-200 overflow-hidden divide-y divide-slate-100 text-left">
                    <div class="px-5 py-3 flex justify-between">
                        <span class="text-[10px] font-black uppercase text-slate-400">Student</span>
                        <span class="text-xs font-black text-slate-900" x-text="replaceModal.card?.student_name"></span>
                    </div>
                    <div class="px-5 py-3 flex justify-between">
                        <span class="text-[10px] font-black uppercase text-slate-400">Current UID</span>
                        <span class="text-xs font-black font-mono text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200" x-text="replaceModal.card?.tag_id"></span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="replaceModal.open = false" class="flex-1 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition cursor-pointer">Cancel</button>
                    <button @click="startReplace()" class="flex-1 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition cursor-pointer">
                        <i class="fa-solid fa-id-card mr-1.5"></i> Scan New Card
                    </button>
                </div>
            </div>

            {{-- Replace Step 2: Wait for new NFC --}}
            <div x-show="replaceModal.step === 2" class="p-5 sm:p-6 md:p-7 space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-wifi text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Tap New NFC Card</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Place the new card on the ACR122U reader</p>
                    </div>
                </div>
                <div class="rounded-2xl border-2 overflow-hidden" :class="nfc.uid && nfc.uid !== replaceModal.card?.tag_id ? 'border-emerald-200 bg-emerald-50' : 'border-indigo-200 bg-indigo-50/50'">
                    <div class="px-5 py-3 flex items-center gap-2 border-b" :class="nfc.uid && nfc.uid !== replaceModal.card?.tag_id ? 'border-emerald-200' : 'border-indigo-200'">
                        <div class="w-2.5 h-2.5 rounded-full animate-pulse" :class="nfc.uid && nfc.uid !== replaceModal.card?.tag_id ? 'bg-emerald-500' : 'bg-indigo-500'"></div>
                        <span class="text-xs font-black uppercase" :class="nfc.uid && nfc.uid !== replaceModal.card?.tag_id ? 'text-emerald-800' : 'text-indigo-800'"
                              x-text="nfc.uid && nfc.uid !== replaceModal.card?.tag_id ? 'New Card Detected' : 'Waiting for new card...'"></span>
                    </div>
                    <div class="px-5 py-4">
                        <template x-if="!nfc.uid || nfc.uid === replaceModal.card?.tag_id">
                            <p class="text-xs font-bold text-indigo-700 text-center py-4">
                                <template x-if="nfc.uid === replaceModal.card?.tag_id">
                                    <span>Same card detected — please use a different NFC card.</span>
                                </template>
                                <template x-if="!nfc.uid">
                                    <span>Place a new NFC card on the reader...</span>
                                </template>
                            </p>
                        </template>
                        <template x-if="nfc.uid && nfc.uid !== replaceModal.card?.tag_id">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase text-emerald-700 mb-0.5">New Card UID</div>
                                    <div class="font-black font-mono text-emerald-900 text-sm" x-text="nfc.uid"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <template x-if="replaceModal.error">
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-800" x-text="replaceModal.error"></div>
                </template>
                <div class="flex gap-3">
                    <button @click="replaceModal.step = 1; stopPolling()" class="flex-1 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition cursor-pointer">Cancel</button>
                    <button @click="confirmReplace()" :disabled="!nfc.uid || nfc.uid === replaceModal.card?.tag_id || replaceModal.loading"
                            class="flex-1 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2">
                        <template x-if="replaceModal.loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <span x-text="replaceModal.loading ? 'Replacing...' : 'Confirm Replace'"></span>
                    </button>
                </div>
            </div>

            {{-- Replace Step 3: Success --}}
            <div x-show="replaceModal.step === 3" class="p-5 sm:p-6 md:p-8 text-center space-y-5">
                <div class="w-20 h-20 rounded-3xl bg-emerald-100 border-2 border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-circle-check text-4xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900">Card Replaced!</h3>
                    <p class="text-xs text-slate-500 font-bold mt-1">NFC card successfully updated.</p>
                </div>
                <button @click="replaceModal.open = false; stopPolling()" class="w-full py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    {{-- ===== PAGE HEADER ===== --}}
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex items-center justify-between sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">NFC Management</h1>
                <p class="text-xs text-slate-500 font-bold mt-0.5">ACR122U Card Binding & Registry</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- NFC Reader Status --}}
            <div class="flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-bold"
                 :class="nfc.bridgeStatus === 'online' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-700'">
                <div class="w-2 h-2 rounded-full"
                     :class="nfc.bridgeStatus === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></div>
                <span x-text="nfc.bridgeStatus === 'online' ? 'Reader Connected' : 'Reader Disconnected'"></span>
            </div>

            <a href="{{ route('admin.nfc.bridge.package') }}"
               class="hidden lg:inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition"
               title="Download the NFC bridge and Windows runner for the computer connected to the ACR122U reader">
                <i class="fa-solid fa-download"></i>
                Download Bridge
            </a>

            <button @click="openBindModal()"
                    class="flex items-center gap-2.5 px-5 py-2.5 bg-[#8b1818] hover:bg-[#731414] text-white text-xs font-black rounded-xl transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-id-card text-amber-300"></i>
                Bind NFC Card
            </button>
        </div>
    </header>

    {{-- ===== MAIN CONTENT ===== --}}
    <main class="flex-1 p-6 lg:px-10 lg:py-8 space-y-6 max-w-[1600px] mx-auto w-full">

        {{-- Flash notifications --}}
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-2 border-emerald-200 text-emerald-900 text-xs font-bold rounded-2xl flex items-center justify-between"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                {{ session('success') }}
            </div>
            <button @click="show = false" class="text-emerald-600 hover:text-emerald-900 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
        @endif
        @if(session('error') || session('duplicate_error'))
        <div class="p-4 bg-rose-50 border-2 border-rose-200 text-rose-900 text-xs font-bold rounded-2xl flex items-center justify-between"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base shrink-0"></i>
                {{ session('error') ?? session('duplicate_error') }}
            </div>
            <button @click="show = false" class="text-rose-600 hover:text-rose-900 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
        @endif

        {{-- STATS ROW --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Total Students</div>
                <div class="text-2xl font-black text-slate-900">{{ $students->count() }}</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Cards Bound</div>
                <div class="text-2xl font-black text-[#8b1818]" x-text="registry.length"></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Unbound</div>
                <div class="text-2xl font-black text-amber-600" x-text="unboundStudents.length"></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Coverage</div>
                <div class="text-2xl font-black text-slate-900" x-text="allStudents.length > 0 ? Math.round((registry.length / allStudents.length) * 100) + '%' : '0%'"></div>
            </div>
        </div>

        {{-- ACTIVE NFC REGISTRY --}}
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            {{-- Registry header --}}
            <div class="p-5 lg:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-amber-300 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900">Active NFC Registry</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">
                            <span x-text="registry.length"></span> bound &nbsp;·&nbsp;
                            <span x-text="unboundStudents.length"></span> unbound
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-72">
                        <input type="text" x-model="registrySearch"
                               placeholder="Search name, LRN, or UID..."
                               class="w-full text-xs font-bold pl-9 pr-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <button x-show="registrySearch" @click="registrySearch = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                    <select x-model="registrySection"
                            @change="registrySection = availableRegistrySections.includes(registrySection) ? registrySection : ''"
                            class="text-xs font-bold px-3 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer whitespace-nowrap">
                        <option value="">All Sections</option>
                        <template x-for="section in availableRegistrySections" :key="'registry-section-' + section">
                            <option :value="section" x-text="section"></option>
                        </template>
                    </select>
                    <select x-model="registryGrade" @change="registrySection = ''"
                            class="text-xs font-bold px-3 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer whitespace-nowrap">
                        <option value="">All Grades</option>
                        <template x-for="grade in gradeLevels" :key="'registry-grade-' + grade">
                            <option :value="grade" x-text="'Grade ' + grade"></option>
                        </template>
                    </select>
                    <button @click="clearRegistryFilters()"
                            x-show="registrySearch || registrySection || registryGrade"
                            class="text-xs font-black px-3 py-2.5 rounded-xl border border-slate-300 text-slate-600 bg-white hover:bg-slate-50 transition cursor-pointer">
                        Clear
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-black tracking-wider text-[10px] border-y border-slate-200">
                            <th class="py-3.5 px-5 w-14">#</th>
                            <th class="py-3.5 px-5">NFC UID</th>
                            <th class="py-3.5 px-5">Student Name</th>
                            <th class="py-3.5 px-5">Grade</th>
                            <th class="py-3.5 px-5">Section</th>
                            <th class="py-3.5 px-5">LRN</th>
                            <th class="py-3.5 px-5">Date Registered</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(card, index) in filteredRegistry" :key="card.id">
                            <tr class="hover:bg-red-50/30 transition">
                                <td class="py-3.5 px-5 text-xs font-black text-slate-400" x-text="index + 1"></td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-900 text-xs font-black rounded-xl">
                                        <i class="fa-solid fa-microchip text-amber-600 text-[10px]"></i>
                                        <span x-text="card.tag_id"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-[10px] shrink-0 font-black">
                                            <i class="fa-solid fa-user-graduate"></i>
                                        </div>
                                        <span class="text-xs font-extrabold text-slate-900" x-text="card.student_name"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="text-xs font-bold text-slate-600" x-text="card.grade ? 'Grade ' + card.grade : '—'"></span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-[10px] font-black rounded-lg" x-text="card.section || '—'"></span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="text-xs font-mono font-bold text-slate-500" x-text="card.lrn || '—'"></span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="text-xs font-medium text-slate-400" x-text="card.date"></span>
                                </td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-2">
                                    <button @click="openReplaceModal(card)"
                                            class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 border border-indigo-200 rounded-lg text-[10px] font-black transition cursor-pointer inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-arrows-rotate text-[9px]"></i> Replace
                                    </button>
                                    <button @click="openRemoveModal(card)"
                                            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 border border-rose-200 rounded-lg text-[10px] font-black transition cursor-pointer inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-link-slash text-[9px]"></i> Remove
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredRegistry.length === 0">
                            <tr>
                                <td colspan="8" class="py-16 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-400"
                                       x-text="registry.length === 0 ? 'No active NFC records found.' : 'No records match your search.'"></p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400">Showing <span x-text="filteredRegistry.length"></span> of <span x-text="registry.length"></span> records</span>
                <button @click="openBindModal()" x-show="unboundStudents.length > 0"
                        class="flex items-center gap-2 text-xs font-bold text-[#8b1818] hover:text-[#731414] transition cursor-pointer">
                    <i class="fa-solid fa-plus-circle"></i> Bind New Card
                </button>
            </div>
        </div>

        {{-- UNBOUND STUDENTS QUICK VIEW --}}
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 lg:p-6 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900">Unbound Students</h2>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">Students available for NFC card assignment</p>
                    </div>
                </div>
                <span class="text-xs font-black text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl" x-text="unboundStudents.length + ' remaining'"></span>
            </div>
            <div class="px-5 lg:px-6 py-4 border-b border-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:w-72">
                        <input type="text" x-model="unboundSearch"
                               placeholder="Search name or LRN..."
                               class="w-full text-xs font-bold pl-9 pr-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <button x-show="unboundSearch" @click="unboundSearch = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                    <select x-model="unboundGrade" @change="unboundSection = ''"
                            class="text-xs font-bold px-3 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer whitespace-nowrap">
                        <option value="">All Grades</option>
                        <template x-for="grade in gradeLevels" :key="'unbound-grade-' + grade">
                            <option :value="grade" x-text="'Grade ' + grade"></option>
                        </template>
                    </select>
                    <select x-model="unboundSection"
                            @change="unboundSection = availableUnboundSections.includes(unboundSection) ? unboundSection : ''"
                            class="text-xs font-bold px-3 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] focus:bg-white transition cursor-pointer whitespace-nowrap">
                        <option value="">All Sections</option>
                        <template x-for="section in availableUnboundSections" :key="'unbound-section-' + section">
                            <option :value="section" x-text="section"></option>
                        </template>
                    </select>
                    <button @click="clearUnboundFilters()"
                            x-show="unboundSearch || unboundSection || unboundGrade"
                            class="text-xs font-black px-3 py-2.5 rounded-xl border border-slate-300 text-slate-600 bg-white hover:bg-slate-50 transition cursor-pointer">
                        Clear
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-black tracking-wider text-[10px] border-y border-slate-200">
                            <th class="py-3.5 px-5 w-14">#</th>
                            <th class="py-3.5 px-5">Student Name</th>
                            <th class="py-3.5 px-5">Grade</th>
                            <th class="py-3.5 px-5">Section</th>
                            <th class="py-3.5 px-5">LRN</th>
                            <th class="py-3.5 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(student, index) in filteredUnboundStudents" :key="student.id">
                            <tr class="hover:bg-amber-50/30 transition">
                                <td class="py-3.5 px-5 text-xs font-black text-slate-400" x-text="index + 1"></td>
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] shrink-0">
                                            <i class="fa-solid fa-user-graduate"></i>
                                        </div>
                                        <span class="text-xs font-extrabold text-slate-900" x-text="student.name"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 text-xs font-bold text-slate-600" x-text="'Grade ' + student.grade"></td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-[10px] font-black rounded-lg" x-text="student.section"></span>
                                </td>
                                <td class="py-3.5 px-5 text-xs font-mono font-bold text-slate-400" x-text="student.lrn"></td>
                                <td class="py-3.5 px-5 text-right">
                                    <button @click="openBindModalFor(student)"
                                            class="px-3 py-1.5 bg-[#8b1818] hover:bg-[#731414] text-white text-[10px] font-black rounded-lg transition cursor-pointer inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-id-card text-amber-300 text-[9px]"></i> Bind Card
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="filteredUnboundStudents.length === 0">
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-3 text-lg">
                                        <i class="fa-solid" :class="unboundStudents.length === 0 ? 'fa-check-circle' : 'fa-filter-circle-xmark'"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-400"
                                       x-text="unboundStudents.length === 0 ? 'All students have NFC cards bound.' : 'No students match your filters.'"></p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>
@endsection

@push('scripts')
@php
    $studentPayload = $students->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->last_name . ', ' . $s->first_name,
        'grade' => $s->grade_level ?? '',
        'section' => $s->section ?? '',
        'lrn' => $s->id_number ?? 'N/A',
        'hasCrd' => (bool) $s->nfcCard,
    ])->values();

    $registryPayload = $boundCards->map(fn ($c) => [
        'id' => $c->id,
        'tag_id' => strtoupper(trim($c->tag_id)),
        'user_id' => $c->user_id,
        'student_name' => ($c->user?->last_name ?? '') . ', ' . ($c->user?->first_name ?? ''),
        'grade' => $c->user?->grade_level ?? '',
        'section' => $c->user?->section ?? '',
        'lrn' => $c->user?->id_number ?? 'N/A',
        'date' => $c->created_at?->format('M d, Y h:i A') ?? 'N/A',
    ])->values();
@endphp
<script>
function nfcManager() {
    return {
        // --- DATA ---
        allStudents: @json($studentPayload),

        registry: @json($registryPayload),

        // --- NFC bridge state ---
        nfc: {
            uid: '',
            boundCard: null,
            bridgeStatus: 'disconnected',
            statusFailures: 0,
            pollTimer: null,
        },

        // --- Modal state ---
        modal: {
            open: false,
            step: 1,
            search: '',
            sectionFilter: '',
            selectedStudent: null,
            loading: false,
            error: '',
        },
        removeModal: { open: false, card: null, loading: false, error: '' },
        replaceModal: { open: false, card: null, step: 1, loading: false, error: '' },

        // --- Registry filter ---
        registrySearch: '',
        registrySection: '',
        registryGrade: '',

        // --- Unbound quick-view filter ---
        unboundSearch: '',
        unboundSection: '',
        unboundGrade: '',

        // --- COMPUTED ---
        get gradeLevels() {
            const grades = this.allStudents
                .map(s => String(s.grade || '').trim())
                .filter(Boolean);

            return [...new Set(grades)].sort((a, b) => Number(a) - Number(b));
        },

        get availableRegistrySections() {
            const sections = this.registry
                .filter(card => !this.registryGrade || String(card.grade) === String(this.registryGrade))
                .map(card => String(card.section || '').trim())
                .filter(Boolean);

            return [...new Set(sections)].sort((a, b) => a.localeCompare(b));
        },

        get availableUnboundSections() {
            const sections = this.unboundStudents
                .filter(student => !this.unboundGrade || String(student.grade) === String(this.unboundGrade))
                .map(student => String(student.section || '').trim())
                .filter(Boolean);

            return [...new Set(sections)].sort((a, b) => a.localeCompare(b));
        },

        get unboundStudents() {
            const boundIds = this.registry.map(c => String(c.user_id));
            return this.allStudents.filter(s => !boundIds.includes(String(s.id)));
        },

        get filteredUnboundStudents() {
            let list = this.unboundStudents;
            if (this.unboundSection) {
                list = list.filter(s => s.section === this.unboundSection);
            }
            if (this.unboundGrade) {
                list = list.filter(s => String(s.grade) === String(this.unboundGrade));
            }
            if (this.unboundSearch) {
                const q = this.unboundSearch.toLowerCase();
                list = list.filter(s =>
                    s.name.toLowerCase().includes(q) ||
                    s.lrn.toLowerCase().includes(q)
                );
            }
            return list;
        },

        get unboundStudentsFiltered() {
            let list = this.unboundStudents;
            if (this.modal.sectionFilter) {
                list = list.filter(s => s.section === this.modal.sectionFilter);
            }
            if (this.modal.search) {
                const q = this.modal.search.toLowerCase();
                list = list.filter(s => s.name.toLowerCase().includes(q) || s.lrn.toLowerCase().includes(q));
            }
            return list;
        },

        get filteredRegistry() {
            let list = this.registry;
            if (this.registrySection) {
                list = list.filter(c => c.section === this.registrySection);
            }
            if (this.registryGrade) {
                list = list.filter(c => String(c.grade) === String(this.registryGrade));
            }
            if (this.registrySearch) {
                const q = this.registrySearch.toLowerCase();
                list = list.filter(c =>
                    c.student_name.toLowerCase().includes(q) ||
                    c.lrn.toLowerCase().includes(q) ||
                    c.tag_id.toLowerCase().includes(q)
                );
            }
            return list;
        },

        clearRegistryFilters() {
            this.registrySearch = '';
            this.registrySection = '';
            this.registryGrade = '';
        },

        clearUnboundFilters() {
            this.unboundSearch = '';
            this.unboundSection = '';
            this.unboundGrade = '';
        },

        // --- INIT ---
        init() {
            this.nfc.bridgeStatus = 'disconnected';
            const selectedStudentId = @json($selectedStudentId ?? null);
            if (selectedStudentId) {
                const student = this.allStudents.find(item => String(item.id) === String(selectedStudentId));
                if (student) this.openBindModalFor(student);
            }
            this.checkBridgeStatus();
            setInterval(() => this.checkBridgeStatus(), 2000);
        },

        async checkBridgeStatus() {
            try {
                const res = await fetch('/api/nfc/latest', { signal: AbortSignal.timeout(2000) });
                if (res.ok) {
                    const data = await res.json();
                    if (data.bridge_online) {
                        this.nfc.statusFailures = 0;
                        this.nfc.bridgeStatus = 'online';
                    } else {
                        this.nfc.statusFailures++;
                        if (this.nfc.statusFailures >= 3) this.nfc.bridgeStatus = 'disconnected';
                    }
                    return;
                }
                this.nfc.statusFailures++;
                if (this.nfc.statusFailures >= 3) this.nfc.bridgeStatus = 'disconnected';
            } catch {
                this.nfc.statusFailures++;
                if (this.nfc.statusFailures >= 3) this.nfc.bridgeStatus = 'disconnected';
            }
        },

        // --- NFC POLLING ---
        startPolling(preserveUid = false) {
            if (!preserveUid) {
                this.nfc.uid = '';
                this.nfc.boundCard = null;
            }
            this.clearTapCache();
            this.nfc.pollTimer = setInterval(async () => {
                try {
                    const res = await fetch('/api/nfc/latest');
                    const data = await res.json();
                    const uid = data.card_uid || data.tag_id || data.uid || '';
                    this.nfc.bridgeStatus = data.bridge_online ? 'online' : 'disconnected';
                    if (uid && uid !== this.nfc.uid) {
                        this.nfc.uid = uid;
                    }
                    if (uid) {
                        this.nfc.boundCard = data.binding || null;
                    }
                } catch { this.nfc.bridgeStatus = 'disconnected'; }
            }, 800);
        },

        stopPolling(clearUid = true) {
            if (this.nfc.pollTimer) {
                clearInterval(this.nfc.pollTimer);
                this.nfc.pollTimer = null;
            }
            if (clearUid) {
                this.nfc.uid = '';
                this.nfc.boundCard = null;
            }
        },

        async clearTapCache() {
            try {
                await fetch('{{ route("admin.nfc.clear-tap") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
            } catch {}
        },

        // --- BIND MODAL ---
        openBindModal() {
            this.modal = { open: true, step: 1, search: '', sectionFilter: '', selectedStudent: null, loading: false, error: '' };
            this.stopPolling();
        },

        openBindModalFor(student) {
            this.modal = { open: true, step: 2, search: '', sectionFilter: '', selectedStudent: student, loading: false, error: '' };
            this.startPolling();
        },

        selectStudent(student) {
            this.modal.selectedStudent = student;
            this.modal.step = 2;
            this.startPolling(Boolean(this.nfc.uid));
        },

        proceedToConfirm() {
            if (!this.nfc.uid || this.nfc.boundCard) {
                return;
            }
            this.stopPolling(false);
            this.modal.step = 3;
        },

        async bindCard() {
            if (this.modal.loading || !this.modal.selectedStudent?.id || !this.nfc.uid || this.nfc.boundCard) {
                return;
            }

            this.modal.loading = true;
            this.modal.error = '';
            this.stopPolling(false);

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 10000);

            try {
                const res = await fetch('{{ route("admin.nfc.binding.ajax") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: this.modal.selectedStudent.id,
                        tag_id: this.nfc.uid,
                    }),
                    signal: controller.signal,
                });
                const responseText = await res.text();
                let data;

                try {
                    data = JSON.parse(responseText);
                } catch {
                    throw new Error(`The server returned an invalid response (${res.status}).`);
                }

                if (!res.ok || !data.success) {
                    this.modal.error = data.message || 'Binding failed. Please try again.';
                    return;
                }
                // Update registry in-memory
                this.registry.push(data.card);
                this.modal.step = 4;
            } catch (e) {
                this.modal.error = e.name === 'AbortError'
                    ? 'Binding timed out after 10 seconds. Check the Laravel server and database, then try again.'
                    : (e.message || 'Network error. Please check connection and retry.');
            } finally {
                clearTimeout(timeout);
                this.modal.loading = false;
            }
        },

        closeModal() {
            this.modal.open = false;
            this.stopPolling();
        },

        // --- REMOVE MODAL ---
        openRemoveModal(card) {
            this.removeModal = { open: true, card, loading: false, error: '' };
        },

        async removeCard() {
            this.removeModal.loading = true;
            this.removeModal.error = '';
            try {
                const res = await fetch(`/admin/nfc/binding/${this.removeModal.card.id}/ajax`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.removeModal.error = data.message || 'Failed to remove binding.';
                    return;
                }
                this.registry = this.registry.filter(c => c.id !== this.removeModal.card.id);
                this.removeModal.open = false;
            } catch {
                this.removeModal.error = 'Network error. Please try again.';
            } finally {
                this.removeModal.loading = false;
            }
        },

        // --- REPLACE MODAL ---
        openReplaceModal(card) {
            this.replaceModal = { open: true, card, step: 1, loading: false, error: '' };
            this.stopPolling();
        },

        startReplace() {
            this.replaceModal.step = 2;
            this.startPolling();
        },

        async confirmReplace() {
            this.replaceModal.loading = true;
            this.replaceModal.error = '';
            const newUid = this.nfc.uid;
            try {
                // Delete old card
                const delRes = await fetch(`/admin/nfc/binding/${this.replaceModal.card.id}/ajax`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                if (!delRes.ok) throw new Error('Failed to remove old binding.');

                // Bind new card
                const bindRes = await fetch('{{ route("admin.nfc.binding.ajax") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: this.replaceModal.card.user_id,
                        tag_id: newUid,
                    }),
                });
                const data = await bindRes.json();
                if (!bindRes.ok || !data.success) {
                    this.replaceModal.error = data.message || 'Failed to bind new card.';
                    return;
                }
                // Update registry
                this.registry = this.registry.filter(c => c.id !== this.replaceModal.card.id);
                this.registry.push(data.card);

                this.stopPolling();
                this.replaceModal.step = 3;
            } catch (e) {
                this.replaceModal.error = e.message || 'Network error. Please try again.';
            } finally {
                this.replaceModal.loading = false;
            }
        },
    };
}
</script>
@endpush