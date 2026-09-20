<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAttendanceController extends Controller
{
    public function live(Request $request)
    {
        $reportTitle = 'Live Attendance Tap Feed';

        $hasAttendance = Schema::hasTable('attendance_logs');
        $recentLogs = collect();

        if ($hasAttendance) {
            $userCols = Schema::hasTable('users') ? Schema::getColumnListing('users') : [];
            $attCols = Schema::getColumnListing('attendance_logs');
            $userForeignKey = in_array('student_id', $attCols) ? 'student_id' : (in_array('user_id', $attCols) ? 'user_id' : null);

            $query = DB::table('attendance_logs');
            if ($userForeignKey) {
                $query->join('users', 'attendance_logs.' . $userForeignKey, '=', 'users.id')
                      ->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.section', 'users.strand');
            }

            $recentLogs = $query->latest('attendance_logs.created_at')->limit(50)->get();
        }

        return view('admin.attendance.live', compact('reportTitle', 'recentLogs'));
    }
}