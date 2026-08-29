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
use Illuminate\Support\Facades\Log;
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

            if ($user->role == 'commissioner') {
                $query->where('id', $user->corporation_id);
            }

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
     * Get import status for a specific corporation and type
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
                    'message' => 'Import completed successfully',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Check for error
            $error = Cache::get($errorKey);
            if ($error) {
                return response()->json([
                    'status' => 'failed',
                    'error' => $error,
                    'message' => 'Import failed',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Check for progress
            $progress = Cache::get($progressKey);
            if ($progress) {
                return response()->json([
                    'status' => 'processing',
                    'progress' => $progress,
                    'message' => 'Import in progress',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Check if queued
            $status = Cache::get($statusKey);
            if ($status === 'queued') {
                return response()->json([
                    'status' => 'queued',
                    'message' => 'Import is queued and waiting to be processed',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            return response()->json([
                'status' => 'not_started',
                'message' => 'No import has been started for this corporation',
                'timestamp' => now()->toDateTimeString()
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
            $hasActiveImport = false;

            foreach ($types as $type) {
                $statusKey = "{$type}_import_status_{$corporationId}";
                $progressKey = "{$type}_import_progress_{$corporationId}";
                $statsKey = "{$type}_import_final_stats_{$corporationId}";
                $errorKey = "{$type}_import_error_{$corporationId}";

                if (Cache::get($statsKey)) {
                    $statuses[$type] = [
                        'status' => 'completed',
                        'stats' => Cache::get($statsKey),
                        'timestamp' => now()->toDateTimeString()
                    ];
                } elseif (Cache::get($errorKey)) {
                    $statuses[$type] = [
                        'status' => 'failed',
                        'error' => Cache::get($errorKey),
                        'timestamp' => now()->toDateTimeString()
                    ];
                } elseif (Cache::get($progressKey)) {
                    $statuses[$type] = [
                        'status' => 'processing',
                        'progress' => Cache::get($progressKey),
                        'timestamp' => now()->toDateTimeString()
                    ];
                    $hasActiveImport = true;
                } elseif (Cache::get($statusKey) === 'queued') {
                    $statuses[$type] = [
                        'status' => 'queued',
                        'timestamp' => now()->toDateTimeString()
                    ];
                    $hasActiveImport = true;
                } else {
                    $statuses[$type] = [
                        'status' => 'not_started'
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data' => $statuses,
                'has_active_import' => $hasActiveImport,
                'corporation_id' => $corporationId,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear import status cache
     */
    public function clearImportStatus($corporationId, $type = null)
    {
        try {
            $types = $type ? [$type] : ['mis', 'water_tax', 'ugd_tax', 'professional_tax'];
            
            foreach ($types as $t) {
                Cache::forget("{$t}_import_status_{$corporationId}");
                Cache::forget("{$t}_import_progress_{$corporationId}");
                Cache::forget("{$t}_import_final_stats_{$corporationId}");
                Cache::forget("{$t}_import_error_{$corporationId}");
            }

            return response()->json([
                'status' => true,
                'message' => 'Import status cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // =====================================================================
    // GeoJSON -> WKT helpers
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

    private function saveBoundary(int $corporationId, string $wkt): void
    {
        DB::statement(
            'UPDATE corporations SET boundary = ST_GeomFromText(?, 0) WHERE id = ?',
            [$wkt, $corporationId]
        );
    }

    private function findWithGeoJson(int $id): Corporation
    {
        return Corporation::query()
            ->select('corporations.*')
            ->selectRaw('ST_AsGeoJSON(boundary) as boundary_geojson')
            ->findOrFail($id);
    }

    /**
     * Run imports synchronously (directly)
     */
    private function runImportsSync(Request $request, int $corporationId): array
    {
        $importStats = [];

        // Process MIS Import
        if ($request->hasFile('mis_file')) {
            try {
                Log::info("Starting MIS import for corporation: {$corporationId}");
                
                ini_set('memory_limit', '2048M');
                set_time_limit(0);
                
                $misImport = new MisImport($corporationId);
                Excel::import($misImport, $request->file('mis_file'));
                $importStats['mis'] = $misImport->getStats();
                
                Log::info("MIS import completed for corporation: {$corporationId}", $importStats['mis']);
            } catch (\Exception $e) {
                Log::error("MIS import failed: " . $e->getMessage());
                $importStats['mis'] = [
                    'error' => $e->getMessage(),
                    'inserted' => 0,
                    'updated' => 0,
                    'skipped' => 0
                ];
            }
        }

        // Process Water Tax Import
        if ($request->hasFile('water_tax_file')) {
            try {
                Log::info("Starting Water Tax import for corporation: {$corporationId}");
                
                $waterTaxImport = new WaterTaxImport($corporationId);
                Excel::import($waterTaxImport, $request->file('water_tax_file'));
                $importStats['water_tax'] = method_exists($waterTaxImport, 'getStats') 
                    ? $waterTaxImport->getStats() 
                    : ['message' => 'Imported successfully'];
                    
                Log::info("Water Tax import completed for corporation: {$corporationId}");
            } catch (\Exception $e) {
                Log::error("Water Tax import failed: " . $e->getMessage());
                $importStats['water_tax'] = ['error' => $e->getMessage()];
            }
        }

        // Process UGD Tax Import
        if ($request->hasFile('ugd_tax_file')) {
            try {
                Log::info("Starting UGD Tax import for corporation: {$corporationId}");
                
                $ugdTaxImport = new UgdTaxImport($corporationId);
                Excel::import($ugdTaxImport, $request->file('ugd_tax_file'));
                $importStats['ugd_tax'] = method_exists($ugdTaxImport, 'getStats') 
                    ? $ugdTaxImport->getStats() 
                    : ['message' => 'Imported successfully'];
                    
                Log::info("UGD Tax import completed for corporation: {$corporationId}");
            } catch (\Exception $e) {
                Log::error("UGD Tax import failed: " . $e->getMessage());
                $importStats['ugd_tax'] = ['error' => $e->getMessage()];
            }
        }

        // Process Professional Tax Import
        if ($request->hasFile('professional_tax_file')) {
            try {
                Log::info("Starting Professional Tax import for corporation: {$corporationId}");
                
                $professionalTaxImport = new ProfessionalTaxImport($corporationId);
                Excel::import($professionalTaxImport, $request->file('professional_tax_file'));
                $importStats['professional_tax'] = method_exists($professionalTaxImport, 'getStats') 
                    ? $professionalTaxImport->getStats() 
                    : ['message' => 'Imported successfully'];
                    
                Log::info("Professional Tax import completed for corporation: {$corporationId}");
            } catch (\Exception $e) {
                Log::error("Professional Tax import failed: " . $e->getMessage());
                $importStats['professional_tax'] = ['error' => $e->getMessage()];
            }
        }

        return $importStats;
    }

    /**
     * Process imports - tries queue first, falls back to sync
     */
    private function processImports(Request $request, int $corporationId): array
    {
        $importStats = [];
        $hasFiles = false;
        $queueAvailable = false;
        
        // Check if any files were uploaded
        if ($request->hasFile('mis_file') || $request->hasFile('water_tax_file') || 
            $request->hasFile('ugd_tax_file') || $request->hasFile('professional_tax_file')) {
            $hasFiles = true;
        }

        if (!$hasFiles) {
            return [
                'message' => 'No files were imported. The corporation was created without any data imports.',
                'no_files' => true
            ];
        }

        // Try to use queue
        try {
            if ($request->hasFile('mis_file')) {
                $path = $request->file('mis_file')->store('imports/mis');
                ProcessMisImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("mis_import_status_{$corporationId}", 'queued', 3600);
                $queueAvailable = true;
            }
            
            if ($request->hasFile('water_tax_file')) {
                $path = $request->file('water_tax_file')->store('imports/water_tax');
                ProcessWaterTaxImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("water_tax_import_status_{$corporationId}", 'queued', 3600);
                $queueAvailable = true;
            }
            
            if ($request->hasFile('ugd_tax_file')) {
                $path = $request->file('ugd_tax_file')->store('imports/ugd_tax');
                ProcessUgdTaxImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("ugd_tax_import_status_{$corporationId}", 'queued', 3600);
                $queueAvailable = true;
            }
            
            if ($request->hasFile('professional_tax_file')) {
                $path = $request->file('professional_tax_file')->store('imports/professional_tax');
                ProcessProfessionalTaxImport::dispatch($path, $corporationId)->onQueue('imports');
                Cache::put("professional_tax_import_status_{$corporationId}", 'queued', 3600);
                $queueAvailable = true;
            }
        } catch (\Exception $e) {
            Log::warning("Queue dispatch failed, falling back to sync: " . $e->getMessage());
            $queueAvailable = false;
        }

        // If queue failed or not available, run sync
        if (!$queueAvailable) {
            Log::info("Running imports synchronously for corporation: {$corporationId}");
            $importStats = $this->runImportsSync($request, $corporationId);
            
            // Store stats in cache
            foreach ($importStats as $type => $stats) {
                Cache::put("{$type}_import_final_stats_{$corporationId}", $stats, 86400);
                Cache::put("{$type}_import_status_{$corporationId}", 'completed', 86400);
            }
        } else {
            // Queue was successful
            $importStats = [
                'message' => 'Imports queued successfully. They will be processed in the background.',
                'queued' => true
            ];
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

            // Create tables
            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if (!$createTable) {
                throw new \Exception('Corporation was saved, but its data tables could not be created.');
            }

            // Process imports (sync or async)
            $importStats = $this->processImports($request, $corporation->id);

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation created successfully.',
                'data'         => $this->findWithGeoJson($corporation->id),
                'import_stats' => $importStats,
                'corporation_id' => $corporation->id,
                'status_check_url' => route('import.status', ['corporationId' => $corporation->id])
            ]);

        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error("Corporation creation failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
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
            $createTable = $this->corporationService->createCorporationTables($corporation->id);

            if (!$createTable) {
                throw new \Exception('Corporation data tables could not be created or verified.');
            }

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

            // Process imports
            $importStats = $this->processImports($request, $corporation->id);

            return response()->json([
                'status'       => true,
                'message'      => 'Corporation updated successfully.',
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