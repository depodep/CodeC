<?php

namespace App\Services;

class IprogSmsService
{
    public static function send($phone, $message)
    {
        return app(SmsGatewayService::class)->sendSms($phone, $message);
    }

    public static function ping()
    {
        return app(SmsGatewayService::class)->testConnection()['success'];
    }
}
