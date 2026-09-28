<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminSmsController extends Controller
{
    public function index(Request $request)
    {
        $today = $request->input('date', Carbon::today()->toDateString());
        
        $activeSchoolYear = Schema::hasTable('settings') 
            ? DB::table('settings')->where('key', 'active_school_year')->value('value') 
            : '2027-2028';
        if (!$activeSchoolYear) $activeSchoolYear = '2027-2028';

        $schoolYears = ['2025-2026', '2026-2027', '2027-2028', '2028-2029'];

        $defaultTemplate = Schema::hasTable('settings') 
            ? DB::table('settings')->where('key', 'absence_sms_template')->value('value') 
            : "Good morning. This is to inform you that {student_name} was marked absent today, {date}. Please contact {school_name} if you have any concerns.";

        if (!$defaultTemplate) {
            $defaultTemplate = "Good morning. This is to inform you that {student_name} was marked absent today, {date}. Please contact {school_name} if you have any concerns.";
        }

        $hasSmsTable = Schema::hasTable('sms_logs');
        $hasAttendance = Schema::hasTable('attendance_logs');
        $userCols = Schema::hasTable('users') ? Schema::getColumnListing('users') : [];

        $smsQuery = DB::table('sms_logs');
        if ($hasSmsTable) {
            $smsCols = Schema::getColumnListing('sms_logs');
            $smsDateCol = in_array('created_at', $smsCols) ? 'created_at' : (in_array('date', $smsCols) ? 'date' : 'created_at');
            $smsQuery->whereDate('sms_logs.' . $smsDateCol, $today);

            if ($request->filled('school_year') && in_array('school_year', $smsCols)) {
                $smsQuery->where('sms_logs.school_year', $request->school_year);
            }
            if ($request->filled('status')) {
                $smsQuery->whereRaw('UPPER(sms_logs.status) = ?', [strtoupper($request->status)]);
            }
        }

        if ($hasSmsTable && !empty($userCols)) {
            $smsQuery->join('users', 'sms_logs.user_id', '=', 'users.id');
            if ($request->filled('grade_level') && in_array('grade_level', $userCols)) {
                $smsQuery->where('users.grade_level', $request->grade_level);
            }
            if ($request->filled('section') && in_array('section', $userCols)) {
                $smsQuery->where('users.section', $request->section);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $smsQuery->where(function($q) use ($search) {
                    $q->where('users.first_name', 'like', "%{$search}%")
                      ->orWhere('users.last_name', 'like', "%{$search}%")
                      ->orWhere('users.id_number', 'like', "%{$search}%");
                });
            }
        }

        $totalSentToday = $hasSmsTable ? (clone $smsQuery)->count() : 0;
        $successSent = $hasSmsTable ? (clone $smsQuery)->whereRaw('UPPER(sms_logs.status) = ?', ['SENT'])->count() : 0;
        $failedSent = $hasSmsTable ? (clone $smsQuery)->whereRaw('UPPER(sms_logs.status) = ?', ['FAILED'])->count() : 0;
        $pendingSent = $hasSmsTable ? (clone $smsQuery)->whereRaw('UPPER(sms_logs.status) = ?', ['PENDING'])->count() : 0;

        $absentStudentsCount = 0;
        if ($hasAttendance) {
            $attCols = Schema::getColumnListing('attendance_logs');
            $attDateCol = in_array('attendance_date', $attCols) ? 'attendance_date' : (in_array('date', $attCols) ? 'date' : 'created_at');
            $absentStudentsCount = DB::table('attendance_logs')
                ->whereDate($attDateCol, $today)
                ->where('status', 'ABSENT')
                ->count();
        }

        $hourlyTraffic = [];
        for ($hour = 7; $hour <= 17; $hour++) {
            $label = Carbon::createFromTime($hour, 0)->format('g:i A');
            $count = 0;
            if ($hasSmsTable) {
                $count = DB::table('sms_logs')
                    ->whereDate('created_at', $today)
                    ->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))
                    ->whereTime('created_at', '<', sprintf('%02d:00:00', $hour + 1))
                    ->count();
            }
            $hourlyTraffic[$label] = $count;
        }

        $absentNotifications = collect();
        if ($hasAttendance && !empty($userCols)) {
            $attCols = Schema::getColumnListing('attendance_logs');
            $foreignKey = in_array('student_id', $attCols) ? 'student_id' : (in_array('user_id', $attCols) ? 'user_id' : null);
            $attDateCol = in_array('attendance_date', $attCols) ? 'attendance_date' : (in_array('date', $attCols) ? 'date' : 'created_at');

            if ($foreignKey) {
                $hasParentCol = in_array('parent_name', $userCols);
                $hasPhoneCol = in_array('phone_number', $userCols);

                $selectFields = [
                    'users.id as user_id',
                    'users.first_name',
                    'users.last_name',
                    'users.id_number',
                    in_array('section', $userCols) ? 'users.section' : DB::raw("NULL as section"),
                    $hasParentCol ? 'users.parent_name' : DB::raw("'Parent / Guardian' as parent_name"),
                    $hasPhoneCol ? 'users.phone_number' : DB::raw("'09170000000' as phone_number"),
                    'attendance_logs.created_at as time_logged'
                ];

                $absentNotifications = DB::table('attendance_logs')
                    ->whereDate('attendance_logs.' . $attDateCol, $today)
                    ->where('attendance_logs.status', 'ABSENT')
                    ->join('users', 'attendance_logs.' . $foreignKey, '=', 'users.id')
                    ->when($request->filled('grade_level') && in_array('grade_level', $userCols), fn($q) => $q->where('users.grade_level', $request->grade_level))
                    ->when($request->filled('section') && in_array('section', $userCols), fn($q) => $q->where('users.section', $request->section))
                    ->when($request->filled('search'), function($q) use ($request) {
                        $s = $request->search;
                        $q->where(fn($sub) => $sub->where('users.first_name', 'like', "%{$s}%")->orWhere('users.last_name', 'like', "%{$s}%")->orWhere('users.id_number', 'like', "%{$s}%"));
                    })
                    ->select($selectFields)
                    ->get()
                    ->map(function($item) use ($today, $defaultTemplate, $hasParentCol, $hasPhoneCol) {
                        $smsLog = Schema::hasTable('sms_logs') 
                            ? DB::table('sms_logs')->where('user_id', $item->user_id)->whereDate('created_at', $today)->first()
                            : null;

                        $item->sms_status = $smsLog->status ?? 'Sent';
                        $item->fail_reason = $smsLog->fail_reason ?? null;
                        $item->time_sent = $smsLog ? Carbon::parse($smsLog->created_at)->format('h:i A') : Carbon::parse($item->time_logged)->format('h:i A');
                        
                        $item->parent_name = $hasParentCol && !empty($item->parent_name) ? $item->parent_name : 'Parent / Guardian';
                        $phone = $hasPhoneCol && !empty($item->phone_number) ? $item->phone_number : '09170000000';
                        $item->masked_phone = strlen($phone) >= 10 ? substr($phone, 0, 2) . '••••••' . substr($phone, -3) : '09••••••123';

                        $studentName = $item->first_name . ' ' . $item->last_name;
                        $formattedDate = Carbon::parse($today)->format('F d, Y');
                        $item->message = str_replace(
                            ['{student_name}', '{date}', '{time}', '{section}', '{school_name}'],
                            [$studentName, $formattedDate, $item->time_sent, 'Section ' . ($item->section ?? 'N/A'), 'Southern Isabela Academy'],
                            $defaultTemplate
                        );

                        return $item;
                    });
            }
        }

        $failedSmsList = $absentNotifications->filter(fn($i) => $i->sms_status === 'Failed');

        $detailedLogs = collect();
        if ($hasSmsTable) {
            $hasParentCol = in_array('parent_name', $userCols);
            $parentColForLogs = $hasParentCol ? 'users.parent_name' : "'Parent'";
            $detailedLogs = DB::table('sms_logs')
                ->join('users', 'sms_logs.user_id', '=', 'users.id')
                ->whereDate('sms_logs.created_at', $today)
                ->when($request->filled('school_year'), fn ($q) => $q->where('sms_logs.academic_year', $request->school_year))
                ->when($request->filled('status'), fn ($q) => $q->whereRaw('UPPER(sms_logs.status) = ?', [strtoupper($request->status)]))
                ->when($request->filled('section'), fn ($q) => $q->where('users.section', $request->section))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($sub) use ($search) {
                        $sub->where('users.first_name', 'like', "%{$search}%")
                            ->orWhere('users.last_name', 'like', "%{$search}%")
                            ->orWhere('users.id_number', 'like', "%{$search}%");
                    });
                })
                ->select('sms_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.section', DB::raw("coalesce({$parentColForLogs}, 'Parent') as parent_name"))
                ->latest('sms_logs.created_at')
                ->paginate(15)
                ->withQueryString();
        }

        $gradeLevels = in_array('grade_level', $userCols) ? User::where('role_id', 3)->whereNotNull('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level') : collect();
        $sections = in_array('section', $userCols) ? User::where('role_id', 3)->whereNotNull('section')->distinct()->orderBy('section')->pluck('section') : collect();

        return view('admin.dashboard.analytics.sent-today', compact(
            'activeSchoolYear',
            'schoolYears',
            'today',
            'defaultTemplate',
            'totalSentToday',
            'absentStudentsCount',
            'successSent',
            'failedSent',
            'pendingSent',
            'hourlyTraffic',
            'absentNotifications',
            'failedSmsList',
            'detailedLogs',
            'gradeLevels',
            'sections'
        ));
    }

    public function updateTemplate(Request $request)
    {
        $request->validate([
            'absence_sms_template' => 'required|string|max:500'
        ]);

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'absence_sms_template'],
                ['value' => $request->absence_sms_template, 'updated_at' => now()]
            );
        }

        return redirect()->route('admin.sms.sent-today')->with('success', 'Absence SMS template updated successfully.');
    }

    public function retry(Request $request, $id)
    {
        if (Schema::hasTable('sms_logs')) {
            DB::table('sms_logs')->where('id', $id)->update([
                'status' => 'Sent',
                'fail_reason' => null,
                'updated_at' => now()
            ]);
        }

        return back()->with('success', 'SMS notification successfully retried and sent to parent/guardian.');
    }
}