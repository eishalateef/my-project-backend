<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send OTP Verification SMS to customer phone
     *
     * @param string $toPhone
     * @param string $otpCode
     * @return array
     */
    public function sendOtp(string $toPhone, string $otpCode): array
    {
        $driver = config('sms.default', 'simulation');
        $message = "Your FlexiStore verification code is: {$otpCode}. It expires in 10 minutes. Do not share this code.";

        Log::info("SMS Request: Driver [{$driver}], To [{$toPhone}], Code [{$otpCode}]");

        switch ($driver) {
            case 'twilio':
                return $this->sendViaTwilio($toPhone, $message, $otpCode);

            case 'infobip':
                return $this->sendViaInfobip($toPhone, $message, $otpCode);

            case 'custom_http':
                return $this->sendViaCustomHttp($toPhone, $message, $otpCode);

            case 'simulation':
            default:
                return [
                    'success' => true,
                    'is_real_sms' => false,
                    'driver' => 'simulation',
                    'message' => "Verification code simulated for {$toPhone}",
                    'demo_code' => $otpCode,
                ];
        }
    }

    /**
     * Send SMS via Twilio REST API
     */
    protected function sendViaTwilio(string $toPhone, string $message, string $otpCode): array
    {
        $sid = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from = trim(config('sms.twilio.from'));

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning("Twilio credentials missing in .env. Falling back to simulation mode.");
            return [
                'success' => true,
                'is_real_sms' => false,
                'driver' => 'twilio (credentials missing, simulated)',
                'message' => "Twilio credentials or phone number missing in .env. Code displayed for testing.",
                'demo_code' => $otpCode,
            ];
        }

        // Normalize destination phone to E.164 format
        $cleanTo = preg_replace('/[^0-9+]/', '', $toPhone);
        if (str_starts_with($cleanTo, '03') && strlen($cleanTo) === 11) {
            $cleanTo = '+92' . substr($cleanTo, 1); // e.g. 03223438019 -> +923223438019
        } elseif (!str_starts_with($cleanTo, '+')) {
            $cleanTo = '+' . $cleanTo;
        }

        // Normalize From phone number to E.164 format
        $cleanFrom = preg_replace('/[^0-9+]/', '', $from);
        if (!str_starts_with($cleanFrom, '+') && is_numeric($cleanFrom)) {
            $cleanFrom = '+' . $cleanFrom;
        }

        try {
            $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post($endpoint, [
                    'From' => $cleanFrom,
                    'To'   => $cleanTo,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Twilio SMS sent successfully to {$cleanTo}. SID: " . ($response->json('sid') ?? 'N/A'));
                return [
                    'success' => true,
                    'is_real_sms' => true,
                    'driver' => 'twilio',
                    'message' => "Real SMS verification code dispatched to {$cleanTo}",
                    'message_id' => $response->json('sid'),
                ];
            } else {
                $errJson = $response->json();
                $errCode = $errJson['code'] ?? null;
                $errMsg = $errJson['message'] ?? 'SMS delivery failed';

                Log::error("Twilio SMS failed [Code: {$errCode}]: " . $response->body());

                $userHint = $errMsg;
                if ($errCode == 21212 || $errCode == 21606) {
                    $userHint = "Twilio Error ({$errCode}): The 'TWILIO_PHONE_NUMBER' ({$from}) must be a valid Twilio-purchased number in E.164 format (e.g. +1...), not a personal phone number.";
                } elseif ($errCode == 21408) {
                    $userHint = "Twilio Error (21408): Geo-Permission disabled for {$cleanTo}. Please enable Pakistan (+92) in Twilio Console under Messaging > Geo-Permissions.";
                } elseif ($errCode == 21614) {
                    $userHint = "Twilio Error (21614): 'To' number is not a valid mobile number.";
                }

                return [
                    'success' => true,
                    'is_real_sms' => false,
                    'driver' => 'twilio (error fallback)',
                    'message' => "Twilio error: {$userHint}",
                    'demo_code' => $otpCode,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Twilio exception: " . $e->getMessage());
            return [
                'success' => true,
                'is_real_sms' => false,
                'driver' => 'twilio (exception fallback)',
                'message' => "Twilio connection error. Code displayed for testing.",
                'demo_code' => $otpCode,
            ];
        }
    }

    /**
     * Send SMS via Infobip REST API
     */
    protected function sendViaInfobip(string $toPhone, string $message, string $otpCode): array
    {
        $baseUrl = config('sms.infobip.base_url');
        $apiKey = config('sms.infobip.api_key');
        $from = config('sms.infobip.from', 'FlexiStore');

        if (empty($baseUrl) || empty($apiKey)) {
            Log::warning("Infobip credentials missing in .env. Falling back to simulation mode.");
            return [
                'success' => true,
                'is_real_sms' => false,
                'driver' => 'infobip (credentials missing)',
                'message' => "Infobip credentials not configured in .env. Code displayed for testing.",
                'demo_code' => $otpCode,
            ];
        }

        try {
            $endpoint = "https://" . rtrim($baseUrl, '/') . "/sms/2/text/advanced";
            $response = Http::withHeaders([
                'Authorization' => "App {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($endpoint, [
                'messages' => [
                    [
                        'from' => $from,
                        'destinations' => [['to' => $toPhone]],
                        'text' => $message,
                    ]
                ]
            ]);

            if ($response->successful()) {
                Log::info("Infobip SMS sent successfully to {$toPhone}");
                return [
                    'success' => true,
                    'is_real_sms' => true,
                    'driver' => 'infobip',
                    'message' => "SMS verification code sent to {$toPhone}",
                ];
            } else {
                Log::error("Infobip SMS failed: " . $response->body());
                return [
                    'success' => true,
                    'is_real_sms' => false,
                    'driver' => 'infobip (error fallback)',
                    'message' => "Infobip error. Code displayed for testing.",
                    'demo_code' => $otpCode,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Infobip exception: " . $e->getMessage());
            return [
                'success' => true,
                'is_real_sms' => false,
                'driver' => 'infobip (exception fallback)',
                'demo_code' => $otpCode,
            ];
        }
    }

    /**
     * Send SMS via Custom HTTP / Local Telecom Gateway (Jazz, Telenor, Zong, etc.)
     */
    protected function sendViaCustomHttp(string $toPhone, string $message, string $otpCode): array
    {
        $url = config('sms.custom_http.url');
        $method = strtoupper(config('sms.custom_http.method', 'POST'));
        $apiKey = config('sms.custom_http.api_key');
        $senderId = config('sms.custom_http.sender_id');

        $paramTo = config('sms.custom_http.param_to', 'to');
        $paramMsg = config('sms.custom_http.param_message', 'message');
        $paramApiKey = config('sms.custom_http.param_api_key', 'api_key');
        $paramSender = config('sms.custom_http.param_sender_id', 'sender_id');

        if (empty($url)) {
            Log::warning("Custom SMS Gateway URL missing in .env. Falling back to simulation mode.");
            return [
                'success' => true,
                'is_real_sms' => false,
                'driver' => 'custom_http (URL missing)',
                'demo_code' => $otpCode,
            ];
        }

        try {
            $payload = [
                $paramTo => $toPhone,
                $paramMsg => $message,
                $paramApiKey => $apiKey,
                $paramSender => $senderId,
            ];

            if ($method === 'GET') {
                $response = Http::get($url, $payload);
            } else {
                $response = Http::asForm()->post($url, $payload);
            }

            if ($response->successful()) {
                Log::info("Custom gateway SMS sent to {$toPhone}");
                return [
                    'success' => true,
                    'is_real_sms' => true,
                    'driver' => 'custom_http',
                    'message' => "SMS verification code sent to {$toPhone}",
                ];
            } else {
                Log::error("Custom gateway SMS failed: " . $response->body());
                return [
                    'success' => true,
                    'is_real_sms' => false,
                    'driver' => 'custom_http (error fallback)',
                    'demo_code' => $otpCode,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Custom gateway SMS exception: " . $e->getMessage());
            return [
                'success' => true,
                'is_real_sms' => false,
                'driver' => 'custom_http (exception fallback)',
                'demo_code' => $otpCode,
            ];
        }
    }
}
