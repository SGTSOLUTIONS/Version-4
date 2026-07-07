<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Imports\MisImport;
use App\Imports\ProfessionalTaxImport;
use App\Imports\UgdTaxImport;
use App\Imports\WaterTaxImport;
use App\Models\Corporation;
use App\Services\CorporationService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CorporationController extends Controller
{
    protected CorporationService $corporationService;

    public function __construct(
        CorporationService $corporationService,

    ) {
        $this->corporationService = $corporationService;
    }

    public function index()
    {
        return view('main.admin.corporation');
    }

    public function list(Request $request)
    {
        try {
            $user = auth()->user();

            $query = Corporation::query()
                ->select('corporations.*')
                ->selectRaw('ST_AsGeoJSON(boundary) as boundary_geojson');

            // Commissioner can see only their corporation
            if ($user->role == 'commissioner') {
                $query->where('id', $user->corporation_id);
            }

            // Admin can use filters
            if ($request->filled('corp_name')) {
                $query->where('name', 'like', '%' . $request->corp_name . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $corporations = $query->latest()->paginate(12);

            return response()->json([
                'status' => true,
                'data'   => $corporations,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to load corporations: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================================
    // GeoJSON -> WKT helpers (boundary column is GEOMETRY, so it can only
    // accept WKT/WKB via ST_GeomFromText, never raw JSON text)
    // =====================================================================

    private function extractGeoJsonGeometry($file): array
    {
        $geojsonData = json_decode(file_get_contents($file->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Boundary file is not valid JSON.');
        }

        $geometry = $geojsonData['features'][0]['geometry'] ?? null;

        if (!$geometry || !isset($geometry['type'], $geometry['coordinates'])) {
            throw new \Exception('Invalid GeoJSON format.');
        }

        return $geometry;
    }

    private function geoJsonToWkt(array $geometry): string
    {
        $type = $geometry['type'] ?? null;
        $coords = $geometry['coordinates'] ?? null;

        if (!$type || !is_array($coords)) {
            throw new \Exception('Unsupported or malformed geometry.');
        }

        return match ($type) {
            'Point'           => 'POINT(' . $this->pointToWkt($coords) . ')',
            'LineString'      => 'LINESTRING(' . $this->lineToWkt($coords) . ')',
            'Polygon'         => 'POLYGON(' . $this->polygonToWkt($coords) . ')',
            'MultiPoint'      => 'MULTIPOINT(' . $this->multiPointToWkt($coords) . ')',
            'MultiLineString' => 'MULTILINESTRING(' . $this->multiLineToWkt($coords) . ')',
            'MultiPolygon'    => 'MULTIPOLYGON(' . $this->multiPolygonToWkt($coords) . ')',
            default           => throw new \Exception("Unsupported geometry type: {$type}"),
        };
    }

    private function pointToWkt(array $point): string
    {
        return (float) $point[0] . ' ' . (float) $point[1];
    }

    private function lineToWkt(array $points): string
    {
        return implode(',', array_map(fn($p) => $this->pointToWkt($p), $points));
    }

    private function polygonToWkt(array $rings): string
    {
        return implode(',', array_map(fn($ring) => '(' . $this->lineToWkt($ring) . ')', $rings));
    }

    private function multiPointToWkt(array $points): string
    {
        return implode(',', array_map(fn($p) => '(' . $this->pointToWkt($p) . ')', $points));
    }

    private function multiLineToWkt(array $lines): string
    {
        return implode(',', array_map(fn($line) => '(' . $this->lineToWkt($line) . ')', $lines));
    }

    private function multiPolygonToWkt(array $polygons): string
    {
        return implode(',', array_map(fn($polygon) => '(' . $this->polygonToWkt($polygon) . ')', $polygons));
    }

    /**
     * Write WKT into the GEOMETRY column via raw SQL. SRID 0 is used so MySQL
     * does not enforce lon/lat range validation (these coordinates are
     * projected meters, not degrees).
     */
    private function saveBoundary(int $corporationId, string $wkt): void
    {
        DB::statement(
            'UPDATE corporations SET boundary = ST_GeomFromText(?, 0) WHERE id = ?',
            [$wkt, $corporationId]
        );
    }

    /**
     * Fetch a corporation with boundary safely converted to GeoJSON text,
     * instead of raw binary — safe to pass to response()->json().
     */
    private function findWithGeoJson(int $id): Corporation
    {
        return Corporation::query()
            ->select('corporations.*')
            ->selectRaw('ST_AsGeoJSON(boundary) as boundary_geojson')
            ->findOrFail($id);
    }

    // =====================================================================
    // CRUD
    // =====================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'code'                  => 'required|string|max:100|unique:corporations,code',
            'state'                 => 'required|string|max:255',
            'district'              => 'required|string|max:255',
            'pincode'               => 'required|string|max:20',
            'status'                => 'required|string|max:50',
            'description'           => 'required|string',
            'image'                 => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'boundary_file'         => 'required|file',
            'mis_file'              => 'nullable|file|mimes:xlsx,xls,csv',
            'water_tax_file'        => 'nullable|file|mimes:xlsx,xls,csv',
            'ugd_tax_file'          => 'nullable|file|mimes:xlsx,xls,csv',
            'professional_tax_file' => 'nullable|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $profileImagePath = $request->hasFile('image')
                ? CommonHelper::uploadProfileImage($request->file('image'), 'corporation/profile')
                : 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&background=1679AB&color=fff';

            $corporation = Corporation::create([
                'name'        => $request->name,
                'code'        => $request->code,
                'state'       => $request->state,
                'district'    => $request->district,
                'pincode'     => $request->pincode,
                'status'      => $request->status,
                'description' => $request->description,
                'image'       => $profileImagePath,
            ]);

            if ($request->hasFile('boundary_file')) {
                $geometry = $this->extractGeoJsonGeometry($request->file('boundary_file'));
                $wkt = $this->geoJsonToWkt($geometry);
                $this->saveBoundary($corporation->id, $wkt);
            }

            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if (!$createTable) {
                throw new \Exception('Corporation tables could not be created.');
            }

            $importStats = [];

            if ($request->hasFile('mis_file')) {
                $misImport = new MisImport($corporation->id);
                Excel::import($misImport, $request->file('mis_file'));
                $importStats['mis'] = $misImport->getStats();
            }

            if ($request->hasFile('water_tax_file')) {
                $waterTaxImport = new WaterTaxImport($corporation->id);
                Excel::import($waterTaxImport, $request->file('water_tax_file'));
                $importStats['water_tax'] = method_exists($waterTaxImport, 'getStats')
                    ? $waterTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            if ($request->hasFile('ugd_tax_file')) {
                $ugdTaxImport = new UgdTaxImport($corporation->id);
                Excel::import($ugdTaxImport, $request->file('ugd_tax_file'));
                $importStats['ugd_tax'] = method_exists($ugdTaxImport, 'getStats')
                    ? $ugdTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            if ($request->hasFile('professional_tax_file')) {
                $professionalTaxImport = new ProfessionalTaxImport($corporation->id);
                Excel::import($professionalTaxImport, $request->file('professional_tax_file'));
                $importStats['professional_tax'] = method_exists($professionalTaxImport, 'getStats')
                    ? $professionalTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            DB::commit();

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation created successfully with data imports.',
                'data'         => $this->findWithGeoJson($corporation->id),
                'import_stats' => $importStats,
            ]);
        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function show(Corporation $corporation)
    {
        try {
            return response()->json([
                'status' => true,
                'data'   => $this->findWithGeoJson($corporation->id),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to load corporation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Corporation $corporation)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'code'                  => 'required|string|max:100|unique:corporations,code,' . $corporation->id,
            'state'                 => 'required|string|max:255',
            'district'              => 'required|string|max:255',
            'pincode'               => 'required|string|max:20',
            'status'                => 'required|string|max:50',
            'description'           => 'required|string',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'boundary_file'         => 'nullable|file',
            'mis_file'              => 'nullable|file|mimes:xlsx,xls,csv',
            'water_tax_file'        => 'nullable|file|mimes:xlsx,xls,csv',
            'ugd_tax_file'          => 'nullable|file|mimes:xlsx,xls,csv',
            'professional_tax_file' => 'nullable|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                if ($corporation->image && !str_starts_with($corporation->image, 'http')) {
                    Storage::disk('public')->delete($corporation->image);
                }

                $corporation->image = CommonHelper::uploadProfileImage(
                    $request->file('image'),
                    'corporation/profile'
                );
            }

            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if ($request->hasFile('boundary_file')) {
                $geometry = $this->extractGeoJsonGeometry($request->file('boundary_file'));
                $wkt = $this->geoJsonToWkt($geometry);
                $this->saveBoundary($corporation->id, $wkt);
            }

            $corporation->name = $request->name;
            $corporation->code = $request->code;
            $corporation->state = $request->state;
            $corporation->district = $request->district;
            $corporation->pincode = $request->pincode;
            $corporation->status = $request->status;
            $corporation->description = $request->description;

            $corporation->save();

            $importStats = [];

            if ($request->hasFile('mis_file')) {
                $misImport = new MisImport($corporation->id);
                Excel::import($misImport, $request->file('mis_file'));
                $importStats['mis'] = $misImport->getStats();
            }

            if ($request->hasFile('water_tax_file')) {
                $waterTaxImport = new WaterTaxImport($corporation->id);
                Excel::import($waterTaxImport, $request->file('water_tax_file'));
                $importStats['water_tax'] = method_exists($waterTaxImport, 'getStats')
                    ? $waterTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            if ($request->hasFile('ugd_tax_file')) {
                $ugdTaxImport = new UgdTaxImport($corporation->id);
                Excel::import($ugdTaxImport, $request->file('ugd_tax_file'));
                $importStats['ugd_tax'] = method_exists($ugdTaxImport, 'getStats')
                    ? $ugdTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            if ($request->hasFile('professional_tax_file')) {
                $professionalTaxImport = new ProfessionalTaxImport($corporation->id);
                Excel::import($professionalTaxImport, $request->file('professional_tax_file'));
                $importStats['professional_tax'] = method_exists($professionalTaxImport, 'getStats')
                    ? $professionalTaxImport->getStats()
                    : ['message' => 'Imported successfully'];
            }

            DB::commit();

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation updated successfully with data imports.',
                'data'         => $this->findWithGeoJson($corporation->id),
                'import_stats' => $importStats,
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            report($e);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Corporation $corporation)
    {
        try {
            $this->corporationService->dropCorporationTables($corporation->id);

            DB::beginTransaction();

            if ($corporation->image && !str_starts_with($corporation->image, 'http')) {
                Storage::disk('public')->delete($corporation->image);
            }

            $corporation->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Corporation deleted successfully.',
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
    }
}
