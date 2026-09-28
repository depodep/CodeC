<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SmsGatewayService
{
    private const KEYS = ['url' => 'sms_gateway_url', 'login' => 'sms_gateway_login', 'password' => 'sms_gateway_password', 'validate_numbers' => 'sms_validate_numbers', 'timeout' => 'sms_timeout', 'delay' => 'sms_message_delay_seconds', 'rate_limit' => 'sms_enable_rate_limiting'];

    public function getConfig(): array
    {
        $settings = $this->settings();
        $storedPassword = $settings[self::KEYS['password']] ?? null;
        return [
            'url' => $settings[self::KEYS['url']] ?? env('SMS_GATEWAY_URL', ''),
            'login' => $settings[self::KEYS['login']] ?? env('SMS_GATEWAY_LOGIN', ''),
            'password' => $this->decrypt($storedPassword ?: env('SMS_GATEWAY_PASSWORD', '')),
            'validate_numbers' => $this->boolean($settings[self::KEYS['validate_numbers']] ?? env('SMS_VALIDATE_NUMBERS', true)),
            'timeout' => max(1, min(300, (int) ($settings[self::KEYS['timeout']] ?? env('SMS_TIMEOUT', 30)))),
            'message_delay_seconds' => max(0, (int) ($settings[self::KEYS['delay']] ?? env('SMS_MESSAGE_DELAY_SECONDS', 60))),
            'enable_rate_limiting' => $this->boolean($settings[self::KEYS['rate_limit']] ?? env('SMS_ENABLE_RATE_LIMITING', true)),
        ];
    }

    public function hasPassword(): bool
    {
        return filled($this->settings()[self::KEYS['password']] ?? null) || filled(env('SMS_GATEWAY_PASSWORD'));
    }

    public function status(): array
    {
        $settings = $this->settings();
        return ['status' => $settings['sms_gateway_last_status'] ?? null, 'checked_at' => $settings['sms_gateway_last_checked_at'] ?? null];
    }

    public function saveConfig(array $data): void
    {
        $this->save(self::KEYS['url'], trim($data['url']));
        $this->save(self::KEYS['login'], trim($data['login']));
        $this->save(self::KEYS['validate_numbers'], $data['validate_numbers'] ? '1' : '0');
        $this->save(self::KEYS['timeout'], (string) $data['timeout']);
        $this->save(self::KEYS['delay'], (string) $data['message_delay_seconds']);
        $this->save(self::KEYS['rate_limit'], $data['enable_rate_limiting'] ? '1' : '0');
        if (filled($data['password'] ?? null)) $this->save(self::KEYS['password'], encrypt($data['password']));
    }

    public function testConnection(): array
    {
        $started = microtime(true);
        try {
            $config = $this->getConfig();
            if (blank($config['url']) || blank($config['login']) || blank($config['password'])) {
                return $this->checked(false, 'SMS API is not configured. Open Configure SMS API.', (int) round((microtime(true) - $started) * 1000));
            }
            $response = $this->client($config)->post($this->apiUrl($config['url'], '/3rdparty/v1/auth/token'), [
                'scopes' => ['messages:send'],
                'ttl' => 60,
            ]);
            $ms = (int) round((microtime(true) - $started) * 1000);
            if (in_array($response->status(), [401, 403], true)) {
                return $this->checked(false, 'SMS gateway authentication failed. Verify the API login and password in Configure SMS API.', $ms);
            }
            if ($response->failed()) return $this->checked(false, 'Gateway returned HTTP ' . $response->status(), $ms);
            if ($response->status() !== 201 || blank($response->json('accessToken') ?? $response->json('access_token'))) {
                return $this->checked(false, 'Gateway authentication was not confirmed.', $ms);
            }
            return $this->checked(true, 'Gateway authentication succeeded.', $ms);
        } catch (Throwable $exception) {
            $message = strtolower($exception->getMessage());
            $reason = str_contains($message, 'timed out') ? 'Connection timeout' : (str_contains($message, 'resolve') ? 'DNS or host failure' : (str_contains($message, 'refused') ? 'Connection refused' : 'Unable to connect to the configured SMS gateway.'));
            Log::warning('SMS gateway connection failed.', ['reason' => $reason]);
            return $this->checked(false, $reason, (int) round((microtime(true) - $started) * 1000));
        }
    }

    public function sendSms(string $number, string $message): bool
    {
        $config = $this->getConfig();
        if ($config['validate_numbers'] && !preg_match('/^\+?[0-9][0-9\s().-]{6,20}$/', $number)) return false;
        try {
            $tokenResponse = $this->client($config)->post($this->apiUrl($config['url'], '/3rdparty/v1/auth/token'), [
                'scopes' => ['messages:send'],
                'ttl' => 60,
            ]);
            $token = $tokenResponse->json('accessToken') ?? $tokenResponse->json('access_token');
            if ($tokenResponse->status() !== 201 || blank($token)) return false;

            return Http::withoutVerifying()->acceptJson()->timeout($config['timeout'])
                ->withToken($token)
                ->post($this->apiUrl($config['url'], '/3rdparty/v1/messages'), [
                    'phoneNumbers' => [$number],
                    'message' => $message,
                ])->successful();
        } catch (Throwable) {
            Log::warning('SMS gateway send failed.');
            return false;
        }
    }

    private function client(array $config)
    {
        return Http::withoutVerifying()->acceptJson()->timeout($config['timeout'])->withBasicAuth($config['login'], $config['password']);
    }

    private function safeUrl(string $url): string
    {
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) throw new \InvalidArgumentException('Invalid gateway URL.');

        if ((int) ($parts['port'] ?? 0) === 443 && strtolower($parts['scheme']) === 'http') {
            $url = 'https://' . substr($url, strlen($parts['scheme']) + 3);
        }

        return $url;
    }

    private function apiUrl(string $url, string $path): string
    {
        return rtrim($this->safeUrl($url), '/') . $path;
    }

    private function checked(bool $success, string $message, int $ms): array
    {
        $this->save('sms_gateway_last_status', $success ? 'connected' : 'failed');
        $this->save('sms_gateway_last_checked_at', now()->toDateTimeString());
        return ['success' => $success, 'message' => $message, 'response_time_ms' => $ms];
    }

    private function settings(): array
    {
        if (!Schema::hasTable('settings')) return [];
        return DB::table('settings')->whereIn('key', array_merge(array_values(self::KEYS), ['sms_gateway_last_status', 'sms_gateway_last_checked_at']))->pluck('value', 'key')->all();
    }

    private function save(string $key, string $value): void
    {
        if (Schema::hasTable('settings')) DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
    }

    private function decrypt(string $value): string
    {
        try { return filled($value) ? decrypt($value) : ''; } catch (Throwable) { return $value; }
    }

    private function boolean(mixed $value): bool { return filter_var($value, FILTER_VALIDATE_BOOLEAN); }
}
