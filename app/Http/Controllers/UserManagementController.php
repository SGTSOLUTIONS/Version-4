<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Models\Corporation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function index()
    {
        $corporations = Corporation::with(['zones.wards'])->get();
        return view('main.admin.user', compact('corporations'));
    }

    public function list(Request $request)
    {
        $query = User::with(['corporation', 'zone', 'ward']);

        // If user is team leader, only show their team members
        if (auth()->user()->isTeamLeader()) {
            $query->where(function($q) {
                $q->where('team_leader_id', auth()->id())
                  ->orWhere('id', auth()->id());
            });
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                case 'suspended':
                    $query->where('is_active', false);
                    break;
            }
        }

        if ($request->filled('corporation_id')) {
            $query->where('corporation_id', $request->corporation_id);
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(12);

        return response()->json([
            'status' => true,
            'data'   => $users,
        ]);
    }

    public function show($id)
    {
        $user = User::with(['corporation', 'zone', 'ward', 'surveyors'])->findOrFail($id);

        // Check permission for team leaders
        if (auth()->user()->isTeamLeader()) {
            if ($user->team_leader_id !== auth()->id() && $user->id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return response()->json([
            'status' => true,
            'data'   => $user,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|max:20',
            'password'       => 'required|min:6',
            'role'           => 'required|string|in:admin,commissioner,dc,ac,aro,bc,teamleader,surveyor',
            'corporation_id' => 'nullable|exists:corporations,id',
            'zone_id'        => 'nullable|exists:zones,id',
            'ward_id'        => 'nullable|exists:wards,id',
            'profile'        => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'is_active'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Validation: Only one team leader per ward
        if ($request->role === 'teamleader') {
            $existingLeader = User::where('role', 'teamleader')
                ->where('corporation_id', $request->corporation_id)
                ->where('zone_id', $request->zone_id)
                ->where('ward_id', $request->ward_id)
                ->first();

            if ($existingLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'A team leader already exists for this ward'
                ], 422);
            }
        }

        $profileImagePath = null;

        if ($request->hasFile('profile')) {
            $profileImagePath = CommonHelper::uploadProfileImage(
                $request->file('profile'),
                'user/profile'
            );
        } else {
            $profileImagePath = 'https://ui-avatars.com/api/?name=' .
                urlencode($request->name) .
                '&background=1679AB&color=fff';
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'role'           => $request->role,
            'corporation_id' => $request->corporation_id,
            'zone_id'        => $request->zone_id,
            'ward_id'        => $request->ward_id,
            'profile'        => $profileImagePath,
            'is_active'      => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data'    => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $id,
            'phone'          => 'required|string|max:20',
            'password'       => 'nullable|min:6',
            'role'           => 'required|string|in:admin,commissioner,dc,ac,aro,bc,teamleader,surveyor',
            'corporation_id' => 'nullable|exists:corporations,id',
            'zone_id'        => 'nullable|exists:zones,id',
            'ward_id'        => 'nullable|exists:wards,id',
            'profile'        => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'is_active'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Validation: Only one team leader per ward
        if ($request->role === 'teamleader') {
            $existingLeader = User::where('role', 'teamleader')
                ->where('corporation_id', $request->corporation_id)
                ->where('zone_id', $request->zone_id)
                ->where('ward_id', $request->ward_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'A team leader already exists for this ward'
                ], 422);
            }
        }

        // If changing from teamleader to something else, unassign all surveyors
        if ($user->isTeamLeader() && $request->role !== 'teamleader') {
            User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
        }

        $profileImagePath = $user->profile;

        if ($request->hasFile('profile')) {
            if ($user->profile && !str_starts_with($user->profile, 'http')) {
                CommonHelper::deleteProfileImage($user->profile);
            }
            $profileImagePath = CommonHelper::uploadProfileImage(
                $request->file('profile'),
                'user/profile'
            );
        }

        $updateData = [
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'role'           => $request->role,
            'corporation_id' => $request->corporation_id,
            'zone_id'        => $request->zone_id,
            'ward_id'        => $request->ward_id,
            'profile'        => $profileImagePath,
            'is_active'      => $request->is_active,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data'    => $user->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Don't allow deleting yourself
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 422);
            }

            // If user is a team leader, unassign all their surveyors
            if ($user->isTeamLeader()) {
                User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
            }

            // If user is a surveyor, remove from team
            if ($user->isSurveyor() && $user->team_leader_id) {
                $user->team_leader_id = null;
                $user->save();
            }

            $user->delete();

            return response()->json([
                'status'  => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
