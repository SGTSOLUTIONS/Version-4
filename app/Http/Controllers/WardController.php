<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Models\Ward;
use App\Models\Zone;
use App\Models\Corporation;
use App\Services\WardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WardController extends Controller

{
    /**
     * Display a listing of the resource.
     */
    protected $wardService;

    public function __construct(
        WardService $wardService
    ) {
        $this->wardService = $wardService;
    }
    public function index()
    {
        $corporations = Corporation::where('status', 'active')->orderBy('name')->get();
        $zones = Zone::with('corporation')->where('status', 'active')->orderBy('zone_name')->get();

        return view('main.admin.ward', compact('corporations', 'zones'));
    }

    /**
     * Get list of wards for AJAX datatable.
     */
    public function list(Request $request)
    {
        $query = Ward::with(['zone.corporation', 'zone']);

        if ($request->filled('ward_no')) {
            $query->where('ward_no', 'like', '%' . $request->ward_no . '%');
        }

        if ($request->filled('zone')) {
            $query->where('zone', 'like', '%' . $request->zone . '%')
                ->orWhereHas('zone', function ($q) use ($request) {
                    $q->where('zone_name', 'like', '%' . $request->zone . '%');
                });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('corp_id')) {
            $query->whereHas('zone', function ($q) use ($request) {
                $q->where('corp_id', $request->corp_id);
            });
        }

        $wards = $query->latest()->paginate(12);

        return response()->json([
            'status' => true,
            'data' => $wards
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'corp_id' => 'required|exists:corporations,id',
            'zone_id' => 'required|exists:zones,id',
            'ward_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wards', 'ward_no')->whereNull('deleted_at'),
            ],
            'drone_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'extent_left' => 'nullable|numeric',
            'extent_right' => 'nullable|numeric',
            'extent_top' => 'nullable|numeric',
            'extent_bottom' => 'nullable|numeric',
            'boundary_file' => 'nullable|file|mimes:json,geojson|max:5120',
            'zone' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'contact_person' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'polygon_file' => 'nullable|file',
            'road_file' => 'nullable|file',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Verify zone belongs to selected corporation
            $zone = Zone::where('id', $request->zone_id)
                ->where('corp_id', $request->corp_id)
                ->first();

            if (!$zone) {
                throw new \Exception('Selected zone does not belong to the chosen corporation');
            }

            // Handle drone image upload using CommonHelper (same as corporation)
            $droneImagePath = null;
            if ($request->hasFile('drone_image')) {
                $droneImagePath = CommonHelper::uploadProfileImage(
                    $request->file('drone_image'),
                    'wards/drone-images'
                );
            }

            // Handle GeoJSON boundary file upload
            $boundary = null;
            if ($request->hasFile('boundary_file')) {
                $geojsonData = json_decode(
                    file_get_contents($request->file('boundary_file')->getRealPath()),
                    true
                );

                if (
                    isset($geojsonData['features'][0]['geometry']) &&
                    isset($geojsonData['features'][0]['geometry']['coordinates'])
                ) {
                    $boundary = json_encode([
                        'coordinates' => $geojsonData['features'][0]['geometry']['coordinates']
                    ]);
                } else {
                    throw new \Exception('Invalid GeoJSON format.');
                }
            }

            // Create ward
            $ward = Ward::create([
                'zone_id' => $request->zone_id,
                'ward_no' => $request->ward_no,
                'drone_image' => $droneImagePath,
                'extent_left' => $request->extent_left,
                'extent_right' => $request->extent_right,
                'extent_top' => $request->extent_top,
                'extent_bottom' => $request->extent_bottom,
                'boundary' => $boundary,
                'zone' => $request->zone,
                'status' => $request->status,
                'contact_person' => $request->contact_person,
                'designation' => $request->designation,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
            ]);

            $createTable = $this->wardService->createWardTables($ward->id);
            if ($createTable) {
                $polygonTable     = $createTable['polygon'];
                $pointTable       = $createTable['point'];
                $polygonDataTable = $createTable['polygon_data'];
                $pointDataTable   = $createTable['point_data'];
                if ($request->hasFile('polygon_file')) {
                    $result = $this->wardService->createPolygonUpdate(
                        $polygonTable,
                        $pointTable,
                        $request->file('polygon_file')
                    );
                }
            }
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ward created successfully',
                'data' => $ward
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Ward $ward)
    {
        $ward->load(['zone.corporation', 'zone']);

        return response()->json([
            'status' => true,
            'data' => $ward
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ward $ward)
    {
        $validator = Validator::make($request->all(), [
            'corp_id' => 'required|exists:corporations,id',
            'zone_id' => 'required|exists:zones,id',
            'ward_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wards', 'ward_no')
                    ->ignore($ward->id)
                    ->whereNull('deleted_at'),
            ],
            'drone_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'extent_left' => 'nullable|numeric',
            'extent_right' => 'nullable|numeric',
            'extent_top' => 'nullable|numeric',
            'extent_bottom' => 'nullable|numeric',
            'boundary_file' => 'nullable|file|mimes:json,geojson|max:5120',
            'zone' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'contact_person' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Verify zone belongs to selected corporation
            $zone = Zone::where('id', $request->zone_id)
                ->where('corp_id', $request->corp_id)
                ->first();

            if (!$zone) {
                throw new \Exception('Selected zone does not belong to the chosen corporation');
            }

            // Handle drone image upload using CommonHelper (same as corporation)
            if ($request->hasFile('drone_image')) {
                // Delete old image if exists and not a default avatar
                if ($ward->drone_image && !str_starts_with($ward->drone_image, 'http')) {
                    Storage::disk('public')->delete($ward->drone_image);
                }

                $ward->drone_image = CommonHelper::uploadProfileImage(
                    $request->file('drone_image'),
                    'wards/drone-images'
                );
            }

            // Handle GeoJSON boundary file upload
            if ($request->hasFile('boundary_file')) {
                $geojsonData = json_decode(
                    file_get_contents($request->file('boundary_file')->getRealPath()),
                    true
                );

                if (
                    isset($geojsonData['features'][0]['geometry']) &&
                    isset($geojsonData['features'][0]['geometry']['coordinates'])
                ) {
                    $ward->boundary = json_encode([
                        'coordinates' => $geojsonData['features'][0]['geometry']['coordinates']
                    ]);
                } else {
                    throw new \Exception('Invalid GeoJSON format.');
                }
            }

            // Update ward details
            $ward->zone_id = $request->zone_id;
            $ward->ward_no = $request->ward_no;
            $ward->extent_left = $request->extent_left;
            $ward->extent_right = $request->extent_right;
            $ward->extent_top = $request->extent_top;
            $ward->extent_bottom = $request->extent_bottom;
            $ward->zone = $request->zone;
            $ward->status = $request->status;
            $ward->contact_person = $request->contact_person;
            $ward->designation = $request->designation;
            $ward->phone = $request->phone;
            $ward->email = $request->email;
            $ward->address = $request->address;

            $ward->save();
            $createTable = $this->wardService->createWardTables($ward->id);
            if ($createTable) {
                $polygonTable     = $createTable['polygon'];
                $pointTable       = $createTable['point'];
                $polygonDataTable = $createTable['polygon_data'];
                $pointDataTable   = $createTable['point_data'];
                if ($request->hasFile('polygon_file')) {
                    $result = $this->wardService->createPolygonUpdate(
                        $polygonTable,
                        $pointTable,
                        $request->file('polygon_file')
                    );
                }
            }
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ward updated successfully',
                'data' => $ward
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ward $ward)
    {
        DB::beginTransaction();

        try {
            // Delete drone image if exists and not a default avatar
            if ($ward->drone_image && !str_starts_with($ward->drone_image, 'http')) {
                Storage::disk('public')->delete($ward->drone_image);
            }

            // Drop ward-specific tables
            $this->wardService->dropWardTables($ward->id);

            $ward->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ward deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wards by zone for dropdown.
     */
    public function getWardsByZone($zoneId)
    {
        try {
            $wards = Ward::where('zone_id', $zoneId)
                ->where('status', 'active')
                ->orderBy('ward_no')
                ->get(['id', 'ward_no']);

            return response()->json([
                'status' => true,
                'data' => $wards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch wards'
            ], 500);
        }
    }

    /**
     * Update ward status (activate/deactivate).
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ward = Ward::findOrFail($id);
            $ward->status = $request->status;
            $ward->save();

            return response()->json([
                'status' => true,
                'message' => 'Ward status updated successfully',
                'data' => $ward
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
