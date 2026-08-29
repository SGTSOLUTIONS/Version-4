<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Imports\MisImport;
use App\Imports\ProfessionalTaxImport;
use App\Imports\UgdTaxImport;
use App\Imports\WaterTaxImport;
use App\Models\Corporation;
use App\Services\CorporationService;
use App\Jobs\ProcessMisImport;
use App\Jobs\ProcessWaterTaxImport;
use App\Jobs\ProcessUgdTaxImport;
use App\Jobs\ProcessProfessionalTaxImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class CorporationController extends Controller
{
    protected CorporationService $corporationService;

    public function __construct(
        CorporationService $corporationService
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

    /**
     * Get import status for a corporation
     */
    public function getImportStatus($corporationId, $type = 'mis')
    {
        try {
            $statusKey = "{$type}_import_status_{$corporationId}";
            $progressKey = "{$type}_import_progress_{$corporationId}";
            $statsKey = "{$type}_import_final_stats_{$corporationId}";
            $errorKey = "{$type}_import_error_{$corporationId}";

            // Check if completed
            $stats = Cache::get($statsKey);
            if ($stats) {
                return response()->json([
                    'status' => 'completed',
                    'stats' => $stats,
                    'message' => 'Import completed successfully'
                ]);
            }

            // Check for error
            $error = Cache::get($errorKey);
            if ($error) {
                return response()->json([
                    'status' => 'failed',
                    'error' => $error,
                    'message' => 'Import failed'
                ]);
            }

            // Check for progress
            $progress = Cache::get($progressKey);
            if ($progress) {
                return response()->json([
                    'status' => 'processing',
                    'progress' => $progress,
                    'message' => 'Import in progress'
                ]);
            }

            // Check if queued
            $status = Cache::get($statusKey);
            if ($status === 'queued') {
                return response()->json([
                    'status' => 'queued',
                    'message' => 'Import is queued and will start shortly'
                ]);
            }

            return response()->json([
                'status' => 'not_found',
                'message' => 'No import found for this corporation'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all import statuses for a corporation
     */
    public function getAllImportStatuses($corporationId)
    {
        try {
            $types = ['mis', 'water_tax', 'ugd_tax', 'professional_tax'];
            $statuses = [];

            foreach ($types as $type) {
                $statusKey = "{$type}_import_status_{$corporationId}";
                $progressKey = "{$type}_import_progress_{$corporationId}";
                $statsKey = "{$type}_import_final_stats_{$corporationId}";
                $errorKey = "{$type}_import_error_{$corporationId}";

                if (Cache::get($statsKey)) {
                    $statuses[$type] = [
                        'status' => 'completed',
                        'stats' => Cache::get($statsKey)
                    ];
                } elseif (Cache::get($errorKey)) {
                    $statuses[$type] = [
                        'status' => 'failed',
                        'error' => Cache::get($errorKey)
                    ];
                } elseif (Cache::get($progressKey)) {
                    $statuses[$type] = [
                        'status' => 'processing',
                        'progress' => Cache::get($progressKey)
                    ];
                } elseif (Cache::get($statusKey) === 'queued') {
                    $statuses[$type] = [
                        'status' => 'queued'
                    ];
                } else {
                    $statuses[$type] = [
                        'status' => 'not_started'
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data' => $statuses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
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

    /**
     * Process imports asynchronously using jobs (NO TIMEOUT ISSUE)
     */
    private function processImportsAsync(Request $request, int $corporationId): array
    {
        $importStats = [];

        // Process MIS Import
        if ($request->hasFile('mis_file')) {
            try {
                $path = $request->file('mis_file')->store('imports/mis');
                ProcessMisImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("mis_import_status_{$corporationId}", 'queued', 3600);
                $importStats['mis'] = [
                    'status' => 'queued',
                    'message' => 'MIS import queued for processing'
                ];
            } catch (\Exception $e) {
                $importStats['mis'] = [
                    'status' => 'error',
                    'message' => 'Failed to queue MIS import: ' . $e->getMessage()
                ];
            }
        }

        // Process Water Tax Import
        if ($request->hasFile('water_tax_file')) {
            try {
                $path = $request->file('water_tax_file')->store('imports/water_tax');
                ProcessWaterTaxImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("water_tax_import_status_{$corporationId}", 'queued', 3600);
                $importStats['water_tax'] = [
                    'status' => 'queued',
                    'message' => 'Water Tax import queued for processing'
                ];
            } catch (\Exception $e) {
                $importStats['water_tax'] = [
                    'status' => 'error',
                    'message' => 'Failed to queue Water Tax import: ' . $e->getMessage()
                ];
            }
        }

        // Process UGD Tax Import
        if ($request->hasFile('ugd_tax_file')) {
            try {
                $path = $request->file('ugd_tax_file')->store('imports/ugd_tax');
                ProcessUgdTaxImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("ugd_tax_import_status_{$corporationId}", 'queued', 3600);
                $importStats['ugd_tax'] = [
                    'status' => 'queued',
                    'message' => 'UGD Tax import queued for processing'
                ];
            } catch (\Exception $e) {
                $importStats['ugd_tax'] = [
                    'status' => 'error',
                    'message' => 'Failed to queue UGD Tax import: ' . $e->getMessage()
                ];
            }
        }

        // Process Professional Tax Import
        if ($request->hasFile('professional_tax_file')) {
            try {
                $path = $request->file('professional_tax_file')->store('imports/professional_tax');
                ProcessProfessionalTaxImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("professional_tax_import_status_{$corporationId}", 'queued', 3600);
                $importStats['professional_tax'] = [
                    'status' => 'queued',
                    'message' => 'Professional Tax import queued for processing'
                ];
            } catch (\Exception $e) {
                $importStats['professional_tax'] = [
                    'status' => 'error',
                    'message' => 'Failed to queue Professional Tax import: ' . $e->getMessage()
                ];
            }
        }

        return $importStats;
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
            'boundary_file'         => 'required|file|mimes:json,geojson',
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

        try {
            // --- Everything that is NOT safely transactional happens first,
            // OUTSIDE of DB::beginTransaction(). File uploads and GeoJSON
            // parsing can fail independently of the DB, and the boundary
            // file is only parsed here (not yet written) so nothing DB-side
            // has happened yet if this throws.
            $profileImagePath = $request->hasFile('image')
                ? CommonHelper::uploadProfileImage($request->file('image'), 'corporation/profile')
                : 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&background=1679AB&color=fff';

            $wkt = null;
            if ($request->hasFile('boundary_file')) {
                $geometry = $this->extractGeoJsonGeometry($request->file('boundary_file'));
                $wkt = $this->geoJsonToWkt($geometry);
            }

            DB::beginTransaction();

            $corporation = Corporation::create([
                'name'        => $request->name,
                'code'        => $request->code,
                'state'       => $request->state,
                'district'    => $request->district,
                'type'        => $request->type,
                'pincode'     => $request->pincode,
                'status'      => $request->status,
                'description' => $request->description,
                'image'       => $profileImagePath,
            ]);

            if ($wkt !== null) {
                $this->saveBoundary($corporation->id, $wkt);
            }

            DB::commit();

            // --- DDL runs AFTER the transaction is committed. CREATE TABLE
            // triggers an implicit commit in MySQL, so it can never safely
            // live inside a transaction anyway — running it after commit()
            // keeps Laravel's transaction bookkeeping and MySQL's actual
            // transaction state in sync.
            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if (!$createTable) {
                throw new \Exception('Corporation was saved, but its data tables could not be created.');
            }

            // Process imports asynchronously (NO TIMEOUT ISSUE)
            $importStats = $this->processImportsAsync($request, $corporation->id);

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation created successfully. Imports are being processed in the background.',
                'data'         => $this->findWithGeoJson($corporation->id),
                'import_stats' => $importStats,
                'corporation_id' => $corporation->id,
                'status_check_url' => '/import-status/' . $corporation->id
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
            'boundary_file'         => 'nullable|file|mimes:json,geojson',
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

        try {
            // --- DDL FIRST, OUTSIDE any transaction. This is the key fix:
            // CREATE TABLE causes MySQL to implicitly commit any open
            // transaction, which desyncs Laravel's transaction counter from
            // MySQL's actual state and produces "There is no active
            // transaction" on the later DB::commit() call. Running it here,
            // before beginTransaction(), avoids that entirely. This matters
            // most for corporations that were bulk-inserted directly via SQL
            // and never had their tables created through store().
            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if (!$createTable) {
                throw new \Exception('Corporation data tables could not be created or verified.');
            }

            // --- Parse (but don't yet persist) anything that can fail
            // independently of the DB, same reasoning as in store().
            $wkt = null;
            if ($request->hasFile('boundary_file')) {
                $geometry = $this->extractGeoJsonGeometry($request->file('boundary_file'));
                $wkt = $this->geoJsonToWkt($geometry);
            }

            $newImagePath = null;
            if ($request->hasFile('image')) {
                $newImagePath = CommonHelper::uploadProfileImage(
                    $request->file('image'),
                    'corporation/profile'
                );
            }

            DB::beginTransaction();

            if ($newImagePath !== null) {
                if ($corporation->image && !str_starts_with($corporation->image, 'http')) {
                    Storage::disk('public')->delete($corporation->image);
                }
                $corporation->image = $newImagePath;
            }

            if ($wkt !== null) {
                $this->saveBoundary($corporation->id, $wkt);
            }

            $corporation->name = $request->name;
            $corporation->code = $request->code;
            $corporation->state = $request->state;
            $corporation->district = $request->district;
            $corporation->pincode = $request->pincode;
            $corporation->status = $request->status;
            $corporation->description = $request->description;
            $corporation->type = $request->type;

            $corporation->save();

            DB::commit();

            // Process imports asynchronously
            $importStats = $this->processImportsAsync($request, $corporation->id);

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation updated successfully. Imports are being processed.',
                'data'         => $this->findWithGeoJson($corporation->id),
                'import_stats' => $importStats,
                'corporation_id' => $corporation->id
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
            // DDL (DROP TABLE) also happens outside any transaction, for the
            // same reason as above.
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
                // Ignore rollback errors
            }

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}