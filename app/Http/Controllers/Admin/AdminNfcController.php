<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\User;
use App\Models\NfcCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminNfcController extends Controller
{
    // Show the NFC Binding Management Page
    public function bindingIndex(Request $request)
    {
        $students = Schema::hasTable('users')
            ? User::where('role_id', 3)
                ->with('nfcCard')
                ->orderBy('last_name')
                ->get()
            : collect();

        $boundCards = Schema::hasTable('nfc_cards')
            ? NfcCard::with('user')
                ->where('status', 'active')
                ->latest()
                ->get()
            : collect();

        $sections = Schema::hasTable('academic_sections')
            ? AcademicSection::orderBy('grade_level')->orderBy('section_name')->get()
            : collect();

        return view('admin.nfc.binding', [
            'students' => $students,
            'boundCards' => $boundCards,
            'sections' => $sections,
            'selectedStudentId' => $request->integer('student_id'),
        ]);
    }

    // Download a bridge configured for this Laravel installation.
    public function downloadBridge(Request $request)
    {
        $configuredScript = $this->configuredBridgeScript($request);

        return response()->streamDownload(
            static function () use ($configuredScript): void {
                echo $configuredScript;
            },
            'siatrack_nfc_bridge.py',
            [
                'Content-Type' => 'text/x-python; charset=utf-8',
                'Cache-Control' => 'no-store',
            ]
        );
    }

    // Download the bridge and a Windows runner as one package.
    public function downloadBridgePackage(Request $request)
    {
        if (!class_exists(\ZipArchive::class)) {
            return $this->downloadBridge($request);
        }

        $configuredScript = $this->configuredBridgeScript($request);
        $driverPath = base_path('ACS-Unified-MSI-4280.rar');
        $venvPath = base_path('.venv');

        if (!is_file($driverPath) || !is_readable($driverPath)) {
            abort(404, 'The ACR122U driver archive is not available.');
        }

        if (!is_dir($venvPath) || !is_file($venvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe')) {
            abort(404, 'The bundled Python runtime is not available.');
        }

        $runner = <<<'BAT'
@echo off
setlocal
title SIATRACK NFC Bridge
cd /d "%~dp0"

if exist "%~dp0.venv\Scripts\python.exe" (
    "%~dp0.venv\Scripts\python.exe" -u "%~dp0siatrack_nfc_bridge.py"
) else (
    where python >nul 2>&1
    if %errorlevel%==0 (
        python -u "%~dp0siatrack_nfc_bridge.py"
    ) else (
        where py >nul 2>&1
        if %errorlevel%==0 (
            py -3 -u "%~dp0siatrack_nfc_bridge.py"
        ) else (
            echo Python 3 was not found.
            pause
            exit /b 1
        )
    )
)

if errorlevel 1 (
    echo.
    echo The NFC bridge stopped with an error.
    pause
)
BAT;

        $archivePath = tempnam(sys_get_temp_dir(), 'siatrack-nfc-');
        if ($archivePath === false) {
            abort(500, 'Unable to prepare the NFC bridge package.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($archivePath);
            abort(500, 'Unable to create the NFC bridge package.');
        }

        $zip->addFromString('siatrack_nfc_bridge.py', $configuredScript);
        $zip->addFromString('RUN_NFC_BRIDGE.bat', $runner);
        $zip->addFile($driverPath, 'ACS-Unified-MSI-4280.rar');

        try {
            $venvFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($venvPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($venvFiles as $venvFile) {
                if (!$venvFile->isFile() || !$venvFile->isReadable() || $venvFile->isLink()) {
                    continue;
                }

                $relativePath = ltrim(str_replace($venvPath, '', $venvFile->getPathname()), DIRECTORY_SEPARATOR);
                if (!$zip->addFile($venvFile->getPathname(), '.venv/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath))) {
                    Log::warning('Skipped an NFC bridge runtime file while building the package.', [
                        'path' => $venvFile->getPathname(),
                    ]);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('The NFC bridge package was built without some bundled runtime files.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$zip->close()) {
            @unlink($archivePath);
            abort(500, 'Unable to finalize the NFC bridge package.');
        }

        return response()
            ->download($archivePath, 'siatrack_nfc_bridge.zip', [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store',
            ])
            ->deleteFileAfterSend(true);
    }

    public function downloadBridgeRunner()
    {
        $runner = <<<'BAT'
@echo off
setlocal
title SIATRACK NFC Bridge
cd /d "%~dp0"

where python >nul 2>&1
if %errorlevel%==0 (
    python -u "%~dp0siatrack_nfc_bridge.py"
) else (
    where py >nul 2>&1
    if %errorlevel%==0 (
        py -3 -u "%~dp0siatrack_nfc_bridge.py"
    ) else (
        echo Python 3 was not found.
        pause
        exit /b 1
    )
)

if errorlevel 1 (
    echo.
    echo The NFC bridge stopped with an error.
    pause
)
BAT;

        return response()->streamDownload(
            static function () use ($runner): void {
                echo $runner;
            },
            'RUN_NFC_BRIDGE.bat',
            [
                'Content-Type' => 'application/x-bat',
                'Cache-Control' => 'no-store',
            ]
        );
    }

    private function configuredBridgeScript(Request $request): string
    {
        $bridgePath = base_path('nfc_bridge.py');

        if (!is_file($bridgePath) || !is_readable($bridgePath)) {
            abort(404, 'NFC bridge script is not available.');
        }

        $bridgeScript = file_get_contents($bridgePath);

        if ($bridgeScript === false) {
            abort(500, 'NFC bridge script could not be read.');
        }

        $apiUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/api/nfc/tap';
        $configuredScript = preg_replace(
            '/API_URL\s*=\s*"[^"]*";/',
            'API_URL = ' . var_export($apiUrl, true) . ';',
            $bridgeScript,
            1,
            $replacementCount
        );

        if ($configuredScript === null || $replacementCount !== 1) {
            abort(500, 'NFC bridge script has an unsupported format.');
        }

        return $configuredScript;
    }

    // Standard form POST binding (fallback)
    public function bindingStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tag_id'  => 'required|string|max:255',
        ]);

        $tagId = strtoupper(trim($request->tag_id));

        $existingCard = NfcCard::where('tag_id', $tagId)
            ->where('user_id', '!=', $request->user_id)
            ->with('user')
            ->first();

        if ($existingCard) {
            $studentName = $existingCard->user
                ? "{$existingCard->user->last_name}, {$existingCard->user->first_name}"
                : 'another student';

            return redirect()->route('admin.nfc.binding')
                ->with('duplicate_error', "NFC Card UID [{$tagId}] is already bound to {$studentName}!");
        }

        $existingStudentCard = NfcCard::where('user_id', $request->user_id)->first();
        if ($existingStudentCard) {
            return redirect()->route('admin.nfc.binding')
                ->with('duplicate_error', "This student already has an active NFC binding (UID: {$existingStudentCard->tag_id}). Remove the existing binding first.");
        }

        if (Schema::hasTable('nfc_cards')) {
            NfcCard::create([
                'user_id' => $request->user_id,
                'tag_id'  => $tagId,
                'status'  => 'active',
            ]);
            Cache::forget('latest_nfc_tap');
        }

        return redirect()->route('admin.nfc.binding')->with('success', 'NFC card successfully bound to student!');
    }

    // AJAX JSON: Bind NFC card
    public function bindAjax(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tag_id'  => 'required|string|max:255',
        ]);

        $tagId = strtoupper(trim($request->tag_id));

        // Check if card already bound to another student
        $existingCard = NfcCard::where('tag_id', $tagId)
            ->where('user_id', '!=', $request->user_id)
            ->with('user')
            ->first();

        if ($existingCard) {
            $studentName = $existingCard->user
                ? "{$existingCard->user->last_name}, {$existingCard->user->first_name}"
                : 'another student';

            return response()->json([
                'success' => false,
                'message' => "NFC Card UID [{$tagId}] is already bound to {$studentName}.",
            ], 422);
        }

        // Check if student already has a card
        $studentCard = NfcCard::where('user_id', $request->user_id)->first();
        if ($studentCard) {
            return response()->json([
                'success' => false,
                'message' => "This student already has an active NFC binding (UID: {$studentCard->tag_id}). Remove the existing binding first.",
            ], 422);
        }

        $card = NfcCard::create([
            'user_id' => $request->user_id,
            'tag_id'  => $tagId,
            'status'  => 'active',
        ]);

        Cache::forget('latest_nfc_tap');

        $user = User::find($request->user_id);

        return response()->json([
            'success' => true,
            'message' => 'NFC card successfully bound!',
            'card' => [
                'id'           => $card->id,
                'tag_id'       => $card->tag_id,
                'user_id'      => $user->id,
                'student_name' => $user->last_name . ', ' . $user->first_name,
                'lrn'          => $user->id_number ?? 'N/A',
                'grade'        => $user->grade_level ?? 'N/A',
                'section'      => $user->section ?? 'N/A',
                'date'         => $card->created_at->format('M d, Y h:i A'),
            ],
        ]);
    }

    // Standard DELETE unbind (redirect)
    public function bindingDestroy(Request $request, $id)
    {
        if (Schema::hasTable('nfc_cards')) {
            $nfcCard = NfcCard::with('user')->find($id);

            if ($nfcCard) {
                $reason       = $request->input('unbind_reason', 'lost');
                $userId       = $nfcCard->user_id;
                $student      = $nfcCard->user;
                $studentName  = $student ? "{$student->first_name} {$student->last_name}" : 'Student';
                $tagId        = $nfcCard->tag_id;

                $nfcCard->delete();

                if ($reason === 'replacement') {
                    $encodedName = urlencode(($student->last_name ?? '') . ', ' . ($student->first_name ?? ''));
                    return redirect()->route('admin.nfc.binding', [
                        'student_id'   => $userId,
                        'student_name' => $encodedName,
                    ])->with('success', "Previous card unlinked. Please scan the new card for {$studentName}.");
                }

                return back()->with('success', "Card UID [{$tagId}] for {$studentName} successfully unlinked.");
            }
        }

        return back()->with('error', 'NFC card record not found.');
    }

    // AJAX JSON: Unbind/Remove NFC card
    public function destroyAjax($id)
    {
        $nfcCard = NfcCard::with('user')->find($id);

        if (!$nfcCard) {
            return response()->json(['success' => false, 'message' => 'NFC card record not found.'], 404);
        }

        $nfcCard->delete();
        Cache::forget('latest_nfc_tap');

        return response()->json(['success' => true, 'message' => 'NFC binding removed successfully.']);
    }

    // AJAX: Clear the cached NFC tap (so stale UIDs don't pollute new scans)
    public function clearTap()
    {
        Cache::forget('latest_nfc_tap');
        return response()->json(['success' => true]);
    }

    // API: Receive NFC tap from Python Bridge, cache it
    public function handleTap(Request $request)
    {
        $uid = $request->input('card_uid') ?? $request->input('tag_id');

        if ($uid) {
            Cache::put('latest_nfc_tap', strtoupper(trim($uid)), 30);
        }

        return response()->json([
            'success' => true,
            'message' => 'Card tap received successfully',
            'uid'     => $uid,
        ]);
    }

    // API: Receive a liveness signal from the Python NFC bridge.
    public function heartbeat()
    {
        if (request()->boolean('reader_connected')) {
            Cache::put('nfc_bridge_heartbeat', now()->timestamp, 30);
            Cache::forget('nfc_bridge_missing_reader');
        } else {
            $missingReports = (int) Cache::get('nfc_bridge_missing_reader', 0) + 1;
            Cache::put('nfc_bridge_missing_reader', $missingReports, 30);

            if ($missingReports >= 3) {
                Cache::forget('nfc_bridge_heartbeat');
            }
        }

        return response()->json(['success' => true]);
    }

    // API: Return the latest cached NFC tap UID for browser polling
    public function getLatestTap()
    {
        $uid = Cache::get('latest_nfc_tap', '');
        $heartbeat = Cache::get('nfc_bridge_heartbeat');
        $bridgeOnline = is_numeric($heartbeat) && now()->timestamp - (int) $heartbeat <= 15;
        $bridgeStatus = $bridgeOnline
            ? 'online'
            : ($heartbeat === null ? 'waiting' : 'disconnected');
        $binding = $uid
            ? NfcCard::with('user')
                ->where('status', 'active')
                ->where('tag_id', strtoupper(trim($uid)))
                ->first()
            : null;

        return response()->json([
            'card_uid' => $uid,
            'bridge_online' => $bridgeOnline,
            'bridge_status' => $bridgeStatus,
            'is_bound' => (bool) $binding,
            'binding' => $binding ? [
                'id' => $binding->id,
                'user_id' => $binding->user_id,
                'student_name' => $binding->user
                    ? "{$binding->user->last_name}, {$binding->user->first_name}"
                    : 'Assigned student',
            ] : null,
        ]);
    }
}