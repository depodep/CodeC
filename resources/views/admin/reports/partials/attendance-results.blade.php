<div id="attendance-report-results" class="space-y-4" aria-live="polite">
    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#590d0d]">Selected report</p>
                    <h2 class="truncate text-lg font-black text-slate-900">{{ $selectedLabel ?? 'No selection' }}</h2>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Period</p>
                    <p class="text-sm font-bold text-slate-700">{{ $periodLabel ?? '—' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:flex-[2]">
                @foreach([
                    ['label' => 'Recorded scans', 'value' => $summary['total'], 'style' => 'slate'],
                    ['label' => 'Present / On time', 'value' => $summary['present'], 'style' => 'emerald'],
                    ['label' => 'Late', 'value' => $summary['late'], 'style' => 'amber'],
                    ['label' => 'Marked absent', 'value' => $summary['absent'], 'style' => 'rose'],
                ] as $card)
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <p class="text-[9px] font-black uppercase tracking-wide text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-0.5 text-xl font-black text-{{ $card['style'] }}-700">{{ number_format($card['value']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-black text-sm text-slate-900">
                    {{ $filters['period'] === 'daily' ? 'Daily attendance' : ($filters['period'] === 'weekly' ? 'Weekly attendance' : 'Monthly attendance') }}
                    · {{ $filters['report_by'] === 'section' ? 'Section' : 'Subject' }}
                </h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Only recorded attendance is shown; a missing scan is not counted as absent.</p>
            </div>
            <span class="text-[11px] font-bold text-slate-500">{{ $attendanceRows->total() }} students</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                @if($filters['period'] === 'daily')
                    <thead class="bg-[#590d0d] text-[10px] uppercase tracking-wide text-amber-200">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Section</th>
                            @if($filters['report_by'] === 'subject')
                                <th class="px-4 py-3">Subject</th>
                            @endif
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Time in</th>
                            <th class="px-4 py-3">Time out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($attendanceRows as $index => $row)
                            @php($log = $row->logs[$dates->first()->toDateString()] ?? null)
                            @php($status = strtoupper((string) ($log->status ?? '')))
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 text-slate-400 font-semibold">{{ $attendanceRows->firstItem() + $index }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-800">{{ $row->student->last_name }}, {{ $row->student->first_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $row->student->id_number ?: 'No ID' }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $row->student->section ?: '—' }}</td>
                                @if($filters['report_by'] === 'subject')
                                    <td class="px-4 py-3 text-slate-600">{{ $selectedLabel }}</td>
                                @endif
                                <td class="px-4 py-3">
                                    @if($log)
                                        <span class="px-2 py-1 rounded-md font-black {{ in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-50 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-50 text-amber-700' : ($status === 'ABSENT' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600')) }}">
                                            {{ $log->status }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-700">{{ $log?->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i A') : '—' }}</td>
                                <td class="px-4 py-3 font-bold text-slate-700">{{ $log?->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $filters['report_by'] === 'subject' ? 7 : 6 }}" class="px-4 py-12 text-center text-slate-400">No students or attendance logs match these filters.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead class="bg-[#590d0d] text-amber-200 text-[10px] uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-3 whitespace-nowrap">#</th>
                            <th class="px-3 py-3 whitespace-nowrap">Student</th>
                            @foreach($dates as $date)
                                <th class="px-2 py-3 text-center whitespace-nowrap">
                                    {{ $date->format('d') }}
                                    <span class="block text-[9px] font-medium text-slate-300">{{ $date->format('D') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceRows as $row)
                            <tr class="border-t border-slate-200 hover:bg-amber-50/50">
                                <td class="px-3 py-3 text-slate-400 font-semibold">{{ $attendanceRows->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-800">{{ $row->student->last_name }}, {{ $row->student->first_name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $row->student->id_number ?: 'No ID' }}</div>
                                </td>
                                @foreach($dates as $date)
                                    @php($log = $row->logs[$date->toDateString()] ?? null)
                                    @php($status = strtoupper((string) ($log->status ?? '')))
                                    <td class="px-2 py-3 text-center align-middle">
                                        @if($log)
                                            <span title="{{ $log->status }}{{ $log->time_in ? ' · '.\Carbon\Carbon::parse($log->time_in)->format('g:i A') : '' }}" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-[10px] font-black {{ in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-100 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-100 text-amber-700' : ($status === 'ABSENT' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600')) }}">
                                                {{ in_array($status, ['ON-TIME', 'PRESENT']) ? 'P' : ($status === 'LATE' ? 'L' : ($status === 'ABSENT' ? 'A' : '—')) }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($dates) + 2 }}" class="px-4 py-12 text-center text-slate-400">No students or attendance logs match these filters.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-[11px] text-slate-500">
                Showing {{ $attendanceRows->firstItem() ?? 0 }} to {{ $attendanceRows->lastItem() ?? 0 }} of {{ $attendanceRows->total() }} students
            </p>
            @if($attendanceRows->hasPages())
                <div class="[&_*]:!mb-0">{{ $attendanceRows->links() }}</div>
            @endif
        </div>
    </section>
</div>
