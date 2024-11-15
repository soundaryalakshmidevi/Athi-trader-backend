<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Otp;
use App\Models\User;
use App\Models\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;



class ForgotPasswordController extends Controller
{
public function sendOTP(Request $request)
{
    ob_clean();
    // Validate the request
    $request->validate([
    'email' => [
        'required',
        'string',
        'email:rfc,dns', // Validate proper email structure and domain
        // Regex to ensure at least 2 characters before the "@" and proper domain
        'regex:/^[a-zA-Z0-9._%+-]{4,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
    ],
    // 'phone' => 'nullable|regex:/^[0-9]{10}$/', // Optional phone validation
], 
[
    'email.required' => 'The email field is required.',
    'email.email' => 'The email must be a valid email address.',
    'email.regex' => 'The email is not in a valid format.', // Custom message for regex validation
    // 'phone.regex' => 'The phone number must be a 10-digit number.'
]);


    // Check if the email exists in the users table
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Email not found'], 404);
    }

    // Generate a random OTP
    $otp = mt_rand(100000, 999999);

    try {
        // Start transaction
        DB::beginTransaction();

        // Store OTP in the database
        $otpRecord = Otp::updateOrCreate(
            ['email' => $request->email], // Where condition
            ['otp' => $otp]               // Update or create with this OTP
        );

        // Convert created_at to local time
        $createdAtLocal = Carbon::now('UTC')->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        // Update the created_at field in the database
        $otpRecord->created_at = $createdAtLocal;
        $otpRecord->save();

        // Commit transaction
        DB::commit();

        // Send OTP via email
        try {
            Mail::raw("Your OTP for password reset is $otp", function ($message) use ($request) {
                $message->to([$request->email, 'testprintmysproject@gmail.com'])
                    ->subject('OTP for Password Reset');
            });

            // Log the success of email
            Log::info('OTP email sent to ' . $request->email . ' and testprintmysproject@gmail.com with OTP: ' . $otp);

        } catch (\Exception $e) {
            // If email fails, rollback and return error response
            Log::error('Failed to send OTP email', ['error' => $e->getMessage()]);
            DB::rollBack();
            return response()->json(['message' => 'Failed to send OTP email.'], 500);
        }

        // Return success response
        return response()->json([
            'message' => 'OTP sent successfully',
            // 'otp' => $otp,
            // 'created_at' => $createdAtLocal,
            // 'phone' => $request->phone // Optionally return phone if provided
        ]);

    } catch (\Exception $e) {
        // Rollback transaction in case of error
        Log::error('Failed to generate OTP', ['error' => $e->getMessage()]);
        DB::rollBack();
        return response()->json(['message' => 'Failed to generate OTP. Please try again later.'], 500);
    }
}


// public function sendOTPWithPhone(Request $request)
// {
//   // Validate the request
//     $request->validate([
//     'email' => [
//         'required',
//         'string',
//         'email:rfc,dns', // Validate proper email structure and domain
//         // Regex to ensure at least 2 characters before the "@" and proper domain
//         'regex:/^[a-zA-Z0-9._%+-]{4,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
//     ],
//     'phone' => 'nullable|regex:/^[0-9]{10}$/', // Optional phone validation
// ], 
// [
//     'email.required' => 'The email field is required.',
//     'email.email' => 'The email must be a valid email address.',
//     'email.regex' => 'The email is not in a valid format.', // Custom message for regex validation
//     'phone.regex' => 'The phone number must be a 10-digit number.'
// ]);
//     // Check if the phone number exists in the users table
//     $user = User::where('phone', $request->phone)->first();

//     if (!$user) {
//         return response()->json(['message' => 'Phone number not found .contact to Admin'], 404);
//     }

//     // Update the email for this phone number
//     $user->email = $request->email;
//     $user->save();

//     // Generate a random OTP
//     $otp = mt_rand(100000, 999999);

//     try {
//         // Start transaction
//         DB::beginTransaction();

//         // Store OTP in the database
//         $otpRecord = Otp::updateOrCreate(
//             ['email' => $request->email], // Store OTP associated with the phone number
//             ['otp' => $otp]
//         );

//         $utcDate = Carbon::now('UTC');
//         // Convert the created_at field to local time zone
//         $createdAtLocal = Carbon::parse($utcDate, 'UTC')
//             ->setTimezone('Asia/Kolkata')
//             ->format('Y-m-d H:i:s');

//         // Update the created_at field in the database with the local time
//         $otpRecord->created_at = $createdAtLocal;
//         $otpRecord->save();

//         // Commit transaction
//         DB::commit();

//         // Log before sending email
//         Log::info('Sending OTP email to ' . $request->email . ' and testprintmysproject@gmail.com with OTP: ' . $otp);

//         // Send OTP via email to both the entered email and the additional email
//         Mail::raw("Your OTP for password reset is $otp", function ($message) use ($request) {
//             $message->to([$request->email, 'testprintmysproject@gmail.com'])
//                 ->subject('OTP for Password Reset');
//         });

//         // Log after sending email
//         Log::info('Email sent', ['time' => $createdAtLocal]);

//         return response()->json([
//             'message' => 'OTP sent successfully',
//             'otp' => $otp,
//             'created_at' => $createdAtLocal,
//             'phone' => $request->phone
//         ]);

//     } catch (\Exception $e) {
//         // Log the error
//         Log::error('Failed to send OTP', ['error' => $e->getMessage()]);

//         // Rollback transaction in case of an error
//         DB::rollBack();
//         return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
//     }
// }



public function verifyOTP(Request $request)
{
    ob_clean();
    // Validate incoming request
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|numeric',
    ]);

    // Find the OTP record for the provided email
    $otpRecord = Otp::where('email', $request->email)->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'Invalid email'], 404);
    }

    // Check if the provided OTP matches the record
    if ($otpRecord->otp != $request->otp) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    // Get the current UTC time
    $utcDate = Carbon::now('UTC');
     $utcparseDate = Carbon::parse($utcDate, 'UTC')
                                    ->setTimezone('Asia/Kolkata')
                                    ->format('Y-m-d H:i:s');
    Log::info(['utcparseDate' => $utcparseDate]);

    // Parse the OTP creation time
    $otpCreatedAt = Carbon::parse($otpRecord->created_at, 'UTC');
    Log::info(['otpCreatedAt' => $otpCreatedAt]);

    // Add 3 minutes to the OTP creation time
    $threeMinutesLater = $otpCreatedAt->copy()->addMinutes(3);
    Log::info(['threeMinutesLater' => $threeMinutesLater]);

    // Compare the current time with the adjusted OTP creation time
  if ($utcparseDate >= $threeMinutesLater) {
        // OTP is expired
        return response()->json(['message' => 'OTP has expired. Please request a new OTP.'], 400);
    }

    // OTP is valid and not expired
    return response()->json(['message' => 'OTP verified successfully'], 200);
}

public function resetPassword(Request $request)
{
    ob_clean();
    $request->validate([
        'email' => 'required|email',
        'newPassword' => 'required|min:4',
    ]);

    $user = User::where('email', $request->email)->first();
    $Login = Login::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid email'], 404);
    } else if (!$Login) {
        return response()->json(['message' => 'Invalid email from Logins table'], 404);
    }

    // Update password for both user and login
    $Login->password = Hash::make($request->newPassword);
    $Login->save();

    $user->password = Hash::make($request->newPassword);
    $user->save();

    // Remove OTP for the email
    Otp::where('email', $request->email)->delete();

    return response()->json(['message' => 'Password reset successfully'], 200);
}



}
