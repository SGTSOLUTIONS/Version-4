<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamManagementController extends Controller
{
    /**
     * Display a listing of all teams (team leaders with their surveyors)
     */
    public function index()
    {
        $corporations = \App\Models\Corporation::with(['zones.wards'])->get();
        return view('main.admin.team', compact('corporations'));
    }

    /**
     * Get list of teams with pagination and filters
     */
    public function list(Request $request)
    {
        $query = User::with(['surveyors', 'corporation', 'zone', 'ward'])
            ->where('role', 'teamleader');

        // If user is team leader, only show their own team
        if (auth()->user()->isTeamLeader()) {
            $query->where('id', auth()->id());
        }

        // Filters
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('corporation_id') && !empty($request->corporation_id)) {
            $query->where('corporation_id', $request->corporation_id);
        }

        if ($request->has('zone_id') && !empty($request->zone_id)) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->has('ward_id') && !empty($request->ward_id)) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->input('per_page', 12);
        $teams = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $teams
        ]);
    }

    /**
     * Get a specific team leader with their surveyors
     */
    public function show($id)
    {
        $teamLeader = User::with(['surveyors', 'corporation', 'zone', 'ward'])
            ->where('role', 'teamleader')
            ->findOrFail($id);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $teamLeader
        ]);
    }

    /**
     * Get available surveyors for a team leader
     * Surveyors who are not assigned to any team leader and belong to the same ward
     */
    public function getAvailableSurveyors($teamLeaderId)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($teamLeaderId);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $surveyors = User::where('role', 'surveyor')
            ->where('corporation_id', $teamLeader->corporation_id)
            ->where('zone_id', $teamLeader->zone_id)
            ->where('ward_id', $teamLeader->ward_id)
            ->where('is_active', true)
            ->whereNull('team_leader_id')
            ->select('id', 'name', 'email', 'phone', 'profile')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $surveyors
        ]);
    }

    /**
     * Get team leader's team members (surveyors)
     */
    public function getTeamMembers($teamLeaderId)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($teamLeaderId);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $surveyors = User::where('role', 'surveyor')
            ->where('team_leader_id', $teamLeaderId)
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'phone', 'profile')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $surveyors
        ]);
    }

    /**
     * Assign a surveyor to a team leader
     */
    public function assignSurveyor(Request $request, $teamLeaderId)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($teamLeaderId);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'surveyor_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if surveyor exists and is available
        $surveyor = User::where('role', 'surveyor')
            ->where('id', $request->surveyor_id)
            ->whereNull('team_leader_id')
            ->first();

        if (!$surveyor) {
            return response()->json([
                'success' => false,
                'message' => 'Surveyor is already assigned to a team or not found'
            ], 422);
        }

        // Check if surveyor belongs to the same ward as team leader
        if ($surveyor->corporation_id != $teamLeader->corporation_id ||
            $surveyor->zone_id != $teamLeader->zone_id ||
            $surveyor->ward_id != $teamLeader->ward_id) {
            return response()->json([
                'success' => false,
                'message' => 'Surveyor does not belong to the same ward as this team leader'
            ], 422);
        }

        // Assign surveyor to team leader
        $surveyor->team_leader_id = $teamLeader->id;
        $surveyor->save();

        return response()->json([
            'success' => true,
            'message' => 'Surveyor assigned to team successfully',
            'data' => $surveyor
        ]);
    }

    /**
     * Remove a surveyor from a team leader
     */
    public function removeSurveyor(Request $request, $teamLeaderId)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($teamLeaderId);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'surveyor_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if surveyor belongs to this team
        $surveyor = User::where('role', 'surveyor')
            ->where('id', $request->surveyor_id)
            ->where('team_leader_id', $teamLeader->id)
            ->first();

        if (!$surveyor) {
            return response()->json([
                'success' => false,
                'message' => 'Surveyor not found in this team'
            ], 422);
        }

        // Remove surveyor from team
        $surveyor->team_leader_id = null;
        $surveyor->save();

        return response()->json([
            'success' => true,
            'message' => 'Surveyor removed from team successfully'
        ]);
    }

    /**
     * Remove multiple surveyors from a team at once
     */
    public function removeMultipleSurveyors(Request $request, $teamLeaderId)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($teamLeaderId);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'surveyor_ids' => 'required|array',
            'surveyor_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Remove all specified surveyors from team
        $count = User::where('role', 'surveyor')
            ->where('team_leader_id', $teamLeader->id)
            ->whereIn('id', $request->surveyor_ids)
            ->update(['team_leader_id' => null]);

        return response()->json([
            'success' => true,
            'message' => $count . ' surveyors removed from team successfully'
        ]);
    }

    /**
     * Delete a team (unassign all surveyors and delete the team leader)
     */
    public function destroy($id)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($id);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Unassign all surveyors from this team leader
        User::where('team_leader_id', $teamLeader->id)->update(['team_leader_id' => null]);

        // Delete the team leader
        $teamLeader->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully'
        ]);
    }

    /**
     * Get team statistics for dashboard
     */
    public function getTeamStats()
    {
        $stats = [
            'total_teams' => User::where('role', 'teamleader')->where('is_active', true)->count(),
            'total_surveyors' => User::where('role', 'surveyor')->where('is_active', true)->count(),
            'assigned_surveyors' => User::where('role', 'surveyor')
                ->whereNotNull('team_leader_id')
                ->where('is_active', true)
                ->count(),
            'unassigned_surveyors' => User::where('role', 'surveyor')
                ->whereNull('team_leader_id')
                ->where('is_active', true)
                ->count(),
            'inactive_teams' => User::where('role', 'teamleader')
                ->where('is_active', false)
                ->count(),
        ];

        return response()->json([
            'status' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all surveyors grouped by team
     */
    public function getAllTeamsWithSurveyors()
    {
        $teams = User::where('role', 'teamleader')
            ->with(['surveyors' => function($query) {
                $query->where('is_active', true);
            }, 'corporation', 'zone', 'ward'])
            ->where('is_active', true)
            ->get();

        $data = $teams->map(function($team) {
            return [
                'id' => $team->id,
                'team_leader_name' => $team->name,
                'team_leader_email' => $team->email,
                'corporation' => $team->corporation ? $team->corporation->name : null,
                'zone' => $team->zone ? $team->zone->zone_name : null,
                'ward' => $team->ward ? 'Ward ' . $team->ward->ward_no : null,
                'surveyors_count' => $team->surveyors->count(),
                'surveyors' => $team->surveyors->map(function($surveyor) {
                    return [
                        'id' => $surveyor->id,
                        'name' => $surveyor->name,
                        'email' => $surveyor->email,
                        'phone' => $surveyor->phone,
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Export team data to CSV
     */
    public function exportTeams()
    {
        $teams = User::where('role', 'teamleader')
            ->with(['surveyors', 'corporation', 'zone', 'ward'])
            ->get();

        $filename = 'teams_export_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');

        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Write headers
        fputcsv($handle, [
            'Team Leader Name',
            'Email',
            'Phone',
            'Corporation',
            'Zone',
            'Ward',
            'Number of Surveyors',
            'Surveyors'
        ]);

        // Write data
        foreach ($teams as $team) {
            fputcsv($handle, [
                $team->name,
                $team->email,
                $team->phone ?? '',
                $team->corporation ? $team->corporation->name : '',
                $team->zone ? $team->zone->zone_name : '',
                $team->ward ? 'Ward ' . $team->ward->ward_no : '',
                $team->surveyors->count(),
                $team->surveyors->pluck('name')->implode(', ')
            ]);
        }

        fclose($handle);
        exit;
    }

    /**
     * Get wards that have team leaders
     */
    public function getWardsWithTeams()
    {
        $wards = User::where('role', 'teamleader')
            ->where('is_active', true)
            ->with(['corporation', 'zone', 'ward'])
            ->get()
            ->groupBy(function($item) {
                return $item->corporation_id . '-' . $item->zone_id . '-' . $item->ward_id;
            })
            ->map(function($group) {
                $first = $group->first();
                return [
                    'corporation_id' => $first->corporation_id,
                    'corporation_name' => $first->corporation ? $first->corporation->name : null,
                    'zone_id' => $first->zone_id,
                    'zone_name' => $first->zone ? $first->zone->zone_name : null,
                    'ward_id' => $first->ward_id,
                    'ward_name' => $first->ward ? 'Ward ' . $first->ward->ward_no : null,
                    'team_leader_id' => $first->id,
                    'team_leader_name' => $first->name,
                    'surveyor_count' => User::where('team_leader_id', $first->id)->count()
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => $wards
        ]);
    }

    /**
     * Bulk assign surveyors to a team
     */
    public function bulkAssignSurveyors(Request $request, $teamLeaderId)
    {
        $teamLeader = User::where('role', 'teamleader')->findOrFail($teamLeaderId);

        // Check permission
        if (auth()->user()->isTeamLeader() && $teamLeader->id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'surveyor_ids' => 'required|array',
            'surveyor_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $assignedCount = 0;
        $errors = [];

        foreach ($request->surveyor_ids as $surveyorId) {
            $surveyor = User::where('role', 'surveyor')
                ->where('id', $surveyorId)
                ->whereNull('team_leader_id')
                ->first();

            if ($surveyor) {
                // Check if surveyor belongs to the same ward
                if ($surveyor->corporation_id == $teamLeader->corporation_id &&
                    $surveyor->zone_id == $teamLeader->zone_id &&
                    $surveyor->ward_id == $teamLeader->ward_id) {

                    $surveyor->team_leader_id = $teamLeader->id;
                    $surveyor->save();
                    $assignedCount++;
                } else {
                    $errors[] = "Surveyor {$surveyor->name} does not belong to the same ward";
                }
            } else {
                $errors[] = "Surveyor ID {$surveyorId} is already assigned or not found";
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$assignedCount} surveyors assigned successfully" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
            'assigned_count' => $assignedCount,
            'errors' => $errors
        ]);
    }
}
