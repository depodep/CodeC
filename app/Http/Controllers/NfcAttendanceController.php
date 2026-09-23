<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NfcCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NfcAttendanceController extends Controller
{
    /**
     * Display the Teacher Attendance Monitor Dashboard with Real Data
     */
public function attendanceIndex(Request $request)
    {
        $teacher = Auth::user(); // Uses the default authenticated user
        $today = Carbon::today();

        // Base Query for Attendance Logs joined with Users
        $query = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.strand', 'users.photo');

        // 1. Date Range Filter (Defaults to today if not specified)
        $dateFrom = $request->input('date_from', $today->toDateString());
        $dateTo   = $request->input('date_to', $today->toDateString());
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('attendance_logs.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }

        // 2. Status Filter (ON-TIME / LATE)
        if ($request->filled('status')) {
            $query->where('attendance_logs.status', $request->status);
        }

        // 3. Search Filter (Student Name or ID Number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('users.first_name', 'like', "%{$search}%")
                  ->orWhere('users.last_name', 'like', "%{$search}%")
                  ->orWhere('users.id_number', 'like', "%{$search}%");
            });
        }

        // Calculate Metrics for the Filtered Period
        $totalScanned = (clone $query)->count();
        $presentCount = (clone $query)->where('attendance_logs.status', 'ON-TIME')->count();
        $lateCount    = (clone $query)->where('attendance_logs.status', 'LATE')->count();
        
        // Paginated Results for the Table Feed
        $recentLogs = $query->orderBy('attendance_logs.created_at', 'desc')->paginate(15)->withQueryString();

        return view('teacher.attendance', compact('teacher', 'totalScanned', 'presentCount', 'lateCount', 'recentLogs', 'dateFrom', 'dateTo'));
    }

    /**
     * Export Filtered Attendance Records to CSV
     */
    public function exportCsv(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->input('date_to', Carbon::today()->toDateString());

        $logs = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->whereBetween('attendance_logs.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('users.id_number', 'users.first_name', 'users.last_name', 'users.strand', 'attendance_logs.status', 'attendance_logs.created_at', 'attendance_logs.time_out')
            ->get();

        $filename = "SIATRACK_Attendance_{$dateFrom}_to_{$dateTo}.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID Number', 'First Name', 'Last Name', 'Strand', 'Status', 'Time In', 'Time Out']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id_number,
                    $log->first_name,
                    $log->last_name,
                    $log->strand,
                    $log->status,
                    $log->created_at,
                    $log->time_out ?? 'N/A'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display the Live NFC Kiosk View
     */
    public function kioskView()
    {
        return view('teacher.kiosk');
    }

    /**
     * Handles NFC Tap from hardware reader or Python bridge in Real-Time
     */
    public function handleTap(Request $request) {
        if($request->has('uid')) {
            Cache::put('latest_nfc_uid', $request->uid, now()->addSeconds(10));
        }

        $tagId = strtoupper(trim((string) ($request->input('tag_id') ?? $request->query('tag_id'))));

        if (empty($tagId)) {
            return response()->json([
                'success' => false,
                'message' => 'No NFC Tag UID detected.',
            ], 400);
        }

        // 1. Cache latest tap for Kiosk UI pollers
        @file_put_contents(storage_path('latest_nfc.txt'), $tagId);
        Cache::put('latest_nfc_tap', $tagId, 300);

        // 2. Locate Student by NFC Tag
        $user = null;
        if (Schema::hasTable('nfc_cards')) {
            $card = DB::table('nfc_cards')->where('tag_id', $tagId)->first();
            if ($card) {
                $user = User::find($card->user_id);
            }
        }

        // Fallback check on users table if nfc_tag_id column exists
        if (!$user && Schema::hasColumn('users', 'nfc_tag_id')) {
            $user = User::where('nfc_tag_id', $tagId)->first();
        }

        if (!$user) {
            return response()->json([
                'success'       => false,
                'is_registered' => false,
                'tag_id'        => $tagId,
                'message'       => "Unregistered card (UID: {$tagId}). Please assign it to a student.",
            ], 404);
        }

        // Resolve Academic Details
        $trackName = match ((int)($user->track ?? $user->strand ?? 0)) {
            1 => 'Academic Track',
            2 => 'Technical-Professional',
            default => 'General Track',
        };

        $sectionName = match ((int)($user->section ?? 0)) {
            1 => 'Amber',
            2 => 'Crystal',
            3 => 'Pearl',
            4 => 'Turquoise',
            default => 'Unassigned Section',
        };

        $gradeLevelText = !empty($user->grade_level) ? 'Grade ' . $user->grade_level : 'Grade 11';

        // 3. Resolve Column Names dynamically
        $logCols    = Schema::hasTable('attendance_logs') ? Schema::getColumnListing('attendance_logs') : [];
        $studentKey = in_array('user_id', $logCols) ? 'user_id' : (in_array('student_id', $logCols) ? 'student_id' : 'user_id');
        $dateCol    = in_array('attendance_date', $logCols) ? 'attendance_date' : (in_array('date', $logCols) ? 'date' : 'created_at');

        $today   = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        // 4. Check for today's existing log for this student
        $existingLog = DB::table('attendance_logs')
            ->where($studentKey, $user->id)
            ->whereDate($dateCol, $today)
            ->first();

        // 8:00 AM Morning Cutoff
        $cutoffTime = Carbon::createFromTime(8, 0, 0);
        $status     = Carbon::now()->gt($cutoffTime) ? 'LATE' : 'ON-TIME';

        if (!$existingLog) {
            // --- FIRST TAP TODAY: TIME IN ---
            $insertData = [
                $studentKey  => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (in_array('academic_period_id', $logCols) && Schema::hasTable('academic_periods')) {
                $insertData['academic_period_id'] = DB::table('academic_periods')
                    ->where('is_active', 1)
                    ->value('id');
            }

            if (in_array('attendance_date', $logCols)) $insertData['attendance_date'] = $today;
            if (in_array('date', $logCols))            $insertData['date'] = $today;
            if (in_array('time_in', $logCols))         $insertData['time_in'] = $nowTime;
            if (in_array('status', $logCols))          $insertData['status'] = $status;

            DB::table('attendance_logs')->insert($insertData);

            $actionType    = 'TIME IN';
            $displayStatus = $status;
            $message       = "TIME IN recorded for {$user->first_name} {$user->last_name} ({$displayStatus})";
        } else {
            // --- SUBSEQUENT TAP: DEBOUNCE OR TIME OUT ---
            $lastActionTime = Carbon::parse($existingLog->updated_at ?? $existingLog->created_at);

            // 60-Second Debounce: ignore accidental double taps while holding card on reader
            if ($lastActionTime->diffInSeconds(now()) < 60 && empty($existingLog->time_out)) {
                return response()->json([
                    'success'       => true,
                    'is_registered' => true,
                    'action'        => 'ALREADY_LOGGED',
                    'status'        => $existingLog->status ?? 'ON-TIME',
                    'time'          => Carbon::parse($existingLog->time_in ?? now())->format('g:i A'),
                    'student'       => [
                        'id'          => $user->id,
                        'name'        => "{$user->first_name} {$user->last_name}",
                        'id_number'   => $user->id_number ?? 'N/A',
                        'grade_level' => $gradeLevelText,
                        'track'       => $trackName,
                        'strand'      => $user->strand ?? 'STEM',
                        'section'     => $sectionName,
                        'avatar'      => !empty($user->photo) ? asset('storage/' . $user->photo) : null,
                    ],
                    'message'       => "{$user->first_name} has already timed in at " . Carbon::parse($existingLog->time_in ?? now())->format('g:i A'),
                ]);
            }

            // Record TIME OUT while preserving original morning status
            $updateData = ['updated_at' => now()];
            if (in_array('time_out', $logCols)) {
                $updateData['time_out'] = $nowTime;
            }

            DB::table('attendance_logs')->where('id', $existingLog->id)->update($updateData);

            $actionType    = 'TIME OUT';
            $displayStatus = $existingLog->status ?? 'ON-TIME';
            $message       = "TIME OUT recorded for {$user->first_name} {$user->last_name}";
        }

        return response()->json([
            'success'       => true,
            'is_registered' => true,
            'action'        => $actionType,
            'status'        => $displayStatus,
            'time'          => Carbon::now()->format('g:i A'),
            'student'       => [
                'id'          => $user->id,
                'name'        => "{$user->first_name} {$user->last_name}",
                'id_number'   => $user->id_number ?? 'N/A',
                'grade_level' => $gradeLevelText,
                'track'       => $trackName,
                'strand'      => $user->strand ?? 'STEM',
                'section'     => $sectionName,
                'avatar'      => !empty($user->photo) ? asset('storage/' . $user->photo) : null,
            ],
            'message'       => $message,
        ]);
    }
}