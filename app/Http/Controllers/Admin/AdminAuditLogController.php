<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Date range filtering (defaults to today if empty)
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        // Base query with filters
        $query = AuditLog::query()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user')) {
            $query->where('user_name', 'like', '%' . $request->input('user') . '%');
        }

        // Statistics calculations
        $totalActivities = (clone $query)->count();
        $loginActivities = (clone $query)->where('action', 'LIKE', '%Login%')->count();
        $dataChanges = (clone $query)->whereIn('action', ['Added Student', 'Updated Student', 'Deleted Student', 'Added Faculty', 'Updated Faculty', 'Deleted Faculty', 'User Created', 'User Updated', 'Settings Updated'])->count();
        $failedActivities = (clone $query)->where('status', 'Failed')->count();

        // Hourly Trend Data for Graph
        $trendRaw = (clone $query)
          ->selectRaw("DATE_FORMAT(created_at, '%H') as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hourlyTrend = [];
        for ($i = 8; $i <= 18; $i++) {
            $hourKey = str_pad($i, 2, '0', STR_PAD_LEFT);
            $label = Carbon::createFromTime($i, 0)->format('g:i A');
            $hourlyTrend[$label] = $trendRaw[$hourKey] ?? 0;
        }

        // Paginated logs table
        $auditLogs = $query->latest()->paginate(10)->withQueryString();

        return view('admin.audit-logs.index', compact(
            'totalActivities',
            'loginActivities',
            'dataChanges',
            'failedActivities',
            'hourlyTrend',
            'auditLogs',
            'startDate',
            'endDate'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $format = $request->input('format', 'csv');

        $query = AuditLog::query()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($request->filled('role')) $query->where('role', $request->input('role'));
        if ($request->filled('module')) $query->where('module', $request->input('module'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));

        $logs = $query->latest()->get();

        $fileName = 'siatrack-audit-logs-' . date('Y-m-d') . '.' . ($format === 'excel' ? 'xls' : 'csv');

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Date & Time', 'User Name', 'Role', 'Action', 'Module', 'Status', 'IP Address', 'Description']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name,
                    $log->role,
                    $log->action,
                    $log->module,
                    $log->status,
                    $log->ip_address,
                    $log->description
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}