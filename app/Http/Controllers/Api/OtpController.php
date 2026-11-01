<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OtpController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send OTP to phone for frictionless guest checkout
     */
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:25'],
        ]);

        // Clean & sanitize phone number (allow only digits and leading plus)
        $phone = preg_replace('/[^0-9+]/', '', $validated['phone']);
        if (strlen($phone) < 9) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid Pakistani mobile phone number (e.g. 0300-1234567).',
            ], 422);
        }
        
        // Generate a cryptographically secure 4-digit code (e.g. 5824)
        $otpCode = (string) random_int(1000, 9999);

        // Store OTP with 10-minute validity
        PhoneOtp::updateOrCreate(
            ['phone' => $phone],
            [
                'otp_code' => $otpCode,
                'expires_at' => Carbon::now()->addMinutes(10),
                'is_verified' => false,
            ]
        );

        // Dispatch via SmsService (Twilio, Infobip, Custom Gateway, or Simulation)
        $smsResult = $this->smsService->sendOtp($phone, $otpCode);

        return response()->json([
            'success' => true,
            'message' => $smsResult['message'] ?? "Verification code sent to {$phone}",
            'is_real_sms' => $smsResult['is_real_sms'] ?? false,
            'driver' => $smsResult['driver'] ?? config('sms.default'),
            'demo_code' => $smsResult['demo_code'] ?? null, // Displayed in UI only when simulated
            'expires_in_minutes' => 10,
        ]);
    }

    /**
     * Verify OTP entered by customer
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'otp_code' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $validated['phone']);
        $otp = PhoneOtp::where('phone', $phone)->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'No verification code was requested for this phone number.',
            ], 422);
        }

        if (Carbon::now()->greaterThan($otp->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code has expired. Please request a new code.',
            ], 422);
        }

        if ($otp->otp_code !== $validated['otp_code']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please check and try again.',
            ], 422);
        }

        $otp->update(['is_verified' => true]);

        $customer = \App\Models\Customer::where('phone', $phone)->first();

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
            'phone' => $phone,
            'customer' => $customer ? [
                'name' => $customer->name,
                'email' => $customer->email,
                'address' => $customer->address,
                'city' => $customer->city,
            ] : null,
            'verification_token' => sha1($phone . $otp->otp_code . config('app.key')),
        ]);
    }
}
