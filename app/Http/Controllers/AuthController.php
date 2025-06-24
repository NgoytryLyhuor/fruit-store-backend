<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

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
                    'success' => false,
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
                'success' => true,
                'message' => 'Registration successful. Welcome!',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Login failed. Please check your input.',
                    'errors' => $validator->errors(),
                ], 200);
            }

            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login failed. Email or password is incorrect.',
                    'error' => 'Invalid credentials',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful. Welcome back!',
                'data' => [
                    'user' => auth()->user(),
                    'token' => $token,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'success' => true,
                'message' => 'Logout successful. See you later!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Update failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->filled('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect.',
                    ], 422);
                }

                $user->password = Hash::make($request->password);
            }

            $user->fill($request->only([
                'first_name', 'last_name', 'email', 'street', 'city', 'postal_code'
            ]));

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Update successful.',
                'data' => [
                    'user' => $user->fresh(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed due to server error.',
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
                    'success' => false,
                    'message' => 'Token is invalid or expired. Please login again.',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'User authenticated successfully.',
                'data' => [
                    'user' => $user,
                ]
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['success' => false, 'message' => 'Token has expired.', 'error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['success' => false, 'message' => 'Token is invalid.', 'error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Token is missing.', 'error' => 'Token absent'], 401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to authenticate user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function profile()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully.',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'success' => true,
                'message' => 'All users retrieved successfully.',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect()->away($frontendUrl . '/login?error=redirect_failed');
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->email) {
                \Log::error('Google user data incomplete', ['user' => $googleUser]);

                $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
                return redirect()->away($frontendUrl . '/login?error=incomplete_user_data');
            }

            // (Continue your logic here...)
        } catch (\Exception $e) {
            \Log::error('Google callback error: ' . $e->getMessage());

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect()->away($frontendUrl . '/login?error=callback_failed');
        }
    }
}
