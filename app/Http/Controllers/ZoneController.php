<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $corporations = Corporation::all();

        // If commissioner, only show their corporation
        if ($user->role == 'commissioner') {
            $corporations = Corporation::where('id', $user->corporation_id)->get();
        }

        return view('main.admin.zone', compact('corporations'));
    }

    /**
     * Get list of zones with filtering and pagination.
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        $query = Zone::with('corporation');

        if ($request->filled('zone_name')) {
            $query->where('zone_name', 'like', '%' . $request->zone_name . '%');
        }

        // Commissioner can only view their corporation's zones
        if ($user->role == 'commissioner') {
            $query->where('corp_id', $user->corporation_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Allow corp_id filter only for non-commissioners
        if ($user->role != 'commissioner' && $request->filled('corp_id')) {
            $query->where('corp_id', $request->corp_id);
        }

        $zones = $query->latest()->paginate(12);

        return response()->json([
            'status' => true,
            'data'   => $zones,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'corp_id' => 'required|exists:corporations,id',
            'zone_name' => 'required|string|max:255',
            'zone_code' => 'required|string|max:50|unique:zones,zone_code',
            'total_wards' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:10',
            'status' => 'required|in:active,inactive',
        ]);

        // Commissioner can only create zones for their corporation
        if ($user->role == 'commissioner') {
            if ($validated['corp_id'] != $user->corporation_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only create zones for your corporation'
                ], 403);
            }
        }

        $zone = Zone::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Zone created successfully',
            'data' => $zone->load('corporation')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Zone $zone)
    {
        $user = Auth::user();

        // Check if commissioner can view this zone
        if ($user->role == 'commissioner' && $zone->corp_id != $user->corporation_id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to view this zone'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $zone->load('corporation')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Zone $zone)
    {
        $user = Auth::user();

        // Check if commissioner can update this zone
        if ($user->role == 'commissioner' && $zone->corp_id != $user->corporation_id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to update this zone'
            ], 403);
        }

        $validated = $request->validate([
            'corp_id' => 'required|exists:corporations,id',
            'zone_name' => 'required|string|max:255',
            'zone_code' => 'required|string|max:50|unique:zones,zone_code,' . $zone->id,
            'total_wards' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:10',
            'status' => 'required|in:active,inactive',
        ]);

        // Commissioner can only update zones for their corporation
        if ($user->role == 'commissioner') {
            if ($validated['corp_id'] != $user->corporation_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only update zones for your corporation'
                ], 403);
            }
        }

        $zone->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Zone updated successfully',
            'data' => $zone->load('corporation')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Zone $zone)
    {
        $user = Auth::user();

        // Check if commissioner can delete this zone
        if ($user->role == 'commissioner' && $zone->corp_id != $user->corporation_id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to delete this zone'
            ], 403);
        }

        $zone->delete();

        return response()->json([
            'status' => true,
            'message' => 'Zone deleted successfully'
        ]);
    }

    /**
     * Get zones by corporation for dropdown.
     */
    public function getZonesByCorporation(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'corp_id' => 'required|exists:corporations,id'
        ]);

        $query = Zone::where('corp_id', $request->corp_id)
            ->where('status', 'active')
            ->orderBy('zone_name');

        // If commissioner, only show their zones
        if ($user->role == 'commissioner') {
            $query->where('corp_id', $user->corporation_id);
        }

        $zones = $query->get(['id', 'zone_name', 'zone_code']);

        return response()->json([
            'status' => true,
            'data' => $zones
        ]);
    }
}
