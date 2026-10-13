<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\SmsGatewayService;
use Carbon\Carbon;

class AdminSettingController extends Controller
{
    private const SMS_TEMPLATE_DEFAULTS = [
        'subject_absence_template' => 'Your child, {student_name}, was recorded absent from {subject_name} on {date}, scheduled from {start_time} to {end_time}. Please contact the school for confirmation.',
        'classroom_absence_template' => 'Attendance Advisory: The following students from {section_name} were recorded absent on {date}: {absent_students}. Please verify their attendance.',
    ];

    public function settingsIndex(SmsGatewayService $smsGateway)
    {
        $settings = collect();

        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')
                ->whereIn('key', ['active_school_year'])
                ->pluck('value', 'key');
        }

        $activePeriod = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', true)->first()
            : null;
        $schoolYearGroups = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->orderByDesc('school_year')->orderBy('start_date')->get()->groupBy('school_year')
            : collect();

        return view('admin.settings.index', [
            'adminUser' => auth()->user(),
            'activePeriod' => $activePeriod,
            'schoolYearGroups' => $schoolYearGroups,
            'smsConfig' => $smsGateway->getConfig(),
            'smsPasswordConfigured' => $smsGateway->hasPassword(),
            'smsStatus' => $smsGateway->status(),
            'smsLastPing' => !empty($smsGateway->status()['checked_at'])
                ? Carbon::parse($smsGateway->status()['checked_at'])->format('F j, Y g:i A')
                : null,
            'smsTemplates' => $this->smsTemplates(),
        ]);
    }

    public function updateSmsTemplates(Request $request)
    {
        $validated = $request->validate([
            'subject_absence_template' => ['required', 'string', 'max:1000'],
            'classroom_absence_template' => ['required', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('settings')) {
            return back()->withErrors(['sms_templates' => 'The settings table is not available. Run the migrations first.']);
        }

        foreach ($validated as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'sms_' . $key],
                ['value' => trim($value), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('success', 'SMS absence templates updated successfully.');
    }

    public function updateSms(Request $request, SmsGatewayService $smsGateway)
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'login' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'validate_numbers' => ['boolean'],
            'timeout' => ['required', 'integer', 'min:1', 'max:300'],
            'message_delay_seconds' => ['required', 'integer', 'min:0'],
            'enable_rate_limiting' => ['boolean'],
            'admin_password' => ['required', 'string'],
        ], [
            'admin_password.required' => 'Admin password is required to update SMS API settings.',
        ]);

        $user = auth()->user();
        $isPasswordValid = false;
        if ($user && $user->password) {
            if ($request->admin_password === $user->password) {
                $isPasswordValid = true;
            } else {
                try {
                    if (\Illuminate\Support\Facades\Hash::check($request->admin_password, $user->password)) {
                        $isPasswordValid = true;
                    }
                } catch (\Exception $e) {
                    $isPasswordValid = false;
                }
            }
        }

        if (!$isPasswordValid) {
            return back()->withErrors(['admin_password' => 'Incorrect admin password. Changes not saved.'])->withInput();
        }

        if (!Schema::hasTable('settings')) {
            return back()->withErrors(['sms' => 'The settings table is not available. Run the migrations first.']);
        }

        if (!$smsGateway->hasPassword() && blank($validated['password'] ?? null)) {
            return back()->withErrors(['password' => 'A gateway password is required for the first configuration.'])->withInput();
        }

        $smsGateway->saveConfig(array_merge($validated, [
            'validate_numbers' => $request->boolean('validate_numbers'),
            'enable_rate_limiting' => $request->boolean('enable_rate_limiting'),
        ]));

        return back()->with('success', 'SMS API configuration updated successfully.');
    }

    public function testSms(SmsGatewayService $smsGateway)
    {
        return response()->json($smsGateway->testConnection());
    }

    public function updateSchoolYearDates(Request $request)
    {
        $validated = $request->validate([
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'admin_password' => ['required', 'string'],
        ], [
            'admin_password.required' => 'Admin password is required to update dates.',
        ]);

        $user = auth()->user();
        $isPasswordValid = false;
        if ($user && $user->password) {
            if ($request->admin_password === $user->password) {
                $isPasswordValid = true;
            } else {
                try {
                    if (\Illuminate\Support\Facades\Hash::check($request->admin_password, $user->password)) {
                        $isPasswordValid = true;
                    }
                } catch (\Exception $e) {
                    $isPasswordValid = false;
                }
            }
        }

        if (!$isPasswordValid) {
            return back()->withErrors(['admin_password' => 'Incorrect admin password. Changes not saved.']);
        }

        if (!Schema::hasTable('academic_periods')) {
            return back()->withErrors(['school_year' => 'The academic periods table is not available.']);
        }

        DB::table('academic_periods')
            ->where('id', $validated['academic_period_id'])
            ->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'School year dates updated successfully.');
    }

    private function saveSetting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function smsTemplates(): array
    {
        if (!Schema::hasTable('settings')) {
            return self::SMS_TEMPLATE_DEFAULTS;
        }

        $saved = DB::table('settings')
            ->whereIn('key', array_map(fn (string $key): string => 'sms_' . $key, array_keys(self::SMS_TEMPLATE_DEFAULTS)))
            ->pluck('value', 'key');

        return [
            'subject_absence_template' => $saved['sms_subject_absence_template'] ?? self::SMS_TEMPLATE_DEFAULTS['subject_absence_template'],
            'classroom_absence_template' => $saved['sms_classroom_absence_template'] ?? self::SMS_TEMPLATE_DEFAULTS['classroom_absence_template'],
        ];
    }
}