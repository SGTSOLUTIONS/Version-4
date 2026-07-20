<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Models\Ward;
use App\Models\Zone;
use App\Models\Corporation;
use App\Services\WardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WardController extends Controller
{
    protected $wardService;

    public function __construct(WardService $wardService)
    {
        $this->wardService = $wardService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->role == 'commissioner') {
                $corporations = Corporation::where('id', $user->corporation_id)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get();

                $zones = Zone::where('corp_id', $user->corporation_id)
                    ->where('status', 'active')
                    ->with('corporation')
                    ->orderBy('zone_name')
                    ->get();
            } else {
                $corporations = Corporation::where('status', 'active')
                    ->orderBy('name')
                    ->get();

                $zones = Zone::with('corporation')
                    ->where('status', 'active')
                    ->orderBy('zone_name')
                    ->get();
            }

            return view('main.admin.ward', compact('corporations', 'zones'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page: ' . $e->getMessage());
        }
    }

    /**
     * Get list of wards for AJAX datatable.
     */
    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Ward::with(['zone.corporation', 'zone']);

            if ($user->role == 'commissioner') {
                $query->whereHas('zone', function ($q) use ($user) {
                    $q->where('corp_id', $user->corporation_id);
                });
            }

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

            if ($user->role != 'commissioner' && $request->filled('corp_id')) {
                $query->whereHas('zone', function ($q) use ($request) {
                    $q->where('corp_id', $request->corp_id);
                });
            }

            $wards = $query->latest()->paginate(12);

            return response()->json([
                'status' => true,
                'data' => $wards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load wards: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Commissioner permission check
            if ($user->role == 'commissioner') {
                $zone = Zone::find($request->zone_id);
                if (!$zone || $zone->corp_id != $user->corporation_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You can only create wards in your corporation\'s zones'
                    ], 403);
                }
            }

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
                'extent_left' => 'nullable',
                'extent_right' => 'nullable',
                'extent_top' => 'nullable',
                'extent_bottom' => 'nullable',
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

            // Start transaction
            DB::beginTransaction();

            try {
                // Verify zone belongs to selected corporation
                $zone = Zone::where('id', $request->zone_id)
                    ->where('corp_id', $request->corp_id)
                    ->first();

                if (!$zone) {
                    throw new \Exception('Selected zone does not belong to the chosen corporation');
                }

                // Handle drone image upload
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

                    if (isset($geojsonData['features'][0]['geometry']['coordinates'])) {
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

                // Create ward tables
                $createTable = $this->wardService->createWardTables($ward->id);
                if ($createTable) {
                    $polygonTable = $createTable['polygon'];
                    $pointTable = $createTable['point'];
                    if ($request->hasFile('polygon_file')) {
                        $result = $this->wardService->createPolygonUpdate(
                            $polygonTable,
                            $pointTable,
                            $request->file('polygon_file')
                        );
                    }
                }

                // Commit transaction
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Ward created successfully',
                    'data' => $ward->load(['zone.corporation', 'zone'])
                ], 201);
            } catch (\Throwable $e) {
                try {
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }
                } catch (\Throwable $rollbackError) {
                }

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
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
        try {
            $user = Auth::user();

            if ($user->role == 'commissioner') {
                $zone = Zone::find($ward->zone_id);
                if (!$zone || $zone->corp_id != $user->corporation_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized to view this ward'
                    ], 403);
                }
            }

            $ward->load(['zone.corporation', 'zone']);

            return response()->json([
                'status' => true,
                'data' => $ward
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load ward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ward $ward)
    {
        try {
            $user = Auth::user();

            if ($user->role == 'commissioner') {
                $zone = Zone::find($ward->zone_id);
                if (!$zone || $zone->corp_id != $user->corporation_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized to update this ward'
                    ], 403);
                }

                $newZone = Zone::find($request->zone_id);
                if (!$newZone || $newZone->corp_id != $user->corporation_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You can only update wards in your corporation\'s zones'
                    ], 403);
                }
            }

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
                'extent_left' => 'nullable',
                'extent_right' => 'nullable',
                'extent_top' => 'nullable',
                'extent_bottom' => 'nullable',
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

            // Start transaction
            DB::beginTransaction();

            try {
                // Verify zone belongs to selected corporation
                $zone = Zone::where('id', $request->zone_id)
                    ->where('corp_id', $request->corp_id)
                    ->first();

                if (!$zone) {
                    throw new \Exception('Selected zone does not belong to the chosen corporation');
                }

                // Handle drone image upload
                if ($request->hasFile('drone_image')) {
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

                    if (isset($geojsonData['features'][0]['geometry']['coordinates'])) {
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

                // Create/update ward tables
                $createTable = $this->wardService->createWardTables($ward->id);
                if ($createTable) {
                    $polygonTable = $createTable['polygon'];
                    $pointTable = $createTable['point'];
                    if ($request->hasFile('polygon_file')) {
                        $result = $this->wardService->createPolygonUpdate(
                            $polygonTable,
                            $pointTable,
                            $request->file('polygon_file')
                        );
                    }
                }

                // Commit transaction
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Ward updated successfully',
                    'data' => $ward->load(['zone.corporation', 'zone'])
                ]);
            } catch (\Throwable $e) {
                try {
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }
                } catch (\Throwable $rollbackError) {
                }

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
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
        try {
            $user = Auth::user();

            if ($user->role == 'commissioner') {
                return response()->json([
                    'status' => false,
                    'message' => 'Commissioners cannot delete wards'
                ], 403);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                if ($ward->drone_image && !str_starts_with($ward->drone_image, 'http')) {
                    Storage::disk('public')->delete($ward->drone_image);
                }

                $this->wardService->dropWardTables($ward->id);
                $ward->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Ward deleted successfully'
                ]);
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
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
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

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

   public function missingBuiilding($ward_id)
{
    try {

        $polygonDataTable = "polygon_data_" . $ward_id;
        $polygonTable     = "polygons_" . $ward_id;

        $missingBuildings = DB::table($polygonTable)
            ->whereNotIn('gisid', function ($query) use ($polygonDataTable) {
                $query->select('gisid')
                      ->from($polygonDataTable);
            })
            ->get();

        $features = [];

        foreach ($missingBuildings as $building) {

            $geometry = json_decode($building->feature, true);

            $features[] = [
                "type" => "Feature",
                "geometry" => $geometry,
                "properties" => [
                    "gisid" => $building->gisid,
                ]
            ];
        }

        $geojson = [
            "type" => "FeatureCollection",
            "features" => $features
        ];

        $filename = "missing_buildings_{$ward_id}.geojson";

        return response()->streamDownload(function () use ($geojson) {
            echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/geo+json',
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            "success" => false,
            "message" => $e->getMessage(),
            "line"    => $e->getLine(),
            "file"    => $e->getFile(),
        ], 500);
    }
}
}
