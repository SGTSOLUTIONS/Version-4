<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\CommonHelper;

class ProfileController extends Controller
{
    /**
     * Show Profile Page
     */
    public function profileShow()
    {
        $user = auth()->user();
        // return response()->json($user);
        return view('auth.profile', compact('user'));
    }

    /**
     * Get Auth User Profile (AJAX)
     */
    public function showProfile()
    {
        return response()->json([
            'status' => true,
            'data' => auth()->user()
        ]);
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'profile'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        // Password Update
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Profile Image Update
        if ($request->hasFile('profile')) {

            // Old image delete
            if (
                $user->profile &&
                !str_contains($user->profile, 'ui-avatars.com')
            ) {
                CommonHelper::deleteProfileImage($user->profile);
            }

            $image = $request->file('profile');
            $path = 'user/profile';

            $profileImagePath = CommonHelper::uploadProfileImage($image, $path);

            $user->profile = $profileImagePath;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }
}
