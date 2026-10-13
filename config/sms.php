<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Android SMS Gateway URL
    |--------------------------------------------------------------------------
    |
    | The base HTTP endpoint of the local Android SMS Gateway server running
    | on an Android device (e.g., http://192.168.1.100:8080 or cloud bridge).
    |
    */
    'gateway_url' => env('SMS_GATEWAY_URL', 'http://192.168.1.100:8080'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Basic Authentication Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials configured on the Android SMS Gateway application for
    | HTTP Basic Auth.
    |
    */
    'login' => env('SMS_GATEWAY_LOGIN', ''),
    'password' => env('SMS_GATEWAY_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Sender ID
    |--------------------------------------------------------------------------
    |
    | The default sender identifier dispatched to the gateway.
    |
    */
    'sender_id' => env('SMS_SENDER_ID', 'Scan-to-notify'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for the HTTP call to the Android device.
    |
    */
    'timeout' => (int) env('SMS_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Phone Number Validation & Formatting
    |--------------------------------------------------------------------------
    |
    | Whether to strictly validate Philippine phone number formats (+639XXXXXXXXX
    | or 09XXXXXXXXX) and normalize them prior to sending.
    |
    */
    'validate_numbers' => (bool) env('SMS_VALIDATE_NUMBERS', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Cooldown Settings
    |--------------------------------------------------------------------------
    |
    | Enforce a cooldown window (in seconds) between SMS dispatches to the
    | exact same phone number to prevent spam and rate-limit violations.
    |
    */
    'enable_rate_limiting' => (bool) env('SMS_ENABLE_RATE_LIMITING', true),
    'cooldown_seconds'     => (int) env('SMS_COOLDOWN_SECONDS', env('SMS_MESSAGE_DELAY_SECONDS', 60)),

    /*
    |--------------------------------------------------------------------------
    | Default Templates
    |--------------------------------------------------------------------------
    |
    | Predefined message templates with placeholder support:
    | [STUDENT_NAME], [DATE], [TIME], [SECTION], [TEACHER_NAME], [SCHOOL_NAME]
    |
    */
    'templates' => [
        'absent_today' => 'Good day. This is to inform you that [STUDENT_NAME] from Section [SECTION] was marked ABSENT today, [DATE]. Please contact the school for any clarification.',
        'time_in'      => 'SIATRACK Alert: [STUDENT_NAME] recorded TIME IN at [TIME] on [DATE]. Have a great day!',
        'time_out'     => 'SIATRACK Alert: [STUDENT_NAME] recorded TIME OUT at [TIME] on [DATE]. Safe travels home!',
        'reminder'     => 'Reminding parent/guardian of [STUDENT_NAME] ([SECTION]) regarding upcoming school activity/deadline on [DATE]. Thank you.',
        'meeting'      => 'Notice of Parent-Teacher Conference: You are invited to attend a meeting regarding [STUDENT_NAME] ([SECTION]) on [DATE]. Your presence is highly appreciated.',
        'exam'         => 'Notice: Upcoming examinations for [SECTION] will commence on [DATE]. Please ensure [STUDENT_NAME] is well prepared. Thank you.',
    ],
];
