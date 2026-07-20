<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Helpers\CommonHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect($this->getDashboardRoute(Auth::user()->role));
        }

        return view('auth.login');
    }
    public function submitLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Attempt to login
        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember') ? true : false;

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            return response()->json([
                'status' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => $this->getDashboardRoute($user->role)
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid email or password. Please try again.'
        ], 401);
    }
    private function getDashboardRoute(string $role): string
    {
        return match ($role) {
            RoleEnum::ADMIN->value        => route('admin.dashboard'),
            RoleEnum::COMMISSIONER->value => route('commissioner.dashboard'),
            RoleEnum::DC->value           => route('dc.dashboard'),
            RoleEnum::ACR->value           => route('acr.dashboard'),
            RoleEnum::AC->value           => route('ac.dashboard'),
            RoleEnum::ARO->value          => route('aro.dashboard'),
            RoleEnum::BC->value           => route('bc.dashboard'),
            RoleEnum::TEAMLEADER->value   => route('teamleader.dashboard'),
            RoleEnum::SURVEYOR->value     => route('surveyor.dashboard'),
            default                       => route('dashboard'),
        };
    }

    public function showRegister()
    {
        return view('auth.register');
    }
    public function submitRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|min:6',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => "Validation error"
            ], 422);
        }

        try {
            $profileImagePath = null;

            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $path = "user/profile";

                // Make sure CommonHelper exists and has this method
                if (class_exists('App\Helpers\CommonHelper')) {
                    $profileImagePath = CommonHelper::uploadProfileImage($image, $path);
                }
            } else {
                $profileImagePath = 'https://ui-avatars.com/api/?name=' .
                    urlencode($request->name) .
                    '&background=1679AB&color=fff';
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'profile' => $profileImagePath, // Note: typo in schema, should be 'profile'
                'role' => RoleEnum::SURVEYOR->value, // Add default role
                'is_active' => false,
                'email_verified_at' => null,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please wait for admin approval.',
                'redirect' => route('login'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function shownForget()
    {
        return view('auth.forget');
    }
    public function submitForget(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation error'
            ], 422);
        }

        try {
            // Send password reset link
            $response = Password::sendResetLink(
                $request->only('email')
            );

            if ($response == Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => true,
                    'message' => 'We have emailed your password reset link!'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Unable to send reset link. Please try again.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
    public function showResetPassword(Request $request, $token)
    {
        // Get email from query string
        $email = $request->query('email');

        // Verify if token exists in database
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            abort(404, 'Invalid or expired reset link.');
        }

        // Check if token is expired (60 minutes)
        $tokenCreatedAt = Carbon::parse($resetRecord->created_at);
        if ($tokenCreatedAt->addMinutes(60)->isPast()) {
            abort(404, 'Reset link has expired. Please request a new one.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email
        ]);
    }

    public function submitResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation error'
            ], 422);
        }

        try {
            // Verify token exists and is valid
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'message' => 'Invalid reset token. Please request a new password reset.'
                ], 400);
            }

            // Check token validity
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'message' => 'Invalid or expired token.'
                ], 400);
            }

            // Check if token is expired (60 minutes)
            $tokenCreatedAt = Carbon::parse($resetRecord->created_at);
            if ($tokenCreatedAt->addMinutes(60)->isPast()) {
                return response()->json([
                    'message' => 'Reset link has expired. Please request a new one.'
                ], 400);
            }

            // Update user password
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete the reset token after successful reset
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Password has been reset successfully!',
                'redirect' => route('login')
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to reset password. Please try again.'
            ], 500);
        }
    }
}
