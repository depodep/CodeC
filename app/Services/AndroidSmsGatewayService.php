<?php

namespace App\Services;

use App\Models\OutboundMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AndroidSmsGatewayService
{
    /**
     * Retrieve the active configuration merged from database settings & config.
     */
    public function getConfig(): array
    {
        $dbSettings = $this->loadDbSettings();

        $url = $dbSettings['sms_gateway_url'] ?? config('sms.gateway_url', 'http://192.168.1.100:8080');
        $login = $dbSettings['sms_gateway_login'] ?? config('sms.login', '');
        $password = $this->decryptValue($dbSettings['sms_gateway_password'] ?? null) ?: config('sms.password', '');
        $senderId = $dbSettings['sms_sender_id'] ?? config('sms.sender_id', 'Scan-to-notify');
        $timeout = (int) ($dbSettings['sms_timeout'] ?? config('sms.timeout', 15));
        $validateNumbers = filter_var($dbSettings['sms_validate_numbers'] ?? config('sms.validate_numbers', true), FILTER_VALIDATE_BOOLEAN);
        $enableRateLimit = filter_var($dbSettings['sms_enable_rate_limiting'] ?? config('sms.enable_rate_limiting', true), FILTER_VALIDATE_BOOLEAN);
        $cooldown = (int) ($dbSettings['sms_message_delay_seconds'] ?? config('sms.cooldown_seconds', 60));

        return [
            'url'                  => rtrim(trim((string)$url), '/'),
            'login'                => trim((string)$login),
            'password'             => (string)$password,
            'sender_id'            => trim((string)$senderId) ?: 'Scan-to-notify',
            'timeout'              => max(1, min(60, $timeout)),
            'validate_numbers'     => $validateNumbers,
            'enable_rate_limiting' => $enableRateLimit,
            'cooldown_seconds'     => max(5, $cooldown),
        ];
    }

    /**
     * Normalize Philippine phone numbers to international standard +639XXXXXXXXX.
     * Accepts:
     * - 09XXXXXXXXX (11 digits)
     * - +639XXXXXXXXX (13 characters)
     * - 639XXXXXXXXX (12 digits)
     * - 9XXXXXXXXX (10 digits)
     *
     * Returns null for invalid formats.
     */
    public function normalizePhoneNumber(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        // Strip non-digit characters except leading plus
        $cleaned = trim($number);
        $hasLeadingPlus = str_starts_with($cleaned, '+');
        $digitsOnly = preg_replace('/\D/', '', $cleaned);

        if (empty($digitsOnly)) {
            Log::warning("SMS Gateway: Empty phone number after stripping non-digits.", ['original' => $number]);
            return null;
        }

        // Match 639XXXXXXXXX
        if (str_starts_with($digitsOnly, '639') && strlen($digitsOnly) === 12) {
            return '+' . $digitsOnly;
        }

        // Match 09XXXXXXXXX
        if (str_starts_with($digitsOnly, '09') && strlen($digitsOnly) === 11) {
            return '+63' . substr($digitsOnly, 1);
        }

        // Match 9XXXXXXXXX
        if (str_starts_with($digitsOnly, '9') && strlen($digitsOnly) === 10) {
            return '+63' . $digitsOnly;
        }

        Log::warning("SMS Gateway: Invalid Philippine mobile format skipped.", [
            'original' => $number,
            'cleaned'  => $digitsOnly,
        ]);

        return null;
    }

    /**
     * Check if a phone number is a valid Philippine mobile number.
     */
    public function isValidPhilippineNumber(?string $number): bool
    {
        return $this->normalizePhoneNumber($number) !== null;
    }

    /**
     * Check if a normalized phone number is currently within the cooldown rate-limit window.
     * Enforces: OutboundMessage::where('contact_number', $number)->where('last_sent_at', '>', now()->subSeconds(60))
     */
    public function isRateLimited(string $normalizedNumber): bool
    {
        $config = $this->getConfig();
        if (!$config['enable_rate_limiting']) {
            return false;
        }

        $cooldown = $config['cooldown_seconds'];

        return OutboundMessage::where('contact_number', $normalizedNumber)
            ->where('last_sent_at', '>', now()->subSeconds($cooldown))
            ->exists();
    }

    /**
     * Ping the Android SMS Gateway and measure round-trip response latency.
     */
    public function ping(): array
    {
        $config = $this->getConfig();
        $startTime = microtime(true);

        if (blank($config['url'])) {
            Log::warning('SMS gateway ping skipped: gateway URL is not configured.');
            return [
                'online'      => false,
                'latency'     => 0,
                'status_code' => null,
                'message'     => 'Gateway URL not configured',
            ];
        }

        try {
            $client = Http::withoutVerifying()
                ->acceptJson()
                ->timeout($config['timeout']);

            if (!empty($config['login']) && !empty($config['password'])) {
                $client = $client->withBasicAuth($config['login'], $config['password']);
            }

            // 1. Try cloud / 3rdparty endpoint used in settings
            $response = $client->post($config['url'] . '/3rdparty/v1/auth/token', [
                'scopes' => ['messages:send'],
                'ttl' => 60,
            ]);

            // If not found or unsupported, fallback to /message or root URL
            if ($response->status() === 404 || $response->status() === 405) {
                $response = $client->get($config['url'] . '/message');
                if (in_array($response->status(), [404, 405])) {
                    $response = $client->get($config['url']);
                }
            }

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            Log::info('SMS gateway ping response received.', [
                'endpoint' => $config['url'],
                'status_code' => $statusCode,
                'latency_ms' => $latency,
                'response_body' => $this->logResponseBody($response->body()),
            ]);

            if (in_array($statusCode, [401, 403], true)) {
                return [
                    'online'      => false,
                    'latency'     => $latency,
                    'status_code' => $statusCode,
                    'message'     => 'Authentication Failed (Check Login & Password)',
                ];
            }

            // 200, 201 (auth token generated), 202, 204
            $isOnline = $response->successful() || in_array($statusCode, [200, 201, 202, 204], true);

            return [
                'online'      => $isOnline,
                'latency'     => $latency,
                'status_code' => $statusCode,
                'message'     => $isOnline ? "Online ({$latency}ms)" : "Offline (HTTP {$statusCode})",
            ];
        } catch (Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $msg = strtolower($e->getMessage());
            $reason = str_contains($msg, 'timed out') ? 'Connection Timeout' : (str_contains($msg, 'refused') ? 'Connection Refused' : 'Connection Failed');

            Log::error('SMS gateway ping failed.', [
                'endpoint' => $config['url'],
                'latency_ms' => $latency,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return [
                'online'      => false,
                'latency'     => $latency,
                'status_code' => null,
                'message'     => "Offline ({$reason})",
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch an SMS message to a single recipient through the Android Gateway.
     *
     * @param string $rawNumber
     * @param string $messageText
     * @param array $meta [sender_user_id, student_id, recipient_name, dispatch_mode, sender_id]
     * @return array [success => bool, record => OutboundMessage, skipped => bool, reason => ?string]
     */
    public function sendSingleMessage(string $rawNumber, string $messageText, array $meta = []): array
    {
        $config = $this->getConfig();
        $senderId = $meta['sender_id'] ?? $config['sender_id'];
        $senderUserId = $meta['sender_user_id'] ?? auth()->id();
        $studentId = $meta['student_id'] ?? null;
        $recipientName = $meta['recipient_name'] ?? null;
        $dispatchMode = $meta['dispatch_mode'] ?? 'single';
        $notificationKey = $meta['notification_key'] ?? null;

        if ($notificationKey) {
            $existing = OutboundMessage::where('notification_key', $notificationKey)
                ->latest('id')
                ->first();
            if ($existing) {
                if ($existing->status === 'failed' && empty($meta['retry_failed']) && $existing->created_at?->gt(now()->subMinutes(5))) {
                    return [
                        'success' => false,
                        'record' => $existing,
                        'skipped' => true,
                        'reason' => 'Recent failed notification is waiting for retry.',
                    ];
                }
                if ($existing->status !== 'failed') {
                    return [
                        'success' => true,
                        'record' => $existing,
                        'skipped' => true,
                        'reason' => 'Notification already recorded.',
                    ];
                }
            }
        }

        // 1. Phone number validation & normalization
        $normalizedNumber = $this->normalizePhoneNumber($rawNumber);

        if (!$normalizedNumber) {
            Log::warning('SMS dispatch skipped: invalid phone number.', [
                'phone_number' => $this->maskPhoneNumber($rawNumber),
                'dispatch_mode' => $dispatchMode,
            ]);

            $record = OutboundMessage::create([
                'sender_user_id' => $senderUserId,
                'student_id'     => $studentId,
                'recipient_name' => $recipientName,
                'contact_number' => $rawNumber ?: 'UNKNOWN',
                'message'        => $messageText,
                'sender_id'      => $senderId,
                'status'         => 'failed',
                'dispatch_mode'  => $dispatchMode,
                'notification_key' => $notificationKey,
                'error_message'  => 'Invalid Philippine phone number format (must be 09XXXXXXXXX or +639XXXXXXXXX).',
                'last_sent_at'   => null,
            ]);

            return [
                'success' => false,
                'record'  => $record,
                'skipped' => true,
                'reason'  => 'Invalid phone number format',
            ];
        }

        // 2. Rate Limiting Check
        if (empty($meta['bypass_rate_limit']) && $this->isRateLimited($normalizedNumber)) {
            $cooldown = $config['cooldown_seconds'];
            Log::warning('SMS dispatch skipped: recipient is on cooldown.', [
                'phone_number' => $this->maskPhoneNumber($normalizedNumber),
                'cooldown_seconds' => $cooldown,
                'dispatch_mode' => $dispatchMode,
            ]);

            $record = OutboundMessage::create([
                'sender_user_id' => $senderUserId,
                'student_id'     => $studentId,
                'recipient_name' => $recipientName,
                'contact_number' => $normalizedNumber,
                'message'        => $messageText,
                'sender_id'      => $senderId,
                'status'         => 'failed',
                'dispatch_mode'  => $dispatchMode,
                'notification_key' => $notificationKey,
                'error_message'  => "Rate-limited: Message to this number was sent within the last {$cooldown} seconds.",
                'last_sent_at'   => null,
            ]);

            return [
                'success' => false,
                'record'  => $record,
                'skipped' => true,
                'reason'  => "Rate limited (sent within last {$cooldown}s)",
            ];
        }

        // 3. Prepare the 3rd-party API payload.
        $payload = [
            'phoneNumbers' => [$normalizedNumber],
            'message' => $messageText,
        ];

        // 4. Authenticate, then send POST request to the 3rd-party messages endpoint.
        $tokenEndpoint = $config['url'] . '/3rdparty/v1/auth/token';
        $endpoint = $config['url'] . '/3rdparty/v1/messages';
        $startedAt = microtime(true);

        Log::info('SMS dispatch started.', [
            'endpoint' => $endpoint,
            'token_endpoint' => $tokenEndpoint,
            'phone_number' => $this->maskPhoneNumber($normalizedNumber),
            'message_length' => mb_strlen($messageText),
            'sender_id' => $senderId,
            'dispatch_mode' => $dispatchMode,
            'has_basic_auth' => $config['login'] !== '' && $config['password'] !== '',
        ]);

        try {
            $client = Http::withoutVerifying()
                ->acceptJson()
                ->timeout($config['timeout']);

            if (!empty($config['login']) || !empty($config['password'])) {
                $client = $client->withBasicAuth($config['login'], $config['password']);
            }

            $tokenResponse = $client->post($tokenEndpoint, [
                'scopes' => ['messages:send'],
                'ttl' => 60,
            ]);
            $token = $tokenResponse->json('accessToken') ?? $tokenResponse->json('access_token');

            if ($tokenResponse->status() !== 201 || blank($token)) {
                Log::error('SMS gateway token request failed during dispatch.', [
                    'endpoint' => $tokenEndpoint,
                    'status_code' => $tokenResponse->status(),
                    'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'response_body' => $this->logResponseBody($tokenResponse->body()),
                ]);

                throw new \RuntimeException('SMS gateway authentication failed before message dispatch.');
            }

            $response = Http::withoutVerifying()
                ->acceptJson()
                ->timeout($config['timeout'])
                ->withToken($token)
                ->post($endpoint, $payload);
            $statusCode = $response->status();
            $body = $response->body();
            $json = $response->json();
            $latency = (int) round((microtime(true) - $startedAt) * 1000);

            // Extract Gateway Message ID
            $gatewayMsgId = null;
            if (is_array($json)) {
                $gatewayMsgId = $json['id']
                    ?? $json['messageId']
                    ?? ($json['messages'][0]['id'] ?? null)
                    ?? ($json[0]['id'] ?? null);
            }

            $isAccepted = $response->successful() || in_array($statusCode, [200, 201, 202]);
            $gatewayState = is_array($json) ? ($json['state'] ?? ($json['recipients'][0]['state'] ?? null)) : null;
            $status = $isAccepted ? $this->mapGatewayStatus($gatewayState) : 'failed';
            $errorMsg = $isAccepted ? null : "Gateway HTTP Error {$statusCode}: " . substr($body, 0, 255);

            Log::log($isAccepted ? 'info' : 'error', $isAccepted
                ? 'SMS gateway accepted dispatch.'
                : 'SMS gateway rejected dispatch.', [
                    'endpoint' => $endpoint,
                    'phone_number' => $this->maskPhoneNumber($normalizedNumber),
                    'status_code' => $statusCode,
                    'latency_ms' => $latency,
                    'gateway_state' => $gatewayState,
                    'stored_status' => $status,
                    'gateway_message_id' => $gatewayMsgId,
                    'response_body' => $this->logResponseBody($body),
                ]);

            $record = OutboundMessage::create([
                'sender_user_id'     => $senderUserId,
                'student_id'         => $studentId,
                'recipient_name'     => $recipientName,
                'contact_number'     => $normalizedNumber,
                'message'            => $messageText,
                'sender_id'          => $senderId,
                'gateway_message_id' => $gatewayMsgId ? (string)$gatewayMsgId : null,
                'status'             => $status,
                'dispatch_mode'      => $dispatchMode,
                'notification_key'   => $notificationKey,
                'api_response'       => $body,
                'error_message'      => $errorMsg,
                'last_sent_at'       => $isAccepted ? now() : null,
            ]);

            return [
                'success' => $isAccepted,
                'record'  => $record,
                'skipped' => false,
                'reason'  => $errorMsg,
            ];
        } catch (Throwable $e) {
            Log::error('SMS gateway dispatch failed with exception.', [
                'recipient' => $normalizedNumber,
                'endpoint'  => $endpoint,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            $record = OutboundMessage::create([
                'sender_user_id'     => $senderUserId,
                'student_id'         => $studentId,
                'recipient_name'     => $recipientName,
                'contact_number'     => $normalizedNumber,
                'message'            => $messageText,
                'sender_id'          => $senderId,
                'gateway_message_id' => null,
                'status'             => 'failed',
                'dispatch_mode'      => $dispatchMode,
                'notification_key'   => $notificationKey,
                'api_response'       => null,
                'error_message'      => 'Connection Error: ' . $e->getMessage(),
                'last_sent_at'       => null,
            ]);

            return [
                'success' => false,
                'record'  => $record,
                'skipped' => false,
                'reason'  => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch send messages to a list of recipients.
     * Skips and logs invalid or rate-limited numbers without crashing the loop.
     *
     * @param array $recipients Array of [number, name, student_id]
     * @param string $message
     * @param array $meta
     * @return array Summary of dispatch results
     */
    public function sendBatchMessages(array $recipients, string $message, array $meta = []): array
    {
        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $skippedReasons = [];
        $results = [];

        foreach ($recipients as $recipient) {
            $number = is_array($recipient) ? ($recipient['number'] ?? '') : (string)$recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? null) : null;
            $studentId = is_array($recipient) ? ($recipient['student_id'] ?? null) : null;

            $itemMeta = array_merge($meta, [
                'recipient_name' => $name,
                'student_id'     => $studentId,
            ]);

            // Replace student specific placeholders if provided
            $personalizedMessage = is_array($recipient) && isset($recipient['message'])
                ? $recipient['message']
                : $message;
            if ($name) {
                $student = $studentId ? User::find($studentId) : null;
                $section = $student?->section ?: 'General';
                $placeholderName = $student?->full_name ?: $name;
                $personalizedMessage = str_replace(
                    ['[STUDENT_NAME]', '{student_name}', '[SECTION]', '{section}'],
                    [$placeholderName, $placeholderName, $section, $section],
                    $personalizedMessage
                );
            }

            $res = $this->sendSingleMessage($number, $personalizedMessage, $itemMeta);
            $results[] = $res;

            if (!empty($res['skipped'])) {
                $skippedCount++;
                $skippedReasons[] = [
                    'recipient' => $name ?: 'Unknown recipient',
                    'reason' => $res['reason'] ?: 'Recipient was skipped by the SMS gateway.',
                ];
            } elseif ($res['success']) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        return [
            'total'   => count($recipients),
            'sent'    => $sentCount,
            'failed'  => $failedCount,
            'skipped' => $skippedCount,
            'skipped_reasons' => $skippedReasons,
            'results' => $results,
        ];
    }

    /**
     * Hook to send attendance notifications (Time In / Time Out) triggered by NFC scans.
     */
    public function sendAttendanceNotification(User $student, string $status, ?string $time = null): ?OutboundMessage
    {
        // 1. Resolve contact number: parent_phone_number first, fallback to phone_number
        $contactNumber = $student->parent_phone_number 
            ?: $student->phone_number 
            ?: $student->contact_person_contact;

        if (blank($contactNumber)) {
            Log::info("SMS Attendance Alert Skipped: No contact number found for student #{$student->id} ({$student->full_name}).");
            return null;
        }

        $studentName = $student->full_name ?: ($student->first_name . ' ' . $student->last_name);
        $sectionName = $student->section ?: ($student->section_name ?: 'General');
        $timeStr = $time ?: Carbon::now()->format('g:i A');
        $dateStr = Carbon::now()->format('M d, Y');
        $actionUpper = strtoupper(trim($status));

        // Format message
        if ($actionUpper === 'TIME OUT') {
            $template = config('sms.templates.time_out', 'SIATRACK Alert: [STUDENT_NAME] recorded TIME OUT at [TIME] on [DATE]. Safe travels home!');
        } else {
            $template = config('sms.templates.time_in', 'SIATRACK Alert: [STUDENT_NAME] recorded TIME IN at [TIME] on [DATE]. Have a great day!');
        }

        $message = strtr($template, [
            '[STUDENT_NAME]' => $studentName,
            '[SECTION]'      => $sectionName,
            '[TIME]'         => $timeStr,
            '[DATE]'         => $dateStr,
        ]);

        $res = $this->sendSingleMessage($contactNumber, $message, [
            'student_id'     => $student->id,
            'recipient_name' => $student->parent_name ?: "Parent of {$studentName}",
            'dispatch_mode'  => 'attendance_auto',
            'sender_id'      => 'Scan-to-notify',
            'sender_user_id' => auth()->id() ?: null,
        ]);

        return $res['record'] ?? null;
    }

    /**
     * Load settings from database table if present.
     */
    protected function loadDbSettings(): array
    {
        if (!Schema::hasTable('settings')) {
            return [];
        }

        try {
            return DB::table('settings')
                ->whereIn('key', [
                    'sms_gateway_url',
                    'sms_gateway_login',
                    'sms_gateway_password',
                    'sms_sender_id',
                    'sms_timeout',
                    'sms_validate_numbers',
                    'sms_message_delay_seconds',
                    'sms_enable_rate_limiting',
                ])
                ->pluck('value', 'key')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Safely decrypt values stored by Admin Settings.
     */
    protected function decryptValue(?string $val): ?string
    {
        if (blank($val)) {
            return null;
        }
        try {
            return decrypt($val);
        } catch (Throwable) {
            return $val;
        }
    }

    private function maskPhoneNumber(?string $number): string
    {
        $number = (string) $number;

        return strlen($number) > 4
            ? str_repeat('*', max(0, strlen($number) - 4)) . substr($number, -4)
            : $number;
    }

    private function logResponseBody(string $body): string
    {
        $body = trim($body);

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            foreach (['accessToken', 'access_token', 'refreshToken', 'refresh_token', 'token', 'password'] as $secretKey) {
                if (array_key_exists($secretKey, $decoded)) {
                    $decoded[$secretKey] = '[redacted]';
                }
            }
            $body = (string) json_encode($decoded);
        }

        return mb_strlen($body) > 1000 ? mb_substr($body, 0, 1000) . '...[truncated]' : $body;
    }

    private function mapGatewayStatus(?string $gatewayState): string
    {
        return match (strtolower(trim((string) $gatewayState))) {
            'delivered' => 'delivered',
            'sent' => 'sent',
            default => 'pending',
        };
    }
}
