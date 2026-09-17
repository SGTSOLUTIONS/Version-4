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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MissingBillExport;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;

class WardController extends Controller
{
    protected $wardService;

    public function __construct(WardService $wardService)
    {
        $this->wardService = $wardService;
    }

    /* =========================================================
     *  CRUD
     * ========================================================= */

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
                $query->where(function ($q) use ($request) {
                    $q->where('zone', 'like', '%' . $request->zone . '%')
                        ->orWhereHas('zone', function ($sub) use ($request) {
                            $sub->where('zone_name', 'like', '%' . $request->zone . '%');
                        });
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

            $wards->getCollection()->transform(function ($ward) {
                $ward->road_names = [];

                $zoneRelation = $ward->getRelation('zone');

                if (!$zoneRelation) {
                    $ward->table_error = 'Zone not found';
                    $ward->zone_id_debug = $ward->zone_id;
                    $ward->zone_name_debug = $ward->zone;
                    return $ward;
                }

                $misTableName = 'mis_' . $zoneRelation->corp_id;
                $ward->mis_table = $misTableName;

                if (Schema::hasTable($misTableName)) {
                    $ward->road_names = DB::table($misTableName)
                        ->where('ward_no', $ward->ward_no)
                        ->whereNotNull('road_name')
                        ->where('road_name', '!=', '')
                        ->distinct()
                        ->orderBy('road_name')
                        ->pluck('road_name')
                        ->toArray();
                } else {
                    $ward->table_error = 'Table not found';
                }

                return $ward;
            });

            return response()->json([
                'status' => true,
                'data' => $wards,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load wards: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

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

            DB::beginTransaction();

            try {
                $zone = Zone::where('id', $request->zone_id)
                    ->where('corp_id', $request->corp_id)
                    ->first();

                if (!$zone) {
                    throw new \Exception('Selected zone does not belong to the chosen corporation');
                }

                $droneImagePath = null;
                if ($request->hasFile('drone_image')) {
                    $droneImagePath = CommonHelper::uploadProfileImage(
                        $request->file('drone_image'),
                        'wards/drone-images'
                    );
                }

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

                $ward = Ward::create([
                    'zone_id' => $request->zone_id,
                    'ward_no' => $request->ward_no,
                    'drone_image' => $droneImagePath,
                    'extent_left' => $request->extent_left,
                    'extent_right' => $request->extent_right,
                    'extent_top' => $request->extent_top,
                    'extent_bottom' => $request->extent_bottom,
                    'boundary' => $boundary,
                    'zone_name' => $request->zone,
                    'status' => $request->status,
                    'contact_person' => $request->contact_person,
                    'designation' => $request->designation,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address,
                ]);

                $createTable = $this->wardService->createWardTables($ward->id);
                if ($createTable) {
                    $polygonTable = $createTable['polygon'];
                    $pointTable = $createTable['point'];
                    $lineTable = $createTable['line'];
                    if ($request->hasFile('polygon_file')) {
                        $this->wardService->createPolygonUpdate(
                            $polygonTable,
                            $pointTable,
                            $request->file('polygon_file')
                        );
                    }
                    if ($request->hasFile('road_file')) {
                        $this->wardService->storeSingleLine(
                            $lineTable,
                            $request->file('road_file')
                        );
                    }
                }

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

            DB::beginTransaction();

            try {
                $zone = Zone::where('id', $request->zone_id)
                    ->where('corp_id', $request->corp_id)
                    ->first();

                if (!$zone) {
                    throw new \Exception('Selected zone does not belong to the chosen corporation');
                }

                if ($request->hasFile('drone_image')) {
                    if ($ward->drone_image && !str_starts_with($ward->drone_image, 'http')) {
                        Storage::disk('public')->delete($ward->drone_image);
                    }

                    $ward->drone_image = CommonHelper::uploadProfileImage(
                        $request->file('drone_image'),
                        'wards/drone-images'
                    );
                }

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

                $ward->zone_id = $request->zone_id;
                $ward->ward_no = $request->ward_no;
                $ward->extent_left = $request->extent_left;
                $ward->extent_right = $request->extent_right;
                $ward->extent_top = $request->extent_top;
                $ward->extent_bottom = $request->extent_bottom;
                $ward->zone_name = $request->zone;
                $ward->status = $request->status;
                $ward->contact_person = $request->contact_person;
                $ward->designation = $request->designation;
                $ward->phone = $request->phone;
                $ward->email = $request->email;
                $ward->address = $request->address;

                $ward->save();

                $createTable = $this->wardService->createWardTables($ward->id);
                if ($createTable) {
                    $polygonTable = $createTable['polygon'];
                    $pointTable = $createTable['point'];
                    $lineTable = $createTable['line'];
                    if ($request->hasFile('polygon_file')) {
                        $this->wardService->createPolygonUpdate(
                            $polygonTable,
                            $pointTable,
                            $request->file('polygon_file')
                        );
                    }
                    if ($request->hasFile('road_file')) {
                        $this->wardService->storeSingleLine(
                            $lineTable,
                            $request->file('road_file')
                        );
                    }
                }

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

            DB::beginTransaction();

            try {
                if ($ward->drone_image && !str_starts_with($ward->drone_image, 'http')) {
                    Storage::disk('public')->delete($ward->drone_image);
                }

                $this->wardService->dropWardTables($ward->id);
                $ward->delete();

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Ward deleted successfully'
                ]);
            } catch (\Exception $e) {
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

    /* =========================================================
     *  GEOJSON EXPORTS
     * ========================================================= */

    public function missingBuiilding($ward_id)
    {
        try {
            $polygonDataTable = "polygon_data_" . $ward_id;
            $polygonTable     = "polygons_" . $ward_id;

            $missingBuildings = DB::table($polygonTable)
                ->whereNotIn('gisid', function ($query) use ($polygonDataTable) {
                    $query->select('gisid')->from($polygonDataTable);
                })
                ->get();

            $features = [];

            foreach ($missingBuildings as $building) {
                $coordinates = json_decode($building->coordinates, true);
                if (!$coordinates) continue;

                if ($building->type == 'Polygon') {
                    if (isset($coordinates[0][0]) && is_numeric($coordinates[0][0])) {
                        $coordinates = [$coordinates];
                    }
                    $ring = &$coordinates[0];
                    if ($ring[0] != end($ring)) {
                        $ring[] = $ring[0];
                    }
                }

                $features[] = [
                    "type" => "Feature",
                    "properties" => [
                        "gisid"  => $building->gisid,
                        "sqfeet" => $building->sqfeet,
                    ],
                    "geometry" => [
                        "type" => $building->type,
                        "coordinates" => $coordinates
                    ]
                ];
            }

            $geojson = [
                "type" => "FeatureCollection",
                "features" => $features
            ];

            return response()->streamDownload(function () use ($geojson) {
                echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }, "missing_buildings_{$ward_id}.geojson", [
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

    public function exportAllPolygons($ward_id)
    {
        try {
            $polygonTable = "polygons_" . $ward_id;

            if (!Schema::hasTable($polygonTable)) {
                return response()->json([
                    "success" => false,
                    "message" => "Polygon table not found for ward #{$ward_id}"
                ], 404);
            }

            $allBuildings = DB::table($polygonTable)->get();

            if ($allBuildings->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No polygons found for ward #{$ward_id}"
                ], 404);
            }

            $features = [];

            foreach ($allBuildings as $building) {
                $coordinates = json_decode($building->coordinates, true);
                if (!$coordinates) continue;

                if (($building->type ?? 'Polygon') === 'Polygon') {
                    if (isset($coordinates[0][0]) && is_numeric($coordinates[0][0])) {
                        $coordinates = [$coordinates];
                    }

                    if (isset($coordinates[0]) && is_array($coordinates[0])) {
                        $ring = &$coordinates[0];
                        if (!empty($ring)) {
                            $first = $ring[0];
                            $last  = $ring[count($ring) - 1];
                            if ($first !== $last) {
                                $ring[] = $first;
                            }
                        }
                    }
                }

                $features[] = [
                    "type" => "Feature",
                    "properties" => [
                        "gisid"  => $building->gisid,
                        "sqfeet" => $building->sqfeet,
                        "type"   => $building->type ?? 'Polygon',
                    ],
                    "geometry" => [
                        "type" => $building->type ?? 'Polygon',
                        "coordinates" => $coordinates
                    ]
                ];
            }

            $geojson = [
                "type" => "FeatureCollection",
                "crs" => [
                    "type" => "name",
                    "properties" => [
                        "name" => "urn:ogc:def:crs:EPSG::32644"
                    ]
                ],
                "features" => $features
            ];

            $fileName = "all_polygons_ward_{$ward_id}.geojson";

            return response()->streamDownload(
                function () use ($geojson) {
                    echo json_encode(
                        $geojson,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_UNESCAPED_UNICODE |
                            JSON_PRESERVE_ZERO_FRACTION
                    );
                },
                $fileName,
                [
                    'Content-Type' => 'application/geo+json',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]
            );
        } catch (\Throwable $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage(),
                "line"    => $e->getLine(),
                "file"    => $e->getFile(),
            ], 500);
        }
    }

    public function exportAllRoads($ward_id)
    {
        try {
            $lineTable = "lines_" . $ward_id;

            if (!Schema::hasTable($lineTable)) {
                return response()->json([
                    "success" => false,
                    "message" => "Line table not found for ward #{$ward_id}"
                ], 404);
            }

            $allLines = DB::table($lineTable)->get();

            if ($allLines->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No lines found for ward #{$ward_id}"
                ], 404);
            }

            $features = [];

            foreach ($allLines as $line) {
                $coordinates = json_decode($line->coordinates, true);
                if (!$coordinates) continue;

                if ($line->type == 'LineString') {
                    if (!isset($coordinates[0][0]) || !is_numeric($coordinates[0][0])) {
                        if (isset($coordinates[0]) && is_array($coordinates[0]) && isset($coordinates[0][0]) && is_numeric($coordinates[0][0])) {
                        } else {
                            continue;
                        }
                    }
                }

                $features[] = [
                    "type" => "Feature",
                    "properties" => [
                        "gisid" => $line->gisid,
                        "type" => $line->type ?? 'LineString',
                        "road_name" => $line->road_name ?? null,
                        "pincode" => $line->pincode ?? null,
                    ],
                    "geometry" => [
                        "type" => $line->type ?? 'LineString',
                        "coordinates" => $coordinates
                    ]
                ];
            }

            $geojson = [
                "type" => "FeatureCollection",
                "features" => $features
            ];

            $fileName = "all_lines_ward_{$ward_id}.geojson";

            return response()->streamDownload(function () use ($geojson) {
                echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }, $fileName, [
                'Content-Type' => 'application/geo+json',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
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

    /* =========================================================
     *  CSV / EXCEL EXPORTS
     * ========================================================= */

    public function missingBuiildingExcel($ward_id)
    {
        try {
            $polygonDataTable = "polygon_data_" . $ward_id;
            $polygonTable     = "polygons_" . $ward_id;

            $missingBuildings = DB::table($polygonTable)
                ->whereNotIn('gisid', function ($query) use ($polygonDataTable) {
                    $query->select('gisid')->from($polygonDataTable);
                })
                ->get();

            $fileName = "missing_buildings_{$ward_id}.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$fileName}",
            ];

            $callback = function () use ($missingBuildings) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['GISID', 'Type', 'SqFeet']);
                foreach ($missingBuildings as $building) {
                    fputcsv($file, [$building->gisid, $building->type, $building->sqfeet]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ], 500);
        }
    }

    public function missingBillExcel(Request $request, $ward_id)
    {
        try {
            $roadName = $request->query('road_name');

            $ward = Ward::findOrFail($ward_id);
            $zone = Zone::findOrFail($ward->zone_id);

            $misTable = 'mis_' . $zone->corp_id;
            $pointDataTable = 'point_data_' . $ward_id;

            $query = DB::table($misTable)->where('ward_no', $ward->ward_no);

            if ($roadName && strtolower($roadName) != 'all') {
                $query->where('road_name', $roadName);
            }

            $missingbill = $query
                ->whereNotIn('assessment', function ($subQuery) use ($pointDataTable) {
                    $subQuery->select('assessment')->from($pointDataTable);
                })
                ->get();

            if ($missingbill->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No missing records found'
                ], 404);
            }

            $fileName = (!empty($roadName) && strtolower($roadName) !== 'all')
                ? "{$roadName}_missing_{$ward_id}.xlsx"
                : "missing_bill_{$ward_id}.xlsx";

            return Excel::download(new MissingBillExport($missingbill), $fileName);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function misBillExcel(Request $request, $ward_id)
    {
        try {
            $roadName = $request->query('road_name');

            $ward = Ward::findOrFail($ward_id);
            $zone = Zone::findOrFail($ward->zone_id);

            $misTable = 'mis_' . $zone->corp_id;

            $query = DB::table($misTable)->where('ward_no', $ward->ward_no);

            if (!empty($roadName) && strtolower($roadName) !== 'all') {
                $query->where('road_name', $roadName);
            }
            $data = $query->get();

            $fileName = (!empty($roadName) && strtolower($roadName) !== 'all')
                ? "{$roadName}_{$ward_id}.xlsx"
                : "mis_bill_{$ward_id}.xlsx";

            return Excel::download(new MissingBillExport($data), $fileName);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function ugdTaxExcel(Request $request, $ward_id)
    {
        try {
            $roadName = $request->query('road_name');

            $ward = Ward::findOrFail($ward_id);
            $zone = Zone::findOrFail($ward->zone_id);

            $ugdTable = 'ugd_tax_' . $zone->corp_id;

            $query = DB::table($ugdTable)->where('ward_no', $ward->ward_no);

            if (!empty($roadName) && strtolower($roadName) !== 'all') {
                $query->where('road_name', $roadName);
            }
            $data = $query->get();
            $fileName = (!empty($roadName) && strtolower($roadName) !== 'all')
                ? "{$roadName}_{$ward_id}.xlsx"
                : "ugd_tax_{$ward_id}.xlsx";

            return Excel::download(new MissingBillExport($data), $fileName);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function professionalTaxExcel(Request $request, $ward_id)
    {
        try {
            $roadName = $request->query('road_name');

            $ward = Ward::findOrFail($ward_id);
            $zone = Zone::findOrFail($ward->zone_id);

            $professionalTable = 'professional_tax_' . $zone->corp_id;

            $query = DB::table($professionalTable)->where('ward_no', $ward->ward_no);

            if (!empty($roadName) && strtolower($roadName) !== 'all') {
                $query->where('road_name', $roadName);
            }
            $data = $query->get();
            $fileName = (!empty($roadName) && strtolower($roadName) !== 'all')
                ? "{$roadName}_{$ward_id}.xlsx"
                : "professional_tax_{$ward_id}.xlsx";

            return Excel::download(new MissingBillExport($data), $fileName);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function waterTaxExcel(Request $request, $ward_id)
    {
        try {
            $roadName = $request->query('road_name');

            $ward = Ward::findOrFail($ward_id);
            $zone = Zone::findOrFail($ward->zone_id);

            $waterTable = 'water_tax_' . $zone->corp_id;

            $query = DB::table($waterTable)->where('ward_no', $ward->ward_no);

            if (!empty($roadName) && strtolower($roadName) !== 'all') {
                $query->where('road_name', $roadName);
            }
            $data = $query->get();

            $fileName = (!empty($roadName) && strtolower($roadName) !== 'all')
                ? "{$roadName}_{$ward_id}.xlsx"
                : "water_tax_{$ward_id}.xlsx";

            return Excel::download(new MissingBillExport($data), $fileName);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    /* =========================================================
     *  PDF EXPORTS
     * ========================================================= */

    public function missingBillPdf(Request $request, $ward_id)
    {
        try {
            $roadName = $request->query('road_name');

            $ward = Ward::findOrFail($ward_id);
            $zone = Zone::findOrFail($ward->zone_id);

            $misTable = 'mis_' . $zone->corp_id;
            $pointDataTable = 'point_data_' . $ward_id;

            $query = DB::table($misTable)->where('ward_no', $ward->ward_no);

            if ($roadName && strtolower($roadName) != 'all') {
                $query->where('road_name', $roadName);
            }

            $missingbill = $query
                ->whereNotIn('assessment', function ($subQuery) use ($pointDataTable) {
                    $subQuery->select('assessment')->from($pointDataTable);
                })
                ->get();

            if ($missingbill->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No missing records found.',
                ], 404);
            }

            $pdf = Pdf::loadView('exports.missing_bill_pdf', [
                'missingbill' => $missingbill,
                'ward'        => $ward,
                'roadName'    => $roadName,
            ])->setPaper('a4', 'landscape');

            $fileName = "missing_bill_{$ward_id}_" .
                str_replace(['.', ' '], '_', $roadName ?? 'all') .
                ".pdf";

            return $pdf->download($fileName);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Export point data PDF.
     * One image per GISID (fetched from polygon_data_{ward_id}).
     */
    public function exportPointDataPdf($ward_id)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        try {
            $pointTable   = "point_data_" . $ward_id;
            $polygonTable = "polygon_data_" . $ward_id;

            Log::info('[PDF] start', ['ward_id' => $ward_id]);

            if (!Schema::hasTable($pointTable)) {
                return response()->json([
                    "success" => false,
                    "message" => "Point data table not found for ward #{$ward_id}"
                ], 404);
            }

            // ---- 1. Detect gisid column in point table ----
            $pointColumns = Schema::getColumnListing($pointTable);
            $gisidColumn  = in_array('point_gisid', $pointColumns, true)
                ? 'point_gisid'
                : (in_array('gisid', $pointColumns, true) ? 'gisid' : null);

            if (!$gisidColumn) {
                return response()->json([
                    "success" => false,
                    "message" => "Neither point_gisid nor gisid column found in {$pointTable}",
                    "columns" => $pointColumns,
                ], 500);
            }

            $pointGisids = DB::table($pointTable)
                ->select($gisidColumn)
                ->whereNotNull($gisidColumn)
                ->where($gisidColumn, '!=', '')
                ->distinct()
                ->pluck($gisidColumn)
                ->toArray();

            Log::info('[PDF] gisids fetched', [
                'column' => $gisidColumn,
                'count'  => count($pointGisids),
            ]);

            if (empty($pointGisids)) {
                return response()->json([
                    "success" => false,
                    "message" => "No point data found for ward #{$ward_id}"
                ], 404);
            }

            // ---- 2. Detect image column in polygon table ----
            $imageColumn = null;
            $polyColumns = [];

            if (Schema::hasTable($polygonTable)) {
                $polyColumns = Schema::getColumnListing($polygonTable);
                $imageColumn = $this->detectImageColumnFromList($polyColumns);
            }

            Log::info('[PDF] polygon inspect', [
                'table'        => $polygonTable,
                'columns'      => $polyColumns,
                'image_column' => $imageColumn,
            ]);

            // ---- 3. Build gisid -> base64 image map ----
            $imageMap = [];

            if ($imageColumn && in_array('gisid', $polyColumns, true)) {
                $rows = DB::table($polygonTable)
                    ->select('gisid', $imageColumn)
                    ->whereIn('gisid', $pointGisids)
                    ->whereNotNull($imageColumn)
                    ->where($imageColumn, '!=', '')
                    ->get();

                Log::info('[PDF] polygon rows matched', ['count' => $rows->count()]);

                $sample = [];

                foreach ($rows as $row) {
                    $gisid     = $row->gisid;
                    $imagePath = $row->{$imageColumn};

                    if (!$gisid || !$imagePath) continue;

                    $absolute = $this->resolveAssetPath($imagePath);

                    if (count($sample) < 3) {
                        $sample[] = [
                            'gisid'      => $gisid,
                            'imagePath'  => $imagePath,
                            'absolute'   => $absolute,
                            'file_exist' => $absolute ? is_file($absolute) : false,
                        ];
                    }

                    if ($absolute && is_file($absolute)) {
                        try {
                            $imageMap[$gisid] = $this->buildBase64Image($absolute);
                        } catch (\Throwable $imgErr) {
                            Log::warning('[PDF] image build failed', [
                                'gisid' => $gisid,
                                'error' => $imgErr->getMessage(),
                            ]);
                        }
                    }
                }

                Log::info('[PDF] image map built', [
                    'images_ok' => count($imageMap),
                    'sample'    => $sample,
                ]);
            }

            // ---- 4. Prepare items, cap count ----
            $maxImages = 300;
            $items = [];
            foreach ($pointGisids as $gisid) {
                $items[] = [
                    'gisid' => $gisid,
                    'image' => $imageMap[$gisid] ?? null,
                ];
            }
            $items = array_slice($items, 0, $maxImages);

            Log::info('[PDF] items prepared', ['count' => count($items)]);

            // ---- 5. Render PDF ----
            $pdf = Pdf::loadView('exports.point_data_pdf', [
                'ward_id' => $ward_id,
                'items'   => $items,

                // Backward-compat
                'gisids'             => array_map(fn($i) => $i['gisid'], $items),
                'polygonImageBase64' => $imageMap ? reset($imageMap) : null,
            ])
                ->setPaper('a4', 'portrait')
                ->set_option('isRemoteEnabled', true)
                ->set_option('isHtml5ParserEnabled', true)
                ->set_option('defaultFont', 'DejaVu Sans');

            $fileName = "point_data_ward_{$ward_id}.pdf";

            Log::info('[PDF] rendering done', ['ward_id' => $ward_id]);

            return $pdf->download($fileName);
        } catch (\Throwable $e) {
            Log::error('[PDF] FAILED', [
                'ward_id' => $ward_id,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                "success" => false,
                "message" => $e->getMessage(),
                "line"    => $e->getLine(),
                "file"    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Export ALL Point Data fields as CSV.
     */
    public function exportPointDataExcel($ward_id)
    {
        try {
            $table = "point_data_" . $ward_id;

            if (!Schema::hasTable($table)) {
                return response()->json([
                    "success" => false,
                    "message" => "Point data table not found for ward #{$ward_id}"
                ], 404);
            }

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No point data found for ward #{$ward_id}"
                ], 404);
            }

            $columns = Schema::getColumnListing($table);
            $fileName = "point_data_ward_{$ward_id}.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$fileName}",
            ];

            $callback = function () use ($rows, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $line = [];
                    foreach ($columns as $col) {
                        $value = $rowArray[$col] ?? '';
                        if (is_array($value) || is_object($value)) {
                            $value = json_encode($value);
                        }
                        $line[] = $value;
                    }
                    fputcsv($file, $line);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage(),
                "line"    => $e->getLine(),
                "file"    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Export ALL Building (Polygon) Data as GeoJSON.
     */
    public function exportBuildingData($ward_id)
    {
        try {
            $table = "polygon_data_" . $ward_id;

            if (!Schema::hasTable($table)) {
                return response()->json([
                    "success" => false,
                    "message" => "Polygon table not found for ward #{$ward_id}"
                ], 404);
            }

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No polygon data found for ward #{$ward_id}"
                ], 404);
            }

            $columns = Schema::getColumnListing($table);
            $features = [];

            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $coordinates = json_decode($row->coordinates ?? '[]', true);
                if (!$coordinates) continue;

                $type = $row->type ?? 'Polygon';

                if ($type === 'Polygon') {
                    if (isset($coordinates[0][0]) && is_numeric($coordinates[0][0])) {
                        $coordinates = [$coordinates];
                    }
                    if (isset($coordinates[0]) && is_array($coordinates[0])) {
                        $ring = &$coordinates[0];
                        if (!empty($ring) && $ring[0] != end($ring)) {
                            $ring[] = $ring[0];
                        }
                    }
                }

                $properties = [];
                foreach ($columns as $col) {
                    if ($col === 'coordinates') continue;
                    $value = $rowArray[$col] ?? null;
                    if (is_string($value) && strlen($value) > 5000) {
                        $value = substr($value, 0, 5000) . '...[truncated]';
                    }
                    $properties[$col] = $value;
                }

                $features[] = [
                    "type" => "Feature",
                    "properties" => $properties,
                    "geometry" => [
                        "type" => $type,
                        "coordinates" => $coordinates
                    ]
                ];
            }

            $geojson = [
                "type" => "FeatureCollection",
                "features" => $features
            ];

            $fileName = "building_data_ward_{$ward_id}.geojson";

            return response()->streamDownload(function () use ($geojson) {
                echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }, $fileName, [
                'Content-Type' => 'application/geo+json',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
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

    /**
     * Export ALL Building (Polygon) Data as CSV.
     */
    public function exportBuildingDataExcel($ward_id)
    {
        try {
            $table = "polygon_data_" . $ward_id;

            if (!Schema::hasTable($table)) {
                return response()->json([
                    "success" => false,
                    "message" => "Polygon table not found for ward #{$ward_id}"
                ], 404);
            }

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No polygon data found for ward #{$ward_id}"
                ], 404);
            }

            $columns = Schema::getColumnListing($table);
            $fileName = "building_data_ward_{$ward_id}.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$fileName}",
            ];

            $callback = function () use ($rows, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $line = [];
                    foreach ($columns as $col) {
                        $value = $rowArray[$col] ?? '';
                        if (is_array($value) || is_object($value)) {
                            $value = json_encode($value);
                        }
                        $line[] = $value;
                    }
                    fputcsv($file, $line);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage(),
                "line"    => $e->getLine(),
                "file"    => $e->getFile(),
            ], 500);
        }
    }

    /* =========================================================
     *  PRIVATE HELPERS
     * ========================================================= */

    /**
     * Detect the image column from a list of column names.
     */
    private function detectImageColumnFromList(array $columns): ?string
    {
        if (empty($columns)) {
            return null;
        }

        $priority = [
            'image', 'image1', 'images', 'image_path', 'imagepath',
            'photo', 'snapshot', 'drone_image', 'asset_image', 'img',
            'building_image', 'buildingimage',
        ];

        $lowerMap = [];
        foreach ($columns as $c) {
            $lowerMap[strtolower($c)] = $c;
        }

        foreach ($priority as $p) {
            if (isset($lowerMap[$p])) {
                return $lowerMap[$p];
            }
        }

        foreach ($lowerMap as $lower => $original) {
            if (str_contains($lower, 'image')) {
                return $original;
            }
        }

        return null;
    }

    /**
     * Detect image column directly from a table name (with caching).
     */
    private function detectImageColumn(string $table): ?string
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        $columns = $this->getTableColumns($table);
        return $cache[$table] = $this->detectImageColumnFromList($columns);
    }

    /**
     * Build a base64-encoded JPEG (downscaled) from a local file.
     */
    private function buildBase64Image(string $absolutePath): ?string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $mime = $this->safeMimeType($absolutePath);

        // GD unavailable -> raw base64
        if (!function_exists('imagecreatefromstring')) {
            $raw = @file_get_contents($absolutePath);
            if ($raw === false) return null;
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $raw = @file_get_contents($absolutePath);
        if ($raw === false) return null;

        $img = @imagecreatefromstring($raw);
        if (!$img) {
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if ($w <= 0 || $h <= 0) {
            imagedestroy($img);
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $maxW = 800;

        if ($w > $maxW) {
            $newW  = $maxW;
            $newH  = (int) max(1, floor($h * ($maxW / $w)));
            $thumb = imagecreatetruecolor($newW, $newH);

            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefill($thumb, 0, 0, $white);

            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);

            $level = ob_get_level();
            ob_start();
            imagejpeg($thumb, null, 65);
            $compressed = ob_get_clean();
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            imagedestroy($thumb);
            imagedestroy($img);

            if ($compressed === false || $compressed === '') {
                return 'data:' . $mime . ';base64,' . base64_encode($raw);
            }

            return 'data:image/jpeg;base64,' . base64_encode($compressed);
        }

        $level = ob_get_level();
        ob_start();
        imagejpeg($img, null, 75);
        $compressed = ob_get_clean();
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        imagedestroy($img);

        if ($compressed === false || $compressed === '') {
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        return 'data:image/jpeg;base64,' . base64_encode($compressed);
    }

    /**
     * Safely get column list for a dynamic table.
     */
    private function getTableColumns(string $table): array
    {
        try {
            $cols = Schema::getColumnListing($table);
            if (!empty($cols)) {
                return $cols;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        try {
            $connection = DB::connection()->getDatabaseName();

            $result = DB::select(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [$connection, $table]
            );

            if (!empty($result)) {
                return array_map(fn($r) => $r->COLUMN_NAME, $result);
            }
        } catch (\Throwable $e) {
            // fall through
        }

        try {
            $first = DB::table($table)->first();
            if ($first) {
                return array_keys((array) $first);
            }
        } catch (\Throwable $e) {
            // give up
        }

        return [];
    }

    /**
     * Safe mime type lookup (no dependency on fileinfo extension).
     */
    private function safeMimeType(string $file): string
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($file);
            if ($mime) {
                return $mime;
            }
        }

        if (function_exists('getimagesize')) {
            $info = @getimagesize($file);
            if (!empty($info['mime'])) {
                return $info['mime'];
            }
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };
    }

    /**
     * Resolve a stored image path to an absolute filesystem path.
     */
    private function resolveAssetPath(string $path): ?string
    {
        if (empty($path)) return null;

        $clean = ltrim($path, '/');

        $candidates = [
            $path,
            public_path($clean),
            base_path($clean),
            base_path('public/' . $clean),
            storage_path('app/public/' . $clean),
            storage_path('app/' . $clean),
            public_path('storage/' . $clean),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate) && is_readable($candidate)) {
                return realpath($candidate);
            }
        }

        return null;
    }

    /**
     * Return first non-null value from a row for any of the given keys.
     */
    protected function firstExisting(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                return $row[$key];
            }
        }
        return null;
    }
}
