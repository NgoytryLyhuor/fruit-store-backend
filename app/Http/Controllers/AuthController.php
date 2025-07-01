<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'in:customer,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Registration failed. Please check your input.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'customer',
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => true,
                'message' => 'Registration successful. Welcome!',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed due to server error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Login failed. Please check your input.',
                    'errors' => $validator->errors(),
                ], 200);
            }

            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Login failed. Email or password is incorrect.',
                    'error' => 'Invalid credentials',
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Login successful. Welcome back!',
                'data' => [
                    'user' => auth()->user(),
                    'token' => $token,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed due to server error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::logout();

            return response()->json([
                'status' => true,
                'message' => 'Logout successful. See you later!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Logout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id = null)
    {
        try {
            $user = $id ? User::findOrFail($id) : auth()->user();

            $validator = Validator::make($request->all(), [
                'first_name' => 'string|max:255',
                'last_name' => 'string|max:255',
                'email' => 'string|email|max:255|unique:users,email,' . $user->id,
                'current_password' => 'required_with:password|string',
                'password' => 'string|min:6',
                'street' => 'string|max:255',
                'city' => 'string|max:255',
                'postal_code' => 'string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Update failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->filled('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Current password is incorrect.',
                    ], 422);
                }

                $user->password = Hash::make($request->password);
            }

            $user->fill($request->only([
                'first_name',
                'last_name',
                'email',
                'street',
                'city',
                'postal_code'
            ]));

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Update successful.',
                'data' => [
                    'user' => $user->fresh(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Update failed due to server error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forgetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email is required.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if user exists
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found with this email address.',
                ], 404);
            }

            // Generate reset token
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email' => $request->email,
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            Mail::to($request->email)->send(new ResetPasswordMail($token, $user));

            return response()->json([
                'status' => true,
                'message' => 'Password reset link sent to your email.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send password reset link.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if user exists
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found with this email address.',
                ], 404);
            }

            // Get password reset record
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired reset token.',
                ], 400);
            }

            // Check if token matches
            if (!Hash::check($request->token, $passwordReset->token)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid reset token.',
                ], 400);
            }

            // Check if token is not expired (24 hours)
            if (now()->diffInHours($passwordReset->created_at) > 24) {
                // Delete expired token
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                return response()->json([
                    'status' => false,
                    'message' => 'Reset token has expired. Please request a new one.',
                ], 400);
            }

            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete the used token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Password reset successful. You can now login with your new password.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to reset password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend password reset email
     */
    public function resendPasswordReset(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email is required.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if user exists
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found with this email address.',
                ], 404);
            }

            // Check if there's a recent reset request (prevent spam)
            $recentReset = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->where('created_at', '>', now()->subMinutes(2))
                ->first();

            if ($recentReset) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please wait at least 2 minutes before requesting another reset email.',
                ], 429);
            }

            // Generate new reset token
            $token = Str::random(64);

            // Store/update token in database
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email' => $request->email,
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Send email with reset link
            Mail::to($request->email)->send(new ResetPasswordMail($token, $user));

            return response()->json([
                'status' => true,
                'message' => 'Password reset link has been resent to your email.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to resend password reset link.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function me()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token is invalid or expired. Please login again.',
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => 'User authenticated successfully.',
                'data' => [
                    'user' => $user,
                ]
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['status' => false, 'message' => 'Token has expired.', 'error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid.', 'error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is missing.', 'error' => 'Token absent'], 401);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to authenticate user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function profile()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found. Please login again.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Profile retrieved successfully.',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function users()
    {
        try {
            $users = User::orderByRaw("role = 'admin' DESC")->get();

            return response()->json([
                'status' => true,
                'message' => 'All users retrieved successfully.',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function redirectToGoogle()
    {
        try {
            session()->flush();

            return Socialite::driver('google')
                ->with(['prompt' => 'select_account'])
                ->redirect();
        } catch (\Exception $e) {
            \Log::error('Google redirect error: ' . $e->getMessage());

            $frontendUrl = env('FRONTEND_URL', 'https://pure-flave-nature.vercel.app');
            return redirect()->away($frontendUrl . '/login?error=redirect_failed');
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->email) {
                \Log::error('Google user data incomplete', ['user' => $googleUser]);

                $frontendUrl = env('FRONTEND_URL', 'https://pure-flave-nature.vercel.app');
                return redirect()->away($frontendUrl . '/login?error=incomplete_user_data');
            }

            // (Continue your logic here...)
        } catch (\Exception $e) {
            \Log::error('Google callback error: ' . $e->getMessage());

            $frontendUrl = env('FRONTEND_URL', 'https://pure-flave-nature.vercel.app');
            return redirect()->away($frontendUrl . '/login?error=callback_failed');
        }
    }
}
