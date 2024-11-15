<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // Validate email input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid email format.',
            ], 422);
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'error' => 'No account found with this email address.',
            ], 404);
        }

        // Send password reset link
        $status = Password::sendResetLink($request->only('email'));
        Log::info('Password reset status: ' . $status);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Reset link sent to your email.',
            ], 200);
        } else {
            return response()->json([
                'error' => 'Failed to send reset link. Please try again.',
            ], 500);
        }
    }
}
